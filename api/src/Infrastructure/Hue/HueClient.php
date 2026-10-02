<?php

namespace App\Infrastructure\Hue;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class HueClient
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $bridgeUrl,
        private string $applicationKey,
    ) {}

    public function isConfigured(): bool
    {
        return '' !== trim($this->bridgeUrl) && '' !== trim($this->applicationKey);
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
        if (!$this->isConfigured()) {
            throw new \RuntimeException('Philips Hue is not configured.');
        }

        $options = [
            'headers' => ['hue-application-key' => $this->applicationKey],
            'verify_peer' => false,
            'verify_host' => false,
        ];

        if (null !== $payload) {
            $options['json'] = $payload;
        }

        return $this->httpClient
            ->request($method, rtrim($this->bridgeUrl, '/').'/clip/v2/resource/'.ltrim($path, '/'), $options)
            ->toArray(false);
    }
}
