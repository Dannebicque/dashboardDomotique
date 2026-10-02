<?php

namespace App\Infrastructure\Hue;

use App\Infrastructure\Integration\CredentialStore;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class HueClient
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private CredentialStore $credentials,
        private string $bridgeUrl,
    ) {}

    public function isConfigured(): bool
    {
        return '' !== trim($this->bridgeUrl) && null !== $this->credentials->get('hue', 'application_key');
    }

    public function saveApplicationKey(string $key): void
    {
        $key = trim($key);
        if ('' === $key) {
            throw new \InvalidArgumentException('The Hue application key cannot be empty.');
        }

        $this->credentials->put('hue', 'application_key', $key);
    }

    public function get(string $path): array
    {
        return $this->request('GET', $path);
    }

    public function put(string $path, array $payload): array
    {
        return $this->request('PUT', $path, $payload);
    }

    private function request(string $method, string $path, ?array $payload = null): array
    {
        $key = $this->credentials->get('hue', 'application_key');
        if ('' === trim($this->bridgeUrl) || null === $key) {
            throw new \RuntimeException('Philips Hue is not configured.');
        }

        $options = [
            'headers' => [
                'hue-application-key' => $key,
                'Accept' => 'application/json',
            ],
            'verify_peer' => false,
            'verify_host' => false,
            'timeout' => 5,
            'max_duration' => 8,
        ];
        if (null !== $payload) {
            $options['json'] = $payload;
        }

        $response = $this->httpClient->request(
            $method,
            rtrim($this->bridgeUrl, '/').'/clip/v2/resource/'.ltrim($path, '/'),
            $options,
        )->toArray(false);

        if ([] !== ($response['errors'] ?? [])) {
            throw new \RuntimeException((string) ($response['errors'][0]['description'] ?? 'Philips Hue API error.'));
        }

        return $response;
    }
}
