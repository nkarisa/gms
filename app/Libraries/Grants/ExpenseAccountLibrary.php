<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\ExpenseAccountModel;
class ExpenseAccountLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new ExpenseAccountModel();

        $this->table = 'expense_account';
    }

    /**
   * get_expense_income_account_by_id
   * 
   * Get the income account id for a given expense account id
   * 
   * @author Nicodemus Karisa
   * @authored_date 8th June 2023
   * @access public
   * 
   * @param int $expense_account_id
   * 
   * @return int - Income account id
   */

  public function getExpenseIncomeAccountId(int $expense_account_id): int
  {

    $income_account_id = 0;
    $builder = $this->read_db->table("expense_account");
    $builder->select(array('fk_income_account_id'));
    $builder->where(array('expense_account_id' => $expense_account_id));
    $expense_account_obj = $builder->get();

    if ($expense_account_obj->getNumRows() > 0) {
      $income_account_id = $expense_account_obj->getRow()->fk_income_account_id;
    }

    return  $income_account_id;
  }

  public function createAccountSystemDepreactionExpenseAccount(array $accountSystemIds){
    $expenseAccountReadBuilder = $this->read_db->table('expense_account');
    $expenseAccountWriteBuilder = $this->write_db->table('expense_account');

    // Check if there is depreciation expense vote head category if not create
    $expenseVoteHeadsCategoryLibrary = new \App\Libraries\Grants\ExpenseVoteHeadsCategoryLibrary();
    $depreciationExpenseVoteHeadsCategoryId = $expenseVoteHeadsCategoryLibrary->checkAndCreateDepreciationExpenseVoteHeadCategory();
    

    // check if a depreciation expense account is present
    $expenseAccountReadBuilder->select(['income_account_id','expense_account_id','income_account_code','expense_account_code','fk_account_system_id']);
    $expenseAccountReadBuilder->where(['fk_expense_vote_heads_category_id' => $depreciationExpenseVoteHeadsCategoryId]);
    $expenseAccountReadBuilder->whereIn('fk_account_system_id',$accountSystemIds);
    $expenseAccountReadBuilder->join('income_account','income_account.income_account_id=expense_account.fk_income_account_id');
    $depreciationIncomeAccountsObj = $expenseAccountReadBuilder->get();

    $depreciationExpenseAccountsByAccountingSystemId = [];
    if($depreciationIncomeAccountsObj->getNumRows() > 0){
      $depreciationIncomeAccounts = $depreciationIncomeAccountsObj->getResultArray();

      foreach($depreciationIncomeAccounts as $depreciationIncomeAccount){
        $depreciationExpenseAccountsByAccountingSystemId[$depreciationIncomeAccount['fk_account_system_id']] = $depreciationIncomeAccount;
      }
    }

    // Get Support income account for the accounting system
    $incomeAccountLibrary = new \App\Libraries\Grants\IncomeAccountLibrary();
    $supportIncomeAccounts = $incomeAccountLibrary->getSupportIncomeAccountsByAccountSystemIds($accountSystemIds);

    foreach($accountSystemIds as $accountSystemId){
      if(!array_key_exists($accountSystemId, $depreciationExpenseAccountsByAccountingSystemId)){
        // Create the depreciation expense account if missing
        $expenseAccountData['expense_account_track_number'] = '';
        $expenseAccountData['expense_account_name'] = '';
        $expenseAccountData['expense_account_description'] = '';
        $expenseAccountData['expense_account_code'] = '';
        $expenseAccountData['expense_account_is_admin'] = '';
        $expenseAccountData['fk_expense_vote_heads_category_id'] = '';
        $expenseAccountData['expense_account_is_medical_rembursable'] = '';
        $expenseAccountData['expense_account_is_active'] = '';
        $expenseAccountData['expense_account_is_budgeted'] = '';
        $expenseAccountData['fk_income_account_id'] = '';
        $expenseAccountData['expense_account_created_date'] = '';
        $expenseAccountData['expense_account_last_modified_date'] = '';
        $expenseAccountData['expense_account_created_by'] = '';
        $expenseAccountData['expense_account_last_modified_by'] = '';
        $expenseAccountData['fk_approval_id'] = '';
        $expenseAccountData['fk_status_id'] = '';
      }
    }
  }

  public function createAccountSystemPayrollLiabilityExpenseAccount(array $accountSystemIds){

  }
   
}