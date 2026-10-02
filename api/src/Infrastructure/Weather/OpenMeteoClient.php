<?php

namespace App\Infrastructure\Weather;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class OpenMeteoClient
{
    public function __construct(private HttpClientInterface $httpClient) {}

    public function forecast(float $latitude, float $longitude, string $timezone = 'Europe/Paris'): array
    {
        return $this->httpClient->request('GET', 'https://api.open-meteo.com/v1/forecast', [
            'query' => [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'timezone' => $timezone,
                'current' => 'temperature_2m,apparent_temperature,weather_code',
                'hourly' => 'temperature_2m,precipitation_probability,weather_code',
                'daily' => 'temperature_2m_min,temperature_2m_max,precipitation_probability_max',
                'forecast_days' => 2,
            ],
        ])->toArray();
    }
}
