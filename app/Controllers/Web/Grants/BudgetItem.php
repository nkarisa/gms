<?php

namespace App\Controllers\Web\Grants;

use App\Controllers\Web\WebController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Libraries\Grants;
use Config\App;

class BudgetItem extends WebController
{

    protected $budgetItemLib;
    protected $monthLib;
    protected $libStatus;

    function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->budgetItemLib = new Grants\BudgetItemLibrary();

        $this->libStatus = new  \App\Libraries\Core\StatusLibrary();
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

    function getBudgetLimitRemainingAmount($budget_id, $expense_account_id)
    {


        echo $this->budgetItemLib->getBudgetLimitRemainingAmount($budget_id, $expense_account_id);
    }


    function insertBudgetItem()
    {

        $post = $this->request->getPost();

        $this->write_db->transStart();

        if (isset($this->session->system_settings['use_pca_objectives']) && $this->session->system_settings['use_pca_objectives']) {
            $strategy = [];


            $strategy_obj = $this->read_db->table('pca_strategy')
                ->select(['pca_strategy_objective_id', 'pca_strategy_objective_name', 'pca_strategy_intervention_id', 'pca_strategy_intervention_name'])
                ->where('pca_strategy_intervention_id', $this->request->getPost('pca_intervention'))->get();

            if ($strategy_obj->getNumRows() > 0) {
                $strategy = $strategy_obj->getRowArray(); // Fetch a single row as an associative array
            }



            if (!empty($strategy)) {
                $objective_array = [
                    'pca_strategy_objective_id' => $this->request->getPost('pca_objective'),
                    'pca_strategy_objective_name' => $strategy['pca_strategy_objective_name'],
                    'pca_strategy_intervention_id' => $this->request->getPost('pca_intervention'),
                    'pca_strategy_intervention_name' => $strategy['pca_strategy_intervention_name']
                ];

                $header['budget_item_objective'] = $this->response->setJSON($objective_array);
            }
        }

        $header['budget_item_track_number'] = $this->libStatus->generateItemTrackNumberAndName('budget_item')['budget_item_track_number'];
        $header['budget_item_name'] = $this->libStatus->generateItemTrackNumberAndName('budget_item')['budget_item_name'];

        $header['budget_item_total_cost'] = (float)$this->request->getPost('budget_item_total_cost'); //$post['budget_item_total_cost'];
        $header['fk_budget_id'] = $this->request->getPost('fk_budget_id'); //$post['fk_budget_id'];
        $header['fk_expense_account_id'] = $this->request->getPost('fk_expense_account_id');
        $header['budget_item_description'] = $this->request->getPost('budget_item_description');
        $header['fk_project_allocation_id'] = $this->request->getPost('fk_project_allocation_id');

        $header['budget_item_quantity'] = (float)$this->request->getPost('budget_item_quantity');
        $header['budget_item_unit_cost'] = (float)$this->request->getPost('budget_item_unit_cost');
        $header['budget_item_often'] = (float)$this->request->getPost('budget_item_often');

        $header['budget_item_created_by'] = $this->session->user_id;
        $header['budget_item_last_modified_by'] = $this->session->user_id;
        $header['budget_item_created_date'] = date('Y-m-d');

        $approvalLibrary = new \App\Libraries\Core\ApprovalLibrary();
        $statusLibrary = new \App\Libraries\Core\StatusLibrary();

        $header['fk_approval_id'] = $approvalLibrary->insertApprovalRecord('budget_item');
        $header['fk_status_id'] = $statusLibrary->initialItemStatus('budget_item');

        $this->write_db->table('budget_item')->insert($header);
        $header_id = $this->write_db->insertID();

        $row = [];

        foreach ($this->request->getPost('fk_month_id') as $month_id => $month_amount) {

            if ($month_amount > 0) {
                $body['budget_item_detail_track_number'] = $this->libStatus->generateItemTrackNumberAndName('budget_item_detail')['budget_item_detail_track_number'];
                $body['budget_item_detail_name'] = $this->libStatus->generateItemTrackNumberAndName('budget_item_detail')['budget_item_detail_name'];
                $body['fk_budget_item_id'] = $header_id;

                $body['budget_item_detail_amount'] = (float)$month_amount;
                $body['fk_month_id'] = $month_id;

                $body['budget_item_detail_created_by'] = $this->session->user_id;
                $body['budget_item_detail_last_modified_by'] = $this->session->user_id;
                $body['budget_item_detail_created_date'] = date('Y-m-d');

                $body['fk_approval_id'] = $approvalLibrary->insertApprovalRecord('budget_item_detail');
                $body['fk_status_id'] = $statusLibrary->initialItemStatus('budget_item_detail');

                $row[] = $body;
            }
        }

        if (sizeof($row) > 0) {
            $this->write_db->table('budget_item_detail')->insertBatch($row);

            //JSONIFY and update the budget_item table

            $data['budget_item_details'] = json_encode($row);


            $builder = $this->write_db->table('budget_item');
            $builder->where('budget_item_id', $header_id);
            $builder->update($data);
        }


        $this->write_db->transComplete();

        if ($this->write_db->transStatus() === FALSE) {
            //echo json_encode($row);
            echo "Budget Item posting failed";
        } else {
            //echo json_encode($row);
            echo "Budget Item posted successfully";
        }
    }

