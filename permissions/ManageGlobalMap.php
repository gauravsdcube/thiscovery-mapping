<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryMapping\permissions;

use humhub\modules\admin\components\BaseAdminPermission;
use Yii;

class ManageGlobalMap extends BaseAdminPermission
{
    protected $id = 'thiscovery_mapping_manage_global';
    protected $moduleId = 'thiscovery-mapping';

    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->title = Yii::t('ThiscoveryMappingModule.base', 'Manage global maps');
        $this->description = Yii::t('ThiscoveryMappingModule.base', 'Allows editing network-level maps and moderating contributions.');
    }
}
