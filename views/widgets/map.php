<?php

use humhub\helpers\Html;

/** @var string $id */
/** @var array $config */
/** @var int $height */
/** @var string $mode */

$mode = $config['mode'] ?? '';
$showSearch = !empty($config['showSearch']) && !empty($config['urls']['geocode']);
$showFilters = !empty($config['showFilters']);
$showCategories = $showFilters && !empty($config['categories']);
$showToolbar = $mode !== 'empty' && $mode !== 'form' && ($showSearch || $showFilters);
$json = json_encode($config, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>
<div class="tm-shell" data-tm-map="<?= Html::encode($id) ?>" style="--tm-h: <?= (int)$height ?>px">
    <?php if (!empty($config['basemap']['needsAuth'])): ?>
        <div class="tm-tile-error" data-tm-tile-error>
            <?= Html::encode($config['tileError'] ?? Yii::t('ThiscoveryMappingModule.base', 'The map background could not load (401). Add a Stadia API key under Administration → Modules → Thiscovery Mapping, and allow this website as an HTTP referrer in the Stadia dashboard.')) ?>
            <?php if (!empty($config['settingsUrl'])): ?>
                <a href="<?= Html::encode($config['settingsUrl']) ?>"><?= Yii::t('ThiscoveryMappingModule.base', 'Open mapping settings') ?></a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <?php if ($showToolbar): ?>
        <div class="tm-toolbar">
            <?php if ($showSearch): ?>
                <div class="tm-search">
                    <input type="search" class="form-control" data-tm-search placeholder="<?= Html::encode(Yii::t('ThiscoveryMappingModule.base', 'Search a place, postcode or address')) ?>" autocomplete="off">
                    <ul class="tm-search__results" data-tm-search-results hidden></ul>
                </div>
            <?php endif; ?>
            <?php if ($showFilters): ?>
                <div class="tm-filters">
                    <?php if ($showCategories): ?>
                        <select class="form-control" data-tm-filter="category">
                            <option value=""><?= Yii::t('ThiscoveryMappingModule.base', 'All categories') ?></option>
                            <?php foreach ($config['categories'] as $cat): ?>
                                <option value="<?= Html::encode($cat['key']) ?>"><?= Html::encode($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                    <select class="form-control" data-tm-filter="type">
                        <option value=""><?= Yii::t('ThiscoveryMappingModule.base', 'All types') ?></option>
                        <option value="Point"><?= Yii::t('ThiscoveryMappingModule.base', 'Points') ?></option>
                        <option value="LineString"><?= Yii::t('ThiscoveryMappingModule.base', 'Lines') ?></option>
                        <option value="Polygon"><?= Yii::t('ThiscoveryMappingModule.base', 'Areas') ?></option>
                    </select>
                    <input type="date" class="form-control" data-tm-filter="from" aria-label="<?= Html::encode(Yii::t('ThiscoveryMappingModule.base', 'From')) ?>">
                    <input type="date" class="form-control" data-tm-filter="to" aria-label="<?= Html::encode(Yii::t('ThiscoveryMappingModule.base', 'To')) ?>">
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($config['contributeHint'])): ?>
        <p class="tm-contribute-hint"><?= Html::encode($config['contributeHint']) ?></p>
    <?php endif; ?>
    <div class="tm-canvas" id="<?= Html::encode($id) ?>"></div>
    <?php if (($config['mode'] ?? '') !== 'form'): ?>
        <aside class="tm-drawer" data-tm-drawer hidden>
            <div class="tm-drawer__head">
                <h3 class="tm-drawer__title" data-tm-drawer-title></h3>
                <button type="button" class="tm-drawer__close" data-tm-close aria-label="<?= Html::encode(Yii::t('ThiscoveryMappingModule.base', 'Close')) ?>">&times;</button>
            </div>
            <div class="tm-drawer__body" data-tm-drawer-body></div>
        </aside>
    <?php endif; ?>
    <?php if (!empty($config['privacy'])): ?>
        <p class="tm-privacy"><?= Html::encode($config['privacy']) ?></p>
    <?php endif; ?>
    <script type="application/json" data-tm-config><?= $json ?></script>
</div>