    public function lastQtrMonthsToBeReviewed($budget_item_id, $source_budget_item_id, $budget_item_marked_for_review, $month_spread)
    {

        $last_qtr_months_to_be_reviewed = [];
        $current_budget = [];
        $past_budget = [];


        //Previous budget
        $past_budget_obj = $this->read_db->table('budget')
            ->select(['fk_office_id as office_id', 'budget_tag_level', 'fk_custom_financial_year_id'])
            ->join('budget_tag', 'budget_tag.budget_tag_id = budget.fk_budget_tag_id')
            ->join('budget_item', 'budget_item.fk_budget_id = budget.budget_id')
            ->where('budget_item.budget_item_id', $source_budget_item_id)
            ->get();

        //Current budget
        $current_budget_obj = $this->read_db->table('budget')
            ->select(['fk_office_id as office_id', 'budget_tag_level', 'fk_custom_financial_year_id'])
            ->join('budget_tag', 'budget_tag.budget_tag_id = budget.fk_budget_tag_id')
            ->join('budget_item', 'budget_item.fk_budget_id = budget.budget_id')
            ->where('budget_item.budget_item_id', $budget_item_id)
            ->get();


        if ($current_budget_obj->getNumRows() > 0 && $past_budget_obj->getNumRows() > 0) {
            $current_budget = $current_budget_obj->getRowArray();
            $past_budget = $past_budget_obj->getRowArray();

            $monthLib = new \App\Libraries\Core\MonthLibrary();

            $past_months_in_fy = $monthLib->pastMonthsInFy($current_budget['office_id'], $current_budget['budget_tag_level']);

            // Check if the custom fy is the same for both the previous and current budget ?????? If not $last_qtr_months_to_be_reviewed is empty
            $pastAndCurrentBudgetHasSameFYSetting = $past_budget['fk_custom_financial_year_id'] == $current_budget['fk_custom_financial_year_id'] ? true : false;

            // Check if these months have registered any change ???? If not $last_qtr_months_to_be_reviewed is empty
            $last_qtr_months_to_be_reviewed = $budget_item_marked_for_review ? array_slice($past_months_in_fy, -3) : [];

            if ($budget_item_marked_for_review && $pastAndCurrentBudgetHasSameFYSetting) {
                $last_qtr_months_to_be_reviewed = array_slice($past_months_in_fy, -3);

                // log_message('error', json_encode([$budget_item_id, $source_budget_item_id, $last_qtr_months_to_be_reviewed]));

                $past_months_has_changes = $this->pastMonthsHasChanges($source_budget_item_id, $last_qtr_months_to_be_reviewed, $month_spread);

                if (!$past_months_has_changes) {
                    $last_qtr_months_to_be_reviewed = [];
                }
            }
        }

        return $last_qtr_months_to_be_reviewed;
    }

    public function pastMonthsHasChanges($source_budget_item_id, $last_qtr_months_to_be_reviewed, $month_spread)
    {

        $past_months_has_changes = false;
        $sum_past = 0;
        $sum_current = 0;

        $current_sum_obj = $this->read_db->table('budget_item_detail')
            ->select('budget_item_detail_amount')
            ->whereIn('fk_month_id', $last_qtr_months_to_be_reviewed)
            ->where('fk_budget_item_id', $source_budget_item_id)
            ->get();

        if ($current_sum_obj->getNumRows() > 0) {
            $details = $current_sum_obj->getResultArray();
            foreach ($details as $detail) {
                $sum_past += $detail['budget_item_detail_amount'];
            }
        }

        foreach ($month_spread as $month_id => $amount) {
            if (in_array($month_id, $last_qtr_months_to_be_reviewed)) {
                $sum_current += $amount;
            }
        }

        if ($sum_past != $sum_current) {
            $past_months_has_changes = true;
        }

        return $past_months_has_changes;
    }

