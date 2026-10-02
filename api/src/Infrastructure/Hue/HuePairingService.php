<?php

namespace App\Infrastructure\Hue;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class HuePairingService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $bridgeUrl,
    ) {}

    public function pair(): string
    {
        if ('' === trim($this->bridgeUrl)) {
            throw new \RuntimeException('HUE_BRIDGE_URL is not configured.');
        }

        $response = $this->httpClient->request('POST', rtrim($this->bridgeUrl, '/').'/api', [
            'json' => ['devicetype' => 'dashboard_domotique#tablet'],
            'verify_peer' => false,
            'verify_host' => false,
        ])->toArray(false);

        $key = $response[0]['success']['username'] ?? null;
        if (!is_string($key) || '' === $key) {
            $message = $response[0]['error']['description'] ?? 'Pairing failed. Press the Hue Bridge button and retry.';
            throw new \RuntimeException((string) $message);
        }

        return $key;
    }
}
