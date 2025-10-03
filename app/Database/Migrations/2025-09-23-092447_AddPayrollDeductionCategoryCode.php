<?php namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPayrollDeductionCategoryCode extends Migration
{
    public function up()
    {
        $this->forge->addColumn('payroll_deduction_category', [
            'payroll_deduction_category_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'collation'  => 'utf8mb4_0900_ai_ci',
                'after'      => 'payroll_deduction_category_track_number',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('payroll_deduction_category', 'payroll_deduction_category_code');
    }
}