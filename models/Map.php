<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

namespace humhub\modules\thiscoveryMapping\models;

use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\content\models\Content;
use humhub\modules\search\interfaces\Searchable;
use humhub\modules\space\models\Space;
use humhub\modules\thiscoveryMapping\helpers\Url;
use humhub\modules\thiscoveryMapping\Module;
use humhub\modules\thiscoveryMapping\permissions\ContributeGlobalMap;
use humhub\modules\thiscoveryMapping\permissions\ContributeMap;
use humhub\modules\thiscoveryMapping\permissions\CreateGlobalMap;
use humhub\modules\thiscoveryMapping\permissions\CreateMap;
use humhub\modules\thiscoveryMapping\permissions\ManageGlobalMap;
use humhub\modules\thiscoveryMapping\permissions\ManageMap;
use humhub\modules\thiscoveryMapping\widgets\WallEntry;
use humhub\modules\user\components\PermissionManager;
use Yii;
use yii\db\ActiveQuery;

/**
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property string $center_lat
 * @property string $center_lng
 * @property int $zoom
 * @property string $visibility_mode
 * @property string $allowed_types
 * @property int $clustering
 * @property string|null $categories_json
 * @property string|null $questions_json
 * @property string|null $settings_json
 *
 * @property-read MapContribution[] $contributions
 * @property-read MapLayer[] $layers
 */
class Map extends ContentActiveRecord implements Searchable
{
    public const VISIBILITY_ALL = 'all';
    public const VISIBILITY_OWN = 'own';
    public const VISIBILITY_MODERATED = 'moderated';

    public const TYPE_POINT = 'Point';
    public const TYPE_LINE = 'LineString';
    public const TYPE_POLYGON = 'Polygon';

    public $moduleId = 'thiscovery-mapping';
    public $wallEntryClass = WallEntry::class;
    protected $createPermission = CreateMap::class;
    protected $managePermission = ManageMap::class;
    protected $canMove = true;

    public static function tableName()
    {
        return 'thiscovery_map';
    }

