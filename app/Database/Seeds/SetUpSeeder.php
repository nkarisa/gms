<?php



namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SetUpSeeder extends Seeder
{
    /**
     * Number of rows to insert in each batch. Adjust as needed.
     */
    private const BATCH_SIZE = 100;

    public function run()
    {
        helper('seeder');
        helper('inflector');
        
        $schemaTables = [
            'approve_item',
            'setting',
            'settings',
            'language',
            'item_reason',
            'account_system',
            'account_system_setting',
            'account_system_language',
            'context_definition',
            'attachment_type',
            'approval_flow',
            'status',
            'permission_label',
            'menu',
            'permission',
            'role',
            'role_permission',
            'role_group',
            'permission_template',
            'role_group_association',
            'status_role',
            'bank',
            'country_currency',
            'office',
            'context_global',
            'context_region',
            'context_country',
            'context_cohort',
            'context_cluster',
            'context_center',
            'office_bank',
            'funding_stream',
            'income_vote_heads_category',
            'income_account',
            'expense_vote_heads_category',
            'expense_account',
            'funding_status',
            'funder',
            'project',
            'project_income_account',
            'project_allocation',
            'office_bank_project_allocation',
            'budget_review_count',
            'month',
            'budget_tag',
            'voucher_type_effect',
            'voucher_type_account',
            'voucher_type',
            'contra_account',
            'designation',
            'department',
            'unique_identifier',
            // 'user'
        ];

        $databaseLibrary = new \App\Libraries\System\DatabaseLibrary();
        $databaseLibrary->truncateTables($schemaTables);

        foreach ($schemaTables as $table) {
            if (!file_exists(APPPATH . DS . 'Database' . DS . 'Seeds' . DS . 'data_csv' . DS . $table . '.csv')) {
                continue;
            }

            $builder = $this->db->table($table);
            $rowsGenerator = csvRowsGenerator($table); // Get the generator

            if ($rowsGenerator === null) {
                // Handle error if the generator couldn't be created (e.g., file not found)
                continue;
            }

            $batch = [];
            $count = 0;
            foreach ($rowsGenerator as $row) {
                $batch[] = $row;
                $count++;

                if ($count === self::BATCH_SIZE) {
                    if (!empty($batch)) {
                        $builder->insertBatch($batch);
                    }
                    $batch = []; // Reset batch
                    $count = 0;  // Reset count
                }
            }

            // Insert any remaining rows in the last batch
            if (!empty($batch)) {
                $builder->insertBatch($batch);
            }
        }
    }
}