<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\modules\thiscoveryMapping\assets\MappingAsset;
use humhub\modules\thiscoveryMapping\helpers\Url;
use humhub\modules\thiscoveryMapping\models\Map;
use humhub\widgets\bootstrap\Badge;
use humhub\widgets\bootstrap\Button;
use yii\helpers\Html;
use yii\web\View;

/** @var Map[] $maps */
/** @var bool $canCreate */
/** @var bool $canConfigure */
/** @var bool $canViewHelp */
/** @var \humhub\modules\content\components\ContentContainerActiveRecord|null $container */

MappingAsset::register($this);

$canCreate = !empty($canCreate);
$canConfigure = !empty($canConfigure);
$canViewHelp = !empty($canViewHelp);
$container = $container ?? null;
$isNetwork = $container === null;
$visibilityShort = Map::visibilityShortLabels();
$filters = [
    'q' => trim((string) Yii::$app->request->get('q', '')),
    'visibility' => (string) Yii::$app->request->get('visibility', ''),
];
$hasFilters = $filters['q'] !== '' || $filters['visibility'] !== '';

$filtered = array_values(array_filter($maps, static function (Map $map) use ($filters) {
    if ($filters['visibility'] !== '' && $map->visibility_mode !== $filters['visibility']) {
        return false;
    }
    if ($filters['q'] !== '') {
        $haystack = mb_strtolower($map->title . ' ' . (string) $map->description);
        if (!str_contains($haystack, mb_strtolower($filters['q']))) {
            return false;
        }
    }
    return true;
}));

$indexUrl = Url::toIndex($container);
$nothingYet = $maps === [] && !$hasFilters;
$shown = count($filtered);

