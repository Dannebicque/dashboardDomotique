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
            'json' => ['devicetype' => 'dashboard_domotique#tablet', 'generateclientkey' => true],
            'headers' => ['Accept' => 'application/json'],
            'verify_peer' => false,
            'verify_host' => false,
            'timeout' => 5,
            'max_duration' => 8,
            ...$this->ipv4Options(),
        ])->toArray(false);

        $key = $response[0]['success']['username'] ?? null;
        if (!is_string($key) || '' === trim($key)) {
            throw new \RuntimeException((string) ($response[0]['error']['description'] ?? 'Press the Hue Bridge button and retry.'));
        }

        $this->credentials->put('hue', 'application_key', $key);

        $clientKey = $response[0]['success']['clientkey'] ?? null;
        if (is_string($clientKey) && '' !== trim($clientKey)) {
            $this->credentials->put('hue', 'client_key', $clientKey);
        }

        return $key;
    }

    private function ipv4Options(): array
    {
        if (!defined('CURLOPT_IPRESOLVE') || !defined('CURL_IPRESOLVE_V4')) {
            return [];
        }

        return ['extra' => ['curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]]];
    }
}
