<?php

namespace App\Controllers\Web\Grants;

use App\Controllers\Web\WebController;
use App\Libraries\System\SearchBuilderLibrary;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class FinancialReport extends WebController
{

    private $financialReportLibrary;
    private $varianceCommentLibrary;
    private $budgetLibrary;
    public $userLibrary;
    private $officeLibrary;
    private $officeBankLibrary;
    private $customFinancialYearLibrary;
    private $budgetTagLibrary;
    private $voucherLibrary;
    private $attachmentLibrary;
    private $checkbookLibrary;
    private $journalLibrary;
    private $grantsLibrary;
    protected $statusLibrary;
    private $searchBuilderLibrary;
    // private $config;

    
    
    function __construct()
    {
        $this->financialReportLibrary = new \App\Libraries\Grants\FinancialReportLibrary();
        $this->varianceCommentLibrary = new \App\Libraries\Grants\VarianceCommentLibrary();
        $this->budgetLibrary = new \App\Libraries\Grants\BudgetLibrary();
        $this->userLibrary = new \App\Libraries\Core\UserLibrary();
        $this->officeLibrary = new \App\Libraries\Core\OfficeLibrary();
        $this->officeBankLibrary = new \App\Libraries\Grants\OfficeBankLibrary();
        $this->customFinancialYearLibrary = new \App\Libraries\Grants\CustomFinancialYearLibrary();
        $this->budgetTagLibrary = new \App\Libraries\Grants\BudgetTagLibrary();
        $this->voucherLibrary = new \App\Libraries\Grants\VoucherLibrary();
        $this->attachmentLibrary = new \App\Libraries\Core\AttachmentLibrary();
        $this->checkbookLibrary = new \App\Libraries\Grants\ChequeBookLibrary();
        $this->journalLibrary = new \App\Libraries\Grants\JournalLibrary();
        $this->statusLibrary = new \App\Libraries\Core\StatusLibrary();
        $this->grantsLibrary = new \App\Libraries\System\GrantsLibrary();
        $this->searchBuilderLibrary = new SearchBuilderLibrary();
        // $this->config = config('GrantsConfig');
        
        
        
    }
    function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

    }

    private function incomeAccounts($office_ids, $project_ids = [])
    {
        // Should be moved to Income accounts library
      return $this->financialReportLibrary->incomeAccounts($office_ids, $project_ids);
    }

    private function monthIncomeAccountReceipts($office_ids, $start_date_of_month, $project_ids = [], $office_bank_ids = [])
    {
      return $this->financialReportLibrary->monthIncomeAccountReceipts($office_ids, $start_date_of_month, $project_ids, $office_bank_ids);
    }

    private function monthIncomeAccountExpenses($office_ids, $start_date_of_month, $project_ids = [], $office_bank_ids = [])
    {
      return $this->financialReportLibrary->monthIncomeAccountExpenses($office_ids, $start_date_of_month, $project_ids, $office_bank_ids);
    }

    private function monthIncomeOpeningBalance($office_ids, $start_date_of_month, $project_ids = [], $office_bank_ids = [])
    {
      return $this->financialReportLibrary->monthIncomeOpeningBalance($office_ids, $start_date_of_month, $project_ids, $office_bank_ids);
    }

    private function fundBalanceReport($office_ids, $start_date_of_month, $project_ids = [], $office_bank_ids = [])
    {
  
      $income_accounts =  $this->financialReportLibrary->incomeAccounts($office_ids, $project_ids, $office_bank_ids);
  
      $all_accounts_month_opening_balance = $this->monthIncomeOpeningBalance($office_ids, $start_date_of_month, $project_ids, $office_bank_ids);
      $all_accounts_month_income = $this->monthIncomeAccountReceipts($office_ids, $start_date_of_month, $project_ids, $office_bank_ids);
      $all_accounts_month_expense = $this->monthIncomeAccountExpenses($office_ids, $start_date_of_month, $project_ids, $office_bank_ids);
  
      $report = array();
  
      $month_cancelled_opening_oustanding_cheques = $this->financialReportLibrary->getMonthCancelledOpeningOutstandingCheques($office_ids, $start_date_of_month, $project_ids, $office_bank_ids);
      $past_months_cancelled_opening_oustanding_cheques = $this->financialReportLibrary->getMonthCancelledOpeningOutstandingCheques($office_ids, $start_date_of_month, $project_ids, $office_bank_ids, 'past_months');
  
      $itr = 0;
  
      foreach ($income_accounts as $account) {
  
        $month_opening_balance = isset($all_accounts_month_opening_balance[$account['income_account_id']]) ? $all_accounts_month_opening_balance[$account['income_account_id']] : 0;
        $month_income = isset($all_accounts_month_income[$account['income_account_id']]) ? $all_accounts_month_income[$account['income_account_id']] : 0;
        $month_expense = isset($all_accounts_month_expense[$account['income_account_id']]) ? $all_accounts_month_expense[$account['income_account_id']] : 0;
  
        if ($month_opening_balance == 0 && $month_income == 0 && $month_expense == 0) {
          continue;
        }
  
        if ($itr == 0) {
          $month_opening_balance = $month_opening_balance + $past_months_cancelled_opening_oustanding_cheques;
          $month_income = $month_income + $month_cancelled_opening_oustanding_cheques;
        }
  
        $report[] = [
          'account_id' => $account['income_account_id'],
          'account_name' => $account['income_account_name'],
          'month_opening_balance' => $month_opening_balance,
          'month_income' => $month_income,
          'month_expense' => $month_expense,
          'month_closing_balance' => ($month_opening_balance + $month_income - $month_expense)
        ];
  
        $itr++;
      }
  
      //If the mfr has been submitted. Adjust the child support fund by taking away exact amount of bounced opening chqs This code was added during enhancement for cancelling opening outstanding chqs
  
      if ($this->financialReportLibrary->checkIfFinancialReportIsSubmitted($office_ids, $start_date_of_month) == true) {
  
        $sum_of_bounced_cheques = $this->financialReportLibrary->getTotalSumOfBouncedOpeningCheques($office_ids, $start_date_of_month);
  
        $total_amount_bounced = isset($sum_of_bounced_cheques[0]['opening_outstanding_cheque_amount']) ? $sum_of_bounced_cheques[0]['opening_outstanding_cheque_amount'] : 0;
        $bounced_date = isset($sum_of_bounced_cheques[0]['opening_outstanding_cheque_cleared_date']) ? $sum_of_bounced_cheques[0]['opening_outstanding_cheque_cleared_date'] : NULL;
        $mfr_report_month = date('Y-m-t', strtotime($start_date_of_month));
  
        if ($total_amount_bounced > 0 &&  $bounced_date > $mfr_report_month && sizeof($report) > 0) {
  
          $month_opening = $report[0]['month_opening_balance'];
  
          $report[0]['month_opening_balance'] = $month_opening - $total_amount_bounced;
        }
  
  
      }
  
      return $report;
    }

    private function _proofOfCash($office_ids, $reporting_month, $project_ids = [], $office_bank_ids = [])
    {
      // log_message('error', json_encode(['office_ids' => $office_ids, 'reporting_month' => $reporting_month, 'project_ids' => $project_ids, 'office_bank_ids' => $office_bank_ids]));
  
      $cash_at_bank = $this->financialReportLibrary->computeCashAtBank($office_ids, $reporting_month, $project_ids, $office_bank_ids);
      $cash_at_hand = $this->financialReportLibrary->computeCashAtHand($office_ids, $reporting_month, $project_ids, $office_bank_ids);
  
      $proof_of_cash = ['cash_at_bank' => $cash_at_bank, 'cash_at_hand' => $cash_at_hand];
  
      // log_message('error', json_encode($proof_of_cash));
  
      return $proof_of_cash;
    }

    private function bankReconciliation($office_ids, $reporting_month, $multiple_offices_report, $multiple_projects_report, $project_ids = [], $office_bank_ids = [])
    {
  
      $bank_statement_date = $this->bankStatementDate($office_ids, $reporting_month, $multiple_offices_report, $multiple_projects_report);
      $bank_statement_balance = $this->bankStatementBalance($office_ids, $reporting_month, $project_ids, $office_bank_ids);
  
      $book_closing_balance = $this->financialReportLibrary->computeCashAtBank($office_ids, $reporting_month, $project_ids, $office_bank_ids); //$this->_book_closing_balance($office_ids,$reporting_month);
  
      $month_outstanding_cheques = $this->sumOfOutstandingChequesAndTransits($office_ids, $reporting_month, 'expense', 'bank_contra', 'bank', $project_ids, $office_bank_ids);
  
  
  
      $month_transit_deposit = $this->sumOfOutstandingChequesAndTransits($office_ids, $reporting_month, 'income', 'cash_contra', 'bank', $project_ids, $office_bank_ids); //$this->_deposit_in_transit($office_ids,$reporting_month);
      $bank_reconciled_balance = $bank_statement_balance - $month_outstanding_cheques + $month_transit_deposit;
  
      $is_book_reconciled = false;
  
      if (round($bank_reconciled_balance, 2) == round($book_closing_balance, 2)) {
        $is_book_reconciled = true;
      }
  
      return [
        'bank_statement_date' => $bank_statement_date,
        'bank_statement_balance' => $bank_statement_balance,
        'book_closing_balance' => $book_closing_balance,
        'month_outstanding_cheques' => $month_outstanding_cheques,
        'month_transit_deposit' => $month_transit_deposit,
        'bank_reconciled_balance' => $bank_reconciled_balance,
        'is_book_reconciled' => $is_book_reconciled
      ];
    }

    function bankStatementDate($office_ids, $reporting_month, $multiple_offices_report, $multiple_projects_report)
    {
  
      $reconciliation_reporting_month = date('Y-m-t', strtotime($reporting_month));
  
      if (!$multiple_offices_report || !$multiple_projects_report) {
        $builder = $this->read_db->table('reconciliation');
        $builder->select(array('financial_report_month'));
        $builder->where(array(
          'fk_office_id' => $office_ids[0],
          'financial_report_month' => date('Y-m-t', strtotime($reporting_month))
        ));
        $builder->join('financial_report', 'financial_report.financial_report_id=reconciliation.fk_financial_report_id');
        $reconciliation_reporting_month_obj = $builder->get();
  
        if ($reconciliation_reporting_month_obj->getNumRows() > 0) {
          $reconciliation_reporting_month = $reconciliation_reporting_month_obj->getRow()->financial_report_month;
        }
      } else {
        $reconciliation_reporting_month = "This field cannot be populated for multiple offices or bank accounts report";
      }
  
      return $reconciliation_reporting_month;
    }

    function bankStatementBalance($office_ids, $reporting_month, $project_ids = [], $office_bank_ids = [])
    {
  
      $financial_report_statement_amount = 0;
      $builder = $this->read_db->table('financial_report');
      $builder->selectSum('reconciliation_statement_balance');
      $builder->whereIn('financial_report.fk_office_id', $office_ids);
      $builder->where(array('financial_report_month' => date('Y-m-01', strtotime($reporting_month))));
      $builder->join('reconciliation', 'reconciliation.fk_financial_report_id=financial_report.financial_report_id');
  
      $builder->groupBy(array('financial_report_month'));
  
      if (count($project_ids) > 0) {
        $builder->whereIn('project_allocation.fk_project_id', $project_ids);
        $builder->join('office_bank', 'office_bank.office_bank_id=reconciliation.fk_office_bank_id');
        $builder->join('office_bank_project_allocation', 'office_bank_project_allocation.fk_office_bank_id=office_bank.office_bank_id');
        $builder->join('project_allocation', 'project_allocation.project_allocation_id=office_bank_project_allocation.fk_project_allocation_id');
      }
  
      if (!empty($office_bank_ids)) {
        $builder->whereIn('reconciliation.fk_office_bank_id', $office_bank_ids);
      }
  
      $financial_report_statement_amount_obj = $builder->get();
  
      if ($financial_report_statement_amount_obj->getNumRows() > 0) {
        $financial_report_statement_amount = $financial_report_statement_amount_obj->getRow()->reconciliation_statement_balance;
      }
  
      return $financial_report_statement_amount;
    }

    function sumOfOutstandingChequesAndTransits($office_ids, $reporting_month, $transaction_type, $contra_type, $voucher_type_account_code, $project_ids = [], $office_bank_ids = [])
    {
  
      // return array_sum(array_column($this->financial_report_model->list_oustanding_cheques_and_deposits($office_ids, $reporting_month, $transaction_type, $contra_type, $voucher_type_account_code, $project_ids, $office_bank_ids), 'voucher_detail_total_cost'));
  
      $all_outsanding_chqs = $this->financialReportLibrary->listOustandingChequesAndDeposits($office_ids, $reporting_month, $transaction_type, $contra_type, $voucher_type_account_code, $project_ids, $office_bank_ids);
  
      // log_message('error', json_encode($all_outsanding_chqs));
  
      $removed_bounced_openning_oustanding_cheques = [];
  
      /*Check if the mfr has been submitted and if so treat the all oustanding cheques as before the adjustemnt of 
          opening oustanding cheques after cancelled. Othewise consider the undjustment by reseting the amount to zero*/
  
      $mfr_submitted_check = $this->financialReportLibrary->checkIfFinancialReportIsSubmitted($office_ids, $reporting_month);
  
      if ($mfr_submitted_check == true) {
  
        $removed_bounced_openning_oustanding_cheques  = $all_outsanding_chqs;
      } else {
        foreach ($all_outsanding_chqs as $all_outsanding_chq) {
  
          if ($all_outsanding_chq['voucher_id'] == 0 && ($all_outsanding_chq['bounce_flag'] == 1 && $all_outsanding_chq['voucher_cleared_month'] < $reporting_month)) {
  
            $all_outsanding_chq['voucher_detail_total_cost'] = 0;
  
            $removed_bounced_openning_oustanding_cheques[] = $all_outsanding_chq;
          } else {
            $removed_bounced_openning_oustanding_cheques[] = $all_outsanding_chq;
          }
        }
      }
  
      //return $removed_bounced_openning_oustanding_cheques;
      return array_sum(array_column($removed_bounced_openning_oustanding_cheques, 'voucher_detail_total_cost'));
    }

    //Ask if this is needed or not
    private function listClearedEffects($office_ids, $reporting_month, $transaction_type, $contra_type, $voucher_type_account_code, $project_ids = [], $office_bank_ids = [])
    {
  
      return $this->financialReportLibrary->listClearedEffects($office_ids, $reporting_month, $transaction_type, $contra_type, $voucher_type_account_code, $project_ids, $office_bank_ids);
    }
    
    //ask if this is needed or not
    private function expenseReport($office_ids, $reporting_month, $project_ids = [], $office_bank_ids = [])
    {
  
      $expense_account_grid = [];
      
      $income_grouped_expense_accounts = $this->incomeGroupedExpenseAccounts($office_ids);
  
      $month_expense = $this->financialReportLibrary->monthExpenseByExpenseAccount($office_ids, $reporting_month, $project_ids, $office_bank_ids);
      $month_expense_to_date = $this->financialReportLibrary->expenseToDateByExpenseAccount($office_ids, $reporting_month, $project_ids, $office_bank_ids);
      $month_and_to_date_budget = $this->financialReportLibrary->budgetToDateByExpenseAccount($office_ids, $reporting_month, $project_ids, $office_bank_ids);
  
      $expense_account_comment = $this->expenseAccountComment($office_ids, $reporting_month);
  
      $budget_to_date = isset($month_and_to_date_budget['to_date']) ? $month_and_to_date_budget['to_date'] : 0 ;
      $month_budget = isset($month_and_to_date_budget['month']) ? $month_and_to_date_budget['month'] : 0;
      // $budget_variance = $this->_budget_variance_by_expense_account($office_ids,$reporting_month);
      // $budget_variance_percent = $this->_budget_variance_percent_by_expense_account($office_ids,$reporting_month);   
  
      // log_message('error', json_encode($income_grouped_expense_accounts));
      
      foreach ($income_grouped_expense_accounts as $income_account_id => $income_account) {
        $check_sum = 0;
        foreach ($income_account['expense_accounts'] as $expense_account) {
          $income_account_id =  $income_account['income_account']['income_account_id'];
          $expense_account_id = $expense_account['expense_account_id'];
  
          $expense_account_grid[$income_account_id]['income_account'] = $income_account['income_account'];
          $expense_account_grid[$income_account_id]['expense_accounts'][$expense_account['expense_account_id']]['expense_account'] = $expense_account;
          $expense_account_grid[$income_account_id]['expense_accounts'][$expense_account['expense_account_id']]['month_expense'] = isset($month_expense[$income_account_id][$expense_account_id]) ? $month_expense[$income_account_id][$expense_account_id] : 0;
          $expense_account_grid[$income_account_id]['expense_accounts'][$expense_account['expense_account_id']]['month_budget'] = isset($month_budget[$income_account_id][$expense_account_id]) ? $month_budget[$income_account_id][$expense_account_id] : 0;
          $expense_account_grid[$income_account_id]['expense_accounts'][$expense_account['expense_account_id']]['month_expense_to_date'] = isset($month_expense_to_date[$income_account_id][$expense_account_id]) ? $month_expense_to_date[$income_account_id][$expense_account_id] : 0;
          $expense_account_grid[$income_account_id]['expense_accounts'][$expense_account['expense_account_id']]['budget_to_date'] = isset($budget_to_date[$income_account_id][$expense_account_id]) ? $budget_to_date[$income_account_id][$expense_account_id] : 0;
          //$expense_account_grid[$income_account_id]['expense_accounts'][$expense_account['expense_account_id']]['budget_variance'] = $budget_variance;
          //$expense_account_grid[$income_account_id]['expense_accounts'][$expense_account['expense_account_id']]['budget_variance_percent'] = $budget_variance_percent;
          $expense_account_grid[$income_account_id]['expense_accounts'][$expense_account['expense_account_id']]['expense_account_comment'] = isset($expense_account_comment[$income_account_id][$expense_account_id]) ? $expense_account_comment[$income_account_id][$expense_account_id] : ''; //$expense_account_comment;
  
          $check_sum += $expense_account_grid[$income_account_id]['expense_accounts'][$expense_account['expense_account_id']]['month_expense_to_date'] +  $expense_account_grid[$income_account_id]['expense_accounts'][$expense_account['expense_account_id']]['budget_to_date'];
        }
        $expense_account_grid[$income_account_id]['check_sum'] = $check_sum;
      }
  
      // log_message('error', json_encode($expense_account_grid));
  
      return $expense_account_grid;
    }

    function postExpenseAccountComment()
    {
      echo $this->varianceCommentLibrary->add();
    }

    //ask if this is needed and if I can add the new methods to the corrsponding library page
    function getExpenseAccountComment()
    {
  
      $post = $this->request->getPost();
  
      // log_message('error', json_encode($post));
  
      $comment = "";
  
      if (isset($post['expense_account_id'])) {
  
        $expense_account_id = $post['expense_account_id'];
        $office_id = $post['office_id'];
        $reporting_month = $post['reporting_month'];
        $report_id = $post['report_id'];

        // log_message('error', json_encode($office_id));
        // log_message('error', intval($office_id));
        //get the office id
        if((intval($office_id)) == 0){
          $decoded_financial_id = hash_id($office_id, 'decode');
          $office_id = $this->getOfficeId2($decoded_financial_id);
        }
        
        
        $budget_id = $this->budgetLibrary->getBudgetIdBasedOnMonth($office_id, $reporting_month);
        $comment =  $this->varianceCommentLibrary->getExpenseAccountComment($expense_account_id, $budget_id, $report_id);
      }
  
      echo $comment;
    }

    function incomeGroupedExpenseAccounts($office_ids)
    {
      $income_accounts = $this->incomeAccounts($office_ids);
  
      $expense_accounts = [];
  
      foreach ($income_accounts as $income_account) {
  
        $expense_accounts[$income_account['income_account_id']]['income_account'] = $income_account;
        $builder = $this->read_db->table('expense_account');
        $builder->select(array('expense_account_id', 'expense_account_code', 'expense_account_name'));
  
        $expense_accounts[$income_account['income_account_id']]['expense_accounts'] = $builder->where(
          'fk_income_account_id' , $income_account['income_account_id'])->get()->getResultArray();
      }
  
      return $expense_accounts;
    }

    function budgetVarianceByExpenseAccount($office_ids, $reporting_month)
    {
      return 150;
    }

    function budgetVariancePercentByExpenseAccount($office_ids, $reporting_month)
    {
      return 0.65;
    }

    //ask if this is needed
    function expenseAccountComment($office_ids, $reporting_month)
    {
  
      $office_id = $office_ids[0];
      $budget_id = $this->budgetLibrary->getBudgetIdBasedOnMonth($office_id, $reporting_month);
      $report_id = hash_id($this->id, 'decode');
      return $this->varianceCommentLibrary->getAllExpenseAccountComment($budget_id, $report_id);
    }

    //ask if this is needed
    function financialReportOfficeHierarchy($reporting_month)
    {
      $user_office_hierarchy = $this->userLibrary->userHierarchyOffices($this->session->user_id, true);
  
      // Remove offices with a financial reporting in the selected reporting month
  
      $user_hierarchy_offices_with_report = $this->userHierarchyOfficesWithFinancialReportForSelectedMonth($reporting_month);
      //print_r($user_hierarchy_offices_with_report);exit;
      foreach ($user_office_hierarchy as $office_context => $offices) {
        foreach ($offices as $key => $office) {
          if (is_array($office) && isset($office['office_id']) && !in_array($office['office_id'], $user_hierarchy_offices_with_report)) {
            unset($user_office_hierarchy[$office_context][$key]);
          }
        }
      }
  
      
      if ($this->config->only_combined_center_financial_reports) {
        $centers = $user_office_hierarchy[$this->userLibrary->getLowestOfficeContext()->context_definition_name];
        unset($user_office_hierarchy);
        $user_office_hierarchy[$this->userLibrary->getLowestOfficeContext()->context_definition_name] = $centers;
      }
  
      return $user_office_hierarchy;
    }

    private function userHierarchyOfficesWithFinancialReportForSelectedMonth($reporting_month)
    {
      $context_ungrouped_user_hierarchy_offices = $this->userLibrary->userHierarchyOffices($this->session->user_id);
  
      $offices_ids = array_column($context_ungrouped_user_hierarchy_offices, 'office_id');
      $office_builder = $this->read_db->table('financial_report');
      $office_builder->select('fk_office_id');
      $office_builder->whereIn('fk_office_id', $offices_ids);
      $office_builder->where('financial_report_month' , $reporting_month);
      $office_builder_result = $office_builder->get();
      $office_ids_with_report = $office_builder_result->getResultArray();

      // $this->read_db->select('fk_office_id');
      // $this->read_db->where_in('fk_office_id', $offices_ids);
      // $office_ids_with_report = $this->read_db->get_where('financial_report', array('financial_report_month' => $reporting_month))->result_array();
  
      return array_column($office_ids_with_report, 'fk_office_id');
    }

    function financialReportInformation($report_id)
    {
  
      $additional_information = $this->financialReportLibrary->financialReportInformation($report_id);
      // print_r($additional_information);exit;
  
      if ((isset($_POST['office_ids']) && isset($_POST['reporting_month']) && count($_POST['office_ids']) > 0)) {
        $additional_information = $this->financialReportLibrary->financialReportInformation($report_id, $_POST['office_ids'], $_POST['reporting_month']);
      }
      
      $reporting_month = $additional_information[0]['financial_report_month'];
  
      $account_system_id = $additional_information[0]['account_system_id'];
  
      $office_ids = array_column($additional_information, 'office_id');
  
      
      $multiple_offices_report = false;
      $multiple_projects_report = false;
  
      if (count($office_ids) == 1) {
        $office_builder = $this->read_db->table("office_bank");
        $office_builder->select('fk_office_id');
        $office_builder->where('fk_office_id' , $office_ids[0]);
        $office_results = $office_builder->get(); 
        $count_of_office_banks = $office_results->getNumRows();
        // $count_of_office_banks = $this->read_db->get_where('office_bank', array('fk_office_id', $office_ids[0]))->num_rows();
  
        if ((isset($_POST['project_ids']) && count($_POST['project_ids']) == $count_of_office_banks) || ($count_of_office_banks > 1 && !isset($_POST['project_ids']))) {
          $multiple_projects_report = true;
        }
      }
  
      $office_names = implode(', ', array_column($additional_information, 'office_name'));
  
      if (count($additional_information) > 1) {
        // Multiple Office
        $multiple_offices_report = true;
      }
  
      return [
        'office_names' => $office_names,
        'reporting_month' => $reporting_month,
        'office_ids' => $office_ids,
        'multiple_offices_report' => $multiple_offices_report,
        'multiple_projects_report' => $multiple_projects_report,
        'status_id' => $additional_information[0]['status_id'],
        'account_system_id' => $account_system_id
        //'test'=>$additional_information,
      ];
    }

    //ask if this is needed
    function getMonthActiveProjects($office_ids, $reporting_month, $show_active_only = false)
    {
  
      return $this->financialReportLibrary->getMonthActiveProjects($office_ids, $reporting_month);
    }

    //ask if this is needed
    function getOfficeBanks($office_ids, $reporting_month, $project_ids = [], $office_bank_ids = [])
    {
  
      // $this->load->model('office_bank_model');
  
      $office_banks = $this->financialReportLibrary->getOfficeBanks($office_ids, $project_ids, $office_bank_ids);
    
      // log_message('error', json_encode($office_banks));
      $office_banks_array = [];
  
      $cnt = 0;
      for($i = 0; $i < count($office_banks); $i++){
        $is_office_bank_obselete = $this->officeBankLibrary->isOfficeBankObselete($office_banks[$i]['office_bank_id'], $reporting_month);
        
        if(!$is_office_bank_obselete){
          // unset($office_banks[$i]);
          $office_banks_array[$cnt] = $office_banks[$i];
          $cnt++;
        }
      }
  
      
      
      return $office_banks_array;
    }

    //find out if this is needed
    function hasSubmittedReportAhead($report)
    {
  
      $reporting_month = $report['reporting_month'];
      $office_id = $report['office_ids'][0];
  
      // log_message('error', json_encode($reporting_month));
  
      $has_submitted_report_ahead = false;
      $financial_report_initial_status = $this->statusLibrary->initialItemStatus('financial_report');
  
      $financial_report_builder = $this->read_db->table("financial_report");
      $financial_report_builder->where(array(
        'financial_report_month > ' => $reporting_month,
        'fk_status_id<>' => $financial_report_initial_status, 'fk_office_id' => $office_id
      ));
      $count_all_results = $financial_report_builder->countAllResults();

      // $this->read_db->where(array(
      //   'financial_report_month > ' => $reporting_month,
      //   'fk_status_id<>' => $financial_report_initial_status, 'fk_office_id' => $office_id
      // ));
      // $this->read_db->from('financial_report');
      // $count_all_results = $this->read_db->count_all_results();
  
      if ($count_all_results > 0) {
        $has_submitted_report_ahead = true;
      }
  
      return  $has_submitted_report_ahead;
    }

    function updateFinancialReportBudgetId($report_id, $office_id)
    {
  
      $budget_id = 0;
      $financial_report_builder = $this->read_db->table('financial_report');
      $financial_report_builder->select('fk_budget_id');
      $financial_report_builder->where('financial_report_id' , $report_id);
      $financial_report_results = $financial_report_builder->get(); 
      $budget_id_result = $financial_report_results->getRow();
      $budget_id = $budget_id_result->fk_budget_id;

      // $this->read_db->where(array('financial_report_id' => $report_id));
      // $budget_id = $this->read_db->get('financial_report')->row()->fk_budget_id;
  
      if ($budget_id == NULL || $budget_id == 0) {
        // $this->load->model('budget_model');
        $current_budget = $this->budgetLibrary->getBudgetByOfficeCurrentTransactionDate($office_id);
  
        if(!empty($current_budget)){
          $budget_id = $current_budget['budget_id'];

          $update_data['fk_budget_id'] = $budget_id;

          $update_financial_report_builder = $this->write_db->table('financial_report');
          $update_financial_report_builder->where('financial_report_id' , $report_id);
          $update_financial_report_builder->update($update_data);

          // $this->write_db->where(array('financial_report_id' => $report_id));
          // $this->write_db->update('financial_report',['fk_budget_id' => $budget_id]);
        }
      }
  
      return $budget_id == NULL ? 0 : $budget_id;
  
    }

    /**
 * Calculates the Operation Ratio for a specific financial report.
 *
 * @param int $account_system_id The ID of the account system.
 * @param int $office_id The ID of the office.
 * @param string $fy_start_date The start date of the financial year.
 * @param string $reporting_month The reporting month.
 * @param array $month_support_expenses The expenses for the month for support.
 * @param array $support_income_account_ids The IDs of the support income accounts.
 *
 * @return float The Operation Ratio.
 */

  private function operationRatio(
    int $account_system_id, 
    int $office_id, 
    string $fy_start_date, 
    string $reporting_month, 
    array $month_support_expenses, 
    array $support_income_account_ids
    ): float {
    
    $sum_all_costs = 0; // Sum of all expenses from CI incomes
    $sum_admin_costs = 0; // Sum of all admin costs from CI incomes
    $admin_ratio = 0; // Compute admin ratio

    // Get all admin expense accounts ids
    $expense_account_builder = $this->read_db->table('expense_account');
    $expense_account_builder->select('expense_account_id');
    $expense_account_builder->where(array('expense_account_is_admin' => 1, 'income_account.fk_account_system_id' => $account_system_id));
    $expense_account_builder->whereIn('fk_income_account_id', $support_income_account_ids);
    $expense_account_builder->join('income_account','income_account.income_account_id=expense_account.fk_income_account_id');
    $expense_account_obj = $expense_account_builder->get();

    // $this->read_db->select(array('expense_account_id'));
    // $this->read_db->where(array('expense_account_is_admin' => 1, 'income_account.fk_account_system_id' => $account_system_id));
    // $this->read_db->where_in('fk_income_account_id', $support_income_account_ids);
    // $this->read_db->join('income_account','income_account.income_account_id=expense_account.fk_income_account_id');
    // $expense_account_obj = $this->read_db->get('expense_account');

    $admin_expense_account_ids = [];

    if($expense_account_obj->getNumRows() > 0){
      $admin_expense_account_ids = array_column($expense_account_obj->getResultArray(), 'expense_account_id');
    }

    // Sum past month all support costs and admin costs
    $financial_report_builder = $this->read_db->table('financial_report');
    $financial_report_builder->select(['financial_report_month','closing_expense_report_data ']);
    $financial_report_builder->where(array('financial_report_month >=' => $fy_start_date, 
    'financial_report_month <' => $reporting_month, 'fk_office_id' => $office_id));
    $closing_expense_report_data_obj = $financial_report_builder->get();

    // $this->read_db->select(array('financial_report_month','closing_expense_report_data '));
    // $this->read_db->where(array('financial_report_month >=' => $fy_start_date, 
    // 'financial_report_month <' => $reporting_month, 'fk_office_id' => $office_id));
    // $closing_expense_report_data_obj = $this->read_db->get('financial_report');

    $closing_expense_report_data  = [];

    if($closing_expense_report_data_obj->getNumRows() > 0){
      $closing_expense_report_data_raw = $closing_expense_report_data_obj->getResultArray();

      for($i = 0; $i < count($closing_expense_report_data_raw); $i++){
        $closing_expense_report_data[$i]['financial_report_month'] = $closing_expense_report_data_raw[$i]['financial_report_month'];
        $closing_expense_report_data[$i]['month_data'] = json_decode($closing_expense_report_data_raw[$i]['closing_expense_report_data'] ?? '', true);
      }
    }

    foreach($closing_expense_report_data as $month_data){
        if(!isset($month_data['month_data']) || empty($month_data['month_data'])) continue;
        foreach($month_data['month_data'] as $account_data){
          if(!isset($account_data['income_account_id'])) continue;
            if(in_array($account_data['income_account_id'], $support_income_account_ids)){
              if(!isset($account_data['expense_report'])) continue;
              foreach($account_data['expense_report'] as $expense_report_data){
                $sum_all_costs += $expense_report_data['month_expense_to_date'];
                if(in_array($expense_report_data['expense_account_id'], $admin_expense_account_ids)){
                  $sum_admin_costs += $expense_report_data['month_expense_to_date'];
                }
              }
            }
        }
    }

    // log_message('error', json_encode(['sum_admin_costs' => $sum_admin_costs, 'sum_all_costs' => $sum_all_costs]));

    // Sum expenses for support in the period of the year
    // $admin = 0;
    // $all = 0;
    $expense_report = array_column($month_support_expenses,'expense_report');
    // log_message('error', json_encode($month_support_expenses));
    foreach($expense_report as $income_expense_report){
      foreach($income_expense_report as $expense_data){
        // $all += $expense_data['month_expense_to_date'];
        $sum_all_costs += $expense_data['month_expense_to_date'];
        if(in_array($expense_data['expense_account_id'], $admin_expense_account_ids)){
          // $admin = $expense_data['month_expense_to_date'];
          $sum_admin_costs += $expense_data['month_expense_to_date'];
        }
      }
    }

    // log_message('error', json_encode(['admin' => $admin, 'all' => $all]));

    if($sum_all_costs > 0){
      // log_message('error', json_encode(['sum_admin_costs' => $sum_admin_costs, 'sum_all_costs' => $sum_all_costs]));
      $admin_ratio = ($sum_admin_costs/$sum_all_costs) * 100;
    }
    
    return number_format($admin_ratio,2);
  }

  /**
 * Computes the survival ratio for a specific office and month.
 *
 * @param int $office_id The ID of the office.
 * @param string $fy_start_date The start date of the financial year.
 * @param string $reporting_month The month for which the survival ratio is calculated.
 * @param array $local_resource_income_account_ids The IDs of the accounts that represent local resource income.
 * @param array $ci_income_income_accounts The IDs of the accounts that represent CI income.
 * @param array $fund_balances The fund balances data for the specific office and month.
 *
 * @return float The survival ratio in percentage.
 */
