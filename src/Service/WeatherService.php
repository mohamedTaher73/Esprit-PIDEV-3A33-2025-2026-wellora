<?php

namespace App\Service;

final class WeatherService
{
    private const GEO_ENDPOINT = 'https://geocoding-api.open-meteo.com/v1/search';
    private const FORECAST_ENDPOINT = 'https://api.open-meteo.com/v1/forecast';

    public function getCurrentWeather(?string $location): ?array
    {
        $normalizedLocation = trim((string) $location);
        if ($normalizedLocation == '') {
            return null;
        }

        $geoData = $this->requestJson(self::GEO_ENDPOINT . '?' . http_build_query([
            'name' => $normalizedLocation,
            'count' => 1,
            'language' => 'en',
            'format' => 'json',
        ]));

        $geoResult = $geoData['results'][0] ?? null;
        if (!is_array($geoResult)) {
            return null;
        }

        $latitude = isset($geoResult['latitude']) ? (float) $geoResult['latitude'] : null;
        $longitude = isset($geoResult['longitude']) ? (float) $geoResult['longitude'] : null;
        $weatherData = $this->fetchWeatherByCoordinates($latitude, $longitude);
        if ($weatherData === null) {
            return null;
        }

        return [
            'location' => (string) ($geoResult['name'] ?? $normalizedLocation),
            'country' => isset($geoResult['country']) ? (string) $geoResult['country'] : null,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'temperature' => $weatherData['temperature'],
            'wind_speed' => $weatherData['wind_speed'],
            'condition' => $weatherData['condition'],
            'updated_at' => $weatherData['updated_at'],
        ];
    }

    public function getCurrentWeatherForCoordinates(?float $latitude, ?float $longitude, ?string $locationLabel = null): ?array
    {
        $weatherData = $this->fetchWeatherByCoordinates($latitude, $longitude);
        if ($weatherData === null) {
            return null;
        }

        return [
            'location' => trim((string) $locationLabel) !== '' ? (string) $locationLabel : 'Selected map point',
            'country' => null,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'temperature' => $weatherData['temperature'],
            'wind_speed' => $weatherData['wind_speed'],
            'condition' => $weatherData['condition'],
            'updated_at' => $weatherData['updated_at'],
        ];
    }

    private function fetchWeatherByCoordinates(?float $latitude, ?float $longitude): ?array
    {
        if ($latitude === null || $longitude === null) {
            return null;
        }

        $forecastData = $this->requestJson(self::FORECAST_ENDPOINT . '?' . http_build_query([
            'latitude' => $latitude,
            'longitude' => $longitude,
            'current' => 'temperature_2m,weather_code,wind_speed_10m',
            'temperature_unit' => 'celsius',
            'wind_speed_unit' => 'kmh',
            'timezone' => 'auto',
        ]));

        $current = $forecastData['current'] ?? null;
        if (!is_array($current)) {
            return null;
        }

        $weatherCode = isset($current['weather_code']) ? (int) $current['weather_code'] : null;

        return [
            'temperature' => isset($current['temperature_2m']) ? (float) $current['temperature_2m'] : null,
            'wind_speed' => isset($current['wind_speed_10m']) ? (float) $current['wind_speed_10m'] : null,
            'condition' => $this->mapWeatherCodeToLabel($weatherCode),
            'updated_at' => isset($current['time']) ? (string) $current['time'] : null,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function requestJson(string $url): ?array
    {
        $streamContext = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 8,
                'header' => "Accept: application/json\r\nUser-Agent: WelloraWeatherClient/1.0\r\n",
                'ignore_errors' => true,
            ],
        ]);

        $json = @file_get_contents($url, false, $streamContext);
        if (!is_string($json) || $json === '') {
            return null;
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function mapWeatherCodeToLabel(?int $weatherCode): string
    {
        return match ($weatherCode) {
            0 => 'Clear sky',
            1, 2, 3 => 'Partly cloudy',
            45, 48 => 'Fog',
            51, 53, 55 => 'Drizzle',
            56, 57 => 'Freezing drizzle',
            61, 63, 65 => 'Rain',
            66, 67 => 'Freezing rain',
            71, 73, 75 => 'Snow',
            77 => 'Snow grains',
            80, 81, 82 => 'Rain showers',
            85, 86 => 'Snow showers',
            95 => 'Thunderstorm',
            96, 99 => 'Thunderstorm with hail',
            default => 'Unknown',
        };
    }
}
