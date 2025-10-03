<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPayslipAuxiliaryPayCategoryIsTaxable extends Migration
{
    public function up()
    {
        $fields = [
            'earning_category_is_taxable' => [
                'type'       => 'ENUM',
                'constraint' => ['0', '1'],
                'default'    => '1',
                'null'       => false,
                'after'      => 'fk_account_system_id',
            ],
        ];

        $this->forge->addColumn('earning_category', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('earning_category', 'earning_category_is_taxable');
    }
}