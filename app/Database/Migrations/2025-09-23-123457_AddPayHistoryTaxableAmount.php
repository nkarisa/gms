<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPayHistoryTaxableAmount extends Migration
{
    public function up()
    {
        $fields = [
            'pay_history_total_earning_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,0',
                'null'       => true,
            ],
        ];

        $this->forge->addColumn('pay_history', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('pay_history', 'pay_history_total_earning_amount');
    }
}