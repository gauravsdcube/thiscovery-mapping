<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryMapping\widgets;

use humhub\modules\content\widgets\stream\WallStreamModuleEntryWidget;
use humhub\modules\thiscoveryMapping\models\Map;

/**
 * @property Map $model
 */
class WallEntry extends WallStreamModuleEntryWidget
{
    public $editRoute = '/thiscovery-mapping/map/edit';

    public function renderContent()
    {
        return $this->render('@thiscovery-mapping/views/widgets/wall-entry', [
            'map' => $this->model,
        ]);
    }

    protected function getTitle()
    {
        return $this->model->title;
    }

    public function getEditUrl()
    {
        if ($this->model->isGlobal() || !$this->model->canManage()) {
            return null;
        }
        return $this->model->content->container->createUrl($this->editRoute, ['id' => $this->model->id]);
    }
}
