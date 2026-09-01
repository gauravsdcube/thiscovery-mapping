<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryMapping\helpers;

use humhub\modules\thiscoveryMapping\models\Map;
use yii\helpers\Url as BaseUrl;

class Url
{
    public static function toView(Map $map, $scheme = false): string
    {
        if ($map->isGlobal()) {
            return BaseUrl::to(['/thiscovery-mapping/global/view', 'id' => $map->id], $scheme);
        }
        return $map->content->container->createUrl('/thiscovery-mapping/map/view', ['id' => $map->id], $scheme);
    }

    public static function toEdit(Map $map): string
    {
        if ($map->isGlobal()) {
            return BaseUrl::to(['/thiscovery-mapping/global/edit', 'id' => $map->id]);
        }
        return $map->content->container->createUrl('/thiscovery-mapping/map/edit', ['id' => $map->id]);
    }

    public static function toCreate($container = null): string
    {
        if ($container === null) {
            return BaseUrl::to(['/thiscovery-mapping/global/edit']);
        }
        return $container->createUrl('/thiscovery-mapping/map/edit');
    }

    public static function toIndex($container = null): string
    {
        if ($container === null) {
            return BaseUrl::to(['/thiscovery-mapping/admin/index']);
        }
        return $container->createUrl('/thiscovery-mapping/map/index');
    }

    public static function toDelete(Map $map): string
    {
        if ($map->isGlobal()) {
            return BaseUrl::to(['/thiscovery-mapping/global/delete', 'id' => $map->id]);
        }
        return $map->content->container->createUrl('/thiscovery-mapping/map/delete', ['id' => $map->id]);
    }

    public static function toFeatures(Map $map): string
    {
        if ($map->isGlobal()) {
            return BaseUrl::to(['/thiscovery-mapping/global/features', 'id' => $map->id]);
        }
        return $map->content->container->createUrl('/thiscovery-mapping/map/features', ['id' => $map->id]);
    }

    public static function toSaveFeature(Map $map): string
    {
        if ($map->isGlobal()) {
            return BaseUrl::to(['/thiscovery-mapping/global/save-feature', 'id' => $map->id]);
        }
        return $map->content->container->createUrl('/thiscovery-mapping/map/save-feature', ['id' => $map->id]);
    }

    public static function toDeleteFeature(Map $map): string
    {
        if ($map->isGlobal()) {
            return BaseUrl::to(['/thiscovery-mapping/global/delete-feature', 'id' => $map->id]);
        }
        return $map->content->container->createUrl('/thiscovery-mapping/map/delete-feature', ['id' => $map->id]);
    }

    public static function toFeatureDetail(Map $map): string
    {
        if ($map->isGlobal()) {
            return BaseUrl::to(['/thiscovery-mapping/global/feature-detail', 'id' => $map->id]);
        }
        return $map->content->container->createUrl('/thiscovery-mapping/map/feature-detail', ['id' => $map->id]);
    }

    public static function toModerate(Map $map): string
    {
        if ($map->isGlobal()) {
            return BaseUrl::to(['/thiscovery-mapping/global/moderate', 'id' => $map->id]);
        }
        return $map->content->container->createUrl('/thiscovery-mapping/map/moderate', ['id' => $map->id]);
    }

    public static function toExport(Map $map, string $format = 'geojson'): string
    {
        $params = ['id' => $map->id, 'format' => $format];
        if ($map->isGlobal()) {
            return BaseUrl::to(array_merge(['/thiscovery-mapping/global/export'], $params));
        }
        return $map->content->container->createUrl('/thiscovery-mapping/map/export', $params);
    }

    public static function toLayerData(Map $map): string
    {
        if ($map->isGlobal()) {
            return BaseUrl::to(['/thiscovery-mapping/global/layer-data', 'id' => $map->id]);
        }
        return $map->content->container->createUrl('/thiscovery-mapping/map/layer-data', ['id' => $map->id]);
    }

    public static function toGeocode(): string
    {
        return BaseUrl::to(['/thiscovery-mapping/geocode/search']);
    }

    public static function toSettings(): string
    {
        return BaseUrl::to(['/thiscovery-mapping/admin/settings']);
    }

    public static function toHelp($container = null, ?string $page = null): string
    {
        $params = [];
        if ($page) {
            $params['page'] = $page;
        }
        if ($container === null) {
            return BaseUrl::to(array_merge(['/thiscovery-mapping/admin/help'], $params));
        }
        return $container->createUrl('/thiscovery-mapping/map/help', $params);
    }
}
