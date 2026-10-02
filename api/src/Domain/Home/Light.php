<?php

namespace App\Domain\Home;

final readonly class Light
{
    public function __construct(
        public string $id,
        public string $name,
        public bool $on,
        public int $brightness,
        public string $status = 'online',
        public bool $supportsDimming = true,
        public bool $supportsColorTemperature = false,
        public ?int $colorTemperature = null,
        public ?int $colorTemperatureMin = null,
        public ?int $colorTemperatureMax = null,
        public bool $supportsColor = false,
        public ?float $colorX = null,
        public ?float $colorY = null,
        public bool $supportsGradient = false,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'on' => $this->on,
            'brightness' => $this->brightness,
            'status' => $this->status,
            'capabilities' => [
                'dimming' => $this->supportsDimming,
                'colorTemperature' => $this->supportsColorTemperature,
                'color' => $this->supportsColor,
                'gradient' => $this->supportsGradient,
            ],
            'colorTemperature' => $this->colorTemperature,
            'colorTemperatureMin' => $this->colorTemperatureMin,
            'colorTemperatureMax' => $this->colorTemperatureMax,
            'color' => null !== $this->colorX && null !== $this->colorY ? ['x' => $this->colorX, 'y' => $this->colorY] : null,
        ];
    }
}
