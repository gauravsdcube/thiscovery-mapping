<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryMapping\controllers;

use humhub\modules\content\components\ContentContainerController;
use humhub\modules\thiscoveryMapping\models\Map;
use yii\web\NotFoundHttpException;

class MapController extends ContentContainerController
{
    use MapActionsTrait;
    use HelpTrait;

    protected function getAccessRules()
    {
        return [
            ['guestAccess' => ['view', 'features', 'feature-detail', 'layer-data']],
        ];
    }

    protected function newMap(): Map
    {
        return new Map($this->contentContainer);
    }

    protected function findMap($id): Map
    {
        $map = Map::find()->contentContainer($this->contentContainer)->andWhere(['thiscovery_map.id' => (int)$id])->one();
        if (!$map) {
            throw new NotFoundHttpException();
        }
        return $map;
    }

    protected function mapListQuery()
    {
        return Map::find()->contentContainer($this->contentContainer)->orderBy(['title' => SORT_ASC]);
    }
}
