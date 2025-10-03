<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPayslipTaxablePay extends Migration
{
    public function up()
    {
        $fields = [
            'payslip_taxable_pay' => [
                'type'       => 'DECIMAL',
                'constraint' => '50,2',
                'null'       => true,
                'after'      => 'payslip_basic_pay',
            ],
        ];

        $this->forge->addColumn('payslip', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('payslip', 'payslip_taxable_pay');
    }
}