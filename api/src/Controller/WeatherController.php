<?php

namespace App\Controller;

use App\Infrastructure\Weather\OpenMeteoClient;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class WeatherController
{
    public function __construct(
        private OpenMeteoClient $weather,
        #[Autowire('%env(float:WEATHER_LATITUDE)%')] private float $latitude,
        #[Autowire('%env(float:WEATHER_LONGITUDE)%')] private float $longitude,
        #[Autowire('%env(string:WEATHER_TIMEZONE)%')] private string $timezone,
    ) {}

    #[Route('/api/weather', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return new JsonResponse($this->weather->forecast($this->latitude, $this->longitude, $this->timezone));
    }
}
