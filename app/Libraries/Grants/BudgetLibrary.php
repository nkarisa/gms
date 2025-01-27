<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\BudgetModel;
use App\Libraries\Grants\CustomFinancialYearLibrary;
use App\Libraries\Grants\BudgetTagLibrary;
use App\Libraries\Core\StatusLibrary;

class BudgetLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface {
    protected $table;
    protected $budgetModel;
    protected $customFinancialYearLibrary;
    protected $budgetTagLibrary;
    protected $voucherLibrary;
    protected $statusLibrary;
    protected $monthLib;
    public $is_multi_row = false;

  function __construct()
  {
    parent::__construct();


    $this->budgetModel = new BudgetModel();
    $this->customFinancialYearLibrary = new CustomFinancialYearLibrary();
    $this->table = 'budget';
    $this->budgetTagLibrary = new BudgetTagLibrary();
    $this->voucherLibrary = new VoucherLibrary();
    $this->statusLibrary = new StatusLibrary();
    $this->monthLib = new \App\Libraries\Core\MonthLibrary();
  }

  public function budgetToDateAmountByIncomeAccount($budget_id, $income_account_id)
  {

    

    $budget_item_detail_amount = 0.0;
    $builder = $this->read_db->table('budget_item_detail');
    $builder->selectSum('budget_item_detail_amount');
    $builder ->join('budget_item', 'budget_item.budget_item_id = budget_item_detail.fk_budget_item_id');
    $builder ->join('expense_account', 'expense_account.expense_account_id = budget_item.fk_expense_account_id');
    $builder ->where([
        'fk_budget_id' => $budget_id,
        'fk_income_account_id' => $income_account_id
    ]);
    $budget_item_detail_amount_obj=$builder->get();

    //log_message('error', json_encode($budget_item_detail_amount_obj));


    if (!empty($budget_item_detail_amount_obj)) {
      $budget_item_detail_amount = $budget_item_detail_amount_obj->getRow()->budget_item_detail_amount;
    }
    // if ($budget_item_detail_amount_obj->getNumRows() > 0) {
    //   $budget_item_detail_amount = $budget_item_detail_amount_obj->getRow()->budget_item_detail_amount;
    // }

    return $budget_item_detail_amount;
  }


  public function getBudgetFyDates(int $budget_id):array{

    // Get the default fy start month number
    $default_fy_start_month = $this->monthLib->defaultFyStartMonth()->month_number;

    // Get budget record and start month number if flexible FY
    $builder = $this->read_db->table('budget');

    $builder->select(['budget_year', 'custom_financial_year_start_month']);

    $builder->join(
        'custom_financial_year',
        'custom_financial_year.custom_financial_year_id = budget.fk_custom_financial_year_id',
        'left'
    );
    $builder->where('budget_id', $budget_id);
    $budget_obj=$builder->get()->getRow();

    // Get the Budget FY
    $budget_fy = $budget_obj->budget_year;

    // Get the Budget start Month
    $budget_start_month = $budget_obj->custom_financial_year_start_month == null ? $default_fy_start_month : $budget_obj->custom_financial_year_start_month;

    // Get century prefix 
    $fourDigitYear = date('Y');
    $centuryPrefix = substr($fourDigitYear, 0, 2);

    // Compute FY start date
    $fy_start_year = $centuryPrefix.$budget_fy;

    if($budget_start_month != 1){
      $budget_fy--;
      $fy_start_year = $centuryPrefix.$budget_fy;
    }

    $fy_start_date = date('Y-m-d', mktime(0, 0, 0, $budget_start_month, 1, $fy_start_year));

    // Compute FY end date
    // Convert the given date to a timestamp
    $fy_start_date_timestamp = strtotime($fy_start_date);
    // Get the last day of the 12th month from the given date
    $fy_last_date = date('Y-m-t', strtotime('+11 months', $fy_start_date_timestamp));

    return ['fy_start_date' => $fy_start_date, 'fy_end_date' => $fy_last_date];

  }
  
