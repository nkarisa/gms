<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCapitalAssetTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'capital_asset_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
                'null'           => false,
            ],
            'capital_asset_track_number' => [
                'type'       => 'VARCHAR',
                'constraint' => '200',
                'null'       => false,
            ],
            'capital_asset_name' => [
                'type'       => 'VARCHAR',
                'constraint' => '200',
                'null'       => false,
            ],
            'capital_asset_description' => [
                'type' => 'LONGTEXT',
                'null' => false,
            ],
            'capital_asset_purchase_date' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'fk_office_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => false,
            ],
            'fk_voucher_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'capital_asset_cost' => [
                'type'       => 'DECIMAL',
                'constraint' => '50,2',
                'null'       => false,
            ],
            'capital_asset_total_depreciation' => [
                'type'       => 'DECIMAL',
                'constraint' => '50,2',
                'null'       => false,
            ],
            'capital_asset_created_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'capital_asset_created_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'capital_asset_last_modified_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
            'capital_asset_last_modified_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'fk_status_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'fk_approval_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
        ]);

        $this->forge->addPrimaryKey('capital_asset_id');
        $this->forge->createTable('capital_asset');
    }

    public function down()
    {
        $this->forge->dropTable('capital_asset');
    }
}
