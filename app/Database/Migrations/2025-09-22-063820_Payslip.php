<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Payslip extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'payslip_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'payslip_name' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
            ],
            'payslip_track_number' => [
                'type'       => 'VARCHAR',
                'constraint' => '200',
            ],
            'fk_user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'fk_payroll_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'payslip_basic_pay' => [
                'type'       => 'DECIMAL',
                'constraint' => '50,2',
                'default'    => '0.00',
            ],
            'fk_pay_history_id' => [
                'type'       => 'INT',
                'constraint' => 100,
                'unsigned'   => true,
                'default'    => null,
            ],
            'payslip_total_deduction' => [
                'type'       => 'DECIMAL',
                'constraint' => '50,2',
                'default'    => '0.00',
            ], 
            'payslip_net_pay' => [
                'type'       => 'DECIMAL',
                'constraint' => '50,2',
                'default'    => '0.00',
            ],
            'payslip_total_liability' => [
                'type'       => 'DECIMAL',
                'constraint' => '50,2',
                'default'    => '0.00',
            ],
            'payslip_total_earning' => [
                'type'       => 'DECIMAL',
                'constraint' => '50,2',
                'default'    => '0.00',
            ],
            'payslip_created_date' => [
                'type' => 'DATE',
            ],
            'payslip_created_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'payslip_last_modified_date' => [
                'type'    => 'TIMESTAMP',
                'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
                'null'    => false,
            ],
            'payslip_last_modified_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
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
        
        // Define primary key
        $this->forge->addKey('payslip_id', true);

        // Define foreign keys
        // $this->forge->addForeignKey('fk_user_id', 'user', 'user_id');
        // $this->forge->addForeignKey('fk_payroll_id', 'payroll', 'payroll_id');

        // Create the table
        $this->forge->createTable('payslip');
    }

    public function down()
    {
        $this->forge->dropTable('payslip');
    }
}