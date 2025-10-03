<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRoleTemplateIdToRoleTable extends Migration
{
    public function up()
    {
        $fields = [
            'role_template_id' => [
                'type'       => 'INT',
                'constraint' => 100,
                'after'      => 'fk_account_system_id',
                'null'       => true,
                'default'    => null,
            ],
        ];
        

        $db = \Config\Database::connect();
        
        if (!$db->fieldExists('role_template_id', 'role')) {
            // 3. Use the Forge class to add the column
            $this->forge->addColumn('role', $fields);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('role', 'role_template_id');
    }
}
