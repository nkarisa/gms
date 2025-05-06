<?php

namespace App\Controllers\Web\Grants;

use App\Controllers\Web\WebController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Libraries\{Core, Grants};
use App\Enums\VoucherTypeEffectEnum;

class Voucher extends WebController
{
  protected $library;

  function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
  {
    parent::initController($request, $response, $logger);

    $this->library = new Grants\VoucherLibrary();
  }

  function result($id = "", $parentTable = null)
  {
    $result = parent::result($id, $parentTable);

    $statusLibrary = new Core\StatusLibrary();
    $requestLibrary = new Grants\RequestLibrary();
    
    if ($this->action == 'view') {
      $result = $this->library->getTransactionVoucher($this->id);
      $status_data = $this->libs->actionButtonData($this->controller, $result['account_system_id']);
      $result['is_voucher_cancellable'] = $this->library->isVoucherCancellable($status_data, $result['header']);
      $result['check_expenses_aganist_income'] = $this->library->checkPendingExpensesExceedsTotalIncome($result['header']);
      $result['status_data'] = $status_data;
      $result['voucher_status_is_max'] = $statusLibrary->isStatusIdMax('voucher', hash_id($this->id, 'decode'));
    } elseif ($this->action == 'multiFormAdd') {
      $result['office_has_request'] = $requestLibrary->getOfficeRequestCount() == 0 ? false : true;
    } elseif ($this->action == 'edit') {
      //$result = [];
      
     //$this->id=$this->request->getUri()->getSegment(3);

      $result['voucher_header_info'] = $this->library->getVoucherHeaderToEdit(hash_id($this->id, 'decode'));
    }

    return $result;
  }

  function printableVoucher()
  {
    $post = $this->request->getPost();
    $vouchers_ids = $post['voucher_ids'];
    $create_mass_vouchers = [];

    $voucherLibrary = new Grants\VoucherLibrary();
    $statusLibrary = new Core\StatusLibrary();

    foreach ($vouchers_ids as $voucher_id) {
      $voucher = $voucherLibrary->getTransactionVoucher(hash_id($voucher_id, 'encode'));
      $create_mass_vouchers[$voucher_id] = $voucher;
      $status_data = $statusLibrary->actionButtonData('voucher', $voucher['account_system_id']);
      $create_mass_vouchers[$voucher_id]['is_voucher_cancellable'] = $voucherLibrary->isVoucherCancellable($status_data, $voucher['header']);
      $create_mass_vouchers[$voucher_id]['status_data'] = $status_data;
    }

    $data['vouchers'] = $create_mass_vouchers;
    $data['journal_id'] = $post['journal_id'];
    $printable_vouchers = view('voucher/mass_print_voucher_view', $data);

    return $printable_vouchers;
  }

  function computeNextVoucherNumber()
  {
    $office_id = $this->request->getPost('office_id');
    $voucherNumber = $this->library->getVoucherNumber($office_id);
    return $this->response->setJSON(compact('voucherNumber'));
  }

  function getOfficeVoucherDate()
  {
      $office_id = $this->request->getPost('office_id');
      $next_vouching_date = $this->library->getVoucherDate($office_id);
      $last_vouching_month_date = date('Y-m-t', strtotime($next_vouching_date));

      $voucher_date_field_dates = ['next_vouching_date' => $next_vouching_date, 'last_vouching_month_date' => $last_vouching_month_date];

      return $this->response->setJSON(($voucher_date_field_dates));
  }

    /**
   * get_active_voucher_types(): get json string of voucher types
   * @author  Livingstone Onduso
   * @dated: 4/06/2023
   * @access public
   * @return void
   * @param int $office_id, $transaction_date
   */
  function getActiveVoucherTypes(int $office_id, string $transaction_date): ResponseInterface
  {

    $account_system_id = $this->library->officeAccountSystem($office_id)->account_system_id;
    $voucher_types = $this->library->getActiveVoucherTypes($account_system_id, $office_id, $transaction_date);

    return $this->response->setJSON($voucher_types);
  }

  function checkVoucherTypeAffectsBank($office_id, $voucher_type_id = 0)
  {

    $response['is_transfer_contra'] = false;
    $response['office_banks'] = [];
    $response['office_cash'] = [];
    $response['is_bank_payment'] = false;
    $response['is_bank_refund'] = false;

    $officeBankLibrary = new Grants\OfficeBankLibrary();

    $response['voucher_type_requires_cheque_referencing'] = $this->library->voucherTypeRequiresChequeReferencing($voucher_type_id);

    $voucher_type_effect_and_code = $this->library->voucherTypeEffectAndCode($voucher_type_id);

    $voucher_type_effect = $voucher_type_effect_and_code->voucher_type_effect_code;
    $voucher_type_account = $voucher_type_effect_and_code->voucher_type_account_code;

    $office_accounting_system = $this->library->officeAccountSystem($office_id);

    if (count($officeBankLibrary->getActiveOfficeBanks($office_id)) > 1 && $voucher_type_account == 'cash') {
      $response['office_banks'] = $this->library->getOfficeBanks($office_id);
    }

    if ($voucher_type_account == 'cash' || $voucher_type_effect == 'bank_contra' || $voucher_type_effect == 'cash_to_cash_contra') {
      $response['office_cash'] = $this->read_db->table('office_cash')
      ->select(array('office_cash_id as item_id', 'office_cash_name as item_name'))
      ->getWhere(
        array('fk_account_system_id' => $office_accounting_system->account_system_id, 'office_cash_is_active' => 1)
      )->getResultArray();
    }

    if (
        $voucher_type_account == 'bank' || 
        $voucher_type_effect == 'cash_contra' || 
        $voucher_type_effect == 'bank_to_bank_contra' ||
        $voucher_type_effect == 'prepayments' ||
        $voucher_type_effect == 'payments' ||
        $voucher_type_effect == 'disbursements'
        ) {
      $response['office_banks'] = $this->library->getOfficeBanks($office_id);
    }

    if ($voucher_type_effect == 'bank_to_bank_contra' || $voucher_type_effect == 'cash_to_cash_contra') {
      $response['is_transfer_contra'] = true;
    }

    if (
        $voucher_type_effect == 'bank_to_bank_contra' || 
        $voucher_type_effect == 'bank_contra' || 
        ($voucher_type_account == 'bank' && $voucher_type_effect == 'expense') ||
        $voucher_type_effect == 'prepayments' || 
        $voucher_type_effect == 'disbursements'
        ) {
      $response['is_bank_payment'] = true;
    }

    if($voucher_type_effect == 'bank_refund'){
      $response['is_bank_refund'] = true;
    }

    return $this->response->setJSON($response);
  }

    /**
   * get_count_of_request
   * @param 
   * @return ResponseInterface
   * @author: Onduso
   * @Date: 4/12/2020
   */
  function getCountOfUnvouchedRequest($office_id): ResponseInterface
  {
    $count = $this->library->getCountOfUnvouchedRequest($office_id);
    return $this->response->setJSON(compact('count'));
  }

  /**
   *compute_bank_balance(): Returns json string of unapproaved float bank amount to used in a ajax call
   * @author Livingstone Onduso: Dated 08-04-2023
   * @access public
   * @return void - json string
   */
  function computeBankBalance($edit_voucher = 0): ResponseInterface
  {
    $post = $this->request->getPost();
    $financialReportLibary = new Grants\FinancialReportLibrary();

    $office_id = $post['office_id'];
    $office_bank_id = $post['office_bank_id'];
    $reporting_month = date('Y-m-01', strtotime($post['transaction_date']));

    //Total bank approved/fully paid vouchers
    $fully_approved_vouchers_bank_balance = $financialReportLibary->computeCashAtBank([$office_id], $reporting_month, [], [$office_bank_id], true);

    //Income to bank
    $unapproved_cash_recieved_to_bank_vouchers = $this->library->unapprovedMonthVouchers($office_id, $reporting_month,  'income', 'bank', 0, $office_bank_id);
    $unapproved_petty_cash_rebank_vouchers = $this->library->unapprovedMonthVouchers($office_id, $reporting_month,  'cash_contra', 'cash', 0, $office_bank_id);

    //Bank expenses unapproved vouchers 
    $unapproved_bank_expense_vouchers = $this->library->unapprovedMonthVouchers($office_id, $reporting_month,  'expense', 'bank', 0, $office_bank_id);
    $unapproved_petty_cash_deposit_vouchers = $this->library->unapprovedMonthVouchers($office_id, $reporting_month,  'bank_contra', 'bank', 0, $office_bank_id);
    $total_bank_expenses = $unapproved_bank_expense_vouchers + $unapproved_petty_cash_deposit_vouchers;
    $total_bank_balance = ($unapproved_cash_recieved_to_bank_vouchers + $unapproved_petty_cash_rebank_vouchers + $fully_approved_vouchers_bank_balance) - $total_bank_expenses;

    //Code when Editing a voucher to correct bug found from SN ticket
    if ($edit_voucher == 1) {
      $voucher_being_edited_id = $post['voucher_being_edited_id'];
      $voucher_id = hash_id($voucher_being_edited_id, 'decode');

      //Voucher total cost
      $total_cost_on_voucher_being_edited = $this->library->totalCostForVoucherToEdit($voucher_id);
      $total_bank_balance = $total_bank_balance + $total_cost_on_voucher_being_edited;
    }

     return $this->response->setJSON(['approved_and_unapproved_vouchers_bank_bal' => round($total_bank_balance, 2)]);
  }

