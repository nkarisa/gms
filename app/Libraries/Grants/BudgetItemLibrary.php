<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\BudgetItemModel;
class BudgetItemLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new BudgetItemModel();

        $this->table = 'grants';
    }

    public function getBudgetIdByBudgetItemId(int $budget_item_id):int{

        $budgetBuilder= $this->read_db->table('budget_item');
        $budgetBuilder->select('fk_budget_id');
        $budgetBuilder->where('budget_item_id', $budget_item_id);
        $budget_data=$budgetBuilder->get();
        //$budget_data = $budgetBuilder->getRowArray();
    
        $budget_id = 0;
        
        if($budget_data->getNumRows() > 0){
          $budget_id = $budget_data->getRow()->fk_budget_id;
        }
    
        return $budget_id;
    
      }

      function projectBudgetableExpenseAccounts(int $project_allocation_id){
    
    
        $builder_accounts = $this->read_db->table('expense_account');
        $builder_accounts->join('income_account', 'income_account.income_account_id = expense_account.fk_income_account_id');
        $builder_accounts->join('project_income_account', 'project_income_account.fk_income_account_id = income_account.income_account_id');
        $builder_accounts->join('project', 'project.project_id = project_income_account.fk_project_id');
        $builder_accounts->join('project_allocation', 'project_allocation.fk_project_id = project.project_id');
        $builder_accounts->where([
        'project_allocation_id' => $project_allocation_id,
        'expense_account_is_budgeted' => 1,
        'expense_account_is_active' => 1
        ]);
        $builder_accounts->select(['expense_account_id', 'expense_account_name']);
        $accounts_records=$builder_accounts->get();
        $accounts=$accounts_records->getResultArray();
        return $accounts;
      }
    
      

      public function getObjectiveInterventions(int $objective_id){

        $pca_strategy_interventions = [];
    
        $builder_pca_strategy = $this->read_db->table('pca_strategy');
        $builder_pca_strategy->select(['pca_strategy_intervention_id', 'pca_strategy_intervention_name']);
        $builder_pca_strategy->where('pca_strategy_objective_id', $objective_id);
        $pca_strategy_interventions_obj=$builder_pca_strategy->get();

        if($pca_strategy_interventions_obj->getNumRows() > 0){
          $pca_strategy_interventions_array = $pca_strategy_interventions_obj->getResultArray();
    
          $pca_strategy_intervention_ids = array_column($pca_strategy_interventions_array,'pca_strategy_intervention_id');
          $pca_strategy_intervention_names = array_column($pca_strategy_interventions_array,'pca_strategy_intervention_name');
    
          $pca_strategy_interventions = array_combine($pca_strategy_intervention_ids, $pca_strategy_intervention_names);
        }
    
        return $pca_strategy_interventions;
      }
      public function getOfficeYearPcaObjectives(int $office_id, int $budget_id){

        $objectives = [];
        $budgetLib=new \App\Libraries\Grants\BudgetLibrary();
        $fy_date_range = $budgetLib->getBudgetFyDates($budget_id); 

       $builder = $this->read_db->table('pca_strategy');
       $builder->distinct();
       $builder->select(['pca_strategy_objective_id', 'pca_strategy_objective_name']);
       $builder->where('fk_office_id', $office_id);
       $builder->where('pca_strategy_end_date', $fy_date_range['fy_end_date']);
       $pca_strategy_obj=$builder->get();

    
        if($pca_strategy_obj->getNumRows() > 0){
          $objective_id = array_column($pca_strategy_obj->getResultArray(),'pca_strategy_objective_id');
          $objective_name = array_column($pca_strategy_obj->getResultArray(),'pca_strategy_objective_name');
    
          $objectives = array_combine($objective_id, $objective_name);
        }
    
        return $objectives;
      }

       /**
     * getBudgetLimitRemainingAmount
     * @param int $budget_id, int $expense_account_id
     * @return array
     * @access public
     * @author: Livingstone Onduso
     * @Date: 4/12/2022
     */
      public function getBudgetLimitRemainingAmount(int $budget_id, int $expense_account_id)
      {
          $builder = $this->read_db->table('expense_account');

          $builder->where('expense_account_id', $expense_account_id);

          $income_account_id =$builder->get()->getRow()->fk_income_account_id;
         
          $budgetLimitLib=new \App\Libraries\Grants\BudgetLimitLibrary();

          return $budgetLimitLib->budgetLimitRemainingAmount($budget_id, $income_account_id);
      }

   
}