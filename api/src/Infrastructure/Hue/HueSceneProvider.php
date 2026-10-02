<?php

namespace App\Infrastructure\Hue;

final readonly class HueSceneProvider
{
    public function __construct(private HueClient $client) {}

    public function all(): array
    {
        $scenes = $this->client->get('scene')['data'] ?? [];

        return array_values(array_map(static fn (array $scene): array => [
            'id' => (string) ($scene['id'] ?? ''),
            'name' => (string) ($scene['metadata']['name'] ?? 'Scène Hue'),
            'roomId' => 'room' === ($scene['group']['rtype'] ?? null) ? (string) ($scene['group']['rid'] ?? '') : null,
            'status' => (string) ($scene['status']['active'] ?? 'inactive'),
        ], $scenes));
    }

    public function recall(string $id): void
    {
        $this->client->put('scene/'.$id, ['recall' => ['action' => 'active']]);
    }
}