  function checkActiveChequeBookForOfficeBankExist($office_id, $office_bank_id, $transaction_date)
  {
    $check_exists = false;
    $statusLibrary = new Core\StatusLibrary();
    $chequeBookLibrary = new Grants\ChequeBookLibrary();
    $officeBankLibrary = new Grants\OfficeBankLibrary();
    
    $max_cheque_book_status_id = $statusLibrary->getMaxApprovalStatusId('cheque_book');

    $builder = $this->read_db->table("cheque_book");
    $builder->where(array('fk_status_id <> ' =>  $max_cheque_book_status_id[0], 'fk_office_bank_id' => $office_bank_id));
    $count_of_unapproved_cheque_book = $builder->get()->getNumRows(); 

    $builder = $this->read_db->table("cheque_book");
    $builder->selectMax('cheque_book_id');
    $builder->where(array('fk_office_bank_id' => $office_bank_id));
    $max_cheque_book_id = $builder->get()->getRow()->cheque_book_id;
    
    // Check if the cheque book is completed, yes turn it inactive
    $remaining_cheque_leaves = $chequeBookLibrary->getRemainingUnusedChequeLeaves($office_bank_id);
    // log_message('error', json_encode($remaining_cheque_leaves));
    if (count($remaining_cheque_leaves) == 0) {
      // Turn the cheque book to inactive
      $chequeBookLibrary->deactivateChequeBook($office_bank_id);
    }

    // Unused reused cheque leaves
    $reused_cheques = $chequeBookLibrary->getUnusedReusedCheques($office_bank_id);

    // $is_max_cheque_book_fully_approved = false;
    $current_cheque_book_id = 0;

    $are_all_cheque_books_fully_approved = true;

    // The count($reused_cheques) is to ensure that no new cheque is created if there are still unused reused cheque leaves in place even if all cheque books are not active
    if ($count_of_unapproved_cheque_book > 0) {
      $are_all_cheque_books_fully_approved =  $count_of_unapproved_cheque_book > 0 ? false : true; // $count_of_unapproved_cheque_book > 0 ? (in_array($max_cheque_book_obj->row()->fk_status_id, $max_cheque_book_status_id) ? true : false) : false; 
    }

    $active_cheque_book_obj = $chequeBookLibrary->checkActiveChequeBookForOfficeBankExist($office_bank_id);

    if ($active_cheque_book_obj->getNumRows() > 0 || count($reused_cheques) > 0) {
      $check_exists = true;
      $current_cheque_book_id = hash_id($max_cheque_book_id, 'encode');
    }

    $disable_controls = false;

    $office_banks_for_office = $officeBankLibrary->getOfficeBanksForOffice($office_id);

    if (!empty($office_banks_for_office['chequebook_exemption_expiry_date'])) {
      foreach ($office_banks_for_office['chequebook_exemption_expiry_date'] as $chequebook_exemption_expiry_date) {
        // This seems but a code repeat but was neccessary to prevent the method get_remaining_unused_cheque_leaves being called in a loop continously
        if ($chequebook_exemption_expiry_date > $transaction_date) {
          $disable_controls = true;
          break;
        } else {
          $leaves = $chequeBookLibrary->getRemainingUnusedChequeLeaves($office_bank_id);
          if (!empty($leaves)) {
            $disable_controls = true;
            break;
          }
        }
      }
    }

    if ($disable_controls) {
      $check_exists = true;
      $are_all_cheque_books_fully_approved = true;
      $current_cheque_book_id = 0;
    }

    $response =  [
      'is_active_cheque_book_existing' => $check_exists,
      'are_all_cheque_books_fully_approved' => $are_all_cheque_books_fully_approved, ///'is_active_cheque_book_fully_approved' => $is_max_cheque_book_fully_approved,
      'current_cheque_book_id' => $current_cheque_book_id
    ];

    return $this->response->setJSON($response);
  }

  /**
   * check_cheque_validity(): gets a json string with chq numbers
   * @author Karisa & Onduso 
   * @access public
   * @return void
   */
  public function checkChequeValidity(): ResponseInterface
  {

    $cancelChequeLibrary = new Grants\CancelChequeLibrary();

    $post = $this->request->getPost();
    $office_bank_id = $post['bank_id'];
    $edit_chq_number = $post['cheque_number'];
    $leaves = $cancelChequeLibrary->getValidCheques($office_bank_id);
    $chq_to_edit_arr = [];

    if ($edit_chq_number > 0 && $edit_chq_number != '') {
      $chq_to_edit_arr['cheque_id'] = (int)$edit_chq_number;
      $chq_to_edit_arr['cheque_number'] = (int)$edit_chq_number;
      array_unshift($leaves, $chq_to_edit_arr);
    }

    return $this->response->setJSON(compact('leaves'));
  }

   /**
   *compute_cash_balance(): Returns json string of unapproaved float cash amount to used in a ajax call
   * @author Livingstone Onduso: Dated 08-04-2023
   * @access public
   * @return void - json string
   */

   public function computeCashBalance($edit_voucher = 0): ResponseInterface
   {
      $financialReportLibrary = new Grants\FinancialReportLibrary();

     $post = $this->request->getPost();
     $office_id = $post['office_id'];
     $office_cash_id = $post['office_cash_id'];
     $reporting_month = date('Y-m-01', strtotime($post['transaction_date']));

     //Get unapproved and approved vourchers
     $fully_approved_vouchers_cash_balance = $financialReportLibrary->computeCashAtHand([$office_id], $reporting_month, [], [], $office_cash_id, true);
     $unsubmitted_and_submitted_vouchers_cash_income = $this->library->unapprovedMonthVouchers($office_id, $reporting_month,  'bank_contra', 'bank', $office_cash_id);
 
     //Total Income
     $total_cash_income = $unsubmitted_and_submitted_vouchers_cash_income + $fully_approved_vouchers_cash_balance;
 
     //Total Expense
     $total_cash_expense = $this->library->unapprovedMonthVouchers($office_id, $reporting_month,  'expense', 'cash', $office_cash_id);
 
     $unsubmitted_vouchers_cash_rebank_voucher = $this->library->unapprovedMonthVouchers($office_id, $reporting_month,  'cash_contra', 'cash', $office_cash_id);
 
     //Total Cash balance
     $total_cash_balance = $total_cash_income - ($total_cash_expense + $unsubmitted_vouchers_cash_rebank_voucher);
 
     //Code when Editing a voucher to correct bug found from SN ticket
     if ($edit_voucher == 1) {
       $voucher_being_edited_id = $post['voucher_being_edited_id'];
       $voucher_id = hash_id($voucher_being_edited_id, 'decode');
 
       //Voucher total cost
       $total_cost_on_voucher_being_edited = $this->library->totalCostForVoucherToEdit($voucher_id);
 
       $total_cash_balance = $total_cash_balance + $total_cost_on_voucher_being_edited;
     }
 
     return $this->response->setJSON(['approved_and_unapproved_vouchers_cash_bal' => round($total_cash_balance, 2)]);
   }

