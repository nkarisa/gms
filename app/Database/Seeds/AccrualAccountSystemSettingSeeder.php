<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AccrualAccountSystemSettingSeeder extends Seeder
{
    public function run()
    {
        $grantsLibrary = new \App\Libraries\System\GrantsLibrary();
        $itemTrackNumberAndName = $grantsLibrary->generateItemTrackNumberAndName('account_system_setting');

        $data = [
            [
                'account_system_setting_name' => 'use_accrual_based_accounting',
                'account_system_setting_track_number' => $itemTrackNumberAndName['account_system_setting_track_number'],
                'account_system_setting_value' => 1,
                'account_system_setting_description' => 'When set to 1 means country requires accrual based accounting',
                'account_system_setting_accounts' => '[]',
                'account_system_setting_created_by' => 1,
                'account_system_setting_created_date' => date('Y-m-d'),
                'account_system_setting_last_modified_by' => 1,
                'account_system_setting_last_modified_date' => date('Y-m-d h:i:s'),
                'fk_status_id' => NULL,
                'fk_approval_id' => NULL
            ]
        ];

        $this->db->table('account_system_setting')->insertBatch($data);
    }
}
