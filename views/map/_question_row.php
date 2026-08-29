<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use yii\helpers\Html;

/** @var int|string $i */
/** @var array $q */
/** @var array $questionTypes */

$q = $q ?? ['label' => '', 'type' => 'text', 'options' => [], 'required' => false];
$i = (string)$i;
$options = $q['options'] ?? [];
$optionsValue = is_array($options) ? implode("\n", $options) : (string)$options;
?>
<div class="tm-repeat-row tm-edit__question">
    <div class="row">
        <div class="col-md-5">
            <label class="tm-edit__mini"><?= Yii::t('ThiscoveryMappingModule.base', 'Question') ?></label>
            <input class="form-control" name="questions[<?= Html::encode($i) ?>][label]" placeholder="<?= Html::encode(Yii::t('ThiscoveryMappingModule.base', 'e.g. Why did you mark this place?')) ?>" value="<?= Html::encode($q['label'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <label class="tm-edit__mini"><?= Yii::t('ThiscoveryMappingModule.base', 'Answer type') ?></label>
            <select class="form-control" name="questions[<?= Html::encode($i) ?>][type]">
                <?php foreach ($questionTypes as $t => $l): ?>
                    <option value="<?= Html::encode($t) ?>" <?= ($q['type'] ?? 'text') === $t ? 'selected' : '' ?>><?= Html::encode($l) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="tm-edit__mini"><?= Yii::t('ThiscoveryMappingModule.base', 'Options') ?></label>
            <input class="form-control" name="questions[<?= Html::encode($i) ?>][options]" placeholder="<?= Html::encode(Yii::t('ThiscoveryMappingModule.base', 'For dropdown or choice: one option per line')) ?>" value="<?= Html::encode($optionsValue) ?>">
        </div>
    </div>
    <label class="checkbox-inline">
        <input type="checkbox" name="questions[<?= Html::encode($i) ?>][required]" value="1" <?= !empty($q['required']) ? 'checked' : '' ?>>
        <?= Yii::t('ThiscoveryMappingModule.base', 'Required') ?>
    </label>
</div>
