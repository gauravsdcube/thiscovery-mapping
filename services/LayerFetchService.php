<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryMapping\services;

use humhub\modules\file\models\File;
use humhub\modules\thiscoveryMapping\models\MapLayer;
use Yii;

class LayerFetchService
{
    public const MAX_BYTES = 1500000;

    public function fetch(MapLayer $layer): ?array
    {
        $cfg = $layer->getConfig();
        if (in_array($layer->type, [MapLayer::TYPE_GEOJSON_URL, MapLayer::TYPE_ARCGIS, MapLayer::TYPE_KML], true)) {
            $url = $this->safeUrl((string)($cfg['url'] ?? ''));
            if ($url === null) {
                return null;
            }
            if ($layer->type === MapLayer::TYPE_ARCGIS) {
                $url = $this->arcgisQueryUrl($url);
            }
            $body = $this->get($url);
            if ($body === null) {
                return null;
            }
            if ($layer->type === MapLayer::TYPE_KML) {
                return $this->kmlToGeoJson($body);
            }
            $decoded = json_decode($body, true);
            return is_array($decoded) ? $decoded : null;
        }

        if ($layer->type === MapLayer::TYPE_GEOJSON_UPLOAD) {
            $guid = (string)($cfg['fileGuid'] ?? '');
            if ($guid === '') {
                return null;
            }
            $file = File::findOne(['guid' => $guid]);
            if (!$file || !is_readable($file->store->get())) {
                return null;
            }
            $size = filesize($file->store->get());
            if ($size === false || $size > self::MAX_BYTES) {
                return null;
            }
            $decoded = json_decode((string)file_get_contents($file->store->get()), true);
            return is_array($decoded) ? $decoded : null;
        }

        return null;
    }

    public function safeUrl(string $url): ?string
    {
        $url = trim($url);
        if ($url === '' || !preg_match('#^https://#i', $url)) {
            return null;
        }
        $parts = parse_url($url);
        $host = strtolower((string)($parts['host'] ?? ''));
        if ($host === '' || $host === 'localhost' || str_ends_with($host, '.local')) {
            return null;
        }
        $ip = gethostbyname($host);
        if ($ip === $host) {
            // DNS failed — still reject obvious IPs in host
            if (filter_var($host, FILTER_VALIDATE_IP) && !$this->publicIp($host)) {
                return null;
            }
        } elseif (!$this->publicIp($ip)) {
            return null;
        }
        return $url;
    }

    private function publicIp(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $long = ip2long($ip);
            $private = [
                ['0.0.0.0', '0.255.255.255'],
                ['10.0.0.0', '10.255.255.255'],
                ['127.0.0.0', '127.255.255.255'],
                ['169.254.0.0', '169.254.255.255'],
                ['172.16.0.0', '172.31.255.255'],
                ['192.168.0.0', '192.168.255.255'],
            ];
            foreach ($private as [$from, $to]) {
                if ($long >= ip2long($from) && $long <= ip2long($to)) {
                    return false;
                }
            }
            return true;
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            if ($ip === '::1' || str_starts_with(strtolower($ip), 'fc') || str_starts_with(strtolower($ip), 'fd') || str_starts_with(strtolower($ip), 'fe80')) {
                return false;
            }
            return true;
        }
        return false;
    }

    private function arcgisQueryUrl(string $url): string
    {
        $url = rtrim($url, '/');
        if (!preg_match('#/query$#i', $url)) {
            $url .= '/query';
        }
        $sep = str_contains($url, '?') ? '&' : '?';
        return $url . $sep . http_build_query([
            'f' => 'geojson',
            'where' => '1=1',
            'outFields' => '*',
            'returnGeometry' => 'true',
            'resultRecordCount' => 2000,
        ]);
    }

    private function get(string $url): ?string
    {
        try {
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 8,
                    'header' => "Accept: application/json,application/vnd.google-earth.kml+xml,text/xml,*/*\r\nUser-Agent: ThiscoveryMapping/1.0\r\n",
                    'follow_location' => 0,
                ],
                'ssl' => [
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                ],
            ]);
            $body = @file_get_contents($url, false, $context);
            if (!is_string($body) || strlen($body) > self::MAX_BYTES) {
                return null;
            }
            return $body;
        } catch (\Throwable $e) {
            Yii::warning('Layer fetch failed: ' . $e->getMessage(), 'thiscovery-mapping');
            return null;
        }
    }

    private function kmlToGeoJson(string $kml): ?array
    {
        $prev = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($kml, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA);
        libxml_use_internal_errors($prev);
        if ($xml === false) {
            return null;
        }
        $xml->registerXPathNamespace('k', 'http://www.opengis.net/kml/2.2');
        $placemarks = $xml->xpath('//k:Placemark') ?: $xml->xpath('//Placemark') ?: [];
        $features = [];
        foreach ($placemarks as $place) {
            $name = trim((string)($place->name ?? ''));
            $coords = trim((string)($place->Point->coordinates ?? $place->LineString->coordinates ?? $place->Polygon->outerBoundaryIs->LinearRing->coordinates ?? ''));
            if ($coords === '') {
                continue;
            }
            $points = [];
            foreach (preg_split('/\s+/', $coords) ?: [] as $pair) {
                $parts = explode(',', $pair);
                if (count($parts) >= 2 && is_numeric($parts[0]) && is_numeric($parts[1])) {
                    $points[] = [(float)$parts[0], (float)$parts[1]];
                }
            }
            if (!$points) {
                continue;
            }
            if (isset($place->Point)) {
                $geom = ['type' => 'Point', 'coordinates' => $points[0]];
            } elseif (isset($place->LineString)) {
                $geom = ['type' => 'LineString', 'coordinates' => $points];
            } else {
                if ($points[0] !== $points[count($points) - 1]) {
                    $points[] = $points[0];
                }
                $geom = ['type' => 'Polygon', 'coordinates' => [$points]];
            }
            $features[] = [
                'type' => 'Feature',
                'geometry' => $geom,
                'properties' => ['name' => $name],
            ];
        }
        return ['type' => 'FeatureCollection', 'features' => $features];
    }
}
