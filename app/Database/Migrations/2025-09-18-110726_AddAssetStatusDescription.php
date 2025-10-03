<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAssetStatusDescription extends Migration
{
    public function up()
    {
        $fields = [
            'asset_state_description' => [
                'type'       => 'LONGTEXT',
                'null'       => true,
                'after'      => 'asset_state_name'
            ],
        ];

        $this->forge->addColumn('asset_state', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('asset_state', 'asset_state_description');
    }
}