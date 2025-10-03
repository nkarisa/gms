<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddItemReasonIsDefaultColumn extends Migration
{
    public function up()
    {
        $fields = [
            'item_reason_is_default' => [
                'type'       => 'INT',
                'constraint' => 5,
                'default'    => 0,
                'after'      => 'fk_approve_item_id', // Add after this column
            ],
        ];

        $db = \Config\Database::connect();
        
        if (!$db->fieldExists('item_reason_is_default', 'item_reason')) {
            // 3. Use the Forge class to add the column
            $this->forge->addColumn('item_reason', $fields);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('item_reason', 'item_reason_is_default');
    }
}
