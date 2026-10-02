<?php

namespace App\Controller;

use App\Infrastructure\Hue\HueLightProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/lights')]
final readonly class LightController
{
    public function __construct(private HueLightProvider $lights) {}

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return new JsonResponse(array_map(static fn ($light) => $light->toArray(), $this->lights->all()));
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(string $id, Request $request): JsonResponse
    {
        $payload = $request->toArray();
        $color = $payload['color'] ?? null;
        if (null !== $color && !is_array($color)) {
            return new JsonResponse(['error' => 'color must contain x and y coordinates.'], 422);
        }

        $light = $this->lights->update(
            $id,
            array_key_exists('on', $payload) ? (bool) $payload['on'] : null,
            array_key_exists('brightness', $payload) ? (int) $payload['brightness'] : null,
            array_key_exists('colorTemperature', $payload) ? (int) $payload['colorTemperature'] : null,
            $color,
        );

        return new JsonResponse($light->toArray());
    }
}
