<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddExpenseVoteHeadsCategoryCode extends Migration
{
    public function up()
    {
        $this->forge->addColumn('expense_vote_heads_category', [
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
                ],
                'null' => true,
                'after' => 'fk_funding_stream_id',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('expense_vote_heads_category', 'expense_vote_heads_category_code');
    }
}