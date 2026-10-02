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

    public function update(string $id, ?bool $on, ?int $brightness, ?int $colorTemperature = null, ?array $color = null): Light
    {
        $payload = [];
        if (null !== $on) {
            $payload['on'] = ['on' => $on];
        }
        if (null !== $brightness) {
            $payload['dimming'] = ['brightness' => max(0, min(100, $brightness))];
        }
        if (null !== $colorTemperature) {
            $payload['color_temperature'] = ['mirek' => $colorTemperature];
        }
        if (null !== $color) {
            $x = $color['x'] ?? null;
            $y = $color['y'] ?? null;
            if (!is_numeric($x) || !is_numeric($y)) {
                throw new \InvalidArgumentException('Hue color requires numeric x and y coordinates.');
            }
            $payload['color'] = ['xy' => ['x' => (float) $x, 'y' => (float) $y]];
        }

        if ([] === $payload) {
            throw new \InvalidArgumentException('No Hue light property to update.');
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
        $temperature = $data['color_temperature'] ?? null;
        $xy = $data['color']['xy'] ?? null;

        return new Light(
            id: (string) ($data['id'] ?? ''),
            name: (string) ($data['metadata']['name'] ?? 'Hue'),
            on: (bool) ($data['on']['on'] ?? false),
            brightness: (int) round((float) ($data['dimming']['brightness'] ?? 0)),
            status: 'online',
            supportsDimming: isset($data['dimming']),
            supportsColorTemperature: isset($data['color_temperature']),
            colorTemperature: isset($temperature['mirek']) && is_numeric($temperature['mirek']) ? (int) $temperature['mirek'] : null,
            colorTemperatureMin: isset($temperature['mirek_schema']['mirek_minimum']) ? (int) $temperature['mirek_schema']['mirek_minimum'] : null,
            colorTemperatureMax: isset($temperature['mirek_schema']['mirek_maximum']) ? (int) $temperature['mirek_schema']['mirek_maximum'] : null,
            supportsColor: isset($data['color']),
            colorX: isset($xy['x']) && is_numeric($xy['x']) ? (float) $xy['x'] : null,
            colorY: isset($xy['y']) && is_numeric($xy['y']) ? (float) $xy['y'] : null,
            supportsGradient: isset($data['gradient']),
        );
    }
}
