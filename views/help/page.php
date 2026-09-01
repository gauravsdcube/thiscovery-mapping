<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\modules\thiscoveryMapping\assets\MappingAsset;
use humhub\modules\thiscoveryMapping\helpers\Url;
use humhub\widgets\bootstrap\Button;
use yii\helpers\Html;

/** @var array $article */
/** @var $contentContainer */
/** @var array $sections */
/** @var array $pages */

MappingAsset::register($this);
$current = $article['slug'];
?>

<div class="tm-help-page tm-help-article">
    <div class="tm-help-header">
        <div>
            <div class="tm-help-kicker"><?= Yii::t('ThiscoveryMappingModule.base', 'Help') ?></div>
            <h1 class="tm-help-title"><?= Html::encode($article['title']) ?></h1>
        </div>
        <div class="tm-help-header__actions">
            <?= Button::light(Yii::t('ThiscoveryMappingModule.base', 'All help'))
                ->link(Url::toHelp($contentContainer))
                ->icon('book')
                ->loader(false) ?>
            <?= Button::light(Yii::t('ThiscoveryMappingModule.base', 'Back to maps'))
                ->link(Url::toIndex($contentContainer))
                ->icon('arrow-left')
                ->loader(false) ?>
        </div>
    </div>

    <div class="tm-help-layout">
        <nav class="tm-help-nav" aria-label="<?= Html::encode(Yii::t('ThiscoveryMappingModule.base', 'Help')) ?>">
            <?php foreach ($sections as $section): ?>
                <div class="tm-help-nav__group"><?= Html::encode($section['title']) ?></div>
                <?php foreach ($section['pages'] as $slug): ?>
                    <?php $meta = $pages[$slug] ?? null; ?>
                    <?php if (!$meta) { continue; } ?>
                    <a class="tm-help-nav__link<?= $slug === $current ? ' is-active' : '' ?>"
                       href="<?= Html::encode(Url::toHelp($contentContainer, $slug)) ?>">
                        <?= Html::encode($meta['title']) ?>
                    </a>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </nav>
        <article class="tm-help-body">
            <?= $article['html'] ?>
        </article>
    </div>
</div>
