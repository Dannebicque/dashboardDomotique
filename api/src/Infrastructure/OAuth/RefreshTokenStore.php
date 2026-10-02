<?php

namespace App\Infrastructure\OAuth;

final readonly class RefreshTokenStore
{
    public function __construct(private string $projectDir) {}

    public function get(string $provider): ?string
    {
        $file = $this->filename($provider);
        if (!is_file($file)) return null;
        $value = trim((string) file_get_contents($file));
        return '' === $value ? null : $value;
    }

    public function put(string $provider, string $token): void
    {
        $dir = $this->projectDir.'/var/oauth';
        if (!is_dir($dir)) mkdir($dir, 0700, true);
        file_put_contents($this->filename($provider), $token, LOCK_EX);
        @chmod($this->filename($provider), 0600);
    }

    private function filename(string $provider): string
    {
        if (!preg_match('/^[a-z0-9_-]+$/', $provider)) throw new \InvalidArgumentException('Invalid OAuth provider.');
        return $this->projectDir.'/var/oauth/'.$provider.'.refresh_token';
    }
}
