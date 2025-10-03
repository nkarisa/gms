<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddVoucherIdToPayroll extends Migration
{
    public function up()
    {
        // Add the new column to the 'payroll' table
        $this->forge->addColumn('payroll', [
            'fk_voucher_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
                'after'      => 'payroll_period',
            ],
        ]);

        // Add the foreign key constraint
        // $this->forge->addForeignKey('fk_voucher_id', 'voucher', 'voucher_id');
        // $this->forge->processIndexes('payroll');
    }

    //--------------------------------------------------------------------

    public function down()
    {
        // Drop the foreign key constraint
        // $this->forge->dropForeignKey('payroll', 'fk_voucher_id');

        // Drop the column
        $this->forge->dropColumn('payroll', 'fk_voucher_id');
    }
}