    public function updateBudgetItem($budget_item_id)
    {

        $post = $this->request->getPost();

        // log_message('error', json_encode($post));
        // return false;
        //Load config using service
        $review_last_quarter_after_mark_for_review=$this->config->review_last_quarter_after_mark_for_review;
        
        //$review_last_quarter_after_mark_for_review = service("settings")->get("GrantsConfig.review_last_quarter_after_mark_for_review");

        $source_budget_item_id = $post['source_budget_item_id'];
        $budget_item_marked_for_review = $post['budget_item_marked_for_review'];


        // last_qtr_months_to_be_reviewed gives the months of the last qtr if changes were made in this period during the budget review
        $last_qtr_months_to_be_reviewed = $this->lastQtrMonthsToBeReviewed($budget_item_id, $source_budget_item_id, $budget_item_marked_for_review, $post['fk_month_id']);



        $this->write_db->transStart();

        $header = [];

        if (isset($this->session->system_settings['use_pca_objectives']) && $this->session->system_settings['use_pca_objectives']) {
            $strategy = [];

            $strategy_obj = $this->read_db->table('pca_strategy')
                ->select(['pca_strategy_objective_id', 'pca_strategy_objective_name', 'pca_strategy_intervention_id', 'pca_strategy_intervention_name'])
                ->where('pca_strategy_intervention_id', $post['pca_intervention'])
                ->get();



            if ($strategy_obj->getNumRows() > 0) {
                $strategy = $strategy_obj->getRowArray();
            }

            if (!empty($strategy)) {
                $objective_array = [
                    'pca_strategy_objective_id' => $post['pca_objective'],
                    'pca_strategy_objective_name' => $strategy['pca_strategy_objective_name'],
                    'pca_strategy_intervention_id' => $post['pca_intervention'],
                    'pca_strategy_intervention_name' => $strategy['pca_strategy_intervention_name']
                ];

                $header['budget_item_objective'] = json_encode($objective_array);
            }
        }

        // Update budget item record
        /**
         * {"budget_item_description":"Secondary School fees for 2020",
         * "fk_expense_account_id":"1",
         * "fk_month_id":{"7":["2500000.00"],"8":["0.00"],"9":["0.00"],
         * "10":["0.00"],"11":["0.00"],"12":["0.00"],"1":["2000000.00"],"2":["0.00"],
         * "3":["0.00"],"4":["0.00"],"5":["0.00"],"6":["0.00"]},"budget_item_total_cost":"4500000",
         * "fk_budget_id":"1"}
         */

        $current_budget_item = $this->getBudgetItemById($budget_item_id);

        $header['fk_expense_account_id'] = $post['fk_expense_account_id'];

        $header['budget_item_quantity'] = $post['budget_item_quantity'];
        $header['budget_item_unit_cost'] = $post['budget_item_unit_cost'];
        $header['budget_item_often'] = $post['budget_item_often'];

        $header['budget_item_total_cost'] = $post['budget_item_total_cost'];
        $header['budget_item_description'] = $post['budget_item_description'];


        $this->createChangeHistory($header, false, $current_budget_item);

        $is_new_budget_item_status = $this->isNewBudgetItemStatus($current_budget_item['budget_item_status_id'], $budget_item_marked_for_review);



        if (!$is_new_budget_item_status) {

            $this->createRevisions($budget_item_id, $post);
        }



        if (!empty($last_qtr_months_to_be_reviewed) && $review_last_quarter_after_mark_for_review) {
            $this->createRevisions($source_budget_item_id, $post);
        }
        //log_message('error', json_encode($is_new_budget_item_status));

        /*Update budget_item  column 'budget_item_details' with JSON data the will replace the insertion of 
        the above records in budget_item_details table sooner than later*/

        $row = [];

        $approvalLibrary = new \App\Libraries\Core\ApprovalLibrary();
        $statusLibrary = new \App\Libraries\Core\StatusLibrary();

        foreach ($this->request->getPost('fk_month_id') as $month_id => $month_amount) {
            if ($month_amount > 0) {
                $data['budget_item_detail_track_number'] = $this->libStatus->generateItemTrackNumberAndName('budget_item_detail')['budget_item_detail_track_number'];
                $data['budget_item_detail_name'] = $this->libStatus->generateItemTrackNumberAndName('budget_item_detail')['budget_item_detail_name'];
                $data['fk_budget_item_id'] = $budget_item_id;

                $data['budget_item_detail_amount'] = $month_amount;
                $data['fk_month_id'] = $month_id;

                $data['budget_item_detail_created_by'] = $this->session->user_id;
                $data['budget_item_detail_last_modified_by'] = $this->session->user_id;
                $data['budget_item_detail_created_date'] = date('Y-m-d');


                $data['fk_approval_id'] = $approvalLibrary->insertApprovalRecord('budget_item_detail');

                $data['fk_status_id'] = $statusLibrary->initialItemStatus('budget_item_detail');

                $row[] = $data;
            }
        }

        $header['budget_item_details'] = json_encode($row);

        $writer_builder=$this->write_db->table('budget_item');
        $writer_builder->where(array('budget_item_id' => $budget_item_id));
        $writer_builder->update($header);

        // Update budget item detail for current review

        foreach ($this->request->getPost('fk_month_id') as $month_id => $month_amount) {
            $this->upsertBudgetItemDetail($budget_item_id, $month_id, $month_amount);
        }

        // Update budget item detail for recent past qtr review

        if (!empty($last_qtr_months_to_be_reviewed) && $review_last_quarter_after_mark_for_review) {

            $past_header['budget_item_quantity'] = $this->request->getPost('budget_item_quantity');
            $past_header['budget_item_unit_cost'] = $this->request->getPost('budget_item_unit_cost');
            $past_header['budget_item_often'] = $this->request->getPost('budget_item_often');

            $this->write_db->where(array('budget_item_id' => $source_budget_item_id));
            $this->write_db->update('budget_item', $past_header);

            foreach ($post['fk_month_id'] as $month_id => $month_amount) {
                $this->upsertBudgetItemDetail($source_budget_item_id, $month_id, $month_amount);
            }
        }



        $this->write_db->transComplete();

        if ($this->write_db->transStatus() === FALSE) {
            echo "Budget Item Update failed";
        } else {
            echo "Budget Item Updated successfully";
        }
    }

