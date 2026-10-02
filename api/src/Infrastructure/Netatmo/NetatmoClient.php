<?php

namespace App\Infrastructure\Netatmo;

use App\Infrastructure\OAuth\RefreshTokenStore;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class NetatmoClient
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private RefreshTokenStore $tokens,
        #[Autowire('%env(string:NETATMO_CLIENT_ID)%')] private string $clientId,
        #[Autowire('%env(string:NETATMO_CLIENT_SECRET)%')] private string $clientSecret,
    ) {}

    public function isConfigured(): bool
    {
        return '' !== $this->clientId && '' !== $this->clientSecret && null !== $this->tokens->get('netatmo');
    }

    public function stations(): array
    {
        return $this->request('GET', 'https://api.netatmo.com/api/getstationsdata');
    }

    public function exchangeCode(string $code, string $redirectUri): void
    {
        $data = $this->httpClient->request('POST', 'https://api.netatmo.com/oauth2/token', [
            'body' => [
                'grant_type' => 'authorization_code',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'code' => $code,
                'redirect_uri' => $redirectUri,
            ],
        ])->toArray();

        $this->tokens->put('netatmo', (string) $data['refresh_token']);
    }

    private function request(string $method, string $url): array
    {
        $refreshToken = $this->tokens->get('netatmo');
        if (null === $refreshToken) throw new \RuntimeException('Netatmo is not connected.');

        $token = $this->httpClient->request('POST', 'https://api.netatmo.com/oauth2/token', [
            'body' => [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ],
        ])->toArray();

        if (isset($token['refresh_token'])) $this->tokens->put('netatmo', (string) $token['refresh_token']);

        return $this->httpClient->request($method, $url, ['auth_bearer' => (string) $token['access_token']])->toArray(false);
    }
}
