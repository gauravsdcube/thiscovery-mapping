<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryMapping\controllers;

use humhub\modules\admin\components\Controller;
use humhub\modules\admin\permissions\ManageModules;
use humhub\modules\thiscoveryMapping\assets\MappingAsset;
use humhub\modules\thiscoveryMapping\models\Map;
use humhub\modules\thiscoveryMapping\models\ModuleSettings;
use humhub\modules\thiscoveryMapping\permissions\CreateGlobalMap;
use humhub\modules\thiscoveryMapping\permissions\ManageGlobalMap;
use Yii;
use yii\web\ForbiddenHttpException;

/**
 * Network-level maps inside the Administration layout, same pattern as Thiscovery Forms.
 */
class AdminController extends Controller
{
    use HelpTrait;

    public $adminOnly = false;

    protected function getAccessRules()
    {
        return [
            ['login'],
            ['checkCanManageMaps'],
        ];
    }

    public function checkCanManageMaps($rule, $access): bool
    {
        if (Yii::$app->user->isGuest) {
            return false;
        }

        $action = Yii::$app->controller->action->id ?? '';
        if ($action === 'settings') {
            return Yii::$app->user->isAdmin() || Yii::$app->user->can(ManageModules::class);
        }

        return Yii::$app->user->isAdmin()
            || Yii::$app->user->can(ManageModules::class)
            || Yii::$app->user->can(ManageGlobalMap::class)
            || Yii::$app->user->can(CreateGlobalMap::class);
    }

    public function actionIndex()
    {
        if (!$this->checkCanManageMaps([], null)) {
            throw new ForbiddenHttpException();
        }

        MappingAsset::register($this->view);

        $query = Map::find()
            ->joinWith('content')
            ->andWhere(['content.contentcontainer_id' => null])
            ->orderBy(['title' => SORT_ASC]);

        $maps = [];
        foreach ($query->all() as $map) {
            if ($map->content->canView() || Yii::$app->user->isAdmin()) {
                $maps[] = $map;
            }
        }
        Map::attachListCounts($maps);

        $probe = new Map();
        return $this->render('index', [
            'maps' => $maps,
            'canCreate' => $probe->canCreate() || Yii::$app->user->isAdmin(),
            'canConfigure' => Yii::$app->user->isAdmin() || Yii::$app->user->can(ManageModules::class),
            'canViewHelp' => $this->canViewHelp(),
            'container' => null,
        ]);
    }

    public function actionSettings()
    {
        if (!Yii::$app->user->isAdmin() && !Yii::$app->user->can(ManageModules::class)) {
            throw new ForbiddenHttpException();
        }

        $model = new ModuleSettings();
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            $this->view->saved();
            return $this->redirect(['settings']);
        }

        return $this->render('settings', ['model' => $model]);
    }
}
