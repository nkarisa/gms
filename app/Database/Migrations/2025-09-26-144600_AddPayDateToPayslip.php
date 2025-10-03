<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPayDateToPayslip extends Migration
{
    /**
     * Applies the changes by adding the new column.
     */
    public function up()
    {
        $fields = [
            'payslip_pay_date' => [
                'type' => 'DATE',
                'null' => true,
                'after' => 'payslip_net_pay',
            ],
        ];

        // Add the column to the 'payslip' table
        $this->forge->addColumn('payslip', $fields);
    }

// -------------------------------------------------------------

    /**
     * Reverts the changes by removing the added column.
     */
    public function down()
    {
        // Remove the column from the 'payslip' table
        $this->forge->dropColumn('payslip', 'payslip_pay_date');
    }
}