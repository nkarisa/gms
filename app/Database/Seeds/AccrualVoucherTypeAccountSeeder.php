<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Enums\VoucherTypeAccountEnum;

class AccrualVoucherTypeAccountSeeder extends Seeder
{
    public function run()
    {
        $grantsLibrary = new \App\Libraries\System\GrantsLibrary();
        $itemTrackNumberAndName = $grantsLibrary->generateItemTrackNumberAndName('voucher_type_account');
        $data = [
            [
                'voucher_type_account_track_number' => $itemTrackNumberAndName['voucher_type_account_track_number'],
                'voucher_type_account_name' => ucfirst(VoucherTypeAccountEnum::ACCRUAL->value),
                'voucher_type_account_code' => VoucherTypeAccountEnum::ACCRUAL->value,
                'voucher_type_account_created_date' => date('Y-m-d'),
                'voucher_type_account_created_by' => 1,
                'voucher_type_account_last_modified_by' => 1,
                'voucher_type_account_last_modified_date' => date('Y-m-d h:i:s'),
                'fk_approval_id' => NULL,
                'fk_status_id' => NULL
            ]
        ];

        $this->db->table('voucher_type_account')->insertBatch($data);
    }
}
