<?php

namespace App\Controller;

use App\Infrastructure\Hue\HueClient;
use App\Infrastructure\Hue\HuePairingService;
use App\Infrastructure\Integration\CredentialStore;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/integrations/hue')]
final readonly class HueController
{
    public function __construct(private HueClient $hue, private HuePairingService $pairing, private CredentialStore $credentials) {}

    #[Route('', methods: ['GET'])]
    public function status(): JsonResponse { return new JsonResponse(['provider' => 'hue', 'configured' => $this->hue->isConfigured()]); }

    #[Route('/pair', methods: ['POST'])]
    public function pair(): JsonResponse
    {
        try {
            $this->pairing->pair();
            return new JsonResponse(['configured' => true]);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 409);
        }
    }

    #[Route('', methods: ['DELETE'])]
    public function disconnect(): JsonResponse
    {
        $this->credentials->remove('hue');
        return new JsonResponse(null, 204);
    }
}
