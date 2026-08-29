<?php

use yii\db\Migration;

class uninstall extends Migration
{
    public function up()
    {
        $this->dropTable('thiscovery_map_layer');
        $this->dropTable('thiscovery_map_contribution');
        $this->dropTable('thiscovery_map');
    }

    public function down()
    {
        echo "uninstall cannot be reverted.\n";
        return false;
    }
}
