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

    /**
     * Proxies the Hue v2 event stream directly to the current HTTP response.
     *
     * Native cURL is intentional here: this is an indefinitely-lived SSE
     * connection, unlike the short JSON requests handled by HttpClient.
     */
    public function streamEvents(callable $write): void
    {
        $key = $this->credentials->get('hue', 'application_key');
        if ('' === trim($this->bridgeUrl) || null === $key) {
            throw new \RuntimeException('Philips Hue is not configured.');
        }
        if (!function_exists('curl_init')) {
            throw new \RuntimeException('The cURL PHP extension is required for Hue events.');
        }

        $curl = curl_init(rtrim($this->bridgeUrl, '/').'/eventstream/clip/v2');
        curl_setopt_array($curl, [
            CURLOPT_HTTPHEADER => [
                'hue-application-key: '.$key,
                'Accept: text/event-stream',
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_WRITEFUNCTION => static function ($handle, string $data) use ($write): int {
                $write($data);

                return strlen($data);
            },
        ]);

        try {
            $ok = curl_exec($curl);
            if (false === $ok && CURLE_WRITE_ERROR !== curl_errno($curl)) {
                throw new \RuntimeException('Hue event stream error: '.curl_error($curl));
            }
        } finally {
            curl_close($curl);
        }
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

        // Hue bridges are LAN devices. Force IPv4 when Symfony uses CurlHttpClient;
        // this mirrors the reliable `curl -4` access used during bridge discovery.
        if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
            $options['extra']['curl'][CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
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