    private function upsertBudgetItemDetail($budget_item_id, $month_id, $month_amount)
    {

        $builder = $this->read_db->table('budget_item_detail');
        // Check if the budget item detail exists
        $cond = array('fk_budget_item_id' => $budget_item_id, 'fk_month_id' => $month_id);

        $builder->where($cond);
        $budget_item_detail_obj = $builder->get();


        if ($budget_item_detail_obj->getNumRows() > 0) {

            $body['budget_item_detail_amount'] = $month_amount;

            $builder_update = $this->write_db->table('budget_item_detail');

            $builder_update->where($cond);

            $builder_update->update($body);
        } elseif ($month_amount > 0) {
            $track = $this->libStatus->generateItemTrackNumberAndName('budget_item_detail');

            $data_insert['budget_item_detail_track_number'] = $track['budget_item_detail_track_number'];
            $data_insert['budget_item_detail_name'] = $track['budget_item_detail_name'];
            $data_insert['fk_budget_item_id'] = $budget_item_id;
            $data_insert['fk_month_id'] = $month_id;
            $data_insert['fk_status_id'] = $this->libStatus->initialItemStatus('budget_item_detail');
            $data_insert['budget_item_detail_amount'] = $month_amount;
            $data_insert['budget_item_detail_created_date'] = date('Y-m-d');
            $data_insert['budget_item_detail_created_by'] = $this->session->user_id;
            $data_insert['budget_item_detail_last_modified_by'] = $this->session->user_id;

            $this->write_db->table('budget_item_detail')->insert($data_insert);
        }
    }