private function survivalRatio(
  int $office_id, 
  string $fy_start_date, 
  string $reporting_month, 
  array $local_resource_income_account_ids, 
  array $ci_income_income_accounts, 
  array $fund_balances
  ): float {

  // log_message('error', json_encode(compact('office_id','fy_start_date','reporting_month','local_resource_income_account_ids','ci_income_income_accounts','fund_balances')));
  
  $sum_month_local_resource_income = 0; // Sum of current month locally mobilized resource income
  $sum_month_ci_resource_income = 0; // Sum of month CI resource income
  $survival_ratio = 0; // Computed survival ratio
  $sum_to_date_local_resource_income = 0; // Sum of past months locally mobilized resource income
  $sum_to_date_ci_income = 0; // Sum of past months CI resource income

  // Get historical funds balance data & get sum of local resources income
  $report_data = $this->monthFundBalanceReportData($office_id, $fy_start_date, $reporting_month);  

   foreach($report_data as $month_data){
    if(!isset($month_data['month_data'])) continue;
    foreach($month_data['month_data'] as $account_data){
      if(in_array($account_data['account_id'], $local_resource_income_account_ids)){
        $sum_to_date_local_resource_income += $account_data['month_income'];
      }

      if(in_array($account_data['account_id'], $ci_income_income_accounts)){
        $sum_to_date_ci_income += $account_data['month_income'];
      }
    }
   }

   // Get months locally mobilized resource
   foreach($fund_balances as $fund_balance){
    if(in_array($fund_balance['account_id'], $local_resource_income_account_ids)){
      $sum_month_local_resource_income += $fund_balance['month_income'];
    }

    if(in_array($fund_balance['account_id'], $ci_income_income_accounts)){
      $sum_month_ci_resource_income += $fund_balance['month_income'];
    }
    // log_message('error', json_encode(compact('fund_balance', 'local_resource_income_account_ids','ci_income_income_accounts','sum_month_local_resource_income','sum_month_ci_resource_income')));
  }

  // Compute the survival ratio in percentage
  // log_message('error', json_encode(compact('','sum_to_date_local_resource_income','sum_month_local_resource_income','sum_to_date_ci_income','sum_month_ci_resource_income')));
  if($sum_to_date_ci_income > 0 || $sum_month_ci_resource_income > 0){
    $survival_ratio = (($sum_to_date_local_resource_income + $sum_month_local_resource_income)/($sum_to_date_ci_income + $sum_month_ci_resource_income)) * 100;
  }
  
  // Format and return the survival ratio
  return number_format($survival_ratio,2);
}

