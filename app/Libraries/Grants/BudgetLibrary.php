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

    function __construct()
    {
        parent::__construct();

        $this->budgetModel = new BudgetModel();
        $this->customFinancialYearLibrary = new CustomFinancialYearLibrary();
        $this->table = 'budget';
        $this->budgetTagLibrary = new BudgetTagLibrary();
        $this->voucherLibrary = new VoucherLibrary();
        $this->statusLibrary = new StatusLibrary();
    }


  function getBudgetByOfficeCurrentTransactionDate($office_id){

    $next_vouching_date = $this->voucherLibrary->getVoucherDate($office_id);
    $custom_financial_year = $this->customFinancialYearLibrary->getDefaultCustomFinancialYearIdByOffice($office_id);
    $this->evaluateCustomFinancialYear($office_id, $next_vouching_date, $custom_financial_year);
   
    $start_month = $custom_financial_year['custom_financial_year_id'] != NULL && !$custom_financial_year['custom_financial_year_is_active'] ? $custom_financial_year['custom_financial_year_start_month'] : 7;
    $custom_financial_year_id = $custom_financial_year['custom_financial_year_id'] != NULL ? $custom_financial_year['custom_financial_year_id'] : 0;

    $fy = calculateFinancialYear($next_vouching_date, $start_month);

    $mfr_budget_tag_id = $this->budgetTagLibrary->getBudgetTagIdBasedOnReportingMonth($office_id, $next_vouching_date, $custom_financial_year)['budget_tag_id'];
    $max_budget_approval_ids = $this->statusLibrary->getMaxApprovalStatusId('budget');

    $builder = $this->read_db->table("budget");
    if($custom_financial_year['custom_financial_year_id'] != NULL && !$custom_financial_year['custom_financial_year_is_active']){
      $builder->where(array('fk_custom_financial_year_id' => $custom_financial_year_id));
    }
    $builder->where(array('fk_office_id' => $office_id,'budget_year' => $fy,'fk_budget_tag_id' => $mfr_budget_tag_id));
    $builder->whereIn('budget.fk_status_id', $max_budget_approval_ids);

    $budget_obj = $builder->get();
    $budget = [];
    if($budget_obj->getNumRows() > 0){
      $budget = $budget_obj->getRowArray();
    }
    
    return $budget;
  }

  function evaluateCustomFinancialYear($office_id, $next_vouching_date, &$custom_financial_year){

    $oldest_declined_financial_report = $this->oldestDeclinedFinancialReport($office_id);

    if(!empty($oldest_declined_financial_report)){
      if($oldest_declined_financial_report['custom_financial_year_id'] == null){
        $custom_financial_year = ['custom_financial_year_start_month' => 7, 'custom_financial_year_id' => NULL, 'custom_financial_year_is_active' => 0, 'custom_financial_year_reset_date' => NULL];
      }else{
        $custom_financial_year = $this->customFinancialYearLibrary->getCustomFinancialYearById($oldest_declined_financial_report['custom_financial_year_id']);
      }
    }

    // Check if the vouching period is still behind the default custom fy reset date
    $transaction_period_behind_default_custom_fy_reset_date = $this->customFinancialYearLibrary->transactionPeriodBehindDefaultCustomFyResetDate($next_vouching_date,$custom_financial_year);

    if($transaction_period_behind_default_custom_fy_reset_date){
      $custom_financial_year = ['custom_financial_year_start_month' => 7, 'custom_financial_year_id' => NULL, 'custom_financial_year_is_active' => 0, 'custom_financial_year_reset_date' => NULL];
      
      if($custom_financial_year['custom_financial_year_id'] != null){
        $custom_financial_year = $this->customFinancialYearLibrary->getPreviousCustomFinancialYearByCurrentId($office_id, $custom_financial_year['custom_financial_year_id']);
      }
    }

  }

  function oldestDeclinedFinancialReport($office_id){

    $decline_status_ids = $this->statusLibrary->getDeclineStatusIds('financial_report');

    $builder = $this->read_db->table('financial_report');
    $builder->select(array('financial_report.fk_office_id as office_id','financial_report_id','budget_id', 'fk_custom_financial_year_id as custom_financial_year_id'));
    $builder->whereIn('financial_report.fk_status_id', $decline_status_ids);
    $builder->where(array('financial_report.fk_office_id' => $office_id));
    $builder->orderBy('financial_report_month ASC');
    $builder->join('budget','budget.budget_id=financial_report.fk_budget_id');
    $financial_report_obj = $builder->get();
      
    $oldest_declined_report = [];
      
    if($financial_report_obj->getNumRows() > 0){
        $oldest_declined_report = $financial_report_obj->getRowArray();
    }
      
    return  $oldest_declined_report;
  }

  function changeFieldType(): array {
    $change['budget_year']['field_type'] = 'varchar';
    return $change;
  }

  function listTableVisibleColumns(): array{
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

  function customTableJoin(\CodeIgniter\Database\BaseBuilder $builder): void{
    // $builder->join('month','month.month_number=custom_financial_year.custom_financial_year_start_month', 'LEFT');
    $builder->join('custom_financial_year','custom_financial_year.custom_financial_year_id=budget.fk_custom_financial_year_id', 'LEFT');
  }

  function getBudgetIdBasedOnMonth($office_id, $reporting_month)
  {

    // $this->load->model('custom_financial_year_model');
    $custom_financial_year = $this->customFinancialYearLibrary->getDefaultCustomFinancialYearIdByOffice($office_id, true);
    $budget_tag_id = $this->budgetTagLibrary->getBudgetTagIdBasedOnReportingMonth($office_id, $reporting_month, $custom_financial_year)['budget_tag_id'];
    // log_message('error', json_encode($budget_tag_id));
    $budget_id = 0;

    $budget_year = get_fy($reporting_month);

    $builder = $this->read_db->table('budget');
    $builder->where(array(
      'fk_budget_tag_id' => $budget_tag_id,
      'fk_office_id' => $office_id, 'budget_year' => $budget_year
    ));

    $budget_id_obj = $builder->get('budget');

    if ($budget_id_obj->getNumRows() > 0) {
      $budget_id =  $budget_id_obj->getRow()->budget_id;
    }

    return $budget_id;
  }

}