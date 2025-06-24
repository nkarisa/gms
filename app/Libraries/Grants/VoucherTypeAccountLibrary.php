<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\VoucherTypeAccountModel;
class VoucherTypeAccountLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new VoucherTypeAccountModel();

        $this->table = 'voucher_type_account';
    }

    public function getAccrualVoucherTypeAccounts(){
        $voucherTypeAccountReadBuilder = $this->read_db->table('voucher_type_account');

        $voucherTypeAccountReadBuilder->select(['voucher_type_account_id','voucher_type_account_name','voucher_type_account_code']);
        $voucherTypeAccountReadBuilder->whereIn('voucher_type_account_code', ['accrual']);
        $voucherTypeAccountsObj = $voucherTypeAccountReadBuilder->get();

        $voucherTypeAccounts = [];

        if($voucherTypeAccountsObj->getNumRows() > 0){
            $voucherTypeAccounts = $voucherTypeAccountsObj->getResultArray(); 
        }

        return $voucherTypeAccounts;
    }
    
   
}