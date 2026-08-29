<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryMapping\controllers;

use humhub\components\access\ControllerAccess;
use humhub\components\Controller;
use humhub\modules\thiscoveryMapping\services\GeocodeService;
use Yii;
use yii\web\Response;

class GeocodeController extends Controller
{
    protected $access = ControllerAccess::class;

    protected function getAccessRules()
    {
        return [
            ['guestAccess' => ['search']],
        ];
    }

    public function actionSearch()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $q = (string)Yii::$app->request->get('q', '');
        $lat = Yii::$app->request->get('lat');
        $lng = Yii::$app->request->get('lng');
        $focusLat = is_numeric($lat) ? (float)$lat : null;
        $focusLng = is_numeric($lng) ? (float)$lng : null;
        return ['results' => (new GeocodeService())->search($q, 8, $focusLat, $focusLng)];
    }
}
