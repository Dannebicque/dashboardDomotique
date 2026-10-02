<?php

namespace App\Infrastructure\Tahoma;

use App\Infrastructure\Integration\CredentialStore;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class TahomaClient
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private CredentialStore $credentials,
        #[Autowire('%env(string:TAHOMA_BASE_URL)%')] private string $baseUrl,
    ) {}

    public function isConfigured(): bool
    {
        return '' !== trim($this->baseUrl) && null !== $this->credentials->get('tahoma', 'token');
    }

    public function saveToken(string $token): void
    {
        if ('' === trim($token)) throw new \InvalidArgumentException('TaHoma token is required.');
        $this->credentials->put('tahoma', 'token', trim($token));
    }

    public function disconnect(): void { $this->credentials->remove('tahoma'); }

    public function setup(): array { return $this->request('GET', '/setup'); }

    private function request(string $method, string $path): array
    {
        $token = $this->credentials->get('tahoma', 'token');
        if (null === $token || '' === trim($this->baseUrl)) throw new \RuntimeException('TaHoma is not configured.');

        return $this->httpClient->request($method, rtrim($this->baseUrl, '/').$path, [
            'auth_bearer' => $token,
            'verify_peer' => false,
            'verify_host' => false,
        ])->toArray(false);
    }
}
