<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryMapping\controllers;

use humhub\modules\content\models\Content;
use humhub\modules\thiscoveryMapping\helpers\Url;
use humhub\modules\thiscoveryMapping\models\Map;
use humhub\modules\thiscoveryMapping\models\MapContribution;
use humhub\modules\thiscoveryMapping\models\MapLayer;
use humhub\modules\thiscoveryMapping\Module;
use humhub\modules\thiscoveryMapping\services\ExportService;
use humhub\modules\thiscoveryMapping\services\LayerFetchService;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

trait MapActionsTrait
{
    abstract protected function newMap(): Map;

    abstract protected function findMap($id): Map;

    protected function mapListQuery()
    {
        return Map::find()->orderBy(['title' => SORT_ASC]);
    }

    public function actionIndex()
    {
        $probe = $this->newMap();
        $query = $this->mapListQuery();
        $maps = [];
        foreach ($query->all() as $map) {
            if ($map->content->canView()) {
                $maps[] = $map;
            }
        }
        Map::attachListCounts($maps);
        if (!$probe->canCreate() && !$probe->canManage() && !$maps) {
            throw new ForbiddenHttpException();
        }

        return $this->render('@thiscovery-mapping/views/map/index', [
            'maps' => $maps,
            'canCreate' => $probe->canCreate(),
            'canConfigure' => false,
            'canViewHelp' => method_exists($this, 'canViewHelp') ? $this->canViewHelp() : false,
            'container' => method_exists($this, 'getContentContainer') ? $this->contentContainer : null,
        ]);
    }

    public function actionView($id)
    {
        $map = $this->findMap($id);
        if (!$map->content->canView()) {
            throw new ForbiddenHttpException();
        }
        return $this->render('@thiscovery-mapping/views/map/view', [
            'map' => $map,
        ]);
    }

    public function actionEdit($id = null)
    {
        $map = $id ? $this->findMap($id) : $this->newMap();
        if ($id && !$map->canManage()) {
            throw new ForbiddenHttpException();
        }
        if (!$id && !$map->canCreate()) {
            throw new ForbiddenHttpException();
        }

        $module = Module::instance();
        if ($map->isNewRecord) {
            $map->title = '';
            $map->center_lat = $module ? $module->getDefaultCenterLat() : 52.4862;
            $map->center_lng = $module ? $module->getDefaultCenterLng() : -1.8904;
            $map->zoom = $module ? $module->getDefaultZoom() : 7;
            $map->visibility_mode = Map::VISIBILITY_ALL;
            $map->allowed_types = 'Point,LineString,Polygon';
            $map->clustering = 1;
        }

        $request = Yii::$app->request;
        if ($request->isPost) {
            $map->title = trim((string)$request->post('title', $map->title));
            $map->description = (string)$request->post('description', '');
            $map->center_lat = $request->post('center_lat', $map->center_lat);
            $map->center_lng = $request->post('center_lng', $map->center_lng);
            $map->zoom = (int)$request->post('zoom', $map->zoom);
            $map->visibility_mode = (string)$request->post('visibility_mode', Map::VISIBILITY_ALL);
            $map->clustering = $request->post('clustering') ? 1 : 0;
            $map->setAllowedGeometryTypes((array)$request->post('allowed_types', []));
            $map->setCategoriesFromPost((array)$request->post('categories', []));
            $map->setQuestionsFromPost((array)$request->post('questions', []));
            $map->setSetting('basemap_style', (string)$request->post('basemap_style', ''));
            $map->setSetting('show_search', $request->post('show_search') ? 1 : 0);
            $map->setSetting('show_filters', $request->post('show_filters') ? 1 : 0);
            $map->setSetting('require_category', $request->post('require_category') ? 1 : 0);

            if ($map->isNewRecord) {
                $map->content->visibility = Content::VISIBILITY_PUBLIC;
            }

            if ($map->save()) {
                $this->saveLayers($map, (array)$request->post('layers', []));
                $this->view->saved();
                return $this->redirect(Url::toView($map));
            }
        }

        return $this->render('@thiscovery-mapping/views/map/edit', [
            'map' => $map,
            'isNew' => $map->isNewRecord,
        ]);
    }

