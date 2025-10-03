<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class AssetStatus extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'asset_state_id' => [
                'type'           => 'INT',
                'constraint'     => 11, // Assuming INT is 11 digits by default for AUTO_INCREMENT
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'asset_state_track_number' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
            ],
            'asset_state_name' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
            ],
            'asset_state_is_default' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
            ],
            'asset_state_created_date' => [
                'type' => 'DATE',
            ],
            'asset_state_created_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'asset_state_last_modified_date' => [
                'type'       => 'TIMESTAMP',
                'null'       => false,
                'default'    => new RawSql('CURRENT_TIMESTAMP'),
                'comment'    => 'Timestamp of the last modification.',
            ],
            'asset_state_last_modified_by' => [
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

        $this->forge->addPrimaryKey('asset_state_id');
        $this->forge->createTable('asset_state');
    }

    public function down()
    {
        $this->forge->dropTable('asset_state');
    }
}