   function getVoucherAccountsAndAllocation($office_id, $voucher_type_id, $transaction_date, $office_bank_id = 0): ResponseInterface
  {
    $response = [];
    $response['approved_requests'] = 0;
    $response['project_allocation'] = [];
    $response['is_contra'] = false;
    $response['project_allocation'] = [];
    $post = $this->request->getPost();
    $bank_refund_from = $post['bank_refund_from'];
    // $voucherLibrary = new \App\Libraries\Grants\VoucherLibrary();

    if (!validate_date($transaction_date)) {
      $transaction_date = date('Y-m-d');
    }

    $office_accounting_system = $this->library->officeAccountSystem($office_id);

    $project_allocation = [];
    $voucher_type_effect_and_code = $this->library->voucherTypeEffectAndCode($voucher_type_id);


    if (
      !$office_accounting_system->account_system_is_allocation_linked_to_account ||
      service("settings")->get("GrantsConfig.toggle_accounts_by_allocation")
    ) {

      if($voucher_type_effect_and_code->voucher_type_effect_code == 'bank_refund'){
        $voucherDetailReadBuilder = $this->read_db->table('voucher_detail');
        $voucherDetailReadBuilder->select(['DISTINCT(project_allocation_id) as project_allocation_id','project_name as project_allocation_name']);
        $voucherDetailReadBuilder->where(['voucher_number' => $bank_refund_from, 'voucher.fk_office_id' => $office_id]);
        $voucherDetailReadBuilder->join('voucher','voucher.voucher_id=voucher_detail.fk_voucher_id');
        $voucherDetailReadBuilder->join('project_allocation','project_allocation.project_allocation_id=voucher_detail.fk_project_allocation_id'); 
        $voucherDetailReadBuilder->join('project','project.project_id=project_allocation.fk_project_id');
        $project_allocation_obj = $voucherDetailReadBuilder->get();

        if ($project_allocation_obj->getNumRows() > 0) {
          $project_allocation = $project_allocation_obj->getResultObject();
        }
       
      }else{
      //Working as expected
      $query_condition = "fk_office_id = " . $office_id . " AND (project_end_date >= '" . $transaction_date . "' OR  project_allocation_extended_end_date >= '" . $transaction_date . "' OR project_end_date LIKE '0000-00-00' || project_end_date IS NULL) AND project_start_date <= '" . $transaction_date . "'";
      
      $builder = $this->read_db->table("project_allocation");
      $builder->select(array('project_allocation_id', 'project_name as project_allocation_name'));
      $builder->join('project', 'project.project_id=project_allocation.fk_project_id');

      if ($this->request->getPost('office_bank_id')) {
        $builder->where(array('fk_office_bank_id' => $this->request->getPost('office_bank_id')));
        $builder->join('office_bank_project_allocation', 'office_bank_project_allocation.fk_project_allocation_id=project_allocation.project_allocation_id');
      }

      $builder->where(array('project_allocation_is_active' => 1));
      $builder->where($query_condition);
      $project_allocation_obj = $builder->get();

      if ($project_allocation_obj->getNumRows() > 0) {
        $project_allocation = $project_allocation_obj->getResultObject();
      }
      }
    }

    $voucher_type_effect_and_code = $this->library->voucherTypeEffectAndCode($voucher_type_id);
    $voucher_type_effect = $voucher_type_effect_and_code->voucher_type_effect_code;

    $response['project_allocation'] = $project_allocation;

    if ($voucher_type_effect == 'bank_contra' || $voucher_type_effect == 'cash_contra') {
      $response['is_contra'] = true;
    }

    if ($voucher_type_effect == 'expense') {
      $response['approved_requests'] = count($this->library->getApprovedUnvouchedRequestDetails($office_id));
    }

    return $this->response->setJSON($response);
  }

