<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use yii\helpers\Html;

/** @var int|string $i */
/** @var array $cat */

$cat = $cat ?? ['key' => '', 'name' => '', 'color' => '#1d70b8'];
$i = (string)$i;
?>
<div class="row tm-repeat-row" style="margin-bottom:8px">
    <div class="col-md-8">
        <input class="form-control" name="categories[<?= Html::encode($i) ?>][name]" placeholder="<?= Html::encode(Yii::t('ThiscoveryMappingModule.base', 'Category name, e.g. Housing')) ?>" value="<?= Html::encode($cat['name'] ?? '') ?>">
    </div>
    <div class="col-md-4">
        <input class="form-control" name="categories[<?= Html::encode($i) ?>][color]" type="color" value="<?= Html::encode($cat['color'] ?? '#1d70b8') ?>" title="<?= Html::encode(Yii::t('ThiscoveryMappingModule.base', 'Colour on the map')) ?>" aria-label="<?= Html::encode(Yii::t('ThiscoveryMappingModule.base', 'Colour on the map')) ?>">
    </div>
    <input type="hidden" name="categories[<?= Html::encode($i) ?>][key]" value="<?= Html::encode($cat['key'] ?? '') ?>">
</div>
