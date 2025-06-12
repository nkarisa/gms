<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAccrualAccountTable extends Migration
{
    public function up()
    {
        // Define the fields for the 'accrual_account' table
        $this->forge->addField([
            'accrual_account_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
                'null'           => false,
            ],
            'accrual_account_track_number' => [
                'type'           => 'TEXT', // LONGTEXT maps to TEXT in CI4
                'null'           => false,
            ],
            'accrual_account_name' => [
                'type'           => 'VARCHAR',
                'constraint'     => '200',
                'null'           => false,
            ],
            'accrual_account_code' => [
                'type'           => 'VARCHAR', // ENUM is handled as VARCHAR with constraint
                'constraint'     => ['receivables', 'payables', 'prepayments', 'depreciation', 'payroll_liability'],
                'null'           => false,
            ],
            'accrual_account_effect' => [
                'type'           => 'VARCHAR', // ENUM is handled as VARCHAR with constraint
                'constraint'     => ['debit', 'credit'],
                'null'           => false,
            ],
            'accrual_account_debit_effect' => [
                'type'           => 'VARCHAR', // ENUM is handled as VARCHAR with constraint
                'constraint'     => ['receivables', 'payments', 'payables', 'disbursements', 'prepayments', 'settlements', 'depreciation', 'payroll_liability'],
                'null'           => true, // DEFAULT NULL in SQL
            ],
            'accrual_account_credit_effect' => [
                'type'           => 'VARCHAR', // ENUM is handled as VARCHAR with constraint
                'constraint'     => ['receivables', 'payments', 'payables', 'disbursements', 'prepayments', 'settlements', 'depreciation', 'payroll_liability'],
                'null'           => true, // DEFAULT NULL in SQL
            ],
            'accrual_account_created_date' => [
                'type'           => 'DATE',
                'null'           => true, // DEFAULT NULL in SQL
            ],
            'accrual_account_created_by' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'null'           => true, // DEFAULT NULL in SQL
            ],
            'accrual_account_last_modified_date' => [
                'type'           => 'TIMESTAMP',
                'null'           => false,
                'default'        => 'CURRENT_TIMESTAMP',
            ],
            'accrual_account_last_modified_by' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'null'           => true, // DEFAULT NULL in SQL
            ],
            'fk_status_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'null'           => true, // DEFAULT NULL in SQL
            ],
            'fk_approval_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'null'           => true, // DEFAULT NULL in SQL
            ],
        ]);

        // Add the primary key
        $this->forge->addPrimaryKey('accrual_account_id');

        // Create the table
        $this->forge->createTable('accrual_account');
    }

    public function down()
    {
        // Drop the 'accrual_account' table if the migration is rolled back
        $this->forge->dropTable('accrual_account');
    }
}
