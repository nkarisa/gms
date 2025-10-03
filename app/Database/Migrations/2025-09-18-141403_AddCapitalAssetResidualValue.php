<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCapitalAssetResidualValue extends Migration
{
    public function up()
    {
        $this->forge->addColumn('capital_asset', [
            'capital_asset_residual_value' => [
                'type'       => 'INT',
                'constraint' => 11,
                'after'      => 'capital_asset_cost',
                'null'       => true,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('capital_asset', 'capital_asset_residual_value');
    }
}