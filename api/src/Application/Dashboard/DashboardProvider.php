<?php

namespace App\Application\Dashboard;

use App\Infrastructure\Netatmo\NetatmoClient;
use App\Infrastructure\Spotify\SpotifyClient;
use App\Infrastructure\Weather\OpenMeteoClient;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DashboardProvider
{
    public function __construct(
        private NetatmoClient $netatmo,
        private SpotifyClient $spotify,
        private OpenMeteoClient $weather,
        #[Autowire('%env(string:WEATHER_LATITUDE)%')] private string $latitude,
        #[Autowire('%env(string:WEATHER_LONGITUDE)%')] private string $longitude,
        #[Autowire('%env(string:WEATHER_TIMEZONE)%')] private string $timezone,
    ) {}

    public function data(): array
    {
        return [
            'weather' => $this->safe(fn () => $this->normalizeWeather(), 'weather'),
            'sensors' => $this->netatmo->isConfigured() ? $this->safe(fn () => $this->normalizeSensors(), 'netatmo') : ['available' => false, 'source' => 'netatmo'],
            'spotify' => $this->spotify->isConfigured() ? $this->safe(fn () => $this->normalizeSpotify(), 'spotify') : ['available' => false, 'source' => 'spotify'],
        ];
    }

    private function normalizeWeather(): array
    {
        if ('' === $this->latitude || '' === $this->longitude) return ['available' => false, 'source' => 'open-meteo'];
        $raw = $this->weather->forecast((float) $this->latitude, (float) $this->longitude, $this->timezone);
        $current = $raw['current'] ?? [];
        $daily = $raw['daily'] ?? [];
        $hourly = $raw['hourly'] ?? [];
        $hours = [];
        foreach (array_slice($hourly['time'] ?? [], 0, 24) as $i => $time) {
            if (strtotime((string) $time) < time()) continue;
            $hours[] = ['time' => date('H\h', strtotime((string) $time)), 'temperature' => (int) round((float) ($hourly['temperature_2m'][$i] ?? 0)), 'rainProbability' => (int) ($hourly['precipitation_probability'][$i] ?? 0)];
            if (4 === count($hours)) break;
        }

        return [
            'available' => true, 'source' => 'open-meteo',
            'temperature' => (int) round((float) ($current['temperature_2m'] ?? 0)),
            'apparentTemperature' => (int) round((float) ($current['apparent_temperature'] ?? 0)),
            'weatherCode' => (int) ($current['weather_code'] ?? 0),
            'min' => (int) round((float) ($daily['temperature_2m_min'][0] ?? 0)),
            'max' => (int) round((float) ($daily['temperature_2m_max'][0] ?? 0)),
            'rainProbability' => (int) ($daily['precipitation_probability_max'][0] ?? 0),
            'hourly' => $hours,
        ];
    }

    private function normalizeSensors(): array
    {
        $body = $this->netatmo->stations()['body'] ?? [];
        $devices = $body['devices'] ?? [];
        $items = [];
        foreach ($devices as $device) {
            $items[] = $this->sensor($device, 'indoor');
            foreach ($device['modules'] ?? [] as $module) {
                $type = (string) ($module['type'] ?? '');
                $kind = match ($type) {
                    'NAModule1' => 'outdoor',
                    'NAModule2' => 'wind',
                    'NAModule3' => 'rain',
                    default => 'indoor',
                };
                $items[] = $this->sensor($module, $kind);
            }
        }
        return ['available' => true, 'source' => 'netatmo', 'items' => array_values(array_filter($items))];
    }

    private function sensor(array $item, string $kind): ?array
    {
        $dashboard = $item['dashboard_data'] ?? [];

        return [
            'id' => (string) ($item['_id'] ?? ''),
            'name' => (string) ($item['module_name'] ?? $item['station_name'] ?? 'Netatmo'),
            'kind' => $kind,
            'moduleType' => (string) ($item['type'] ?? ''),
            'reachable' => (bool) ($item['reachable'] ?? true),
            'lastSeen' => isset($dashboard['time_utc']) ? (int) $dashboard['time_utc'] : (isset($item['last_seen']) ? (int) $item['last_seen'] : null),
            'batteryPercent' => isset($item['battery_percent']) ? (int) $item['battery_percent'] : null,
            'temperature' => isset($dashboard['Temperature']) ? (float) $dashboard['Temperature'] : null,
            'humidity' => isset($dashboard['Humidity']) ? (int) $dashboard['Humidity'] : null,
            'co2' => isset($dashboard['CO2']) ? (int) $dashboard['CO2'] : null,
            'noise' => isset($dashboard['Noise']) ? (int) $dashboard['Noise'] : null,
            'pressure' => isset($dashboard['Pressure']) ? (float) $dashboard['Pressure'] : null,
            'temperatureTrend' => isset($dashboard['temp_trend']) ? (string) $dashboard['temp_trend'] : null,
            'pressureTrend' => isset($dashboard['pressure_trend']) ? (string) $dashboard['pressure_trend'] : null,
            'minTemperature' => isset($dashboard['min_temp']) ? (float) $dashboard['min_temp'] : null,
            'maxTemperature' => isset($dashboard['max_temp']) ? (float) $dashboard['max_temp'] : null,
            'windStrength' => isset($dashboard['WindStrength']) ? (float) $dashboard['WindStrength'] : null,
            'windAngle' => isset($dashboard['WindAngle']) ? (int) $dashboard['WindAngle'] : null,
            'gustStrength' => isset($dashboard['GustStrength']) ? (float) $dashboard['GustStrength'] : null,
            'gustAngle' => isset($dashboard['GustAngle']) ? (int) $dashboard['GustAngle'] : null,
            'rain' => isset($dashboard['Rain']) ? (float) $dashboard['Rain'] : null,
            'rain24h' => isset($dashboard['sum_rain_24']) ? (float) $dashboard['sum_rain_24'] : null,
        ];
    }

    private function normalizeSpotify(): array
    {
        $player = $this->spotify->playback();
        if (null === $player || !isset($player['item'])) return ['available' => true, 'source' => 'spotify', 'isPlaying' => false, 'track' => null];
        $item = $player['item'];
        return [
            'available' => true, 'source' => 'spotify', 'isPlaying' => (bool) ($player['is_playing'] ?? false),
            'device' => isset($player['device']) ? [
                'name' => (string) ($player['device']['name'] ?? ''),
                'type' => (string) ($player['device']['type'] ?? ''),
                'volumePercent' => isset($player['device']['volume_percent']) ? (int) $player['device']['volume_percent'] : null,
            ] : null,
            'track' => [
                'title' => (string) ($item['name'] ?? ''), 'artist' => implode(', ', array_column($item['artists'] ?? [], 'name')),
                'album' => (string) ($item['album']['name'] ?? ''), 'coverUrl' => (string) ($item['album']['images'][0]['url'] ?? ''),
                'progressMs' => (int) ($player['progress_ms'] ?? 0), 'durationMs' => (int) ($item['duration_ms'] ?? 0),
            ],
        ];
    }

    private function safe(callable $callback, string $source): array
    {
        try { return $callback(); } catch (\Throwable $e) { return ['available' => false, 'source' => $source, 'error' => $e->getMessage()]; }
    }
}
