<?php

/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @license AGPL-3.0-or-later
 */

use humhub\components\Migration;

class m260828_090000_thiscovery_mapping_initial extends Migration
{
    public function safeUp()
    {
        $this->safeCreateTable('thiscovery_map', [
            'id' => $this->primaryKey(),
            'title' => $this->string(255)->notNull(),
            'description' => $this->text()->null(),
            'center_lat' => $this->decimal(10, 7)->notNull()->defaultValue(52.4862000),
            'center_lng' => $this->decimal(11, 7)->notNull()->defaultValue(-1.8904000),
            'zoom' => $this->tinyInteger()->notNull()->defaultValue(7),
            'visibility_mode' => $this->string(16)->notNull()->defaultValue('all'),
            'allowed_types' => $this->string(64)->notNull()->defaultValue('Point,LineString,Polygon'),
            'clustering' => $this->boolean()->notNull()->defaultValue(1),
            'categories_json' => $this->text()->null(),
            'questions_json' => $this->text()->null(),
            'settings_json' => $this->text()->null(),
        ]);

        $this->safeCreateTable('thiscovery_map_contribution', [
            'id' => $this->primaryKey(),
            'map_id' => $this->integer()->notNull(),
            'category_key' => $this->string(64)->null(),
            'geometry_type' => $this->string(16)->notNull(),
            'geojson' => $this->text()->notNull(),
            'bbox_minx' => $this->decimal(11, 7)->null(),
            'bbox_miny' => $this->decimal(10, 7)->null(),
            'bbox_maxx' => $this->decimal(11, 7)->null(),
            'bbox_maxy' => $this->decimal(10, 7)->null(),
            'comment' => $this->text()->null(),
            'responses_json' => $this->text()->null(),
            'status' => $this->string(16)->notNull()->defaultValue('approved'),
        ]);
        $this->safeAddForeignKey('fk_tmc_map', 'thiscovery_map_contribution', 'map_id', 'thiscovery_map', 'id', 'CASCADE');
        $this->safeCreateIndex('idx_tmc_map_status', 'thiscovery_map_contribution', ['map_id', 'status']);
        $this->safeCreateIndex('idx_tmc_map_type', 'thiscovery_map_contribution', ['map_id', 'geometry_type']);

        $this->safeCreateTable('thiscovery_map_layer', [
            'id' => $this->primaryKey(),
            'map_id' => $this->integer()->notNull(),
            'type' => $this->string(32)->notNull(),
            'name' => $this->string(255)->notNull(),
            'description' => $this->string(500)->null(),
            'enabled' => $this->boolean()->notNull()->defaultValue(1),
            'sort_order' => $this->integer()->notNull()->defaultValue(0),
            'config_json' => $this->text()->null(),
        ]);
        $this->safeAddForeignKey('fk_tml_map', 'thiscovery_map_layer', 'map_id', 'thiscovery_map', 'id', 'CASCADE');
        $this->safeCreateIndex('idx_tml_map_sort', 'thiscovery_map_layer', ['map_id', 'sort_order']);
    }

    public function safeDown()
    {
        $this->safeDropTable('thiscovery_map_layer');
        $this->safeDropTable('thiscovery_map_contribution');
        $this->safeDropTable('thiscovery_map');
    }
}
