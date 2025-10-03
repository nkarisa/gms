<?php namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIsBasicToAuxiliaryPayCategory extends Migration
{
    public function up()
    {
        // Define the column structure
        $fields = [
            'earning_category_is_basic' => [
                'type'       => 'ENUM',
                'constraint' => ['0', '1'],
                'default'    => '0', // It's good practice to provide a default for NOT NULL columns
                'null'       => false,
                'after'      => 'fk_account_system_id', // Specify where to add the column
            ],
        ];

        // Add the column to the table
        $this->forge->addColumn('earning_category', $fields);
    }

    //--------------------------------------------------------------------

    public function down()
    {
        // Remove the column when rolling back the migration
        $this->forge->dropColumn('earning_category', 'earning_category_is_basic');
    }
}