<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPayslipAuxiliaryPayCategoryIsRecurring extends Migration
{
    public function up()
    {
        $fields = [
            'earning_category_is_recurring' => [
                'type'       => 'ENUM',
                'constraint' => ['0', '1'],
                'null'       => false,
                'default'    => '1',
                'after'      => 'earning_category_is_taxable',
            ],
        ];

        $this->forge->addColumn('earning_category', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('earning_category', 'earning_category_is_recurring');
    }
}