/**
 * Retrieves the past month fund balance report data for a specific office and reporting month.
 *
 * @param int $office_id The ID of the office.
 * @param string $fy_start_date The start date of the financial year.
 * @param string $reporting_month The reporting month.
 *
 * @return array An array containing the past month fund balance report data.
 */
private function monthFundBalanceReportData(
  int $office_id, 
  string $fy_start_date, 
  string $reporting_month
  ): array {

  // Get the past month fund balance report data to date (Immidiate last month)
  $builder = $this->read_db->table('financial_report');
  $builder->select(['financial_report_month','month_fund_balance_report_data']);
  $builder->where(array('financial_report_month >=' => $fy_start_date, 
  'financial_report_month <' => $reporting_month, 'fk_office_id' => $office_id));
  $month_fund_balance_report_data_obj = $builder->get();
  // $this->read_db->select(array('financial_report_month','month_fund_balance_report_data'));
  // $this->read_db->where(array('financial_report_month >=' => $fy_start_date, 
  // 'financial_report_month <' => $reporting_month, 'fk_office_id' => $office_id));
  // $month_fund_balance_report_data_obj = $this->read_db->get('financial_report');

  $month_fund_balance_report_data = [];

  if($month_fund_balance_report_data_obj->getNumRows() > 0){
    $month_fund_balance_report_data_raw = $month_fund_balance_report_data_obj->getResultArray();

    for($i = 0; $i < count($month_fund_balance_report_data_raw); $i++){
      $month_fund_balance_report_data[$i]['financial_report_month'] = $month_fund_balance_report_data_raw[$i]['financial_report_month'];
      $month_fund_balance_report_data[$i]['month_data'] = json_decode($month_fund_balance_report_data_raw[$i]['month_fund_balance_report_data'] ?? '', true);
    }
  }

  return $month_fund_balance_report_data;
}

/**
 * Calculates the accumulation ratio for the given office and month.
 *
 * @param int $office_id The ID of the office.
 * @param string $fy_start_date The start date of the financial year.
 * @param string $reporting_month The month for which the ratio is calculated.
 * @param array $support_income_account_ids The IDs of the support income accounts.
 * @param array $fund_balances The fund balances for the given office and month.
 *
 * @return float The calculated accumulation ratio.
 */
private function accumulationRatio(
  int $office_id, 
  string $fy_start_date, 
  string $reporting_month, 
  array $support_income_account_ids, 
  array $fund_balances
  ): float{

  $sum_support_to_date_income = 0; // Sum of all CI incomes in the past months of the year
  $number_of_months = 0; // Number of months past in the current year
  $sum_month_support_income = 0; // Month's support income
  $sum_month_support_closing = 0; // Month's support closing balance
  $accumulation_ratio = 0; // Computed accumulation ratio

  // Sum past support income and number of months past in the current year
  $report_data = $this->monthFundBalanceReportData($office_id, $fy_start_date, $reporting_month);    
   foreach($report_data as $month_data){
    if(!isset($month_data['month_data'])) continue;
    foreach($month_data['month_data'] as $account_data){
      if(in_array($account_data['account_id'], $support_income_account_ids)){
        $sum_support_to_date_income += $account_data['month_income'];
      }
    }
    $number_of_months++;
   }

  // Sum the support income for the current month and get the current closing support balance 
  foreach($fund_balances as $fund_balance){
    if(in_array($fund_balance['account_id'], $support_income_account_ids)){
      $sum_month_support_income += $fund_balance['month_income'];
      $sum_month_support_closing += $fund_balance['month_closing_balance'];
    }
  }

  // Get the avarage support income for the year
  $avg_past_month_income = ($sum_support_to_date_income + $sum_month_support_income) / ($number_of_months + 1);
  
  // Compute the accumulation ratio
  if($avg_past_month_income > 0){
    $accumulation_ratio = $sum_month_support_closing/ $avg_past_month_income; 
  }
  
  // Format and return the accumulation ratio
  return number_format($accumulation_ratio,2);
}

/**
 * Calculates the budget variance for the given support income accounts and expenses.
 *
 * @param array $support_expenses The monthly expenses report for all income accounts.
 *
 * @return float The budget variance percentage.
 */
private function budgetVariance(
  array $support_expenses
  ) {

  $sum_budget_to_date = 0;
  $sum_expense_to_date = 0;
  $variance = -100;

  $expense_report = array_column($support_expenses,'expense_report');
  foreach($expense_report as $income_expense_report){
    foreach($income_expense_report as $expense_data){
      $sum_budget_to_date += $expense_data['budget_to_date'];
      $sum_expense_to_date += $expense_data['month_expense_to_date'];
    }
  }

  // Compute the budget variance to date
  if($sum_budget_to_date > 0){
    $variance = number_format((($sum_budget_to_date - $sum_expense_to_date)/$sum_budget_to_date) * 100,2);
  }

  return $variance;
}

/**
 * Get all support related expenses from the given month expenses.
 *
 * @param array $month_expenses The monthly expenses report for all income accounts.
 * @param array $income_account_ids The income account IDs for support income.
 *
 * @return array The support related expenses.
 */
private function supportIncomeExpenses(
  array $month_expenses, 
  array $income_account_ids
): array {
  // Get all support related expenses
  $support_expenses = [];
  foreach($month_expenses as $month_data){
    if(in_array($month_data['income_account_id'], $income_account_ids)){
      $support_expenses[$month_data['income_account_id']] = $month_data;
    }
  }

  // log_message('error', json_encode($support_expenses));
  return $support_expenses;
}

/**
 * Retrieves income account IDs grouped by funding streams.
 *
 * @param int $account_system_id The ID of the account system.
 * @return array An associative array where the keys are funding stream codes and the values are arrays of income account IDs.
 */
private function incomeAccountIdsByFundingStreams(
  int $account_system_id
  ): array {
  // funding streams can support,local,gift,individual and ongoing 
    $builder =$this->read_db->table('income_account');
    $builder->select(['income_account.income_account_id','funding_stream.funding_stream_code']);
    $builder->join('income_vote_heads_category','income_vote_heads_category.income_vote_heads_category_id=income_account.fk_income_vote_heads_category_id');
    $builder->join('funding_stream','funding_stream.funding_stream_id=income_vote_heads_category.fk_funding_stream_id');
    $builder->where('income_account.fk_account_system_id' , $account_system_id);
    $income_account_obj = $builder->get();
  // $this->read_db->select(array('income_account_id','funding_stream_code'));
  // $this->read_db->join('income_vote_heads_category','income_vote_heads_category.income_vote_heads_category_id=income_account.fk_income_vote_heads_category_id');
  // $this->read_db->join('funding_stream','funding_stream.funding_stream_id=income_vote_heads_category.fk_funding_stream_id');
  // $this->read_db->where(array('fk_account_system_id' => $account_system_id));
  // $income_account_obj = $this->read_db->get('income_account');

  $income_account_ids = [];

  if($income_account_obj->getNumRows() > 0){
    $income_accounts = $income_account_obj->getResultArray();
    // $income_account_ids = array_column($income_account_obj->result_array(), 'income_account_id');
    for($i = 0; $i < count($income_accounts); $i++){
      $income_account_ids[$income_accounts[$i]['funding_stream_code']][] = $income_accounts[$i]['income_account_id'];
    }
  }

  return $income_account_ids;
}

/**
 * Reorganizes the format of the monthly expense report to match the historical data format.
 *
 * @param array $month_expenses The monthly expense report data.
 * @return array The reorganized monthly expense report data.
 */
private function reorganizeMonthExpenseReport(
  array $month_expenses
  ): array {

  $reorganize_month_expenses = [];
  
  // Check if the first element of the incoming array has a key "income_account". This means its not from saved expense report history
  // And thus it needs to be formated to match the historical data format
  $first_element = reset($month_expenses);
  if(!array_key_exists('income_account',$first_element)){
    return $month_expenses;
  }

  // Formating the current expense report format to match the historical data format
  $c = 0;
  foreach($month_expenses as $month_expense){
    if(!isset($month_expense['income_account']['income_account_id'])) continue;
    $reorganize_month_expenses[$c]['income_account_id'] = $month_expense['income_account']['income_account_id'];
    
    if(!isset($month_expense['expense_accounts'])) continue;
    $reorganize_month_expenses[$c]['expense_report'] = [];
    $cnt = 0;
    foreach($month_expense['expense_accounts'] as $expense_account_data){
      $budget_to_date = $expense_account_data['budget_to_date'];
      $month_expense_to_date = $expense_account_data['month_expense_to_date'];
      $budget_variance = $budget_to_date - $month_expense_to_date;
      $variance_percentage = $budget_to_date > 0 ? (($budget_variance/$budget_to_date)*100) : -100;

      $reorganize_month_expenses[$c]['expense_report'][$cnt]['expense_account_id'] = $expense_account_data['expense_account']['expense_account_id'];
      $reorganize_month_expenses[$c]['expense_report'][$cnt]['month_expense'] = $expense_account_data['month_expense'];
      $reorganize_month_expenses[$c]['expense_report'][$cnt]['month_expense_to_date'] = $expense_account_data['month_expense_to_date'];
      $reorganize_month_expenses[$c]['expense_report'][$cnt]['budget_to_date'] = $expense_account_data['budget_to_date'];
      $reorganize_month_expenses[$c]['expense_report'][$cnt]['budget_variance'] = $budget_variance;
      $reorganize_month_expenses[$c]['expense_report'][$cnt]['budget_variance_percent'] = number_format($variance_percentage,2);
      
      $cnt++;
    }
    $c++;
  }
  
  return $reorganize_month_expenses;
}

