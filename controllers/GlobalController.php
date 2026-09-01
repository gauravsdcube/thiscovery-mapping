<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryMapping\controllers;

use humhub\components\Controller;
use humhub\components\access\ControllerAccess;
use humhub\modules\admin\permissions\ManageModules;
use humhub\modules\thiscoveryMapping\models\Map;
use humhub\modules\thiscoveryMapping\permissions\CreateGlobalMap;
use humhub\modules\thiscoveryMapping\permissions\ManageGlobalMap;
use Yii;
use yii\web\NotFoundHttpException;

class GlobalController extends Controller
{
    use MapActionsTrait;

    public $subLayout = '@thiscovery-mapping/views/layouts/admin';

    protected $access = ControllerAccess::class;

    public function beforeAction($action)
    {
        if ($action && in_array($action->id, ['edit', 'moderate'], true)) {
            $this->subLayout = '@humhub/modules/admin/views/layouts/main';
        } elseif ($action && $action->id === 'view' && $this->canUseAdminLayout()) {
            $this->subLayout = '@humhub/modules/admin/views/layouts/main';
        }
        return parent::beforeAction($action);
    }

    private function canUseAdminLayout(): bool
    {
        if (Yii::$app->user->isGuest) {
            return false;
        }

        return Yii::$app->user->isAdmin()
            || Yii::$app->user->can(ManageModules::class)
            || Yii::$app->user->can(ManageGlobalMap::class)
            || Yii::$app->user->can(CreateGlobalMap::class);
    }

    public function actionIndex()
    {
        return $this->redirect(['/thiscovery-mapping/admin/index']);
    }

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
