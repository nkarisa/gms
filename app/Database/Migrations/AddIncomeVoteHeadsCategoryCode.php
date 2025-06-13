<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIncomeVoteHeadsCategoryCode extends Migration
{
    /**
     * @var string $table The name of the table to modify.
     */
    protected $table = 'income_vote_heads_category';

    /**
     * Adds the income_vote_heads_category_code column.
     *
     * @return void
     */
    public function up(): void
    {
        // Define the field to be added to the table
        $fields = [
            'income_vote_heads_category_code' => [
                'type'       => 'ENUM',
                'constraint' => ['support', 'gifts', 'non_compassion', 'ongoing_intervention', 'individual_intervention'],
                'null'       => true, // Allows NULL values, as specified in the SQL
                'after'      => 'fk_funding_stream_id', // Specifies the column after which this new column should be added
            ],
        ];

        // Add the column to the table
        $this->forge->addColumn($this->table, $fields);

        // Output a message to the console indicating the action
        // $this->console->writeLine('Column `income_vote_heads_category_code` added to `' . $this->table . '` table.');
    }

    /**
     * Removes the income_vote_heads_category_code column.
     *
     * @return void
     */
    public function down(): void
    {
        // Drop the column from the table
        $this->forge->dropColumn($this->table, 'income_vote_heads_category_code');

        // Output a message to the console indicating the action
        // $this->console->writeLine('Column `income_vote_heads_category_code` dropped from `' . $this->table . '` table.');
    }
}

