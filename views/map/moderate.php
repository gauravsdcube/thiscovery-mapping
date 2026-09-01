<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\modules\thiscoveryMapping\assets\MappingAsset;
use humhub\modules\thiscoveryMapping\helpers\Url;
use humhub\modules\thiscoveryMapping\models\Map;
use humhub\modules\thiscoveryMapping\models\MapContribution;
use humhub\widgets\bootstrap\Button;
use yii\helpers\Html;

/** @var Map $map */
/** @var MapContribution[] $pending */

MappingAsset::register($this);

$this->title = Yii::t('ThiscoveryMappingModule.base', 'Moderate') . ': ' . $map->title;
$backUrl = Url::toIndex($map->isGlobal() ? null : ($map->content->container ?? null));
?>
<div class="panel panel-default">
    <div class="panel-heading tm-view-heading">
        <?= Html::encode($this->title) ?>
        <div class="tm-view-heading__actions">
            <?= Button::light(Yii::t('ThiscoveryMappingModule.base', 'Back to maps'))
                ->link($backUrl)
                ->icon('arrow-left')
                ->sm()
                ->loader(false) ?>
            <?= Button::light(Yii::t('ThiscoveryMappingModule.base', 'Back to map'))
                ->link(Url::toView($map))
                ->sm()
                ->loader(false) ?>
        </div>
    </div>
    <div class="panel-body">
        <?php if (!$pending): ?>
            <p class="text-muted"><?= Yii::t('ThiscoveryMappingModule.base', 'Nothing waiting for review.') ?></p>
        <?php else: ?>
            <?php foreach ($pending as $row): ?>
                <div class="media">
                    <div class="media-body">
                        <strong><?= Html::encode($row->content->createdBy?->displayName ?? '') ?></strong>
                        · <?= Html::encode(Map::geometryTypeLabels()[$row->geometry_type] ?? '') ?>
                        <div><?= nl2br(Html::encode((string)$row->comment)) ?></div>
                        <form method="post" style="margin-top:8px">
                            <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->csrfToken ?>">
                            <input type="hidden" name="featureId" value="<?= (int)$row->id ?>">
                            <button class="btn btn-sm btn-primary" name="status" value="approved"><?= Yii::t('ThiscoveryMappingModule.base', 'Approve') ?></button>
                            <button class="btn btn-sm btn-danger" name="status" value="rejected"><?= Yii::t('ThiscoveryMappingModule.base', 'Reject') ?></button>
                        </form>
                    </div>
                </div>
                <hr>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
