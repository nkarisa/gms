<?php

namespace App\Controllers\Web\Grants;

use App\Controllers\Web\WebController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Libraries\Grants;
use App\Libraries\Core\StatusLibrary;
use App\Libraries\Grants\IncomeAccountLibrary;
use App\Traits\System\SetupTrait;
use App\Traits\System\ApprovalTrait;
class Budget extends WebController
{
  use SetupTrait;
  use ApprovalTrait;
  protected $budgetLib;
  protected $statusLib;
  protected $incomeAccountLib;

  function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
  {
    parent::initController($request, $response, $logger);

    $this->budgetLib = new Grants\BudgetLibrary();

    $this->statusLib=new StatusLibrary();

    $this->incomeAccountLib=new IncomeAccountLibrary();

    //$this->action='multiFormAdd';
  }

  // public function result($id = '', $parentTable = null){

  //   $result=parent::result($id, $parentTable);

  //   if($this->action=='multiFormAdd'){
  //      $result['fields']=[];
  //   }
   

  //   return $result;
    
  // }
  function checkOfficePeriodBudgetExists($office_id)
  {

    //$budgetLibrary = new \App\Libraries\Grants\BudgetLibrary();
    $budget = $this->budgetLib->getBudgetByOfficeCurrentTransactionDate($office_id);

    $check = false;

    if (count($budget) > 0) {
      $check = true;
    }

    return $this->response->setJSON(compact('check'));
  }

  function page_name(): String
  {

    $segment_budget_view_type = parent::page_name();

    $segments = $this->request->getUri()->getSegments();

    // log_message('error', json_encode($segments));

    if ($this->action == 'view') {

      //$segment_budget_view_type = $this->uri->getSegment(4, 'summary');

      //log_message('error', json_encode(["yes"=>$this->request->getUri()->getSegment(4)]));

      if ($this->request->getUri()->getSegment(4) =='schedule') {
       // log_message('error',json_encode($this->uri->getSegment(4)));
        $segment_budget_view_type = 'budget_schedule_view';
      } else {
        $segment_budget_view_type = 'budget_summary_view';
      }
    }

    //log_message("error", json_encode($segment_budget_view_type));
    return $segment_budget_view_type;
  }