    function createRevisions($budget_item_id, $new_data)
    {

        $update_data = [];
        $old_data = [];

        $builder = $this->read_db->table('budget_item');
        // Old data
        $builder->select(array(
            'budget_item_id',
            'budget_item_total_cost',
            'fk_expense_account_id',
            'budget_item_description',
            'budget_item_quantity',
            'budget_item_unit_cost',
            'budget_item_often',
            'budget_item_marked_for_review',
            'budget_item.fk_status_id as status_id',
            'budget_item_source_id',
            'fk_project_allocation_id',
            'fk_month_id',
            'budget_item_detail_amount',
            'fk_budget_id',
            'budget_item_source_id',
            'budget_item_revisions'
        ));

        $builder->where(array('budget_item_id' => $budget_item_id));
        $builder->join('budget_item_detail', 'budget_item_detail.fk_budget_item_id=budget_item.budget_item_id');
        $budget_item_obj = $builder->get();

        if ($budget_item_obj->getNumRows() > 0) {
            $old_data_array = $budget_item_obj->getResultArray();

            foreach ($old_data_array as $row) {
                $old_data[$row['budget_item_id']]['budget_item_revisions'] = $row['budget_item_revisions'];
                $old_data[$row['budget_item_id']]['budget_item_marked_for_review'] = $row['budget_item_marked_for_review'];
                $old_data[$row['budget_item_id']]['budget_item_source_id'] = $row['budget_item_source_id'];
                $old_data[$row['budget_item_id']]['fk_budget_id'] = $row['fk_budget_id'];
                $old_data[$row['budget_item_id']]['fk_expense_account_id'] = $row['fk_expense_account_id'];
                $old_data[$row['budget_item_id']]['budget_item_description'] = $row['budget_item_description'];
                $old_data[$row['budget_item_id']]['budget_item_quantity'] = $row['budget_item_quantity'];
                $old_data[$row['budget_item_id']]['budget_item_unit_cost'] = $row['budget_item_unit_cost'];
                $old_data[$row['budget_item_id']]['budget_item_total_cost'] = $row['budget_item_total_cost'];
                $old_data[$row['budget_item_id']]['budget_item_often'] = $row['budget_item_often'];
                $old_data[$row['budget_item_id']]['fk_month_id'][$row['fk_month_id']] = $row['budget_item_detail_amount'];
            }
        }

        $revision_data = $this->prepareRevisionData($old_data[$budget_item_id], $new_data);

        $builder_update = $this->write_db->table('budget_item');
        $builder_update->where(array('budget_item_id' => $budget_item_id));
        $update_data['budget_item_revisions'] = json_encode($revision_data);
        $builder_update->update($update_data);
    }

    function prepareRevisionData($original_data, $new_data)
    {
        $revision_data = [];
        $revision_number = 1;
        $budget_item_revisions = [];

        //Check if there is a existing revisions for the budget item
        if ($original_data['budget_item_revisions'] != NULL && $original_data['budget_item_revisions'] != "" && $original_data['budget_item_revisions'] != '[]') {
            $budget_item_revisions = !is_null($original_data['budget_item_revisions']) ? json_decode($original_data['budget_item_revisions'], true) : [];
            $revision_numbers = array_column($budget_item_revisions, 'revision_number');
            sort($revision_numbers);
            $last_revision_number = end($revision_numbers);

            $revision_number = $last_revision_number + 1;
        }

        $build_new_revisions = [];
        $original = $this->revisionData($original_data); // Preset original data for new unlocked original data

        // Remove the last unlocked revision - There can only be one unlocked revision per budget item object
        if (!empty($budget_item_revisions)) {
            foreach ($budget_item_revisions as $budget_item_revision) {
                if (array_key_exists('locked', $budget_item_revision) && $budget_item_revision['locked'] == false) {
                    $revision_number = $budget_item_revision['revision_number']; // Prevent replacing revision number when updating unlocked revision
                    $original = $budget_item_revision['data']['original']; // Prevent replacing original data in unlocked revision
                    continue;
                }

                array_push($build_new_revisions, (object)$budget_item_revision);
            }
        }


        $revision_data['revision_number'] = $revision_number;
        $revision_data['revision_date'] = date('Y-m-d h:i:s');
        $revision_data['locked'] = false;

        // $original = $this->revision_data($original_data);
        $revised = $this->revisionData($new_data);

        $revision_data['data']['original'] = $original;
        $revision_data['data']['revised']  = $revised;

        $month_spread_different = $this->areArraysDifferent($original['month_spread'], $revised['month_spread']);

        // Prevent updating revisions if no change in spread was made. Change in description, unit cost, quantity and often if not affecting
        // spread will not trigger revision update.

        if ($month_spread_different) {
            array_push($build_new_revisions, $revision_data);
        }

        return $build_new_revisions;
    }

    private function areArraysDifferent($array1, $array2)
    {

        $this->arrayFillKeys($array1);
        $this->arrayFillKeys($array2);

        if (count($array1) != count($array2)) {
            return true; // If arrays have different lengths, they are different.
        }

        foreach ($array1 as $key => $value) {
            if ($value !== $array2[$key]) {
                return true; // If any element is different, the arrays are different.
            }
        }

        return false; // If the arrays have the same elements, they are not different.
    }

    private function arrayFillKeys(&$array): array
    {
        if (sizeof($array) != 12) {
            for ($i = 1; $i < 13; $i++) {
                if (!array_key_exists($i, $array)) {
                    $array[$i] = 0;
                }
            }
        }

        return $array;
    }

