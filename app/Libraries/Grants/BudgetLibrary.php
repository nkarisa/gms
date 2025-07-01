<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\BudgetModel;
use App\Libraries\Grants\CustomFinancialYearLibrary;
use App\Libraries\Grants\BudgetTagLibrary;
use App\Libraries\Core\StatusLibrary;
use App\Libraries\Grants\MessageDetailLibrary;

class BudgetLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{
  protected $table;
  protected $budgetModel;
  protected $customFinancialYearLibrary;
  protected $budgetTagLibrary;
  protected $voucherLibrary;
  protected $statusLibrary;
  protected $budgetReviewCountLib;
  protected $monthLib;
  public $is_multi_row = false;

  protected $messageLib;

  public $dependant_table = 'budget_item';

  function __construct()
  {
    parent::__construct();


    $this->budgetModel = new BudgetModel();
    $this->customFinancialYearLibrary = new CustomFinancialYearLibrary();
    $this->table = 'budget';
    $this->budgetTagLibrary = new BudgetTagLibrary();
    $this->voucherLibrary = new VoucherLibrary();
    $this->statusLibrary = new StatusLibrary();
    $this->budgetReviewCountLib = new BudgetReviewCountLibrary();
    $this->messageLib=new MessageDetailLibrary();

    $this->monthLib = new \App\Libraries\Core\MonthLibrary();
  }

  public function budgetToDateAmountByIncomeAccount($budget_id, $expense_account_id)
  {


    //budget_to_date_amount_by_income_account

    //Get account_income_id
    $builder_reader=$this->read_db->table('expense_account');
    $builder_reader->select(['fk_income_account_id']);
    $builder_reader->where('expense_account_id', $expense_account_id);
    $income_account_id=$builder_reader->get()->getRow()->fk_income_account_id;
    
    //Get the total amount
    $budget_item_detail_amount = 0.0;
    $builder = $this->read_db->table('budget_item_detail');
    $builder->selectSum('budget_item_detail_amount');
    $builder->join('budget_item', 'budget_item.budget_item_id = budget_item_detail.fk_budget_item_id');
   // $builder->join('expense_account', 'expense_account.expense_account_id = budget_item.fk_expense_account_id');
    $builder->where([
      'fk_budget_id' => $budget_id,
      //'fk_income_account_id' => $income_account_id
    ]);
    $budget_item_detail_amount_obj = $builder->get();


    if (!empty($budget_item_detail_amount_obj)) {
      $budget_item_detail_amount = $budget_item_detail_amount_obj->getRow()->budget_item_detail_amount;
    }
    // if ($budget_item_detail_amount_obj->getNumRows() > 0) {
    //   $budget_item_detail_amount = $budget_item_detail_amount_obj->getRow()->budget_item_detail_amount;
    // }

    return $budget_item_detail_amount;
  }


  public function getBudgetFyDates(int $budget_id): array
  {

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
    $budget_obj = $builder->get()->getRow();

    // Get the Budget FY
    $budget_fy = $budget_obj->budget_year;

    // Get the Budget start Month
    $budget_start_month = $budget_obj->custom_financial_year_start_month == null ? $default_fy_start_month : $budget_obj->custom_financial_year_start_month;

    // Get century prefix 
    $fourDigitYear = date('Y');
    $centuryPrefix = substr($fourDigitYear, 0, 2);

    // Compute FY start date
    $fy_start_year = $centuryPrefix . $budget_fy;

    if ($budget_start_month != 1) {
      $budget_fy--;
      $fy_start_year = $centuryPrefix . $budget_fy;
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
        $custom_financial_year = $this->customFinancialYearLibrary->getCustomFinancialYearById($oldest_declined_financial_report['custom_financial_year_id']);
      }
    }

    // Check if the vouching period is still behind the default custom fy reset date
    $transaction_period_behind_default_custom_fy_reset_date = $this->customFinancialYearLibrary->transactionPeriodBehindDefaultCustomFyResetDate($next_vouching_date, $custom_financial_year);

