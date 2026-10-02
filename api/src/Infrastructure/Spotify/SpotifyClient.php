<?php

namespace App\Infrastructure\Spotify;

use App\Infrastructure\OAuth\RefreshTokenStore;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class SpotifyClient
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private RefreshTokenStore $tokens,
        #[Autowire('%env(string:SPOTIFY_CLIENT_ID)%')] private string $clientId,
        #[Autowire('%env(string:SPOTIFY_CLIENT_SECRET)%')] private string $clientSecret,
    ) {}

    public function isConfigured(): bool
    {
        return '' !== $this->clientId && '' !== $this->clientSecret && null !== $this->tokens->get('spotify');
    }

    public function exchangeCode(string $code, string $redirectUri): void
    {
        $data = $this->httpClient->request('POST', 'https://accounts.spotify.com/api/token', [
            'auth_basic' => [$this->clientId, $this->clientSecret],
            'body' => ['grant_type' => 'authorization_code', 'code' => $code, 'redirect_uri' => $redirectUri],
        ])->toArray();

        $this->tokens->put('spotify', (string) $data['refresh_token']);
    }

    public function playback(): ?array
    {
        $response = $this->request('GET', '/v1/me/player');
        return [] === $response ? null : $response;
    }

    public function command(string $command): void
    {
        $method = in_array($command, ['play', 'pause'], true) ? 'PUT' : 'POST';
        $path = match ($command) {
            'play' => '/v1/me/player/play',
            'pause' => '/v1/me/player/pause',
            'next' => '/v1/me/player/next',
            'previous' => '/v1/me/player/previous',
            default => throw new \InvalidArgumentException('Unsupported Spotify command.'),
        };

        $deviceId = $this->playback()['device']['id'] ?? null;
        $query = is_string($deviceId) && '' !== $deviceId ? '?device_id='.rawurlencode($deviceId) : '';
        $this->request($method, $path.$query);
    }

    public function queue(): array
    {
        return $this->request('GET', '/v1/me/player/queue');
    }

    public function devices(): array
    {
        return $this->request('GET', '/v1/me/player/devices');
    }

    public function setVolume(int $volume): void
    {
        $this->request('PUT', '/v1/me/player/volume?volume_percent='.max(0, min(100, $volume)));
    }

    public function setShuffle(bool $enabled): void
    {
        $this->request('PUT', '/v1/me/player/shuffle?state='.($enabled ? 'true' : 'false'));
    }

    public function setRepeat(string $state): void
    {
        if (!in_array($state, ['off', 'context', 'track'], true)) {
            throw new \InvalidArgumentException('Unsupported repeat state.');
        }
        $this->request('PUT', '/v1/me/player/repeat?state='.$state);
    }

    public function transfer(string $deviceId): void
    {
        $this->request('PUT', '/v1/me/player', ['device_ids' => [$deviceId], 'play' => true]);
    }

    private function request(string $method, string $path, ?array $json = null): array
    {
        $refreshToken = $this->tokens->get('spotify');
        if (null === $refreshToken) throw new \RuntimeException('Spotify is not connected.');

        $data = $this->httpClient->request('POST', 'https://accounts.spotify.com/api/token', [
            'auth_basic' => [$this->clientId, $this->clientSecret],
            'body' => ['grant_type' => 'refresh_token', 'refresh_token' => $refreshToken],
        ])->toArray();

        if (isset($data['refresh_token'])) $this->tokens->put('spotify', (string) $data['refresh_token']);

        $options = ['auth_bearer' => (string) $data['access_token']];
        if (null !== $json) $options['json'] = $json;
        $response = $this->httpClient->request($method, 'https://api.spotify.com'.$path, $options);
        return 204 === $response->getStatusCode() ? [] : $response->toArray(false);
    }
}
