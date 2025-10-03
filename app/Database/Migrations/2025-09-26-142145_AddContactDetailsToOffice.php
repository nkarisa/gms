<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddContactDetailsToOffice extends Migration
{
    /**
     * Executes the migration.
     *
     * Adds 'office_email', 'office_phone', 'office_postal_address' columns
     * and changes the definition of 'office_end_date'.
     */
    public function up()
    {
        // 1. Add new columns
        $fields = [
            'office_email' => [
                'type'       => 'VARCHAR',
                'constraint' => '200',
                'after'      => 'office_name',
                'null'       => true,
            ],
            'office_phone' => [
                'type'       => 'VARCHAR',
                'constraint' => '200',
                'after'      => 'office_email',
                'null'       => true,
            ],
            'office_postal_address' => [
                'type'       => 'VARCHAR',
                'constraint' => '200',
                'after'      => 'office_phone',
                'null'       => true,
            ],
        ];

        $this->forge->addColumn('office', $fields);

        // 2. Modify 'office_end_date' column
        // This is primarily to ensure the column type and nullability,
        // matching the intent of the original SQL 'CHANGE' clause.
        $this->forge->modifyColumn('office', [
            'office_end_date' => [
                'type'       => 'DATE',
                'after'      => 'office_start_date',
                'null'       => true,
            ],
        ]);
    }

// -------------------------------------------------------------

    /**
     * Reverts the changes made by the migration.
     *
     * Removes the added columns and reverts the change to 'office_end_date'.
     * NOTE: Reverting the 'office_end_date' change requires knowing its *original* definition.
     * Assuming it was *not* NULLable initially for the sake of the 'down' method.
     */
    public function down()
    {
        // 1. Remove the added columns
        $this->forge->dropColumn('office', 'office_email');
        $this->forge->dropColumn('office', 'office_phone');
        $this->forge->dropColumn('office', 'office_postal_address');

        // 2. Revert the modification of 'office_end_date'
        // This *must* revert the column to its state *before* the 'up' method ran.
        // Assuming its previous state was 'DATE' NOT NULL and position was different or irrelevant.
        // For a full revert, you might need to know the *exact* original definition (e.g., position, NOT NULL constraint).
        $this->forge->modifyColumn('office', [
            'office_end_date' => [
                'type' => 'DATE',
                // Removed 'after' to revert position (or set to known original)
                'null' => false, // Assuming it was NOT NULL before
            ],
        ]);
        
        // NOTE: The 'CHANGE' part of your original SQL was essentially just ensuring it's a DATE type and NULLable, 
        // and setting its position. The 'down' method here attempts to revert it, but full positional revert 
        // via `modifyColumn` can be tricky without knowing the absolute original position.
    }
}