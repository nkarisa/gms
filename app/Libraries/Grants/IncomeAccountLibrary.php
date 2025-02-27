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

  public function list($builder, array $selectColumns, $parentId = null, $parentTable = null): array
  {
    $this->lookupJoins($builder);

    $selectColumns = array_values($selectColumns);
    $this->dataTableBuilder($builder, $this->controller, $selectColumns);
    $builder->select($selectColumns);
    $result_obj = $builder->get();

    $results = [];

    if ($result_obj->getNumRows() > 0) {
      $results = $result_obj->getResultArray();
    }

    return compact('results');
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
}
