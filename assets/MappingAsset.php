<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryMapping\assets;

use yii\web\AssetBundle;
use yii\web\View;

class MappingAsset extends AssetBundle
{
    public $sourcePath = '@thiscovery-mapping/resources';

    public $css = [
        'vendor/leaflet/leaflet.css',
        'vendor/markercluster/MarkerCluster.css',
        'vendor/markercluster/MarkerCluster.Default.css',
        'vendor/geoman/leaflet-geoman.css',
        'vendor/fullscreen/Control.FullScreen.css',
        'css/thiscovery-mapping.css',
    ];

    public $js = [
        'vendor/leaflet/leaflet.js',
        'vendor/markercluster/leaflet.markercluster.js',
        'vendor/geoman/leaflet-geoman.min.js',
        'vendor/fullscreen/Control.FullScreen.js',
        'js/humhub.thiscoveryMapping.js',
    ];

    public $depends = [
        'humhub\assets\CoreApiAsset',
    ];

    public $jsOptions = [
        'position' => View::POS_END,
    ];

    public $publishOptions = [
        'forceCopy' => true,
    ];
}
