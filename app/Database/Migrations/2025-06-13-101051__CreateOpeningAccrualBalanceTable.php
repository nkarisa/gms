<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOpeningAccrualBalanceTable extends Migration
{
    /**
     * @inheritDoc
     */
    public function up()
    {
        // Define the fields for the 'opening_accrual_balance' table.
        $this->forge->addField([
            'opening_accrual_balance_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
                'comment'        => 'Primary key for the opening accrual balance record.',
            ],
            'opening_accrual_balance_name' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => false,
                'comment'    => 'Name or description of the accrual balance.',
            ],
            'opening_accrual_balance_track_number' => [
                'type'       => 'LONGTEXT',
                'null'       => false,
                'comment'    => 'Tracking number associated with the accrual balance.',
            ],
            'fk_system_opening_balance_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true, // Assuming system_opening_balance_id is unsigned
                'null'       => false,
                'comment'    => 'Foreign key to the system_opening_balance table.',
            ],
            'opening_accrual_balance_account' => [
                'type'       => 'ENUM',
                'constraint' => ['receivables', 'payables', 'prepayments', 'depreciation', 'payroll_liability'],
                'null'       => false,
                'comment'    => 'Type of account for the accrual balance.',
            ],
            'opening_accrual_balance_amount' => [
                'type'        => 'DECIMAL',
                'constraint'  => '50,2',
                'null'        => false,
                'comment'     => 'The actual amount of the opening accrual.',
            ],
            'opening_accrual_balance_effect' => [
                'type'       => 'ENUM',
                'constraint' => ['debit', 'credit'],
                'null'       => false,
                'comment'    => 'Type of the accrual effect.',
            ],
            'opening_accrual_balance_created_date' => [
                'type'    => 'DATE',
                'null'    => true,
                'default' => null,
                'comment' => 'Date when the record was created.',
            ],
            'opening_accrual_balance_created_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
                'default'    => null,
                'comment'    => 'ID of the user who created the record.',
            ],
            'opening_accrual_balance_last_modified_date' => [
                'type'       => 'TIMESTAMP',
                'null'       => false,
                'default'    => 'CURRENT_TIMESTAMP',
                'comment'    => 'Timestamp of the last modification.',
            ],
            'opening_accrual_balance_last_modified_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
                'default'    => null,
                'comment'    => 'ID of the user who last modified the record.',
            ],
            'fk_status_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
                'default'    => null,
                'comment'    => 'Foreign key to a status table (optional).',
            ],
            'fk_approval_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
                'default'    => null,
                'comment'    => 'Foreign key to an approval table (optional).',
            ],
        ]);

        // Add the primary key.
        $this->forge->addPrimaryKey('opening_accrual_balance_id');

        // Add the foreign key constraint.
        // The second parameter ('system_opening_balance') is the table name,
        // and the third parameter ('system_opening_balance_id') is the column in that table.
        $this->forge->addForeignKey('fk_system_opening_balanace_id', 'system_opening_balance', 'system_opening_balance_id');

        // Create the table.
        $this->forge->createTable('opening_accrual_balance', true);
    }

    /**
     * @inheritDoc
     */
    public function down()
    {
        // Drop the 'opening_accrual_balance' table if it exists during rollback.
        $this->forge->dropTable('opening_accrual_balance', true);
    }
}