  function result($id = "", $parentTable = null)
  {

    // $result = parent::result($id, $parentTable);
  
   // $segment_budget_view_type = $this->request->getUri()->getSegment(4, 'summary');

   $segment_budget_view_type="summary";

   //log_message('error', json_encode($this->request->getUri()->getTotalSegments()));

    if ($this->request->getUri()->getTotalSegments() ==5) {
      $segment_budget_view_type = $this->request->getUri()->getSegment(4);
  } 


    $budget_header = $this->budgetHeaderInformation();

    if ($this->action == 'view') {

    
      $customFy = new Grants\CustomFinancialYearLibrary();
      $financial_report = new Grants\FinancialReportLibrary();
      $statusId=$this->statusLib->initialItemStatus($this->controller);
      //Get office as per passed in budget_id
      $budget_id = hash_id($this->id, 'decode');

      $office = $financial_report->getOffice($budget_id);

      if ($segment_budget_view_type == 'summary') {

        $budget_limit_lib = new Grants\BudgetLimitLibrary();
        $strategic_objectives_lib = new Grants\StrategicObjectivesLibrary();
        //$statusLib=new \App\Libraries\Core\StatusLibrary();
        $userLib=new \App\Libraries\Core\UserLibrary();

       $result['initial_status']=$statusId;//$statusLib->initialItemStatus('budget');

       $result['user_has_create_budget_item_permission']=$userLib->checkRoleHasPermissions('budget_item', 'create');
       $result['user_has_create_budget_limit_permission']=$userLib->checkRoleHasPermissions('budget_limit','create');
       //$result['user_has_update_budget_limit_permission']=$userLib->checkRoleHasPermissions('Budget_limit', 'update');

        $budget_summary = $this->budgetSummaryResult();

        $result['result'] = array_merge($budget_header, $budget_summary);

        $result['budget_limits'] = $this->budgetLimits($budget_id);

       // log_message('error', json_encode($this->budgetLimits($budget_id)));
        
        $result['months'] = month_order($office->office_id, $budget_id);
        //$result['budget_limit_list_view'] = $budget_limit_lib->loadBudgetListView($budget_id);
        //$budgetLimitLib=new \App\Libraries\Grants\BudgetLimitLibrary();

        $result['budget_limit'] =  $budget_limit_lib->getBudgetLimitByBudgeId($budget_id);

        $result['strategic_objectives_costing_view'] =  $strategic_objectives_lib->loadStrategicObjectivesCostingView($budget_id);
      } else {
        $income_account_id = hash_id($this->request->getUri()->getSegment(5), 'decode');

        //log_message('error',json_encode($income_account_id));
        // $max_voucher_approval_ids = $this->general_model->get_max_approval_status_id('budget_item');

        $month_array = month_order($office->office_id, $budget_id);

        $month_numbers = array_column($month_array, 'month_number');
        $month_names = array_column($month_array, 'month_name');

        $budget_schedule['budget_status_id'] = $budget_header['status_id'];
        $budget_schedule['month_names_with_number_keys'] = array_combine($month_numbers, $month_names);
        $budget_schedule['budget_schedule'] = $this->budgetScheduleResult($income_account_id);
        $is_current_review['is_current_review'] = $this->checkIfCurrentReview();
        $is_last_budget_review['is_last_budget_review'] = $this->checkIfIsLastBudgetReview(hash_id($this->id, 'decode'), $office->office_id);

        
        // $budget_item_id_fully_approved['budget_item_id_fully_approved'] = max_voucher_approval_ids;

        // log_message('error', json_encode($status_data));
        $result['result'] = array_merge($budget_header, $budget_schedule, $is_current_review, $is_last_budget_review);
      }

      $result['budget_status_data'] = $this->libs->actionButtonData('budget', $office->account_system_id);
      $result['budget_item_status_data'] = $this->libs->actionButtonData('budget_item', $office->account_system_id);

      $custom_financial_year = $customFy->getDefaultCustomFinancialYearIdByOffice($office->office_id, true);

      $result['active_custom_fy'] = $custom_financial_year['custom_financial_year_is_active'];
      $result['all_mfrs_submitted'] =  $financial_report->allOfficeFinancialReportSubmitted($office->office_id);
      $result['is_budget_final_approval_status'] = $this->isBudgetFinalApprovalStatus($budget_id);
      $budget_has_custom_fy = $office->custom_financial_year_id;

      $result['action_button_disabled'] = false;
      $result['budget_message'] = '';

      if (
        $result['active_custom_fy'] &&
        !$result['all_mfrs_submitted'] &&
        !$result['is_budget_final_approval_status'] &&
        $budget_has_custom_fy
      ) {
        $result['action_button_disabled'] = true;
        $result['budget_message'] = get_phrase('all_mfrs_submitted_message', 'Make sure all financial reports are submitted in order to change the budget approval status or sign off the budget. This is due to newly activated custom financial year.');
      }

      $item_budget_approved = $this->budgetLib->checkIfAllBudgetItemsAreApproved($budget_id);

      if (!$item_budget_approved) {
        $result['action_button_disabled'] = true;
        $result['budget_message'] = get_phrase('all_budget_items_submitted_message', 'Make sure all budget items are submitted or reinstated before you sign off the budget.');
      }


      $result['is_declined_state'] = $this->isBudgetDeclinedState(hash_id($this->id, 'decode'));
      $result['status_data'] = $this->libs->actionButtonData('Budget', $office->account_system_id);
      $result['budget_status_id'] = $office->budget_status_id;
    }
     else{
      $result=parent::result($id, $parentTable);

      $columns=$this->budgetLib->singleFormAddVisibleColumns();

      $result['fields']=$this->addFormFields($columns);


    
     
    }
    
    return $result;
  }

