<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFkAssetStatusIdToCapitalAsset extends Migration
{
    public function up()
    {
        $this->forge->addColumn('capital_asset', [
            'fk_asset_state_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
        ]);

        $this->forge->addForeignKey('fk_asset_state_id', 'asset_state', 'asset_state_id', 'CASCADE', 'RESTRICT');
    }

    public function down()
    {
        $this->forge->dropForeignKey('capital_asset', 'capital_asset_fk_asset_state_id_foreign');
        $this->forge->dropColumn('capital_asset', 'fk_asset_state_id');
    }
}