    private function revisionData($data)
    {
        $revision_data['budget_item_description'] = $data["budget_item_description"];
        $revision_data['budget_item_quantity'] = $data["budget_item_quantity"];
        $revision_data['budget_item_unit_cost'] = $data["budget_item_unit_cost"];
        $revision_data['budget_item_often'] = $data["budget_item_often"];
        $revision_data['budget_item_total_cost'] = $data["budget_item_total_cost"];

        $amounts = [];
        foreach ($data['fk_month_id'] as $month_id => $amount) {
            if ($amount > 0) {
                $amounts[$month_id] = $amount;
            }
        }

        $revision_data['month_spread'] = $amounts;

        return $revision_data;
    }

    function isNewBudgetItemStatus($budget_item_status_id, $budget_item_marked_for_review)
    {

        $is_new_budget_item_status = false;

        $initial_item_status = $this->libStatus->initialItemStatus('budget_item');

        if (!$budget_item_marked_for_review && $initial_item_status == $budget_item_status_id) {
            $is_new_budget_item_status = true;
        }

        return $is_new_budget_item_status;
    }

    /**
     * get_budget_item_by_id
     * 
     * Get a budget item by its item Id
     */
    public function getBudgetItemById($budget_item_id)
    {
        $budget_item = [];

        $budget_item_obj = $this->read_db->table('budget_item')->select([
            'budget_item.fk_status_id as budget_item_status_id',
            'fk_expense_account_id',
            'budget_item_quantity',
            'budget_item_unit_cost',
            'budget_item_often',
            'budget_item_total_cost',
            'budget_item_description'
        ])->where('budget_item_id', $budget_item_id)->get();

        if ($budget_item_obj->getNumRows() > 0) {
            $budget_item = $budget_item_obj->getRowArray();
        }

        return $budget_item;
    }


    /**
     * markForReview(): This method marks a budget item for review
     * @author Livingstone Onduso
     * @access public
     * @return void
     * @param int $mark, float $budget_item_id
     */

    public function markForReview(int $mark, float $budget_item_id): void
    {
        $alt_mark = 0;

        if ($mark == 0) {
            $alt_mark = 1;
        }

        $builder = $this->write_db->table('budget_item');

        $builder->where(array('budget_item_id' => $budget_item_id));
        $update_data['budget_item_marked_for_review'] = $alt_mark;
        $builder->update($update_data);

        if ($this->write_db->affectedRows() > 0) {
            $new_data = ['budget_item_id' => $budget_item_id, 'budget_item_marked_for_review' => $alt_mark];
            //$old_data = ['budget_item_id' => $budget_item_id, 'marked_for_review' => $mark];
            parent::createChangeHistory($new_data, false, [], 'budget_item', 0);
            echo  $alt_mark;
        } else {
            echo $mark;
        }
    }

    /**
     * getBudgetItemNotesHistory(): This method marks a budget item for review
     * @author Livingstone Onduso
     * @access public
     * @return void
     * @param int $mark, $budget_item_id
     */
    public function getBudgetItemNotesHistory(int $budget_item_id)
    {
        $messageLib=new \App\Libraries\Core\MessageLibrary();

        $notes=$messageLib->notesHistory($budget_item_id);

        echo json_encode($notes);
    
    }

     /**
     * postBudgetItemNote(): This method posts types budget item notes in the db table
     * @author Livingstone Onduso
     * @access public
     * @return void
     * @param int $mark, $budget_item_id
     */
    public function postBudgetItemNote(){

        $post = $this->request->getPost();
    
        extract($post);

        $messageLib=new \App\Libraries\Core\MessageLibrary();
    
        
        $response = 0;
        
        if($post['update']['message_detail_id'] == ""){
            $messageLib->postNewMessage('budget_item',$budget_item_id,$note);
        }else{
          $messageLib->updateMessage($post['update']['message_detail_id'],$note);
        }
    
        echo $response;
    
      }

    /**
     * deleteNote(): This method deletes existing messages/notes
     * @author Livingstone Onduso
     * @access public
     * @return  void
     */
    public function deleteNote(){
  
        $messageLib=new \App\Libraries\Core\MessageLibrary();

        $response=$messageLib->deleteNote();
  
        if((int)$response ==1){
          echo get_phrase('deletion_successful', 'Message Deleted.');
        }else{
          echo get_phrase('deletion_failed', 'Deletion Failed.');
        }
      }
}
