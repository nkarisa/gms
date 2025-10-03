<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAssetDepreciationMonthCount extends Migration
{
    public function up()
    {
        $fields = [
            'asset_depreciation_month_count' => [
                'type'       => 'INT',
                'constraint' => 11,
                'after'      => 'asset_depreciation_month',
            ],
        ];

        $this->forge->addColumn('asset_depreciation', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('asset_depreciation', 'asset_depreciation_month_count');
    }
}