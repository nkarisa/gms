<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAssetCategoryDescription extends Migration
{
    public function up()
    {
        $fields = [
            'asset_category_description' => [
                'type'       => 'LONGTEXT',
                'null'       => true,
                'after'      => 'asset_category_name'
            ],
        ];

        $this->forge->addColumn('asset_category', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('asset_category', 'asset_category_description');
    }
}