  function getAccountsForProjectAllocation()
  {
    $officeGroupLibrary = new Core\OfficeGroupLibrary();
    $contraAccountLibrary = new Grants\ContraAccountLibrary();
    $officeBankLibrary = new Grants\OfficeBankLibrary();

    $officeBankReadBuilder = $this->read_db->table('office_bank');
    $contraAccountReadBuilder = $this->read_db->table('contra_account');
    $expenseAccountReadBuilder = $this->read_db->table("expense_account");
    $incomeAccountReadBuilder = $this->read_db->table("income_account");

    $post = $this->request->getPost();
    $voucher_type_effect_and_code = $this->library->voucherTypeEffectAndCode($post['voucher_type_id']);
    $bank_refund_from = $post['bank_refund_from'];
    $office_id = $post['office_id'];

    $voucher_type_effect = $voucher_type_effect_and_code->voucher_type_effect_code;

    $accounts_obj = null;

    $project_allocation_id = $post['allocation_id'];
    $office_bank_id = $post['office_bank_id'];

    if(!$office_bank_id){
      $office_bank_id = $officeBankLibrary->getDefaultOfficeBank($office_id)['office_bank_id'];
    }
    
    $office_accounting_system = $this->library->officeAccountSystem($this->request->getPost('office_id'));

    if (
        $voucher_type_effect == 'expense' || 
        $voucher_type_effect == 'bank_refund' || 
        $voucher_type_effect ==  VoucherTypeEffectEnum::PAYABLES->getCode() || 
        $voucher_type_effect == VoucherTypeEffectEnum::PREPAYMENT_SETTLEMENTS->getCode()
        ) {

      if($voucher_type_effect == 'payables'){
        $contraAccountLibrary->addContraAccount($office_bank_id);
      }

      // Check if the office is a lead in an office group
      $is_office_group_lead = $officeGroupLibrary->checkIfOfficeIsOfficeGroupLead($this->request->getPost('office_id'));

      if (!$is_office_group_lead) {
        $string_condition = 'AND expense_account_office_association_is_active = 1';
        $this->libs->notExistsSubQuery($expenseAccountReadBuilder, 'expense_account', 'expense_account_office_association', $string_condition);
      }

      if($voucher_type_effect == 'bank_refund'){
        $expenseAccountReadBuilder->join('voucher_detail','voucher_detail.fk_expense_account_id=expense_account.expense_account_id');
        $expenseAccountReadBuilder->join('project_allocation', 'project_allocation.project_allocation_id=voucher_detail.fk_project_allocation_id');
        $expenseAccountReadBuilder->join('voucher','voucher.voucher_id=voucher_detail.fk_voucher_id');
        $expenseAccountReadBuilder->where(['voucher_number' => $bank_refund_from, 'voucher.fk_office_id' => $office_id, 'fk_project_allocation_id' => $project_allocation_id]);
      }else{
        $expenseAccountReadBuilder->join('income_account', 'income_account.income_account_id=expense_account.fk_income_account_id');
        $expenseAccountReadBuilder->join('project_income_account', 'project_income_account.fk_income_account_id=income_account.income_account_id');
        $expenseAccountReadBuilder->join('project', 'project.project_id=project_income_account.fk_project_id');
        $expenseAccountReadBuilder->join('project_allocation', 'project_allocation.fk_project_id=project.project_id');
        $expenseAccountReadBuilder->where(array('project_allocation_id' => $project_allocation_id, 'expense_account_is_active' => 1));
        $expenseAccountReadBuilder->where(array('fk_account_system_id' => $office_accounting_system->account_system_id));
      }
      $expenseAccountReadBuilder->select(array('expense_account_id as account_id', 'expense_account_name as account_name'));
      $accounts_obj = $expenseAccountReadBuilder->get();
    } elseif (
      $voucher_type_effect == 'income' || 
      $voucher_type_effect == 'bank_to_bank_contra' || 
      $voucher_type_effect == VoucherTypeEffectEnum::RECEIVABLES->getCode()
      ) {

      if($voucher_type_effect == VoucherTypeEffectEnum::RECEIVABLES->getCode()){
        $contraAccountLibrary->addContraAccount($office_bank_id);
      }

      $incomeAccountReadBuilder->where(array('project_allocation_id' => $project_allocation_id, 'income_account_is_active' => 1));
      $incomeAccountReadBuilder->where(array('fk_account_system_id' => $office_accounting_system->account_system_id));
      $incomeAccountReadBuilder->join('project_income_account', 'project_income_account.fk_income_account_id=income_account.income_account_id');
      $incomeAccountReadBuilder->join('project', 'project.project_id=project_income_account.fk_project_id');
      $incomeAccountReadBuilder->join('project_allocation', 'project_allocation.fk_project_id=project.project_id');
      $incomeAccountReadBuilder->select(array('income_account_id as account_id', 'income_account_name as account_name'));
      $accounts_obj = $incomeAccountReadBuilder->get();
    } elseif ($voucher_type_effect == 'cash_contra') {
      $accounts = $contraAccountLibrary->addContraAccount($office_bank_id);

      $contraAccountReadBuilder->select(array('contra_account_id as account_id', 'contra_account_name as account_name', 'contra_account_code as account_code'));
      $contraAccountReadBuilder->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=contra_account.fk_voucher_type_effect_id');
      $contraAccountReadBuilder->join('office_bank', 'office_bank.office_bank_id=contra_account.fk_office_bank_id');
      $contraAccountReadBuilder->where(array('fk_account_system_id' => $office_accounting_system->account_system_id));
      $accounts_obj = $contraAccountReadBuilder->getWhere(
        array(
          'voucher_type_effect_code' => 'cash_contra',
          'office_bank_is_active' => 1,
          'office_bank_id' => $office_bank_id
        )
      );
    } elseif ($voucher_type_effect == 'bank_contra') {
      $contraAccountLibrary->addContraAccount($office_bank_id);
      $contraAccountReadBuilder->select(array('contra_account_id as account_id', 'contra_account_name as account_name', 'contra_account_code as account_code'));
      $contraAccountReadBuilder->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=contra_account.fk_voucher_type_effect_id');
      $contraAccountReadBuilder->join('office_bank', 'office_bank.office_bank_id=contra_account.fk_office_bank_id');
      $contraAccountReadBuilder->where(array('fk_account_system_id' => $office_accounting_system->account_system_id));
      $contraAccountReadBuilder->where(array(
        'voucher_type_effect_code' => 'bank_contra',
        'office_bank_is_active' => 1,
        'office_bank_id' => $office_bank_id
      ));
      $accounts_obj = $contraAccountReadBuilder->get();
    } elseif ($voucher_type_effect == 'cash_to_cash_contra') {

      $this->makeAtleastOneOfficeBankDefaultIfOneMissing($this->request->getPost('office_id'));

      $contraAccountLibrary->addContraAccount($office_bank_id);

      $officeBankReadBuilder->where(array('fk_office_id' => $this->request->getPost('office_id'), 'office_bank_is_active' => 1, 'office_bank_is_default' => 1));
      $office_bank_id = $officeBankReadBuilder->get()->getRow()->office_bank_id;
      
      $contraAccountReadBuilder->select(array('contra_account_id as account_id', 'contra_account_name as account_name', 'contra_account_code as account_code'));
      $contraAccountReadBuilder->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=contra_account.fk_voucher_type_effect_id');
      $contraAccountReadBuilder->join('office_bank', 'office_bank.office_bank_id=contra_account.fk_office_bank_id');
      $contraAccountReadBuilder->where(array('fk_account_system_id' => $office_accounting_system->account_system_id));
      $accounts_obj = $contraAccountReadBuilder->where(
        array(
          'voucher_type_effect_code' => 'cash_to_cash_contra',
          'fk_account_system_id' => $office_accounting_system->account_system_id,
          'office_bank_is_active' => 1,
          'office_bank_id' => $office_bank_id
        )
      )->get();
    }elseif ($voucher_type_effect == VoucherTypeEffectEnum::PREPAYMENTS->getCode()){
      $contraAccountLibrary->addContraAccount($office_bank_id);

      $contraAccountReadBuilder->select(array('contra_account_id as account_id', 'contra_account_name as account_name', 'contra_account_code as account_code'));
      $contraAccountReadBuilder->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=contra_account.fk_voucher_type_effect_id');
      $contraAccountReadBuilder->join('office_bank', 'office_bank.office_bank_id=contra_account.fk_office_bank_id');
      $contraAccountReadBuilder->where(array('fk_account_system_id' => $office_accounting_system->account_system_id));
      $contraAccountReadBuilder->where(array(
        'voucher_type_effect_code' => VoucherTypeEffectEnum::PREPAYMENTS->getCode(),
        'office_bank_is_active' => 1,
        'office_bank_id' => $office_bank_id
      ));
      $accounts_obj = $contraAccountReadBuilder->get();
    }elseif ($voucher_type_effect == VoucherTypeEffectEnum::RECEIVABLES_PAYMENTS->getCode()){
      $contraAccountLibrary->addContraAccount($office_bank_id);

      $contraAccountReadBuilder->select(array('contra_account_id as account_id', 'contra_account_name as account_name', 'contra_account_code as account_code'));
      $contraAccountReadBuilder->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=contra_account.fk_voucher_type_effect_id');
      $contraAccountReadBuilder->join('office_bank', 'office_bank.office_bank_id=contra_account.fk_office_bank_id');
      $contraAccountReadBuilder->where(array('fk_account_system_id' => $office_accounting_system->account_system_id));
      $contraAccountReadBuilder->where(array(
        'voucher_type_effect_code' => VoucherTypeEffectEnum::RECEIVABLES->getCode(),
        'office_bank_is_active' => 1,
        'office_bank_id' => $office_bank_id
      ));
      $accounts_obj = $contraAccountReadBuilder->get();
    }elseif ($voucher_type_effect == VoucherTypeEffectEnum::PAYABLE_DISBURSEMENTS->getCode()){
      $contraAccountLibrary->addContraAccount($office_bank_id);

      $contraAccountReadBuilder->select(array('contra_account_id as account_id', 'contra_account_name as account_name', 'contra_account_code as account_code'));
      $contraAccountReadBuilder->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=contra_account.fk_voucher_type_effect_id');
      $contraAccountReadBuilder->join('office_bank', 'office_bank.office_bank_id=contra_account.fk_office_bank_id');
      $contraAccountReadBuilder->where(array('fk_account_system_id' => $office_accounting_system->account_system_id));
      $contraAccountReadBuilder->where(array(
        'voucher_type_effect_code' => VoucherTypeEffectEnum::PAYABLES->getCode(),
        'office_bank_is_active' => 1,
        'office_bank_id' => $office_bank_id
      ));
      $accounts_obj = $contraAccountReadBuilder->get();
    }

    $expense_or_income_accounts_array = [];

    if ($accounts_obj->getNumRows() > 0) {
      $accounts = $accounts_obj->getResultObject();
      //Remove duplicates account_ids
      $account_ids = array_column($accounts, 'account_id');
      $unique_account_ids = array_unique($account_ids);

      //Remove duplicate account names and combine the arrays to form one
      $account_names = array_column($accounts, 'account_name');
      $unique_names = array_unique($account_names);

      if (sizeof($unique_account_ids) == sizeof($unique_names)) {
        $expense_or_income_accounts_array = array_combine($unique_account_ids, $unique_names);
      }
    }

    return $this->response->setJSON($expense_or_income_accounts_array);
  }

  function makeAtleastOneOfficeBankDefaultIfOneMissing($office_id)
  {
    // Every query must be done on the write_db node
    $builder = $this->write_db->table("office_bank");
    $builder->where(array('fk_office_id' => $office_id, 'office_bank_is_active' => 1, 'office_bank_is_default' => 1));
    $office_bank_id_obj = $builder->get();

    if ($office_bank_id_obj->getNumRows() == 0) {
      $$builder->where(array('office_bank_is_active' => 1, 'fk_office_id' => $office_id));
      $active_office_bank_count_obj = $$builder->get();

      if ($active_office_bank_count_obj->getNumRows() > 0) {
        // Make one active office bank default
        $first_active_office_bank_id = $active_office_bank_count_obj->row()->office_bank_id;
        $builder->where(array('office_bank_id' => $first_active_office_bank_id, 'office_bank_is_active' => 1, 'fk_office_id' => $office_id));
        $builder->update( ['office_bank_is_default' => 1]);

        // Make inactive office banks not default
        $builder->where(array('office_bank_is_active' => 0, 'fk_office_id' => $office_id));
        $builder->update(['office_bank_is_default' => 0]);
      }
    }
  }

  function getDuplicateChequesForAnOffice($office_id = 0, $cheque_number = '', $office_bank_id = 0, $hold_cheque_number_for_edit = '', $has_eft = '')
  {
    $check = $this->library->getDuplicateChequesForAnOffice($office_id, $cheque_number, $office_bank_id, $hold_cheque_number_for_edit, $has_eft);
 
    return $this->response->setJSON(compact('check'));
  }

  /**
   *cash_limit_exceed_check(): Returns 1 for true and 0 for false
   * @author Livingstone Onduso: Dated 08-04-2023
   * @access public
   * @return void - echo 1 0r 0 to be used with ajax
   */

