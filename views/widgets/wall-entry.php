<?php

use humhub\modules\thiscoveryMapping\helpers\Url;
use humhub\modules\thiscoveryMapping\models\Map;
use yii\helpers\Html;

/** @var Map $map */
?>
<div>
    <?php if ($map->description): ?>
        <p class="text-muted"><?= Html::encode(mb_strimwidth($map->description, 0, 180, '…')) ?></p>
    <?php endif; ?>
    <a class="btn btn-primary btn-sm" href="<?= Html::encode(Url::toView($map)) ?>">
        <?= Yii::t('ThiscoveryMappingModule.base', 'Open map') ?>
    </a>
</div>
