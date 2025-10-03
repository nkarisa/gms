<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;
class CreateAssetDepreciationTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'asset_depreciation_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'asset_depreciation_name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false,
            ],
            'asset_depreciation_track_number' => [
                'type' => 'VARCHAR',
                'constraint' => 200,
                'null' => false,
            ],
            'asset_depreciation_month' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'asset_depreciation_start_value' => [
                'type' => 'DECIMAL',
                'constraint' => '50,2',
                'null' => false,
            ],
            'asset_depreciation_expense' => [
                'type' => 'DECIMAL',
                'constraint' => '50,2',
                'null' => false,
            ],
            'asset_depreciation_accumulated' => [
                'type' => 'DECIMAL',
                'constraint' => '50,2',
                'null' => false,
            ],
            'asset_depreciation_end_value' => [
                'type' => 'DECIMAL',
                'constraint' => '50,2',
                'null' => false,
            ],
            'fk_capital_asset_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'fk_voucher_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'asset_depreciation_created_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'asset_depreciation_created_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'asset_depreciation_last_modified_date' => [
                'type'       => 'TIMESTAMP',
                'null'       => false,
                'default'    => new RawSql('CURRENT_TIMESTAMP'),
                'comment'    => 'Timestamp of the last modification.',
            ],
            'asset_depreciation_last_modified_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'fk_status_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'fk_approval_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('asset_depreciation_id');
        $this->forge->addKey('fk_capital_asset_id');
        $this->forge->addKey('fk_voucher_id');
        // $this->forge->addForeignKey('fk_capital_asset_id', 'capital_asset', 'capital_asset_id');
        // $this->forge->addForeignKey('fk_voucher_id', 'voucher', 'voucher_id');
        $this->forge->createTable('asset_depreciation');
    }

    public function down()
    {
        $this->forge->dropTable('asset_depreciation');
    }
}
