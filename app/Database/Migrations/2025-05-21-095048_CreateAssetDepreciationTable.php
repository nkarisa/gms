<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAssetDepreciationTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'asset_depreciation_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'asset_depreciation_name' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
            ],
            'asset_depreciation_track_number' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
            ],
            'asset_depreciation_month' => [
                'type' => 'DATE',
            ],
            'asset_depreciation_cost' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
            ],
            'fk_capital_asset_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
            ],
            'asset_depreciation_created_date' => [
                'type'    => 'DATE',
                'null'    => true,
            ],
            'asset_depreciation_created_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'asset_depreciation_last_modified_date' => [
                'type'    => 'TIMESTAMP',
                'null'    => false,
                'default' => 'CURRENT_TIMESTAMP',
            ],
            'asset_depreciation_last_modified_by' => [
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

        $this->forge->addPrimaryKey('asset_depreciation_id');
        $this->forge->addForeignKey('fk_capital_asset_id', 'capital_asset', 'capital_asset_id', 'CASCADE', 'RESTRICT'); // Assuming CASCADE on delete and RESTRICT on update, adjust as needed.
        $this->forge->createTable('asset_depreciation');
    }

    public function down()
    {
        $this->forge->dropTable('asset_depreciation');
    }
}