$this->title = Yii::t('ThiscoveryMappingModule.base', 'Maps');
?>
<div class="tm-list-page">
    <div class="tm-list-header">
        <div>
            <h1 class="tm-list-title"><?= Yii::t('ThiscoveryMappingModule.base', 'Maps') ?></h1>
            <p class="tm-list-sub">
                <?= $isNetwork
                    ? Yii::t('ThiscoveryMappingModule.base', 'Browse, open, and manage network-level maps.')
                    : Yii::t('ThiscoveryMappingModule.base', 'Browse, open, and manage maps in this space.') ?>
            </p>
        </div>
        <div class="tm-list-header__actions">
            <?php if ($canConfigure): ?>
                <?= Button::light(Yii::t('ThiscoveryMappingModule.base', 'Configuration'))
                    ->link(Url::toSettings())
                    ->icon('cog')
                    ->loader(false) ?>
            <?php endif; ?>
            <?php if ($canViewHelp): ?>
                <?= Button::light(Yii::t('ThiscoveryMappingModule.base', 'Help'))
                    ->link(Url::toHelp($container))
                    ->icon('question-circle')
                    ->loader(false) ?>
            <?php endif; ?>
            <?php if ($canCreate): ?>
                <?= Button::primary(Yii::t('ThiscoveryMappingModule.base', 'Create map'))
                    ->link(Url::toCreate($container))
                    ->icon('plus')
                    ->loader(false) ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($nothingYet): ?>
        <div class="tm-list-empty">
            <i class="fa fa-map-marker"></i>
            <h3><?= Yii::t('ThiscoveryMappingModule.base', 'No maps yet.') ?></h3>
            <?php if ($canCreate): ?>
                <p><?= Yii::t('ThiscoveryMappingModule.base', 'Create a map people can explore and contribute to.') ?></p>
                <?= Button::primary(Yii::t('ThiscoveryMappingModule.base', 'Create map'))
                    ->link(Url::toCreate($container))
                    ->loader(false) ?>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <form method="get" action="<?= Html::encode($indexUrl) ?>" class="tm-list-filters" data-pjax-prevent>
            <div class="tm-list-filters__search">
                <i class="fa fa-search" aria-hidden="true"></i>
                <input type="search" name="q" value="<?= Html::encode($filters['q']) ?>"
                       placeholder="<?= Html::encode(Yii::t('ThiscoveryMappingModule.base', 'Search maps')) ?>"
                       aria-label="<?= Html::encode(Yii::t('ThiscoveryMappingModule.base', 'Search maps')) ?>">
            </div>
            <select name="visibility" aria-label="<?= Html::encode(Yii::t('ThiscoveryMappingModule.base', 'Visibility')) ?>" data-tm-auto-submit>
                <option value=""><?= Yii::t('ThiscoveryMappingModule.base', 'All visibilities') ?></option>
                <?php foreach ($visibilityShort as $value => $label): ?>
                    <option value="<?= Html::encode($value) ?>"<?= $filters['visibility'] === $value ? ' selected' : '' ?>>
                        <?= Html::encode($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-default btn-sm"><?= Yii::t('ThiscoveryMappingModule.base', 'Search') ?></button>
            <?php if ($hasFilters): ?>
                <a class="btn btn-link btn-sm" href="<?= Html::encode($indexUrl) ?>"><?= Yii::t('ThiscoveryMappingModule.base', 'Clear') ?></a>
            <?php endif; ?>
        </form>

        <?php if ($shown === 0): ?>
            <div class="tm-list-empty">
                <i class="fa fa-search"></i>
                <h3><?= Yii::t('ThiscoveryMappingModule.base', 'No matching maps.') ?></h3>
                <p><?= Yii::t('ThiscoveryMappingModule.base', 'Try a different search or clear the filters.') ?></p>
            </div>
        <?php else: ?>
            <div class="tm-map-table-wrap">
                <table class="tm-map-table">
                    <thead>
                    <tr>
                        <th class="tm-map-table__actions-col"><?= Yii::t('ThiscoveryMappingModule.base', 'Actions') ?></th>
                        <th class="tm-map-table__status-col"><?= Yii::t('ThiscoveryMappingModule.base', 'Visibility') ?></th>
                        <th class="tm-map-table__map-col"><?= Yii::t('ThiscoveryMappingModule.base', 'Map') ?></th>
                        <th class="tm-map-table__num-col"><?= Yii::t('ThiscoveryMappingModule.base', 'Contributions') ?></th>
                        <th class="tm-map-table__date-col"><?= Yii::t('ThiscoveryMappingModule.base', 'Date created') ?></th>
                        <th class="tm-map-table__date-col"><?= Yii::t('ThiscoveryMappingModule.base', 'Date modified') ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($filtered as $map): ?>
                        <?php
                        $canManage = $map->canManage();
                        $createdAt = $map->content->created_at ?? null;
                        $updatedAt = $map->content->updated_at ?? null;
                        $count = (int) ($map->contributions_count ?? $map->getContributions()->count());
                        $pending = (int) ($map->pending_count ?? 0);
                        $vis = $visibilityShort[$map->visibility_mode] ?? $map->visibility_mode;
                        ?>
                        <tr class="tm-map-row">
                            <td class="tm-map-table__actions-col">
                                <div class="tm-map-row__actions">
                                    <?= Button::primary(Yii::t('ThiscoveryMappingModule.base', 'Open'))
                                        ->link(Url::toView($map))
                                        ->sm()
                                        ->loader(false) ?>
                                    <?php if ($canManage): ?>
                                        <?= Button::light()
                                            ->link(Url::toEdit($map))
                                            ->sm()
                                            ->icon('pencil')
                                            ->tooltip(Yii::t('ThiscoveryMappingModule.base', 'Edit'))
                                            ->loader(false) ?>
                                        <?php if ($map->visibility_mode === Map::VISIBILITY_MODERATED): ?>
                                            <?= Button::light()
                                                ->link(Url::toModerate($map))
                                                ->sm()
                                                ->icon('gavel')
                                                ->tooltip(Yii::t('ThiscoveryMappingModule.base', 'Moderate') . ($pending ? ' (' . $pending . ')' : ''))
                                                ->loader(false) ?>
                                        <?php endif; ?>
                                        <?= Html::beginForm(Url::toDelete($map), 'post', [
                                            'class' => 'tm-map-row__delete',
                                            'data-pjax-prevent' => true,
                                        ]) ?>
                                            <?= Button::danger()
                                                ->confirm(Yii::t('ThiscoveryMappingModule.base', 'Delete this map and all contributions?'))
                                                ->submit()
                                                ->sm()
                                                ->icon('trash')
                                                ->tooltip(Yii::t('ThiscoveryMappingModule.base', 'Delete'))
                                                ->loader(false) ?>
                                        <?= Html::endForm() ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="tm-map-table__status-col">
                                <?php if ($map->visibility_mode === Map::VISIBILITY_MODERATED): ?>
                                    <?= Badge::warning($vis) ?>
                                <?php elseif ($map->visibility_mode === Map::VISIBILITY_OWN): ?>
                                    <?= Badge::light($vis) ?>
                                <?php else: ?>
                                    <?= Badge::success($vis) ?>
                                <?php endif; ?>
                            </td>
                            <td class="tm-map-table__map-col">
                                <div class="tm-map-row__title">
                                    <?= Html::a(Html::encode($map->title), Url::toView($map)) ?>
                                </div>
                                <?php if ($map->description): ?>
                                    <div class="tm-map-row__desc">
                                        <?= Html::encode(mb_strimwidth(strip_tags((string) $map->description), 0, 120, '…')) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="tm-map-table__num-col">
                                <span class="tm-map-row__stat"><?= $count ?></span>
                                <?php if ($pending > 0): ?>
                                    <div class="tm-map-row__desc"><?= Yii::t('ThiscoveryMappingModule.base', '{n} pending', ['n' => $pending]) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="tm-map-table__date-col">
                                <?= $createdAt ? Html::encode(Yii::$app->formatter->asDatetime($createdAt, 'short')) : '—' ?>
                            </td>
                            <td class="tm-map-table__date-col">
                                <?= $updatedAt ? Html::encode(Yii::$app->formatter->asDatetime($updatedAt, 'short')) : '—' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="tm-list-pager">
                <div class="tm-list-pager__count">
                    <?= Yii::t('ThiscoveryMappingModule.base', '{n,plural,=1{1 map} other{# maps}}', ['n' => $shown]) ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php
$this->registerJs('humhub.require("thiscoveryMapping").initListMaps();', View::POS_READY);
