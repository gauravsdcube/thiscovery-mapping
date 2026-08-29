<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\modules\comment\widgets\Comments;
use humhub\modules\like\widgets\LikeLink;
use humhub\modules\thiscoveryMapping\models\Map;
use humhub\modules\thiscoveryMapping\models\MapContribution;
use yii\helpers\Html;

/** @var Map $map */
/** @var MapContribution $contribution */

$responses = $contribution->getResponses();
?>
<div class="tm-detail">
    <p class="tm-detail__meta">
        <strong><?= Html::encode(Map::geometryTypeLabels()[$contribution->geometry_type] ?? $contribution->geometry_type) ?></strong>
        <?php if ($contribution->content->createdBy): ?>
            · <?= Html::encode($contribution->content->createdBy->displayName) ?>
        <?php endif; ?>
        <?php if ($contribution->status === MapContribution::STATUS_PENDING): ?>
            · <span class="label label-warning"><?= Yii::t('ThiscoveryMappingModule.base', 'Pending') ?></span>
        <?php endif; ?>
    </p>
    <?php if ($contribution->comment): ?>
        <p><?= nl2br(Html::encode($contribution->comment)) ?></p>
    <?php endif; ?>
    <?php foreach ($map->getQuestions() as $q): ?>
        <?php $val = $responses[$q['key']] ?? ''; ?>
        <?php if ($val !== ''): ?>
            <p><span class="text-muted"><?= Html::encode($q['label']) ?></span><br><?= Html::encode((string)$val) ?></p>
        <?php endif; ?>
    <?php endforeach; ?>
    <div class="tm-detail__engage">
        <?= LikeLink::widget(['object' => $contribution]) ?>
        <?= Comments::widget(['object' => $contribution]) ?>
    </div>
</div>
