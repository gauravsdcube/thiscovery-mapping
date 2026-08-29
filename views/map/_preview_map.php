<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\modules\thiscoveryMapping\models\Map;
use humhub\modules\thiscoveryMapping\services\BasemapService;
use yii\helpers\Html;

/** @var Map|null $map */

$preview = (new BasemapService())->previewConfig($map ?? null);
$json = json_encode($preview, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>
<div class="tm-preview">
    <div class="tm-preview__frame">
        <div class="tm-canvas tm-preview__map"
             data-tm-preview-map
             data-tm-preview-config="<?= Html::encode($json) ?>"
             style="height:320px;width:100%"></div>
        <div class="tm-preview__crosshair" aria-hidden="true"></div>
    </div>
    <p class="help-block"><?= Yii::t('ThiscoveryMappingModule.base', 'This is the opening view. Search above, or pan and zoom this map. The circle marks the centre.') ?></p>
    <?php if (!empty($preview['needsAuth'])): ?>
        <p class="tm-tile-error"><?= Yii::t('ThiscoveryMappingModule.base', 'The map background could not load (401). Add a Stadia API key under Administration → Modules → Thiscovery Mapping, and allow this website as an HTTP referrer in the Stadia dashboard.') ?></p>
    <?php endif; ?>
</div>
