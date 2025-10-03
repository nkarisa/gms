<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class PayHistory extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'pay_history_id' => [
                'type'           => 'INT',
                'constraint'     => 100,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'pay_history_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ],
            'pay_history_track_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
                'null'       => false,
            ],
            'fk_office_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            'fk_user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            'pay_history_start_date' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'pay_history_end_date' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'pay_history_created_date' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'pay_history_created_by' => [
                'type'       => 'INT',
                'constraint' => 100,
                'null'       => false,
            ],
            'pay_history_last_modified_date' => [
                'type'    => 'TIMESTAMP',
                'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
                'null'    => false,
            ],
            'pay_history_last_modified_by' => [
                'type'       => 'INT',
                'constraint' => 100,
                'null'       => false,
            ],
            'fk_status_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'fk_approval_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
        ]);

        $this->forge->addPrimaryKey('pay_history_id');
        $this->forge->addForeignKey('fk_office_id', 'office', 'office_id');
        $this->forge->addForeignKey('fk_user_id', 'user', 'user_id');
        $this->forge->createTable('pay_history');
    }

    public function down()
    {
        $this->forge->dropTable('pay_history');
    }
}