<?php

namespace App\Application\Home;

use App\Infrastructure\Hue\HueRoomProvider;

final readonly class HomeZoneProvider
{
    private const ZONES = [
        'salon' => [
            'name' => 'Salon',
            'hueRoomNames' => ['Salon', 'mur', 'canapé'],
        ],
        'cuisine' => [
            'name' => 'Cuisine',
            'hueRoomNames' => ['kitchen'],
        ],
    ];

    public function __construct(private HueRoomProvider $hueRooms) {}

    public function all(): array
    {
        $hueRooms = $this->hueRooms->all();
        $consumed = [];
        $zones = [];

        foreach (self::ZONES as $id => $definition) {
            $sourceRooms = array_values(array_filter(
                $hueRooms,
                static fn (array $room): bool => in_array($room['name'], $definition['hueRoomNames'], true),
            ));

            if ([] === $sourceRooms) {
                continue;
            }

            foreach ($sourceRooms as $sourceRoom) {
                $consumed[$sourceRoom['id']] = true;
            }

            $zones[] = $this->merge($id, $definition['name'], $sourceRooms);
        }

        foreach ($hueRooms as $room) {
            if (isset($consumed[$room['id']])) {
                continue;
            }

            $zones[] = $this->merge('hue-'.$room['id'], $room['name'], [$room]);
        }

        return $zones;
    }

    private function merge(string $id, string $name, array $rooms): array
    {
        $lights = [];
        $shutters = [];
        $hueRoomIds = [];

        foreach ($rooms as $room) {
            $hueRoomIds[] = $room['id'];
            foreach ($room['lights'] ?? [] as $light) {
                $lights[$light['id']] = $light;
            }
            foreach ($room['shutters'] ?? [] as $shutter) {
                $shutters[$shutter['id']] = $shutter;
            }
        }

        return [
            'id' => $id,
            'name' => $name,
            'hueRoomIds' => $hueRoomIds,
            'lights' => array_values($lights),
            'shutters' => array_values($shutters),
        ];
    }
}
