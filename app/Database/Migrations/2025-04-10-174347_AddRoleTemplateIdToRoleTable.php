<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRoleTemplateIdToRoleTable extends Migration
{
    public function up()
    {
        $this->forge->addColumn('role', [
            'role_template_id' => [
                'type'       => 'INT',
                'constraint' => 100,
                'after'      => 'fk_account_system_id',
                'null'       => true,
                'default'    => null,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('role', 'role_template_id');
    }
}
