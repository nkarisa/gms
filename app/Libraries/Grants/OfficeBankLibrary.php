<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\OfficeBankModel;
class OfficeBankLibrary extends GrantsLibrary
{

  protected $table;
  protected $grantsModel;

  function __construct()
  {
    parent::__construct();

    $this->grantsModel = new OfficeBankModel();

    $this->table = 'grants';
  }


  public function detailListTableVisibleColumns(): array
  {
    return [
      'office_bank_track_number',
      'office_bank_name',
      'office_bank_is_active',
      'office_bank_account_number',
      'office_name',
      'bank_name',
      'office_bank_chequebook_size',
      'office_bank_is_default',
      'status_name',
      'approval_name'
    ];
  }

  public function singleFormAddVisibleColumns(): array
  {
    return [
      'office_name',
      'office_bank_name',
      'bank_name',
      'office_bank_account_number',
      'office_bank_chequebook_size'
    ];
  }

  function editVisibleColumns(): array
  {
    return [
      'office_name',
      'bank_name',
      'office_bank_name',
      'office_bank_account_number',
      'office_bank_is_default',
      'office_bank_is_active',
      'office_bank_chequebook_size',
      'office_bank_book_exemption_expiry_date'

    ];
  }

  function lookupValues(): array
  {
    $lookupValues = parent::lookupValues();
    $offices = [];

    $builder = $this->read_db->table('office');
    $builder->select(['office_id', 'office_name']);
    $builder->where(['fk_context_definition_id' => 1, 'office_is_active' => 1]);
    $builder->orWhere('office_is_readonly', 0);
    $offices_obj = $builder->get();

    if($offices_obj->getNumRows() > 0){
      $offices = $offices_obj->getResultArray();
    }

    $lookupValues['office'] = $offices;

    return $lookupValues;
  }

  function get_active_office_banks_by_reporting_month($office_ids, $reporting_month, $project_ids = [], $office_bank_ids = [])
  {
    $office_banks = $this->getOfficeBanks($office_ids, $project_ids, $office_bank_ids);
    $office_banks_array = [];

    $cnt = 0;
    for ($i = 0; $i < count($office_banks); $i++) {
      $is_office_bank_obselete = $this->isOfficeBankObselete($office_banks[$i]['office_bank_id'], $reporting_month);

      if (!$is_office_bank_obselete) {
        // unset($office_banks[$i]);
        $office_banks_array[$cnt] = $office_banks[$i];
        $cnt++;
      }
    }

    return $office_banks_array;
  }

  function getOfficeBanks(array $office_ids, array $project_ids = [], array $office_bank_ids = []): array
  {
    $builder = $this->read_db->table("office_bank_project_allocation");
    $builder->select(array('DISTINCT(office_bank_id)', 'office_bank_name'));
    $builder->whereIn('fk_office_id', $office_ids);
    $builder->join('office_bank', 'office_bank.office_bank_id=office_bank_project_allocation.fk_office_bank_id');

    if (!empty($office_bank_ids)) {
      $builder->whereIn('fk_office_bank_id', $office_bank_ids);
    }

    $office_banks = $builder->get()->getResultArray();
    return $office_banks;
  }

  public function isOfficeBankObselete(int $office_bank_id, string $reporting_month)
  {
    // Office bank acount becomes obselete when all these conditions are met:
    // 1. Should not have funds
    // 2. Should not have outstanding cheques and deposit in transit
    // 3. Should not have vouchers in the given month
    // 4. Should be Inactive ***

    $builder = $this->read_db->table("office_bank");
    $builder->where(array('office_bank_id' => $office_bank_id));
    $office_id = $builder->get()->getRow()->fk_office_id;

    $is_office_bank_obselete = false;

    $account_balance = $this->officeBankAccountBalance($office_bank_id, $reporting_month);
    $office_bank_outstanding_cheques = $this->officeBankOutstandingCheques($office_id, $reporting_month);
    $office_bank_transit_deposit = $this->officeBankTransitDeposit($office_id, $reporting_month);

    $office_bank_outstanding_cheques_amount = 0;

    if (!empty($office_bank_outstanding_cheques)) {
      $office_bank_outstanding_cheques_amount = array_sum(array_column($office_bank_outstanding_cheques, 'amount'));
    }

    $office_bank_transit_deposit_amount = 0;

    if (!empty($office_bank_transit_deposit)) {
      $office_bank_transit_deposit_amount = array_sum(array_column($office_bank_transit_deposit, 'amount'));
    }

    $office_bank_has_transaction_in_month = $this->officeBankHasTransactionInMonth($office_bank_id, $reporting_month);

    if ($account_balance == 0 && $office_bank_outstanding_cheques_amount == 0 && $office_bank_transit_deposit_amount == 0 && !$office_bank_has_transaction_in_month) {
      $is_office_bank_obselete = true;
    }

    return $is_office_bank_obselete;
  }

