<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryMapping\blocks;

use humhub\modules\thiscoveryMapping\models\Map;
use humhub\modules\thiscoveryMapping\widgets\MapWidget;
use humhub\modules\thiscoveryPageBuilder\blocks\BaseBlock;
use humhub\modules\thiscoveryPageBuilder\models\EngagementPage;
use Yii;

class MapEmbedBlock extends BaseBlock
{
    public const TYPE = 'map_embed';

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getLabel(): string
    {
        return Yii::t('ThiscoveryMappingModule.base', 'Map');
    }

    public function normalizeSettings(): array
    {
        return [
            'map_id' => $this->intOrNull('map_id'),
            'height' => max(280, min(720, (int)($this->settings['height'] ?? 480))),
        ];
    }

    public function getMap(): ?Map
    {
        $id = $this->intOrNull('map_id');
        if ($id === null) {
            return null;
        }
        $map = Map::findOne($id);
        if (!$map || !$map->content->canView()) {
            return null;
        }
        return $map;
    }

    public function render(EngagementPage $page): string
    {
        $map = $this->getMap();
        $settings = $this->getPersistedSettings();
        if ($map === null) {
            $html = '<section class="ep-block ep-map-embed"><p class="text-muted">'
                . Yii::t('ThiscoveryMappingModule.base', $settings['map_id'] ? 'Map unavailable' : 'No map selected')
                . '</p></section>';
            return $this->wrapAligned($html);
        }

        $html = '<section class="ep-block ep-map-embed">'
            . MapWidget::widget(['map' => $map, 'mode' => 'embed', 'height' => (int)$settings['height']])
            . '</section>';
        return $this->wrapAligned($html);
    }
}
