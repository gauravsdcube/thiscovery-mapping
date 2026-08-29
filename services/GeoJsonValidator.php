<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryMapping\services;

class GeoJsonValidator
{
    public const MAX_CHARS = 65000;
    public const MAX_COORDS = 8000;

    /**
     * @param string[] $allowedTypes
     */
    public function sanitizeFeature($feature, array $allowedTypes): ?array
    {
        if (is_string($feature)) {
            $feature = json_decode($feature, true);
        }
        if (!is_array($feature)) {
            return null;
        }
        if (($feature['type'] ?? '') === 'FeatureCollection' && isset($feature['features'][0])) {
            $feature = $feature['features'][0];
        }
        if (($feature['type'] ?? '') !== 'Feature' || !is_array($feature['geometry'] ?? null)) {
            return null;
        }
        $geom = $feature['geometry'];
        $type = (string)($geom['type'] ?? '');
        if (!in_array($type, $allowedTypes, true)) {
            return null;
        }
        $coords = $geom['coordinates'] ?? null;
        if (!$this->validCoordinates($type, $coords)) {
            return null;
        }
        $encoded = json_encode(['type' => 'Feature', 'geometry' => ['type' => $type, 'coordinates' => $coords], 'properties' => new \stdClass()]);
        if ($encoded === false || strlen($encoded) > self::MAX_CHARS) {
            return null;
        }
        return json_decode($encoded, true);
    }

    /**
     * @param string[] $allowedTypes
     */
    public function sanitizeCollection($raw, array $allowedTypes, int $maxFeatures = 50): ?array
    {
        if (is_string($raw)) {
            $raw = json_decode($raw, true);
        }
        if (!is_array($raw)) {
            return null;
        }
        if (($raw['type'] ?? '') === 'Feature') {
            $list = [$raw];
        } elseif (($raw['type'] ?? '') === 'FeatureCollection') {
            $list = $raw['features'] ?? [];
        } else {
            return null;
        }
        if (!is_array($list)) {
            return null;
        }
        if ($maxFeatures < 1) {
            $maxFeatures = 1;
        }
        if ($maxFeatures > 50) {
            $maxFeatures = 50;
        }
        $features = [];
        foreach ($list as $item) {
            $clean = $this->sanitizeFeature($item, $allowedTypes);
            if ($clean === null) {
                continue;
            }
            $features[] = $clean;
            if (count($features) >= $maxFeatures) {
                break;
            }
        }
        return ['type' => 'FeatureCollection', 'features' => $features];
    }

    public function bbox(array $geometry): ?array
    {
        $flat = [];
        $this->walkCoords($geometry['coordinates'] ?? [], $flat);
        if (!$flat) {
            return null;
        }
        $xs = array_column($flat, 0);
        $ys = array_column($flat, 1);
        return [min($xs), min($ys), max($xs), max($ys)];
    }

    private function validCoordinates(string $type, $coords): bool
    {
        $flat = [];
        if ($type === 'Point') {
            if (!$this->isLonLat($coords)) {
                return false;
            }
            return true;
        }
        if ($type === 'LineString') {
            if (!is_array($coords) || count($coords) < 2) {
                return false;
            }
            $this->walkCoords($coords, $flat);
            return count($flat) <= self::MAX_COORDS && count($flat) >= 2;
        }
        if ($type === 'Polygon') {
            if (!is_array($coords) || !$coords || !is_array($coords[0]) || count($coords[0]) < 4) {
                return false;
            }
            $this->walkCoords($coords, $flat);
            return count($flat) <= self::MAX_COORDS && count($flat) >= 4;
        }
        return false;
    }

    private function walkCoords($node, array &$flat): void
    {
        if ($this->isLonLat($node)) {
            $flat[] = [(float)$node[0], (float)$node[1]];
            return;
        }
        if (!is_array($node)) {
            return;
        }
        foreach ($node as $child) {
            $this->walkCoords($child, $flat);
        }
    }

    private function isLonLat($node): bool
    {
        if (!is_array($node) || !isset($node[0], $node[1]) || is_array($node[0]) || is_array($node[1])) {
            return false;
        }
        if (!is_numeric($node[0]) || !is_numeric($node[1])) {
            return false;
        }
        $lon = (float)$node[0];
        $lat = (float)$node[1];
        return $lon >= -180 && $lon <= 180 && $lat >= -90 && $lat <= 90;
    }
}