   public function cashLimitExceedCheck(): ResponseInterface
   {
  
     $post = $this->request->getPost();
     $voucher_type_id = $post['voucher_type_id'];
     $amount = floatval(preg_replace('/[^\d.]/', '', $post['amount']));
     $office_cash_id = $post['office_cash_id'];
     $voucher_cheque_number = $post['cheque_number'];
     $cash_limit_exceeded = 0;
 
     if ($office_cash_id != "" && $voucher_cheque_number == "") {
       $unapproved_and_approved_cash_vouchers = $post['unapproved_and_approved_vouchers'];
     } else if ($voucher_cheque_number != "" && $office_cash_id != "") {
       $unapproved_and_approved_cash_vouchers = $post['bank_balance'];
     } else {
       $unapproved_and_approved_cash_vouchers = $post['bank_balance'];
     }
 
     //Get the account and effect codes
     $voucher_type_obj = $this->library->getAccountAndEffectCodes($voucher_type_id);
 
     //Check if the transaction an expense or cash/bank contra and the balance is negative
     if ((strpos($unapproved_and_approved_cash_vouchers, "-") === 0) && ($voucher_type_obj->voucher_type_effect_code == "cash_contra" || $voucher_type_obj->voucher_type_effect_code == "expense" || $voucher_type_obj->voucher_type_effect_code == "bank_contra")) {
       $cash_limit_exceeded = 1;
     } else {
       //Compute the expense and cash balance balance
       if ((strpos($unapproved_and_approved_cash_vouchers, "-") === 0)) {
         $unapproved_and_approved_cash_vouchers = -round(floatval(preg_replace('/[^\d.]/', '', $unapproved_and_approved_cash_vouchers)), 2);
         $unapproved_and_approved_cash_vouchers += $amount;
       } else {
         $unapproved_and_approved_cash_vouchers = round(floatval(preg_replace('/[^\d.]/', '', $unapproved_and_approved_cash_vouchers)), 2);
         $unapproved_and_approved_cash_vouchers -= $amount;
       }
       //Check if amount is > than Balance
       if (round($unapproved_and_approved_cash_vouchers, 2) < 0 && ($voucher_type_obj->voucher_type_effect_code == 'expense' || $voucher_type_obj->voucher_type_effect_code == 'bank_contra' || $voucher_type_obj->voucher_type_effect_code == 'cash_contra')) {
         $cash_limit_exceeded = 1;
       }
     }
 
     return $this->response->setJSON(compact('cash_limit_exceeded'));
   }

   function deleteDuplicateMfr($report_date, $office)
  {

    $report_month = date('Y-m-1', strtotime($report_date));

    // Check if a journal for the same month and FCP exists
    $builder = $this->write_db->table("financial_report");
    $builder->where(array('fk_office_id' => $office, 'financial_report_month' => $report_month));
    $count_financial_report = $builder->get()->getResultArray();

    if (!empty($count_financial_report)) {
      if (sizeof($count_financial_report) > 1) {
        $financial_report_id = '';
        foreach ($count_financial_report as $key => $record) {
          if ($record['financial_report_is_submitted'] == 0) {
            $financial_report_id = $count_financial_report[$key]['financial_report_id'];
            break;
          }
        }
        //Delete duplicate mfr
        $builder = $this->write_db->table("financial_report");
        $builder->where(['financial_report_id' => $financial_report_id, 'fk_office_id' => $office]);
        $builder->delete();

        echo 'Duplicate MFR deleted';
      } else {
        echo "No duplicate MFRs exists";
      }
    }
  }

  function deleteDuplicateCj($journal_date, $office_id)
  {
    $journal_month = date('Y-m-1', strtotime($journal_date));
    // Check if a journal for the same month and FCP exists
    $builder = $this->write_db->table('journal');
    $builder->where(array('fk_office_id' => $office_id, 'journal_month' => $journal_month));
    $count_journals = $builder->get()->getResultArray();

    if (!empty($count_journals)) {
      if (sizeof($count_journals) > 1) {
        $duplicate_cash_journal_id = $count_journals[1]['journal_id'];

        $builder = $this->write_db->table("journal");
        $builder->where(['journal_id' => $duplicate_cash_journal_id, 'fk_office_id' => $office_id]);
        $builder->delete();
        echo 'Duplicate CJ deleted';
      } else {
        echo "No Duplicate CJ exists";
      }
    }
  }

  /**
   *voucher_header_records(): Returns a rows of voucher details information from voucher_detail table
   * @author Livingstone Onduso: Dated 08-05-2023
   * @access public
   * @param int $voucher_id - voucher id
   * @return void 
   */
  function voucherHeaderRecords(int $voucher_id): ResponseInterface
  {
    $voucherLibrary = new \App\Libraries\Grants\VoucherLibrary();
    $voucher_records_to_edit = $voucherLibrary->getVoucherHeaderToEdit($voucher_id);

    return $this->response->setJSON($voucher_records_to_edit);
  }

  /**
   * get_active_office_bank(): get json string of voucher types
   * @author  Livingstone Onduso
   * @dated: 5/06/2023
   * @param int $office_id
   * @access public
   * @return void
   */
  function getActiveOfficeBank(int $office_id): ResponseInterface
  {
    $officeBankLibrary = new \App\Libraries\Grants\OfficeBankLibrary();
    $office_bank = $officeBankLibrary->getActiveOfficeBank($office_id);

    return $this->response->setJSON($office_bank);
  }

  /**
   *get_voucher_detail_to_edit(): Returns a rows of voucher details information from voucher_detail table
   * @author Livingstone Onduso: Dated 08-05-2023
   * @access public
   * @param Int $voucher_id - voucher id
   * @return void
   */
  function getVoucherDetailToEdit(int $voucher_id, string $voucher_type_effect_name): ResponseInterface
  {
    $voucherLibrary = new \App\Libraries\Grants\VoucherLibrary();
    $voucher_detail_records_to_edit = $voucherLibrary->getVoucherDetailToEdit($voucher_id, $voucher_type_effect_name);

    return $this->response->setJSON($voucher_detail_records_to_edit);
  }

  function checkEftValidity()
  {
    $post = $this->request->getPost();
    $is_valid = true;

    $cheque_number = $post['cheque_number'];
    $office_bank_id = $post['bank_id'];

    $voucherReadBuilder = $this->read_db->table('voucher');
    $voucherReadBuilder->select(array('voucher_cheque_number'));
    $voucherReadBuilder->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
    $voucherReadBuilder->where(array('fk_office_bank_id' => $office_bank_id, 'voucher_cheque_number' => $cheque_number, 'voucher_type_is_cheque_referenced' => 0));
    $used_eft_ref = $voucherReadBuilder->get();

    if ($used_eft_ref->getNumRows() > 0) {
      $is_valid = false;
    }

    echo $is_valid;
  }

  /**
   * get_active_project_expenses_accounts
   * @date: 13 Nov 2023
   * 
   * @return void
   * @author Onduso
   */
  function get_active_project_expenses_accounts(int $project_id, int $voucher_type_id): ResponseInterface
  {
    $voucherLibrary = new \App\Libraries\Grants\VoucherLibrary();
    $account_ids_and_names = $voucherLibrary->getActiveProjectExpensesAccounts($project_id, $voucher_type_id);
    return $this->response->setJSON($account_ids_and_names);
  }

  function getApproveRequestDetails($office_id)
  {
    //echo "Approved request details";
    $voucherLibrary = new \App\Libraries\Grants\VoucherLibrary();
    $result = $this->approvedUnvouchedRequestDetails($office_id);
    return $this->response->setJSON(compact('result'));
  }

  function approvedUnvouchedRequestDetails($office_id){
    $voucherLibrary = new \App\Libraries\Grants\VoucherLibrary();
    $data['result'] = $voucherLibrary->getApprovedUnvouchedRequestDetails($office_id);
    return view('voucher/unvouched_request_details',$data);
  }

    /**
   * get_voucher_type_effect
   * 
   * The method gives the voucher type effsct code for a give voucher type.
   * Each voucher type has an associated effect and an account. 
   * 
   * There are 4 voucher type effects with codes income, expense, bank_contra 
   * [bank_contra - is for monies taken from bank to petty cash box] and 
   * cash_contra [is for monies rebanked from petty cash box to bank]
   * 
   * There are 2 voucher type accounts with codes names bank [holds bank transactions] and cash [petty cash transactions]
   * 
   * A valid combination for a voucher type can therefore be Bank Account with Effect of Expense
   * 
   * @param int $voucher_type_id - Is an primary key of a certain voucher type
   * 
   * @return string - Voucher Type Effect of a given voucher type id
   * 
   * @author Nicodemus Karisa Mwambire
   * 
   */
  function getVoucherTypeEffect(int $voucher_type_id): ResponseInterface
  {
    $voucherLibrary = new \App\Libraries\Grants\VoucherLibrary();
    $result = $voucherLibrary->getVoucherTypeEffect($voucher_type_id)['voucher_type_effect_code'];
    return $this->response->setJSON(compact('result'));
  }

