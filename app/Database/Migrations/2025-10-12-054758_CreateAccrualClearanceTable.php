<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql; // Import RawSql for the timestamp default

class CreateAccrualClearanceTable extends Migration
{
    /**
     * Creates the 'accrual_clearance' table.
     */
    public function up()
    {
        // Define Fields
        $this->forge->addField([
            'accrual_clearance_id' => [
                'type'           => 'INT',
                'constraint'     => 100,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'accrual_clearance_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
                'null'       => false,
            ],
            'accrual_clearance_track_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
                'null'       => false,
            ],
            'fk_voucher_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            
            'accrual_clearance_status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'cleared'],
                'default'    => 'pending',
                'null'       => false,
            ],
            
            'accrual_clearance_voucher_from' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'Id of voucher is being cleared',
            ],
            'accrual_clearance_vouchers' => [
                'type'       => 'JSON',
                'null'       => true,
                'comment'    => 'Ids of vouchers clearing an origin',
            ],
            'accrual_clearance_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '50,2',
                'null'       => false,
                'comment'    => 'Cleared amount so far',
            ],
            'accrual_clearance_created_date' => [
                'type'       => 'DATE',
                'null'       => true,
            ],
            'accrual_clearance_created_by' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => true,
            ],
            'accrual_clearance_last_modified_date' => [
                'type'       => 'TIMESTAMP',
                'default'    => new RawSql('CURRENT_TIMESTAMP'),
                'null'       => false,
            ],
            'accrual_clearance_last_modified_by' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => true,
            ],
            'fk_status_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'fk_approval_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
        ]);

        // Define Primary Key
        $this->forge->addPrimaryKey('accrual_clearance_id');

        // Create the table
        $this->forge->createTable('accrual_clearance');

        // Define Foreign Keys using the database connection (for performance/reliability)
        $this->db->query('ALTER TABLE accrual_clearance
            ADD CONSTRAINT fk_accrual_voucher_id
            FOREIGN KEY (fk_voucher_id) REFERENCES voucher(voucher_id)
        ');
        
        $this->db->query('ALTER TABLE accrual_clearance
            ADD CONSTRAINT fk_accrual_voucher_from
            FOREIGN KEY (accrual_clearance_voucher_from) REFERENCES voucher(voucher_id)
        ');
    }

    /**
     * Drops the 'accrual_clearance' table.
     */
    public function down()
    {
        $this->forge->dropTable('accrual_clearance', true);
    }
}