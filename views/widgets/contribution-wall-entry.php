<?php

use humhub\modules\thiscoveryMapping\helpers\Url;
use humhub\modules\thiscoveryMapping\models\MapContribution;
use yii\helpers\Html;

/** @var MapContribution $contribution */
$map = $contribution->map;
?>
<div>
    <p><?= Html::encode($contribution->getContentDescription()) ?></p>
    <?php if ($map): ?>
        <a class="btn btn-sm btn-default" href="<?= Html::encode(Url::toView($map)) ?>">
            <?= Yii::t('ThiscoveryMappingModule.base', 'View map') ?>
        </a>
    <?php endif; ?>
</div>
