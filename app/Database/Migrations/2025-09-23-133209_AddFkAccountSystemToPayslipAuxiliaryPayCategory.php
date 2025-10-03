<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFkAccountSystemToPayslipAuxiliaryPayCategory extends Migration
{
    public function up()
    {
        $fields = [
            'fk_account_system_id' => [
                'type'       => 'INT',
                'null'       => false,
                'after'      => 'earning_category_track_number',
            ],
        ];

        $this->forge->addColumn('earning_category', $fields);

        // $this->forge->addForeignKey('fk_account_system_id', 'account_system', 'account_system_id');
        // $this->forge->processIndexes('earning_category');
    }

    public function down()
    {
        // $this->forge->dropForeignKey('earning_category', 'earning_category_fk_account_system_id_foreign');
        $this->forge->dropColumn('earning_category', 'fk_account_system_id');
    }
}