<?php

namespace App\Controller;

use App\Infrastructure\Hue\HueClient;
use App\Infrastructure\Netatmo\NetatmoClient;
use App\Infrastructure\Spotify\SpotifyClient;
use App\Infrastructure\Tahoma\TahomaClient;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class IntegrationsController
{
    public function __construct(
        private HueClient $hue,
        private SpotifyClient $spotify,
        private NetatmoClient $netatmo,
        private TahomaClient $tahoma,
        #[Autowire('%env(string:WEATHER_LATITUDE)%')] private string $weatherLatitude,
        #[Autowire('%env(string:WEATHER_LONGITUDE)%')] private string $weatherLongitude,
    ) {}

    #[Route('/api/integrations', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return new JsonResponse([
            'hue' => ['configured' => $this->hue->isConfigured(), 'local' => true],
            'tahoma' => ['configured' => $this->tahoma->isConfigured(), 'local' => true],
            'netatmo' => ['configured' => $this->netatmo->isConfigured(), 'local' => false],
            'spotify' => ['configured' => $this->spotify->isConfigured(), 'local' => false],
            'weather' => ['configured' => '' !== $this->weatherLatitude && '' !== $this->weatherLongitude, 'local' => false],
        ]);
    }
}
