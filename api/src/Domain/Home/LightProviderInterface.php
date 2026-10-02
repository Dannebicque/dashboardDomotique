<?php

namespace App\Domain\Home;

interface LightProviderInterface
{
    /** @return list<Light> */
    public function all(): array;

    public function update(string $id, ?bool $on, ?int $brightness): Light;
}
