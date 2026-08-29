<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryMapping\services;

use humhub\modules\thiscoveryMapping\models\Map;
use humhub\modules\thiscoveryMapping\models\MapContribution;
use Yii;

class ExportService
{
    public function geoJson(Map $map): array
    {
        $features = [];
        foreach ($this->visibleQuery($map)->each(100) as $row) {
            /** @var MapContribution $row */
            $features[] = $row->toPublicFeature($map, true);
        }
        return ['type' => 'FeatureCollection', 'features' => $features];
    }

    public function csv(Map $map): string
    {
        $fh = fopen('php://temp', 'r+');
        $questions = $map->getQuestions();
        $header = [
            Yii::t('ThiscoveryMappingModule.base', 'ID'),
            Yii::t('ThiscoveryMappingModule.base', 'Type'),
            Yii::t('ThiscoveryMappingModule.base', 'Category'),
            Yii::t('ThiscoveryMappingModule.base', 'Comment'),
            Yii::t('ThiscoveryMappingModule.base', 'Longitude'),
            Yii::t('ThiscoveryMappingModule.base', 'Latitude'),
            Yii::t('ThiscoveryMappingModule.base', 'WKT'),
            Yii::t('ThiscoveryMappingModule.base', 'Status'),
            Yii::t('ThiscoveryMappingModule.base', 'User'),
            Yii::t('ThiscoveryMappingModule.base', 'Created'),
        ];
        foreach ($questions as $q) {
            $header[] = $q['label'];
        }
        fputcsv($fh, $header);

        foreach ($this->visibleQuery($map)->each(100) as $row) {
            /** @var MapContribution $row */
            $geom = $row->getFeature()['geometry'] ?? [];
            $lon = $lat = '';
            if (($geom['type'] ?? '') === 'Point' && isset($geom['coordinates'][0], $geom['coordinates'][1])) {
                $lon = $geom['coordinates'][0];
                $lat = $geom['coordinates'][1];
            }
            $line = [
                $row->id,
                $row->geometry_type,
                $row->category_key,
                (string)$row->comment,
                $lon,
                $lat,
                $this->wkt($geom),
                $row->status,
                $row->content->createdBy?->displayName ?? '',
                $row->content->created_at ?? '',
            ];
            $responses = $row->getResponses();
            foreach ($questions as $q) {
                $val = $responses[$q['key']] ?? '';
                $line[] = is_array($val) ? implode('; ', $val) : (string)$val;
            }
            fputcsv($fh, $line);
        }
        rewind($fh);
        return stream_get_contents($fh) ?: '';
    }

    private function visibleQuery(Map $map)
    {
        return MapContribution::find()->andWhere(['map_id' => $map->id])->orderBy(['id' => SORT_ASC]);
    }

    private function wkt(array $geom): string
    {
        $type = $geom['type'] ?? '';
        $c = $geom['coordinates'] ?? [];
        if ($type === 'Point' && isset($c[0], $c[1])) {
            return sprintf('POINT(%s %s)', $c[0], $c[1]);
        }
        if ($type === 'LineString' && is_array($c)) {
            $pairs = [];
            foreach ($c as $p) {
                if (isset($p[0], $p[1])) {
                    $pairs[] = $p[0] . ' ' . $p[1];
                }
            }
            return 'LINESTRING(' . implode(', ', $pairs) . ')';
        }
        if ($type === 'Polygon' && isset($c[0]) && is_array($c[0])) {
            $pairs = [];
            foreach ($c[0] as $p) {
                if (isset($p[0], $p[1])) {
                    $pairs[] = $p[0] . ' ' . $p[1];
                }
            }
            return 'POLYGON((' . implode(', ', $pairs) . '))';
        }
        return '';
    }
}
