<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryMapping;

use humhub\components\console\Application as ConsoleApplication;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\content\components\ContentContainerModule;
use humhub\modules\thiscoveryMapping\models\Map;
use humhub\modules\thiscoveryMapping\models\MapContribution;
use humhub\modules\thiscoveryMapping\permissions\ContributeGlobalMap;
use humhub\modules\thiscoveryMapping\permissions\ContributeMap;
use humhub\modules\thiscoveryMapping\permissions\CreateGlobalMap;
use humhub\modules\thiscoveryMapping\permissions\CreateMap;
use humhub\modules\thiscoveryMapping\permissions\ManageGlobalMap;
use humhub\modules\thiscoveryMapping\permissions\ManageMap;
use humhub\modules\space\models\Space;
use Yii;
use yii\helpers\Url;

class Module extends ContentContainerModule
{
    public const SETTING_PROVIDER = 'basemap_provider';
    public const SETTING_API_KEY = 'basemap_api_key';
    public const SETTING_TILE_URL = 'basemap_tile_url';
    public const SETTING_ATTRIBUTION = 'basemap_attribution';
    public const SETTING_STYLE = 'basemap_style';
    public const SETTING_GEOCODER = 'geocoder_provider';
    public const SETTING_CENTER_LAT = 'default_center_lat';
    public const SETTING_CENTER_LNG = 'default_center_lng';
    public const SETTING_ZOOM = 'default_zoom';

    public const PROVIDER_STADIA = 'stadia';
    public const PROVIDER_CUSTOM = 'custom';

    public const GEOCODER_STADIA = 'stadia';
    public const GEOCODER_NONE = 'none';

    public const STADIA_TILE_EU = 'https://tiles-eu.stadiamaps.com';
    public const STADIA_API_EU = 'https://api-eu.stadiamaps.com';
    public const STADIA_DEFAULT_STYLE = 'alidade_smooth';
    public const STADIA_DEFAULT_ATTRIBUTION = '&copy; <a href="https://stadiamaps.com/" target="_blank" rel="noopener">Stadia Maps</a> &copy; <a href="https://openmaptiles.org/" target="_blank" rel="noopener">OpenMapTiles</a> &copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>';

    public $resourcesPath = 'resources';
    public $icon = 'fa-map-marker';

    public function getName()
    {
        return Yii::t('ThiscoveryMappingModule.base', 'Thiscovery Mapping');
    }

    public function getDescription()
    {
        return Yii::t(
            'ThiscoveryMappingModule.base',
            'Interactive maps people can contribute to. Embed on Thiscovery pages or use as a form question.'
        );
    }

    public function init()
    {
        parent::init();
        if (Yii::$app instanceof ConsoleApplication) {
            $this->controllerNamespace = 'humhub\modules\thiscoveryMapping\commands';
        }
        Events::registerPageBuilder();
    }

    public function getContentContainerTypes()
    {
        return [Space::class];
    }

    public function getContentClasses(): array
    {
        return [Map::class, MapContribution::class];
    }

    public function getPermissions($contentContainer = null)
    {
        if ($contentContainer instanceof Space) {
            return [
                new CreateMap(),
                new ManageMap(),
                new ContributeMap(),
            ];
        }

        if ($contentContainer === null) {
            return [
                new CreateGlobalMap(),
                new ManageGlobalMap(),
                new ContributeGlobalMap(),
            ];
        }

        return [];
    }

    public function getContentContainerName(ContentContainerActiveRecord $container)
    {
        return Yii::t('ThiscoveryMappingModule.base', 'Maps');
    }

    public function getContentContainerDescription(ContentContainerActiveRecord $container)
    {
        return Yii::t('ThiscoveryMappingModule.base', 'Create interactive maps members can contribute to.');
    }

    public function getConfigUrl()
    {
        return Url::to(['/thiscovery-mapping/admin/settings']);
    }

    public function getBasemapProvider(): string
    {
        $value = (string)$this->settings->get(self::SETTING_PROVIDER, self::PROVIDER_STADIA);
        return $value === self::PROVIDER_CUSTOM ? self::PROVIDER_CUSTOM : self::PROVIDER_STADIA;
    }

    public function getApiKey(): string
    {
        return trim((string)$this->settings->get(self::SETTING_API_KEY, ''));
    }

    public function getBasemapStyle(): string
    {
        $style = trim((string)$this->settings->get(self::SETTING_STYLE, self::STADIA_DEFAULT_STYLE));
        return $style !== '' ? $style : self::STADIA_DEFAULT_STYLE;
    }

    public function getCustomTileUrl(): string
    {
        return trim((string)$this->settings->get(self::SETTING_TILE_URL, ''));
    }

    public function getAttribution(): string
    {
        $attr = trim((string)$this->settings->get(self::SETTING_ATTRIBUTION, ''));
        return $attr !== '' ? $attr : self::STADIA_DEFAULT_ATTRIBUTION;
    }

    public function getGeocoderProvider(): string
    {
        $value = (string)$this->settings->get(self::SETTING_GEOCODER, self::GEOCODER_STADIA);
        return $value === self::GEOCODER_NONE ? self::GEOCODER_NONE : self::GEOCODER_STADIA;
    }

    public function getDefaultCenterLat(): float
    {
        $raw = $this->settings->get(self::SETTING_CENTER_LAT, '52.4862');
        $lat = (float)$raw;
        return ($lat >= -90 && $lat <= 90) ? $lat : 52.4862;
    }

    public function getDefaultCenterLng(): float
    {
        $raw = $this->settings->get(self::SETTING_CENTER_LNG, '-1.8904');
        $lng = (float)$raw;
        return ($lng >= -180 && $lng <= 180) ? $lng : -1.8904;
    }

    public function getDefaultZoom(): int
    {
        $zoom = (int)$this->settings->get(self::SETTING_ZOOM, 7);
        if ($zoom < 1) {
            return 1;
        }
        if ($zoom > 20) {
            return 20;
        }
        return $zoom;
    }

    public static function instance(): ?self
    {
        $module = Yii::$app->getModule('thiscovery-mapping');
        return $module instanceof self ? $module : null;
    }

    public function disable()
    {
        foreach (Map::find()->each(50) as $map) {
            $map->hardDelete();
        }
        parent::disable();
    }

    public function disableContentContainer(ContentContainerActiveRecord $container)
    {
        foreach (Map::find()->contentContainer($container)->each(50) as $map) {
            $map->hardDelete();
        }
        parent::disableContentContainer($container);
    }
}
