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
        $voucher_types = $builder2->get();
    
    
        
    
        if($voucher_types->getNumRows() > 0){
          $voucher_type_ids = array_column($voucher_types->getResultArray(),'voucher_type_id');
        }
    
        return $voucher_type_ids;
      }

      function checkIfHiddenBankIncomeExpenseVoucherTypePresent(){

        $accountSystemId = $this->session->user_account_system_id;

        $hidden_bank_income_expense_voucher_type_present = false;
        $voucherTypeReadBuilder = $this->read_db->table('voucher_type');
        $voucherTypeReadBuilder->join('voucher_type_account','voucher_type_account.voucher_type_account_id=voucher_type.fk_voucher_type_account_id');
        $voucherTypeReadBuilder->join('voucher_type_effect','voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
        $voucherTypeReadBuilder->join('account_system','account_system.account_system_id=voucher_type.fk_account_system_id');
        $voucherTypeReadBuilder->where(array('voucher_type_is_active' => 1, 'voucher_type_is_hidden' => 1));
        $voucherTypeReadBuilder->where(array('voucher_type_account_code' => 'bank'));
        $voucherTypeReadBuilder->whereIn('voucher_type_effect_code', ['income','expense']);
        $voucherTypeReadBuilder->where(array('fk_account_system_id' => $accountSystemId ));
        $voucher_type = $voucherTypeReadBuilder->get();
    
        if($voucher_type->getNumRows() < 3){
          // Create the missing one
          $accountSystemCode = $voucher_type->getRowArray()['account_system_code'];
          $hidden_bank_income_expense_voucher_type_present = $this->createHiddenVoucherTypes($accountSystemId, $accountSystemCode);
        }else{
          $hidden_bank_income_expense_voucher_type_present = true;
        }
    
        return $hidden_bank_income_expense_voucher_type_present;
      }


      function createHiddenVoucherTypes(int $accountSystemId, string $accountSystemCode){

        //return true;
        $voucherTypeEffectReadBuilder = $this->read_db->table('voucher_type_effect');
        $voucherTypeEffectReadBuilder->select(array('voucher_type_effect_id','voucher_type_effect_code'));
        $voucherTypeEffectReadBuilder->whereIn('voucher_type_effect_code', ['income','expense']);
        $voucher_type_effects = $voucherTypeEffectReadBuilder->get()->getResultArray();
    
        $voucher_type_effect_ids = array_column($voucher_type_effects,'voucher_type_effect_id');
        $voucher_type_effect_codes = array_column($voucher_type_effects,'voucher_type_effect_code');
    
        $ordered_voucher_type_effect =  array_combine($voucher_type_effect_codes, $voucher_type_effect_ids);
    
        $voucherTypeAccountReadBuilder = $this->read_db->table('voucher_type_account');
        $voucherTypeAccountReadBuilder->where(array('voucher_type_account_code' => 'bank'));
        $bank_voucher_type_account_id = $voucherTypeAccountReadBuilder->get()->getRow()->voucher_type_account_id;

        $statusLibrary = new \App\Libraries\Core\StatusLibrary();
    
        $data['income']['voucher_type_track_number'] = $this->generateItemTrackNumberAndName('voucher_type')['voucher_type_track_number'];
        $data['income']['voucher_type_name'] = $accountSystemCode.'-Income Funds Transfer Requests';
        $data['income']['voucher_type_is_active'] = 1;
        $data['income']['voucher_type_is_hidden'] = 1;
        $data['income']['voucher_type_abbrev'] = 'IFTR';
        $data['income']['fk_voucher_type_account_id'] = $bank_voucher_type_account_id;
        $data['income']['fk_voucher_type_effect_id'] = $ordered_voucher_type_effect['income'];
        $data['income']['voucher_type_is_cheque_referenced'] = 0;
        $data['income']['fk_account_system_id'] = $accountSystemId;
        $data['income']['voucher_type_created_by'] = $this->session->user_id;
        $data['income']['voucher_type_created_date'] = date('Y-m-d');
        $data['income']['voucher_type_last_modified_by'] = $this->session->user_id;
        $data['income']['voucher_type_last_modified_date'] = date('Y-m-d h:i:s');
        $data['income']['fk_status_id'] = $statusLibrary->getMaxApprovalStatusId('voucher_type')[0];
    
        $data['expense']['voucher_type_track_number'] = $this->generateItemTrackNumberAndName('voucher_type')['voucher_type_track_number'];
        $data['expense']['voucher_type_name'] = $accountSystemCode.'-Expense Funds Transfer Requests';
        $data['expense']['voucher_type_is_active'] = 1;
        $data['expense']['voucher_type_is_hidden'] = 1;
        $data['expense']['voucher_type_abbrev'] = 'EFTR';
        $data['expense']['fk_voucher_type_account_id'] = $bank_voucher_type_account_id;
        $data['expense']['fk_voucher_type_effect_id'] = $ordered_voucher_type_effect['expense'];
        $data['expense']['voucher_type_is_cheque_referenced'] = 0;
        $data['expense']['fk_account_system_id'] = $accountSystemId;
        $data['expense']['voucher_type_created_by'] = $this->session->user_id;
        $data['expense']['voucher_type_created_date'] = date('Y-m-d');
        $data['expense']['voucher_type_last_modified_by'] = $this->session->user_id;
        $data['expense']['voucher_type_last_modified_date'] = date('Y-m-d h:i:s');
        $data['expense']['fk_status_id'] = $statusLibrary->getMaxApprovalStatusId('voucher_type')[0];
    
      $data['chequecancellation']['voucher_type_track_number'] = $this->generateItemTrackNumberAndName('voucher_type')['voucher_type_track_number'];
      $data['chequecancellation']['voucher_type_name'] = $accountSystemCode.'-Voided Cheque';
      $data['chequecancellation']['voucher_type_is_active'] = 1;
      $data['chequecancellation']['voucher_type_is_hidden'] = 1;
      $data['chequecancellation']['voucher_type_abbrev'] = 'VChq';
      $data['chequecancellation']['fk_voucher_type_account_id'] = $bank_voucher_type_account_id;
      $data['chequecancellation']['fk_voucher_type_effect_id'] = $ordered_voucher_type_effect['expense'];
      $data['chequecancellation']['voucher_type_is_cheque_referenced'] = 1;
      $data['chequecancellation']['fk_account_system_id'] = $accountSystemId;
      $data['chequecancellation']['voucher_type_created_by'] = $this->session->user_id;
      $data['chequecancellation']['voucher_type_created_date'] = date('Y-m-d');
      $data['chequecancellation']['voucher_type_last_modified_by'] = $this->session->user_id;
      $data['chequecancellation']['voucher_type_last_modified_date'] = date('Y-m-d h:i:s');
      $data['chequecancellation']['fk_status_id'] = $statusLibrary->getMaxApprovalStatusId('voucher_type')[0];
    
        $flag = false;
    
        $this->write_db->transStart();
    
        if(!$this->checkMissingHiddenVoucherTypeByAbbreviation('IFTR')){
          $this->write_db->table('voucher_type')->insert($data['income']);
        }
    
        if(!$this->checkMissingHiddenVoucherTypeByAbbreviation('EFTR')){
          $this->write_db->table('voucher_type')->insert($data['expense']);
        }
    
      if(!$this->checkMissingHiddenVoucherTypeByAbbreviation('VChq')){
          $this->write_db->table('voucher_type')->insert($data['chequecancellation']);
      }
    
        $this->write_db->transComplete();
    
        if($this->write_db->transStatus() == true){
          $flag = true;
        }
    
        return $flag;
    
      }

      function checkMissingHiddenVoucherTypeByAbbreviation($abbreviation){

        $voucherTypeReadBuilder = $this->read_db->table('voucher_type');
        $voucherTypeReadBuilder->join('voucher_type_account','voucher_type_account.voucher_type_account_id=voucher_type.fk_voucher_type_account_id');
        $voucherTypeReadBuilder->join('voucher_type_effect','voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
        $voucherTypeReadBuilder->where(array('voucher_type_is_active' => 1, 'voucher_type_is_hidden' => 1));
        $voucherTypeReadBuilder->where(array('voucher_type_account_code' => 'bank'));
        $voucherTypeReadBuilder->where(array('voucher_type_abbrev' => $abbreviation));
        $voucherTypeReadBuilder->where(array('fk_account_system_id' => $this->session->user_account_system_id));
        $voucher_type = $voucherTypeReadBuilder->get();
    
        $check = false;
    
        if($voucher_type->getNumRows() > 0){
          $check = true;
        }
    
        return $check;
      }

      public function getHiddenVoucherType($voucher_type_abbrev, $account_system_id){
        $abbrev = ['IFTR','EFTR','VChq'];
    
        if(!in_array($voucher_type_abbrev, $abbrev)){
          return (object)[];
        }
    
        $voucherTypeReadBuilder = $this->read_db->table('voucher_type');
        $voucherTypeReadBuilder->join('voucher_type_account','voucher_type_account.voucher_type_account_id=voucher_type.fk_voucher_type_account_id');
        $voucherTypeReadBuilder->join('voucher_type_effect','voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
        $voucherTypeReadBuilder->where(array('voucher_type_is_active' => 1, 'voucher_type_is_hidden <> ' => 0));
        $voucherTypeReadBuilder->where(array('fk_account_system_id' => $account_system_id, 'voucher_type_abbrev' => $voucher_type_abbrev));
        $voucher_type_obj = $voucherTypeReadBuilder->get();
    
        $voucher_type = [];
        if($voucher_type_obj->getNumRows() > 0){
          $voucher_type = $voucher_type_obj->getRow();
        }
    
        return $voucher_type;
      }

  function isVoucherTypeAffectsBank(int $voucher_type_id): bool
  {

    $is_voucher_type_affects_bank = false;

    $voucherTypeReadBuilder = $this->read_db->table('voucher_type');
    $voucherTypeReadBuilder->where(['voucher_type_id' => $voucher_type_id]);
    $voucherTypeReadBuilder->groupStart();
    $voucherTypeReadBuilder->where(['voucher_type_account_code' => 'bank']);
    $voucherTypeReadBuilder->orWhere(['voucher_type_effect_code' => 'cash_contra']);
    $voucherTypeReadBuilder->groupEnd();
    $voucherTypeReadBuilder->join('voucher_type_account', 'voucher_type_account.voucher_type_account_id=voucher_type.fk_voucher_type_account_id');
    $voucherTypeReadBuilder->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
    $voucher_obj = $voucherTypeReadBuilder->get();

    if ($voucher_obj->getNumRows() > 0) {
      $is_voucher_type_affects_bank = true;
    }

    return $is_voucher_type_affects_bank;
  }

  function singleFormAddVisibleColumns(): array {
    return [
      'voucher_type_name',
      'voucher_type_abbrev',
      'voucher_type_is_active',
      'voucher_type_account_name',
      'voucher_type_effect_name',
      'voucher_type_is_cheque_referenced',
      'account_system_name'
    ];
  }

  function editVisibleColumns(): array {
    $fields = [...$this->singleFormAddVisibleColumns()];

    // Check if voucher type has been used 
    $voucherTypeIsUsed = $this->voucherTypeIsUsed(hash_id($this->id, 'decode'));

    if($voucherTypeIsUsed == true){
      unset($fields[array_search('voucher_type_account_name', $fields)]);
      unset($fields[array_search('voucher_type_effect_name', $fields)]);
      unset($fields[array_search('voucher_type_is_cheque_referenced', $fields)]);
    }

    return $fields;
  }

  function voucherTypeIsUsed($voucherTypeId){
    $voucherReadBuilder = $this->read_db->table('voucher');

    $voucherReadBuilder->where('fk_voucher_type_id', $voucherTypeId);
    $voucherReadBuilder->limit(1);
    $count = $voucherReadBuilder->countAllResults();
    
    return $count > 0 ? true : false;
  }

  function listTableVisibleColumns(): array {
    return ['voucher_type_track_number',...$this->singleFormAddVisibleColumns()];
  }

  function listTableWhere(\CodeIgniter\Database\BaseBuilder $queryBuilder): void {
    $queryBuilder->where('voucher_type_is_hidden', 0);
  }
   
}