  function checkIfAllBudgetItemsAreApproved(float $budget_id): bool
  {

    $return = true;

    // Query Builder
    $builder = $this->read_db->table('office');
    $builder->select(['fk_account_system_id', 'budget.fk_status_id as budget_status_id']);
    $builder->join('budget', 'budget.fk_office_id = office.office_id');
    $builder->where('budget_id', $budget_id);

    // Execute the query
    $result_obj = $builder->get();

    $account_system_id = $result_obj->getRow()->fk_account_system_id;
    $budget_status_id = $result_obj->getRow()->budget_status_id;
    $budget_item_max_states = $this->statusLibrary->getMaxApprovalStatusId('budget', [], $account_system_id);

    if (!in_array($budget_status_id, $budget_item_max_states)) {
      $budget_item_initial_status = $this->statusLibrary->initialItemStatus('budget_item', $account_system_id);
      // Query Builder
      $builder = $this->read_db->table('budget_item');
      $builder->select(['budget_item_id', 'fk_status_id']);
      $builder->where('fk_budget_id', $budget_id);

      // Execute the query
      $budget_items_obj = $builder->get();

      if ($budget_items_obj->getNumRows() > 0) {
        //$budget_item_max_states
        $budget_items = $budget_items_obj->getResultArray();
        $states = [];
        for ($i = 0; $i < count($budget_items); $i++) {
          if (!in_array($budget_items[$i]['fk_status_id'], $states)) {
            $states[$i] = $budget_items[$i]['fk_status_id'];
          }
        }

        $declined_item_status = $this->accountSystemDeclineStates('budget_item', $account_system_id); // $this->general_model->decline_status($budget_item['budget_item_id'],$account_system_id);
        if (in_array($budget_item_initial_status, $states) || array_intersect($declined_item_status, $states)) {
          $return = false;
        }
      }
    }

    return $return;
  }

  function getBudgetByOfficeCurrentTransactionDate($office_id)
  {

    $voucherLibrary = new VoucherLibrary();
    $customFinancialYearLibrary = new CustomFinancialYearLibrary();
    $budgetTagLibrary = new BudgetTagLibrary();


    $next_vouching_date = $voucherLibrary->getVoucherDate($office_id);
    $custom_financial_year = $customFinancialYearLibrary->getDefaultCustomFinancialYearIdByOffice($office_id);
    $this->evaluateCustomFinancialYear($office_id, $next_vouching_date, $custom_financial_year);

    $start_month = $custom_financial_year['custom_financial_year_id'] != NULL && !$custom_financial_year['custom_financial_year_is_active'] ? $custom_financial_year['custom_financial_year_start_month'] : 7;
    $custom_financial_year_id = $custom_financial_year['custom_financial_year_id'] != NULL ? $custom_financial_year['custom_financial_year_id'] : 0;

    $fy = calculateFinancialYear($next_vouching_date, $start_month);

    $mfr_budget_tag_id = $budgetTagLibrary->getBudgetTagIdBasedOnReportingMonth($office_id, $next_vouching_date, $custom_financial_year)['budget_tag_id'];
    $max_budget_approval_ids = $this->statusLibrary->getMaxApprovalStatusId('budget');

    $builder = $this->read_db->table("budget");
    if ($custom_financial_year['custom_financial_year_id'] != NULL && !$custom_financial_year['custom_financial_year_is_active']) {
      $builder->where(array('fk_custom_financial_year_id' => $custom_financial_year_id));
    }
    $builder->where(array('fk_office_id' => $office_id, 'budget_year' => $fy, 'fk_budget_tag_id' => $mfr_budget_tag_id));
    $builder->whereIn('budget.fk_status_id', $max_budget_approval_ids);

    $budget_obj = $builder->get();
    $budget = [];
    if ($budget_obj->getNumRows() > 0) {
      $budget = $budget_obj->getRowArray();
    }

    return $budget;
  }

  function evaluateCustomFinancialYear($office_id, $next_vouching_date, &$custom_financial_year)
  {

    $oldest_declined_financial_report = $this->oldestDeclinedFinancialReport($office_id);

    if (!empty($oldest_declined_financial_report)) {
      if ($oldest_declined_financial_report['custom_financial_year_id'] == null) {
        $custom_financial_year = ['custom_financial_year_start_month' => 7, 'custom_financial_year_id' => NULL, 'custom_financial_year_is_active' => 0, 'custom_financial_year_reset_date' => NULL];
      } else {
        $custom_financial_year = $customFinancialYearLibrary->getCustomFinancialYearById($oldest_declined_financial_report['custom_financial_year_id']);
      }
    }

    // Check if the vouching period is still behind the default custom fy reset date
    $transaction_period_behind_default_custom_fy_reset_date = $customFinancialYearLibrary->transactionPeriodBehindDefaultCustomFyResetDate($next_vouching_date, $custom_financial_year);

    if ($transaction_period_behind_default_custom_fy_reset_date) {
      $custom_financial_year = ['custom_financial_year_start_month' => 7, 'custom_financial_year_id' => NULL, 'custom_financial_year_is_active' => 0, 'custom_financial_year_reset_date' => NULL];

      if ($custom_financial_year['custom_financial_year_id'] != null) {
        $custom_financial_year = $customFinancialYearLibrary->getPreviousCustomFinancialYearByCurrentId($office_id, $custom_financial_year['custom_financial_year_id']);
      }
    }
  }

