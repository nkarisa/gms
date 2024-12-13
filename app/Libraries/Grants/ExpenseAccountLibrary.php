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
   
}