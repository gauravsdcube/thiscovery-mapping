<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryMapping\services;

use humhub\modules\thiscoveryMapping\models\Map;
use humhub\modules\thiscoveryMapping\Module;

class BasemapService
{
    /**
     * Leaflet tile config. API keys are only appended for Stadia when set;
     * restrict the key by HTTP referrer in the Stadia dashboard.
     */
    public function leafletConfig(?Map $map = null): array
    {
        $module = Module::instance();
        $attribution = $module ? $module->getAttribution() : Module::STADIA_DEFAULT_ATTRIBUTION;
        $provider = $module ? $module->getBasemapProvider() : Module::PROVIDER_STADIA;
        $style = $map ? $map->getBasemapStyle() : ($module ? $module->getBasemapStyle() : Module::STADIA_DEFAULT_STYLE);

        if ($provider === Module::PROVIDER_CUSTOM && $module && $module->getCustomTileUrl() !== '') {
            return [
                'url' => $module->getCustomTileUrl(),
                'attribution' => $attribution,
                'maxZoom' => 20,
                'provider' => Module::PROVIDER_CUSTOM,
            ];
        }

        $url = Module::STADIA_TILE_EU . '/tiles/' . rawurlencode($style) . '/{z}/{x}/{y}{r}.png';
        $key = $module ? $module->getApiKey() : '';
        if ($key !== '') {
            $url .= (str_contains($url, '?') ? '&' : '?') . 'api_key=' . rawurlencode($key);
        }

        return [
            'url' => $url,
            'attribution' => $attribution,
            'maxZoom' => 20,
            'provider' => Module::PROVIDER_STADIA,
            'privacy' => 'stadia-eu',
            'needsAuth' => $key === '',
        ];
    }

    /**
     * Tile config for the create/edit preview. Stadia URLs include a {style} token.
     */
    public function previewConfig(?Map $map = null): array
    {
        $cfg = $this->leafletConfig($map);
        if (($cfg['provider'] ?? '') === Module::PROVIDER_STADIA) {
            $module = Module::instance();
            $style = $map ? $map->getBasemapStyle() : ($module ? $module->getBasemapStyle() : Module::STADIA_DEFAULT_STYLE);
            $cfg['style'] = $style;
            $url = Module::STADIA_TILE_EU . '/tiles/{style}/{z}/{x}/{y}{r}.png';
            $key = $module ? $module->getApiKey() : '';
            if ($key !== '') {
                $url .= '?api_key=' . rawurlencode($key);
            }
            $cfg['urlTemplate'] = $url;
        }
        return $cfg;
    }
}
