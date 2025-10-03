<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddExpenseVoteHeadsCategoryCode extends Migration
{
    public function up()
    {
        $fields = [
            'expense_vote_heads_category_code' => [
                'type' => 'ENUM',
                'constraint' => [
                    'cognitive',
                    'spiritual',
                    'social_emotional',
                    'physical',
                    'administration',
                    'gifts',
                    'non_compassion',
                    'ongoing_interventions',
                    'individual_interventions',
                    'depreciation',
                    'payroll_liability',
                    'suspense',
                    'asset_acquisition'
                ],
                'null' => true,
                'after' => 'fk_funding_stream_id',
            ],
        ];

        $db = \Config\Database::connect();

        if (!$db->fieldExists('expense_vote_heads_category_code', 'expense_vote_heads_category')) {
            // 3. If it does NOT exist, add the column
            $this->forge->addColumn('expense_vote_heads_category', $fields);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('expense_vote_heads_category', 'expense_vote_heads_category_code');
    }
}