<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\BudgetLimitModel;
class BudgetLimitLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new BudgetLimitModel();

        $this->table = 'grants';
    }
    function index() {}


    // function loadBudgetListView($budget_id)
    // {

      
    //     $data['data'] = $this->getBudgetLimitByBudgeId($budget_id);

    //     $budget_limit_view = view('budget_limit/budget_limit_list', $data);

    //     return $budget_limit_view;
    // }

     /**
     * budgetLimitRemainingAmount
     * @param int $budget_id , int $income_account_id
     * @return array
     * @access public
     * @author: Livingstone Onduso
     * @Date: 4/12/2022
     */

    public function budgetLimitRemainingAmount(int $budget_id, int $income_account_id):float
    {
        $budget_limit_amount = $this->budgetLimitAmount($budget_id, $income_account_id);

        //log_message('error',json_encode($budget_limit_amount ));
        
        $budgetLib=new \App\Libraries\Grants\BudgetLibrary();

        $sum_year_budgeted_amount = $budgetLib->budgetToDateAmountByIncomeAccount($budget_id, $income_account_id);

        //log_message('error',json_encode( $sum_year_budgeted_amount ));

        return (float)$budget_limit_amount - (float)$sum_year_budgeted_amount;
    }
   /**
     * budgetLimitAmount
     * @param int $budget_id, $income_account_id
     * @return array
     * @access private
     * @author: Livingstone Onduso
     * @Date: 4/12/2022
     */
    private function budgetLimitAmount(int $budget_id, int $expense_account_id):float
    {

        $budget_limit_amount = 0;


        //Get the income_account
        $writer_builder_budget_limit=$this->write_db->table('expense_account');
        $writer_builder_budget_limit->where(['expense_account_id'=>$expense_account_id]);
        $income_account_id=$writer_builder_budget_limit->get()->getRow()->fk_income_account_id;


        $builder_budget_limit = $this->read_db->table('budget_limit');
        $builder_budget_limit->join('budget', 'budget.budget_id = budget_limit.fk_budget_id');
        $builder_budget_limit->where([
        'fk_budget_id' => $budget_id,
        'fk_income_account_id' => $income_account_id
        ]);
        $budget_limit_obj=$builder_budget_limit->get();

        if ($budget_limit_obj->getNumRows() > 0) {
            $budget_limit_amount = $budget_limit_obj->getRow()->budget_limit_amount;
        }

        return $budget_limit_amount;
    }
     /**
     * getBudgetLimitByBudgeId
     * @param float $budget_id
     * @return array
     * @access private
     * @author: Livingstone Onduso
     * @Date: 4/12/2022
     */
    public function getBudgetLimitByBudgeId(float $budget_id):array
    {

        $budget_limits = [];

        // Query
        $builder = $this->read_db->table('budget_limit');
        $builder->select([
            'budget_limit_id',
            'budget_limit_track_number',
            'office_code',
            'budget_year',
            'budget_tag_name',
            'income_account_name',
            'budget_limit_amount'
        ]);
        $builder->where('budget_id', $budget_id);
        $builder->join('budget', 'budget.budget_id = budget_limit.fk_budget_id');
        $builder->join('office', 'office.office_id = budget.fk_office_id');
        $builder->join('budget_tag', 'budget_tag.budget_tag_id = budget.fk_budget_tag_id');
        $builder->join('income_account', 'income_account.income_account_id = budget_limit.fk_income_account_id');
        $builder->orderBy('income_account_id');

        $budget_limit_obj = $builder->get();

        // Check if there are rows and fetch the result as an array
        if ($budget_limit_obj->getNumRows() > 0) {
            $budget_limits = $budget_limit_obj->getResultArray();
        }

        //$userLib=new \App\Libraries\Core\UserLibrary();

        //$data['data'] = $budget_limits;
       // $data['user_has_update_budget_limit_permission']=$userLib->checkRoleHasPermissions('Budget_limit', 'update');;

        return $budget_limits;
    }

    public function lookupValues(): array
  {
    $lookup_values = [];

    // if (!session()->get('system_admin')) {

    // /**
    //  * 
    //  */
    //   $builder = $this->read_db->table('income_account');  // Query builder for 'office' table

    //   // Apply WHERE IN condition for office_id
    //   $builder->join('office', 'office.fk_account_system_id=income_account.fk_account_system_id');
    //   $builder->whereIn('office_id', array_column(session()->get('hierarchy_offices'), 'office_id'));
    //   $builder->where([
    //     'income_account_is_active ' => 1,
       
    //   ]);

    //   // Fetch results
    //   $lookup_values['fk_income_account_id'] = $builder->get()->getResultArray();

    // }

    // log_message('error',json_encode($lookup_values));
    return $lookup_values;
  }

    
}