    if ($transaction_period_behind_default_custom_fy_reset_date) {
      $custom_financial_year = ['custom_financial_year_start_month' => 7, 'custom_financial_year_id' => NULL, 'custom_financial_year_is_active' => 0, 'custom_financial_year_reset_date' => NULL];

      if ($custom_financial_year['custom_financial_year_id'] != null) {
        $custom_financial_year = $this->customFinancialYearLibrary->getPreviousCustomFinancialYearByCurrentId($office_id, $custom_financial_year['custom_financial_year_id']);
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

  // function changeFieldType(): array
  // {
  //   $change['budget_year']['field_type'] = 'text';
  //   return $change;
  // }
  function singleFormAddVisibleColumns(): array
  {

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

  public function lookupValues(): array
  {
    $lookup_values = [];

    if (!session()->get('system_admin')) {
      $builder = $this->read_db->table('office');  // Query builder for 'office' table

      // Apply WHERE IN condition for office_id
      $builder->whereIn('office_id', array_column(session()->get('hierarchy_offices'), 'office_id'));
      $builder->where([
        'office_is_readonly' => 0,
        'fk_context_definition_id' => 1
      ]);

      // Fetch results
      $lookup_values['office'] = $builder->get()->getResultArray();

      // Check environment conditions
      $config = config('GrantsConfig'); // Load configuration
      $env = session()->get('env');
      $show_all_budget_tags = $config->show_all_budget_tags ?? false;

      $builderBudgetTag = $this->read_db->table('budget_tag');
      if ($env === 'production' || (!$show_all_budget_tags && $env !== 'production')) {
        $current_month = date('n');

        // Fetch quarter months based on the budget review period
        $next_current_quarter_months = financial_year_quarter_months(month_after_adding_size_of_budget_review_period($current_month));

        // Query builder for 'budget_tag'
        $builderBudgetTag->select(['budget_tag_id', 'budget_tag_name']);
        $builderBudgetTag->groupStart();

        // Calculate months_in_quarter_index_offset
        $months_in_quarter_index_offset = ($config->size_in_months_of_a_budget_review_period ?? 3) -
          ($config->number_of_month_to_start_budget_review_before_close_of_review_period ?? 1);

        if ($months_in_quarter_index_offset < 0) {
          $months_in_quarter_index_offset = ($config->size_in_months_of_a_budget_review_period ?? 3) - 1;
        }

        // Add condition based on the calculated offset
        if (month_after_adding_size_of_budget_review_period($current_month) >= $next_current_quarter_months['months_in_quarter'][$months_in_quarter_index_offset]) {
          $builderBudgetTag->whereIn('fk_month_id', $next_current_quarter_months['months_in_quarter']);
        }

        // Handle budget_tag_level logic
        $quarter_number = $next_current_quarter_months['quarter_number'] - 1;
        $budget_tag_level = ($quarter_number == 0) ? ($config->maximum_review_count ?? 4) : $quarter_number;
        $builderBudgetTag->orWhere('budget_tag_level', $budget_tag_level);

        $builderBudgetTag->groupEnd();
      }

      // Additional filters for budget_tag
      $builderBudgetTag->where([
        'fk_account_system_id' => session()->get('user_account_system_id'),
        'budget_tag_is_active' => 1
      ]);

      // Fetch budget_tag data
      $lookup_values['budget_tag'] = $builderBudgetTag->get()->getResultArray();
    }

    return $lookup_values;
  }


  /**
   * @todo:
   * Await documentation
   */

  public function validBudgetYears(int $office_id)
  {

    $valid_budget_years = [];

    // Get office start date
    $custom_financial_year = $this->customFinancialYearLibrary->getDefaultCustomFinancialYearIdByOffice($office_id);

    // log_message('error', json_encode($custom_financial_year));

    $budget_start_date =  $this->voucherLibrary->getVoucherDate($office_id);

    if ($custom_financial_year['custom_financial_year_is_active']) {
      $budget_start_date = $custom_financial_year['custom_financial_year_reset_date'];
    }

    $office_start_fy = calculateFinancialYear($budget_start_date, $custom_financial_year['custom_financial_year_start_month']);

    // log_message('error', json_encode(['budget_start_date' => $budget_start_date, 'custom_financial_year' => $custom_financial_year, 'office_start_fy' => $office_start_fy]));

    $budget_review_count = $this->officeBudgetReviewCount($office_id);

    // All budget present for the office
    $office_budget_records = []; // 

    // $default_custom_financial_year = $this->get_custom_financial_year($office_id, true);

    if ($custom_financial_year['custom_financial_year_id'] != NULL) {
      $office_budget_records = $this->officeBudgetRecords($office_id, $office_start_fy, $custom_financial_year['custom_financial_year_start_month']);
    } else {
      $office_budget_records = $this->officeBudgetRecords($office_id);
    }

    if (empty($office_budget_records)) {
      $valid_budget_years = [$office_start_fy];
    } else {
      $last_budget_record = end($office_budget_records);
      $last_budget_year = $last_budget_record['budget_year'];
      $budget_tag_level = $last_budget_record['budget_tag_level'];

      if ($budget_tag_level == $budget_review_count) {
        $valid_budget_years = [$last_budget_year + 1];
      } else {
        $valid_budget_years = [$last_budget_year];
      }
    }

    return $valid_budget_years;
  }

  /**
   *officeBudgetReviewCount():This method returns review counts.
   * @author Livingstone Onduso: Dated 30-01-2025
   * @access public
   * @return int 
   * @param int $office_id
   */
  private function officeBudgetReviewCount(int $office_id): int
  {

    $review_count = $this->budgetReviewCountLib->budgetReviewCountByOffice($office_id);

    return $review_count;
  }

  /**
   *officeBudgetRecords():This method returns review counts.
   * @author Livingstone Onduso: Dated 30-01-2025
   * @access public
   * @return array 
   * @param int $office_id
   */


  private function officeBudgetRecords($office_id, $budget_year = '', $start_month = ''): array
  {
    $builder = $this->read_db->table('budget'); // Query Builder for 'budget' table

    // Select required columns
    $builder->select([
      'budget.fk_office_id as office_id',
      'fk_account_system_id as account_system_id',
      'budget_year',
      'budget_tag_id',
      'budget_tag_level'
    ]);

    // Join 'budget_tag' table
    $builder->join('budget_tag', 'budget_tag.budget_tag_id = budget.fk_budget_tag_id');
    $builder->where('budget.fk_office_id', $office_id);

    // Filter by budget year if provided
    if (!empty($budget_year)) {
      $builder->where('budget_year', $budget_year);
    }

    // Handle optional 'start_month' filter
    if (!empty($start_month)) {
      $builder->join('custom_financial_year', 'custom_financial_year.custom_financial_year_id = budget.fk_custom_financial_year_id');
      $builder->where('custom_financial_year_start_month', $start_month);
    }

    // Order by 'budget_year' and 'budget_tag_level'
    $builder->orderBy('budget_year ASC');
    $builder->orderBy('budget_tag_level ASC');

    // Execute query
    $query = $builder->get();

    // Fetch results
    return ($query->getNumRows() > 0) ? $query->getResultArray() : [];
  }

  public function changeFieldType(): array
  {
    $fields = [];

    $current_year = date('y');
    $year_range = range($current_year - 1, $current_year + 3);

    $fields['budget_year']['field_type'] = 'select';

    foreach ($year_range as $year) {
      $fields['budget_year']['options'][$year] = 'FY' . $year;
    }
    return $fields;
  }
  /**
   *getOffice():This returns the a row.
   * @author Livingstone Onduso: Dated 30-01-2025
   * @access private
   * @return object 
   * @param int $office_id
   */
  private function getOffice(int $office_id): object
  {

    $builder = $this->read_db->table('office');
    // Select the required columns
    $builder->select([
      'office_id',
      'office_start_date',
      'fk_account_system_id AS account_system_id'
    ]);

    // Add WHERE condition
    $builder->where('office_id', $office_id);

    // Fetch single row
    $office_start_date = $builder->get()->getRow();

    return $office_start_date;
  }

  public function getCustomFinancialYear($office_id, $show_default_only = false)
  {
    $builder = $this->read_db->table('custom_financial_year');
    // Select the required columns
    $builder->select([
      'custom_financial_year_id',
      'custom_financial_year_start_month AS start_month',
      'custom_financial_year_reset_date AS start_date'
    ]);

    // Define the condition
    $condition = [
      'custom_financial_year_is_active' => 1,
      'fk_office_id' => $office_id
    ];

    if ($show_default_only) {
      $condition = [
        'custom_financial_year_is_default' => 1,
        'fk_office_id' => $office_id
      ];
    }

    // Apply WHERE condition
    $builder->where($condition);

    // Execute the query
    $query = $builder->get()->getRow();

    // Prepare the result
    $budget_year = [];

    if ($query) {
      $fy = get_fy($query->start_date, $office_id);

      $budget_year = [
        'budget_year' => $fy,
        'start_date' => $query->start_date,
        'start_month' => $query->start_month,
        'id' => $query->custom_financial_year_id
      ];
    }

    return $budget_year;
  }


  /**
   *validBudgetTags():This returns the budget tags.
   * @author Livingstone Onduso: Dated 30-01-2025
   * @access public
   * @return array 
   * @param int $office_id, int $budget_year
   */

  function validBudgetTags(int $office_id, int $budget_year)
  {

    $valid_budget_tags = [];

    $office = $this->getOffice($office_id);

    $default_custom_financial_year = $this->getCustomFinancialYear($office_id, true);

    //log_message('error',json_encode($default_custom_financial_year));

    $year_budget_records = [];

    if (!empty($this->getCustomFinancialYear($office_id))) {
      $month_number = date('n', strtotime($default_custom_financial_year['start_date']));
      $quarter_number = financial_year_quarter_months($month_number, $office_id)['quarter_number'];
      $condition = array('fk_account_system_id' => $office->account_system_id, 'budget_tag_level' => $quarter_number);
      $year_budget_records = $this->officeBudgetRecords($office_id, $budget_year, $default_custom_financial_year['start_month']);
    } else {
      $condition = array('fk_account_system_id' => $office->account_system_id);

      if (!empty($default_custom_financial_year)) {
        $year_budget_records = $this->officeBudgetRecords($office_id, $budget_year, $default_custom_financial_year['start_month']);
      } else {
        $year_budget_records = $this->officeBudgetRecords($office_id, $budget_year);
      }
    }

    // return $year_budget_records;
    // log_message('error', json_encode(['default_custom_financial_year' => $default_custom_financial_year]));

    $utilized_budget_tag_levels = array_column($year_budget_records, 'budget_tag_level');
    $range_of_budget_tag_levels = range(1, $this->officeBudgetReviewCount($office_id));

    // log_message('error', json_encode(['default_custom_financial_year' => $default_custom_financial_year, 'utilized_budget_tag_levels' => $utilized_budget_tag_levels]));
    // Sort and iterate the standard list of budget tag levels and remove those levels that are before the first utilized quarter

    // return $range_of_budget_tag_levels;

    if (!empty($utilized_budget_tag_levels)) {

      sort($utilized_budget_tag_levels);

      $lowest_utilized_level = $utilized_budget_tag_levels[0];

      foreach ($range_of_budget_tag_levels as $range_of_budget_tag_level) {
        if ($range_of_budget_tag_level < $lowest_utilized_level) {
          unset($range_of_budget_tag_levels[array_search($range_of_budget_tag_level, $range_of_budget_tag_levels)]);
        }
      }
    }

    $year_remaining_budget_tags = array_diff($range_of_budget_tag_levels, $utilized_budget_tag_levels);

    sort($year_remaining_budget_tags);

    // Get all office budget records
    $all_office_budget_records = $year_budget_records; //$this->office_budget_records($office_id);

    $builder = $this->read_db->table('budget_tag'); // Initialize Query Builder for 'budget_tag' table

    // Apply condition if both arrays are not empty
    if (!empty($all_office_budget_records) && !empty($year_remaining_budget_tags)) {
      $builder->where('budget_tag_level', $year_remaining_budget_tags[0]); // Take the immediate next tag
    }

    // Apply additional conditions
    $builder->where($condition);

    // Select specific columns
    $builder->select(['budget_tag_id', 'budget_tag_name']);

    // Execute the query
    $budget_tags = $builder->get()->getResultArray();

    $budget_tag_ids = array_column($budget_tags, 'budget_tag_id');
    $budget_tag_names = array_column($budget_tags, 'budget_tag_name');

    $valid_budget_tags = array_combine($budget_tag_ids, $budget_tag_names);

    return $valid_budget_tags;
  }


  function actionBeforeInsert(array $post_array): array
  {
    
    $office_id  = $post_array['header']['fk_office_id'];

    //log_message("error",json_encode($post_array['header']['fk_office_id']));

    $current_unsubmitted_budget = $this->getCurrentUnsignedOffBudget($office_id);

    if (!empty($current_unsubmitted_budget)) {
      return ['message' => get_phrase('has_unsigned_off_budget', 'Failure to create budget due to unsigned off previous budgets')];
    }

    return $post_array;
  }


  function actionAfterInsert($post_array, $approval_id, $header_id): bool
  {

    $this->write_db->transStart();

    $default_custom_financial_year = $this->getCustomFinancialYear($post_array['fk_office_id'], true);

    if (!empty($default_custom_financial_year)) {
      $this->updateBudgetCustomFinancialYear($header_id, $default_custom_financial_year['start_month'], $default_custom_financial_year['id']);
    }

    $this->replicateBudget($post_array, $approval_id, $header_id, $default_custom_financial_year);

    $this->write_db->transComplete();

    return $this->write_db->transStatus();
  }

  function updateBudgetCustomFinancialYear($budgetId, $start_month, $defaultCustomFinancialYear)
  {

    $builder = $this->write_db->table('budget');

    $builder->where('budget_id', $budgetId)->update(['fk_custom_financial_year_id' => $defaultCustomFinancialYear]);
  }


  function replicateBudget($post_array, $approval_id, $header_id, $default_custom_financial_year)
  {
    // Checking the bugdet tag level of the posted budget and retrive the budget record that has n-1 budget tag level
    // $budget_tag_id_of_new_budget = $post_array['fk_budget_tag_id'];
    $office_id = $post_array['fk_office_id'];
    $current_budget_fy = $post_array['budget_year'];
    $previous_budget_id = 0;
    $previous_budget = [];
    $budget_overlap_month = [];
    $budget_start_date = '';

    $custom_financial_year_start_month = $this->getCustomFinancialYearStartMonth($post_array['fk_office_id']);

    // Check if the budget is using a flexible FY and its the first budget
    $is_financial_year_switching  =  $this->isFinancialYearSwitching($default_custom_financial_year);

    if ($is_financial_year_switching) {
      $budget_start_date = $default_custom_financial_year['start_date'];
      $previous_budget = $this->getImmediatePreviousBudget($office_id, $current_budget_fy, $header_id, $custom_financial_year_start_month, $is_financial_year_switching);
      // log_message('error', "Switch FY");
      // log_message('error', json_encode($previous_budget));
    } else {
      $previous_budget = $this->getImmediatePreviousBudget($office_id, $current_budget_fy, $header_id, $custom_financial_year_start_month);
      $this->replicateBudgetLimit($post_array, $previous_budget);
      // log_message('error', "Standard FY");
      // log_message('error', json_encode($previous_budget));
    }

    $previous_budget_id = $previous_budget['budget_id'];
    $budget_overlap_month = $this->budgetOverlapMonth($header_id, $previous_budget, $budget_start_date);

    if (!empty($budget_overlap_month['overlap_months'])) {
      // log_message('error', json_encode($budget_overlap_month['overlap_months']));
      $this->insertPreviousBudgetItems($header_id, $previous_budget_id, $budget_overlap_month['overlap_months'], $is_financial_year_switching);
    }
  }

  /**
   * insert_previous_budget_items
   * 
   * Replicate budget from previous tag or on fy transitions
   * 
   * @author Nicodemus Karisa Mwambire
   * @authored_date 22nd June 2023
   * 
   * @param int $current_budget_id - Newly created budget id 
   * @param int $previous_budget_id - Immediate previous budget id
   * @param array $overlaps_month - Overlapping months incase of FY settings transitions
   * 
   * @return void
   */
  public function insertPreviousBudgetItems(int $current_budget_id, int $previous_budget_id, array $overlaps_month = [], bool $is_financial_year_switching = false): void
  {
    // Prepare the base query
    $builder = $this->read_db->table('budget_item bi');

    $builder->select(
      '
            bi.budget_item_id,
            jt.budget_item_detail_amount,
            jt.fk_budget_item_id,
            jt.fk_month_id,
            jt.fk_budget_item_detail_id as budget_item_detail_id,
            budget_item_quantity,
            budget_item_unit_cost,
            budget_item_often,
            budget_item_details,
            budget_item_total_cost,
            fk_expense_account_id,
            budget_item_description,
            fk_project_allocation_id,
            budget_item_detail_amount,
            bi.fk_status_id as fk_status_id,
            budget_item_marked_for_review'
    );

    $builder->join('JSON_TABLE(
            bi.budget_item_details,
            "$[*]" COLUMNS (
                budget_item_detail_amount DECIMAL(10, 2) PATH "$.budget_item_detail_amount",
                fk_budget_item_id INT PATH "$.fk_budget_item_id",
                fk_month_id INT PATH "$.fk_month_id",
                fk_budget_item_detail_id INT PATH "$.fk_budget_item_detail_id"
            )
        ) AS jt', 'bi.budget_item_id = jt.fk_budget_item_id', 'inner')
      ->where('bi.fk_budget_id', $previous_budget_id);

    // Add the conditional WHERE IN for fk_month_id if $overlaps_month is not empty
    if (count($overlaps_month) > 0) {
      $builder->whereIn('jt.fk_month_id', $overlaps_month);
    }

    // Execute the query and fetch results
    $query = $builder->get();

    $budget_item_details = [];

    if ($query->getNumRows() > 0) {
      $budget_item_details = $query->getResultArray();

      $budget_item_details_grouped = [];
      $new_json_budget_items = [];

      foreach ($budget_item_details as $budget_item_detail) {
        // Rebuild the JSON string
        $new_json_budget_items[$budget_item_detail['fk_budget_item_id']][] = [
          'fk_month_id' => $budget_item_detail['fk_month_id'],
          'fk_status_id' => null,
          'fk_approval_id' => null,
          'fk_budget_item_id' => $budget_item_detail['fk_budget_item_id'],
          'budget_item_detail_name' => null,
          'budget_item_detail_amount' => $budget_item_detail['budget_item_detail_amount'],
          'budget_item_detail_created_by' => null,
          'budget_item_detail_created_date' => null,
          'budget_item_detail_track_number' => null,
          'budget_item_detail_last_modified_by' => null
        ];

        $budget_item_details_grouped[$budget_item_detail['budget_item_id']]['budget_item'] = [
          'budget_item_quantity' => $budget_item_detail['budget_item_quantity'],
          'budget_item_unit_cost' => $budget_item_detail['budget_item_unit_cost'],
          'budget_item_often' => $budget_item_detail['budget_item_often'],
          'budget_item_details' => json_encode($new_json_budget_items[$budget_item_detail['fk_budget_item_id']]),
          'budget_item_total_cost' => $budget_item_detail['budget_item_total_cost'],
          'fk_expense_account_id' => $budget_item_detail['fk_expense_account_id'],
          'budget_item_description' => $budget_item_detail['budget_item_description'],
          'fk_project_allocation_id' => $budget_item_detail['fk_project_allocation_id'],
          'fk_status_id' => $budget_item_detail['fk_status_id'],
          'budget_item_marked_for_review' => $budget_item_detail['budget_item_marked_for_review'],
          'budget_item_id' => $budget_item_detail['budget_item_id']
        ];

        $budget_item_details_grouped[$budget_item_detail['budget_item_id']]['budget_item_detail'][$budget_item_detail['fk_month_id']] = $budget_item_detail['budget_item_detail_amount'];
      }

      foreach ($budget_item_details_grouped as $loop_budget_item_and_details) {
        // Insert budget item
        $budget_item_array['budget_item_name'] = $this->generateItemTrackNumberAndName('budget_item')['budget_item_name'];
        $budget_item_array['budget_item_track_number'] = $this->generateItemTrackNumberAndName('budget_item')['budget_item_track_number'];
        $budget_item_array['fk_budget_id'] = $current_budget_id;
        $budget_item_array['budget_item_quantity'] = $loop_budget_item_and_details['budget_item']['budget_item_quantity'];
        $budget_item_array['budget_item_unit_cost'] = $loop_budget_item_and_details['budget_item']['budget_item_unit_cost'];
        $budget_item_array['budget_item_often'] = $loop_budget_item_and_details['budget_item']['budget_item_often'];
        $budget_item_array['budget_item_total_cost'] = $loop_budget_item_and_details['budget_item']['budget_item_total_cost'];
        $budget_item_array['fk_expense_account_id'] = $loop_budget_item_and_details['budget_item']['fk_expense_account_id'];
        $budget_item_array['budget_item_description'] = $loop_budget_item_and_details['budget_item']['budget_item_description'];
        $budget_item_array['fk_project_allocation_id'] = $loop_budget_item_and_details['budget_item']['fk_project_allocation_id'];
        $budget_item_array['budget_item_details'] = $loop_budget_item_and_details['budget_item']['budget_item_details'];
        $budget_item_array['budget_item_created_by'] = $this->session->user_id ?? 1;
        $budget_item_array['budget_item_created_date'] = date('Y-m-d');
        $budget_item_array['budget_item_marked_for_review'] = $loop_budget_item_and_details['budget_item']['budget_item_marked_for_review'];
        $budget_item_array['fk_approval_id'] = 0;
        $budget_item_array['fk_status_id'] = $loop_budget_item_and_details['budget_item']['fk_status_id'];
        $budget_item_array['budget_item_source_id'] = $loop_budget_item_and_details['budget_item']['budget_item_id'];

        $budget_item_computed_total_cost = $budget_item_array['budget_item_quantity'] * $budget_item_array['budget_item_unit_cost'] * $budget_item_array['budget_item_often'];

        if (!$is_financial_year_switching && $loop_budget_item_and_details['budget_item']['budget_item_marked_for_review'] == 1) {
          $budget_item_array['fk_approval_id'] = $this->insertApprovalRecord('budget_item');
          $budget_item_array['fk_status_id'] = $this->initialItemStatus('budget_item');
        }

        // Insert into the database
        $this->write_db->table('budget_item')->insert($budget_item_array);

        $budget_item_id = $this->write_db->insertID();

        // Update the budget_item_detail
        $this->updateBudgetItemDetailsCol($budget_item_id);

        $budget_item_details_to_loop = $loop_budget_item_and_details['budget_item_detail'];
        $item_total_amount = 0;

        foreach ($budget_item_details_to_loop as $month_id => $amount) {
          $budget_item_detail_array = [
            'budget_item_detail_name' => $this->generateItemTrackNumberAndName('budget_item_detail')['budget_item_detail_name'],
            'budget_item_detail_track_number' => $this->generateItemTrackNumberAndName('budget_item_detail')['budget_item_detail_track_number'],
            'fk_month_id' => $month_id,
            'budget_item_detail_amount' => $amount,
            'fk_budget_item_id' => $budget_item_id
          ];

          $budget_item_detail_array_to_insert = $this->mergeWithHistoryFields('budget_item_detail', $budget_item_detail_array, false);
          $this->write_db->table('budget_item_detail')->insert($budget_item_detail_array_to_insert);

          $item_total_amount += $amount;
        }

        // Update if total cost differs
        if ($item_total_amount != $budget_item_computed_total_cost) {
          $update_item = [
            'budget_item_quantity' => 1,
            'budget_item_unit_cost' => $item_total_amount,
            'budget_item_often' => 1,
            'budget_item_total_cost' => $item_total_amount
          ];

          $this->write_db->table('budget_item')->where('budget_item_id', $budget_item_id)->update($update_item);

          $note = get_phrase('recomputed_buget_item_frequency', 'The budget item quantity, unit cost, and frequency have been recomputed due to partial spreading from the previous budget review');
          $this->messageLib->postNewMessage('budget_item', $budget_item_id, $note);
        }
      }
    }
  }

  /**
	 *update_budget_item_details_col():This is method when excuted updates the budget_item_details in the budget_items table.
	 * @author Livingstone Onduso:
	 * @Dated 11-11-2024
	 * @access public
	 * @return void
	 */
  private function updateBudgetItemDetailsCol(int $budget_item_id): void
  {
    // Get the budget_item_details to update the budget_item_id
    $builder = $this->write_db->table('budget_item');
    $builder->select('budget_item_details');
    $builder->where('budget_item_id', $budget_item_id);
    $query = $builder->get();

    $budget_item_details = $query->getRowArray()['budget_item_details'] ?? '';

    // Convert to array
    if (!empty($budget_item_details)) {
      $budget_item_details = json_decode($budget_item_details, true);

      $build_array = [];

      // Loop and update the item with the correct/currently inserted budget_item_id
      foreach ($budget_item_details as &$budget_item_detail) {
        $budget_item_detail['fk_budget_item_id'] = $budget_item_id;
        $build_array[] = $budget_item_detail;
      }

      // Update budget_item
      $data = ['budget_item_details' => json_encode($build_array)];

      $this->write_db->table('budget_item')
        ->where('budget_item_id', $budget_item_id)
        ->update($data);
    }
  }

  public function budgetOverlapMonth($budgetId, $previousBudget, $customYearStartDate = "")
  {
    $previousBudgetId = 0;
    $overlapMonthNumbers = [];

    if ($previousBudget['budget_id'] > 0) {
      $previousBudgetId = $previousBudget['budget_id'];

      // Get budget month order
      $newBudgetMonthOrder = $this->listBudgetMonthOrder($budgetId, $customYearStartDate);
      $previousBudgetMonthOrder = $this->listBudgetMonthOrder($previousBudget['budget_id']);

      // Find overlapping months
      $overlap = array_intersect($newBudgetMonthOrder, $previousBudgetMonthOrder);

      // Extract month numbers from overlapping dates
      foreach ($overlap as $date) {
        $overlapMonthNumbers[] = date('n', strtotime($date));
      }
    }

    $result = [
      'previous_budget_id' => $previousBudgetId,
      'overlap_months' => $overlapMonthNumbers
    ];

    return $result;
  }

  private function listBudgetMonthOrder($budget_id, $custom_year_start_date = "")
  {
    $budget_start_month_and_year = $this->getBudgetStartMonthAndYear($budget_id);
    $monthNumber = $budget_start_month_and_year['start_month']; //4; // April
    $year = $budget_start_month_and_year['budget_start_year']; //2023;

    // Create an array to store the first-day dates
    $firstDayDates = array();

    // Create a starting date for the first day of the specified month and year
    $date = new \DateTime("{$year}-{$monthNumber}-01");

    // Define the end date by adding one year
    $endDate = clone $date;
    $endDate->modify('+1 year');

    // Loop through the months and add the first day of each month to the array
    while ($date < $endDate) {
      $firstDayDates[] = $date->format('Y-m-d');
      $date->modify('+1 month'); // Move to the next month
    }

    if ($custom_year_start_date != "") {
      foreach ($firstDayDates as $key => $date) {
        // Check if the month is less than or equal to 6
        if ($date < $custom_year_start_date) {
          // Remove the date from the array
          unset($firstDayDates[$key]);
        }
      }
    }

    return $firstDayDates;
  }


  private function getBudgetStartMonthAndYear($budgetId)
  {
    // Queries in this method MUST use write_db handle. DO NOT CHANGE

    $budgetStartYear = '';
    $startMonth = 7;

    $budget = $this->write_db->table('budget')
      ->where('budget_id', $budgetId)
      ->get()
      ->getRow();

    if ($budget && $budget->fk_custom_financial_year_id > 0) {
      $customYear = $this->write_db->table('custom_financial_year')
        ->where('custom_financial_year_id', $budget->fk_custom_financial_year_id)
        ->get()
        ->getRow();

      if ($customYear) {
        $startMonth = $customYear->custom_financial_year_start_month;
      }
    }

    $budgetStartYear = $this->getBudgetStartYear($startMonth, $budget->budget_year);

    return [
      'budget_start_year' => $budgetStartYear,
      'start_month' => $startMonth
    ];
  }

  private function getBudgetStartYear($startMonth, $budget_year)
  {
    // $months = array();
    $budget_start_year = '20' . $budget_year;

    // Loop through the months, wrapping around from December to January if necessary
    for ($i = 0; $i < 12; $i++) {
      $month = ($startMonth + $i) % 12;
      // Adjust for 0-based array index
      if ($month === 0) {
        $month = 12;
      }
      $months[] = $month;

      if ($i > 0 && $month == 1) {
        $budget_year -= 1;
        $budget_start_year = '20' . $budget_year;
      }
    }

    return $budget_start_year;
  }


  private function replicateBudgetLimit($newBudgetArray, $previousBudgetArray)
  {

    $readDb = $this->read_db->table('budget_limit');  // Read DB table
    $writeDb = $this->write_db->table('budget_limit'); // Write DB table

    // Fetch limits from the previous budget
    $readDb->where('fk_budget_id', $previousBudgetArray['budget_id']);
    $budgetLimitQuery = $readDb->get();

    if ($budgetLimitQuery->getNumRows() > 0) {
      $budgetLimits = $budgetLimitQuery->getResultArray();
      $budgetLimitsData = [];

      foreach ($budgetLimits as $budgetLimit) {
        unset($budgetLimit['budget_limit_id']); // Remove ID for insert

        // Generate new tracking data
        $tracking = $this->generateItemTrackNumberAndName('budget_limit');

        // Prepare new budget limit data
        $budgetLimitsData[] = [
          'budget_limit_name' => $tracking['budget_limit_name'],
          'budget_limit_track_number' => $tracking['budget_limit_track_number'],
          'fk_budget_id' => $newBudgetArray['fk_budget_id'],
          'budget_limit_amount' => $budgetLimit['budget_limit_amount'],
          'fk_income_account_id' => $budgetLimit['fk_income_account_id'],
          'budget_limit_created_date' => date('Y-m-d'),
          'budget_limit_created_by' => session()->get('user_id'),
          'budget_limit_last_modified_date' => date('Y-m-d H:i:s'),
          'budget_limit_last_modified_by' => session()->get('user_id'),
          'fk_status_id' => $this->initialItemStatus('budget_limit')
        ];
      }

      // Ensure no duplicate records
      $writeDb->where('fk_budget_id', $newBudgetArray['fk_budget_id']);
      $newLimitCount = $writeDb->countAllResults();

      if ($newLimitCount == 0) {
        $writeDb->insertBatch($budgetLimitsData);
      }
    }
  }

  public function getImmediatePreviousBudget($officeId, $currentBudgetFy, $headerId, $startMonth = '', $isFinancialYearSwitching = false)
  {
    $builder = $this->read_db->table('budget');
    // Initialize default values
    $previousBudget = [
      'budget_tag_level' => 0,
      'budget_id' => 0,
      'budget_tag_id' => 0,
      'budget_fy_start_month' => 7
    ];

    // Select relevant columns
    $builder->select(['budget_tag_level', 'budget_id', 'budget_tag_id', 'fk_custom_financial_year_id']);
    $builder->where('budget.fk_office_id', $officeId);
    $builder->where('budget_id <', $headerId);
    $builder->orderBy('budget_id', 'DESC');
    $builder->limit(1);

    if (!$isFinancialYearSwitching) {
      if (!empty($startMonth) && $startMonth != 7) {
        $builder->join('custom_financial_year', 'custom_financial_year.custom_financial_year_id = budget.fk_custom_financial_year_id', 'left');
        $builder->where('custom_financial_year.custom_financial_year_start_month', $startMonth);
        $builder->select('custom_financial_year.custom_financial_year_start_month');
      }
      $builder->where('budget_year', $currentBudgetFy);
      $builder->orderBy('budget_tag_level', 'DESC');
    }

    // Join budget_tag table
    $builder->join('budget_tag', 'budget_tag.budget_tag_id = budget.fk_budget_tag_id', 'left');

    $previousBudgetQuery = $builder->get();

    if ($previousBudgetQuery->getNumRows() > 0) {
      $previousBudgetRow = $previousBudgetQuery->getRowArray();

      $previousBudget['budget_tag_level'] = $previousBudgetRow['budget_tag_level'];
      $previousBudget['budget_id'] = $previousBudgetRow['budget_id'];
      $previousBudget['budget_tag_id'] = $previousBudgetRow['budget_tag_id'];

      if (!empty($previousBudgetRow['fk_custom_financial_year_id'])) {
        $cfyBuilder = $this->read_db->table('custom_financial_year');
        $cfyBuilder->select('custom_financial_year_start_month');
        $cfyBuilder->where('custom_financial_year_id', $previousBudgetRow['fk_custom_financial_year_id']);

        $cfyQuery = $cfyBuilder->get();
        if ($cfyQuery->getNumRows() > 0) {
          $previousBudget['budget_fy_start_month'] = $cfyQuery->getRow()->custom_financial_year_start_month;
        }
      }
    }

    return $previousBudget;
  }


  private function isFinancialYearSwitching(array $defaultCustomFinancialYear): bool
  {
    if (empty($defaultCustomFinancialYear)) {
      return false;
    }

    $customFyId = $defaultCustomFinancialYear['id'];

    // Ensure we use the write_db connection as required
    $builder = $this->write_db->table('budget');

    $countOfBudgetWithCustomFy = $builder->where('fk_custom_financial_year_id', $customFyId)->countAllResults();

    return $countOfBudgetWithCustomFy == 1;
  }

  private function getCustomFinancialYearStartMonth($officeId)
  {
    $builder = $this->read_db->table('custom_financial_year');

    $customFinancialYear = $builder->select('custom_financial_year_start_month')
      ->where('custom_financial_year_is_default', 1)
      ->where('fk_office_id', $officeId)
      ->get()
      ->getRow();

    return $customFinancialYear ? $customFinancialYear->custom_financial_year_start_month : 7;
  }






  /**
   * getCurrentUnsignedOffBudget
   * 
   * Get the recent unsubmitted budget for an office
   * 
   * @author Nicodemus Karisa Mwambire 
   * @modfiedBy Livingstone Onduso on 31/01/2025
   * @authored_date 21st June 2023
   * 
   * @param int $office_id
   * 
   * @return array Last unsubmitted budget
   */

  function getCurrentUnsignedOffBudget(int $officeId): array
  {

    $maxApprovalIds = $this->statusLibrary->getMaxApprovalStatusId('budget', [$officeId]);
    $builder = $this->write_db->table('budget');
    $builder->select([
      'budget_id',
      'budget_tag_id',
      'budget_year',
      'budget_tag_name',
      'status_name'
    ]);
    $builder->where('budget.fk_office_id', $officeId);
    $builder->whereNotIn('budget.fk_status_id', $maxApprovalIds);
    $builder->join('budget_tag', 'budget_tag.budget_tag_id = budget.fk_budget_tag_id');
    $builder->join('status', 'status.status_id = budget.fk_status_id');
    $builder->orderBy('budget_id', 'DESC');

    $budgets = $builder->get();

    return $budgets->getNumRows() > 0 ? $budgets->getRowArray() : [];
  }

  function getBudgetIdBasedOnMonth($office_id, $reporting_month)
  {
    if((intval($office_id)) == 0){
      $financial_report_id = hash_id($office_id, 'decode');
      $builder = $this->read_db->table('financial_report');
      $builder->select('fk_office_id');
      $office_id = $builder->where('financial_report_id', $financial_report_id)->get()->getRow()->fk_office_id;
    }

    $custom_financial_year = $this->customFinancialYearLibrary->getDefaultCustomFinancialYearIdByOffice($office_id, true);
    $budget_tag_id = $this->budgetTagLibrary->getBudgetTagIdBasedOnReportingMonth($office_id, $reporting_month, $custom_financial_year)['budget_tag_id'];
    // log_message('error', json_encode($budget_tag_id));
    $budget_id = 0;

    $budget_year = get_fy($reporting_month);

    $builder = $this->read_db->table('budget');
    $builder->select('budget_id');
    $builder->where(
      array(
      'fk_budget_tag_id' => $budget_tag_id,
      'fk_office_id' => $office_id, 'budget_year' => $budget_year
      )
    );
    $budget_id_obj = $builder->get();
    if ($budget_id_obj->getNumRows() > 0) {
      $budget_id =  $budget_id_obj->getRow()->budget_id;
    }

    return $budget_id;
  }

}
