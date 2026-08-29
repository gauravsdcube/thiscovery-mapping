<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryMapping\permissions;

use humhub\libs\BasePermission;
use humhub\modules\space\models\Space;
use Yii;

class ManageMap extends BasePermission
{
    protected $moduleId = 'thiscovery-mapping';

    public $defaultAllowedGroups = [
        Space::USERGROUP_OWNER,
        Space::USERGROUP_ADMIN,
        Space::USERGROUP_MODERATOR,
    ];

    protected $fixedGroups = [
        Space::USERGROUP_OWNER,
        Space::USERGROUP_ADMIN,
        Space::USERGROUP_USER,
        Space::USERGROUP_GUEST,
    ];

    public function getTitle()
    {
        return Yii::t('ThiscoveryMappingModule.base', 'Manage maps');
    }

    public function getDescription()
    {
        return Yii::t('ThiscoveryMappingModule.base', 'Allows editing maps, layers, and moderating contributions.');
    }
}
