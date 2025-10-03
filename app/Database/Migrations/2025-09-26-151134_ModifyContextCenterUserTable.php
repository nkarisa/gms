<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ModifyContextCenterUserTable extends Migration
{
    /**
     * Applies the changes.
     */
    public function up()
    {
        // 1. Add new columns
        $add_fields = [
            'context_center_user_employment_number' => [
                'type' => 'INT',
                'null' => true,
                'after' => 'fk_designation_id',
            ],
            'context_center_user_primary' => [
                'type' => 'INT',
                'constraint' => 11, // Assuming a standard integer size
                'null' => false,
                'default' => 1,
                'comment' => 'This is the FCP the user is primarily resident in',
                'after' => 'context_center_user_employment_number',
            ],
        ];

        $this->forge->addColumn('context_center_user', $add_fields);

        // 2. Modify existing columns (CHANGE clauses)
        $modify_fields = [
            'context_center_user_created_date' => [
                'type' => 'DATE',
                'null' => true,
                'after' => 'context_center_user_created_by',
            ],
            'context_center_user_last_modified_date' => [
                'type' => 'TIMESTAMP',
                'null' => true,
                'after' => 'context_center_user_created_date',
            ],
        ];

        $this->forge->modifyColumn('context_center_user', $modify_fields);
    }

// -------------------------------------------------------------

    /**
     * Reverts the changes.
     * NOTE: Reverting column changes requires knowing the *exact* original definition.
     */
    public function down()
    {
        // 1. Remove the added columns
        $this->forge->dropColumn('context_center_user', 'context_center_user_employment_number');
        $this->forge->dropColumn('context_center_user', 'context_center_user_primary');

        // 2. Revert the modification of existing columns
        // NOTE: The values below are *assumptions* of the original state.
        $revert_fields = [
            'context_center_user_created_date' => [
                // Assuming it was a TIMESTAMP or DATETIME before
                'type' => 'DATETIME',
                'null' => false, // Assuming it was NOT NULL before
                'after' => 'context_center_user_created_by', // May need adjusting
            ],
            'context_center_user_last_modified_date' => [
                // Assuming it was a DATETIME or had different constraints
                'type' => 'DATETIME',
                'null' => false, // Assuming it was NOT NULL before
                // Position will revert if 'after' is omitted or set to its original value
            ],
        ];
        
        $this->forge->modifyColumn('context_center_user', $revert_fields);
    }
}