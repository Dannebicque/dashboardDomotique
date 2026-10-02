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
        $data = $this->tokenRequest([
            'grant_type' => 'authorization_code',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'redirect_uri' => $redirectUri,
        ]);

        if (!isset($data['refresh_token'])) {
            throw new \RuntimeException('Netatmo did not return a refresh token.');
        }

        $this->tokens->put('netatmo', (string) $data['refresh_token']);
    }

    private function request(string $method, string $url): array
    {
        $refreshToken = $this->tokens->get('netatmo');
        if (null === $refreshToken) throw new \RuntimeException('Netatmo is not connected.');

        $token = $this->tokenRequest([
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        if (isset($token['refresh_token'])) $this->tokens->put('netatmo', (string) $token['refresh_token']);
        if (!isset($token['access_token'])) throw new \RuntimeException('Netatmo did not return an access token.');

        $response = $this->httpClient->request($method, $url, ['auth_bearer' => (string) $token['access_token']]);
        $status = $response->getStatusCode();
        $content = $response->getContent(false);
        $data = json_decode($content, true);

        if ($status >= 400) {
            $message = $data['error']['message'] ?? $data['error_description'] ?? $data['error'] ?? $content ?: 'Netatmo API error.';
            throw new \RuntimeException(sprintf('Netatmo API %d: %s', $status, is_string($message) ? $message : json_encode($message)));
        }

        if (!is_array($data)) throw new \RuntimeException(sprintf('Unexpected Netatmo response (%d).', $status));

        return $data;
    }

    private function tokenRequest(array $body): array
    {
        $response = $this->httpClient->request('POST', 'https://api.netatmo.com/oauth2/token', ['body' => $body]);
        $status = $response->getStatusCode();
        $content = $response->getContent(false);
        $data = json_decode($content, true);

        if ($status >= 400 || !is_array($data)) {
            $message = is_array($data)
                ? ($data['error_description'] ?? $data['error'] ?? $content)
                : $content;

            throw new \RuntimeException(sprintf('Netatmo OAuth %d: %s', $status, is_string($message) ? $message : json_encode($message)));
        }

        return $data;
    }
}
