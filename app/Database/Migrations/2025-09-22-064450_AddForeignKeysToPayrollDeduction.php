<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddForeignKeysToPayrollDeduction extends Migration
{
    public function up()
    {
        // Add foreign key for fk_payroll_deduction_category_id
        $this->forge->addForeignKey('fk_payroll_deduction_category_id', 'payroll_deduction_category', 'payroll_deduction_category_id');

        // Add foreign key for fk_payslip_id (Note: The SQL references payroll, but the context suggests payslip. Assuming payslip)
        $this->forge->addForeignKey('fk_payslip_id', 'payslip', 'payslip_id');

        // This applies the foreign key changes to the table
        $this->forge->process('payroll_deduction');
    }

    public function down()
    {
        // Drop foreign key for fk_payroll_deduction_category_id
        $this->forge->dropForeignKey('payroll_deduction', 'fk_payroll_deduction_category_id');

        // Drop foreign key for fk_payslip_id
        $this->forge->dropForeignKey('payroll_deduction', 'fk_payslip_id');
    }
}