/**
 * Computes the financial ratios for a given office and reporting month.
 *
 * @param int $office_id The ID of the office.
 * @param string $reporting_month The reporting month in the format 'YYYY-MM'.
 * @param array $month_expenses The monthly expenses data.
 * @param array $fund_balances The fund balances data.
 *
 * @return array An associative array containing the financial ratios:
 * - operation_ratio: The operation ratio.
 * - accumulation_ratio: The accumulation ratio.
 * - budget_variance: The budget variance.
 * - survival_ratio: The survival ratio.
 */
private function toDateFinancialRatios(
  int $office_id, 
  string $reporting_month, 
  array $month_expenses, 
  array $fund_balances
  ): array {

  // log_message('error', json_encode($month_expenses));

  $month_expenses = $this->reorganizeMonthExpenseReport($month_expenses);

  // Get the office account system id
  $office_builder = $this->read_db->table('office');
  $office_builder->select('fk_account_system_id');
  $office_builder->where('office_id' , $office_id);
  $account_system_id = $office_builder->get()->getRow()->fk_account_system_id;

  // Compute the financial year start date for the office
  $custom_financial_year = $this->customFinancialYearLibrary->getDefaultCustomFinancialYearIdByOffice($office_id, true);
  $fy_start_date = fy_start_date($reporting_month, $custom_financial_year);
  
  // Get income accounts grouped by funding streams
  $income_account_ids_by_funding_streams = $this->incomeAccountIdsByFundingStreams($account_system_id);

  // Get support, gift, local, individual and ongoing income accounts ids 
  $support_income_account_ids = isset($income_account_ids_by_funding_streams['support']) ? $income_account_ids_by_funding_streams['support'] : [];
  $local_resource_income_account_ids = isset($income_account_ids_by_funding_streams['local']) ? $income_account_ids_by_funding_streams['local'] : [];
  $gift_resource_income_account_ids = isset($income_account_ids_by_funding_streams['gift']) ? $income_account_ids_by_funding_streams['gift'] : [];
  $individual_resource_income_account_ids = isset($income_account_ids_by_funding_streams['individual']) ? $income_account_ids_by_funding_streams['individual'] : [];
  $ongoing_resource_income_account_ids = isset($income_account_ids_by_funding_streams['ongoing']) ? $income_account_ids_by_funding_streams['ongoing'] : [];
  
  // Merge all CI income accounts
  $ci_income_income_accounts = array_merge($support_income_account_ids, $gift_resource_income_account_ids, $individual_resource_income_account_ids, $ongoing_resource_income_account_ids);
  // log_message('error', json_encode($ci_income_income_accounts));

  $month_support_expenses = $this->supportIncomeExpenses($month_expenses, $support_income_account_ids);

  // Gather ratios data
  $operation_ratio = $this->operationRatio($account_system_id, $office_id, $fy_start_date, $reporting_month, $month_support_expenses, $support_income_account_ids);
  $accumulation_ratio = $this->accumulationRatio($office_id, $fy_start_date, $reporting_month, $support_income_account_ids, $fund_balances);
  $budget_variance = $this->budgetVariance($month_support_expenses);
  $survival_ratio = $this->survivalRatio($office_id, $fy_start_date, $reporting_month, $local_resource_income_account_ids, $ci_income_income_accounts, $fund_balances);

  // Return the ratios
  return [
    'operation_ratio' => $operation_ratio,
    'accumulation_ratio' => $accumulation_ratio,
    'budget_variance' => $budget_variance,
    'survival_ratio' => $survival_ratio
  ];
}

function result($id = '', $parentId = null)
{
  $result = parent::result($id, $parentId);

  if ($this->action == 'view') {

    $report = $this->financialReportInformation($this->id);
    extract($report);

    // check if report has budget id if not update it
    $budget_id = $this->updateFinancialReportBudgetId(hash_id($this->id,'decode'), $office_ids[0]);
    $budget_tag_name = '';

    if($budget_id == 0){
      $custom_financial_year = $this->customFinancialYearLibrary->getDefaultCustomFinancialYearIdByOffice($office_ids[0], true);
      $budget_tag_name = $this->budgetTagLibrary->getBudgetTagIdBasedOnReportingMonth($office_ids[0],$reporting_month, $custom_financial_year)['budget_tag_name'];
    }
    $month_expenses = $this->expenseReport($office_ids, $reporting_month);
    $fund_balances = $this->fundBalanceReport($office_ids, $reporting_month);
    $action_label = $this->libs->actionLabels('financial_report', hash_id($this->id, 'decode'));


    return array_merge([
      'test' => [],
      "financial_ratios" => $this->toDateFinancialRatios($office_ids[0],$reporting_month , $month_expenses, $fund_balances),
      'report_id' => hash_id($this->id,'decode'),
      'budget_id' => $budget_id,
      'budget_tag_name' => $budget_tag_name,
      'allow_mfr_reconciliation' => ($multiple_offices_report || $multiple_projects_report || count($this->getOfficeBanks($office_ids, $reporting_month)) > 1) ? false : true,
      'month_active_projects' => $this->getMonthActiveProjects($office_ids, $reporting_month),
      'office_banks' => $this->getOfficeBanks($office_ids, $reporting_month),
      'multiple_offices_report' => $multiple_offices_report,
      'multiple_projects_report' => $multiple_projects_report,
      'financial_report_submitted' => $this->checkIfFinancialReportIsSubmitted($office_ids, $reporting_month),
      'has_submitted_report_ahead' => $this->hasSubmittedReportAhead($report),
      'user_office_hierarchy' => $this->financialReportOfficeHierarchy($reporting_month),
      'office_names' => $office_names,
      'office_ids' => $office_ids,
      'reporting_month' => $reporting_month,
      'fund_balance_report' => $fund_balances,
      'projects_balance_report' => $this->projectsBalanceReport($office_ids, $reporting_month),
      'proof_of_cash' => $this->_proofOfCash($office_ids, $reporting_month),
      'bank_statements_uploads' => $this->bankStatementsUploads($office_ids, $reporting_month),
      'bank_reconciliation' => $this->bankReconciliation($office_ids, $reporting_month, $multiple_offices_report, $multiple_projects_report),
      'outstanding_cheques' => $this->financialReportLibrary->listOustandingChequesAndDeposits($office_ids, $reporting_month, 'expense', 'bank_contra', 'bank'),
      'clear_outstanding_cheques' => $this->listClearedEffects($office_ids, $reporting_month, 'expense', 'bank_contra', 'bank'),
      'deposit_in_transit' => $this->financialReportLibrary->listOustandingChequesAndDeposits($office_ids, $reporting_month, 'income', 'cash_contra', 'bank'), //$this->_deposit_in_transit($office_ids,$reporting_month),
      'cleared_deposit_in_transit' => $this->listClearedEffects($office_ids, $reporting_month, 'income', 'cash_contra', 'bank'),
      'expense_report' => $month_expenses, //$this->_expense_report($office_ids, $reporting_month),
      'logged_role_id' => $this->session->role_ids,
      'table' => 'financial_report',
      'primary_key' => hash_id($this->id, 'decode'),
      'financial_report_status' => $status_id,
      
      'funds_transfers' => $this->voucherLibrary->monthFundsTransferVouchers($office_ids, $reporting_month),
      'is_status_id_max' => $this->statusLibrary->isStatusIdMax('financial_report', hash_id($this->id, 'decode')),
      'office_id' =>$this->getOfficeId(),
      'action_lable'=>$action_label['status_name'],
    ], $this->grantsLibrary->actionButtonData($this->controller, $account_system_id));
  } 
  
  return $result;
}

function getOfficeId(){
  $builder = $this->read_db->table('financial_report');
  $builder->select('fk_office_id');
  $builder->where('financial_report_id' , hash_id($this->id, 'decode'));
  $id = $builder->get()->getRow()->fk_office_id;
  return $id;
}


function resultArray($report_id, $office_ids, $reporting_month, $project_ids = [], $office_bank_ids = [])
{
  extract($this->financialReportInformation($report_id));

  
  $month_expenses = $this->expenseReport($office_ids, $reporting_month, $project_ids, $office_bank_ids);
  $fund_balances = $this->fundBalanceReport($office_ids, $reporting_month, $project_ids, $office_bank_ids);

  return [
    //'test1'=>$this->financial_report_information($report_id),
    //'test'=>[$office_ids,$reporting_month,'expense','bank_contra','bank',$project_ids,$office_bank_ids],
    'financial_ratios'=>$this->toDateFinancialRatios($office_ids[0],$reporting_month , $month_expenses, $fund_balances),
    'month_active_projects' => $this->getMonthActiveProjects($office_ids, $reporting_month),
    'allow_mfr_reconciliation' => ($multiple_offices_report || $multiple_projects_report || count($this->getOfficeBanks($office_ids, $reporting_month, $project_ids, $office_bank_ids)) > 1) ? true : false,
    'office_banks' => $this->getOfficeBanks($office_ids, $reporting_month, $project_ids, $office_bank_ids),
    'multiple_offices_report' => $multiple_offices_report,
    'multiple_projects_report' => $multiple_projects_report,
    'financial_report_submitted' => $this->checkIfFinancialReportIsSubmitted($office_ids, $reporting_month),
    'user_office_hierarchy' => $this->financialReportOfficeHierarchy($reporting_month),
    'office_names' => $office_names,
    'office_ids' => $office_ids,
    'reporting_month' => $reporting_month,
    'fund_balance_report' => $fund_balances, // $this->_fund_balance_report($office_ids, $reporting_month, $project_ids, $office_bank_ids),
    'projects_balance_report' => $this->projectsBalanceReport($office_ids, $reporting_month, $project_ids, $office_bank_ids),
    'proof_of_cash' => $this->_proofOfCash($office_ids, $reporting_month, $project_ids, $office_bank_ids),
    'bank_statements_uploads' => $this->bankStatementsUploads($office_ids, $reporting_month, $project_ids, $office_bank_ids),
    'bank_reconciliation' => $this->bankReconciliation($office_ids, $reporting_month, $multiple_offices_report, $multiple_projects_report, $project_ids, $office_bank_ids),
    'outstanding_cheques' => $this->financialReportLibrary->listOustandingChequesAndDeposits($office_ids, $reporting_month, 'expense', 'bank_contra', 'bank', $project_ids, $office_bank_ids),
    'clear_outstanding_cheques' => $this->listClearedEffects($office_ids, $reporting_month, 'expense', 'bank_contra', 'bank', $project_ids, $office_bank_ids),
    'deposit_in_transit' => $this->financialReportLibrary->listOustandingChequesAndDeposits($office_ids, $reporting_month, 'income', 'cash_contra', 'bank', $project_ids, $office_bank_ids), //$this->_deposit_in_transit($office_ids,$reporting_month),
    'cleared_deposit_in_transit' => $this->listClearedEffects($office_ids, $reporting_month, 'income', 'cash_contra', 'bank', $project_ids, $office_bank_ids),
    'expense_report' => $month_expenses,
    'funds_transfers' => $this->voucherLibrary->monthFundsTransferVouchers($office_ids, $reporting_month),
  ];
}

function ajaxTest()
{

  $report_id = '8zoLYo3YXb';
  $office_ids = [1];
  $reporting_month = '2020-04-01';
  $project_ids = [5];

  $result = $this->resultArray($report_id, $office_ids, $reporting_month, $project_ids);
  //$result = $this->_fund_balance_report($office_ids,$reporting_month,$project_ids);

  echo json_encode($result);
}

function filterFinancialReport()
{

  // log_message('error', json_encode($this->input->post()));

  $project_ids = $this->request->getPost('project_ids') == null ? [] : $this->request->getPost('project_ids');
  $office_bank_ids = $this->request->getPost('office_bank_ids') == null ? [] : $this->request->getPost('office_bank_ids');
  $office_ids = $this->request->getPost('office_ids');
  $report_id = $this->request->getPost('report_id');
  $reporting_month = $this->request->getPost('reporting_month');

  $report_result = $this->resultArray($report_id, $office_ids, $reporting_month, $project_ids, $office_bank_ids);
  $result['result'] = $report_result;
  $result['report_id'] = $report_id;

  //echo json_encode($result);
  
  // $view_page =  $this->load->view('financial_report/ajax_view', $result, true);
  $view_page =  view('financial_report/ajax_view', $result);

  echo $view_page;
}

// function view()
// {
//   parent::view();
// }

function checkIfFinancialReportIsSubmitted($office_ids, $reporting_month)
{
  return $this->financialReportLibrary->checkIfFinancialReportIsSubmitted($office_ids, $reporting_month);
}

function bankStatementsUploads($office_ids, $reporting_month, $project_ids = [], $office_bank_ids = [])
{

  $attachment_results = [];
  $reconciliation_ids = [];
  $reconciliation_builder = $this->read_db->table('reconciliation');
  $reconciliation_builder->select('reconciliation_id');
  $reconciliation_builder->whereIn('fk_office_id', $office_ids);
  $reconciliation_builder->where(array('financial_report_month' => date('Y-m-01', strtotime($reporting_month))));
  $reconciliation_builder->join('financial_report', 'financial_report.financial_report_id=reconciliation.fk_financial_report_id');


  if (!empty($office_bank_ids)) {
    $reconciliation_builder->whereIn('reconciliation.fk_office_bank_id', $office_bank_ids);
  }

  if (!empty($project_ids)) {
    $reconciliation_builder->join('office_bank', 'office_bank.office_bank_id=reconciliation.office_bank_id');
    $reconciliation_builder->join('office_bank_project_allocation', 'office_bank_project_allocation.fk_office_bank_id=office_bank.office_bank_id');
    $reconciliation_builder->join('project_allocation', 'project_allocation.project_allocation_id=office_bank_project_allocation.fk_project_allocation_id');
    $reconciliation_builder->whereIn('project_allocation.fk_project_id', $project_ids);
  }

  $reconciliation_ids_obj = $reconciliation_builder->get();

  if ($reconciliation_ids_obj->getNumRows() > 0) {
    $reconciliation_ids = $reconciliation_ids_obj->getResultArray();
  }

  $attachment_where_condition_array = [];

  $approve_item_name = 'reconciliation';
  $approve_item_builder = $this->read_db->table('approve_item');
  $approve_item_builder->select('approve_item_id');
  $approve_item_builder->where('approve_item_name' , $approve_item_name);
  $approve_item_builder_id = $approve_item_builder->get();

  $approve_item_id = $approve_item_builder_id->getRow()->approve_item_id;

  $attachment_where_condition_array['fk_approve_item_id'] = $approve_item_id;
  $attachment_where_condition_array['attachment_primary_id'] = array_column($reconciliation_ids, 'reconciliation_id');

  // return $this->Aws_attachment_library->retrieve_file_uploads_info('reconciliation',array_column($reconciliation_ids,'reconciliation_id'));

  //fetch URL and file name from attachment table 
  if(!empty($attachment_where_condition_array['attachment_primary_id'])){
    $attachment_builder = $this->read_db->table('attachment');
    $attachment_builder->select(['attachment_id','attachment_name', 'attachment_url', 'attachment_size', 'attachment_last_modified_date']);
    $attachment_builder->whereIn('attachment_primary_id', $attachment_where_condition_array['attachment_primary_id']);
    $attachment_results = $attachment_builder->get()->getResultArray();
    return $attachment_results;
  }else{
    return $attachment_results;
  }

}

