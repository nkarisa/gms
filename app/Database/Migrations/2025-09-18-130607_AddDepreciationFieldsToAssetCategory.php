<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDepreciationFieldsToAssetCategory extends Migration
{
    public function up()
    {
        $fields = [
            'asset_category_useful_years' => [
                'type'       => 'INT',
                'constraint' => 5,
                'unsigned'   => true,
                'default'    => 0,
                'after'      => 'asset_category_description', // Adjust this to where you want the column
            ],
            'asset_category_depreciation_method' => [
                'type'       => 'ENUM',
                'constraint' => ['straight', 'declining', 'sum_of_years_digits'],
                'default'    => 'straight',
                'after'      => 'asset_category_useful_years',
            ],
        ];

        $this->forge->addColumn('asset_category', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('asset_category', ['asset_category_annual_depreciation_rate', 'asset_category_depreciation_method']);
    }
}