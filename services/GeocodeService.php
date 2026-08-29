<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryMapping\services;

use humhub\modules\thiscoveryMapping\Module;
use Yii;

class GeocodeService
{
    public function search(string $query, int $limit = 5, ?float $focusLat = null, ?float $focusLng = null): array
    {
        $query = trim($query);
        if ($query === '' || mb_strlen($query) > 120) {
            return [];
        }
        $module = Module::instance();
        if (!$module || $module->getGeocoderProvider() !== Module::GEOCODER_STADIA) {
            return [];
        }
        $key = $module->getApiKey();
        $params = [
            'text' => $query,
            'size' => max(1, min(8, $limit)),
        ];
        if ($focusLat !== null && $focusLng !== null
            && $focusLat >= -90 && $focusLat <= 90
            && $focusLng >= -180 && $focusLng <= 180) {
            $params['focus.point.lat'] = $focusLat;
            $params['focus.point.lon'] = $focusLng;
        }
        $url = Module::STADIA_API_EU . '/geocoding/v1/autocomplete?' . http_build_query($params);
        if ($key !== '') {
            $url .= '&api_key=' . rawurlencode($key);
        }

        $raw = $this->get($url);
        if ($raw === null) {
            return [];
        }
        $decoded = json_decode($raw, true);
        $features = is_array($decoded['features'] ?? null) ? $decoded['features'] : [];
        $out = [];
        foreach ($features as $feature) {
            if (!is_array($feature)) {
                continue;
            }
            $coords = $feature['geometry']['coordinates'] ?? null;
            $label = (string)($feature['properties']['label'] ?? $feature['properties']['name'] ?? '');
            if (!is_array($coords) || count($coords) < 2 || $label === '') {
                continue;
            }
            $out[] = [
                'label' => $label,
                'lng' => (float)$coords[0],
                'lat' => (float)$coords[1],
                'layer' => (string)($feature['properties']['layer'] ?? ''),
            ];
        }
        return $out;
    }

    private function get(string $url): ?string
    {
        try {
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 6,
                    'header' => "Accept: application/json\r\nUser-Agent: ThiscoveryMapping/1.0\r\n",
                ],
                'ssl' => [
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                ],
            ]);
            $body = @file_get_contents($url, false, $context);
            return is_string($body) ? $body : null;
        } catch (\Throwable $e) {
            Yii::warning('Geocode failed: ' . $e->getMessage(), 'thiscovery-mapping');
            return null;
        }
    }
}