  function oldestDeclinedFinancialReport($office_id)
  {

    // $statusLibrary = new \App\Libraries\Core\StatusLibrary();
    $decline_status_ids = $this->statusLibrary->getDeclineStatusIds('financial_report');

    $builder = $this->read_db->table('financial_report');
    $builder->select(array('financial_report.fk_office_id as office_id', 'financial_report_id', 'budget_id', 'fk_custom_financial_year_id as custom_financial_year_id'));
    $builder->whereIn('financial_report.fk_status_id', $decline_status_ids);
    $builder->where(array('financial_report.fk_office_id' => $office_id));
    $builder->orderBy('financial_report_month ASC');
    $builder->join('budget', 'budget.budget_id=financial_report.fk_budget_id');
    $financial_report_obj = $builder->get();

    $oldest_declined_report = [];

    if ($financial_report_obj->getNumRows() > 0) {
      $oldest_declined_report = $financial_report_obj->getRowArray();
    }

    return  $oldest_declined_report;
  }

  function changeFieldType(): array
  {
    $change['budget_year']['field_type'] = 'text';
    return $change;
  }
  function singleFormAddVisibleColumns():array{

    return [
      'office_name',
      'budget_year'
  
  
  ];

  }
  function listTableVisibleColumns(): array
  {
    $columns = [
      'budget_id',
      'budget_track_number',
      'office_name',
      'budget_tag_name',
      'budget_year',
      'status_name',
      // 'month_name', // as budget_fy_start_month',
      'custom_financial_year_reset_date', // as fy_reset_start_date'
    ];

    return $columns;
  }

  function customTableJoin(\CodeIgniter\Database\BaseBuilder $builder): void
  {
    // $builder->join('month','month.month_number=custom_financial_year.custom_financial_year_start_month', 'LEFT');
    $builder->join('custom_financial_year', 'custom_financial_year.custom_financial_year_id=budget.fk_custom_financial_year_id', 'LEFT');
  }

  public function budgetOffice()
  {
    // Query
    $builder = $this->read_db->table('budget');
    $builder->select([
      'office_id',
      'office_name',
      'office_code',
      'budget_year',
      'budget_tag_id',
      'budget_tag_name',
      'budget.fk_status_id as status_id'
    ]);
    $builder->join('office', 'office.office_id = budget.fk_office_id');
    $builder->join('budget_tag', 'budget_tag.budget_tag_id = budget.fk_budget_tag_id');
    $builder->where('budget_id', hash_id($this->id, 'decode'));
    $budget_office = $builder->get()->getRow();

    return $budget_office;
  }

  public function getProjects(float $office_id): array
  {

    // Query
    $builder = $this->read_db->table('project');
    $builder->select([
      'funder_id',
      'funder_name',
      'project_allocation_id',
      'project_allocation_name'
    ]);
    $builder->join('project_allocation', 'project_allocation.fk_project_id = project.project_id');
    $builder->join('funder', 'funder.funder_id = project.fk_funder_id');
    $builder->where('fk_office_id', $office_id);
    $projects = $builder->get()->getResultObject();

    return  $projects;
  }

  // function getBudgetIdBasedOnMonth($office_id, $reporting_month)
  // {

  //   // $this->load->model('custom_financial_year_model');
  //   $custom_financial_year = $this->customFinancialYearLibrary->getDefaultCustomFinancialYearIdByOffice($office_id, true);
  //   $budget_tag_id = $this->budgetTagLibrary->getBudgetTagIdBasedOnReportingMonth($office_id, $reporting_month, $custom_financial_year)['budget_tag_id'];
  //   // log_message('error', json_encode($budget_tag_id));
  //   $budget_id = 0;

  //   $budget_year = get_fy($reporting_month);

  //   $builder = $this->read_db->table('budget');
  //   $builder->where(array(
  //     'fk_budget_tag_id' => $budget_tag_id,
  //     'fk_office_id' => $office_id, 'budget_year' => $budget_year
  //   ));

  //   $budget_id_obj = $builder->get('budget');

  //   if ($budget_id_obj->getNumRows() > 0) {
  //     $budget_id =  $budget_id_obj->getRow()->budget_id;
  //   }

  //   return $budget_id;
  // }
}
