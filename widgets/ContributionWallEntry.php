<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryMapping\widgets;

use humhub\modules\content\widgets\stream\WallStreamModuleEntryWidget;
use humhub\modules\thiscoveryMapping\models\MapContribution;

/**
 * @property MapContribution $model
 */
class ContributionWallEntry extends WallStreamModuleEntryWidget
{
    public function renderContent()
    {
        return $this->render('@thiscovery-mapping/views/widgets/contribution-wall-entry', [
            'contribution' => $this->model,
        ]);
    }

    protected function getTitle()
    {
        return $this->model->getContentDescription();
    }

    public function getEditUrl()
    {
        return null;
    }
}
