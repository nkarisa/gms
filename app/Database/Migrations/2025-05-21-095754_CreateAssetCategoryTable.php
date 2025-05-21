<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAssetCategoryTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'asset_category_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'asset_category_name' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
            ],
            'asset_category_track_number' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
            ],
            'asset_category_created_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'asset_category_created_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'asset_category_last_modified_date' => [
                'type'    => 'TIMESTAMP',
                'default' => 'CURRENT_TIMESTAMP',
            ],
            'asset_category_last_modified_by' => [
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
        $this->forge->addPrimaryKey('asset_category_id');
        $this->forge->createTable('asset_category');
    }

    public function down()
    {
        $this->forge->dropTable('asset_category');
    }
}