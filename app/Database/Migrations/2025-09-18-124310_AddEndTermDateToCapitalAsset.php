<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEndTermDateToCapitalAsset extends Migration
{
    public function up()
    {
        $fields = [
            'capital_asset_end_term_date' => [
                'type'       => 'DATE',
                'null'       => true,
                'after'      => 'capital_asset_purchase_date',
            ],
        ];

        $this->forge->addColumn('capital_asset', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('capital_asset', 'capital_asset_end_term_date');
    }
}