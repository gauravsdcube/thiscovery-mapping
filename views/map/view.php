<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\modules\thiscoveryMapping\helpers\Url;
use humhub\modules\thiscoveryMapping\models\Map;
use humhub\modules\thiscoveryMapping\widgets\MapWidget;
use yii\helpers\Html;

/** @var Map $map */

$this->title = $map->title;
?>
<div class="panel panel-default">
    <div class="panel-heading">
        <?= Html::encode($map->title) ?>
        <?php if ($map->canManage()): ?>
            <div class="pull-right">
                <a class="btn btn-sm btn-default" href="<?= Html::encode(Url::toEdit($map)) ?>">
                    <?= Yii::t('ThiscoveryMappingModule.base', 'Edit') ?>
                </a>
                <?php if ($map->visibility_mode === Map::VISIBILITY_MODERATED): ?>
                    <a class="btn btn-sm btn-default" href="<?= Html::encode(Url::toModerate($map)) ?>">
                        <?= Yii::t('ThiscoveryMappingModule.base', 'Moderate') ?>
                    </a>
                <?php endif; ?>
                <a class="btn btn-sm btn-default" href="<?= Html::encode(Url::toExport($map, 'geojson')) ?>">GeoJSON</a>
                <a class="btn btn-sm btn-default" href="<?= Html::encode(Url::toExport($map, 'csv')) ?>">CSV</a>
            </div>
        <?php endif; ?>
    </div>
    <div class="panel-body">
        <?php if ($map->description): ?>
            <p><?= nl2br(Html::encode($map->description)) ?></p>
        <?php endif; ?>
        <?= MapWidget::widget(['map' => $map, 'mode' => 'view']) ?>
    </div>
</div>
