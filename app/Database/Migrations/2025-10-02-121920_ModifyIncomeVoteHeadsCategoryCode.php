<?php namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ModifyIncomeVoteHeadsCategoryCode extends Migration
{
    /**
     * @inheritDoc
     */
    public function up()
    {
        $this->forge->modifyColumn('income_vote_heads_category', [
            'income_vote_heads_category_code' => [
                'type'       => 'ENUM',
                'constraint' => [
                    'support',
                    'gifts',
                    'non_compassion',
                    'ongoing_intervention',
                    'individual_intervention',
                    'depreciation',
                    'payroll_liability',
                    'suspense',
                    'asset_acquisition'
                ],
                // Note: Collation is handled automatically by CI's forge,
                // but you can specify it if needed (though often it's ignored or set by DB config).
                // 'collation'  => 'latin1_swedish_ci',
                'null'       => true,
                'after'      => 'fk_funding_stream_id',
            ],
        ]);
    }

    //--------------------------------------------------------------------

    /**
     * @inheritDoc
     */
    public function down()
    {
        // Define the previous state of the column for rollback (down migration).
        // This is necessary because 'modifyColumn' changes the column permanently.

        // You must replace the 'old_value_1', 'old_value_2', etc., with the
        // actual *previous* ENUM constraints before this migration was run.
        $this->forge->modifyColumn('income_vote_heads_category', [
            'income_vote_heads_category_code' => [
                'type'       => 'ENUM',
                'constraint' => [
                    // Example: ['old_support', 'old_gifts', 'etc']
                    'old_value_1', 
                    'old_value_2',
                    // ... list all previous constraints here
                ],
                'null'       => true,
                'after'      => 'fk_funding_stream_id',
            ],
        ]);
    }
}