<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class PayrollDeductionCategory extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'payroll_deduction_category_id' => [
                'type'           => 'INT',
                'constraint'     => 100,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'payroll_deduction_category_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ],
            'payroll_deduction_category_track_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ],
            'payroll_deduction_category_liability' => [
                'type'       => 'ENUM',
                'constraint' => ['long_term', 'short_term'],
                'null'       => false,
            ],
            'fk_account_system_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            'payroll_deduction_category_created_date' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'payroll_deduction_category_created_by' => [
                'type'       => 'INT',
                'constraint' => 100,
                'null'       => false,
            ],
            'payroll_deduction_category_last_modified_date' => [
                'type'    => 'TIMESTAMP',
                'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
                'null'    => false,
            ],
            'payroll_deduction_category_last_modified_by' => [
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

        $this->forge->addPrimaryKey('payroll_deduction_category_id');
        // $this->forge->addForeignKey('fk_account_system_id', 'account_system', 'account_system_id');
        $this->forge->createTable('payroll_deduction_category');
    }

    public function down()
    {
        $this->forge->dropTable('payroll_deduction_category');
    }
}