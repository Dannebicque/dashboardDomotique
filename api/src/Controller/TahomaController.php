<?php

namespace App\Controller;

use App\Infrastructure\Tahoma\TahomaClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/integrations/tahoma')]
final readonly class TahomaController
{
    public function __construct(private TahomaClient $tahoma) {}

    #[Route('', methods: ['GET'])]
    public function status(): JsonResponse
    {
        return new JsonResponse(['provider' => 'tahoma', 'configured' => $this->tahoma->isConfigured()]);
    }

    #[Route('', methods: ['PUT'])]
    public function configure(Request $request): JsonResponse
    {
        try {
            $token = (string) ($request->toArray()['token'] ?? '');
            $this->tahoma->saveToken($token);
            $this->tahoma->setup();
            return new JsonResponse(['configured' => true]);
        } catch (\Throwable $e) {
            $this->tahoma->disconnect();
            return new JsonResponse(['error' => 'TaHoma connection failed: '.$e->getMessage()], 409);
        }
    }

    #[Route('', methods: ['DELETE'])]
    public function disconnect(): JsonResponse
    {
        $this->tahoma->disconnect();
        return new JsonResponse(null, 204);
    }
}
