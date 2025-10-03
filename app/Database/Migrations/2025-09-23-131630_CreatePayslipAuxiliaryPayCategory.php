<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePayslipAuxiliaryPayCategory extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'earning_category_id' => [
                'type'           => 'INT',
                'constraint'     => 100,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'earning_category_name' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => false,
            ],
            'earning_category_track_number' => [
                'type'       => 'VARCHAR',
                'constraint' => '200',
                'null'       => false,
            ],
            'earning_category_created_date' => [
                'type'       => 'DATE',
                'null'       => false,
            ],
            'earning_category_created_by' => [
                'type'       => 'INT',
                'constraint' => 100,
                'null'       => false,
            ],
            'earning_category_last_modified_date' => [
                'type'    => 'TIMESTAMP',
                'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
                'null'    => false,
            ],
            'earning_category_last_modified_by' => [
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

        $this->forge->addPrimaryKey('earning_category_id');
        $this->forge->createTable('earning_category');
    }

    public function down()
    {
        $this->forge->dropTable('earning_category');
    }
}