<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePayslipAuxiliaryPay extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'earning_id' => [
                'type'           => 'INT',
                'constraint'     => 100,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'earning_name' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => false,
            ],
            'earning_track_number' => [
                'type'       => 'VARCHAR',
                'constraint' => '200',
                'null'       => false,
            ],
            'fk_pay_history_id' => [
                'type'       => 'INT',
                'null'       => false,
            ],
            'fk_earning_category_id' => [
                'type'       => 'INT',
                'null'       => false,
            ],
            'earning_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '50,2',
                'null'       => false,
            ],
            'earning_created_date' => [
                'type'       => 'DATE',
                'null'       => false,
            ],
            'earning_created_by' => [
                'type'       => 'INT',
                'constraint' => 100,
                'null'       => false,
            ],
            'earning_last_modified_date' => [
                'type'    => 'TIMESTAMP',
                'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
                'null'    => false,
            ],
            'earning_last_modified_by' => [
                'type'       => 'INT',
                'constraint' => 100,
                'null'       => false,
            ],
            'fk_status_id' => [
                'type'       => 'INT',
                'null'       => true,
            ],
            'fk_approval_id' => [
                'type'       => 'INT',
                'null'       => true,
            ],
        ]);

        $this->forge->addPrimaryKey('earning_id');
        // $this->forge->addForeignKey('fk_pay_history_id', 'pay_history', 'pay_history_id');
        // $this->forge->addForeignKey('fk_earning_category_id', 'earning_category', 'earning_category_id');
        $this->forge->createTable('earning');
    }

    public function down()
    {
        $this->forge->dropTable('earning');
    }
}