<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryMapping\models;

use humhub\components\ActiveRecord;
use Yii;
use yii\db\ActiveQuery;

/**
 * @property int $id
 * @property int $map_id
 * @property string $type
 * @property string $name
 * @property string|null $description
 * @property int $enabled
 * @property int $sort_order
 * @property string|null $config_json
 *
 * @property-read Map $map
 */
class MapLayer extends ActiveRecord
{
    public const TYPE_GEOJSON_URL = 'geojson_url';
    public const TYPE_GEOJSON_UPLOAD = 'geojson_upload';
    public const TYPE_WMS = 'wms';
    public const TYPE_ARCGIS = 'arcgis';
    public const TYPE_KML = 'kml';

    public static function tableName()
    {
        return 'thiscovery_map_layer';
    }

    public function rules()
    {
        return [
            [['map_id', 'type', 'name'], 'required'],
            [['map_id', 'sort_order'], 'integer'],
            [['enabled'], 'boolean'],
            [['name'], 'string', 'max' => 255],
            [['description'], 'string', 'max' => 500],
            [['type'], 'in', 'range' => array_keys(self::typeLabels())],
            [['config_json'], 'string'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'type' => Yii::t('ThiscoveryMappingModule.base', 'Layer type'),
            'name' => Yii::t('ThiscoveryMappingModule.base', 'Name'),
            'description' => Yii::t('ThiscoveryMappingModule.base', 'Description'),
            'enabled' => Yii::t('ThiscoveryMappingModule.base', 'Enabled'),
        ];
    }

    public static function typeLabels(): array
    {
        return [
            self::TYPE_GEOJSON_URL => Yii::t('ThiscoveryMappingModule.base', 'GeoJSON URL'),
            self::TYPE_GEOJSON_UPLOAD => Yii::t('ThiscoveryMappingModule.base', 'GeoJSON upload'),
            self::TYPE_WMS => Yii::t('ThiscoveryMappingModule.base', 'WMS'),
            self::TYPE_ARCGIS => Yii::t('ThiscoveryMappingModule.base', 'ArcGIS FeatureServer'),
            self::TYPE_KML => Yii::t('ThiscoveryMappingModule.base', 'KML URL'),
        ];
    }

    public function getMap(): ActiveQuery
    {
        return $this->hasOne(Map::class, ['id' => 'map_id']);
    }

    public function getConfig(): array
    {
        $decoded = json_decode((string)$this->config_json, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function setConfig(array $config): void
    {
        $this->config_json = $config ? json_encode($config, JSON_UNESCAPED_UNICODE) : null;
    }

    public function toClientConfig(): array
    {
        $cfg = $this->getConfig();
        $popupFields = [];
        foreach ((array)($cfg['popupFields'] ?? []) as $field) {
            $field = trim((string)$field);
            if ($field !== '' && preg_match('/^[A-Za-z0-9_\.\-]{1,64}$/', $field)) {
                $popupFields[] = $field;
            }
        }
        return [
            'id' => (int)$this->id,
            'type' => $this->type,
            'name' => $this->name,
            'description' => (string)$this->description,
            'enabled' => (bool)$this->enabled,
            'url' => (string)($cfg['url'] ?? ''),
            'layers' => (string)($cfg['layers'] ?? ''),
            'popupFields' => $popupFields,
            'fileGuid' => (string)($cfg['fileGuid'] ?? ''),
        ];
    }
}
