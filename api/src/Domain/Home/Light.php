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
    ) {}

    public function toArray(): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'on' => $this->on, 'brightness' => $this->brightness, 'status' => $this->status];
    }
}