  /**
   * add_form_fields
   * 
   * This method builds the add form (single form add or multi form add - master part) fields. 
   * It builds the columns names as keys anf the field html as the value in an associative array
   * 
   * @param $visible_columns_array Array : Columns to be selected
   * 
   * @return Array
   */
  private function addFormFields(array $visible_columns_array): array
  {

    $fields = array();

    //$detail_tables_visible_columns
    //print_r($visible_columns_array);exit;
    // exit;

    foreach ($visible_columns_array as $table_name => $column) { // Some table names can be 0, 1, 3 for single_form_add_visible_columns or defined names for detail_tables_single_form_add_visible_columns
      //if ($table_name !== 'status_role') continue;
      $field_value = '';
      $show_only_selected_value = false;

      if (!is_array($column)) {
        // Used to set the default select value in a single_form_add name fields if the form has been opened from a 
        // parent record


        if ($this->id != null  && hash_id($this->id, 'decode') > 0 && $column == $this->subAction . '_name') {
          $field_value = hash_id($this->id, 'decode');
          $show_only_selected_value = true;
        }


        $fields[$column] = $this->budgetLib->headerRowField($column, $field_value, $show_only_selected_value);
      } else {

        $detail_table = '';

        if (!is_numeric($table_name)) {
          $detail_table = $table_name;
        }

        foreach ($column as $detail_column) {
          $fields[$detail_column] = $this->budgetLib->headerFowField($detail_column, $field_value, $show_only_selected_value, $detail_table);
        }
      }
    }

    return $fields;
  }

  /**
   *isBudgetDeclinedState():This method checks if budgets exists which is fully approved.
   * @author Livingstone Onduso: Dated 06-05-2024
   * @access private
   * @return bool 
   * @param float $budget_id
   */

  private function isBudgetDeclinedState(float $budget_id): bool
  {
    // Get the status of the opened budget
    $builder = $this->read_db->table('status');
    $builder->where('budget_id', $budget_id);
    $builder->join('budget', 'budget.fk_status_id = status.status_id');

    // Execute the query and get the result
    $status_approval_direction = $builder->get()->getRow()->status_approval_direction;

    return $status_approval_direction == -1 ? true : false;
  }
  /**
   *isBudgetFinalApprovalStatus():This method checks if budgets exists which is fully approved.
   * @author Livingstone Onduso: Dated 06-05-2024
   * @access private
   * @return bool 
   * @param float $budget_id
   */
  private function isBudgetFinalApprovalStatus(float $budget_id): bool
  {

    $statusLibrary = new \App\Libraries\Core\StatusLibrary();
    $max_budget_approval_status_ids  = $statusLibrary->getMaxApprovalStatusId('budget');

    // Query Builder
    $builder = $this->read_db->table('budget');
    $builder->where('budget_id', $budget_id);
    $builder->whereIn('fk_status_id', $max_budget_approval_status_ids);

    // Count the rows
    $budget_count = $builder->countAllResults();

    return $budget_count > 0 ? true : false;
  }
  /**
   *checkIfIsLastBudgetReview():This method checks if exists the last budget review exists.
   * @author Livingstone Onduso: Dated 06-05-2024
   * @access private
   * @return bool 
   * @param float $budget_id
   */
  private function checkIfIsLastBudgetReview(float $budget_id, float $office_id): bool
  {

    $check = false;

    $builder = $this->read_db->table('budget');
    $builder->select('fk_budget_tag_id');
    $builder->where('budget_id', $budget_id);

    // Execute the query and fetch the result
    $budget_tag_id = $builder->get()->getRow()->fk_budget_tag_id;

    // Get all country budget tags in order
    // Step 1: Get the account system ID
    $builder = $this->read_db->table('office');
    $builder->select('fk_account_system_id');
    $builder->where('office_id', $office_id);
    $account_system_id = $builder->get()->getRow()->fk_account_system_id;

    // Step 2: Get the budget tags
    $builder = $this->read_db->table('budget_tag');
    $builder->select(['budget_tag_id', 'budget_tag_level']);
    $builder->where('fk_account_system_id', $account_system_id);
    $builder->orderBy('budget_tag_level', 'ASC');

    $budget_tag = $builder->get()->getResultArray();

    $last_budget_tag_id = end($budget_tag)['budget_tag_id'];

    if ($budget_tag_id == $last_budget_tag_id) {
      $check = true;
    }

    return $check;
  }


