<?php

namespace App\Controllers\Web\Grants;

use App\Controllers\Web\WebController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Libraries\{Core, Grants};

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
      $result = [];
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
   * @param int $$office_id, $transaction_date
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

    if ($voucher_type_account == 'bank' || $voucher_type_effect == 'cash_contra' || $voucher_type_effect == 'bank_to_bank_contra') {
      $response['office_banks'] = $this->library->getOfficeBanks($office_id);
    }

    if ($voucher_type_effect == 'bank_to_bank_contra' || $voucher_type_effect == 'cash_to_cash_contra') {
      $response['is_transfer_contra'] = true;
    }

    if ($voucher_type_effect == 'bank_to_bank_contra' || $voucher_type_effect == 'bank_contra' || ($voucher_type_account == 'bank' && $voucher_type_effect == 'expense')) {
      $response['is_bank_payment'] = true;
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

    if (!validate_date($transaction_date)) {
      $transaction_date = date('Y-m-d');
    }

    $office_accounting_system = $this->library->officeAccountSystem($office_id);

    $project_allocation = [];

    if (
      !$office_accounting_system->account_system_is_allocation_linked_to_account ||
      service("settings")->get("GrantsConfig.toggle_accounts_by_allocation")
    ) {
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

    $post = $this->request->getPost();
    $voucher_type_effect_and_code = $this->library->voucherTypeEffectAndCode($post['voucher_type_id']);
    $voucher_type_effect = $voucher_type_effect_and_code->voucher_type_effect_code;

    $accounts_obj = [];

    $project_allocation_id = $post['allocation_id'];
    $office_bank_id = $post['office_bank_id'];
    $office_accounting_system = $this->library->officeAccountSystem($this->request->getPost('office_id'));

    if ($voucher_type_effect == 'expense') {
      // Check if the office is a lead in an office group
      $is_office_group_lead = $officeGroupLibrary->checkIfOfficeIsOfficeGroupLead($this->request->getPost('office_id'));

      $builder = $this->read_db->table("expense_account");
      if (!$is_office_group_lead) {
        $string_condition = 'AND expense_account_office_association_is_active = 1';
        $this->libs->notExistsSubQuery($builder, 'expense_account', 'expense_account_office_association', $string_condition);
      }

      $builder->join('income_account', 'income_account.income_account_id=expense_account.fk_income_account_id');
      $builder->join('project_income_account', 'project_income_account.fk_income_account_id=income_account.income_account_id');
      $builder->join('project', 'project.project_id=project_income_account.fk_project_id');
      $builder->join('project_allocation', 'project_allocation.fk_project_id=project.project_id');
      $builder->where(array('project_allocation_id' => $project_allocation_id, 'expense_account_is_active' => 1));
      $builder->where(array('fk_account_system_id' => $office_accounting_system->account_system_id));
      $builder->select(array('expense_account_id as account_id', 'expense_account_name as account_name'));
      $accounts_obj = $builder->get();
    } elseif ($voucher_type_effect == 'income' || $voucher_type_effect == 'bank_to_bank_contra') {
      $builder = $this->read_db->table("income_account");
      $builder->where(array('project_allocation_id' => $project_allocation_id, 'income_account_is_active' => 1));
      $builder->where(array('fk_account_system_id' => $office_accounting_system->account_system_id));
      $builder->join('project_income_account', 'project_income_account.fk_income_account_id=income_account.income_account_id');
      $builder->join('project', 'project.project_id=project_income_account.fk_project_id');
      $builder->join('project_allocation', 'project_allocation.fk_project_id=project.project_id');
      $builder->select(array('income_account_id as account_id', 'income_account_name as account_name'));
      $accounts_obj = $builder->get();
    } elseif ($voucher_type_effect == 'cash_contra') {
      $accounts = $contraAccountLibrary->addContraAccount($office_bank_id);

      $builder = $this->read_db->table('contra_account');
      $builder->select(array('contra_account_id as account_id', 'contra_account_name as account_name', 'contra_account_code as account_code'));
      $builder->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=contra_account.fk_voucher_type_effect_id');
      $builder->join('office_bank', 'office_bank.office_bank_id=contra_account.fk_office_bank_id');
      $builder->where(array('fk_account_system_id' => $office_accounting_system->account_system_id));
      $accounts_obj = $builder->getWhere(
        array(
          'voucher_type_effect_code' => 'cash_contra',
          'office_bank_is_active' => 1,
          'office_bank_id' => $office_bank_id
        )
      );
    } elseif ($voucher_type_effect == 'bank_contra') {
      $contraAccountLibrary->addContraAccount($office_bank_id);

      $builder = $this->read_db->table("contra_account");
      $builder->select(array('contra_account_id as account_id', 'contra_account_name as account_name', 'contra_account_code as account_code'));
      $builder->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=contra_account.fk_voucher_type_effect_id');
      $builder->join('office_bank', 'office_bank.office_bank_id=contra_account.fk_office_bank_id');
      $builder->where(array('fk_account_system_id' => $office_accounting_system->account_system_id));
      $builder->where(array(
        'voucher_type_effect_code' => 'bank_contra',
        'office_bank_is_active' => 1,
        'office_bank_id' => $office_bank_id
      ));
      $accounts_obj = $builder->get();
    } elseif ($voucher_type_effect == 'cash_to_cash_contra') {

      $this->makeAtleastOneOfficeBankDefaultIfOneMissing($this->request->getPost('office_id'));

      $contraAccountLibrary->addContraAccount($office_bank_id);

      $builder = $this->read_db->table('office_bank');
      $builder->where(array('fk_office_id' => $this->request->getPost('office_id'), 'office_bank_is_active' => 1, 'office_bank_is_default' => 1));
      $office_bank_id = $builder->get()->getRow()->office_bank_id;

      $builder = $this->read_db->table('contra_account');
      $builder->select(array('contra_account_id as account_id', 'contra_account_name as account_name', 'contra_account_code as account_code'));
      $builder->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=contra_account.fk_voucher_type_effect_id');
      $builder->join('office_bank', 'office_bank.office_bank_id=contra_account.fk_office_bank_id');
      $builder->where(array('fk_account_system_id' => $office_accounting_system->account_system_id));
      $accounts_obj = $builder->getWhere(
        array(
          'voucher_type_effect_code' => 'cash_to_cash_contra',
          'fk_account_system_id' => $office_accounting_system->account_system_id,
          'office_bank_is_active' => 1,
          'office_bank_id' => $office_bank_id
        )
      );
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
}
