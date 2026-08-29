<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\modules\thiscoveryMapping\helpers\Url;
use humhub\modules\thiscoveryMapping\models\Map;
use humhub\widgets\bootstrap\Button;
use yii\helpers\Html;

/** @var Map[] $maps */
/** @var bool $canCreate */
/** @var \humhub\modules\content\components\ContentContainerActiveRecord|null $container */

$this->title = Yii::t('ThiscoveryMappingModule.base', 'Maps');
?>
<div class="panel panel-default">
    <div class="panel-heading">
        <?= Yii::t('ThiscoveryMappingModule.base', '<strong>Maps</strong>') ?>
        <?php if ($canCreate): ?>
            <div class="pull-right">
                <?= Button::primary(Yii::t('ThiscoveryMappingModule.base', 'Create map'))
                    ->link(Url::toCreate($container))
                    ->icon('plus')
                    ->sm() ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="panel-body">
        <?php if (!$maps): ?>
            <p class="text-muted"><?= Yii::t('ThiscoveryMappingModule.base', 'No maps yet.') ?></p>
        <?php else: ?>
            <ul class="media-list">
                <?php foreach ($maps as $map): ?>
                    <li class="tm-map-row">
                        <a href="<?= Html::encode(Url::toView($map)) ?>">
                            <strong><?= Html::encode($map->title) ?></strong>
                        </a>
                        <?php if ($map->description): ?>
                            <div class="text-muted small"><?= Html::encode(mb_strimwidth($map->description, 0, 160, '…')) ?></div>
                        <?php endif; ?>
                        <?php if ($map->canManage()): ?>
                            <div class="tm-map-row__actions">
                                <a href="<?= Html::encode(Url::toEdit($map)) ?>"><?= Yii::t('ThiscoveryMappingModule.base', 'Edit') ?></a>
                                <?php if ($map->visibility_mode === Map::VISIBILITY_MODERATED): ?>
                                    · <a href="<?= Html::encode(Url::toModerate($map)) ?>"><?= Yii::t('ThiscoveryMappingModule.base', 'Moderate') ?></a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
