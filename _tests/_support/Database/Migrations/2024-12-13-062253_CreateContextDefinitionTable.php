<?php
namespace Tests\Support\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateContextDefinitionTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'context_definition_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'context_definition_track_number' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'context_definition_name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'context_definition_level' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'context_definition_is_implementing' => [
                'type' => 'INT',
                'constraint' => 1,
                'null' => true,
            ],
            'context_definition_is_active' => [
                'type' => 'INT',
                'constraint' => 1,
                'default' => 1,
                'null' => false,
            ],
            'context_definition_created_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'context_definition_last_modified_date' => [
                'type' => 'TIMESTAMP',
                'null' => true,
                // 'default' => 'CURRENT_TIMESTAMP',
            ],
            'context_definition_created_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'context_definition_last_modified_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'context_definition_deleted_at' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'fk_approval_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'fk_status_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
        ]);

        $this->forge->addKey('context_definition_id', true); // Primary Key
        $this->forge->addUniqueKey('context_definition_level');

        $this->forge->createTable('context_definition');
    }

    public function down()
    {
        $this->forge->dropTable('context_definition');
    }
}
