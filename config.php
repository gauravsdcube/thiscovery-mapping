<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\commands\IntegrityController;
use humhub\modules\admin\widgets\AdminMenu;
use humhub\modules\thiscoveryMapping\Events;
use humhub\modules\thiscoveryMapping\Module;
use humhub\modules\space\widgets\Menu;
use humhub\modules\user\models\User;
use humhub\widgets\TopMenu;

return [
    'id' => 'thiscovery-mapping',
    'class' => Module::class,
    'namespace' => 'humhub\modules\thiscoveryMapping',
    'events' => [
        ['class' => Menu::class, 'event' => Menu::EVENT_INIT, 'callback' => [Events::class, 'onSpaceMenuInit']],
        ['class' => TopMenu::class, 'event' => TopMenu::EVENT_INIT, 'callback' => [Events::class, 'onTopMenuInit']],
        ['class' => AdminMenu::class, 'event' => AdminMenu::EVENT_INIT, 'callback' => [Events::class, 'onAdminMenuInit']],
        ['class' => User::class, 'event' => User::EVENT_BEFORE_DELETE, 'callback' => [Events::class, 'onUserDelete']],
        ['class' => IntegrityController::class, 'event' => IntegrityController::EVENT_ON_RUN, 'callback' => [Events::class, 'onIntegrityCheck']],
        // String class name so mapping can be enabled without Page Builder installed.
        ['class' => 'humhub\modules\thiscoveryPageBuilder\services\BlockRegistry', 'event' => 'register', 'callback' => [Events::class, 'onRegisterPageBlocks']],
    ],
    'urlManagerRules' => [
        'thiscovery-mapping/global/view/<id:\d+>' => 'thiscovery-mapping/global/view',
        'thiscovery-mapping/global/edit/<id:\d+>' => 'thiscovery-mapping/global/edit',
    ],
];
