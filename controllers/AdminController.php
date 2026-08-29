<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryMapping\controllers;

use humhub\modules\admin\components\Controller;
use humhub\modules\admin\permissions\ManageModules;
use humhub\modules\thiscoveryMapping\models\ModuleSettings;
use Yii;
use yii\web\ForbiddenHttpException;

class AdminController extends Controller
{
    public $adminOnly = false;

    protected function getAccessRules()
    {
        return [
            ['login'],
        ];
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
