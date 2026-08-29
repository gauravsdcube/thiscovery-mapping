<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryMapping\models;

use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\content\models\Content;
use humhub\modules\thiscoveryMapping\permissions\ContributeMap;
use humhub\modules\thiscoveryMapping\services\GeoJsonValidator;
use Yii;
use yii\db\ActiveQuery;

/**
 * @property int $id
 * @property int $map_id
 * @property string|null $category_key
 * @property string $geometry_type
 * @property string $geojson
 * @property string|null $bbox_minx
 * @property string|null $bbox_miny
 * @property string|null $bbox_maxx
 * @property string|null $bbox_maxy
 * @property string|null $comment
 * @property string|null $responses_json
 * @property string $status
 *
 * @property-read Map $map
 */
class MapContribution extends ContentActiveRecord
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public $moduleId = 'thiscovery-mapping';
    public $wallEntryClass = 'humhub\modules\thiscoveryMapping\widgets\ContributionWallEntry';
    protected $createPermission = ContributeMap::class;
    public $silentContentCreation = true;
    protected $streamChannel = null;

    public static function tableName()
    {
        return 'thiscovery_map_contribution';
    }

    public function rules()
    {
        return [
            [['map_id', 'geometry_type', 'geojson'], 'required'],
            [['map_id'], 'integer'],
            [['geometry_type'], 'in', 'range' => array_keys(Map::geometryTypeLabels())],
            [['category_key'], 'string', 'max' => 64],
            [['comment'], 'string', 'max' => 4000],
            [['geojson', 'responses_json'], 'string', 'max' => 65535],
            [['status'], 'in', 'range' => [self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_REJECTED]],
            [['bbox_minx', 'bbox_miny', 'bbox_maxx', 'bbox_maxy'], 'number'],
        ];
    }

    public function getContentName()
    {
        return Yii::t('ThiscoveryMappingModule.base', 'Map contribution');
    }

    public function getContentDescription()
    {
        $comment = trim((string)$this->comment);
        if ($comment !== '') {
            return mb_strimwidth($comment, 0, 120, '…');
        }
        return Map::geometryTypeLabels()[$this->geometry_type] ?? $this->geometry_type;
    }

    public function getIcon()
    {
        return 'fa-map-pin';
    }

    public function getMap(): ActiveQuery
    {
        return $this->hasOne(Map::class, ['id' => 'map_id']);
    }

    public function getResponses(): array
    {
        $decoded = json_decode((string)$this->responses_json, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function setResponses(array $responses): void
    {
        $this->responses_json = $responses ? json_encode($responses, JSON_UNESCAPED_UNICODE) : null;
    }

    public function getFeature(): ?array
    {
        $decoded = json_decode((string)$this->geojson, true);
        return is_array($decoded) ? $decoded : null;
    }

    public function applyGeometry(array $feature, Map $map): bool
    {
        $validator = new GeoJsonValidator();
        $clean = $validator->sanitizeFeature($feature, $map->getAllowedGeometryTypes());
        if ($clean === null) {
            $this->addError('geojson', Yii::t('ThiscoveryMappingModule.base', 'That drawing is not valid.'));
            return false;
        }
        $this->geometry_type = $clean['geometry']['type'];
        $this->geojson = json_encode($clean, JSON_UNESCAPED_UNICODE);
        $bbox = $validator->bbox($clean['geometry']);
        if ($bbox) {
            $this->bbox_minx = $bbox[0];
            $this->bbox_miny = $bbox[1];
            $this->bbox_maxx = $bbox[2];
            $this->bbox_maxy = $bbox[3];
        }
        return true;
    }

    public function canEditOwn($user = null): bool
    {
        $user = $user ?: Yii::$app->user->getIdentity();
        if (!$user) {
            return false;
        }
        $map = $this->map;
        if ($map && $map->canManage($user)) {
            return true;
        }
        return $this->isOwner($user);
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        try {
            if ($this->content && !$this->content->isNewRecord) {
                $this->content->visibility = Content::VISIBILITY_PUBLIC;
                $this->content->save(false);
            }
        } catch (\Throwable $e) {
            Yii::warning('Contribution visibility sync failed: ' . $e->getMessage(), 'thiscovery-mapping');
        }
    }

    public function toPublicFeature(Map $map, bool $includePrivate = false): array
    {
        $feature = $this->getFeature() ?: [
            'type' => 'Feature',
            'geometry' => null,
            'properties' => [],
        ];
        $category = null;
        foreach ($map->getCategories() as $cat) {
            if ($cat['key'] === (string)$this->category_key) {
                $category = $cat;
                break;
            }
        }
        $feature['id'] = $this->id;
        $feature['properties'] = [
            'id' => $this->id,
            'mapId' => (int)$this->map_id,
            'type' => $this->geometry_type,
            'category' => $this->category_key,
            'categoryName' => $category['name'] ?? '',
            'color' => $category['color'] ?? '#1d70b8',
            'comment' => (string)$this->comment,
            'responses' => $includePrivate || $map->visibility_mode !== Map::VISIBILITY_OWN
                ? $this->getResponses()
                : [],
            'status' => $this->status,
            'createdAt' => $this->content->created_at ?? null,
            'createdBy' => (int)($this->content->created_by ?? 0),
            'author' => $this->content->createdBy?->displayName ?? '',
            'canEdit' => $this->canEditOwn(),
        ];
        return $feature;
    }
}
