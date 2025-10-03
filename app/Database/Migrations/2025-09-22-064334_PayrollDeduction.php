<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class PayrollDeduction extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'payroll_deduction_id' => [
                'type'           => 'INT',
                'constraint'     => 100,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'payroll_deduction_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ],
            'payroll_deduction_track_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
                'null'       => false,
            ],
            'fk_payroll_deduction_category_id' => [
                'type'       => 'INT',
                'constraint' => 100,
                'unsigned'   => true,
                'null'       => false,
            ],
            'fk_payslip_id' => [
                'type'       => 'INT',
                'constraint' => 100,
                'unsigned'   => true,
                'null'       => false,
            ],
            'payroll_deduction_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '50,2',
                'null'       => false,
            ],
            'payroll_deduction_created_date' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'payroll_deduction_created_by' => [
                'type'       => 'INT',
                'constraint' => 100,
                'null'       => false,
            ],
            'payroll_deduction_last_modified_date' => [
                'type'    => 'TIMESTAMP',
                'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
                'null'    => false,
            ],
            'payroll_deduction_last_modified_by' => [
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

        $this->forge->addPrimaryKey('payroll_deduction_id');
        // $this->forge->addForeignKey('fk_payroll_deduction_category_id', 'payroll_deduction_category', 'payroll_deduction_category_id');
        // $this->forge->addForeignKey('fk_payslip_id', 'payslip', 'payslip_id');
        $this->forge->createTable('payroll_deduction');
    }

    public function down()
    {
        $this->forge->dropTable('payroll_deduction');
    }
}