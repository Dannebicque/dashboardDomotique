<?php

namespace App\Controller;

use App\Infrastructure\Hue\HueClient;
use App\Infrastructure\Hue\HuePairingService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/integrations/hue')]
final readonly class HueController
{
    public function __construct(
        private HueClient $hue,
        private HuePairingService $pairing,
    ) {}

    #[Route('', methods: ['GET'])]
    public function status(): JsonResponse
    {
        return new JsonResponse(['provider' => 'hue', 'configured' => $this->hue->isConfigured()]);
    }

    #[Route('/pair', methods: ['POST'])]
    public function pair(): JsonResponse
    {
        try {
            return new JsonResponse([
                'applicationKey' => $this->pairing->pair(),
                'message' => 'Store this key in HUE_APPLICATION_KEY in api/.env.local.',
            ]);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 409);
        }
    }
}
