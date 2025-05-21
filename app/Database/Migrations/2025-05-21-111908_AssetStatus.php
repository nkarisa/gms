<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AssetStatus extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'asset_status_id' => [
                'type'           => 'INT',
                'constraint'     => 11, // Assuming INT is 11 digits by default for AUTO_INCREMENT
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'asset_status_track_number' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
            ],
            'asset_status_name' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
            ],
            'asset_status_is_default' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
            ],
            'asset_status_created_date' => [
                'type' => 'DATE',
            ],
            'asset_status_created_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'asset_status_last_modified_date' => [
                'type'    => 'TIMESTAMP',
                'default' => 'CURRENT_TIMESTAMP',
            ],
            'asset_status_last_modified_by' => [
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

        $this->forge->addPrimaryKey('asset_status_id');
        $this->forge->createTable('asset_status');
    }

    public function down()
    {
        $this->forge->dropTable('asset_status');
    }
}