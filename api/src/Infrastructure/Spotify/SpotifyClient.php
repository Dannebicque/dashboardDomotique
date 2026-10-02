<?php

namespace App\Infrastructure\Spotify;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class SpotifyClient
{
    public function __construct(
        private HttpClientInterface $httpClient,
        #[Autowire('%env(string:SPOTIFY_CLIENT_ID)%')] private string $clientId,
        #[Autowire('%env(string:SPOTIFY_CLIENT_SECRET)%')] private string $clientSecret,
        #[Autowire('%env(string:SPOTIFY_REFRESH_TOKEN)%')] private string $refreshToken,
    ) {}

    public function isConfigured(): bool
    {
        return '' !== $this->clientId && '' !== $this->clientSecret && '' !== $this->refreshToken;
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
        $this->request($method, $path);
    }

    private function request(string $method, string $path): array
    {
        $response = $this->httpClient->request($method, 'https://api.spotify.com'.$path, ['auth_bearer' => $this->accessToken()]);
        return 204 === $response->getStatusCode() ? [] : $response->toArray(false);
    }

    private function accessToken(): string
    {
        if (!$this->isConfigured()) throw new \RuntimeException('Spotify is not configured.');

        $data = $this->httpClient->request('POST', 'https://accounts.spotify.com/api/token', [
            'auth_basic' => [$this->clientId, $this->clientSecret],
            'body' => ['grant_type' => 'refresh_token', 'refresh_token' => $this->refreshToken],
        ])->toArray();

        return (string) $data['access_token'];
    }
}