    public function rules()
    {
        return [
            [['title'], 'required'],
            [['title'], 'string', 'max' => 255],
            [['description'], 'string'],
            [['center_lat'], 'number', 'min' => -90, 'max' => 90],
            [['center_lng'], 'number', 'min' => -180, 'max' => 180],
            [['zoom'], 'integer', 'min' => 1, 'max' => 20],
            [['visibility_mode'], 'in', 'range' => array_keys(self::visibilityLabels())],
            [['allowed_types'], 'string', 'max' => 64],
            [['clustering'], 'boolean'],
            [['categories_json', 'questions_json', 'settings_json'], 'string'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'title' => Yii::t('ThiscoveryMappingModule.base', 'Title'),
            'description' => Yii::t('ThiscoveryMappingModule.base', 'Description'),
            'center_lat' => Yii::t('ThiscoveryMappingModule.base', 'Centre latitude'),
            'center_lng' => Yii::t('ThiscoveryMappingModule.base', 'Centre longitude'),
            'zoom' => Yii::t('ThiscoveryMappingModule.base', 'Zoom'),
            'visibility_mode' => Yii::t('ThiscoveryMappingModule.base', 'Contribution visibility'),
            'allowed_types' => Yii::t('ThiscoveryMappingModule.base', 'Drawing types'),
            'clustering' => Yii::t('ThiscoveryMappingModule.base', 'Cluster point markers'),
        ];
    }

    public static function visibilityLabels(): array
    {
        return [
            self::VISIBILITY_ALL => Yii::t('ThiscoveryMappingModule.base', 'Everyone can see all contributions'),
            self::VISIBILITY_OWN => Yii::t('ThiscoveryMappingModule.base', 'People only see their own contributions'),
            self::VISIBILITY_MODERATED => Yii::t('ThiscoveryMappingModule.base', 'Contributions appear after moderation'),
        ];
    }

    public static function geometryTypeLabels(): array
    {
        return [
            self::TYPE_POINT => Yii::t('ThiscoveryMappingModule.base', 'Point / pin'),
            self::TYPE_LINE => Yii::t('ThiscoveryMappingModule.base', 'Line / route'),
            self::TYPE_POLYGON => Yii::t('ThiscoveryMappingModule.base', 'Area / polygon'),
        ];
    }

    public function getContentName()
    {
        return Yii::t('ThiscoveryMappingModule.base', 'Map');
    }

    public function getContentDescription()
    {
        return $this->title;
    }

    public function getIcon()
    {
        return 'fa-map-marker';
    }

    public function getSearchAttributes()
    {
        return [
            'title' => $this->title,
            'description' => (string)$this->description,
        ];
    }

    public function getUrl($scheme = false): string
    {
        return Url::toView($this, $scheme);
    }

    public function getContributions(): ActiveQuery
    {
        return $this->hasMany(MapContribution::class, ['map_id' => 'id']);
    }

    public function getLayers(): ActiveQuery
    {
        return $this->hasMany(MapLayer::class, ['map_id' => 'id'])->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC]);
    }

    public function isGlobal(): bool
    {
        try {
            if ($this->content && !$this->content->isNewRecord) {
                return empty($this->content->contentcontainer_id);
            }
            return $this->content->getContainer() === null;
        } catch (\Throwable $e) {
            return empty($this->content->contentcontainer_id ?? null);
        }
    }

    public function getAllowedGeometryTypes(): array
    {
        $raw = array_filter(array_map('trim', explode(',', (string)$this->allowed_types)));
        $allowed = array_values(array_intersect($raw, array_keys(self::geometryTypeLabels())));
        return $allowed ?: [self::TYPE_POINT];
    }

    public function setAllowedGeometryTypes(array $types): void
    {
        $types = array_values(array_intersect($types, array_keys(self::geometryTypeLabels())));
        $this->allowed_types = $types ? implode(',', $types) : self::TYPE_POINT;
    }

    /**
     * @return array<int, array{key:string,name:string,color:string}>
     */
    public function getCategories(): array
    {
        $decoded = json_decode((string)$this->categories_json, true);
        if (!is_array($decoded)) {
            return [];
        }
        $out = [];
        foreach ($decoded as $row) {
            if (!is_array($row)) {
                continue;
            }
            $key = preg_replace('/[^a-z0-9_\-]/', '', strtolower((string)($row['key'] ?? '')));
            $name = trim((string)($row['name'] ?? ''));
            if ($key === '' || $name === '') {
                continue;
            }
            $color = (string)($row['color'] ?? '#1d70b8');
            if (!preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $color)) {
                $color = '#1d70b8';
            }
            $out[] = ['key' => $key, 'name' => $name, 'color' => $color];
        }
        return $out;
    }

    public function setCategoriesFromPost(array $rows): void
    {
        $clean = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $name = trim((string)($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $key = preg_replace('/[^a-z0-9_\-]/', '', strtolower((string)($row['key'] ?? '')));
            if ($key === '') {
                $key = substr(preg_replace('/[^a-z0-9]+/', '-', strtolower($name)), 0, 40);
                $key = trim($key, '-');
            }
            if ($key === '') {
                continue;
            }
            $color = (string)($row['color'] ?? '#1d70b8');
            if (!preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $color)) {
                $color = '#1d70b8';
            }
            $clean[] = ['key' => $key, 'name' => $name, 'color' => $color];
        }
        $this->categories_json = $clean ? json_encode($clean, JSON_UNESCAPED_UNICODE) : null;
    }

    /**
     * @return array<int, array{key:string,type:string,label:string,required:bool,options:string[]}>
     */
    public function getQuestions(): array
    {
        $decoded = json_decode((string)$this->questions_json, true);
        if (!is_array($decoded)) {
            return [];
        }
        $allowed = ['text', 'textarea', 'dropdown', 'radio'];
        $out = [];
        foreach ($decoded as $row) {
            if (!is_array($row)) {
                continue;
            }
            $key = preg_replace('/[^a-z0-9_\-]/', '', strtolower((string)($row['key'] ?? '')));
            $label = trim((string)($row['label'] ?? ''));
            $type = (string)($row['type'] ?? 'text');
            if ($key === '' || $label === '' || !in_array($type, $allowed, true)) {
                continue;
            }
            $options = [];
            foreach ((array)($row['options'] ?? []) as $opt) {
                $opt = trim((string)$opt);
                if ($opt !== '') {
                    $options[] = $opt;
                }
            }
            $out[] = [
                'key' => $key,
                'type' => $type,
                'label' => $label,
                'required' => !empty($row['required']),
                'options' => $options,
            ];
        }
        return $out;
    }

    public function setQuestionsFromPost(array $rows): void
    {
        $clean = [];
        $allowed = ['text', 'textarea', 'dropdown', 'radio'];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $label = trim((string)($row['label'] ?? ''));
            if ($label === '') {
                continue;
            }
            $type = (string)($row['type'] ?? 'text');
            if (!in_array($type, $allowed, true)) {
                $type = 'text';
            }
            $key = preg_replace('/[^a-z0-9_\-]/', '', strtolower((string)($row['key'] ?? '')));
            if ($key === '') {
                $key = substr(preg_replace('/[^a-z0-9]+/', '-', strtolower($label)), 0, 40);
                $key = trim($key, '-');
            }
            if ($key === '') {
                continue;
            }
            $options = [];
            $rawOpts = $row['options'] ?? '';
            if (is_string($rawOpts)) {
                foreach (preg_split('/\r\n|\r|\n/', $rawOpts) ?: [] as $line) {
                    $line = trim($line);
                    if ($line !== '') {
                        $options[] = $line;
                    }
                }
            } elseif (is_array($rawOpts)) {
                foreach ($rawOpts as $opt) {
                    $opt = trim((string)$opt);
                    if ($opt !== '') {
                        $options[] = $opt;
                    }
                }
            }
            $clean[] = [
                'key' => $key,
                'type' => $type,
                'label' => $label,
                'required' => !empty($row['required']),
                'options' => $options,
            ];
        }
        $this->questions_json = $clean ? json_encode($clean, JSON_UNESCAPED_UNICODE) : null;
    }

    public function getSetting(string $key, $default = null)
    {
        $decoded = json_decode((string)$this->settings_json, true);
        if (!is_array($decoded) || !array_key_exists($key, $decoded)) {
            return $default;
        }
        return $decoded[$key];
    }

    public function setSetting(string $key, $value): void
    {
        $decoded = json_decode((string)$this->settings_json, true);
        if (!is_array($decoded)) {
            $decoded = [];
        }
        $decoded[$key] = $value;
        $this->settings_json = json_encode($decoded, JSON_UNESCAPED_UNICODE);
    }

    public function getBasemapStyle(): string
    {
        $style = trim((string)$this->getSetting('basemap_style', ''));
        $module = Module::instance();
        return $style !== '' ? $style : ($module ? $module->getBasemapStyle() : Module::STADIA_DEFAULT_STYLE);
    }

    public function beforeSave($insert)
    {
        if ($this->visibility_mode === '') {
            $this->visibility_mode = self::VISIBILITY_ALL;
        }
        if ($this->allowed_types === '' || $this->allowed_types === null) {
            $this->allowed_types = 'Point,LineString,Polygon';
        }
        if ($this->zoom === null || $this->zoom === '') {
            $this->zoom = 7;
        }
        return parent::beforeSave($insert);
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        try {
            if ($this->content && !$this->content->isNewRecord && (int)$this->content->visibility !== Content::VISIBILITY_PUBLIC) {
                $this->content->visibility = Content::VISIBILITY_PUBLIC;
                $this->content->save(false);
            }
        } catch (\Throwable $e) {
            Yii::warning('Map visibility sync failed: ' . $e->getMessage(), 'thiscovery-mapping');
        }
    }

    public function beforeDelete()
    {
        if (!parent::beforeDelete()) {
            return false;
        }
        foreach ($this->contributions as $row) {
            $row->hardDelete();
        }
        MapLayer::deleteAll(['map_id' => $this->id]);
        return true;
    }

    public function canCreate($user = null): bool
    {
        $user = $user ?: Yii::$app->user->getIdentity();
        if (!$user) {
            return false;
        }
        $container = null;
        try {
            $container = $this->content->getContainer();
        } catch (\Throwable $e) {
            $container = null;
        }
        if ($container instanceof Space) {
            return $container->getPermissionManager($user)->can(CreateMap::class);
        }
        return (new PermissionManager(['subject' => $user]))->can(CreateGlobalMap::class);
    }

    public function canManage($user = null): bool
    {
        $user = $user ?: Yii::$app->user->getIdentity();
        if (!$user) {
            return false;
        }
        $container = null;
        try {
            $container = $this->content->getContainer();
        } catch (\Throwable $e) {
            $container = null;
        }
        if ($container instanceof Space) {
            return $container->getPermissionManager($user)->can(ManageMap::class);
        }
        return Yii::$app->user->isAdmin()
            || (new PermissionManager(['subject' => $user]))->can(ManageGlobalMap::class);
    }

    public function canContribute($user = null): bool
    {
        $user = $user ?: Yii::$app->user->getIdentity();
        if (!$user) {
            return false;
        }
        $container = null;
        try {
            $container = $this->content->getContainer();
        } catch (\Throwable $e) {
            $container = null;
        }
        if ($container instanceof Space) {
            return $container->getPermissionManager($user)->can(ContributeMap::class);
        }
        return (new PermissionManager(['subject' => $user]))->can(ContributeGlobalMap::class);
    }

    public static function pickerOptions($container = null): array
    {
        $out = [];
        $add = static function ($query, string $prefix = '') use (&$out) {
            foreach ($query->orderBy(['title' => SORT_ASC])->all() as $map) {
                if ($map->content->canView()) {
                    $out[$map->id] = $prefix !== '' ? ($prefix . $map->title) : $map->title;
                }
            }
        };
        $add(
            self::find()->joinWith('content')->andWhere(['content.contentcontainer_id' => null]),
            $container ? (Yii::t('ThiscoveryMappingModule.base', 'Global') . ': ') : ''
        );
        if ($container) {
            $add(self::find()->contentContainer($container));
        }
        return $out;
    }
}
