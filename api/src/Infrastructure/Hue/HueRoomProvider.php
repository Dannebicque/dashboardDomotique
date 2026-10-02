<?php

namespace App\Infrastructure\Hue;

final readonly class HueRoomProvider
{
    public function __construct(private HueClient $client) {}

    public function all(): array
    {
        $rooms = $this->client->get('room')['data'] ?? [];
        $devices = $this->indexById($this->client->get('device')['data'] ?? []);
        $lights = $this->indexById($this->client->get('light')['data'] ?? []);

        return array_values(array_map(
            fn (array $room): array => $this->mapRoom($room, $devices, $lights),
            $rooms,
        ));
    }

    private function mapRoom(array $room, array $devices, array $lights): array
    {
        $roomLights = [];

        foreach ($room['children'] ?? [] as $child) {
            if ('device' !== ($child['rtype'] ?? null)) {
                continue;
            }

            $device = $devices[$child['rid'] ?? ''] ?? null;
            if (null === $device) {
                continue;
            }

            foreach ($device['services'] ?? [] as $service) {
                if ('light' !== ($service['rtype'] ?? null)) {
                    continue;
                }

                $light = $lights[$service['rid'] ?? ''] ?? null;
                if (null === $light) {
                    continue;
                }

                $roomLights[$light['id']] = [
                    'id' => (string) $light['id'],
                    'name' => (string) ($light['metadata']['name'] ?? $device['metadata']['name'] ?? 'Hue'),
                    'on' => (bool) ($light['on']['on'] ?? false),
                    'brightness' => (int) round((float) ($light['dimming']['brightness'] ?? 0)),
                    'status' => 'online',
                ];
            }
        }

        return [
            'id' => (string) ($room['id'] ?? ''),
            'name' => (string) ($room['metadata']['name'] ?? 'Pièce'),
            'lights' => array_values($roomLights),
            'shutters' => [],
        ];
    }

    private function indexById(array $items): array
    {
        $indexed = [];
        foreach ($items as $item) {
            if (isset($item['id'])) {
                $indexed[(string) $item['id']] = $item;
            }
        }

        return $indexed;
    }
}
