<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\modules\thiscoveryMapping\assets\MappingFormAsset;
use humhub\modules\thiscoveryMapping\helpers\Url;
use humhub\modules\thiscoveryMapping\models\Map;
use humhub\modules\thiscoveryMapping\models\MapLayer;
use humhub\modules\thiscoveryMapping\models\ModuleSettings;
use yii\helpers\Html;

/** @var Map $map */
/** @var bool $isNew */

MappingFormAsset::register($this);

$this->title = $isNew
    ? Yii::t('ThiscoveryMappingModule.base', 'Create map')
    : Yii::t('ThiscoveryMappingModule.base', 'Edit map');

$categories = $map->getCategories() ?: [['key' => '', 'name' => '', 'color' => '#1d70b8']];
$questions = $map->getQuestions() ?: [];
$layers = $map->isNewRecord ? [] : $map->layers;
$allowed = $map->getAllowedGeometryTypes();
$questionTypes = [
    'text' => Yii::t('ThiscoveryMappingModule.base', 'Short text'),
    'textarea' => Yii::t('ThiscoveryMappingModule.base', 'Long text'),
    'dropdown' => Yii::t('ThiscoveryMappingModule.base', 'Dropdown'),
    'radio' => Yii::t('ThiscoveryMappingModule.base', 'Choice'),
];
?>
<div class="panel panel-default">
    <div class="panel-heading"><?= Html::encode($this->title) ?></div>
    <div class="panel-body tm-edit">
        <p class="tm-edit__lead">
            <?= Yii::t('ThiscoveryMappingModule.base', 'Only a title is required. The rest can stay at the defaults until you need them.') ?>
        </p>
        <form method="post">
            <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->csrfToken ?>">

            <section class="tm-edit__section">
                <h4><?= Yii::t('ThiscoveryMappingModule.base', 'About this map') ?></h4>
                <p class="help-block"><?= Yii::t('ThiscoveryMappingModule.base', 'Shown in lists, on the map page, and when you embed the map.') ?></p>
                <div class="form-group">
                    <label><?= Yii::t('ThiscoveryMappingModule.base', 'Title') ?></label>
                    <input class="form-control" name="title" required maxlength="255" value="<?= Html::encode($map->title) ?>">
                </div>
                <div class="form-group">
                    <label><?= Yii::t('ThiscoveryMappingModule.base', 'Description') ?>
                        <span class="text-muted"><?= Yii::t('ThiscoveryMappingModule.base', '(optional)') ?></span>
                    </label>
                    <textarea class="form-control" name="description" rows="3" placeholder="<?= Html::encode(Yii::t('ThiscoveryMappingModule.base', 'What should people add, and why?')) ?>"><?= Html::encode((string)$map->description) ?></textarea>
                    <p class="help-block"><?= Yii::t('ThiscoveryMappingModule.base', 'A short intro above the map. Leave blank if the title is enough.') ?></p>
                </div>
            </section>

            <section class="tm-edit__section" data-tm-place-wrap>
                <h4><?= Yii::t('ThiscoveryMappingModule.base', 'Starting view') ?></h4>
                <p class="help-block"><?= Yii::t('ThiscoveryMappingModule.base', 'Where the map opens. Search for a place rather than looking up coordinates. People can still pan and zoom afterwards.') ?></p>
                <?= $this->render('_place_search') ?>
                <?= $this->render('_preview_map', ['map' => $map]) ?>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><?= Yii::t('ThiscoveryMappingModule.base', 'Centre latitude') ?></label>
                            <input class="form-control" name="center_lat" type="number" step="0.0000001" data-tm-lat value="<?= Html::encode($map->center_lat) ?>">
                            <p class="help-block"><?= Yii::t('ThiscoveryMappingModule.base', 'Filled by the place search. North–south position.') ?></p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><?= Yii::t('ThiscoveryMappingModule.base', 'Centre longitude') ?></label>
                            <input class="form-control" name="center_lng" type="number" step="0.0000001" data-tm-lng value="<?= Html::encode($map->center_lng) ?>">
                            <p class="help-block"><?= Yii::t('ThiscoveryMappingModule.base', 'Filled by the place search. East–west position. Negative values are west of Greenwich.') ?></p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><?= Yii::t('ThiscoveryMappingModule.base', 'Zoom') ?></label>
                            <input class="form-control" name="zoom" type="number" min="1" max="20" data-tm-zoom value="<?= (int)$map->zoom ?>">
                            <p class="help-block"><?= Yii::t('ThiscoveryMappingModule.base', '1 is the whole world, about 7 is a region, 14 is a neighbourhood, 18 is street level.') ?></p>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label><?= Yii::t('ThiscoveryMappingModule.base', 'Basemap style') ?></label>
                    <select class="form-control" name="basemap_style">
                        <?php foreach (ModuleSettings::styleLabels() as $value => $label): ?>
                            <option value="<?= Html::encode($value) ?>" <?= $map->getBasemapStyle() === $value ? 'selected' : '' ?>><?= Html::encode($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="help-block"><?= Yii::t('ThiscoveryMappingModule.base', 'The background map. Smooth is a pale street map; satellite shows aerial photography. This does not change what people can draw.') ?></p>
                </div>
            </section>

            <section class="tm-edit__section">
                <h4><?= Yii::t('ThiscoveryMappingModule.base', 'What people can add') ?></h4>
                <div class="form-group">
                    <label><?= Yii::t('ThiscoveryMappingModule.base', 'Drawing types') ?></label>
                    <p class="help-block"><?= Yii::t('ThiscoveryMappingModule.base', 'Choose at least one. Tick only what you need so the drawing tools stay simple.') ?></p>
                    <?php foreach (Map::geometryTypeLabels() as $type => $label): ?>
                        <label class="checkbox-inline">
                            <input type="checkbox" name="allowed_types[]" value="<?= Html::encode($type) ?>" <?= in_array($type, $allowed, true) ? 'checked' : '' ?>>
                            <?= Html::encode($label) ?>
                        </label>
                    <?php endforeach; ?>
                    <p class="help-block tm-edit__after-checks">
                        <?= Yii::t('ThiscoveryMappingModule.base', 'Pins mark a place. Lines mark a route or boundary. Areas mark a neighbourhood, site, or zone.') ?>
                    </p>
                </div>
                <div class="form-group">
                    <label><?= Yii::t('ThiscoveryMappingModule.base', 'Who can see contributions') ?></label>
                    <select class="form-control" name="visibility_mode">
                        <?php foreach (Map::visibilityLabels() as $value => $label): ?>
                            <option value="<?= Html::encode($value) ?>" <?= $map->visibility_mode === $value ? 'selected' : '' ?>><?= Html::encode($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="help-block"><?= Yii::t('ThiscoveryMappingModule.base', 'Everyone: all drawings are public. Own only: each person sees only what they added. Moderated: drawings stay hidden until a map manager approves them.') ?></p>
                </div>
                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="clustering" value="1" <?= $map->clustering ? 'checked' : '' ?>>
                        <?= Yii::t('ThiscoveryMappingModule.base', 'Group nearby pins into clusters') ?>
                    </label>
                    <p class="help-block"><?= Yii::t('ThiscoveryMappingModule.base', 'Recommended when many pins overlap. Clusters open into individual pins as people zoom in. Lines and areas are not clustered.') ?></p>
                </div>
            </section>

            <section class="tm-edit__section">
                <h4><?= Yii::t('ThiscoveryMappingModule.base', 'Categories') ?>
                    <span class="text-muted"><?= Yii::t('ThiscoveryMappingModule.base', '(optional)') ?></span>
                </h4>
                <p class="help-block"><?= Yii::t('ThiscoveryMappingModule.base', 'Let people tag a drawing, for example Housing or Transport. Each category has its own colour. Skip this if every drawing is the same kind of thing.') ?></p>
                <div id="tm-categories">
                    <?php foreach ($categories as $i => $cat): ?>
                        <?= $this->render('_category_row', ['i' => $i, 'cat' => $cat]) ?>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn btn-sm btn-default" data-tm-add="categories"><?= Yii::t('ThiscoveryMappingModule.base', 'Add category') ?></button>
            </section>

            <section class="tm-edit__section">
                <h4><?= Yii::t('ThiscoveryMappingModule.base', 'Extra questions') ?>
                    <span class="text-muted"><?= Yii::t('ThiscoveryMappingModule.base', '(optional)') ?></span>
                </h4>
                <p class="help-block"><?= Yii::t('ThiscoveryMappingModule.base', 'Asked in a small form when someone saves a drawing, alongside an optional comment. Keep this to a few short questions. For a full survey, use a Thiscovery Form instead.') ?></p>
                <div id="tm-questions">
                    <?php foreach ($questions as $i => $q): ?>
                        <?= $this->render('_question_row', ['i' => $i, 'q' => $q, 'questionTypes' => $questionTypes]) ?>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn btn-sm btn-default" data-tm-add="questions"><?= Yii::t('ThiscoveryMappingModule.base', 'Add question') ?></button>
            </section>

            <section class="tm-edit__section">
                <h4><?= Yii::t('ThiscoveryMappingModule.base', 'Background layers') ?>
                    <span class="text-muted"><?= Yii::t('ThiscoveryMappingModule.base', '(optional)') ?></span>
                </h4>
                <p class="help-block"><?= Yii::t('ThiscoveryMappingModule.base', 'Show existing data under people’s drawings, such as ward boundaries or a council dataset. Use HTTPS links only. Skip this unless you have a GIS feed to overlay.') ?></p>
                <ul class="help-block tm-edit__list">
                    <li><?= Yii::t('ThiscoveryMappingModule.base', 'GeoJSON URL — a public .geojson or FeatureCollection file.') ?></li>
                    <li><?= Yii::t('ThiscoveryMappingModule.base', 'WMS — a map image service. Put the layer names in the WMS field, as listed by the service.') ?></li>
                    <li><?= Yii::t('ThiscoveryMappingModule.base', 'ArcGIS FeatureServer — a FeatureServer or MapServer/query endpoint. This site fetches the features.') ?></li>
                    <li><?= Yii::t('ThiscoveryMappingModule.base', 'KML URL — a public KML file. Converted to drawings on this site.') ?></li>
                </ul>
                <div id="tm-layers">
                    <?php foreach ($layers as $i => $layer): ?>
                        <?php $cfg = $layer->getConfig(); ?>
                        <?= $this->render('_layer_row', [
                            'i' => $i,
                            'layer' => $layer,
                            'cfg' => $cfg,
                        ]) ?>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn btn-sm btn-default" data-tm-add="layers"><?= Yii::t('ThiscoveryMappingModule.base', 'Add layer') ?></button>
            </section>

            <div class="tm-edit__actions">
                <button class="btn btn-primary" type="submit"><?= Yii::t('ThiscoveryMappingModule.base', 'Save') ?></button>
                <a class="btn btn-default" href="<?= Html::encode($isNew ? Url::toIndex($map->isGlobal() ? null : ($map->content->container ?? null)) : Url::toView($map)) ?>"><?= Yii::t('ThiscoveryMappingModule.base', 'Cancel') ?></a>
            </div>
        </form>
        <template id="tm-tpl-categories">
            <?= $this->render('_category_row', [
                'i' => '__I__',
                'cat' => ['key' => '', 'name' => '', 'color' => '#1d70b8'],
            ]) ?>
        </template>
        <template id="tm-tpl-questions">
            <?= $this->render('_question_row', [
                'i' => '__I__',
                'q' => ['label' => '', 'type' => 'text', 'options' => [], 'required' => false],
                'questionTypes' => $questionTypes,
            ]) ?>
        </template>
        <template id="tm-tpl-layers">
            <?= $this->render('_layer_row', [
                'i' => '__I__',
                'layer' => new MapLayer(['type' => MapLayer::TYPE_GEOJSON_URL, 'name' => '', 'enabled' => 1, 'description' => '']),
                'cfg' => ['url' => '', 'layers' => '', 'popupFields' => []],
            ]) ?>
        </template>
    </div>
</div>
