<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryMapping\permissions;

use humhub\modules\admin\components\BaseAdminPermission;
use Yii;

class ContributeGlobalMap extends BaseAdminPermission
{
    protected $id = 'thiscovery_mapping_contribute_global';
    protected $moduleId = 'thiscovery-mapping';
    protected $defaultState = self::STATE_ALLOW;

    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->title = Yii::t('ThiscoveryMappingModule.base', 'Contribute to global maps');
        $this->description = Yii::t('ThiscoveryMappingModule.base', 'Allows placing pins, lines, and areas on network-level maps.');
    }
}
