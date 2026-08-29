<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\modules\thiscoveryMapping\helpers\Url;
use humhub\modules\thiscoveryMapping\Module;
use yii\helpers\Html;

$module = Yii::$app->getModule('thiscovery-mapping');
$geocodeOn = $module instanceof Module && $module->getGeocoderProvider() === Module::GEOCODER_STADIA;
$geocodeUrl = $geocodeOn ? Url::toGeocode() : '';
?>
<div class="form-group tm-place-search" <?= $geocodeUrl !== '' ? 'data-tm-geocode-url="' . Html::encode($geocodeUrl) . '"' : '' ?> data-tm-empty="<?= Html::encode(Yii::t('ThiscoveryMappingModule.base', 'No places found')) ?>">
    <label><?= Yii::t('ThiscoveryMappingModule.base', 'Find a place') ?></label>
    <?php if ($geocodeUrl !== ''): ?>
        <input type="search" class="form-control" data-tm-place-search autocomplete="off"
               placeholder="<?= Html::encode(Yii::t('ThiscoveryMappingModule.base', 'Address, postcode, town or city')) ?>">
        <ul class="tm-search__results" data-tm-place-results hidden></ul>
        <p class="help-block"><?= Yii::t('ThiscoveryMappingModule.base', 'Type a place and pick a result. Latitude, longitude, and zoom are filled in for you.') ?></p>
    <?php else: ?>
        <p class="help-block"><?= Yii::t('ThiscoveryMappingModule.base', 'Place search needs Stadia Maps in Administration → Modules → Thiscovery Mapping. Until then, enter latitude and longitude below.') ?></p>
    <?php endif; ?>
</div>
