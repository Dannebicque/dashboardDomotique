<?php

namespace App\Infrastructure\Integration;

final readonly class CredentialStore
{
    public function __construct(private string $projectDir) {}

    public function get(string $provider, string $key): ?string
    {
        $file = $this->filename($provider, $key);
        if (!is_file($file)) return null;
        $value = trim((string) file_get_contents($file));
        return '' === $value ? null : $value;
    }

    public function put(string $provider, string $key, string $value): void
    {
        $dir = $this->projectDir.'/var/integrations/'.$this->safe($provider);
        if (!is_dir($dir)) mkdir($dir, 0700, true);
        $file = $this->filename($provider, $key);
        file_put_contents($file, $value, LOCK_EX);
        @chmod($file, 0600);
    }

    public function remove(string $provider): void
    {
        $dir = $this->projectDir.'/var/integrations/'.$this->safe($provider);
        if (!is_dir($dir)) return;
        foreach (glob($dir.'/*') ?: [] as $file) if (is_file($file)) @unlink($file);
        @rmdir($dir);
    }

    private function filename(string $provider, string $key): string
    {
        return $this->projectDir.'/var/integrations/'.$this->safe($provider).'/'.$this->safe($key);
    }

    private function safe(string $value): string
    {
        if (!preg_match('/^[a-z0-9_-]+$/', $value)) throw new \InvalidArgumentException('Invalid credential key.');
        return $value;
    }
}
