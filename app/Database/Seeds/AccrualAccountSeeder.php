<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AccrualAccountSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'accrual_account_id'           => 1,
                'accrual_account_track_number' => 'ACNT-4555654',
                'accrual_account_name'         => 'Accounts Receivables',
                'accrual_account_code'         => 'receivables',
                'accrual_account_effect'       => 'debit',
                'accrual_account_debit_effect' => 'receivables',
                'accrual_account_credit_effect'=> 'payments',
                'accrual_account_created_date' => '2025-06-12',
                'accrual_account_created_by'   => 1,
                'accrual_account_last_modified_date' => '2025-06-12 00:11:20',
                'accrual_account_last_modified_by'   => 1,
                'fk_status_id'                 => null,
                'fk_approval_id'               => null,
            ],
            [
                'accrual_account_id'           => 2,
                'accrual_account_track_number' => 'ACNT-667253',
                'accrual_account_name'         => 'Accounts Payables',
                'accrual_account_code'         => 'payables',
                'accrual_account_effect'       => 'credit',
                'accrual_account_debit_effect' => 'disbursements',
                'accrual_account_credit_effect'=> 'payables',
                'accrual_account_created_date' => '2025-06-12',
                'accrual_account_created_by'   => 1,
                'accrual_account_last_modified_date' => '2025-06-12 00:11:20',
                'accrual_account_last_modified_by'   => 1,
                'fk_status_id'                 => null,
                'fk_approval_id'               => null,
            ],
            [
                'accrual_account_id'           => 3,
                'accrual_account_track_number' => 'ACNT-6366475',
                'accrual_account_name'         => 'Prepayments',
                'accrual_account_code'         => 'prepayments',
                'accrual_account_effect'       => 'debit',
                'accrual_account_debit_effect' => 'prepayments',
                'accrual_account_credit_effect'=> 'settlements',
                'accrual_account_created_date' => '2025-06-12',
                'accrual_account_created_by'   => 1,
                'accrual_account_last_modified_date' => '2025-06-12 00:11:20',
                'accrual_account_last_modified_by'   => 1,
                'fk_status_id'                 => null,
                'fk_approval_id'               => null,
            ],
            [
                'accrual_account_id'           => 4,
                'accrual_account_track_number' => 'ACNT-4426637',
                'accrual_account_name'         => 'Depreciation',
                'accrual_account_code'         => 'depreciation',
                'accrual_account_effect'       => 'credit',
                'accrual_account_debit_effect' => null, // This was NULL in your SQL
                'accrual_account_credit_effect'=> 'depreciation',
                'accrual_account_created_date' => '2025-06-12',
                'accrual_account_created_by'   => 1,
                'accrual_account_last_modified_date' => '2025-06-12 00:11:20',
                'accrual_account_last_modified_by'   => 1,
                'fk_status_id'                 => null,
                'fk_approval_id'               => null,
            ],
            [
                'accrual_account_id'           => 5,
                'accrual_account_track_number' => 'ACNT-8868765',
                'accrual_account_name'         => 'Payroll Liability',
                'accrual_account_code'         => 'payroll_liability',
                'accrual_account_effect'       => 'credit',
                'accrual_account_debit_effect' => null, // This was NULL in your SQL
                'accrual_account_credit_effect'=> 'payroll_liability',
                'accrual_account_created_date' => '2025-06-12',
                'accrual_account_created_by'   => 1,
                'accrual_account_last_modified_date' => '2025-06-12 00:11:20',
                'accrual_account_last_modified_by'   => 1,
                'fk_status_id'                 => null,
                'fk_approval_id'               => null,
            ],
        ];

        // Using insertBatch to insert multiple rows
        $this->db->table('accrual_account')->insertBatch($data);
    }
}