   /**
   * get_active_office_cash(): get json string of voucher types
   * @author  Livingstone Onduso
   * @dated: 4/06/2023
   * @access public
   * @return void
   */
  function getActiveOfficeCash(): ResponseInterface
  {

    $account_system_id = $this->session->user_account_system_id;

    $officeCashLibrary = new \App\Libraries\Grants\OfficeCashLibrary();
    $office_cash_accounts = $officeCashLibrary->getActiveOfficeCash($account_system_id);

    return $this->response->setJSON($office_cash_accounts);
  }

    /**
   * get_active_recipient_bank(): get json string of voucher types
   * @author  Livingstone Onduso
   * @dated: 5/03/2024
   * @param int $voucher_id
   * @access public
   * @return void
   */
  function getActiveRecipientBank(int $voucher_id): ResponseInterface
  {

    $officeBankLibrary = new \App\Libraries\Grants\OfficeBankLibrary();
    $recipient_office_bank = $officeBankLibrary->getActiveRecipientBank($voucher_id);

    return $this->response->setJSON($recipient_office_bank);
  }

    /**
   *unapproved_month_vouchers(): Returns the total of unapproved vouchers for current month for an office
   *
   * @author Livingstone Onduso: Dated 08-04-2023
   * @access public
   * @param int $office_id - Office primary key
   * @param string $reporting_month - Date of the month
   * @param string $effect_code - Effect code e.g. income or expense
   * @param string $account_code - Account code e.g cash or bank
   * @param int $cash_type_id - Cash type e.g. petty cash
   * @param int $office_bank_id - Cash type e.g. bank 1 
   * @return float - True if reconciliation has been created else false
   */

   public function unapprovedMonthVouchers(int $office_id, string $reporting_month, string $effect_code, string $account_code, int $cash_type_id = 0, int $office_bank_id = 0): ResponseInterface
   {
    
    $voucherLibrary = new \App\Libraries\Grants\VoucherLibrary();
     $unproved_expense_vouchers = $voucherLibrary->unapprovedMonthVouchers($office_id, $reporting_month, $effect_code, $account_code, $cash_type_id, $office_bank_id);
 
     return $this->response->setJSON($unproved_expense_vouchers);
   }

     /**
   * get_cheques_for_office
   * 
   * This return list of cheques
   * 
   * @return array - Array
   * @author Onduso
   */
  function getChequesForOffice(Int $office, Int $bank_office_id, Int $cheque_number): ResponseInterface
  {

    $cheque_number_exists = false;
    $voucherLibrary = new \App\Libraries\Grants\VoucherLibrary();
    $cheque_numbers = $voucherLibrary->getChequesForOffice($office, $bank_office_id, $cheque_number);

    if ($cheque_numbers > 0) {
      $cheque_number_exists = true;
    }
    return $this->response->setJSON(['result' => $cheque_number_exists]);
  }

  function voucherTypeRequiresChequeReferencing($voucher_type_id)
  {
    $voucherLibrary = new \App\Libraries\Grants\VoucherLibrary();
    $result = $voucherLibrary->voucherTypeRequiresChequeReferencing($voucher_type_id);
    
    return $this->response->setJSON(compact('result'));
  }

  /**
   * get_active_project_expenses_accounts
   * @date: 13 Nov 2023
   * 
   * @return void
   * @author Onduso
   */
  function getActiveProjectExpensesAccounts(int $project_id, int $voucher_type_id): ResponseInterface
  {
    $voucherLibrary = new \App\Libraries\Grants\VoucherLibrary();
    $account_ids_and_names = $voucherLibrary->getActiveProjectExpensesAccounts($project_id, $voucher_type_id);
    return $this->response->setJSON($account_ids_and_names);
  }

    /**
   *edit_voucher(): It modifies a voucher and saves it
   *
   * @author Livingstone Onduso: Dated 08-04-2023
   * @access public
   * @param Int $voucher_id - Office primary key
   * @return void - True if reconciliation has been created else false
   */

   public function editVoucher(int $voucher_id): void
   {
 
     $voucherLibrary = new \App\Libraries\Grants\VoucherLibrary();
     $voucherTypeLibrary = new \App\Libraries\Grants\VoucherTypeLibrary();
     $voucherWriteBuilder = $this->write_db->table('voucher');
     $voucherDetailWriteBuilder = $this->write_db->table('voucher_detail');
     $statusLibrary = new \App\Libraries\Core\StatusLibrary();
     $post = $this->request->getPost();

    //  log_message('error', json_encode($post));
     
     $voucher_type_effect_code = $voucherLibrary->voucherTypeEffectAndCode($post['fk_voucher_type_id'])->voucher_type_effect_code;
     $office_cash_id = 0;
     $cheque_number = 0;
     $fk_voucher_type_id = $post['fk_voucher_type_id'];
     $original_voucher_type_effect_before_edit = $post['hold_voucher_type_effect_for_edit'];
     
     $this->write_db->transBegin();

     //Voucher header details
     if (isset($post['fk_office_cash_id'])) {
       $office_cash_id = $post['fk_office_cash_id'] == null ? 0 : $post['fk_office_cash_id'];
     }
     if (isset($post['voucher_cheque_number'])) {
       $cheque_number = $post['voucher_cheque_number'] == null ? 0 : $post['voucher_cheque_number'];
     }
     $voucher_header_data = [
       'fk_office_id' => $post['fk_office_id'],
       'voucher_date' => $post['voucher_date'],
       'fk_voucher_type_id' => $fk_voucher_type_id,
       'fk_office_bank_id' => $this->getOfficeBankIdToPost($post['fk_office_id']),
       'fk_office_cash_id' => $office_cash_id,
       'voucher_cheque_number' => $cheque_number,
       'voucher_vendor' => $post['voucher_vendor'],
       'voucher_vendor_address' => $post['voucher_vendor_address'],
       'voucher_description' => $post['voucher_description'],
       'voucher_last_modified_by' => $this->session->user_id
     ];
 
     $is_voucher_type_affects_bank = $voucherTypeLibrary->isVoucherTypeAffectsBank($fk_voucher_type_id);
 
     if ($is_voucher_type_affects_bank) { 
       $voucher_header_data['voucher_cleared'] = 0;
       $voucher_header_data['voucher_cleared_month'] = NULL;
     }

     $quantity = count($post['voucher_detail_quantity']);
 
     if ($quantity > 0) {
       //Update Voucher Table
       $voucherWriteBuilder->where(['voucher_id' => $voucher_id]);
       $voucherWriteBuilder->update($voucher_header_data);
 
       $detail = [];
       $detail_ids = [];
       //Loop to update Voucher Details
       for ($i = 0; $i <  $quantity; $i++) {
 
         $voucher_detail_quantity = str_replace(",", "", $post['voucher_detail_quantity'][$i]);
         $voucher_detail_unit_cost = str_replace(",", "", $post['voucher_detail_unit_cost'][$i]);
         $voucher_detail_total_cost = str_replace(",", "", $post['voucher_detail_total_cost'][$i]);
 
         $detail['voucher_detail_quantity'] = $voucher_detail_quantity;
         $detail['voucher_detail_description'] = $post['voucher_detail_description'][$i];
         $detail['voucher_detail_unit_cost'] = $voucher_detail_unit_cost; 
         $detail['voucher_detail_total_cost'] = $voucher_detail_total_cost;
 
         //if original_voucher_type_effect_before_edit is EMPTY it means voucher type effect didn't change since the voucher type dropdown was not toggled
         $voucher_detail_id = '';
         if ($original_voucher_type_effect_before_edit == $voucher_type_effect_code || $original_voucher_type_effect_before_edit == '') {
           $voucher_detail_id = $post['hold_voucher_detail_id'][$i];
           $detail_ids[$i] = $voucher_detail_id;
         }
  
         if ($voucher_type_effect_code == 'expense') {
           $detail['fk_expense_account_id'] = $post['voucher_detail_account'][$i];
           $detail['fk_contra_account_id'] = 0;
           $detail['fk_income_account_id'] = $post['store_income_account_id'][$i];
         } elseif ($voucher_type_effect_code == 'income' || $voucher_type_effect_code == 'bank_to_bank_contra') {
           $detail['fk_expense_account_id'] = 0;
           $detail['fk_contra_account_id'] = 0;
           $detail['fk_income_account_id'] = $post['store_income_account_id'][$i];
         } elseif ($voucher_type_effect_code == 'bank_contra' || $voucher_type_effect_code == 'cash_contra') {
           $detail['fk_expense_account_id'] = 0;
           $detail['fk_contra_account_id'] = $post['voucher_detail_account'][$i];
         }
         $detail['fk_project_allocation_id'] = isset($post['fk_project_allocation_id'][$i]) ? $post['fk_project_allocation_id'][$i] : 0;
         $detail['fk_request_detail_id'] =  isset($post['fk_request_detail_id'][$i]) ? $post['fk_request_detail_id'][$i] : 0;
 
         if ($voucher_detail_id != '') {
           //update the voucher_detail table
           $voucherDetailWriteBuilder->where(['voucher_detail_id' => $voucher_detail_id]);
           $voucherDetailWriteBuilder->update($detail);
         } else {
           //Insert newlt added detail row
           $itemTrackNumberAndName = $this->libs->generateItemTrackNumberAndName('voucher_detail');
           $detail['fk_voucher_id'] = $voucher_id;
           $detail['voucher_detail_track_number'] = $itemTrackNumberAndName['voucher_detail_track_number'];
           $detail['voucher_detail_name'] = $itemTrackNumberAndName['voucher_detail_name'];
           $detail['fk_approval_id'] = $this->libs->insertApprovalRecord('voucher_detail');
           $detail['fk_status_id'] = $statusLibrary->initialItemStatus('voucher_detail');
           $voucherDetailWriteBuilder->insert($detail);
           $detail_ids[$i] = $this->write_db->insertId();
         }
       }


       if(count($detail_ids) > 0){
          // Delete where not in $detail_ids
          $voucherDetailWriteBuilder->where('voucher_detail_id NOT IN ('. implode(',', $detail_ids). ')');
          $voucherDetailWriteBuilder->where(['fk_voucher_id' => $voucher_id]);  // delete the details that are not in the $detail_ids array
          $voucherDetailWriteBuilder->delete();  // delete the rest of the details that are not in the $detail_ids array
       }
      //  log_message('error', json_encode($detail_ids));
 
       // This is to be used in the future as a replacement for inserting in the details
       $voucher_posting_condition = $this->voucherPostingCondition($post);
 
       if ($this->write_db->transStatus() === FALSE || !$voucher_posting_condition) {
         $this->write_db->transRollback();
         echo 0; //"Voucher Update failed"
       } else {
         $this->write_db->transCommit();
         echo 1; //"Voucher Updated successfully";
       }
     }
   }