function projectsBalanceReport($office_ids, $reporting_month, $project_ids = [], $office_bank_ids = [])
{
  $headers = [];
  $body = [];


  $projects = $this->officeProjects($office_ids, $reporting_month, $project_ids, $office_bank_ids);

  // log_message('error', json_encode($projects));

  foreach ($projects as $project_id => $project) {
    $body[$project_id]['funder'] = $project['funder_name'];
    $body[$project_id]['project'] = $project['project_name'];
    //Income account
    $body[$project_id]['month_expense'] = $this->projectsMonthExpense([$project['office_id']], $reporting_month, [$project_id], $office_bank_ids) == null ? 0 : $this->projectsMonthExpense([$project['office_id']], $reporting_month, [$project_id], $office_bank_ids);
    // $body[$project_id]['allocation_target'] = $this->_projects_allocation_target([$project['office_id']], [$project_id], $office_bank_ids) == null ? 0 : $this->_projects_allocation_target([$project['office_id']], [$project_id], $office_bank_ids);
  }

  if ($this->config->funding_balance_report_aggregate_method == 'receipt') {
    $headers = [
      "funder" => get_phrase("funder"),
      "project" => get_phrase("project"),
      // "allocation_target" => get_phrase("allocation_target"),
      "opening_balance" => get_phrase("opening_balance"),
      "month_income" => get_phrase("month_income"),
      "month_expense" => get_phrase("month_expense"),
      "closing_balance" => get_phrase("closing_balance")
    ];

    foreach ($projects as $project_id => $project) {
      $body[$project_id]['opening_balance'] = $this->projectsOpeningBalances([$project['office_id']], $reporting_month, [$project_id], $office_bank_ids) == null ? 0 : $this->projectsOpeningBalances([$project['office_id']], $reporting_month, [$project_id], $office_bank_ids);
      $body[$project_id]['month_income'] = $this->projectsMonthIncome([$project['office_id']], $reporting_month, [$project_id], $office_bank_ids) == null ? 0 : $this->projectsMonthIncome([$project['office_id']], $reporting_month, [$project_id], $office_bank_ids);
      $body[$project_id]['closing_balance'] = $this->projectsReceiptClosingBalance([$project['office_id']], $reporting_month, [$project_id], $office_bank_ids) == null ? 0 : $this->projectsReceiptClosingBalance([$project['office_id']], $reporting_month, [$project_id], $office_bank_ids);
    }
  } elseif ($this->config->funding_balance_report_aggregate_method == 'allocation') {
    $headers = [
      "funder" => get_phrase("funder"),
      "project" => get_phrase("project"),
      // "allocation_target" => get_phrase("allocation_target"),
      "month_expense" => get_phrase("month_expense"),
      "month_expense_to_date" => get_phrase("month_expense_to_date"),
      "closing_balance" => get_phrase("closing_balance")
    ];

    foreach ($projects as $project_id => $project) {
      $body[$project_id]['month_expense_to_date'] = $this->projectsMonthExpenseToDate([$project['office_id']], $reporting_month, [$project_id], $office_bank_ids);
      $body[$project_id]['closing_balance'] = $this->projectsAllocationClosingBalance([$project['office_id']], $reporting_month, [$project_id], $office_bank_ids);
    }
  }

  // log_message('error', json_encode($body));
  $this->removeZeroProjectBalances($body);

  return ['headers' => $headers, 'body' => $body];
}

function removeZeroProjectBalances(&$balances)
{
  foreach ($balances as $project_id => $balance) {
    if ($balance['opening_balance'] == 0 && $balance['month_income'] == 0 && $balance['month_expense'] == 0) {
      unset($balances[$project_id]);
    }
  }
}

function projectsAllocationClosingBalance($office_ids, $reporting_month, $project_ids = [], $office_bank_ids = [])
{
  $closing_balance = $this->projectsAllocationTarget($office_ids, $project_ids, $office_bank_ids) - $this->projectsMonthExpenseToDate($office_ids, $reporting_month, $project_ids, $office_bank_ids);

  return $closing_balance;
}

function projectsMonthExpenseToDate($office_ids, $reporting_month, $project_ids = [], $office_bank_ids = [])
{

  $end_of_reporting_month = date('Y-m-t', strtotime($reporting_month));

  $this->read_db->select_sum('voucher_detail_total_cost');
  $this->read_db->whereIn('voucher_type_effect_code', ['expense','bank_refund']);
  $this->read_db->where(array('voucher_date<=' => $end_of_reporting_month));
  $this->read_db->where_in('voucher.fk_office_id', $office_ids);

  $this->read_db->join('voucher', 'voucher.voucher_id=voucher_detail.fk_voucher_id');
  $this->read_db->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
  $this->read_db->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
  $this->read_db->join('project_allocation', 'project_allocation.project_allocation_id=voucher_detail.fk_project_allocation_id');
  $this->read_db->join('office_bank_project_allocation', 'office_bank_project_allocation.fk_project_allocation_id=project_allocation.project_allocation_id');

  if (!empty($project_ids)) {
    $this->read_db->where_in('project_allocation.fk_project_id', $project_ids);
  }

  if (!empty($office_bank_ids)) {
    $this->read_db->where_in('office_bank_project_allocation.fk_office_bank_id', $office_bank_ids);
  }

  $voucher_detail_total_cost = $this->read_db->get('voucher_detail')->row()->voucher_detail_total_cost;

  return $voucher_detail_total_cost;
}

function projectsAllocationTarget($office_ids, $project_ids = [], $office_bank_ids = [])
{

  $this->read_db->select_sum('project_allocation_amount');
  $this->read_db->where_in('fk_office_id', $office_ids);

  if (!empty($project_ids)) {
    $this->read_db->join('project', 'project.project_id=project_allocation.fk_project_id');
    $this->read_db->where_in('project_id', $project_ids);
  }

  if (!empty($office_bank_ids)) {
    $this->read_db->join('office_bank_project_allocation', 'office_bank_project_allocation.fk_project_allocation_id=project_allocation.project_allocation_id');
    $this->read_db->where_in('office_bank_project_allocation.fk_office_bank_id', $office_bank_ids);
  }


  $sum_project_allocation_amount = $this->read_db->get('project_allocation')->row()->project_allocation_amount;

  return $sum_project_allocation_amount;
}

function projectsReceiptClosingBalance($office_ids, $reporting_month, $project_ids = [], $office_bank_ids = [])
{
  $opening_balance = $this->projectsOpeningBalances($office_ids, $reporting_month, $project_ids, $office_bank_ids);
  $month_income = $this->projectsMonthIncome($office_ids, $reporting_month, $project_ids, $office_bank_ids);
  $month_expense = $this->projectsMonthExpense($office_ids, $reporting_month, $project_ids, $office_bank_ids);

  $closing_balance = $opening_balance + $month_income - $month_expense;

  return $closing_balance;
}

function projectsMonthIncome($office_ids, $reporting_month, $project_ids = [], $office_bank_ids = [])
{

  $start_date_of_reporting_month = date('Y-m-01', strtotime($reporting_month));
  $end_date_of_reporting_month = date('Y-m-t', strtotime($reporting_month));
  $max_approval_status_ids = $this->statusLibrary->getMaxApprovalStatusId('voucher', $office_ids);

  $builder = $this->read_db->table('voucher_detail');
  $builder->selectSum('voucher_detail_total_cost');
  $builder->where('voucher_type_effect_code' , 'income');
  $builder->whereIn('voucher.fk_office_id', $office_ids);
  $builder->where(array('voucher.voucher_date>=' => $start_date_of_reporting_month, 'voucher.voucher_date<=' => $end_date_of_reporting_month));

  $builder->whereIn('voucher.fk_status_id', $max_approval_status_ids);

  $builder->join('voucher', 'voucher.voucher_id=voucher_detail.fk_voucher_id');
  $builder->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
  $builder->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');

  // $this->read_db->select_sum('voucher_detail_total_cost');
  // $this->read_db->where(array('voucher_type_effect_code' => 'income'));
  // $this->read_db->where_in('voucher.fk_office_id', $office_ids);
  // $this->read_db->where(array('voucher.voucher_date>=' => $start_date_of_reporting_month, 'voucher.voucher_date<=' => $end_date_of_reporting_month));

  // $this->read_db->where_in('voucher.fk_status_id', $max_approval_status_ids);

  // $this->read_db->join('voucher', 'voucher.voucher_id=voucher_detail.fk_voucher_id');
  // $this->read_db->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
  // $this->read_db->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');

  if (!empty($project_ids)) {
    $builder->join('project_allocation', 'project_allocation.project_allocation_id=voucher_detail.fk_project_allocation_id');
    $builder->whereIn('project_allocation.fk_project_id', $project_ids);

    // $this->read_db->join('project_allocation', 'project_allocation.project_allocation_id=voucher_detail.fk_project_allocation_id');
    // $this->read_db->where_in('project_allocation.fk_project_id', $project_ids);
  }

  if (!empty($office_bank_ids)) {
    $builder->join('office_bank_project_allocation', 'office_bank_project_allocation.fk_project_allocation_id=project_allocation.project_allocation_id');
    $builder->whereIn('office_bank_project_allocation.fk_office_bank_id', $office_bank_ids);

    // $this->read_db->join('office_bank_project_allocation', 'office_bank_project_allocation.fk_project_allocation_id=project_allocation.project_allocation_id');
    // $this->read_db->where_in('office_bank_project_allocation.fk_office_bank_id', $office_bank_ids);
  }

  $voucher_detail_total_cost = $builder->get()->getRow()->voucher_detail_total_cost;

  return $voucher_detail_total_cost;
}

private function projectAllocationSystemOpeningBalance($office_ids, $reporting_month, $project_ids, $office_bank_ids){
  $opening_balances = 0;

  // log_message('error', json_encode(['office_ids' => $office_ids, 'project_ids' => $project_ids]));
  $builder = $this->read_db->table('opening_fund_balance');
  $builder->selectSum('opening_fund_balance_amount');
  $builder->whereIn('system_opening_balance.fk_office_id', $office_ids);
  $builder->whereIn('opening_fund_balance.fk_project_id', $project_ids);
  $builder->join('system_opening_balance', 'system_opening_balance.system_opening_balance_id=opening_fund_balance.fk_system_opening_balance_id');
  $opening_fund_balance_obj = $builder->get();

  // $this->read_db->select_sum('opening_fund_balance_amount');
  // $this->read_db->where_in('system_opening_balance.fk_office_id', $office_ids);
  // $this->read_db->where_in('opening_fund_balance.fk_project_id', $project_ids);
  // $this->read_db->join('system_opening_balance', 'system_opening_balance.system_opening_balance_id=opening_fund_balance.fk_system_opening_balance_id');
  // $opening_fund_balance_obj = $this->read_db->get('opening_fund_balance');

  if($opening_fund_balance_obj->getNumRows() > 0){
    $opening_balances = $opening_fund_balance_obj->getRow()->opening_fund_balance_amount;
  }
  // log_message('error', json_encode($opening_balances));
  return $opening_balances;
}

function projectsOpeningBalances($office_ids, $reporting_month, $project_ids = [], $office_bank_ids = [])
{
  $system_opening_balance = $this->projectAllocationSystemOpeningBalance($office_ids, $reporting_month, $project_ids, $office_bank_ids); ////$this->_projects_allocation_target($office_ids,$project_ids,$office_bank_ids);
  $projects_previous_months_expense_to_date = $this->projectsPreviousMonthsExpenseToDate($office_ids, $reporting_month, $project_ids, $office_bank_ids);
  $projects_previous_months_income_to_date = $this->projectsPreviousMonthsIncomeToDate($office_ids, $reporting_month, $project_ids, $office_bank_ids);;

  $opening_balance = ($system_opening_balance + $projects_previous_months_income_to_date) - $projects_previous_months_expense_to_date;

  return $opening_balance;
}

function projectsPreviousMonthsIncomeToDate($office_ids, $reporting_month, $project_ids = [], $office_bank_ids = [])
{
  $start_of_reporting_month = date('Y-m-01', strtotime($reporting_month));

  $builder = $this->read_db->table('voucher_detail');
  $builder->selectSum('voucher_detail_total_cost');
  $builder->where('voucher_type_effect_code' , 'income');
  $builder->where('voucher_date<' , $start_of_reporting_month);
  $builder->whereIn('voucher.fk_office_id', $office_ids);

  $builder->join('voucher', 'voucher.voucher_id=voucher_detail.fk_voucher_id');
  $builder->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
  $builder->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');

  // $this->read_db->select_sum('voucher_detail_total_cost');
  // $this->read_db->where(array('voucher_type_effect_code' => 'income'));
  // $this->read_db->where(array('voucher_date<' => $start_of_reporting_month));
  // $this->read_db->where_in('voucher.fk_office_id', $office_ids);

  // $this->read_db->join('voucher', 'voucher.voucher_id=voucher_detail.fk_voucher_id');
  // $this->read_db->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
  // $this->read_db->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');

  if (!empty($project_ids)) {
    $builder->join('project_allocation', 'project_allocation.project_allocation_id=voucher_detail.fk_project_allocation_id');
    $builder->whereIn('project_allocation.fk_project_id', $project_ids);

    // $this->read_db->join('project_allocation', 'project_allocation.project_allocation_id=voucher_detail.fk_project_allocation_id');
    // $this->read_db->where_in('project_allocation.fk_project_id', $project_ids);
  }

  if (!empty($office_bank_ids)) {
    $builder->join('office_bank_project_allocation', 'office_bank_project_allocation.fk_project_allocation_id=project_allocation.project_allocation_id');
    $builder->whereIn('office_bank_project_allocation.fk_office_bank_id', $office_bank_ids);

    // $this->read_db->join('office_bank_project_allocation', 'office_bank_project_allocation.fk_project_allocation_id=project_allocation.project_allocation_id');
    // $this->read_db->where_in('office_bank_project_allocation.fk_office_bank_id', $office_bank_ids);
  }

  $voucher_detail_total_cost = $builder->get()->getRow()->voucher_detail_total_cost;

  return $voucher_detail_total_cost;
}

