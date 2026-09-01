<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryMapping\services;

use humhub\modules\thiscoveryMapping\helpers\Url;
use humhub\modules\thiscoveryMapping\Module;
use Yii;
use yii\helpers\Markdown;

/**
 * In-product Help from docs/user markdown.
 */
class HelpService
{
    /**
     * @return array<int, array{id:string,title:string,intro:string,pages:string[]}>
     */
    public static function sections(): array
    {
        return [
            [
                'id' => 'admin',
                'title' => Yii::t('ThiscoveryMappingModule.base', 'Administration'),
                'intro' => Yii::t('ThiscoveryMappingModule.base', 'Enable the module, add a map key, and set who can create and manage maps.'),
                'pages' => ['admins'],
            ],
            [
                'id' => 'creators',
                'title' => Yii::t('ThiscoveryMappingModule.base', 'Map creators'),
                'intro' => Yii::t('ThiscoveryMappingModule.base', 'Create maps, choose what people can draw, moderate contributions, and embed a map.'),
                'pages' => [
                    'creators-getting-started',
                    'creators-settings',
                    'creators-contributing',
                    'creators-embed',
                ],
            ],
        ];
    }

    /**
     * @return array<string, array{file:string,title:string,summary:string,icon:string}>
     */
    public static function pages(): array
    {
        return [
            'admins' => [
                'file' => 'admins.md',
                'title' => Yii::t('ThiscoveryMappingModule.base', 'Thiscovery Mapping for administrators'),
                'summary' => Yii::t('ThiscoveryMappingModule.base', 'Module enablement, Stadia API key, permissions, and default map view.'),
                'icon' => 'cog',
            ],
            'creators-getting-started' => [
                'file' => 'creators-getting-started.md',
                'title' => Yii::t('ThiscoveryMappingModule.base', 'Getting started'),
                'summary' => Yii::t('ThiscoveryMappingModule.base', 'Where maps live, the map list, and how to create and open a map.'),
                'icon' => 'play-circle',
            ],
            'creators-settings' => [
                'file' => 'creators-settings.md',
                'title' => Yii::t('ThiscoveryMappingModule.base', 'Map settings'),
                'summary' => Yii::t('ThiscoveryMappingModule.base', 'Title, starting view, drawing types, visibility, categories, questions, and layers.'),
                'icon' => 'wrench',
            ],
            'creators-contributing' => [
                'file' => 'creators-contributing.md',
                'title' => Yii::t('ThiscoveryMappingModule.base', 'Contributions and moderation'),
                'summary' => Yii::t('ThiscoveryMappingModule.base', 'Drawing tools, who can see pins, review queues, and export.'),
                'icon' => 'map-marker',
            ],
            'creators-embed' => [
                'file' => 'creators-embed.md',
                'title' => Yii::t('ThiscoveryMappingModule.base', 'Pages, forms, and the top bar'),
                'summary' => Yii::t('ThiscoveryMappingModule.base', 'Embed a map on a page, use a map question on a form, and add maps to Navigation.'),
                'icon' => 'external-link',
            ],
        ];
    }

    public static function find(string $slug): ?array
    {
        $pages = self::pages();
        return $pages[$slug] ?? null;
    }

    /**
     * @return array{slug:string,title:string,html:string}|null
     */
    public static function render(string $slug, $container = null): ?array
    {
        $meta = self::find($slug);
        if (!$meta) {
            return null;
        }

        $path = self::docsPath() . DIRECTORY_SEPARATOR . $meta['file'];
        if (!is_readable($path)) {
            return null;
        }

        $markdown = (string)file_get_contents($path);
        $markdown = preg_replace('/^#\s+.*\R+/', '', $markdown, 1) ?? $markdown;
        $markdown = preg_replace_callback(
            '/\]\(([\w-]+)\.md(#[^)]+)?\)/',
            static function (array $m) use ($container): string {
                $url = Url::toHelp($container, $m[1]);
                return '](' . $url . ($m[2] ?? '') . ')';
            },
            $markdown
        ) ?? $markdown;

        return [
            'slug' => $slug,
            'title' => $meta['title'],
            'html' => Markdown::process($markdown, 'gfm'),
        ];
    }

    public static function docsPath(): string
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('thiscovery-mapping');
        return $module->getBasePath() . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'user';
    }
}
