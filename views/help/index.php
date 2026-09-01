<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\modules\thiscoveryMapping\assets\MappingAsset;
use humhub\modules\thiscoveryMapping\helpers\Url;
use humhub\widgets\bootstrap\Button;
use yii\helpers\Html;

/** @var $contentContainer */
/** @var array $sections */
/** @var array $pages */

MappingAsset::register($this);
?>

<div class="tm-help-page">
    <div class="tm-help-header">
        <div>
            <div class="tm-help-kicker"><?= Yii::t('ThiscoveryMappingModule.base', 'Thiscovery Mapping') ?></div>
            <h1 class="tm-help-title"><?= Yii::t('ThiscoveryMappingModule.base', 'Help') ?></h1>
            <p class="tm-help-sub">
                <?= Yii::t('ThiscoveryMappingModule.base', 'Guides for administrators and map creators. People only viewing or drawing on a map do not see these pages.') ?>
            </p>
        </div>
        <div class="tm-help-header__actions">
            <?= Button::light(Yii::t('ThiscoveryMappingModule.base', 'Back to maps'))
                ->link(Url::toIndex($contentContainer))
                ->icon('arrow-left')
                ->loader(false) ?>
        </div>
    </div>

    <?php foreach ($sections as $section): ?>
        <section class="tm-help-section">
            <h2 class="tm-help-section__title"><?= Html::encode($section['title']) ?></h2>
            <p class="tm-help-section__intro"><?= Html::encode($section['intro']) ?></p>
            <div class="tm-help-cards">
                <?php foreach ($section['pages'] as $slug): ?>
                    <?php $meta = $pages[$slug] ?? null; ?>
                    <?php if (!$meta) { continue; } ?>
                    <a class="tm-help-card" href="<?= Html::encode(Url::toHelp($contentContainer, $slug)) ?>">
                        <span class="tm-help-card__icon"><i class="fa fa-<?= Html::encode($meta['icon']) ?>" aria-hidden="true"></i></span>
                        <span class="tm-help-card__body">
                            <span class="tm-help-card__title"><?= Html::encode($meta['title']) ?></span>
                            <span class="tm-help-card__summary"><?= Html::encode($meta['summary']) ?></span>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
</div>
