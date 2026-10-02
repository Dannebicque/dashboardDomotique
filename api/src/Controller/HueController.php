<?php

namespace App\Controller;

use App\Infrastructure\Hue\HueClient;
use App\Infrastructure\Hue\HuePairingService;
use App\Infrastructure\Integration\CredentialStore;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/integrations/hue')]
final readonly class HueController
{
    public function __construct(
        private HueClient $hue,
        private HuePairingService $pairing,
        private CredentialStore $credentials,
    ) {}

    #[Route('', methods: ['GET'])]
    public function status(): JsonResponse
    {
        return new JsonResponse(['provider' => 'hue', 'configured' => $this->hue->isConfigured()]);
    }

    #[Route('', methods: ['PUT'])]
    public function configure(Request $request): JsonResponse
    {
        try {
            $key = $request->toArray()['applicationKey'] ?? null;
            if (!is_string($key)) {
                return new JsonResponse(['error' => 'applicationKey is required.'], 422);
            }

            $this->hue->saveApplicationKey($key);
            $this->hue->get('bridge');

            return new JsonResponse(['configured' => true]);
        } catch (\Throwable $e) {
            $this->credentials->remove('hue');
            return new JsonResponse(['error' => $e->getMessage()], 409);
        }
    }

    #[Route('/pair', methods: ['POST'])]
    public function pair(): JsonResponse
    {
        try {
            $this->pairing->pair();
            $this->hue->get('bridge');

            return new JsonResponse(['configured' => true]);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 409);
        }
    }

    #[Route('/events', methods: ['GET'])]
    public function events(): StreamedResponse
    {
        $response = new StreamedResponse(function (): void {
            echo ": connected\n\n";
            flush();

            $this->hue->streamEvents(static function (string $chunk): void {
                if (connection_aborted()) {
                    return;
                }

                echo $chunk;
                flush();
            });
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('X-Accel-Buffering', 'no');

        return $response;
    }

    #[Route('', methods: ['DELETE'])]
    public function disconnect(): JsonResponse
    {
        $this->credentials->remove('hue');
        return new JsonResponse(null, 204);
    }
}
