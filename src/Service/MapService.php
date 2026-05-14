<?php

namespace App\Service;

final class MapService
{
    private const NOMINATIM_SEARCH_ENDPOINT = 'https://nominatim.openstreetmap.org/search';
    private const NOMINATIM_REVERSE_ENDPOINT = 'https://nominatim.openstreetmap.org/reverse';
    private const OSM_EMBED_ENDPOINT = 'https://www.openstreetmap.org/export/embed.html';
    private const OSM_VIEW_ENDPOINT = 'https://www.openstreetmap.org/';
    private const DEFAULT_ZOOM = 14;

    public function geocodeLocation(?string $location): ?array
    {
        $normalizedLocation = trim((string) $location);
        if ($normalizedLocation === '') {
            return null;
        }

        $payload = $this->requestJson(self::NOMINATIM_SEARCH_ENDPOINT . '?' . http_build_query([
            'q' => $normalizedLocation,
            'format' => 'jsonv2',
            'limit' => 1,
            'addressdetails' => 0,
        ]));

        $firstResult = $payload[0] ?? null;
        if (!is_array($firstResult)) {
            return null;
        }

        $latitude = isset($firstResult['lat']) ? (float) $firstResult['lat'] : null;
        $longitude = isset($firstResult['lon']) ? (float) $firstResult['lon'] : null;
        if ($latitude === null || $longitude === null) {
            return null;
        }

        return [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'label' => isset($firstResult['display_name']) ? (string) $firstResult['display_name'] : $normalizedLocation,
        ];
    }

    public function reverseGeocodeCoordinates(?float $latitude, ?float $longitude): ?string
    {
        if ($latitude === null || $longitude === null) {
            return null;
        }

        $payload = $this->requestJson(self::NOMINATIM_REVERSE_ENDPOINT . '?' . http_build_query([
            'format' => 'jsonv2',
            'lat' => $latitude,
            'lon' => $longitude,
            'zoom' => 16,
        ]));

        if (!is_array($payload)) {
            return null;
        }

        $displayName = trim((string) ($payload['display_name'] ?? ''));

        return $displayName !== '' ? $displayName : null;
    }

    public function getMapDataForCoordinates(?float $latitude, ?float $longitude, ?string $locationLabel = null): ?array
    {
        if ($latitude === null || $longitude === null) {
            return null;
        }

        $normalizedLatitude = (float) number_format($latitude, 6, '.', '');
        $normalizedLongitude = (float) number_format($longitude, 6, '.', '');
        $resolvedLabel = trim((string) $locationLabel);
        if ($resolvedLabel === '') {
            $resolvedLabel = $this->reverseGeocodeCoordinates($normalizedLatitude, $normalizedLongitude) ?? 'Selected map point';
        }

        $bbox = $this->buildBoundingBox($normalizedLatitude, $normalizedLongitude);
        $embedUrl = self::OSM_EMBED_ENDPOINT . '?' . http_build_query([
            'bbox' => $bbox,
            'layer' => 'mapnik',
            'marker' => $this->formatCoordinate($normalizedLatitude) . ',' . $this->formatCoordinate($normalizedLongitude),
        ]);
        $externalUrl = self::OSM_VIEW_ENDPOINT . '?' . http_build_query([
            'mlat' => $this->formatCoordinate($normalizedLatitude),
            'mlon' => $this->formatCoordinate($normalizedLongitude),
        ]) . '#map=' . self::DEFAULT_ZOOM . '/' . $this->formatCoordinate($normalizedLatitude) . '/' . $this->formatCoordinate($normalizedLongitude);

        return [
            'label' => $resolvedLabel,
            'latitude' => $normalizedLatitude,
            'longitude' => $normalizedLongitude,
            'zoom' => self::DEFAULT_ZOOM,
            'bbox' => $bbox,
            'embed_url' => $embedUrl,
            'external_url' => $externalUrl,
        ];
    }

    private function buildBoundingBox(float $latitude, float $longitude): string
    {
        $latDelta = 0.008;
        $lngDelta = 0.012;

        $minLat = max(-90.0, $latitude - $latDelta);
        $maxLat = min(90.0, $latitude + $latDelta);
        $minLng = max(-180.0, $longitude - $lngDelta);
        $maxLng = min(180.0, $longitude + $lngDelta);

        return implode(',', [
            $this->formatCoordinate($minLng),
            $this->formatCoordinate($minLat),
            $this->formatCoordinate($maxLng),
            $this->formatCoordinate($maxLat),
        ]);
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
                'header' => "Accept: application/json\r\nUser-Agent: WelloraMapClient/1.0\r\n",
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

    private function formatCoordinate(float $value): string
    {
        return number_format($value, 6, '.', '');
    }
}