   private function voucherPostingCondition($post){
    $checkResult = true;
    $has_details = count($post['voucher_detail_quantity']) > 0 ? true : false;
    $all_details_have_accounts = $this->allDetailsHaveAccounts($post['voucher_detail_account']);

    if(!$has_details || !$all_details_have_accounts){
      $checkResult = false;
    }

    return $checkResult;
  }

  private function allDetailsHaveAccounts($detailsAccounts){
    $checkResult =  true;

    foreach($detailsAccounts as $detailsAccount){
      if(!$detailsAccount || $detailsAccount == 0){
        $checkResult = false;
        break;
      }
    }
    return $checkResult;
  }

   function getOfficeBankIdToPost($office_id)
   {

    $post = $this->request->getPost();

    //log_message('error', json_encode($post));
    $officeBankLibrary = new \App\Libraries\Grants\OfficeBankLibrary();
 
     $office_bank_id = !isset($post['fk_office_bank_id']) ? 0 : $post['fk_office_bank_id'];
 
     if ($office_bank_id == 0) {
       // Get id of active office bank
       $office_bank_id = $officeBankLibrary->getActiveOfficeBanks($office_id)[0]['office_bank_id'];
     }
 
     return $office_bank_id;
   }

     /**
   * Enhancement
   *get_project_allocation_income_account(): Returns  income account numeric value
   * @author Livingstone Onduso: Dated 29-06-2023
   * @access public
   * @param Int Int $project_allocation_id
   * @return int
   **/
  function getProjectAllocationIncomeAccount(int $project_allocation_id): void
  {
    $incomeAccountLibrary = new \App\Libraries\Grants\IncomeAccountLibrary();
    $income_account_id = $incomeAccountLibrary->getProjectAllocationIncomeAccount($project_allocation_id);

    echo $income_account_id;
  }

  private function officeAccountSystem($office_id)
  {
    $officeReadBuilder = $this->read_db->table('office');
    $officeReadBuilder->join('account_system', 'account_system.account_system_id=office.fk_account_system_id');
    $office_accounting_system = $officeReadBuilder->where(array('office_id' => $office_id))->get()->getRow();

    return $office_accounting_system;
  }

  public function getProjectDetailsAccount(): ResponseInterface
  {

    $post = $this->request->getPost();
    $voucherLibrary = new \App\Libraries\Grants\VoucherLibrary();

    $voucher_type_effect_and_code = $voucherLibrary->voucherTypeEffectAndCode($post['voucher_type_id']);

    $voucher_type_effect = $voucher_type_effect_and_code->voucher_type_effect_code;
    // $voucher_type_account = $voucher_type_effect_and_code->voucher_type_account_code;

    $project_allocation = [];

    $income_account_id = $post['account_id'];

    $office_accounting_system = $this->officeAccountSystem($post['office_id']);

    if ($voucher_type_effect == 'expense') {
      $expenseAccountReadBuilder = $this->read_db->table('expense_account');
      $expenseAccountReadBuilder->select('income_account_id');
      $expenseAccountReadBuilder->join('income_account', 'income_account.income_account_id=expense_account.fk_income_account_id');
      $income_account_id = $expenseAccountReadBuilder->where(
        array('expense_account_id' => $post['account_id'])
      )->get()->getRow()->income_account_id;
    }

    if ($voucher_type_effect == 'expense' || $voucher_type_effect == 'income') {
      $projectAllocationReadBuilder = $this->read_db->table('project_allocation');
      $query_condition = "fk_office_id = " . $post['office_id'] . " AND (project_end_date >= '" . $post['transaction_date'] . "' OR  project_allocation_extended_end_date >= '" . $post['transaction_date'] . "')";
      $projectAllocationReadBuilder->select(array('project_allocation_id', 'project_allocation_name'));
      $projectAllocationReadBuilder->join('project', 'project.project_id=project_allocation.fk_project_id');

      if ($post['office_bank_id']) {
        $projectAllocationReadBuilder->where(array('fk_office_bank_id' => $post['office_bank_id']));
        $projectAllocationReadBuilder->join('office_bank_project_allocation', 'office_bank_project_allocation.fk_project_allocation_id=project_allocation.project_allocation_id');
      }

      if ($office_accounting_system->account_system_is_allocation_linked_to_account) {
        $projectAllocationReadBuilder->where(array('fk_income_account_id' => $income_account_id));
      }

      $projectAllocationReadBuilder->where($query_condition);
      $project_allocation = $projectAllocationReadBuilder->get('project_allocation')->getResultObject();
    }


    return $this->response->setJSON($project_allocation);
  }

  public function validateRefundLimit(): ResponseInterface{
    $post = $this->request->getPost();

    $account_id = $post['account_id'];
    $bank_refund_from = $post['bank_refund_from'];
    $office_id = $post['office_id'];
    $refund_voucher_amount = $post['refund_voucher_amount'];
    $totalcost = $post['totalcost'];
    $is_refundable = 0;
    $detail_amount = 0;

    $voucherDetailReadBuilder = $this->read_db->table('voucher_detail');
    $voucherDetailReadBuilder->selectSum('voucher_detail_total_cost');
    $voucherDetailReadBuilder->where(['fk_expense_account_id' => $account_id, 'voucher.fk_office_id' => $office_id, 'voucher_number' => $bank_refund_from, 'voucher_type_is_hidden' => 0]);
    $voucherDetailReadBuilder->where(['voucher_cleared' => 1]);
    $voucherDetailReadBuilder->where(['voucher_reversal_from' => 0, 'voucher_reversal_to' => 0, 'voucher_type_account_code' => 'bank', 'voucher_type_effect_code' => 'expense']);
    $voucherDetailReadBuilder->join('voucher','voucher.voucher_id=voucher_detail.fk_voucher_id');
    $voucherDetailReadBuilder->join('voucher_type','voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
    $voucherDetailReadBuilder->join('voucher_type_effect','voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
    $voucherDetailReadBuilder->join('voucher_type_account','voucher_type_account.voucher_type_account_id=voucher_type.fk_voucher_type_account_id');
    $voucher_detail_total_cost_obj = $voucherDetailReadBuilder->get();

    if($voucher_detail_total_cost_obj->getNumRows() > 0){
      $result = $voucher_detail_total_cost_obj->getRowArray();
      if(isset($result['voucher_detail_total_cost'])){
        $detail_amount = $result['voucher_detail_total_cost'];
        if($detail_amount >= $totalcost && $refund_voucher_amount >= $totalcost){
          $is_refundable = 1;
        }
      }
    }

    $output = compact('is_refundable','detail_amount','post');
    
    return $this->response->setJSON($output);
  }

  function validateRefundFromVoucher(): ResponseInterface{

    $message = 'failed';
    $voucherLibrary = new \App\Libraries\Grants\VoucherLibrary();
    $post = $this->request->getPost();
    $voucher_number = $post['bank_refund_voucher'];
    $office_id = $post['office_id'];
    $next_vouching_date = $voucherLibrary->getVoucherDate($office_id);
    $max_date_of_reversable_voucher = date('Y-m-01', strtotime('-6 months', strtotime($next_vouching_date)));

    $invalid_reasons = "a) A bank refund should only be for bank expenses\n
    b) Expense MUST be have been done within 6 months\n
    c) A voucher can only be refunded once\n
    d) A voucher MUST be cleared in the bank reconciliation";

