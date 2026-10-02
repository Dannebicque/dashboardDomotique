<?php

namespace App\Controller;

use App\Infrastructure\Hue\HueClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class HueController
{
    public function __construct(private HueClient $hue) {}

    #[Route('/api/integrations/hue', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return new JsonResponse([
            'provider' => 'hue',
            'configured' => $this->hue->isConfigured(),
        ]);
    }
}
