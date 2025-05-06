<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\ContraAccountModel;
use App\Enums\{AccountSystemSettingEnum, VoucherTypeEffectEnum};
class ContraAccountLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new ContraAccountModel();

        $this->table = 'contra_account';
    }

    function addContraAccount($office_bank_id){
        // Create contra accounts for the newly added bank account
        $officeLibrary = new \App\Libraries\Core\OfficeLibrary();
        $accountSystemSettingLibrary = new \App\Libraries\Core\AccountSystemSettingLibrary();

        $bank_to_bank_contra_effects = $this->read_db->table("voucher_type_effect")->get()->getResultArray();
  
        $builder = $this->write_db->table("office");
        $builder->select(array('office_name','fk_account_system_id','office_id'));
        $builder->join('office_bank','office_bank.fk_office_id=office.office_id');
        $builder->where(array('office_bank_id'=>$office_bank_id));
        $office_info_obj = $builder->get();

        $this->write_db->transStart();

        if($office_info_obj->getNumRows() > 0){
          $office_info = $office_info_obj->getRow();

          $account_system_settings = $accountSystemSettingLibrary->getAccountSystemSettings($office_info->fk_account_system_id);
          $use_accrual_based_accounting = array_key_exists(AccountSystemSettingEnum::ACCRUAL_SETTING_NAME->value,$account_system_settings);

          foreach($bank_to_bank_contra_effects as $bank_to_bank_contra_effect){
  
            if(
                $bank_to_bank_contra_effect['voucher_type_effect_code'] == 'bank_contra' ||
                $bank_to_bank_contra_effect['voucher_type_effect_code'] == 'cash_contra' ||
                $bank_to_bank_contra_effect['voucher_type_effect_code'] == 'bank_to_bank_contra' || 
                $bank_to_bank_contra_effect['voucher_type_effect_code'] == 'cash_to_cash_contra' ||
                $use_accrual_based_accounting && (
                  $bank_to_bank_contra_effect['voucher_type_effect_code'] == VoucherTypeEffectEnum::RECEIVABLES->getCode() ||
                  $bank_to_bank_contra_effect['voucher_type_effect_code'] == VoucherTypeEffectEnum::PAYABLES->getCode() ||
                  $bank_to_bank_contra_effect['voucher_type_effect_code'] == VoucherTypeEffectEnum::PREPAYMENTS->getCode()
                )
              ){  
    
                  $contra_account_name = '';
                  $contra_account_code = '';
                  $voucher_type_account_id = 0;
    
                  if($bank_to_bank_contra_effect['voucher_type_effect_code'] == 'bank_contra'){
                    $contra_account_name = $office_info->office_name." Bank to Cash";
                    $contra_account_code = "B2C"; 
                    $voucher_type_account_id = $this->read_db->table("voucher_type_account")->getWhere(
                    array('voucher_type_account_code'=>'bank'))->getRow()->voucher_type_account_id;
                  }elseif($bank_to_bank_contra_effect['voucher_type_effect_code'] == 'cash_contra'){
                    $contra_account_name = $office_info->office_name." Cash to Bank";
                    $contra_account_code = "C2B";
                    $voucher_type_account_id = $this->read_db->table("voucher_type_account")->getWhere(
                    array('voucher_type_account_code'=>'cash'))->getRow()->voucher_type_account_id;
                  }elseif($bank_to_bank_contra_effect['voucher_type_effect_code'] == 'bank_to_bank_contra'){
                    $contra_account_name = $office_info->office_name." Bank to Bank";
                    $contra_account_code = "B2B";
                    $voucher_type_account_id = $this->read_db->table("voucher_type_account")->getWhere(
                    array('voucher_type_account_code'=>'bank'))->getRow()->voucher_type_account_id;
                  }elseif($bank_to_bank_contra_effect['voucher_type_effect_code'] == 'cash_to_cash_contra'){
                    $contra_account_name = $office_info->office_name." Cash to Cash";
                    $contra_account_code = "C2C";
                    $voucher_type_account_id = $this->read_db->table("voucher_type_account")->getWhere(
                    array('voucher_type_account_code'=>'cash'))->getRow()->voucher_type_account_id;
                  }elseif($bank_to_bank_contra_effect['voucher_type_effect_code'] == 'receivables' && $use_accrual_based_accounting){
                    $contra_account_name = $office_info->office_name." Receivables";
                    $contra_account_code = "RCB";
                    $voucher_type_account_id = $this->read_db->table("voucher_type_account")->getWhere(
                    array('voucher_type_account_code'=>'accrual'))->getRow()->voucher_type_account_id;
                  }elseif($bank_to_bank_contra_effect['voucher_type_effect_code'] == 'payables' && $use_accrual_based_accounting){
                    $contra_account_name = $office_info->office_name." Payables";
                    $contra_account_code = "PYB";
                    $voucher_type_account_id = $this->read_db->table("voucher_type_account")->getWhere(
                    array('voucher_type_account_code'=>'accrual'))->getRow()->voucher_type_account_id;
                  }elseif($bank_to_bank_contra_effect['voucher_type_effect_code'] == 'prepayments' && $use_accrual_based_accounting){
                    $contra_account_name = $office_info->office_name." Prepayments";
                    $contra_account_code = "PRP";
                    $voucher_type_account_id = $this->read_db->table("voucher_type_account")->getWhere(
                    array('voucher_type_account_code'=>'accrual'))->getRow()->voucher_type_account_id;
                  }
                  
             
                 $builder = $this->read_db->table("contra_account")->where(
                    [
                      'fk_voucher_type_account_id'=>$voucher_type_account_id,
                      'fk_voucher_type_effect_id'=>$bank_to_bank_contra_effect['voucher_type_effect_id'],
                      'fk_office_bank_id'=>$office_bank_id,
                      'fk_account_system_id'=>$office_info->fk_account_system_id]
                    );
                  $contra_account_obj = $builder->get();
                  
                  if($contra_account_obj->getNumRows() == 0){
                      $contra_account_record['contra_account_track_number'] = $this->generateItemTrackNumberAndName('contra_account')['contra_account_track_number'];
                      $contra_account_record['contra_account_name'] = $contra_account_name;
                      $contra_account_record['contra_account_code'] = $contra_account_code;
                      $contra_account_record['contra_account_description'] = $contra_account_name;;
                      $contra_account_record['fk_voucher_type_account_id'] = $voucher_type_account_id;
                      $contra_account_record['fk_voucher_type_effect_id'] = $bank_to_bank_contra_effect['voucher_type_effect_id'];
                      $contra_account_record['fk_office_bank_id'] = $office_bank_id;
                      $contra_account_record['fk_account_system_id'] = $office_info->fk_account_system_id;
                      $contra_account_record['contra_account_created_by'] = $this->session->user_id;
                      $contra_account_record['contra_account_last_modified_by'] = $this->session->user_id;
      
                      $contra_account_data_to_insert = $this->mergeWithHistoryFields('contra_account',$contra_account_record,false);
                      $this->write_db->table('contra_account')->insert($contra_account_data_to_insert);
                  }
                
             }
    
          }
        }
  
        $this->write_db->transComplete();
  
        if ($this->write_db->transStatus() === FALSE)
          {
            return false;
          }else{
            return true;
          }
      }
   
}