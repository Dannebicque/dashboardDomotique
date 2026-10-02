<?php

namespace App\Infrastructure\Hue;

use App\Infrastructure\Integration\CredentialStore;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class HuePairingService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private CredentialStore $credentials,
        private string $bridgeUrl,
    ) {}

    public function pair(): string
    {
        if ('' === trim($this->bridgeUrl)) {
            throw new \RuntimeException('HUE_BRIDGE_URL is not configured.');
        }

        $response = $this->httpClient->request('POST', rtrim($this->bridgeUrl, '/').'/api', [
            'json' => ['devicetype' => 'dashboard_domotique#tablet'],
            'headers' => ['Accept' => 'application/json'],
            'verify_peer' => false,
            'verify_host' => false,
            'timeout' => 5,
            'max_duration' => 8,
        ])->toArray(false);

        $key = $response[0]['success']['username'] ?? null;
        if (!is_string($key) || '' === trim($key)) {
            throw new \RuntimeException((string) ($response[0]['error']['description'] ?? 'Press the Hue Bridge button and retry.'));
        }

        $this->credentials->put('hue', 'application_key', $key);

        return $key;
    }
}
