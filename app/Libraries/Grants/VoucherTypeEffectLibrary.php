<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\VoucherTypeEffectModel;
class VoucherTypeEffectLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new VoucherTypeEffectModel();

        $this->table = 'grants';
    }

    public function getAccrualVoucherTypeEffects(){
        $voucherTypeEffectReadBuilder = $this->read_db->table('voucher_type_effect');

        $voucherTypeEffectReadBuilder->select(['voucher_type_effect_id','voucher_type_effect_name','voucher_type_effect_code']);
        $voucherTypeEffectReadBuilder->whereIn('voucher_type_effect_code', [
            'receivables',
            'payments',
            'payables',
            'disbursements',
            'prepayments',
            'settlements',
            'depreciation',
            'payroll_liability'
        ]);
        $voucherTypeEffectObj = $voucherTypeEffectReadBuilder->get();

        $voucherTypeEffects = [];

        if($voucherTypeEffectObj->getNumRows() > 0){
            $voucherTypeEffects = $voucherTypeEffectObj->getResultArray();
        }

        return $voucherTypeEffects;
    }
   
}