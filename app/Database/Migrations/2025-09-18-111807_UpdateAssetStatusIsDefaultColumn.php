<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateAssetStatusIsDefaultColumn extends Migration
{
    public function up()
    {
        $fields = [
            'asset_state_is_default' => [
                'type'       => 'ENUM',
                'constraint' => ['0', '1'],
                'default'    => '0',
            ],
        ];

        $this->forge->modifyColumn('asset_state', $fields);
    }

    public function down()
    {
        // Revert the column to its previous state
        $fields = [
            'asset_state_is_default' => [
                'type'       => 'TINYINT', // Assuming it was a TINYINT before
                'constraint' => 1,
                'default'    => '0', // Assuming a previous default
            ],
        ];

        $this->forge->modifyColumn('asset_state', $fields);
    }
}