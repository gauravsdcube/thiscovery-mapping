<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryMapping\models;

use humhub\modules\thiscoveryMapping\Module;
use Yii;
use yii\base\Model;

class ModuleSettings extends Model
{
    public $provider = Module::PROVIDER_STADIA;
    public $apiKey = '';
    public $style = Module::STADIA_DEFAULT_STYLE;
    public $customTileUrl = '';
    public $attribution = '';
    public $geocoder = Module::GEOCODER_STADIA;
    public $centerLat = '52.4862';
    public $centerLng = '-1.8904';
    public $zoom = 7;

    public function init()
    {
        parent::init();
        $module = Module::instance();
        if (!$module) {
            return;
        }
        $this->provider = $module->getBasemapProvider();
        $this->apiKey = $module->getApiKey();
        $this->style = $module->getBasemapStyle();
        $this->customTileUrl = $module->getCustomTileUrl();
        $this->attribution = $module->getAttribution();
        $this->geocoder = $module->getGeocoderProvider();
        $this->centerLat = (string)$module->getDefaultCenterLat();
        $this->centerLng = (string)$module->getDefaultCenterLng();
        $this->zoom = $module->getDefaultZoom();
    }

    public function rules()
    {
        return [
            [['provider'], 'in', 'range' => [Module::PROVIDER_STADIA, Module::PROVIDER_CUSTOM]],
            [['geocoder'], 'in', 'range' => [Module::GEOCODER_STADIA, Module::GEOCODER_NONE]],
            [['apiKey', 'style', 'customTileUrl', 'attribution'], 'string', 'max' => 500],
            [['centerLat'], 'number', 'min' => -90, 'max' => 90],
            [['centerLng'], 'number', 'min' => -180, 'max' => 180],
            [['zoom'], 'integer', 'min' => 1, 'max' => 20],
            [['customTileUrl'], 'url', 'skipOnEmpty' => true, 'defaultScheme' => null, 'pattern' => '/^https:\/\/.+/i'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'provider' => Yii::t('ThiscoveryMappingModule.base', 'Basemap provider'),
            'apiKey' => Yii::t('ThiscoveryMappingModule.base', 'Stadia API key'),
            'style' => Yii::t('ThiscoveryMappingModule.base', 'Stadia style'),
            'customTileUrl' => Yii::t('ThiscoveryMappingModule.base', 'Custom tile URL'),
            'attribution' => Yii::t('ThiscoveryMappingModule.base', 'Attribution'),
            'geocoder' => Yii::t('ThiscoveryMappingModule.base', 'Place search'),
            'centerLat' => Yii::t('ThiscoveryMappingModule.base', 'Default latitude'),
            'centerLng' => Yii::t('ThiscoveryMappingModule.base', 'Default longitude'),
            'zoom' => Yii::t('ThiscoveryMappingModule.base', 'Default zoom'),
        ];
    }

    public function attributeHints()
    {
        return [
            'provider' => Yii::t('ThiscoveryMappingModule.base', 'Stadia Maps EU is the default. Use custom XYZ only if you already have a tile service.'),
            'apiKey' => Yii::t('ThiscoveryMappingModule.base', 'Required for the background map and place search. Without it, the map shows a 401 authentication error. Paste the key from your Stadia account, then allow this website as an HTTP referrer in the Stadia dashboard.'),
            'style' => Yii::t('ThiscoveryMappingModule.base', 'Default background for new maps. Each map can override this.'),
            'customTileUrl' => Yii::t('ThiscoveryMappingModule.base', 'Only used when the provider is Custom XYZ. Must be https and include {z}/{x}/{y}.'),
            'attribution' => Yii::t('ThiscoveryMappingModule.base', 'Copyright text shown on the map. Required by most tile providers.'),
            'geocoder' => Yii::t('ThiscoveryMappingModule.base', 'Place search is sent through this site so the API key is not exposed in the browser.'),
            'centerLat' => Yii::t('ThiscoveryMappingModule.base', 'Starting north–south position for new maps.'),
            'centerLng' => Yii::t('ThiscoveryMappingModule.base', 'Starting east–west position for new maps.'),
            'zoom' => Yii::t('ThiscoveryMappingModule.base', 'Starting zoom for new maps. About 7 is a UK region.'),
        ];
    }

    public static function providerLabels(): array
    {
        return [
            Module::PROVIDER_STADIA => Yii::t('ThiscoveryMappingModule.base', 'Stadia Maps (EU)'),
            Module::PROVIDER_CUSTOM => Yii::t('ThiscoveryMappingModule.base', 'Custom XYZ tiles'),
        ];
    }

    public static function styleLabels(): array
    {
        return [
            'alidade_smooth' => 'Alidade Smooth',
            'alidade_smooth_dark' => 'Alidade Smooth Dark',
            'alidade_satellite' => 'Alidade Satellite',
            'osm_bright' => 'OSM Bright',
            'outdoors' => 'Outdoors',
            'stamen_toner' => 'Stamen Toner',
            'stamen_terrain' => 'Stamen Terrain',
            'stamen_watercolor' => 'Stamen Watercolor',
        ];
    }

    public static function geocoderLabels(): array
    {
        return [
            Module::GEOCODER_STADIA => Yii::t('ThiscoveryMappingModule.base', 'Stadia Maps (EU, via this site)'),
            Module::GEOCODER_NONE => Yii::t('ThiscoveryMappingModule.base', 'Off'),
        ];
    }

    public function save(): bool
    {
        if (!$this->validate()) {
            return false;
        }
        $module = Module::instance();
        if (!$module) {
            return false;
        }
        $module->settings->set(Module::SETTING_PROVIDER, $this->provider);
        $module->settings->set(Module::SETTING_API_KEY, trim((string)$this->apiKey));
        $module->settings->set(Module::SETTING_STYLE, $this->style ?: Module::STADIA_DEFAULT_STYLE);
        $module->settings->set(Module::SETTING_TILE_URL, trim((string)$this->customTileUrl));
        $module->settings->set(Module::SETTING_ATTRIBUTION, trim((string)$this->attribution));
        $module->settings->set(Module::SETTING_GEOCODER, $this->geocoder);
        $module->settings->set(Module::SETTING_CENTER_LAT, (string)$this->centerLat);
        $module->settings->set(Module::SETTING_CENTER_LNG, (string)$this->centerLng);
        $module->settings->set(Module::SETTING_ZOOM, (string)(int)$this->zoom);
        return true;
    }
}