function projectsPreviousMonthsExpenseToDate($office_ids, $reporting_month, $project_ids = [], $office_bank_ids = [])
{

  $start_of_reporting_month = date('Y-m-01', strtotime($reporting_month));

  $builder = $this->read_db->table('voucher_detail');
  $builder->selectSum('voucher_detail_total_cost');
  $builder->whereIn('voucher_type_effect_code' , ['expense', 'bank_refund']);
  $builder->where('voucher_date<' , $start_of_reporting_month);
  $builder->whereIn('voucher.fk_office_id', $office_ids);

  $builder->join('voucher', 'voucher.voucher_id=voucher_detail.fk_voucher_id');
  $builder->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
  $builder->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');

  // $this->read_db->select_sum('voucher_detail_total_cost');
  // $this->read_db->where(array('voucher_type_effect_code' => 'expense'));
  // $this->read_db->where(array('voucher_date<' => $start_of_reporting_month));
  // $this->read_db->where_in('voucher.fk_office_id', $office_ids);

  // $this->read_db->join('voucher', 'voucher.voucher_id=voucher_detail.fk_voucher_id');
  // $this->read_db->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
  // $this->read_db->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');

  if (!empty($project_ids)) {
    $builder->join('project_allocation', 'project_allocation.project_allocation_id=voucher_detail.fk_project_allocation_id');
    $builder->whereIn('project_allocation.fk_project_id', $project_ids);

    // $this->read_db->join('project_allocation', 'project_allocation.project_allocation_id=voucher_detail.fk_project_allocation_id');
    // $this->read_db->where_in('project_allocation.fk_project_id', $project_ids);
  }

  if (!empty($office_bank_ids)) {
    $builder->join('office_bank_project_allocation', 'office_bank_project_allocation.fk_project_allocation_id=project_allocation.project_allocation_id');
    $builder->whereIn('office_bank_project_allocation.fk_office_bank_id', $office_bank_ids);

    // $this->read_db->join('office_bank_project_allocation', 'office_bank_project_allocation.fk_project_allocation_id=project_allocation.project_allocation_id');
    // $this->read_db->where_in('office_bank_project_allocation.fk_office_bank_id', $office_bank_ids);
  }

  $voucher_detail_total_cost = $builder->get()->getRow()->voucher_detail_total_cost;

  return $voucher_detail_total_cost;
}

function projectsMonthExpense($office_ids, $reporting_month, $project_ids = [], $office_bank_ids = [])
{
  
  $start_date_of_reporting_month = date('Y-m-01', strtotime($reporting_month));
  $end_date_of_reporting_month = date('Y-m-t', strtotime($reporting_month));
  $max_approval_status_ids = $this->statusLibrary->getMaxApprovalStatusId('voucher', $office_ids);

  // log_message('error',json_encode($max_approval_status_ids));
  $builder= $this->read_db->table('voucher_detail');
  $builder->selectSum('voucher_detail_total_cost');
  $builder->whereIn('voucher_type_effect_code', ['expense', 'bank_refund']);
  $builder->whereIn('voucher.fk_office_id', $office_ids);
  $builder->where(array('voucher.voucher_date>=' => $start_date_of_reporting_month, 'voucher.voucher_date<=' => $end_date_of_reporting_month));

  $builder->join('voucher', 'voucher.voucher_id=voucher_detail.fk_voucher_id');
  $builder->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
  $builder->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');

  // $this->read_db->select_sum('voucher_detail_total_cost');
  // $this->read_db->where_in('voucher_type_effect_code', ['expense', 'bank_refund']);
  // $this->read_db->where_in('voucher.fk_office_id', $office_ids);
  // $this->read_db->where(array('voucher.voucher_date>=' => $start_date_of_reporting_month, 'voucher.voucher_date<=' => $end_date_of_reporting_month));

  // $this->read_db->join('voucher', 'voucher.voucher_id=voucher_detail.fk_voucher_id');
  // $this->read_db->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
  // $this->read_db->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');


  if (!empty($project_ids)) {
    $builder->join('project_allocation', 'project_allocation.project_allocation_id=voucher_detail.fk_project_allocation_id');
    $builder->whereIn('project_allocation.fk_project_id', $project_ids);

    // $this->read_db->join('project_allocation', 'project_allocation.project_allocation_id=voucher_detail.fk_project_allocation_id');
    // $this->read_db->where_in('project_allocation.fk_project_id', $project_ids);
  }

  if (!empty($office_bank_ids)) {
    $builder->join('office_bank_project_allocation', 'office_bank_project_allocation.fk_project_allocation_id=project_allocation.project_allocation_id');
    $builder->whereIn('office_bank_project_allocation.fk_office_bank_id', $office_bank_ids);

    // $this->read_db->join('office_bank_project_allocation', 'office_bank_project_allocation.fk_project_allocation_id=project_allocation.project_allocation_id');
    // $this->read_db->where_in('office_bank_project_allocation.fk_office_bank_id', $office_bank_ids);
  }

  $builder->whereIn('voucher.fk_status_id', $max_approval_status_ids);
  // $this->read_db->where_in('voucher.fk_status_id', $max_approval_status_ids);

  $voucher_detail_total_cost = $builder->get()->getRow()->voucher_detail_total_cost;

  return $voucher_detail_total_cost;
}

function officeProjects($office_ids, $reporting_month, $project_ids = [], $office_bank_ids = [])
{

  // log_message('error', json_encode(['office_ids' => $office_ids, 'reporting_month' => $reporting_month, 'project_ids' => $project_ids, 'office_bank_ids' => $office_bank_ids]));

  $start_date_of_reporting_month = date('Y-m-01', strtotime($reporting_month));
  // $end_date_of_reporting_month = date('Y-m-t', strtotime($reporting_month));
  $builder = $this->read_db->table('project_allocation');
  $builder->select(['project_id', 'project_name', 'funder_name', 'fk_office_id', 'project_allocation_amount']);
  $builder->whereIn('fk_office_id', $office_ids);
  $query_condition = "(project_start_date <= '" . $start_date_of_reporting_month . "' AND project_end_date IS NOT NULL AND project_end_date NOT LIKE '0000-00-00')";
  $builder->where($query_condition);
  $builder->where('project_is_default' , 0);
  $builder->join('project', 'project.project_id=project_allocation.fk_project_id');


  // $this->read_db->select(array('project_id', 'project_name', 'funder_name', 'fk_office_id', 'project_allocation_amount'));
  // $this->read_db->where_in('fk_office_id', $office_ids);
  // // $query_condition = "(project_end_date >= '" . $start_date_of_reporting_month . "' OR  project_allocation_extended_end_date >= '" . $start_date_of_reporting_month . "')";
  // $query_condition = "(project_start_date <= '" . $start_date_of_reporting_month . "' AND project_end_date IS NOT NULL AND project_end_date NOT LIKE '0000-00-00')";
  // $this->read_db->where($query_condition);

  // // Only list non default projects. There can be only 1 default project per accouting system
  // $this->read_db->where(array('project_is_default' => 0));

  // $this->read_db->join('project', 'project.project_id=project_allocation.fk_project_id');


  if (!empty($project_ids)) {
    $builder->whereIn('project_id', $project_ids);
    // $this->read_db->where_in('project_id', $project_ids);
  }

  if (!empty($office_bank_ids)) {
    $builder->join('office_bank_project_allocation', 'office_bank_project_allocation.fk_project_allocation_id=project_allocation.project_allocation_id');
    $builder->whereIn('office_bank_project_allocation.fk_office_bank_id', $office_bank_ids);
    // $this->read_db->join('office_bank_project_allocation', 'office_bank_project_allocation.fk_project_allocation_id=project_allocation.project_allocation_id');
    // $this->read_db->where_in('office_bank_project_allocation.fk_office_bank_id', $office_bank_ids);
  }
  $builder->join('funder', 'funder.funder_id=project.fk_funder_id');
  // $this->read_db->join('funder', 'funder.funder_id=project.fk_funder_id');

  $projects = $builder->get()->getResultArray();

  $ordered_array = [];

  foreach ($projects as $project) {
    $ordered_array[$project['project_id']]['project_name'] = $project['project_name'];
    $ordered_array[$project['project_id']]['funder_name'] = $project['funder_name'];
    $ordered_array[$project['project_id']]['office_id'] = $project['fk_office_id'];
    $ordered_array[$project['project_id']]['project_allocation_amount'] = $project['project_allocation_amount'];
  }

  //print_r($ordered_array);exit;

  return $ordered_array;
}

function updateBankStatementBalance()
{

  $post = $this->request->getPost();

  $financial_report_obj = $this->read_db->get_where(
    'financial_report',
    array(
      'fk_office_id' => $post['office_id'],
      'financial_report_month' => date('Y-m-01', strtotime($post['reporting_month']))
    )
  );

  $this->write_db->trans_start();

  $this->write_db->where(array('financial_report_id' => $financial_report_obj->row()->financial_report_id));
  //$update_financial_report_data['financial_report_statement_balance'] = $post['bank_statement_balance'];
  $update_financial_report_data['financial_report_statement_date'] = $post['statement_date'];
  $this->write_db->update('financial_report', $update_financial_report_data);

  $this->write_db->trans_complete();

  if ($this->write_db->trans_status() == false) {
    echo "Update failed";
  } else {
    echo "Updated successful";
  }
}

function getOpeningOustandingCheque($cheque_id)
{

  $bounced_chq_record = $this->financialReportLibrary->getOpeningOustandingCheque($cheque_id);


  echo $bounced_chq_record;
}

function updateBankSupportFundsAndOustandingChequeOpeningBalances($office_bank_id, $cheque_id, $reporting_month, $bounced_flag)
{

  echo $this->financialReportLibrary->updateBankSupportFundsAndOustandingChequeOpeningBalances($office_bank_id, $cheque_id, $reporting_month, $bounced_flag);
}

function clearTransactions()
{
  $post = $this->request->getPost();

  $db = $this->write_db;
  
  $db->transStart();
  $builder = $this->write_db->table('opening_outstanding_cheque');
  if (isset($post['opening_deposit_transit_id']) && $post['opening_deposit_transit_id'] > 0) {

    $update_data['opening_deposit_transit_is_cleared'] = 1;
    $update_data['opening_deposit_transit_cleared_date'] = date('Y-m-t', strtotime($post['reporting_month'])); //date('Y-m-t');

    if ($post['voucher_state'] == 1) {
      $update_data['opening_deposit_transit_is_cleared'] = 0;
      $update_data['opening_deposit_transit_cleared_date'] = null;
    }

    $builder->where(array('opening_deposit_transit_id' => $post['opening_deposit_transit_id']));
    $builder->update('opening_deposit_transit', $update_data);
  } elseif (isset($post['opening_outstanding_cheque_id']) && $post['opening_outstanding_cheque_id'] > 0) {
    $update_data['opening_outstanding_cheque_is_cleared'] = 1;
    $update_data['opening_outstanding_cheque_cleared_date'] = date('Y-m-t', strtotime($post['reporting_month'])); //date('Y-m-t');

    if ($post['voucher_state'] == 1) {
      $update_data['opening_outstanding_cheque_is_cleared'] = 0;
      $update_data['opening_outstanding_cheque_cleared_date'] = NULL; //'0000-00-00';
      $update_data['opening_outstanding_cheque_bounced_flag'] = 0;
    }

    $builder->where(array('opening_outstanding_cheque_id' => $post['opening_outstanding_cheque_id']));
    $builder->update( $update_data);
  } else {
    $update_data['voucher_cleared'] = 1;
    $update_data['voucher_cleared_month'] = date('Y-m-t', strtotime($post['reporting_month'])); //date('Y-m-t');

    if ($post['voucher_state'] == 1) {
      $update_data['voucher_cleared'] = 0;
      $update_data['voucher_cleared_month'] = null;
    }
    $builder2 = $this->write_db->table('voucher');
    $builder2->where(array('voucher_id' => $post['voucher_id']));

    $builder2->update($update_data);
  }

  $db->transComplete();

  if ($db->transStatus() == false) {
    echo false;
  } else {
    echo true;
  }
}

function uploadStatements()
{

  $post = $this->request->getPost();

  $office_banks = explode(",", $post['office_bank_ids']);


  // Check if a reconciliation record exists, if not create it
  $reconciliation_builder = $this->read_db->table('reconciliation');
  $reconciliation_builder->select('reconciliation_id');
  $reconciliation_builder->join('financial_report', 'financial_report.financial_report_id=reconciliation.fk_financial_report_id');
  $reconciliation_builder->where(array(
    'reconciliation.fk_office_bank_id' => $office_banks[0],
    'financial_report.fk_office_id' => $post['office_id'],
    'financial_report.financial_report_month' => $post['reporting_month']
  ));
  $reconciliation_obj = $reconciliation_builder->get();
  

  if ($reconciliation_obj->getNumRows() == 0) {
    // Create a reconciliation record
    $financial_report_builder = $this->read_db->table('financial_report');
    $financial_report_builder->select('financial_report_id');
    $financial_report_builder->where(array('fk_office_id' => $post['office_id'], 'financial_report_month' => $post['reporting_month']));
    $financial_report_id = $financial_report_builder->get()->getRow()->financial_report_id;

    $this->insertReconciliation($financial_report_id, $office_banks[0]);
  }

  $result = [];

  if (count($office_banks) == 1) {
    $builder = $this->read_db->table('reconciliation');
    $builder->select('reconciliation_id');
    $builder->join('financial_report', 'financial_report.financial_report_id=reconciliation.fk_financial_report_id');
    $builder->where(array(
      'reconciliation.fk_office_bank_id' => $office_banks[0],
      'fk_office_id' => $post['office_id'],
      'financial_report_month' => $post['reporting_month']
    ));
    $reconciliation_id =$builder->get()->getRow()->reconciliation_id;

    $storeFolder = upload_url('reconciliation', $reconciliation_id, [$office_banks[0]]);
    $result = $this->attachmentLibrary->uploadFiles($storeFolder);
    // if (
    //   is_array($this->attachmentLibrary->uploadFiles($storeFolder)) &&
    //   count($this->attachmentLibrary->uploadFiles($storeFolder)) > 0
    // ) {
    //   $result = $this->attachmentLibrary->uploadFiles($storeFolder);
    // }
  }

  echo json_encode($result);
}

function deleteStatement()
{
  // $path = $this->request->getPost('path');

  // $msg = "File deletion failed";
  // if (file_exists($path)) {
  //   if (unlink($path)) {
  //     $msg = "File deleted successful";
  //   }
  // }

  // echo $msg;
  $msg=get_phrase('delete_s3_object_failed', 'Bank statement not deleted.');

  //Get the values from the form 
  $path = $this->request->getPost('file_path');

  $file_name=$this->request->getPost('file_name');

  $attachment_id=(int)$this->request->getPost('id');

  $file_path=$path.'/'.$file_name;

  //Delete Object in S3 and the record in attachement Table.
  $deleted_flag=$this->attachmentLibrary->deleteUploadedDocument($attachment_id,$file_path);

  if($deleted_flag==1){
    $msg=get_phrase('delete_s3_object_success', 'Bank statement deleted successfully.');
  }
  echo $msg;


}

function reverseMfrSubmission($report_id)
{

  $success = get_phrase("financial_report_not_declined");

  $data['financial_report_is_submitted'] = 0;
  $builder = $this->write_db->table('financial_report');
  $builder->where(array('financial_report_id' => $report_id));
  $builder_result = $builder->update($data);
  
  if ($builder_result->getAffectedRows() > 0) {
    $success = get_phrase("financial_report_declined");
  }

  echo $success;
}