  /**
   *checkIfCurrentReview():This method checks if the budget review is current.
   * @author Livingstone Onduso: Dated 06-05-2024
   * @access private
   * @return bool 
   */
  private function checkIfCurrentReview(): bool
  {
    $this->read_db->reconnect();

    $budget_id = hash_id($this->id, 'decode');

    // Get office object for the budget
    $builder = $this->read_db->table('budget');
    $builder->select([
      'budget_year',
      'fk_office_id',
      'budget_tag_level',
      'fk_custom_financial_year_id'
    ]);
    $builder->join('budget_tag', 'budget_tag.budget_tag_id = budget.fk_budget_tag_id');
    $builder->where('budget_id', $budget_id);

    $budget_obj = $builder->get()->getRow();

    $fy = $budget_obj->budget_year;
    $office_id = $budget_obj->fk_office_id;
    $current_budget_tag_level = $budget_obj->budget_tag_level;
    $custom_financial_year_id = $budget_obj->fk_custom_financial_year_id;

    // Get all used budget tag levels for office and fy
    $builder = $this->read_db->table('budget');
    $builder->select('budget_tag_level');
    $builder->join('budget_tag', 'budget_tag.budget_tag_id = budget.fk_budget_tag_id');
    $builder->where([
      'fk_office_id' => $office_id,
      'fk_custom_financial_year_id' => $custom_financial_year_id,
      'budget_year' => $fy
    ]);
    $builder->orderBy('budget_tag_level', 'ASC');

    // Execute the query and fetch the result as an array
    $budget_tag_levels = $builder->get()->getResultArray();

    $budget_tag_levels_array = array_column($budget_tag_levels, 'budget_tag_level');

    $max_used_level = array_pop($budget_tag_levels_array);

    if ($current_budget_tag_level == $max_used_level) {
      return true;
    } else {
      return false;
    }
  }

  /**
   *budgetScheduleResult():This method returns array grid of records.
   * @author Livingstone Onduso: Dated 06-05-2024
   * @access private
   * @return array 
   * @param float $income_account_id
   */
  private function budgetScheduleResult(float $income_account_id): array
  {

    $budget_id = hash_id($this->id, 'decode');

    // Execute the stored procedure
    $query = $this->read_db->query("CALL get_budget_schedule_data(?, ?)", [$budget_id, $income_account_id]);

    // Fetch the results as an array of objects
    $budget_item_details = $query->getResultObject();

    $result_grid = [];
    $month_spread = [];

    foreach ($budget_item_details as $row) {
      $month_spread[$row->budget_item_id][$row->month_number] =
        [
          'month_id' => $row->month_id,
          'month_number' => $row->month_number,
          'month_name' => $row->month_name,
          'amount' => $row->budget_item_detail_amount
        ];
    }

    foreach ($budget_item_details as $row) {

      $result_grid[$row->income_account_id]['income_account'] = ['income_account_id' => $row->income_account_id, 'income_account_name' => $row->income_account_name, 'income_account_code' => $row->income_account_code];
      $result_grid[$row->income_account_id]['budget_items'][$row->expense_account_id]['expense_account'] = ['expense_account_id' => $row->expense_account_id, 'expense_account_name' => $row->expense_account_name, 'expense_account_code' => $row->expense_account_code];
      $result_grid[$row->income_account_id]['budget_items'][$row->expense_account_id]['expense_items'][$row->budget_item_id] =
        [
          'budget_item_id' => $row->budget_item_id,
          'track_number' => $row->budget_item_track_number,
          'description' => $row->budget_item_description,
          'quantity' => $row->budget_item_quantity,
          'unit_cost' => $row->budget_item_unit_cost,
          'often' => $row->budget_item_often,
          'total_cost' => $row->budget_item_total_cost,
          'status' => ['status_id' => $row->status_id, 'status_name' => $row->status_name],
          'budget_item_marked_for_review' => $row->budget_item_marked_for_review,
          'message_id' => $row->message_id,
          'month_spread' => $month_spread[$row->budget_item_id],
          'budget_item_source_id' => $row->budget_item_source_id,
          'budget_item_revisions' => !is_null($row->budget_item_revisions) ? json_decode($row->budget_item_revisions) : [],
          'objectives' => !is_null($row->budget_item_objective) ? json_decode($row->budget_item_objective) : []
        ];
    }

    //$result_grid['spreading_of_month'] = $month_spread;
    return $result_grid;
  }

