<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\IncomeAccountModel;
class IncomeAccountLibrary extends GrantsLibrary
{

    protected $table;
    protected $incomeAccountModel;

    function __construct()
    {
        parent::__construct();

        $this->incomeAccountModel = new IncomeAccountModel();

        $this->table = 'income_account';
    }

    function detailTables(): array {
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

  function incomeAccountMissingProjectAllocation(int $office_id, array $office_bank_ids):array {

    $income_accounts_with_allocation = [];

    $officeLibrary = new \App\Libraries\Core\OfficeLibrary();
    $financialReportLibrary = new \App\Libraries\Grants\FinancialReportLibrary();

    $office_start_dates = $officeLibrary->getOfficeStartDateById($office_id);
    $office_start_month = $office_start_dates['month_start_date'];

    $all_financial_report_income_accounts = $financialReportLibrary->monthUtilizedIncomeAccounts([$office_id],$office_start_month,[],[]);
    $office_bank_financial_report_income_accounts = $financialReportLibrary->monthUtilizedIncomeAccounts([$office_id],$office_start_month,[],$office_bank_ids);

    $all_income_account_ids = array_column($all_financial_report_income_accounts, 'income_account_id');
    $office_bank_all_income_account_ids = array_column($office_bank_financial_report_income_accounts, 'income_account_id');

    $income_accounts_with_allocation = array_diff($all_income_account_ids,$office_bank_all_income_account_ids);

    return $income_accounts_with_allocation;

  }
   
}