<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql; // Import RawSql for CURRENT_TIMESTAMP default

class CreateAccrualLedgerTable extends Migration
{
    /**
     * Creates the accrual_ledger table.
     */
    public function up(): void
    {
        $this->forge->addField([
            'accrual_ledger_id' => [
                'type'           => 'INT',
                'constraint'     => 5,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'accrual_ledger_track_number' => [
                'type'       => 'TEXT',
                'null'       => false,
            ],
            'accrual_ledger_name' => [
                'type'       => 'VARCHAR',
                'constraint' => '200',
                'null'       => false,
            ],
            'accrual_ledger_is_active' => [
                'type'       => 'INT',
                'null'       => false,
                'default'    => 1,
            ],
            'accrual_ledger_code' => [
                'type'       => 'ENUM',
                'constraint' => ['receivables', 'payables', 'prepayments', 'depreciation', 'payroll_liability'],
                'null'       => false,
            ],
            'accrual_ledger_effect' => [
                'type'       => 'ENUM',
                'constraint' => ['debit', 'credit'],
                'null'       => true, // The original SQL had a default of NULL, so setting 'null' to true
                'default'    => null,
            ],
            'accrual_ledger_debit_effect' => [
                'type'       => 'ENUM',
                'constraint' => ['receivables', 'payments', 'payables', 'disbursements', 'prepayments', 'settlements', 'depreciation', 'payroll_liability'],
                'null'       => true,
                'default'    => null,
            ],
            'accrual_ledger_credit_effect' => [
                'type'       => 'ENUM',
                'constraint' => ['receivables', 'payments', 'payables', 'disbursements', 'prepayments', 'settlements', 'depreciation', 'payroll_liability'],
                'null'       => true,
                'default'    => null,
            ],
            'accrual_ledger_created_date' => [
                'type'       => 'DATE',
                'null'       => true,
                'default'    => null,
            ],
            'accrual_ledger_created_by' => [
                'type'       => 'INT',
                'constraint' => 5,
                'unsigned'   => true,
                'null'       => true,
                'default'    => null,
            ],
            // Use RawSql for the exact TIMESTAMP definition
            'accrual_ledger_last_modified_date' => [
                'type'       => 'TIMESTAMP',
                'null'       => false,
                'default'    => new RawSql('CURRENT_TIMESTAMP'), 
            ],
            'accrual_ledger_last_modified_by' => [
                'type'       => 'INT',
                'constraint' => 5,
                'unsigned'   => true,
                'null'       => true,
                'default'    => null,
            ],
            'fk_status_id' => [
                'type'       => 'INT',
                'constraint' => 5,
                'unsigned'   => true,
                'null'       => true,
                'default'    => null,
            ],
            'fk_approval_id' => [
                'type'       => 'INT',
                'constraint' => 5,
                'unsigned'   => true,
                'null'       => true,
                'default'    => null,
            ],
        ]);

        $this->forge->addPrimaryKey('accrual_ledger_id');
        $this->forge->createTable('accrual_ledger', true);
    }

    /**
     * Drops the accrual_ledger table.
     */
    public function down(): void
    {
        $this->forge->dropTable('accrual_ledger', true);
    }
}