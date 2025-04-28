<?php

namespace App\Controllers\Web\Grants;

use App\Controllers\Web\WebController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class VoucherType extends WebController
{

    function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

    }

    function getVoucherTypeEffects($voucher_type_account_id){

        $voucherTypeAccountReadBuilder = $this->read_db->table('voucher_type_account');
        $voucherTypeEffectReadBuilder = $this->read_db->table('voucher_type_effect');

        $voucherTypeAccountReadBuilder->where(array('voucher_type_account_id'=>$voucher_type_account_id));
        $voucher_type_account_code = $voucherTypeAccountReadBuilder->get()->getRow()->voucher_type_account_code;
   
        $voucher_type_effect_codes = [];
   
        if($voucher_type_account_code == 'bank'){
           $voucher_type_effect_codes = ['income','expense','bank_contra','bank_to_bank_contra','bank_refund'];
        }elseif($voucher_type_account_code == 'cash'){
           $voucher_type_effect_codes = ['income','expense','cash_contra','cash_to_cash_contra'];
        }
   
        if(!empty($voucher_type_effect_codes)){
           $voucherTypeEffectReadBuilder->whereIn('voucher_type_effect_code',$voucher_type_effect_codes);
        }
   
        $voucherTypeEffectReadBuilder->select(array('voucher_type_effect_id','voucher_type_effect_name','voucher_type_effect_code'));
        $voucher_type_effect = $voucherTypeEffectReadBuilder->get()->getResultArray();
        
        return $this->response->setJSON($voucher_type_effect);
     }

    function checkSelectVoucherTypeEffect($voucher_type_effect_id){
        $voucherTypeEffectReadBuilder = $this->read_db->table('voucher_type_effect');

        $voucher_type_effect_code = $voucherTypeEffectReadBuilder->where(array('voucher_type_effect_id'=>$voucher_type_effect_id))
        ->get()->getRow()->voucher_type_effect_code;
   
        return $this->response->setJSON(compact('voucher_type_effect_code'));
     }
}