function submitFinancialReport()
{

  $post = $this->request->getPost();
  $post['financial_report_id'] = hash_id($post['financial_report_id'], 'encode');

  $message = 1; //'MFR Submitted Successful';

  // Check of Proof Of Cash
  $is_proof_of_cash_correct = $this->isProofOfCashCorrect($post['office_id'], $post['reporting_month']);
  
   // Check if the report has reconciled
  $report_reconciled = $this->checkIfReportHasReconciled($post['office_id'], $post['reporting_month']);

  
  // Check if the all vouchers have been approved
  $vouchers_approved = $this->checkIfMonthVouchersAreApproved($post['office_id'], $post['reporting_month']);

  // // Check if their is a bank statement
  $bank_statements_uploaded = $this->checkIfBankStatementsAreUploaded($post['office_id'], $post['reporting_month']);

  $budget_is_active = $this->checkIfBudgetIsActive($post['office_id'], $post['reporting_month']);

  if ((!$report_reconciled  || !$is_proof_of_cash_correct || !$vouchers_approved || !$bank_statements_uploaded || !$budget_is_active) && !$this->config->submit_mfr_without_controls) {
    $message = "You have missing requirements and report is not submitted. Check the following items:\n";
    $items = "";

    // log_message('error', $is_proof_of_cash_correct );
    if (!$is_proof_of_cash_correct) $items .= "-> Proof of Cash is correct\n";
    if (!$report_reconciled) $items .= "-> Report is reconciled\n";
    if (!$vouchers_approved) $items .= "-> All vouchers in the month are approved or journal is not empty\n";
    if (!$bank_statements_uploaded) $items .= "-> Bank statement uploaded\n";
    if(!$budget_is_active) $items .= "-> The current period budget for the office must be active\n";

    $message .= $items;
  } else {

    $office_id = $post['office_id'];
    $reporting_month = $post['reporting_month'];

    // Get next status Id
    $financial_report_information = $this->financialReportInformation($post['financial_report_id']);
    $next_status_id = $this->statusLibrary->nextStatus($financial_report_information['status_id']);
  
    // Log Fund Balances
    $fund_balances = $this->fundBalanceReport([$office_id], $reporting_month);
    $income_account_ids = array_column($fund_balances,'account_id');
    $fund_balance_amount = array_column($fund_balances,'month_closing_balance');
    $fund_closing_balances = array_combine($income_account_ids,$fund_balance_amount);
    $this->removeZeroBalances($fund_closing_balances);

    //month_fund_income_data
    $fund_balance_report = [];
    $cnt = 0;
    foreach($fund_balances as $fund_balance){
      $fund_balance_report[$cnt]['account_id'] = $fund_balance['account_id'];
      $fund_balance_report[$cnt]['month_opening_balance'] = $fund_balance['month_opening_balance'];
      $fund_balance_report[$cnt]['month_income'] = $fund_balance['month_income'];
      $fund_balance_report[$cnt]['month_expense'] = $fund_balance['month_expense'];
      $fund_balance_report[$cnt]['month_closing_balance'] = $fund_balance['month_closing_balance'];
      $cnt++;
    }

    // Log Project Balances
    $project_balances = $this->projectsBalanceReport([$office_id], $reporting_month)['body'];
    $project_ids = array_keys($project_balances);
    $project_balance_amount = array_column($project_balances,'closing_balance');
    $project_closing_balances = array_combine($project_ids,$project_balance_amount);
    $this->removeZeroBalances($project_closing_balances);

    // Log Total Cash Balances
    $total_cash_balance = $this->_proofOfCash([$office_id], $reporting_month); // To remove this line from being logged in the future since the cash breakdown has catered for it in more details
    $total_cash_balance['cash_breakdown'] = $this->journalLibrary->cashBreakdown($office_id, $reporting_month);

    // Log Total Statement Balances
    $bank_reconciliation = $this->bankReconciliation([$office_id], $reporting_month, false, false);
    $statement_balance = ['bank_statement_date' => $bank_reconciliation['bank_statement_date'], 'bank_statement_balance' => $bank_reconciliation['bank_statement_balance']];

    $financial_report_is_reconciled = $bank_reconciliation['is_book_reconciled'] == 'true' || $bank_reconciliation['is_book_reconciled'] == true ? 1 : 0;
    

    // Log Outstanding cheques Balances
    $outstanding_cheques = $this->financialReportLibrary->listOustandingChequesAndDeposits([$office_id], $reporting_month, 'expense', 'bank_contra', 'bank');
    $outstanding_cheques_balance = [];
    $overdue_outstanding_cheques = [];

    // log_message('error', json_encode($outstanding_cheques));

    if(!empty($outstanding_cheques)){
      $cnt = 0;
      foreach($outstanding_cheques as $outstanding_cheque){
        $outstanding_cheques_balance[$cnt]['voucher_id'] = isset($outstanding_cheque['voucher_id']) ? $outstanding_cheque['voucher_id'] : NULL;
        $outstanding_cheques_balance[$cnt]['voucher_date'] = $outstanding_cheque['voucher_date'];
        $outstanding_cheques_balance[$cnt]['voucher_number'] = isset($outstanding_cheque['voucher_number']) ? $outstanding_cheque['voucher_number'] : NULL;
        $outstanding_cheques_balance[$cnt]['cheque_number'] = $outstanding_cheque['voucher_cheque_number'];
        $outstanding_cheques_balance[$cnt]['description'] = $outstanding_cheque['voucher_description'];
        $outstanding_cheques_balance[$cnt]['office_bank_id'] = $outstanding_cheque['fk_office_bank_id'];
        // $outstanding_cheques_balance[$cnt]['office_bank_name'] = $outstanding_cheque['office_bank_name'];
        $outstanding_cheques_balance[$cnt]['amount'] = $outstanding_cheque['voucher_detail_total_cost'];

        $voucher_date = strtotime($outstanding_cheque['voucher_date']);
        $reportingTimestamp = strtotime($reporting_month);

        // Calculate the difference in seconds between the two dates
        $timeDifference = $reportingTimestamp - $voucher_date;

        // Number of seconds in 6 months (approximately)
        $sixMonthsInSeconds = 15778800;

        if($timeDifference >= $sixMonthsInSeconds){
          $overdue_outstanding_cheques[$cnt] = $outstanding_cheques_balance[$cnt];
        }

        $cnt++;
      }
    }

    // Log Transit Deposit Balances
    $transit_deposits = $this->financialReportLibrary->listOustandingChequesAndDeposits([$office_id], $reporting_month, 'income', 'cash_contra', 'bank');
    $transit_deposit_balance = [];
    $overdue_transit_deposit = [];

    if(!empty($transit_deposits)){
      $cnt = 0;
      foreach($transit_deposits as $transit_deposit){
        $transit_deposit_balance[$cnt]['voucher_id'] = isset($transit_deposit['voucher_id']) ? $transit_deposit['voucher_id'] : NULL;
        $transit_deposit_balance[$cnt]['voucher_date'] = $transit_deposit['voucher_date'];
        $transit_deposit_balance[$cnt]['voucher_number'] = isset($transit_deposit['voucher_number']) ? $transit_deposit['voucher_number'] : NULL;
        $transit_deposit_balance[$cnt]['description'] = $transit_deposit['voucher_description'];
        $transit_deposit_balance[$cnt]['office_bank_id'] = $transit_deposit['fk_office_bank_id'];
        // $transit_deposit_balance[$cnt]['office_bank_name'] = $outstanding_cheque['office_bank_name'];
        $transit_deposit_balance[$cnt]['amount'] = $transit_deposit['voucher_detail_total_cost'];

        $voucher_date = strtotime($outstanding_cheque['voucher_date']);
        $reportingTimestamp = strtotime($reporting_month);

        // Calculate the difference in seconds between the two dates
        $timeDifference = $reportingTimestamp - $voucher_date;

        // Number of seconds in 6 months (approximately)
        $twoMonthsInSeconds = 5256192;

        if($timeDifference >= $twoMonthsInSeconds){
          $overdue_transit_deposit[$cnt] = $transit_deposit_balance[$cnt];
        }

        $cnt++;
      }
    }

    // Log Expense Report Balances
    $expense_report = $this->expenseReport([$office_id], $reporting_month);
    $expense_report_balance = [];

    if(!empty($expense_report)){
      $cnt = 0;
      foreach($expense_report as $report){
        if(!isset($report['income_account'])) continue;
        $expense_report_balance[$cnt]['income_account_id'] = $report['income_account']['income_account_id'];
        $inner = 0;
        foreach($report['expense_accounts'] as $expense_account){
          $expense_report_balance[$cnt]['expense_report'][$inner]['expense_account_id'] = $expense_account['expense_account']['expense_account_id'];
          $expense_report_balance[$cnt]['expense_report'][$inner]['month_expense'] = $expense_account['month_expense'];
          $expense_report_balance[$cnt]['expense_report'][$inner]['month_expense_to_date'] = $expense_account['month_expense_to_date'];
          $expense_report_balance[$cnt]['expense_report'][$inner]['budget_to_date'] = $expense_account['budget_to_date'];
          $expense_report_balance[$cnt]['expense_report'][$inner]['budget_variance'] = $expense_account['budget_to_date'] - $expense_account['month_expense_to_date'];
          $expense_report_balance[$cnt]['expense_report'][$inner]['budget_variance_percent'] = $expense_account['budget_to_date'] > 0 ? (($expense_account['budget_to_date'] - $expense_account['month_expense_to_date'])/$expense_account['budget_to_date']) : -1;
          $inner++;
        }
        $cnt++;
      }
    }

     // Post all vouchers
     $month_vouchers = $this->journalLibrary->journalRecords($post['office_id'], $post['reporting_month']); 

    // Post financial reatio historical data 
    $financial_ratios = $this->toDateFinancialRatios($post['office_id'],$post['reporting_month'] , $expense_report_balance, $fund_balance_report);
    $db = $this->write_db;
    $db->transStart();

    // Update financial report table
    $builder = $this->write_db->table('financial_report');
    $builder->where(array('fk_office_id' => $post['office_id'], 'financial_report_month' => $post['reporting_month']));

    $current_budget = $this->budgetLibrary->getBudgetByOfficeCurrentTransactionDate($post['office_id']);
    
    $update_data['financial_report_is_submitted'] = 1;
    $update_data['closing_fund_balance_data'] = json_encode($fund_closing_balances);
    $update_data['closing_project_balance_data'] = json_encode($project_closing_balances);
    $update_data['closing_total_cash_balance_data'] = json_encode($total_cash_balance);
    $update_data['closing_total_statement_balance_data'] = json_encode($statement_balance);
    $update_data['closing_outstanding_cheques_data'] = json_encode($outstanding_cheques_balance);
    $update_data['closing_transit_deposit_data'] = json_encode($transit_deposit_balance);
    $update_data['closing_expense_report_data'] = json_encode($expense_report_balance);
    $update_data['closing_overdue_cheques_data'] = json_encode($overdue_outstanding_cheques);
    $update_data['closing_overdue_deposit_data'] = json_encode($overdue_transit_deposit);
    $update_data['financial_report_is_reconciled'] = $financial_report_is_reconciled;
    $update_data['month_fund_balance_report_data'] = json_encode($fund_balance_report);
    $update_data['month_vouchers'] = json_encode($month_vouchers);
    $update_data['to_date_financial_ratios'] = json_encode($financial_ratios);
    $update_data['financial_report_submitted_date'] = date('Y-m-d');
    $update_data['fk_budget_id'] = $current_budget['budget_id'];
    $update_data['fk_status_id'] = $next_status_id;
    // log_message('error', json_encode($update_data));

    $builder->update($update_data);

    // Deactivate non default cheque book
    $this->officeBankLibrary->deactivateNonDefaultOfficeBankByOfficeId($office_id, $post['reporting_month']);

    if (method_exists($this->financialReportLibrary, 'postApprovalActionEvent')) {
      $this->financialReportLibrary->postApprovalActionEvent([
        'item' => 'financial_report',
        'post' => [
          'item_id' => $post['financial_report_id'],
          'next_status' => $next_status_id,
          'current_status' => $financial_report_information['status_id']
        ]
      ]);
    }

    $db->transComplete();

    if ($db->transStatus() === FALSE)
    {
      
    }

    //parent::approve();
  }

  echo $message;
}

 /**
   * removeZeroBalances
   * 
   * Filter balance amounts that are not zero. This method is called by reference
   *
   * @author nkarisa <nkarisa@ke.ci.org> 
   * @param array $balances - Raw list of balances
   * 
   * @return void
   */
  private function removeZeroBalances(&$balances)
  {
    foreach ($balances as $account_id => $amount) {
      if ($amount == 0) {
        unset($balances[$account_id]);
      }
    }
  }

    /**
   * is_proof_of_cash_correct: Check if the proof of cash is correct before submitting a financial report
   * 
   * @author Nicodemus Karisa Mwambire/ Modified By Livingstone Onduso
   * @reviewer Livingstone
   * @reviewed_date None
   * @access private
   * 
   * @param $is_proof_of_cash_correct
   */

   private function isProofOfCashCorrect($office_id, $reporting_month, $project_ids = [], $office_bank_ids = []): bool
   {
 
     $fund_balance_report = $this->fundBalanceReport([$office_id], $reporting_month, $project_ids, $office_bank_ids);
 
     $sum_month_opening_balance = array_sum(array_column($fund_balance_report, 'month_opening_balance'));
     $sum_month_income = array_sum(array_column($fund_balance_report, 'month_income'));
     $sum_month_expense = array_sum(array_column($fund_balance_report, 'month_expense'));
 
     $total_closing_fund_balance = $sum_month_opening_balance + $sum_month_income - $sum_month_expense;
 
     $total_cash = array_sum($this->_proofOfCash([$office_id], $reporting_month, $project_ids, $office_bank_ids));
 
     $total_closing_fund_balance = floor($total_closing_fund_balance);
 
     $total_cash = floor($total_cash);

     $is_proof_of_cash_correct =(float)$total_cash == (float)$total_closing_fund_balance ? true : false;
 
     return $is_proof_of_cash_correct;
   }

  /**
   * @param float $number
   * @param int decimals
   * @return float
   */

   function truncate($number, $decimals = "0")
   {
    //  $power = pow(10, $decimals);
    //  if ($number > 0) {
    //    return floor($number * $power) / $power;
    //  } else {
    //    return ceil($number * $power) / $power;
    //  }

    return round($number, $decimals, PHP_ROUND_HALF_UP);
   }   

   function checkIfBudgetIsActive($office_id, $reporting_month)
   {
     // log_message('error', json_encode([$office_id, $reporting_month]));
 
     $flag = false;
 
     
     $custom_financial_year = $this->customFinancialYearLibrary->getDefaultCustomFinancialYearIdByOffice($office_id, true);
     $budget_tag_id = $this->budgetTagLibrary->getBudgetTagIdBasedOnReportingMonth($office_id, $reporting_month, $custom_financial_year)['budget_tag_id'];//$this->get_budget_tag_id_by_date($office_id, $reporting_month);
 
     $budget_year = $this->getFinancialYear($office_id, $reporting_month);
 
     $active_budget_status_ids = $this->getActiveBudgetStatusId($office_id);
 
     // log_message('error', json_encode(['budget_tag_id' => $budget_tag_id, 'budget_year' => $budget_year, 'active_budget_status_ids' => $active_budget_status_ids]));
      $budget_builder = $this->read_db->table('budget');
      $budget_builder->select('budget_id');
      $budget_builder->where(
        array(
        'fk_office_id' => $office_id,
        'fk_budget_tag_id' => $budget_tag_id,
        'budget_year' => $budget_year
      )); 
      $budget_builder->whereIn('fk_status_id', $active_budget_status_ids); 
      $budget_obj = $budget_builder->get();

    //  $this->read_db->where(
    //    array(
    //      'fk_office_id' => $office_id,
    //      'fk_budget_tag_id' => $budget_tag_id,
    //      'budget_year' => $budget_year
    //    )
    //  );
 
    //  $this->read_db->where_in('fk_status_id', $active_budget_status_ids);
 
    //  $budget_obj = $this->read_db->get('budget');
 
 
     if ($budget_obj->getNumRows() > 0) {
       // log_message('error', json_encode($budget_obj->result_array()));
       $flag = true;
     }
 
     return $flag;
   }

   function getActiveBudgetStatusId($office_id)
   {
 
     // $active_budget_status = 0;
 
     // modify get_max_approval_status_id to consider a specific office in case a user in another country attempts to submit MFR for office in another country
     $active_budget_statuses = $this->statusLibrary->getMaxApprovalStatusId('budget');
 
     // if(count($active_budget_statuses) == 1) { // Greater than 1 means that the logged user is not above country level
     //     $active_budget_status = $active_budget_statuses[0];
     // }
 
     // log_message('error', json_encode($active_budget_status));
 
     return $active_budget_statuses;
   }

   function getFinancialYear($office_id, $reporting_month)
   {
 
     // $fy = get_fy($reporting_month);
 
     // log_message('error',$fy);
     
     $default_custom_financial_year = $this->customFinancialYearLibrary->getDefaultCustomFinancialYearIdByOffice($office_id);
 
     $fy = calculateFinancialYear($reporting_month, $default_custom_financial_year['custom_financial_year_start_month']);
 
     return $fy;
   }

   function getBudgetTagIdByDate($office_id, $reporting_month)
   {
 
     $budget_tag_id = 0;
 
     $month_number = date('n', strtotime($reporting_month));
 
     $month_quarter = financial_year_quarter_months($month_number)['quarter_number'];
 
     $this->read_db->select(array('budget_tag_id', 'month_number'));
     $this->read_db->where(array('office_id' => $office_id));
     $this->read_db->join('account_system', 'account_system.account_system_id=budget_tag.fk_account_system_id');
     $this->read_db->join('office', 'office.fk_account_system_id=account_system.account_system_id');
     $this->read_db->join('month', 'month.month_id=budget_tag.fk_month_id');
     $budget_tags = $this->read_db->get('budget_tag')->result_array();
 
     foreach ($budget_tags as $budget_tag) {
       $quarter_number = financial_year_quarter_months($budget_tag['month_number'])['quarter_number'];
 
       if ($quarter_number == $month_quarter) {
         $budget_tag_id = $budget_tag['budget_tag_id'];
       }
     }
 
     // log_message('error', json_encode($budget_tag_id));
 
     return $budget_tag_id;
   }

   function checkIfReportHasReconciled($office_id, $reporting_month)
   {
     //return false;
 
     $bank_reconciliation_statement = $this->bankReconciliation([$office_id], $reporting_month, false, true);
 
     $is_book_reconciled = $bank_reconciliation_statement['is_book_reconciled'];
 
     return $is_book_reconciled;
     //echo json_encode($bank_reconciliation_statement);
   }

   function checkIfMonthVouchersAreApproved($office_id, $reporting_month)
   {
     //return false;
     return $this->voucherLibrary->checkIfMonthVouchersAreApproved($office_id, $reporting_month);
   }

   function checkIfBankStatementsAreUploaded($office_id, $reporting_month)
   {
    $office_bank_builder = $this->read_db->table('office_bank');
    $office_bank_builder->select('office_bank_id');
    $office_bank_builder->where(array('fk_office_id' => $office_id, 'office_bank_is_active' => 1));
    $office_bank_builder_result = $office_bank_builder->get();
    //  $this->read_db->select(array('office_bank_id'));
    //  $this->read_db->where(array('fk_office_id' => $office_id, 'office_bank_is_active' => 1));
    //  $office_bank = $this->read_db->get('office_bank');
 
     $statements_uploaded = true;
 
     $approve_item_builder = $this->read_db->table('approve_item');
     $approve_item_builder->select('approve_item_id');
     $approve_item_builder->where(array('approve_item_name' => 'reconciliation'));
     $reconciliation_approve_item_id = $approve_item_builder->get()->getRow()->approve_item_id;
    //  $reconciliation_approve_item_id = $this->read_db->get_where(
    //    'approve_item',
    //    array('approve_item_name' => 'reconciliation')
    //  )->row()->approve_item_id;
 
     
     foreach ($office_bank_builder_result->getResultObject() as $office_bank) {
 
       $is_office_bank_obselete = $this->officeBankLibrary->isOfficeBankObselete($office_bank->office_bank_id, $reporting_month);
 
       if($is_office_bank_obselete){
         continue;
       }
       
       $attachment_builder = $this->read_db->table('attachment');
       $attachment_builder->select('attachment.attachment_id');
       $attachment_builder->where(array(
        'reconciliation.fk_office_bank_id' => $office_bank->office_bank_id,
        'attachment.fk_approve_item_id' => $reconciliation_approve_item_id,
        'financial_report_month' => $reporting_month
      ));
      $attachment_builder->join('reconciliation', 'reconciliation.reconciliation_id=attachment.attachment_primary_id');
      $attachment_builder->join('financial_report', 'financial_report.financial_report_id=reconciliation.fk_financial_report_id');
      $attachment_obj = $attachment_builder->get();

      //  $this->read_db->where(array(
      //    'reconciliation.fk_office_bank_id' => $office_bank->office_bank_id,
      //    'attachment.fk_approve_item_id' => $reconciliation_approve_item_id,
      //    'financial_report_month' => $reporting_month
      //  ));
 
      //  $this->read_db->join('reconciliation', 'reconciliation.reconciliation_id=attachment.attachment_primary_id');
      //  $this->read_db->join('financial_report', 'financial_report.financial_report_id=reconciliation.fk_financial_report_id');
      //  $attachment_obj = $this->read_db->get('attachment');
 
       if ($attachment_obj->getNumRows() == 0) {
         $statements_uploaded = false;
         break;
       }
     }
 
     return $statements_uploaded;
   }


   function updateBankReconciliationBalance()
   {
     $post = $_POST;
 
     $db = $this->write_db;
     $db->transStart();
    // log_message('error', json_encode($post));
 
     if (
       count($post['office_ids']) > 1 ||
       (isset($post['project_ids']) && is_array($post['project_ids']) && count($post['project_ids']) > 1) ||
       (isset($post['office_bank_ids']) && is_array($post['office_bank_ids']) && count($post['office_bank_ids']) > 1)
     ) {
       // This piece f code will never run since the statement balance field is not present in the view when the above is met
       echo "Cannot update balances when multiple offices, banks or projects are selected";
     } else {
      // log_message('error', json_encode(array('financial_report_month' => $post['reporting_month'], 'offices' => $post['office_ids'][0],'id'=>$this->id)));
      if(intval($post['office_ids'][0]) == 0){
        $financial_report_id = hash_id($post['office_ids'][0], 'decode');
      }else{
        $financial_report_builder1 = $this->read_db->table('financial_report');
        $financial_report_builder1->select('financial_report_id');
        $financial_report_builder1->where(array('financial_report_month' => $post['reporting_month'], 'fk_office_id' => $post['office_ids'][0]));
        $financial_report_result = $financial_report_builder1->get();
        $financial_report_id = $financial_report_result->getRow()->financial_report_id;
      }
       
      
      // $financial_report_id = hash_id($post['office_ids'][0], 'decode');
      // $financial_report_id = $this->read_db->get_where(
      //    'financial_report',
      //    array('financial_report_month' => $post['reporting_month'], 'fk_office_id' => $post['office_ids'][0])
      //  )->row()->financial_report_id;
 
       $office_bank_id = 0;
 
       if (isset($post['office_bank_ids']) && is_array($post['office_bank_ids']) && !empty($post['office_bank_ids'])) {
         $office_bank_id = $post['office_bank_ids'][0];
 
         $condition_array = array('fk_financial_report_id' => $financial_report_id, 'fk_office_bank_id' => $office_bank_id);
       } elseif (isset($post['project_ids'])  && is_array($post['project_ids']) && !empty($post['project_ids'])) {
          $builder2 = $this->read_db->table('office_bank');
          $builder2->join('office_bank_project_allocation', 'office_bank_project_allocation.fk_office_bank_id=office_bank.office_bank_id');
          $builder2->join('project_allocation', 'project_allocation.project_allocation_id=office_bank_project_allocation.fk_project_allocation_id');
          $builder2->select('office_bank_id');
          $builder2->where(array('fk_project_id' => $post['project_ids'][0]));
          $office_bank_id_result = $builder2->get();
          $office_bank_id = $office_bank_id_result->getRow()->office_bank_id;
        //  $office_bank_id = $this->read_db->get_where(
        //    'office_bank',
        //    array('fk_project_id' => $post['project_ids'][0])
        //  )->row()->office_bank_id;
 
         $condition_array = array('fk_financial_report_id' => $financial_report_id, 'fk_office_bank_id' => $office_bank_id);
       } else {
         // This piece will never run since reconciliation done when atleast 1 bank account is selected in the MFR filter
         $condition_array = array('fk_financial_report_id' => $financial_report_id);
       }
       // Check if reconciliation record exists and update else create
       $builder3 = $this->read_db->table('reconciliation');
       $builder3->select('*');
       $builder3->where($condition_array);
       $reconciliation_record_result = $builder3->get();
       $reconciliation_record = $reconciliation_record_result->getNumRows();
      //  $reconciliation_record = $this->read_db->get_where('reconciliation', $condition_array)->num_rows();
      
      $builder4 = $this->write_db->table('reconciliation');
       if ($reconciliation_record == 0) {
        $data = [
          'reconciliation_track_number'=>$this->grantsLibrary->generateItemTrackNumberAndName('reconciliation')['reconciliation_track_number'],
          'reconciliation_name'=>$this->grantsLibrary->generateItemTrackNumberAndName('reconciliation')['reconciliation_name'],
          'fk_financial_report_id'=>$financial_report_id,
          'fk_office_bank_id'=>$office_bank_id,
          'reconciliation_statement_balance'=>$post['balance'],
          'reconciliation_suspense_amount'=>0,
          'reconciliation_created_by'=>$this->session->user_id,
          'reconciliation_created_date'=>date('Y-m-d'),
          'reconciliation_last_modified_by'=>$this->session->user_id,
          'fk_approval_id'=>$this->grantsLibrary->insertApprovalRecord('reconciliation'),
          'fk_status_id'=>$this->statusLibrary->initialItemStatus('reconciliation')

        ];
        //  $data['reconciliation_track_number'] = $this->grantsLibrary->generateItemTrackNumberAndName('reconciliation')['reconciliation_track_number'];
        //  $data['reconciliation_name'] = $this->grantsLibrary->generateItemTrackNumberAndName('reconciliation')['reconciliation_name'];
 
        //  $data['fk_financial_report_id'] = $financial_report_id;
        //  $data['fk_office_bank_id'] = $office_bank_id;
        //  $data['reconciliation_statement_balance'] = $post['balance'];
        //  $data['reconciliation_suspense_amount'] = 0;
 
        //  $data['reconciliation_created_by'] = $this->session->user_id;
        //  $data['reconciliation_created_date'] = date('Y-m-d');
        //  $data['reconciliation_last_modified_by'] = $this->session->user_id;
 
        //  $data['fk_approval_id'] = $this->grantsLibrary->insertApprovalRecord('reconciliation');
        //  $data['fk_status_id'] = $this->statusLibrary->initialItemStatus('reconciliation');
 
         //echo $this->grants_model->initial_item_status('reconciliation'); exit(); 1534
         
         $builder4->insert($data);
        //  $this->write_db->insert('reconciliation', $data);
       } else {
 
         $condition_array = array('fk_financial_report_id'=>$financial_report_id);
         //  print_r($condition_array) ;exit();
 
         $builder4->where($condition_array);
 
         $data = [
            'reconciliation_statement_balance' => $post['balance']
         ];
         $builder4->update($data);
       }
 
 
 
       $db->transComplete();
 
       if ($db->transStatus() == false) {
         echo "Error in updating bank reconciliation balance";
       } else {
         echo "Update completed";
       }
     }
   }

   function insertReconciliation($financial_report_id, $office_bank_id, $statement_balance = 0, $suspense_amount = 0)
   {
     $data['reconciliation_track_number'] = $this->grantsLibrary->generateItemTrackNumberAndName('reconciliation')['reconciliation_track_number'];
     $data['reconciliation_name'] = $this->grantsLibrary->generateItemTrackNumberAndName('reconciliation')['reconciliation_name'];
 
     $data['fk_financial_report_id'] = $financial_report_id;
     $data['fk_office_bank_id'] = $office_bank_id;
     $data['reconciliation_statement_balance'] = $statement_balance;
     $data['reconciliation_suspense_amount'] = $suspense_amount;
 
     $data['reconciliation_created_by'] = $this->session->user_id;
     $data['reconciliation_created_date'] = date('Y-m-d');
     $data['reconciliation_last_modified_by'] = $this->session->user_id;
 
     $data['fk_approval_id'] = $this->grantsLibrary->insertApprovalRecord('reconciliation');
     $data['fk_status_id'] = 0; //$this->grants_model->initial_item_status('reconciliation');
 
     $builder = $this->write_db->table('reconciliation');
     $builder->insert($data);
    //  $this->write_db->insert('reconciliation', $data);
 
     //return json_encode($data);
   }

   public function fund_balance_report()
   {
 
     $post = $this->request->getPost();
 
     $office_ids = [$post['office_id']];
     $reporting_month = $post['reporting_month'];
     $project_ids = [];
     $office_bank_ids = [];
 
     $office_banks = $this->getOfficeBanks($office_ids, $reporting_month);
 
     if (count($office_banks) > 1) {
       // log_message('error', json_encode($office_banks));
       $project_ids = isset($post['project_ids']) && $post['project_ids'] != "" ? explode(",", $post['project_ids']) : [];
       $office_bank_ids = isset($post['office_bank_ids']) && $post['office_bank_ids'] != "" ? explode(",", $post['office_bank_ids']) : [];
     }
 
     // log_message('error', json_encode($office_banks));
 
     $data['result']['fund_balance_report'] = $this->fundBalanceReport($office_ids, $reporting_month, $project_ids, $office_bank_ids);
 
    //  echo $this->load->view('financial_report/includes/include_fund_balance_report.php', $data, true);
    return view('financial_report/includes/include_fund_balance_report.php', $data);
   }

   public function proofOfCash()
   {
 
     $post = $this->request->getPost();
 
     $office_ids = [$post['office_id']];
     $reporting_month = $post['reporting_month'];
     $project_ids = isset($post['project_ids']) && $post['project_ids'] != "" ? explode(",", $post['project_ids']) : [];
     $office_bank_ids = isset($post['office_bank_ids']) && $post['office_bank_ids'] != "" ? explode(",", $post['office_bank_ids']) : [];
 
     $data['proof_of_cash'] = $this->proofOfCash($office_ids, $reporting_month, $project_ids, $office_bank_ids);
 
    //  echo $this->load->view('financial_report/includes/include_proof_of_cash.php', $data, true);
    $proof_of_cash = view('financial_report/includes/include_proof_of_cash.php', $data);
    return $proof_of_cash;
   }

  //  //method used to get the office id using the financial report id
   public function getOfficeId2($financial_report_id){
    $builder = $this->read_db->table('financial_report');
    $result = $builder->where('financial_report_id', $financial_report_id)->get()->getRow()->fk_office_id;
    return $result;
  }

}
