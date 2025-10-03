<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIsOperationalToAssetStatus extends Migration
{
    public function up()
    {
        $fields = [
            'asset_state_operation' => [
                'type'       => 'ENUM',
                'constraint' => ['pending','operational','obselete'],
                'default'    => 'operational',
                'after'      => 'asset_state_description',
            ],
        ];

        $this->forge->addColumn('asset_state', $fields);
    }

    public function down()
    {
        $db = \Config\Database::connect();
        if($db->fieldExists('asset_state_is_operational', 'asset_state')){
            $this->forge->dropColumn('asset_state', 'asset_state_is_operational');
        }
    }
}