    public function actionDelete($id)
    {
        $map = $this->findMap($id);
        if (!$map->canManage()) {
            throw new ForbiddenHttpException();
        }
        $this->forcePost();
        $map->hardDelete();
        $this->view->success(Yii::t('ThiscoveryMappingModule.base', 'Map deleted.'));
        return $this->redirect(Url::toIndex($map->isGlobal() ? null : $map->content->container));
    }

    public function actionFeatures($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $map = $this->findMap($id);
        if (!$map->content->canView()) {
            throw new ForbiddenHttpException();
        }

        $category = trim((string)Yii::$app->request->get('category', ''));
        $type = trim((string)Yii::$app->request->get('type', ''));
        $from = trim((string)Yii::$app->request->get('from', ''));
        $to = trim((string)Yii::$app->request->get('to', ''));

        $query = MapContribution::find()->andWhere(['map_id' => $map->id]);
        $userId = (int)Yii::$app->user->id;
        $isManager = $map->canManage();
        $fromOk = $from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from);
        $toOk = $to !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to);
        $ownOnly = $map->visibility_mode === Map::VISIBILITY_OWN && !$isManager;
        $moderated = $map->visibility_mode === Map::VISIBILITY_MODERATED && !$isManager;

        if ($ownOnly || $moderated || $fromOk || $toOk) {
            $query->joinWith('content');
        }

        if ($ownOnly) {
            $query->andWhere(['content.created_by' => $userId ?: 0]);
        } elseif ($moderated) {
            $query->andWhere([
                'or',
                ['status' => MapContribution::STATUS_APPROVED],
                ['content.created_by' => $userId ?: 0],
            ]);
        } else {
            $query->andWhere(['<>', 'status', MapContribution::STATUS_REJECTED]);
        }

        if ($category !== '') {
            $query->andWhere(['category_key' => $category]);
        }
        if (in_array($type, array_keys(Map::geometryTypeLabels()), true)) {
            $query->andWhere(['geometry_type' => $type]);
        }
        if ($fromOk) {
            $query->andWhere(['>=', 'content.created_at', $from . ' 00:00:00']);
        }
        if ($toOk) {
            $query->andWhere(['<=', 'content.created_at', $to . ' 23:59:59']);
        }

        $features = [];
        foreach ($query->all() as $row) {
            $features[] = $row->toPublicFeature($map, $isManager || $row->isOwner());
        }

        return ['type' => 'FeatureCollection', 'features' => $features];
    }

    public function actionSaveFeature($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $this->forcePost();
        $map = $this->findMap($id);
        $payload = Yii::$app->request->post('payload');
        if (is_string($payload)) {
            $decoded = json_decode($payload, true);
            $payload = is_array($decoded) ? $decoded : [];
        } elseif (!is_array($payload)) {
            $payload = Yii::$app->request->getBodyParams();
        }
        $featureId = (int)($payload['id'] ?? 0);

        if ($featureId) {
            $row = MapContribution::findOne(['id' => $featureId, 'map_id' => $map->id]);
            if (!$row || !$row->canEditOwn()) {
                throw new ForbiddenHttpException();
            }
        } else {
            if (!$map->canContribute()) {
                throw new ForbiddenHttpException();
            }
            $row = $map->isGlobal()
                ? new MapContribution()
                : new MapContribution($map->content->container);
            $row->map_id = $map->id;
            $row->status = $map->visibility_mode === Map::VISIBILITY_MODERATED
                ? MapContribution::STATUS_PENDING
                : MapContribution::STATUS_APPROVED;
            $row->content->visibility = Content::VISIBILITY_PUBLIC;
        }

        if (!$row->applyGeometry($payload['feature'] ?? [], $map)) {
            Yii::$app->response->statusCode = 422;
            return ['ok' => false, 'error' => $row->getFirstError('geojson')];
        }

        $category = preg_replace('/[^a-z0-9_\-]/', '', strtolower((string)($payload['category'] ?? '')));
        $validKeys = array_column($map->getCategories(), 'key');
        $row->category_key = ($category !== '' && in_array($category, $validKeys, true)) ? $category : null;
        if ($map->requireCategory() && $validKeys && $row->category_key === null) {
            Yii::$app->response->statusCode = 422;
            return ['ok' => false, 'error' => Yii::t('ThiscoveryMappingModule.base', 'Please choose a category.')];
        }
        $row->comment = mb_substr(trim((string)($payload['comment'] ?? '')), 0, 4000);
        $row->setResponses($this->sanitizeResponses($map, $payload['responses'] ?? []));

        if (!$row->save()) {
            Yii::$app->response->statusCode = 422;
            return ['ok' => false, 'error' => implode(' ', $row->getFirstErrors())];
        }

        return ['ok' => true, 'feature' => $row->toPublicFeature($map, true)];
    }

    public function actionDeleteFeature($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $this->forcePost();
        $map = $this->findMap($id);
        $featureId = (int)Yii::$app->request->post('featureId');
        if (!$featureId) {
            $body = Yii::$app->request->getBodyParams();
            $featureId = (int)($body['featureId'] ?? 0);
        }
        $row = MapContribution::findOne(['id' => $featureId, 'map_id' => $map->id]);
        if (!$row || !$row->canEditOwn()) {
            throw new ForbiddenHttpException();
        }
        $row->hardDelete();
        return ['ok' => true];
    }

    public function actionFeatureDetail($id)
    {
        $map = $this->findMap($id);
        if (!$map->content->canView()) {
            throw new ForbiddenHttpException();
        }
        $featureId = (int)Yii::$app->request->get('featureId');
        $row = MapContribution::findOne(['id' => $featureId, 'map_id' => $map->id]);
        if (!$row) {
            throw new NotFoundHttpException();
        }
        if (!$this->canSeeContribution($map, $row)) {
            throw new ForbiddenHttpException();
        }

        $html = $this->renderPartial('@thiscovery-mapping/views/map/feature-detail', [
            'map' => $map,
            'contribution' => $row,
        ]);
        Yii::$app->response->format = Response::FORMAT_JSON;
        return ['html' => $html];
    }

    public function actionModerate($id)
    {
        $map = $this->findMap($id);
        if (!$map->canManage()) {
            throw new ForbiddenHttpException();
        }
        $request = Yii::$app->request;
        if ($request->isPost) {
            $this->forcePost();
            $row = MapContribution::findOne(['id' => (int)$request->post('featureId'), 'map_id' => $map->id]);
            $status = (string)$request->post('status');
            if ($row && in_array($status, [MapContribution::STATUS_APPROVED, MapContribution::STATUS_REJECTED, MapContribution::STATUS_PENDING], true)) {
                $row->status = $status;
                $row->save(false);
            }
            if ($request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ['ok' => true];
            }
        }

        $pending = MapContribution::find()->andWhere(['map_id' => $map->id, 'status' => MapContribution::STATUS_PENDING])->all();
        return $this->render('@thiscovery-mapping/views/map/moderate', [
            'map' => $map,
            'pending' => $pending,
        ]);
    }

    public function actionExport($id)
    {
        $map = $this->findMap($id);
        if (!$map->canManage()) {
            throw new ForbiddenHttpException();
        }
        $format = (string)Yii::$app->request->get('format', 'geojson');
        $export = new ExportService();
        $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($map->title)) ?: 'map';
        if ($format === 'csv') {
            Yii::$app->response->format = Response::FORMAT_RAW;
            Yii::$app->response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
            Yii::$app->response->headers->set('Content-Disposition', 'attachment; filename="' . $slug . '.csv"');
            return $export->csv($map);
        }
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->headers->set('Content-Disposition', 'attachment; filename="' . $slug . '.geojson"');
        return $export->geoJson($map);
    }

    public function actionLayerData($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $map = $this->findMap($id);
        if (!$map->content->canView()) {
            throw new ForbiddenHttpException();
        }
        $layerId = (int)Yii::$app->request->get('layerId');
        $layer = MapLayer::findOne(['id' => $layerId, 'map_id' => $map->id, 'enabled' => 1]);
        if (!$layer) {
            throw new NotFoundHttpException();
        }
        if ($layer->type === MapLayer::TYPE_WMS) {
            return $layer->toClientConfig();
        }
        $data = (new LayerFetchService())->fetch($layer);
        if ($data === null) {
            Yii::$app->response->statusCode = 502;
            return ['ok' => false, 'error' => Yii::t('ThiscoveryMappingModule.base', 'Layer could not be loaded.')];
        }
        return $data;
    }

    private function canSeeContribution(Map $map, MapContribution $row): bool
    {
        if ($map->canManage()) {
            return true;
        }
        if ($row->isOwner()) {
            return true;
        }
        if ($map->visibility_mode === Map::VISIBILITY_OWN) {
            return false;
        }
        if ($map->visibility_mode === Map::VISIBILITY_MODERATED) {
            return $row->status === MapContribution::STATUS_APPROVED;
        }
        return $row->status !== MapContribution::STATUS_REJECTED;
    }

    private function sanitizeResponses(Map $map, $posted): array
    {
        if (!is_array($posted)) {
            return [];
        }
        $out = [];
        foreach ($map->getQuestions() as $q) {
            $raw = $posted[$q['key']] ?? '';
            $val = is_array($raw) ? implode(', ', array_map('strval', $raw)) : trim((string)$raw);
            $val = mb_substr($val, 0, 2000);
            if ($q['required'] && $val === '') {
                continue;
            }
            if (in_array($q['type'], ['dropdown', 'radio'], true) && $q['options'] && $val !== '' && !in_array($val, $q['options'], true)) {
                continue;
            }
            $out[$q['key']] = $val;
        }
        return $out;
    }

    private function saveLayers(Map $map, array $rows): void
    {
        $keep = [];
        $sort = 0;
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $name = trim((string)($row['name'] ?? ''));
            $type = (string)($row['type'] ?? '');
            if ($name === '' || !isset(MapLayer::typeLabels()[$type])) {
                continue;
            }
            $id = (int)($row['id'] ?? 0);
            $layer = $id ? MapLayer::findOne(['id' => $id, 'map_id' => $map->id]) : new MapLayer();
            if (!$layer) {
                $layer = new MapLayer();
            }
            $layer->map_id = $map->id;
            $layer->name = $name;
            $layer->type = $type;
            $layer->description = mb_substr(trim((string)($row['description'] ?? '')), 0, 500);
            $layer->enabled = !empty($row['enabled']) ? 1 : 0;
            $layer->sort_order = $sort++;
            $url = trim((string)($row['url'] ?? ''));
            if ($url !== '' && !preg_match('#^https://#i', $url)) {
                $url = '';
            }
            $popup = [];
            foreach (preg_split('/\s*,\s*/', (string)($row['popupFields'] ?? '')) ?: [] as $field) {
                if (preg_match('/^[A-Za-z0-9_\.\-]{1,64}$/', $field)) {
                    $popup[] = $field;
                }
            }
            $layer->setConfig([
                'url' => $url,
                'layers' => mb_substr(trim((string)($row['wmsLayers'] ?? '')), 0, 200),
                'popupFields' => $popup,
                'fileGuid' => preg_replace('/[^a-zA-Z0-9\-]/', '', (string)($row['fileGuid'] ?? '')),
            ]);
            if ($layer->save()) {
                $keep[] = (int)$layer->id;
            }
        }
        $query = MapLayer::find()->andWhere(['map_id' => $map->id]);
        if ($keep) {
            $query->andWhere(['not in', 'id', $keep]);
        }
        foreach ($query->all() as $orphan) {
            $orphan->delete();
        }
    }

    private function forcePost(): void
    {
        if (!Yii::$app->request->isPost) {
            throw new ForbiddenHttpException();
        }
    }
}