    $message = get_phrase('invalid_refund_voucher','Voucher number {{voucher_number}} is invalid for the following reasons {{invalid_reasons}}', ['voucher_number' => $voucher_number, 'invalid_reasons' => $invalid_reasons]);

    $voucherReadBuilder = $this->read_db->table('voucher');
    $voucherReadBuilder->selectSum('voucher_detail_total_cost');
    $voucherReadBuilder->where(['fk_office_id' => $office_id, 'voucher_date <= ' => $next_vouching_date, 'voucher_date >=' => $max_date_of_reversable_voucher]);
    $voucherReadBuilder->where(['voucher_cleared' => 1]);
    $voucherReadBuilder->where(['voucher_reversal_from' => 0, 'voucher_reversal_to' => 0, 'voucher_number' => $voucher_number, 'voucher_type_is_hidden' => 0]);
    $voucherReadBuilder->where(['voucher_type_account_code' => 'bank', 'voucher_type_effect_code' => 'expense']);
    $voucherReadBuilder->join('voucher_detail','voucher_detail.fk_voucher_id=voucher.voucher_id');
    $voucherReadBuilder->join('voucher_type','voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
    $voucherReadBuilder->join('voucher_type_effect','voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
    $voucherReadBuilder->join('voucher_type_account','voucher_type_account.voucher_type_account_id=voucher_type.fk_voucher_type_account_id');
    $voucher_obj = $voucherReadBuilder->get();

    // $voucher_cost = 0;

    if($voucher_obj->getNumRows() > 0){
      $voucher_cost_array = $voucher_obj->getRowArray();
      if(isset($voucher_cost_array['voucher_detail_total_cost'])){
          $message = 'success';
      }
    }

    $from_voucher_obj = $voucherReadBuilder->select('voucher_id')
    ->where(array('voucher_number' => $voucher_number, 'fk_office_id' =>$office_id))->get();

    $from_voucher_id = 0;
    $voucher_cost = 0;

    if($from_voucher_obj->getNumRows() > 0){
      $from_voucher_id = $from_voucher_obj->getRowArray()['voucher_id'];
      // Compute unrefunded amount
      $voucher_cost = $voucherLibrary->unrefundedAmountByFromVoucherId($from_voucher_id);
    }
  

    $output = compact('message', 'voucher_cost', 'voucher_number');

    return $this->response->setJSON($output);
  }

  function uploadReceipts(): ResponseInterface{

    $post = $this->request->getPost();
    $voucher_id = $post['voucher_id'];

    // Query Builders
    $voucherReadBuilder = $this->read_db->table('voucher');
    $approveItemReadBuilder = $this->read_db->table('approve_item');
    
    $voucherReadBuilder->select(['account_system_code','office_code','voucher_number']);
    $voucherReadBuilder->where(['voucher_id' => $voucher_id]);
    $voucherReadBuilder->join('office','office.office_id = voucher.fk_office_id');
    $voucherReadBuilder->join('account_system','account_system.account_system_id = office.fk_account_system_id');
    $voucher = $voucherReadBuilder->get()->getRow();

    $account_system_code = $voucher->account_system_code;
    $office_code = $voucher->office_code;
    $voucher_number = $voucher->voucher_number;

    $storeFolder = upload_url('voucher', '', ['voucher_docs',$account_system_code,$office_code,$voucher_number,$voucher_id]);

    $approve_item_id = $approveItemReadBuilder->where(array('approve_item_name' => 'voucher'))
    ->get()
    ->getRow()->approve_item_id;

    $additional_attachment_table_insert_data = [];
 
    $itemTrackNumberAndName = $this->libs->generateItemTrackNumberAndName('attachment');
    $statusLibrary = new \App\Libraries\Core\StatusLibrary();
    $attachmentLibrary = new \App\Libraries\Core\AttachmentLibrary();
    $awsAttachmentLibrary = new \App\Libraries\System\AwsAttachmentLibrary();

    $additional_attachment_table_insert_data['fk_approve_item_id'] = $approve_item_id;
    $additional_attachment_table_insert_data['attachment_primary_id'] = $voucher_id;
    $additional_attachment_table_insert_data['attachment_is_s3_upload'] = 1;
    $additional_attachment_table_insert_data['attachment_created_by'] = $this->session->user_id;
    $additional_attachment_table_insert_data['attachment_last_modified_by'] = $this->session->user_id;
    $additional_attachment_table_insert_data['attachment_created_date'] = date('Y-m-d');
    $additional_attachment_table_insert_data['attachment_track_number'] = $itemTrackNumberAndName['attachment_track_number'];
    $additional_attachment_table_insert_data['fk_approval_id'] = $this->libs->insertApprovalRecord('attachment');
    $additional_attachment_table_insert_data['fk_status_id'] = $statusLibrary->initialItemStatus('attachment');
    $additional_attachment_table_insert_data['fk_attachment_type_id'] = $attachmentLibrary->getAttachmentTypeId('voucher_receipts');

    //log_message('error',json_encode($additional_attachment_table_insert_data));
    $attachment_where_condition_array = [];

    $attachment_where_condition_array = array(
      'fk_approve_item_id' => $approve_item_id,
      'attachment_primary_id' => $voucher_id
    );

    
    $preassigned_urls = $awsAttachmentLibrary->uploadFiles($storeFolder, $additional_attachment_table_insert_data, $attachment_where_condition_array);
    
    return $this->response->setJSON($preassigned_urls);
  }

  function deleteAttachment($attachment_id, $voucher_id){
    $approve_item_id = $this->read_db->table('approve_item')
    ->where(['approve_item_name' => 'voucher'])
    ->get()
    ->getRow()->approve_item_id;

    $attachments = [];
    $attachmentLibrary = new \App\Libraries\Core\AttachmentLibrary();
    $voucherLibrary = new \App\Libraries\Grants\VoucherLibrary();

    if($attachmentLibrary->deleteUploadedDocument($attachment_id)){
      $attachments = $voucherLibrary->getAttachments($approve_item_id, $voucher_id);
    }
    
    return $this->response->setJSON($attachments);
  }

  function getAttachmentDocuments($voucher_id){

    $approve_item_id = $this->read_db->table('approve_item')->where(['approve_item_name' => 'voucher'])->get()->getRow()->approve_item_id;

    $attachments = [];
    $voucherLibrary = new \App\Libraries\Grants\VoucherLibrary();

    $attachments = $voucherLibrary->getAttachments($approve_item_id, $voucher_id);
    
    return $this->response->setJSON($attachments);
  }

  function getExpenceAccountIncome(int $account_id, int $voucher_type)
  {


    //Get the voucher type effect
    $builder_reader=$this->read_db->table('voucher_type');
    $builder_reader->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
    $builder_reader->where(['voucher_type_id' => $voucher_type]);
    $voucher_type_effect_code=$builder_reader->get()->getRow()->voucher_type_effect_code;


    //Get income for an expense

    $builder_reader_expense=$this->read_db->table('expense_account');

    if ($voucher_type_effect_code == 'expense') {

      $builder_reader_expense->select(['fk_income_account_id']);

      $builder_reader_expense->where(['expense_account_id' => $account_id]);

      $income_account = $builder_reader_expense->get()->getRow()->fk_income_account_id;
    } else {
      $income_account = $account_id;
    }

    echo   $income_account;
  }
}
