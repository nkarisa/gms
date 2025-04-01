<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddContextDefinitionIdColumnInRoleGroup extends Migration
{
    public function up()
    {
        $fields = [
            'fk_context_definition_id' => [
                'type'       => 'INT',
                'constraint' => 100,
                'null'       => true,
                'default'    => null,
            ],
        ];

        $this->forge->addColumn('role_group', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('role_group', 'fk_context_definition_id');
    }
}
