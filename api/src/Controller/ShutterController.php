<?php

namespace App\Controller;

use App\Infrastructure\Tahoma\TahomaClient;
use App\Infrastructure\Tahoma\TahomaShutterProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class ShutterController
{
    public function __construct(private TahomaShutterProvider $shutters, private TahomaClient $tahoma) {}

    #[Route('/api/integrations/tahoma', methods: ['GET'])]
    public function status(): JsonResponse
    {
        return new JsonResponse(['provider' => 'tahoma', 'configured' => $this->tahoma->isConfigured()]);
    }

    #[Route('/api/shutters', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return new JsonResponse(array_map(static fn ($shutter) => $shutter->toArray(), $this->shutters->all()));
    }

    #[Route('/api/shutters/{id}', methods: ['PUT'])]
    public function move(string $id, Request $request): JsonResponse
    {
        $position = (int) ($request->toArray()['position'] ?? 0);
        return new JsonResponse($this->shutters->move($id, $position)->toArray());
    }

    #[Route('/api/shutters/{id}/stop', methods: ['POST'])]
    public function stop(string $id): JsonResponse
    {
        $this->shutters->stop($id);
        return new JsonResponse(null, 204);
    }
}
