<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPayrollFkToPayslip extends Migration
{
    public function up()
    {
        $this->forge->addForeignKey('fk_payroll_id', 'payroll', 'payroll_id');
        $this->forge->process('payslip');
    }

    public function down()
    {
        $this->forge->dropForeignKey('payslip', 'fk_payroll_id');
    }
}