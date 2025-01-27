<?php

namespace App\Controllers\Web\Grants;

use App\Controllers\Web\WebController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Libraries\Grants;

class BudgetItem extends WebController
{

    protected $budgetItemLib;
    protected $monthLib;

    function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->budgetItemLib = new Grants\BudgetItemLibrary();
    }


    public function result($id = "", $parentTable = null)
    {

        $monthLib = new \App\Libraries\Core\MonthLibrary();
        $budgetLimitLib = new \App\Libraries\Grants\BudgetLimitLibrary();
        if ($this->action == 'multiFormAdd' || $this->action == 'edit') {

            $result = [];
            $expense_accounts = [];
            $project_allocations = [];
            $income_account = [];
            $budget_limit_amount = 0;

            $builder = $this->read_db->table('office');
            $builder->select([
                'office_id',
                'office_name',
                'office_code',
                'budget_year',
                'office.fk_account_system_id as account_system_id',
                'budget_tag_level'
            ]);
            $builder->join('budget', 'budget.fk_office_id = office.office_id');
            $builder->join('budget_tag', 'budget_tag.budget_tag_id = budget.fk_budget_tag_id');
            $id = $this->request->getUri()->getSegment(3);
            if ($this->action == 'multiFormAdd') {

                
                $budget_id = hash_id($id, 'decode');
                $builder->where(array('budget_id' => $budget_id));
            } else {
                $builder->join('budget_item', 'budget_item.fk_budget_id=budget.budget_id');

                $builder->where(array('budget_item_id' => hash_id($id, 'decode')));
            }

            $office = $builder->get()->getRow();

            if ($this->action == 'edit') {
                //getBudgetIdByBudgetItemId
                $budget_id = $this->budgetItemLib->getBudgetIdByBudgetItemId(hash_id($id, 'decode'));
            }

            $pca_objectives =  $this->budgetItemLib->getOfficeYearPcaObjectives($office->office_id, $budget_id);

            $months = []; // month_order($office->office_id, $budget_id);

            // Get project allocations
            $budgeting_date = date('Y-m-d');
            $query_condition = "fk_office_id = " . $office->office_id . " AND ((project_end_date >= '" . $budgeting_date . "' OR project_end_date LIKE '0000-00-00' OR project_end_date IS NULL) OR  project_allocation_extended_end_date >= '" . $budgeting_date . "')";

            $builder_projects = $this->read_db->table('project_allocation');
            $builder_projects->select(['project_allocation_id', 'project_allocation_name', 'project_name']);
            $builder_projects->join('project', 'project.project_id = project_allocation.fk_project_id');
            $builder_projects->join('project_income_account', 'project_income_account.fk_project_id = project.project_id');
            $builder_projects->join('income_account', 'income_account.income_account_id = project_income_account.fk_income_account_id');
            $builder_projects->where($query_condition);
            $builder_projects->where('income_account_is_budgeted', 1);
            $project_allocations_with_duplicates_obj = $builder_projects->get();

            $project_allocations = $project_allocations_with_duplicates_obj->getResultArray();

            $project_allocations = [];

            if ($project_allocations_with_duplicates_obj->getNumRows() > 0) {

                $project_allocations_with_duplicates = $project_allocations_with_duplicates_obj->getResultObject();

                foreach ($project_allocations_with_duplicates as  $project_allocation) {
                    $project_allocations[$project_allocation->project_allocation_id] = $project_allocation;
                }
            }

            $months_to_freeze = $monthLib->pastMonthsInFy($office->office_id, $office->budget_tag_level);

            if ($this->action == 'edit') {

                // Get Income Account
                $builder_income = $this->read_db->table('income_account');
                $builder_income->join('expense_account', 'expense_account.fk_income_account_id = income_account.income_account_id');
                $builder_income->join('budget_item', 'budget_item.fk_expense_account_id = expense_account.expense_account_id');
                $builder_income->where('budget_item_id', hash_id($id, 'decode'));
                
                $income_account = $builder_income->get()->getRow();

                // Get Expense Accounts
                $builder_expense = $this->read_db->table('expense_account');
                $builder_expense->select(['expense_account_id', 'expense_account_name', 'expense_account_code']);
                $builder_expense->join('income_account', 'income_account.income_account_id = expense_account.fk_income_account_id');
                $builder_expense->join('account_system', 'account_system.account_system_id = income_account.fk_account_system_id');
                $builder_expense->where('fk_income_account_id', $income_account->income_account_id);
                $builder_expense->where('fk_account_system_id', $office->account_system_id);
                $builder_expense->where('expense_account_is_active', 1);
                //$builder_expense->get();
                $expense_accounts = $builder_expense->get()->getResultObject();


                // Get Budget Items
                $builder_budget_item = $this->read_db->table('budget_item');
                $builder_budget_item->join('expense_account', 'expense_account.expense_account_id = budget_item.fk_expense_account_id');
                $builder_budget_item->where('budget_item_id', hash_id($this->id, 'decode'));
                //$builder_budget_item->get();
                $budget_item = $builder_budget_item->get()->getRow();


                $budget_limit_amount = $budgetLimitLib->budgetLimitRemainingAmount($budget_item->fk_budget_id, $budget_item->fk_income_account_id);

                

                $builder_budget_item = $this->read_db->table('budget_item_detail');
                $builder_budget_item->select(array(
                    'budget_item_detail_id',
                    'month_id',
                    'month_number',
                    'budget_item_detail_amount',
                    'budget_item_id',
                    'fk_budget_id as budget_id',
                    'budget_item_total_cost',
                    'expense_account_id',
                    'budget_item_description',
                    'budget_item_quantity',
                    'budget_item_unit_cost',
                    'budget_item_often',
                    'budget_item_marked_for_review',
                    'budget_item_source_id',
                    'budget_item_revisions',
                    'fk_project_allocation_id',
                    'expense_account_id',
                    'expense_account_name',
                    'expense_account_code',
                    'budget_item.fk_status_id as status_id',
                    'budget_item_objective as objective'
                ));
                $builder_budget_item->join('budget_item', 'budget_item.budget_item_id = budget_item_detail.fk_budget_item_id');
                $builder_budget_item->join('expense_account', 'expense_account.expense_account_id = budget_item.fk_expense_account_id');
                $builder_budget_item->join('month', 'month.month_id = budget_item_detail.fk_month_id');
                $builder_budget_item->where('budget_item_id', hash_id($id, 'decode'));
                //$builder_budget_item->get();
                $budget_item_details = $builder_budget_item->get()->getResultArray();

                $result['budget_item_details'] = [];

                foreach ($budget_item_details as $budget_item_detail) {
                    $budget_item_detail['objective'] = !is_null($budget_item_detail['objective']) ? json_decode($budget_item_detail['objective']) : [];
                    $result['budget_item_details'][$budget_item_detail['month_number']] = $budget_item_detail;
                }

                $result['current_expense_account_id'] = $income_account->fk_expense_account_id;

                $result['interventions'] = $budget_item_details[0]['objective'] != null ? $this->budgetItemLib->getObjectiveInterventions(json_decode($budget_item_details[0]['objective'])->pca_strategy_objective_id) : [];

                $months = month_order($office->office_id, $budget_item->fk_budget_id);
                $months_to_freeze = $monthLib->pastMonthsInFy($office->office_id, $office->budget_tag_level, true);
            } else {
                $months = month_order($office->office_id, $budget_id);
            }

            $result['project_allocations'] = $project_allocations;
            $result['expense_accounts'] = $expense_accounts;
            $result['months'] = $months;
            $result['office'] = $office;
            $result['budget_limit_amount'] = $budget_limit_amount;
            $result['pca_objectives'] = $pca_objectives;
            $result['months_to_freeze'] = $months_to_freeze;

            //Added by Onduso to resolve the bug of freezing all months when the FY is beginning and during editing
            // $result['count_of_fys_occurence_and_pick_last_value'] = $this->month_model->count_fys_occurances($office->office_id);

            return $result;
        } else {

            $result = parent::result($id, $parentTable);

            return $result;
        }
    }

    public function projectBudgetableExpenseAccounts(int $project_allocation_id)
    {

        //log_message('error',json_encode($project_allocation_id));

        // ajax/budgetItem/projectBudgetableExpenseAccounts/11824'
        $accounts = $this->budgetItemLib->projectBudgetableExpenseAccounts($project_allocation_id);

        echo json_encode($accounts);
    }

    public function budgetLimitRemainingAmount($budget_id, $expense_account_id)
    {
        $budgetLimitLib = new \App\Libraries\Grants\BudgetLimitLibrary();

        echo $budgetLimitLib->budgetLimitRemainingAmount($budget_id, $expense_account_id);
    }
}
