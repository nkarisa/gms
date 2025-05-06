<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Enums\VoucherTypeEffectEnum;

class AccrualVoucherTypeEffectSeeder extends Seeder
{
    public function run()
    {
        $grantsLibrary = new \App\Libraries\System\GrantsLibrary();
        $data = [];
        $effects = [
            VoucherTypeEffectEnum::RECEIVABLES->getCode() => VoucherTypeEffectEnum::RECEIVABLES->getName(),
            VoucherTypeEffectEnum::PAYABLES->getCode() => VoucherTypeEffectEnum::PAYABLES->getName(),
            VoucherTypeEffectEnum::PREPAYMENTS->getCode() => VoucherTypeEffectEnum::PREPAYMENTS->getName(),
            VoucherTypeEffectEnum::RECEIVABLES_PAYMENTS->getCode() => VoucherTypeEffectEnum::RECEIVABLES_PAYMENTS->getName(),
            VoucherTypeEffectEnum::PAYABLE_DISBURSEMENTS->getCode() => VoucherTypeEffectEnum::PAYABLE_DISBURSEMENTS->getName(),
            VoucherTypeEffectEnum::PREPAYMENT_SETTLEMENTS->getCode() => VoucherTypeEffectEnum::PREPAYMENT_SETTLEMENTS->getName()
        ];

        $cnt = 0;
        foreach ($effects as $effectCode => $effectName) {
            $itemTrackNumberAndName = $grantsLibrary->generateItemTrackNumberAndName('voucher_type_effect');
            $data[$cnt] = [
                'voucher_type_effect_track_number' => $itemTrackNumberAndName['voucher_type_effect_track_number'],
                'voucher_type_effect_name' => $effectName,
                'voucher_type_effect_code' => $effectCode,
                'voucher_type_effect_created_date' => date('Y-m-d'),
                'voucher_type_effect_created_by' => 1,
                'voucher_type_effect_last_modified_by' => 1,
                'voucher_type_effect_last_modified_date' => date('Y-m-d h:i:s'),
                'fk_approval_id' => NULL,
                'fk_status_id' => NULL
            ];
            $cnt++;
        }

        $this->db->table('voucher_type_effect')->insertBatch($data);
    }
}
