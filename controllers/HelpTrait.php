<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryMapping\controllers;

use humhub\modules\admin\permissions\ManageModules;
use humhub\modules\space\models\Space;
use humhub\modules\thiscoveryMapping\permissions\CreateGlobalMap;
use humhub\modules\thiscoveryMapping\permissions\CreateMap;
use humhub\modules\thiscoveryMapping\permissions\ManageGlobalMap;
use humhub\modules\thiscoveryMapping\permissions\ManageMap;
use humhub\modules\thiscoveryMapping\services\HelpService;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * In-product Help for map creators and administrators.
 */
trait HelpTrait
{
    protected function helpContainer()
    {
        return property_exists($this, 'contentContainer') ? $this->contentContainer : null;
    }

    public function canViewHelp(): bool
    {
        if (Yii::$app->user->isGuest) {
            return false;
        }
        if (Yii::$app->user->isAdmin() || Yii::$app->user->can(ManageModules::class)) {
            return true;
        }

        $container = $this->helpContainer();
        if ($container instanceof Space) {
            $pm = $container->getPermissionManager();
            return $pm->can(CreateMap::class) || $pm->can(ManageMap::class);
        }

        return Yii::$app->user->can(CreateGlobalMap::class)
            || Yii::$app->user->can(ManageGlobalMap::class);
    }

    public function actionHelp($page = null)
    {
        if (!$this->canViewHelp()) {
            throw new ForbiddenHttpException();
        }

        $container = $this->helpContainer();
        $page = trim((string)$page);
        if ($page !== '') {
            $article = HelpService::render($page, $container);
            if (!$article) {
                throw new NotFoundHttpException();
            }
            return $this->render('@thiscovery-mapping/views/help/page', [
                'article' => $article,
                'contentContainer' => $container,
                'sections' => HelpService::sections(),
                'pages' => HelpService::pages(),
            ]);
        }

        return $this->render('@thiscovery-mapping/views/help/index', [
            'contentContainer' => $container,
            'sections' => HelpService::sections(),
            'pages' => HelpService::pages(),
        ]);
    }
}
