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
    $lookupValues = array();

    $lookupValues['office'] = [];

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

    $account_balance = $this->office_bank_account_balance($office_bank_id, $reporting_month);
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

  function office_bank_account_balance($office_bank_id, $reporting_month)
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
}