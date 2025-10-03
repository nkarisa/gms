<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Payroll extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'payroll_id' => [
                'type'           => 'INT',
                'constraint'     => 100,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'payroll_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ],
            'payroll_track_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
                'null'       => false,
            ],
            'fk_office_id' => [
                'type'       => 'INT',
                'constraint' => 11, // Standard integer constraint
                'unsigned'   => true, // Assumes a positive office_id
                'null'       => false,
            ],
            'payroll_period' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'payroll_created_date' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'payroll_created_by' => [
                'type'       => 'INT',
                'constraint' => 100,
                'null'       => false,
            ],
            'payroll_last_modified_date' => [
                'type'    => 'TIMESTAMP',
                'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
                'null'    => false,
            ],
            'payroll_last_modified_by' => [
                'type'       => 'INT',
                'constraint' => 100,
                'null'       => false,
            ],
            'fk_status_id' => [
                'type'       => 'INT',
                'constraint' => 11, // Standard integer constraint
                'unsigned'   => true, // Assumes a positive status_id
                'null'       => true,
            ],
            'fk_approval_id' => [
                'type'       => 'INT',
                'constraint' => 11, // Standard integer constraint
                'unsigned'   => true, // Assumes a positive approval_id
                'null'       => true,
            ],
        ]);

        $this->forge->addPrimaryKey('payroll_id');
        // $this->forge->addForeignKey('fk_office_id', 'office', 'office_id');
        $this->forge->createTable('payroll');
    }

    public function down()
    {
        $this->forge->dropTable('payroll');
    }
}