<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryMapping\assets;

use yii\web\AssetBundle;
use yii\web\View;

class MappingFormAsset extends AssetBundle
{
    public $sourcePath = '@thiscovery-mapping/resources';

    public $css = [
        'vendor/leaflet/leaflet.css',
        'css/thiscovery-mapping.css',
    ];

    public $js = [
        'vendor/leaflet/leaflet.js',
        'js/humhub.thiscoveryMapping.form.js',
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

    public static function register($view)
    {
        $bundle = parent::register($view);
        $view->registerJsConfig('thiscoveryMapping.form', [
            'leafletSrc' => $bundle->baseUrl . '/vendor/leaflet/leaflet.js',
        ]);
        return $bundle;
    }
}
