<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryMapping;

use humhub\commands\IntegrityController;
use humhub\helpers\ControllerHelper;
use humhub\modules\admin\permissions\ManageModules;
use humhub\modules\admin\widgets\AdminMenu;
use humhub\modules\thiscoveryMapping\helpers\Url;
use humhub\modules\thiscoveryMapping\models\Map;
use humhub\modules\thiscoveryMapping\models\MapContribution;
use humhub\modules\thiscoveryMapping\permissions\CreateGlobalMap;
use humhub\modules\thiscoveryMapping\permissions\ManageGlobalMap;
use humhub\modules\space\models\Space;
use humhub\modules\space\widgets\Menu;
use humhub\modules\ui\menu\MenuLink;
use humhub\modules\user\models\User;
use humhub\widgets\TopMenu;
use Yii;
use yii\base\Event;

class Events
{
    private static bool $pageBuilderRegistered = false;

    public static function registerPageBuilder(): void
    {
        if (self::$pageBuilderRegistered) {
            return;
        }
        if (!class_exists(\humhub\modules\thiscoveryPageBuilder\services\BlockRegistry::class)) {
            return;
        }
        Event::on(
            \humhub\modules\thiscoveryPageBuilder\services\BlockRegistry::class,
            \humhub\modules\thiscoveryPageBuilder\services\BlockRegistry::EVENT_REGISTER,
            [self::class, 'onRegisterPageBlocks']
        );
        self::$pageBuilderRegistered = true;
    }

    public static function onSpaceMenuInit($event): void
    {
        /** @var Menu $menu */
        $menu = $event->sender;
        $space = $menu->space ?? null;
        if (!$space instanceof Space || !$space->moduleManager->isEnabled('thiscovery-mapping')) {
            return;
        }

        $probe = new Map($space);
        if (!$probe->canCreate() && !$probe->canManage() && Map::find()->contentContainer($space)->count() === 0) {
            return;
        }

        $menu->addEntry(new MenuLink([
            'label' => Yii::t('ThiscoveryMappingModule.base', 'Maps'),
            'url' => Url::toIndex($space),
            'icon' => 'map-marker',
            'isActive' => ControllerHelper::isActivePath('thiscovery-mapping', 'map'),
            'sortOrder' => 410,
        ]));
    }

    public static function onTopMenuInit($event): void
    {
        if (class_exists(\humhub\modules\thiscoveryNavigation\helpers\Navigation::class)
            && \humhub\modules\thiscoveryNavigation\helpers\Navigation::isActive()) {
            return;
        }

        $module = Yii::$app->getModule('thiscovery-mapping');
        if (!$module || !$module->getIsEnabled()) {
            return;
        }

        $count = Map::find()->joinWith('content')->andWhere(['content.contentcontainer_id' => null])->count();
        if ((int)$count === 0) {
            return;
        }

        /** @var TopMenu $menu */
        $menu = $event->sender;
        $menu->addEntry(new MenuLink([
            'label' => Yii::t('ThiscoveryMappingModule.base', 'Maps'),
            'id' => 'thiscovery-mapping-top',
            'url' => Url::toIndex(null),
            'icon' => 'map-marker',
            'isActive' => ControllerHelper::isActivePath('thiscovery-mapping', 'global'),
            'sortOrder' => 410,
        ]));
    }

    public static function onAdminMenuInit($event): void
    {
        if (Yii::$app->user->isGuest) {
            return;
        }

        $allowed = Yii::$app->user->isAdmin()
            || Yii::$app->user->can(ManageModules::class)
            || Yii::$app->user->can(ManageGlobalMap::class)
            || Yii::$app->user->can(CreateGlobalMap::class);

        if (!$allowed || !Yii::$app->getModule('thiscovery-mapping')) {
            return;
        }

        /** @var AdminMenu $menu */
        $menu = $event->sender;
        $menu->addEntry(new MenuLink([
            'label' => Yii::t('ThiscoveryMappingModule.base', 'Thiscovery Mapping'),
            'id' => 'thiscovery-mapping-admin',
            'icon' => 'map-marker',
            'url' => ['/thiscovery-mapping/admin/index'],
            'sortOrder' => 556,
            'isActive' => ControllerHelper::isActivePath('thiscovery-mapping', 'admin')
                || ControllerHelper::isActivePath('thiscovery-mapping', 'global'),
            'isVisible' => true,
        ]));
    }

    public static function onUserDelete($event): void
    {
        if (!isset($event->sender) || !$event->sender instanceof User) {
            return;
        }
        foreach (MapContribution::find()
            ->joinWith('content')
            ->andWhere(['content.created_by' => $event->sender->id])
            ->each(50) as $row) {
            $row->hardDelete();
        }
    }

    public static function onIntegrityCheck($event): void
    {
        /** @var IntegrityController $integrity */
        $integrity = $event->sender;
        $integrity->showTestHeading('Thiscovery Mapping');

        foreach (MapContribution::find()->each(100) as $row) {
            if (Map::findOne($row->map_id) === null) {
                $integrity->showFixing('Orphan map contribution #' . $row->id);
                $row->hardDelete();
            }
        }
    }

    public static function onRegisterPageBlocks(Event $event): void
    {
        $module = Yii::$app->getModule('thiscovery-mapping');
        if (!$module || !$module->getIsEnabled()) {
            return;
        }
        if (!$event instanceof \humhub\modules\thiscoveryPageBuilder\services\RegisterBlocksEvent) {
            return;
        }
        $type = \humhub\modules\thiscoveryMapping\blocks\MapEmbedBlock::TYPE;
        $event->types[$type] = \humhub\modules\thiscoveryMapping\blocks\MapEmbedBlock::class;
        foreach ($event->palette as $item) {
            if (($item['type'] ?? '') === $type) {
                return;
            }
        }
        $event->palette[] = [
            'type' => $type,
            'icon' => 'fa-map-marker',
            'group' => 'engagement',
        ];
    }
}
