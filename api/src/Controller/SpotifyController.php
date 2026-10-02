<?php

namespace App\Controller;

use App\Infrastructure\Spotify\SpotifyClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/media/spotify')]
final readonly class SpotifyController
{
    public function __construct(private SpotifyClient $spotify) {}

    #[Route('', methods: ['GET'])]
    public function status(): JsonResponse
    {
        return new JsonResponse(['configured' => $this->spotify->isConfigured(), 'playback' => $this->spotify->isConfigured() ? $this->spotify->playback() : null]);
    }

    #[Route('/{command}', requirements: ['command' => 'play|pause|next|previous'], methods: ['POST'])]
    public function command(string $command): JsonResponse
    {
        $this->spotify->command($command);
        return new JsonResponse(null, 204);
    }
}
