<?php

namespace App\Infrastructure\OAuth;

use App\Infrastructure\Integration\CredentialStore;

final readonly class RefreshTokenStore
{
    public function __construct(private CredentialStore $credentials) {}

    public function get(string $provider): ?string
    {
        return $this->credentials->get($provider, 'refresh_token');
    }

    public function put(string $provider, string $token): void
    {
        $this->credentials->put($provider, 'refresh_token', $token);
    }

    public function remove(string $provider): void
    {
        $this->credentials->remove($provider);
    }
}
