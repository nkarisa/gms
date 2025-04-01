<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAccountSystemLevelToAccountSystem extends Migration
{
    public function up()
    {
        $fields = [
            'account_system_level' => [
                'type'       => 'INT',
                'constraint' => 100,
                'null'       => true,
                'default'    => null,
            ],
        ];

        $this->forge->addColumn('account_system', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('account_system', 'account_system_level');
    }
}
