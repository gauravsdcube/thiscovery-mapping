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
$type = (string)($q['type'] ?? 'text');
$needsOptions = in_array($type, ['dropdown', 'radio'], true);
$options = $q['options'] ?? [];
if (!is_array($options)) {
    $options = preg_split('/\r\n|\r|\n/', (string)$options) ?: [];
    $options = array_values(array_filter(array_map('trim', $options), static fn($v) => $v !== ''));
}
if ($needsOptions && $options === []) {
    $options = ['', ''];
}
$nameOpts = 'questions[' . $i . '][options][]';
?>
<div class="tm-repeat-row tm-edit__question">
    <div class="row">
        <div class="col-md-7">
            <label class="tm-edit__mini"><?= Yii::t('ThiscoveryMappingModule.base', 'Question') ?></label>
            <input class="form-control" name="questions[<?= Html::encode($i) ?>][label]" placeholder="<?= Html::encode(Yii::t('ThiscoveryMappingModule.base', 'e.g. Why did you mark this place?')) ?>" value="<?= Html::encode($q['label'] ?? '') ?>">
        </div>
        <div class="col-md-5">
            <label class="tm-edit__mini"><?= Yii::t('ThiscoveryMappingModule.base', 'Answer type') ?></label>
            <select class="form-control" name="questions[<?= Html::encode($i) ?>][type]" data-tm-question-type>
                <?php foreach ($questionTypes as $t => $l): ?>
                    <option value="<?= Html::encode($t) ?>" <?= $type === $t ? 'selected' : '' ?>><?= Html::encode($l) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="tm-edit__question-options" data-tm-question-options<?= $needsOptions ? '' : ' hidden' ?>>
        <label class="tm-edit__mini"><?= Yii::t('ThiscoveryMappingModule.base', 'Choices') ?></label>
        <p class="help-block"><?= Yii::t('ThiscoveryMappingModule.base', 'People pick one of these when they save a drawing. Add as many as you need.') ?></p>
        <div class="tm-edit__choice-list" data-tm-choice-list data-name="<?= Html::encode($nameOpts) ?>">
            <?php foreach ($options as $opt): ?>
                <div class="tm-edit__choice">
                    <input class="form-control" name="<?= Html::encode($nameOpts) ?>" value="<?= Html::encode((string)$opt) ?>" placeholder="<?= Html::encode(Yii::t('ThiscoveryMappingModule.base', 'Choice')) ?>">
                    <button type="button" class="tm-edit__choice-remove" data-tm-choice-remove aria-label="<?= Html::encode(Yii::t('ThiscoveryMappingModule.base', 'Remove')) ?>">&times;</button>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="btn btn-sm btn-default" data-tm-choice-add><?= Yii::t('ThiscoveryMappingModule.base', 'Add choice') ?></button>
    </div>
    <label class="checkbox-inline">
        <input type="checkbox" name="questions[<?= Html::encode($i) ?>][required]" value="1" <?= !empty($q['required']) ? 'checked' : '' ?>>
        <?= Yii::t('ThiscoveryMappingModule.base', 'Required') ?>
    </label>
</div>