  /**
   *budgetLimits():This method returns budget limit records.
   * @author Livingstone Onduso: Dated 06-05-2024
   * @access private
   * @return array 
   * @param float $budget_id
   */
  private function budgetLimits(float $budget_id): array
  {

    $budget_limit_array = [];


    // Reconnect the database (if needed)
    $this->read_db->reconnect();

    // Query
    $builder = $this->read_db->table('budget_limit');
    $builder->select([
      'fk_income_account_id',
      'budget_limit_amount'
    ]);
    $builder->join('budget', 'budget.budget_id = budget_limit.fk_budget_id');
    $builder->where('budget_id', $budget_id);

    // Execute the query
    $budget_limit_obj = $builder->get();

    if ($budget_limit_obj->getNumRows() > 0) {
      $budget_limits = $budget_limit_obj->getResultArray();
      $accounts = array_column($budget_limits, 'fk_income_account_id');
      $amounts = array_column($budget_limits, 'budget_limit_amount');
      $budget_limit_array = array_combine($accounts, $amounts);
    }

    return $budget_limit_array;
  }

  private function budgetSummaryResult($budget_year = ""): array
  {
    $data = [];

    //$budget_office = $this->budget_office();
    //Get the budget summary
    $budget_id = hash_id($this->id, 'decode');

    // Execute the stored procedure
    $query = $this->read_db->query("CALL get_budget_summary(?)", [$budget_id]);
    // Fetch the result as an array of objects
    $jsonfied_result = $query->getResultObject();

    $result = [];

    foreach ($jsonfied_result as $detail) {

      $result[$detail->income_account_id]['income_account'] = ['income_account_id' => $detail->income_account_id, 'income_account_name' => $detail->income_account_name, 'income_account_code' => $detail->income_account_code];
      $result[$detail->income_account_id]['spread_expense_account'][$detail->expense_account_id]['expense_account'] = ['account_name' => $detail->expense_account_name, 'account_code' => $detail->expense_account_code];
      $result[$detail->income_account_id]['spread_expense_account'][$detail->expense_account_id]['spread'][$detail->month_name] = $detail->budget_item_detail_amount;
    }

    $data['summary'] =  $result;

    return $data;
  }

  private function budgetHeaderInformation($budget_year = '')
  {

    $budget_office = $this->budgetLib->budgetOffice();

    $budget_year = 0;
    $office_id = 0;
    $office_name = "";
    $budget_tag_name = "";
    $budget_status_id = 0;
    $budget_tag_id = 0;

    if (isset($budget_office->office_id)) {
      $office_id = $budget_office->office_id;
      $budget_year = $budget_office->budget_year;
      $office_name = $budget_office->office_name;
      $budget_tag_name = $budget_office->budget_tag_name;
      $budget_tag_id = $budget_office->budget_tag_id;
      $budget_status_id = $budget_office->status_id;
    }

    $projects = $this->budgetLib->getProjects($office_id);

    $data = [];

    foreach ($projects as $project) {
      $data['funder_projects'][$project->funder_id]['funder'] = ['funder_id' => $project->funder_id, 'funder_name' => $project->funder_name];
      $data['funder_projects'][$project->funder_id]['projects'][] = ['project_allocation_id' => $project->project_allocation_id, 'project_allocation_name' => $project->project_allocation_name];
    }

    $data['current_year'] = $budget_year;
    $data['office'] = $office_name;
    $data['budget_tag'] = $budget_tag_name;
    $data['status_id'] = $budget_status_id;
    $data['office_id'] = $office_id;
    $data['budget_tag_id'] = $budget_tag_id;


    return $data;
  }

  /**
   *listValidBudgetYearsForOffice():This method give budget years as json object.
   * @author Livingstone Onduso: Dated 30-01-2025
   * @access public
   * @return array 
   */
  function listValidBudgetYearsForOffice()
  {
    //$post = $this->request->getPost('office_id');

    $office_id = $this->request->getPost('office_id');//$post['office_id'];

    $valid_budget_years = $this->budgetLib->validBudgetYears($office_id);

    echo json_encode($valid_budget_years);
  }

