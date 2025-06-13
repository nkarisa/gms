<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\IncomeAccountModel;

class IncomeAccountLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

  protected $table;
  protected $incomeAccountModel;

  function __construct()
  {
    parent::__construct();

    $this->incomeAccountModel = new IncomeAccountModel();

    $this->table = 'income_account';
  }

  function detailTables(): array
  {
    return ['expense_account'];
  }

  /**
   * income_account_missing_project_allocation
   * 
   * Get income accounts at opening that lack project assignment
   * 
   * @author Unknown
   * @reviewed_by Nicodemus Karisa
   * @reviewed_date 14th June 2023
   * 
   * @param int $office_id - Office Id
   * @param array $office_bank_ids - List office banks for the given office
   * 
   * @return array - List of income accounts missing project assignment
   */

  function incomeAccountMissingProjectAllocation(int $office_id, array $office_bank_ids): array
  {

    $income_accounts_with_allocation = [];

    $officeLibrary = new \App\Libraries\Core\OfficeLibrary();
    $financialReportLibrary = new \App\Libraries\Grants\FinancialReportLibrary();

    $office_start_dates = $officeLibrary->getOfficeStartDateById($office_id);
    $office_start_month = $office_start_dates['month_start_date'];

    $all_financial_report_income_accounts = $financialReportLibrary->monthUtilizedIncomeAccounts([$office_id], $office_start_month, [], []);
    $office_bank_financial_report_income_accounts = $financialReportLibrary->monthUtilizedIncomeAccounts([$office_id], $office_start_month, [], $office_bank_ids);

    $all_income_account_ids = array_column($all_financial_report_income_accounts, 'income_account_id');
    $office_bank_all_income_account_ids = array_column($office_bank_financial_report_income_accounts, 'income_account_id');

    $income_accounts_with_allocation = array_diff($all_income_account_ids, $office_bank_all_income_account_ids);

    return $income_accounts_with_allocation;
  }


 /**
   *incomeAccountByOfficeId():This method returns array of income accounts.
   * @author Livingstone Onduso: Dated 30-01-2025
   * @access public
   * @param int $office_id
   */
  public function incomeAccountByOfficeId(int $office_id):array
  {
    $builder = $this->read_db->table('income_account');

    $builder->select(['income_account_id', 'income_account_name']);
    $builder->where([
      'income_account_is_budgeted' => 1,
      'income_account_is_active'   => 1,
      'office_id'                  => $office_id
    ]);

    $builder->join('account_system', 'account_system.account_system_id = income_account.fk_account_system_id');
    $builder->join('office', 'office.fk_account_system_id = account_system.account_system_id');

    $query = $builder->get();
    $income_accounts = $query->getResultArray();

    return $income_accounts;//$this->response->setJSON($income_accounts);
  }

  function getExpenseIncomeAccount($expense_income_id)
  {
    $builder = $this->read_db->table("income_account");
    $builder->select(array('income_account_id', 'income_account_name'));
    $builder->join('expense_account', 'expense_account.fk_income_account_id=income_account.income_account_id');
    $builder->where(array('expense_account_id' => $expense_income_id));
    $income_account = $builder->get()->getRow();

    return $income_account;
  }

   /**
  * Enhancement
   *get_project_allocation_income_account(): Returns  income account numeric value
   * @author Livingstone Onduso: Dated 29-06-2023
   * @access public
   * @param int Int $project_allocation_id
   * @return int
   **/
  function getProjectAllocationIncomeAccount(int $project_allocation_id):int
  {
    $projectIncomeAccountReadBuilder = $this->read_db->table('project_income_account');
    $projectIncomeAccountReadBuilder->select(['fk_income_account_id']);
    $projectIncomeAccountReadBuilder->join('project_allocation', 'project_allocation.fk_project_id=project_income_account.fk_project_id');
    $projectIncomeAccountReadBuilder->where(['project_allocation_id' => $project_allocation_id]);
    $income_account_id=$projectIncomeAccountReadBuilder->get()->getRow()->fk_income_account_id;

    return $income_account_id;
  }

  public function getSupportIncomeAccountsByAccountSystemIds(array $accountSystemIds, array $accountSystemIdsWithCodes){
    $incomeAccountReadBuilder = $this->read_db->table('income_account');
    $incomeAccountWriteBuilder = $this->write_db->table('income_account');

    // Get support income account id
    $incomeAccountReadBuilder->select(['income_account_id','income_account_name','income_account_code','fk_account_system_id']);
    $incomeAccountReadBuilder->whereIn('fk_account_system_id', $accountSystemIds);
    $incomeAccountReadBuilder->join('income_vote_heads_category','income_vote_heads_category.income_vote_heads_category_id=income_account.fk_income_vote_heads_category_id');
    $incomeAccountReadBuilder->where(['income_vote_heads_category_code' => 'support']);
    $supportIncomeAccountObj = $incomeAccountReadBuilder->get();

    $supportIncomeAccountsByAccountSystemIds = [];

    if($supportIncomeAccountObj->getNumRows() > 0){
      $incomeAccountsResult = $supportIncomeAccountObj->getResultArray();

      foreach($incomeAccountsResult as $incomeAccount){
        $supportIncomeAccountsByAccountSystemIds[$incomeAccount['fk_account_system_id']] = $incomeAccount;
      }
    }

    // Get support income vote heads category
    $incomeVoteHeadsCategoryLibrary = new \App\Libraries\Grants\IncomeVoteHeadsCategoryLibrary();
    $supportVoteHeadCategoryId = $incomeVoteHeadsCategoryLibrary->getSupportIncomeVoteHeadsCategoryId();

    foreach($accountSystemIds as $accountSystemId){
      if(!array_key_exists($accountSystemId, $supportIncomeAccountsByAccountSystemIds)){
        // Create a support income account if not existing and add it to the supportIncomeAccountsByAccountSystemIds
        $itemTrackNumberAndName = $this->generateItemTrackNumberAndName('income_account');
        $statusLibrary = new \App\Libraries\Core\StatusLibrary();

        $incomeAccountCode = $accountSystemIdsWithCodes[$accountSystemId].'R001';

        $incomeAccountData['income_account_track_number'] = $itemTrackNumberAndName['income_account_track_number'];
        $incomeAccountData['income_account_name'] = $incomeAccountCode.'-'.get_phrase('child_support');
        $incomeAccountData['income_account_description'] = get_phrase('child_support_funds_desciption');
        $incomeAccountData['income_account_code'] = $incomeAccountCode;
        $incomeAccountData['income_account_reconciliation_is_required'] = 0;
        $incomeAccountData['income_account_is_active'] = 1;
        $incomeAccountData['fk_income_vote_heads_category_id'] = $supportVoteHeadCategoryId;
        $incomeAccountData['income_account_is_budgeted'] = 1;
        $incomeAccountData['income_account_is_donor_funded'] = 0;
        $incomeAccountData['fk_account_system_id'] = $accountSystemId;
        $incomeAccountData['income_account_created_date'] = date('Y-m-d');
        $incomeAccountData['income_account_last_modified_date'] = date('Y-m-d');
        $incomeAccountData['income_account_created_by'] = $this->session->user_id;
        $incomeAccountData['income_account_last_modified_by'] = $this->session->user_id;
        $incomeAccountData['fk_approval_id'] = NULL;
        $incomeAccountData['fk_status_id'] = $statusLibrary->initialItemStatus('income_account');

        $incomeAccountWriteBuilder->insert($incomeAccountData);

        $incomeAccountId = $this->write_db->insertID();

        $supportIncomeAccountsByAccountSystemIds[$accountSystemId] = [
          'income_account_id' => $incomeAccountId,
          'income_account_name' => $incomeAccountData['income_account_name'],
          'income_account_code' => $incomeAccountData['income_account_code'],
          'fk_account_system_id'  => $incomeAccountData['fk_account_system_id'],
        ];
      }
    }

    return $supportIncomeAccountsByAccountSystemIds;
  }
}
