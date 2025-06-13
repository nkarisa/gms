<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddVoucherTypeExpenseAccountsToVoucherType extends Migration
{
    /**
     * The `up` method is used to perform the migration.
     * This is where you define the changes to be applied to the database.
     */
    public function up()
    {
        // Define the new column to be added to the 'voucher_type' table.
        $fields = [
            'voucher_type_expense_accounts' => [
                'type' => 'json', // Specifies the column type as JSON.
                'null' => true,   // Allows the column to store NULL values.
                'after' => 'fk_account_system_id', // Positions the new column after 'fk_account_system_id'.
            ],
        ];

        // Add the defined column to the 'voucher_type' table.
        // The `forge->addColumn()` method takes the table name and an array of field definitions.
        $this->forge->addColumn('voucher_type', $fields);

        // You can optionally print a message to the console for confirmation.
        echo "Column 'voucher_type_expense_accounts' added to 'voucher_type' table.\n";
    }

    /**
     * The `down` method is used to revert the migration.
     * This is crucial for rolling back database changes if needed.
     */
    public function down()
    {
        // Drop the 'voucher_type_expense_accounts' column from the 'voucher_type' table.
        // This reverses the action performed in the `up` method.
        $this->forge->dropColumn('voucher_type', 'voucher_type_expense_accounts');

        // You can optionally print a message to the console for confirmation.
        echo "Column 'voucher_type_expense_accounts' removed from 'voucher_type' table.\n";
    }
}