 /**
   *listBudgetableIncomeAccount():This method give income accounts years as json object.
   * @author Livingstone Onduso: Dated 30-01-2025
   * @access public
   * @param int $office_id
   */
  public function listBudgetableIncomeAccount(int $office_id)
  {

    $income_accounts = $this->incomeAccountLib->incomeAccountByOfficeId($office_id);

    echo json_encode($income_accounts);
  }

  /**
   *getOfficeBudgetTags():This method give income accounts years as json object.
   * @author Livingstone Onduso: Dated 30-01-2025
   * @access public
   */
  public function getOfficeBudgetTags()
  {

    $office_id = $this->request->getPost('office_id') ;
    $budget_year =$this->request->getPost('budget_year') ;

    $valid_budget_tags = $this->budgetLib->validBudgetTags($office_id, $budget_year);

    echo json_encode($valid_budget_tags);
  }

  public function postBudget()
{
   
    $session = session();

    $post = $this->request->getPost(); 
    
    $this->write_db->transBegin(); 

    $actionBeforeInsert =$this->budgetLib->actionBeforeInsert($post);
    
    extract($post);
    if (!array_key_exists('header', $actionBeforeInsert)) {
        return $this->response->setJSON($actionBeforeInsert);

    }

    // Insert the budget
    $statusLib=new \App\Libraries\Core\StatusLibrary();
    $tracking = $this->generateItemTrackNumberAndName('budget');

    $budgetInsertData = [
        'budget_track_number' => $tracking['budget_track_number'],
        'budget_name' => $tracking['budget_name'],
        'fk_office_id' => $actionBeforeInsert['header']['fk_office_id'],
        'budget_year' => $actionBeforeInsert['header']['budget_year'],
        'fk_budget_tag_id' => $actionBeforeInsert['header']['fk_budget_tag_id'],
        'fk_status_id' => $statusLib->initialItemStatus('budget'),
        'budget_created_by' => $session->get('user_id'),
        'budget_created_date' => date('Y-m-d'),
        'budget_last_modified_by' => $session->get('user_id'),
        'budget_last_modified_date' => date('Y-m-d H:i:s'),
    ];

   
    $this->write_db->table('budget')->insert($budgetInsertData);
    $budgetId = $this->write_db->insertID();
    $hashedBudgetId = hash_id($budgetId, 'encode');

    // Insert budget limits
    $budgetLimitInsertData = [];
    $incomeAccountIds = isset($actionBeforeInsert['details']['fk_income_account_id']) ? $actionBeforeInsert['details']['fk_income_account_id']: [];

    foreach ($incomeAccountIds as $index => $incomeAccountId) {
        $budgetLimitTracking = $this->generateItemTrackNumberAndName('budget_limit');

        $budgetLimitInsertData[] = [
            'budget_limit_track_number' => $budgetLimitTracking['budget_limit_track_number'],
            'budget_limit_name' => $budgetLimitTracking['budget_limit_name'],
            'fk_income_account_id' => $incomeAccountId,
            'budget_limit_amount' => isset($actionBeforeInsert['details']['budget_limit_amount'][$index])? $actionBeforeInsert['details']['budget_limit_amount'][$index]: 0,
            'fk_budget_id' => $budgetId,
            'fk_status_id' => $statusLib->initialItemStatus('budget_limit'),
            'budget_limit_created_by' => $session->get('user_id'),
            'budget_limit_created_date' => date('Y-m-d'),
            'budget_limit_last_modified_by' => $session->get('user_id'),
            'budget_limit_last_modified_date' => date('Y-m-d H:i:s'),
        ];
    }

    if (!empty($budgetLimitInsertData)) {
        $this->write_db->table('budget_limit')->insertBatch($budgetLimitInsertData);
    }

    $post['header']['fk_budget_id'] = $budgetId;
    $actionAfterInsert = $this->budgetLib->actionAfterInsert($post['header'], 0, $budgetId);

    if ($this->write_db->transStatus() === false || !$actionAfterInsert) {
        $this->write_db->transRollback();
        return $this->response->setJSON(["message" => "Budget record failed to create"]);
    } else {
        $this->write_db->transCommit();
        return $this->response->setJSON(["budget_id" => $hashedBudgetId]);
    }
}


}
