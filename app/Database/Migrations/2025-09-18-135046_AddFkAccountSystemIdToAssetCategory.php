<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFkAccountSystemIdToAssetCategory extends Migration
{
    public function up()
    {
        $this->forge->addColumn('asset_category', [
            'fk_account_system_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'after'      => 'asset_category_depreciation_method',
                'unsigned'   => true,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('asset_category', 'fk_account_system_id');
    }
}