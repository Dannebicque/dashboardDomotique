<?php

namespace App\Controller;

use App\Infrastructure\Tahoma\TahomaClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Temporary read-only TaHoma endpoint.
 * Shutter commands will be enabled once the actual /setup payload has been
 * inspected against the local box.
 */
final readonly class ShutterController
{
    public function __construct(private TahomaClient $tahoma) {}

    #[Route('/api/shutters', methods: ['GET'])]
    public function list(): JsonResponse
    {
        if (!$this->tahoma->isConfigured()) {
            return new JsonResponse(['configured' => false, 'items' => []]);
        }

        return new JsonResponse([
            'configured' => true,
            'items' => [],
            'message' => 'TaHoma is connected; device mapping is pending setup inspection.',
        ]);
    }
}
