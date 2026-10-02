<?php

namespace App\Infrastructure\Hue;

use App\Domain\Home\Light;
use App\Domain\Home\LightProviderInterface;

final readonly class HueLightProvider implements LightProviderInterface
{
    public function __construct(private HueClient $client) {}

    public function all(): array
    {
        $response = $this->client->get('light');

        return array_map([$this, 'mapLight'], $response['data'] ?? []);
    }

    public function update(string $id, ?bool $on, ?int $brightness): Light
    {
        $payload = [];
        if (null !== $on) {
            $payload['on'] = ['on' => $on];
        }
        if (null !== $brightness) {
            $payload['dimming'] = ['brightness' => max(0, min(100, $brightness))];
        }

        $this->client->put('light/'.$id, $payload);

        foreach ($this->all() as $light) {
            if ($light->id === $id) {
                return $light;
            }
        }

        throw new \RuntimeException(sprintf('Hue light "%s" not found.', $id));
    }

    private function mapLight(array $data): Light
    {
        return new Light(
            id: (string) ($data['id'] ?? ''),
            name: (string) ($data['metadata']['name'] ?? 'Hue'),
            on: (bool) ($data['on']['on'] ?? false),
            brightness: (int) round((float) ($data['dimming']['brightness'] ?? 0)),
            status: 'online',
        );
    }
}
