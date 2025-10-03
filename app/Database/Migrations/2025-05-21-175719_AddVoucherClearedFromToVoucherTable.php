<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddVoucherClearedFromToVoucherTable extends Migration
{
    public function up()
    {
        $fields = [
            'voucher_cleared_from' => [
                'type'       => 'INT',
                'constraint' => 11, // Or whatever integer constraint you prefer
                'null'       => false,
                'default'    => 0,
                'after'      => 'voucher_reversal_from', // This places it after the specified column
            ],
            'voucher_cleared_to' => [
                'type'       => 'INT',
                'constraint' => 11, // Or whatever integer constraint you prefer
                'null'       => false,
                'default'    => 0,
                'after'      => 'voucher_cleared_from', // This places it after the specified column
            ],
        ];

        $db = \Config\Database::connect();
        
        if (!$db->fieldExists('voucher_cleared_from', 'voucher') && !$db->fieldExists('voucher_cleared_to', 'voucher')) {
            // 3. Use the Forge class to add the column
            $this->forge->addColumn('voucher', $fields);
        }
        
    }

    public function down()
    {
        // This is the reverse operation.
        // It's good practice to provide a way to revert the migration.
        $this->forge->dropColumn('voucher', 'voucher_cleared_from');
        $this->forge->dropColumn('voucher', 'voucher_cleared_to');
    }
}