  function officeBankAccountBalance($office_bank_id, $reporting_month)
  {
    $office_id = 0;

    $builder = $this->read_db->table("office_bank");
    $builder->select(array('fk_office_id as office_id'));
    $builder->where(array('office_bank_id' => $office_bank_id));
    $office_bank_obj = $builder->get();

    if ($office_bank_obj->getNumRows() > 0) {
      $office_id = $office_bank_obj->getRow()->office_id;
    }

    $financialReportLibrary = new \App\Libraries\Grants\FinancialReportLibrary();
    $account_balance = $financialReportLibrary->computeCashAtBank([$office_id], $reporting_month, [], [$office_bank_id]);

    if ($account_balance > -1 && $account_balance < 1) {
      $account_balance = 0;
    }

    return $account_balance;
  }

  private function officeBankOutstandingCheques($office_id, $reporting_month)
  {
    $financialReportLibrary = new \App\Libraries\Grants\FinancialReportLibrary();
    $outstanding_cheques = [];
    $reporting_month = date('Y-m-01', strtotime($reporting_month));

    $outstanding_cheques = $financialReportLibrary->listOustandingChequesAndDeposits([$office_id], $reporting_month, 'expense', 'bank_contra', 'bank');

    return $outstanding_cheques;
  }

  private function officeBankTransitDeposit($office_id, $reporting_month)
  {
    $financialReportLibrary = new \App\Libraries\Grants\FinancialReportLibrary();
    $deposit_in_transit = [];
    $reporting_month = date('Y-m-01', strtotime($reporting_month));

    $deposit_in_transit = $financialReportLibrary->listOustandingChequesAndDeposits([$office_id], $reporting_month, 'income', 'cash_contra', 'bank');

    return $deposit_in_transit;
  }

  private function officeBankHasTransactionInMonth($office_bank_id, $reporting_month)
  {

    $office_bank_has_transaction_in_month = false;

    $start_month_date = date('Y-m-01', strtotime($reporting_month));
    $end_month_date = date('Y-m-t', strtotime($reporting_month));

    $builder = $this->read_db->table("voucher");
    $builder->where(array('voucher_date >= ' => $start_month_date, 'voucher_date <= ' => $end_month_date, 'fk_office_bank_id' => $office_bank_id));
    $count_of_vouchers = $builder->get()->getNumRows();

    $office_bank_has_transaction_in_month = $count_of_vouchers > 0;

    return $office_bank_has_transaction_in_month;

  }

  function officeBankAccounts($office_id, $office_bank_id = 0)
  {
    $builder = $this->read_db->table("office_bank");
    $builder->select(array('office_bank_id', 'office_bank_account_number', 'bank_name', 'office_bank_name'));
    $builder->join('bank', 'bank.bank_id=office_bank.fk_bank_id');

    if ($office_bank_id > 0) {
      $builder->where(array('office_bank_id' => $office_bank_id));
    }

    $builder->where(array('fk_office_id' => $office_id));
    $result = $builder->get()->getResultArray();

    return $result;
  }

  function officeHasMultipleBankAccounts($office_id)
  {
    $result = $this->read_db->table("office_bank")
    ->getWhere(array('fk_office_id' => $office_id))->getNumRows();
    $office_has_multiple_bank_accounts = $result > 1 ? true : false;
    return $office_has_multiple_bank_accounts;
  }


  function changeFieldType(): array{
    $change_field_type = [];
    $change_field_type['office_bank_chequebook_size']['field_type']='select';
    $change_field_type['office_bank_chequebook_size']['options'] = [24=>24, 48=>48, 50 => 50,60=>60, 100 => 100, 150 => 150, 200 => 200];
    return $change_field_type;
  }

  private function activeChequeBookWarning($hash_office_bank_id){
    $message = '';
    $chequeBookLibrary = new \App\Libraries\Grants\ChequeBookLibrary();
    $office_bank_id = hash_id($hash_office_bank_id,'decode');
    $leaves = $chequeBookLibrary->getRemainingUnusedChequeLeaves($office_bank_id);
    if(!empty($leaves)){
      $message = "<br/>".get_phrase("cheque_book_use_expiry_edit_warning","Editing the cheque book use expiry date is not permitted for office banks with active cheque book.");
    }
    return $message;
  }

