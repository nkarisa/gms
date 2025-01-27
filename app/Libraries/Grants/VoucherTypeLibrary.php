<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\VoucherTypeModel;
class VoucherTypeLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new VoucherTypeModel();

        $this->table = 'grants';
    }

    function officeHiddenBankVoucherTypes($office_id){

        $builder = $this->read_db->table('office');
        $account_system_id = $builder->getWhere(array('office_id' => $office_id))->getRow()->fk_account_system_id;
        
        $voucher_type_ids = [];
    
        $builder2 = $this->read_db->table('voucher_type');
        $builder2->select(array('voucher_type_id','voucher_type_effect_code'));
        $builder2->join('voucher_type_account','voucher_type_account.voucher_type_account_id=voucher_type.fk_voucher_type_account_id');
        $builder2->join('voucher_type_effect','voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
        $builder2->where(array('voucher_type_is_active' => 1, 'voucher_type_is_hidden' => 1));
        $builder2->where(array('voucher_type_account_code' => 'bank'));
        $builder2->where(array('fk_account_system_id' => $account_system_id));
        $voucher_types = $builder2->get('voucher_type');
    
    
        
    
        if($voucher_types->getNumRows() > 0){
          $voucher_type_ids = array_column($voucher_types->getResultArray(),'voucher_type_id');
        }
    
        return $voucher_type_ids;
      }

   
}