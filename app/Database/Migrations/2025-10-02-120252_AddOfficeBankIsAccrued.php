<?php namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOfficeBankIsAccrued extends Migration
{
    /**
     * @inheritDoc
     */
    public function up()
    {
        $this->forge->addColumn('office_bank', [
            'office_bank_is_accrued' => [
                'type'       => 'ENUM',
                'constraint' => ['0', '1'],
                'null'       => true,
                'default'    => '0',
                'after'      => 'fk_bank_id',
            ],
        ]);
    }

    //--------------------------------------------------------------------

    /**
     * @inheritDoc
     */
    public function down()
    {
        // To reverse the migration, we drop the column.
        $this->forge->dropColumn('office_bank', 'office_bank_is_accrued');
    }
}