  function pagePosition(){
    $widget = [];
    if($this->action == 'view' || $this->action == 'edit'){
      $message = $this->createAccountBalanceMessageForOfficeBankAccount();
      $message .= $this->activeChequeBookWarning($this->id);
      if($message != ""){
        $widget['position_1']['view'][] =  '<div class = "warning">'.$message.'</div><hr/>';
        $widget['position_1']['edit'][] =  '<div class = "warning">'.$message.'</div><hr/>';
      } 
    }

    if($this->action == 'singleFormAdd'){
      // Only show if this is not the first office bank account for the office
      $message = $this->checksWhenCreatingOfficeBank();
      $widget['position_1']['singleFormAdd'][] = $message;
    }

    return $widget;
  }

  private function checksWhenCreatingOfficeBank(){
    $message = '<div id = "office_bank_conditions" class = "hidden info">
                  <b>'.get_phrase('actions_before_creating_a_new_office_bank','Before creating another office bank account for an office for an office to transition to, please make sure that you have done the following').':</b><br/><br/>
                  
                  <div style = "text-align: left;position:relative; left: 250px;">
                    <input type = "checkbox" class = "office_bank_checklist_items"/> '.get_phrase('committee_minutes_required','The FCP has discussed and minuted the matter with the FCP committee and have submitted the minutes to you').' <br/>
                    <input type = "checkbox" class = "office_bank_checklist_items"/> '.get_phrase('submitted_bank_details_to_finance','The new bank account details have been sent to the National Office accountants and ready to receive funds in the next disbursement').' <br/>
                    <input type = "checkbox" class = "office_bank_checklist_items"/> '.get_phrase('sent_copy_of_cheque_book_leaf','The FCP has sent you a screenshot of the first leaf of the cheque book given by the new bank').' <br/>
                    <input type = "checkbox" class = "office_bank_checklist_items"/> '.get_phrase('noted_amount_to_transfer','You are aware of the total amount the FCP is going to transfer from the old office bank to the new office bank. You will be required to follow up on this in your next FCP visit').' <br/>
                    <input type = "checkbox" class = "office_bank_checklist_items"/> '.get_phrase('noted_outstanding_cheques','You have checked if the FCP has outstanding cheques in the old office bank and adviced them not to request for the physical closure of the old account until all cheques are paid by the bank').' <br/>
                    <input type = "checkbox" class = "office_bank_checklist_items"/> '.get_phrase('uploaded_minutes_and_cheque_leaf','Make sure that you upload the minutes, last bank statement of the previous bank and first leaf the new bank account cheque book in the comment section of this record after creation for future reference').' <br/><br/>
                  
                  
                  '.get_phrase('funds_transfer_condition','Note: FCP can only transfer funds to the new office bank as per the book balance and not the whole amount in the old bank account unless they lack outstanding cheques').' <br/><br/>

                  '.get_phrase('After creating the new office bank record, you will be requred to do the following follow up. Make sure you create a Connect Facilitation Plan Task for each of the items below').':<br/><br/>

                
                    1. '.get_phrase('follow_up_on_transfered_funds','The FCP transferred the exact funds as agreed when creating the new office bank record').' <br/>
                    2. '.get_phrase('follow_up_on_transfer_voucher','A Bank to Bank Transfer voucher has been created in the system for the funds tranfers done').' <br/>
                    3. '.get_phrase('follow_up_on_cheques_clearance','All the outstanding cheques that were present in the old bank have been fully paid and the FCP has given instrusctions to close the account').' <br/>
                  </div>
              </div>
              ';
    
    return $message;
  }

  function createAccountBalanceMessageForOfficeBankAccount(){
    // Office bank Id
    $office_bank_id = hash_id($this->id,'decode');

    // Office Id
    $builder = $this->read_db->table('office_bank');
    $builder->where(array('office_bank_id' => $office_bank_id));
    $office_id = $builder->get()->getRow()->fk_office_id;

    // Account balance amount
    $voucherLibrary = new \App\Libraries\Grants\VoucherLibrary();
    $reporting_month = date('Y-m-01',strtotime($voucherLibrary->getVoucherDate($office_id)));
    $account_balance = $this->officeBankAccountBalance($office_bank_id, $reporting_month);

    // Office Currency Code
    $countryCurrencyLibrary = new \App\Libraries\Grants\CountryCurrencyLibrary();
    $currency_code = $countryCurrencyLibrary->getCountryCurrencyCodeByOfficeId($office_id);

    $message = '';

    if($account_balance > 0){
      $message = get_phrase(
        'account_balance_deactivation_notification',
        'Office bank account cannot be closed/deactivated if balance is not zero. The current book balance is {{currency_code}}. {{account_balance}}.', 
        ['currency_code' => $currency_code, 'account_balance' => number_format(round($account_balance,2),2)]);
    }

    return $message;
  }

}