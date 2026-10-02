<?php

namespace App\Controller;

use App\Infrastructure\Hue\HueSceneProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/integrations/hue/scenes')]
final readonly class HueSceneController
{
    public function __construct(private HueSceneProvider $scenes) {}

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return new JsonResponse($this->scenes->all());
    }

    #[Route('/{id}/recall', methods: ['POST'])]
    public function recall(string $id): JsonResponse
    {
        $this->scenes->recall($id);

        return new JsonResponse(null, 204);
    }
}
