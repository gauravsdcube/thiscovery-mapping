<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryMapping\controllers;

use humhub\components\Controller;
use humhub\components\access\ControllerAccess;
use humhub\modules\thiscoveryMapping\models\Map;
use yii\web\NotFoundHttpException;

class GlobalController extends Controller
{
    use MapActionsTrait;

    public $subLayout = '@thiscovery-mapping/views/layouts/admin';

    protected $access = ControllerAccess::class;

    protected function getAccessRules()
    {
        return [
            ['guestAccess' => ['view', 'features', 'feature-detail', 'layer-data']],
            ['login', 'actions' => [
                'index', 'edit', 'delete', 'save-feature', 'delete-feature',
                'moderate', 'export',
            ]],
        ];
    }

    protected function newMap(): Map
    {
        return new Map();
    }

    protected function findMap($id): Map
    {
        $map = Map::findOne((int)$id);
        if (!$map || !$map->isGlobal()) {
            throw new NotFoundHttpException();
        }
        return $map;
    }

    protected function mapListQuery()
    {
        return Map::find()->joinWith('content')->andWhere(['content.contentcontainer_id' => null])->orderBy(['title' => SORT_ASC]);
    }
}
