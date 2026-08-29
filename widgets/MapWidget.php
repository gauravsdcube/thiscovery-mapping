<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryMapping\widgets;

use humhub\components\Widget;
use humhub\modules\thiscoveryMapping\assets\MappingAsset;
use humhub\modules\thiscoveryMapping\helpers\Url;
use humhub\modules\thiscoveryMapping\models\Map;
use humhub\modules\thiscoveryMapping\Module;
use humhub\modules\thiscoveryMapping\services\BasemapService;
use Yii;

class MapWidget extends Widget
{
    public ?Map $map = null;

    /** view|embed|form */
    public string $mode = 'view';

    public string $inputName = '';

    public string $inputValue = '';

    public array $formConfig = [];

    public int $height = 480;

    public bool $readOnly = false;

    public function run()
    {
        MappingAsset::register($this->view);

        $cfg = $this->clientConfig();
        $id = 'tm-map-' . substr(md5(json_encode($cfg) . uniqid('', true)), 0, 10);

        return $this->render('@thiscovery-mapping/views/widgets/map', [
            'id' => $id,
            'config' => $cfg,
            'height' => max(240, min(800, $this->height)),
            'map' => $this->map,
            'mode' => $this->mode,
        ]);
    }

    public function clientConfig(): array
    {
        $module = Module::instance();
        $basemap = (new BasemapService())->leafletConfig($this->map);
        $geocodeOn = $module && $module->getGeocoderProvider() === Module::GEOCODER_STADIA;

        if ($this->mode === 'form') {
            return [
                'mode' => 'form',
                'inputName' => $this->inputName,
                'value' => $this->inputValue,
                'center' => [
                    (float)($this->formConfig['lng'] ?? ($module ? $module->getDefaultCenterLng() : -1.89)),
                    (float)($this->formConfig['lat'] ?? ($module ? $module->getDefaultCenterLat() : 52.48)),
                ],
                'zoom' => (int)($this->formConfig['zoom'] ?? ($module ? $module->getDefaultZoom() : 7)),
                'allowedTypes' => $this->formConfig['allowedTypes'] ?? ['Point'],
                'maxFeatures' => (int)($this->formConfig['maxFeatures'] ?? 1),
                'categories' => $this->formConfig['categories'] ?? [],
                'readOnly' => $this->readOnly,
                'basemap' => $basemap,
                'geocodeUrl' => $geocodeOn ? Url::toGeocode() : '',
                'csrf' => Yii::$app->request->csrfToken,
                'tileError' => Yii::t(
                    'ThiscoveryMappingModule.base',
                    'The map background could not load (401). Add a Stadia API key under Administration → Modules → Thiscovery Mapping, and allow this website as an HTTP referrer in the Stadia dashboard.'
                ),
                'settingsUrl' => '',
            ];
        }

        $map = $this->map;
        if (!$map) {
            return ['mode' => 'empty'];
        }

        $layers = [];
        foreach ($map->layers as $layer) {
            if ($layer->enabled) {
                $layers[] = $layer->toClientConfig();
            }
        }

        return [
            'mode' => $this->mode,
            'mapId' => (int)$map->id,
            'center' => [(float)$map->center_lng, (float)$map->center_lat],
            'zoom' => (int)$map->zoom,
            'allowedTypes' => $map->getAllowedGeometryTypes(),
            'categories' => $map->getCategories(),
            'questions' => $map->getQuestions(),
            'clustering' => (bool)$map->clustering,
            'visibility' => $map->visibility_mode,
            'canContribute' => $map->canContribute(),
            'canManage' => $map->canManage(),
            'basemap' => $basemap,
            'layers' => $layers,
            'urls' => [
                'features' => Url::toFeatures($map),
                'save' => Url::toSaveFeature($map),
                'delete' => Url::toDeleteFeature($map),
                'detail' => Url::toFeatureDetail($map),
                'layer' => Url::toLayerData($map),
                'geocode' => $geocodeOn ? Url::toGeocode() : '',
            ],
            'csrf' => Yii::$app->request->csrfToken,
            'privacy' => $basemap['provider'] === Module::PROVIDER_STADIA
                ? Yii::t('ThiscoveryMappingModule.base', 'Map tiles and place search are provided by Stadia Maps in the EU.')
                : '',
            'tileError' => Yii::t(
                'ThiscoveryMappingModule.base',
                'The map background could not load (401). Add a Stadia API key under Administration → Modules → Thiscovery Mapping, and allow this website as an HTTP referrer in the Stadia dashboard.'
            ),
            'settingsUrl' => ($this->map && $this->map->canManage()) ? Url::toSettings() : '',
        ];
    }
}
