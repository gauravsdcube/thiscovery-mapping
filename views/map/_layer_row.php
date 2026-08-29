<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\modules\thiscoveryMapping\models\MapLayer;
use yii\helpers\Html;

/** @var int|string $i */
/** @var MapLayer $layer */
/** @var array $cfg */

$cfg = $cfg ?? [];
?>
<div class="panel panel-light tm-repeat-row tm-edit__layer">
    <div class="panel-body">
        <input type="hidden" name="layers[<?= Html::encode((string)$i) ?>][id]" value="<?= (int)($layer->id ?? 0) ?>">
        <div class="row">
            <div class="col-md-4">
                <label class="tm-edit__mini"><?= Yii::t('ThiscoveryMappingModule.base', 'Layer name') ?></label>
                <input class="form-control" name="layers[<?= Html::encode((string)$i) ?>][name]" value="<?= Html::encode((string)$layer->name) ?>" placeholder="<?= Html::encode(Yii::t('ThiscoveryMappingModule.base', 'e.g. Ward boundaries')) ?>">
            </div>
            <div class="col-md-4">
                <label class="tm-edit__mini"><?= Yii::t('ThiscoveryMappingModule.base', 'Source type') ?></label>
                <select class="form-control" name="layers[<?= Html::encode((string)$i) ?>][type]">
                    <?php foreach (MapLayer::typeLabels() as $t => $l): ?>
                        <?php if ($t === MapLayer::TYPE_GEOJSON_UPLOAD) {
                            continue;
                        } ?>
                        <option value="<?= Html::encode($t) ?>" <?= $layer->type === $t ? 'selected' : '' ?>><?= Html::encode($l) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="checkbox-inline tm-edit__enabled">
                    <input type="checkbox" name="layers[<?= Html::encode((string)$i) ?>][enabled]" value="1" <?= $layer->enabled ? 'checked' : '' ?>>
                    <?= Yii::t('ThiscoveryMappingModule.base', 'Show on the map') ?>
                </label>
            </div>
        </div>
        <label class="tm-edit__mini"><?= Yii::t('ThiscoveryMappingModule.base', 'HTTPS address') ?></label>
        <input class="form-control" name="layers[<?= Html::encode((string)$i) ?>][url]" value="<?= Html::encode($cfg['url'] ?? '') ?>" placeholder="https://">
        <p class="help-block"><?= Yii::t('ThiscoveryMappingModule.base', 'Must start with https://. Local or private network addresses are blocked.') ?></p>
        <label class="tm-edit__mini"><?= Yii::t('ThiscoveryMappingModule.base', 'WMS layer names') ?></label>
        <input class="form-control" name="layers[<?= Html::encode((string)$i) ?>][wmsLayers]" value="<?= Html::encode($cfg['layers'] ?? '') ?>" placeholder="<?= Html::encode(Yii::t('ThiscoveryMappingModule.base', 'Only for WMS, e.g. boundaries:wards')) ?>">
        <label class="tm-edit__mini"><?= Yii::t('ThiscoveryMappingModule.base', 'Popup attributes') ?></label>
        <input class="form-control" name="layers[<?= Html::encode((string)$i) ?>][popupFields]" value="<?= Html::encode(implode(', ', $cfg['popupFields'] ?? [])) ?>" placeholder="<?= Html::encode(Yii::t('ThiscoveryMappingModule.base', 'Field names to show when a feature is clicked, comma separated')) ?>">
        <label class="tm-edit__mini"><?= Yii::t('ThiscoveryMappingModule.base', 'Internal note') ?></label>
        <input class="form-control" name="layers[<?= Html::encode((string)$i) ?>][description]" value="<?= Html::encode((string)$layer->description) ?>" placeholder="<?= Html::encode(Yii::t('ThiscoveryMappingModule.base', 'Optional note for map managers')) ?>">
        <input type="hidden" name="layers[<?= Html::encode((string)$i) ?>][fileGuid]" value="<?= Html::encode($cfg['fileGuid'] ?? '') ?>">
    </div>
</div>
