<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\OfficeCashModel;
class OfficeCashLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

  protected $table;
  protected $grantsModel;

  function __construct()
  {
    parent::__construct();

    $this->grantsModel = new OfficeCashModel();

    $this->table = 'grants';
  }


  public function getActiveOfficeCashByOfficeId($office_id)
  {
    $builder = $this->read_db->table('office_cash');
    $builder->select(['office_cash_id', 'office_cash_name']);
    $builder->where(['office_cash_is_active' => 1, 'office_id' => $office_id]);
    $builder->join('account_system', 'account_system.account_system_id=office_cash.fk_account_system_id');
    $builder->join('office', 'office.fk_account_system_id=account_system.account_system_id');
    $cash_accounts = $builder->get()->getResultArray();

    return $cash_accounts;
  }

  /**
   * get_active_office_cash(): get json string of voucher types
   * @author  Livingstone Onduso
   * @dated: 4/06/2023
   * @access public
   * @return array
   * @param int $account_system_id
   */
  public function getActiveOfficeCash(int $account_system_id): array
  {
    $officeCashReadBuilder = $this->read_db->table('office_cash');
    $officeCashReadBuilder->select(['office_cash_id', 'office_cash_name']);
    $officeCashReadBuilder->where(['office_cash_is_active' => 1, 'fk_account_system_id' => $account_system_id]);
    $cash_accounts = $officeCashReadBuilder->get()->getResultArray();

    return $cash_accounts;

  }

  public function listTableVisibleColumns(): array
  {
    return [
      'office_cash_track_number',
      'office_cash_name',
      'office_cash_is_active',
      'account_system_name',
      'office_cash_created_date'
    ];
  }

  public function getAccountSystemOfficeCashAccounts($accountSystemId)
  {
    $officeCashBuilder = $this->read_db->table('office_cash');

    $officeCashBuilder->select('office_cash_id, office_cash_name');
    $officeCashBuilder->where('fk_account_system_id', $accountSystemId);
    $officeCashAccountObj = $officeCashBuilder->get();

    $officeCashAccounts = [];

    if ($officeCashAccountObj->getNumRows() > 0) {
      $officeCashAccounts = $officeCashAccountObj->getResultArray();
    }

    return $officeCashAccounts;
  }

  function officeCashAccountBalance($officeId, $officeCashId, $reportingMonth)
  {

    $financialReportLibrary = new \App\Libraries\Grants\FinancialReportLibrary();
    $account_balance = $financialReportLibrary->computeCashAtHand([$officeId], $reportingMonth, [], [], $officeCashId);

    if ($account_balance > -1 && $account_balance < 1) {
      $account_balance = 0;
    }

    return $account_balance;
  }

  private function officeCashHasTransactionInMonth($officeId, $officeCashId, $reportingMonth)
  {

    $office_cash_has_transaction_in_month = false;

    $start_month_date = date('Y-m-01', strtotime($reportingMonth));
    $end_month_date = date('Y-m-t', strtotime($reportingMonth));

    $builder = $this->read_db->table("voucher");
    $builder->where(array('voucher_date >= ' => $start_month_date, 'voucher_date <= ' => $end_month_date, 'voucher.fk_office_id' => $officeId));
    $builder->join('cash_recipient_account','cash_recipient_account.fk_voucher_id = voucher.voucher_id','left');

    $builder->groupStart();
    $builder->where('voucher.fk_office_cash_id', $officeCashId);
    $builder->orWhere('cash_recipient_account.fk_office_cash_id', $officeCashId);
    $builder->groupEnd();
    
    $count_of_vouchers = $builder->get()->getNumRows();

    $office_cash_has_transaction_in_month = $count_of_vouchers > 0;

    return $office_cash_has_transaction_in_month;

  }

  private function isOfficeCashObselete($officeId, $officeCashId, $reportingMonth)
  {
    // Office cash acount becomes obselete when all these conditions are met:
    // 1. Should not have funds
    // 2. Should not have vouchers in the given month
    // 3. Should be Inactive ***

    $is_office_cash_obselete = false;

    $account_balance = $this->officeCashAccountBalance($officeId,$officeCashId, $reportingMonth);
    $office_cash_has_transaction_in_month = $this->officeCashHasTransactionInMonth($officeId,$officeCashId, $reportingMonth);

    if ($account_balance == 0 && !$office_cash_has_transaction_in_month) {
      $is_office_cash_obselete = true;
    }

    return $is_office_cash_obselete;
  }

  public function getActiveOfficeCashAccountsByReportingMonth($office_id, $transacting_month)
  {
    $voucherLibrary = new \App\Libraries\Grants\VoucherLibrary();

    $accountSystemId = $voucherLibrary->officeAccountSystem($office_id)->account_system_id;
    $officeCashAccounts = $this->getAccountSystemOfficeCashAccounts($accountSystemId);

    $activeOfficeCashAccounts = [];

    foreach ($officeCashAccounts as $officeCashAccount) {
      $is_office_cash_obselete = $this->isOfficeCashObselete($office_id, $officeCashAccount['office_cash_id'], $transacting_month);

      if (!$is_office_cash_obselete) {
        $activeOfficeCashAccounts[] = $officeCashAccount;
      }

    }

    return $activeOfficeCashAccounts;
  }

}