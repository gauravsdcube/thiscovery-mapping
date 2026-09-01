<?php

use humhub\modules\thiscoveryMapping\models\Map;

/** @var Map[] $maps */
/** @var bool $canCreate */
/** @var bool $canConfigure */
/** @var bool $canViewHelp */

echo $this->render('@thiscovery-mapping/views/map/index', [
    'maps' => $maps,
    'canCreate' => $canCreate,
    'canConfigure' => $canConfigure ?? false,
    'canViewHelp' => $canViewHelp ?? false,
    'container' => null,
]);
