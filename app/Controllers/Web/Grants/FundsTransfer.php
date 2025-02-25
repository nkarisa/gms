<?php

namespace App\Controllers\Web\Grants;

use App\Controllers\Web\WebController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class FundsTransfer extends WebController
{

    function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

    }

  protected function result($id = '', $parentTable = null)
  {
      $result = parent::result();
      $fundsTransferLibrary = new \App\Libraries\Grants\FundsTransferLibrary();

      if($this->action == 'view'){
        $funds_transfer_request = $this->getSingleFundsTransferRequest(hash_id($this->id,'decode'));
        $result['transfer_request'] = $fundsTransferLibrary->formatFundsTransferRequest($funds_transfer_request);

        if(!$funds_transfer_request['office_bank_is_default'] || !$funds_transfer_request['office_bank_is_active']){
          $result['errors'][] = get_phrase('funds_transfer_error_missing_active_default_bank_account','FCP does not have an active default office bank. Resolved the issue before proceeding.');
        }

      }elseif($this->action == 'edit'){
        $request = $this->getFundsTransferRequests(hash_id($this->id,'decode'));
        $office_id = $request['fk_office_id'];
        $source_account = $request['funds_transfer_source_account_id'];
        $destination_account = $request['funds_transfer_target_account_id'];
        $funds_transfer_type = $request['funds_transfer_type'];

        // $result['accounts'] = $this->derive_funds_transfer_accounts($request['fk_office_id'],$request['funds_transfer_type'], $request['funds_transfer_source_project_allocation_id'] > 0 ? 1 : 0, $request['funds_transfer_target_project_allocation_id'] > 0 ? 1 : 0);
        $result['transfer_request'] = $request; 
        $result['allocation_codes'] = $this->fundsTransferAllocations($office_id);
        $result['source_accounts'] = $this->fundsTransferRequestAllocationAccounts($request['funds_transfer_type'], $request['funds_transfer_source_project_allocation_id']);
        $result['destination_accounts'] = $this->fundsTransferRequestAllocationAccounts($request['funds_transfer_type'], $request['funds_transfer_target_project_allocation_id']);
        $result['source_fund_balance'] = number_format($this->_incomeAccountFundBalance($office_id, $source_account,$request['funds_transfer_source_project_allocation_id'], $funds_transfer_type),2);
        $result['destination_fund_balance'] = number_format($this->_incomeAccountFundBalance($office_id, $destination_account,$request['funds_transfer_target_project_allocation_id'], $funds_transfer_type),2);

      }

      return $result;
  }

  function fundsTransferAllocations($office_id){

    $allocation_codes = [];
    // funds_transfer_allocations
    $builder = $this->read_db->table('project_allocation');
    $builder->select(array('project_allocation_id', 'project_name'));
    $builder->where(array('fk_office_id' => $office_id));
    $builder->join('project','project.project_id=project_allocation.fk_project_id');
    $allocations = $builder->get();

    if($allocations->getNumRows() > 0){
      $allocs = $allocations->getResultArray();
      $project_allocation_ids = array_column( $allocs, 'project_allocation_id');
      $project_names = array_column($allocs, 'project_name');

      $allocation_codes = array_combine($project_allocation_ids, $project_names);
    }

    return $allocation_codes;
  }

  private function getFundsTransferRequests($request_id){
    $builder = $this->read_db->table('funds_transfer');
    $builder->select(
      array(
        'funds_transfer_id',
        'fk_office_id',
        'funds_transfer_type',
        'funds_transfer_source_account_id',
        'funds_transfer_target_account_id',
        'funds_transfer_source_project_allocation_id',
        'funds_transfer_target_project_allocation_id',
        'funds_transfer_amount',
        'funds_transfer_description',
      )
    );
  
    $builder->where(array('funds_transfer_id' => $request_id));
    $request = $builder->get()->getRowArray();
  
    return $request;
  }

  function getSingleFundsTransferRequest($request_id){

    $fundsTransferReadBuilder = $this->read_db->table('funds_transfer');

    $columns = [
      'funds_transfer_id',
      'funds_transfer_created_date as raise_date',
      'fk_voucher_id as voucher_number',
      'office_name as office_name',
      'CONCAT(user_firstname, " " ,user_lastname) as requestor',
      'funds_transfer.fk_status_id as request_status',
      'funds_transfer_amount as amount',
      'funds_transfer_description as description',
      'funds_transfer_source_account_id as source_account',
      'funds_transfer_target_account_id as destination_account',
      'funds_transfer_source_project_allocation_id as source_project_allocation_id',
      'funds_transfer_target_project_allocation_id as target_project_allocation_id',
      'funds_transfer_type',
      'funds_transfer_last_modified_by',
      'funds_transfer.fk_status_id as funds_transfer_status_id',
      'funds_transfer_last_modified_date',
      'office.fk_account_system_id as fk_account_system_id',
      'office_bank.office_bank_is_active as office_bank_is_active',
      'office_bank.office_bank_is_default as office_bank_is_default',
    ];

    $fundsTransferReadBuilder->select($columns);
    $fundsTransferReadBuilder->join('office','office.office_id=funds_transfer.fk_office_id');
    $fundsTransferReadBuilder->join('office_bank','office_bank.fk_office_id=office.office_id');
    $fundsTransferReadBuilder->join('user','user.user_id=funds_transfer.funds_transfer_created_by');
    $fundsTransferReadBuilder->where(array('funds_transfer_id' => $request_id));
    $request = $fundsTransferReadBuilder->get()->getRowArray();

    //$assigned_voucher_numbers = [];
    $voucher_id = 0;

    foreach($request as $field_name => $field_value){
        if($field_name == 'voucher_number' && $field_value > 0){
          $voucher_id = $field_value;
        }
    } 

    $voucher = $this->read_db->table('voucher')
    ->where(array('voucher_id' => $voucher_id))
    ->get();

    if($voucher->getNumRows() > 0){
      $request['voucher_number'] = "<a target='__blank' href='".base_url()."voucher/view/".hash_id($voucher_id,'encode')."'>".$voucher->getRow()->voucher_number."</a>";
    }
   

    return $request;
  }

    function fundsTransferAllocationCodes(){
        $post = $this->request->getPost();
        $office_id = $post['office_id'];
    
        $allocation_codes = $this->fundsTransferAllocations( $office_id);

        return $this->response->setJSON($allocation_codes);
    }
    

      function fundsTransferAllocationAccounts(){

        $post = $this->request->getPost();
    
        $allocation_id = $post['allocation_id'];
        $funds_transfer_type = $post['funds_transfer_type'];
    
        $accounts = $this->fundsTransferRequestAllocationAccounts($funds_transfer_type, $allocation_id);
    
        return $this->response->setJSON($accounts);
      }

      function fundsTransferRequestAllocationAccounts($funds_transfer_type, $allocation_id){
        $accounts = [];
        $accounts_obj = null;
    
        if($funds_transfer_type == 1){
          $incomeAccountBuilder = $this->read_db->table('income_account');
          $incomeAccountBuilder->select(array('income_account_id as account_id', 'income_account_name as account_name'));
          $incomeAccountBuilder->join('project_income_account','project_income_account.fk_income_account_id=income_account.income_account_id');
          $incomeAccountBuilder->join('project','project.project_id=project_income_account.fk_project_id');
          $incomeAccountBuilder->join('project_allocation','project_allocation.fk_project_id=project.project_id');
          $incomeAccountBuilder->where(array('project_allocation_id' => $allocation_id, 'income_account_is_active' => 1));
          $accounts_obj = $incomeAccountBuilder->get();
        }else{
          $expenseAccountBuilder = $this->read_db->table('expense_account');  
          $expenseAccountBuilder->select(array('expense_account_id as account_id', 'expense_account_name as account_name'));
          $expenseAccountBuilder->join('income_account','income_account.income_account_id=expense_account.fk_income_account_id');
          $expenseAccountBuilder->join('project_income_account','project_income_account.fk_income_account_id=income_account.income_account_id');
          $expenseAccountBuilder->join('project','project.project_id=project_income_account.fk_project_id');
          $expenseAccountBuilder->join('project_allocation','project_allocation.fk_project_id=project.project_id');
          $expenseAccountBuilder->where(array('project_allocation_id' => $allocation_id, 'expense_account_is_active' => 1));
          $accounts_obj = $expenseAccountBuilder->get();
        }
        
        if($accounts_obj->getNumRows() > 0){
          $account_ids = array_column($accounts_obj->getResultArray(), 'account_id');
          $account_names = array_column($accounts_obj->getResultArray(), 'account_name');
        //   log_message('error', json_encode(compact('account_ids','account_names')));
          $accounts = array_combine($account_ids, $account_names);
        }
    
        return $accounts;
      }

      function incomeAccountFundBalance(){
        $post = $this->request->getPost();
    
        $account_id = $post['account_id'];
        $project_allocation_id = $post['project_allocation_id'];
        $funds_transfer_type = $post['funds_transfer_type'];
        $office_id = $post['office_id'];
    
        $fund_balance_amount = $this->_incomeAccountFundBalance($office_id, $account_id, $project_allocation_id, $funds_transfer_type);
    
        return number_format($fund_balance_amount,2);
      }

      private function _incomeAccountFundBalance($office_id, $account_id, $project_allocation_id, $funds_transfer_type){
        // Get Current Voucher Date 
        $voucherLibrary = new \App\Libraries\Grants\VoucherLibrary();
        $incomeAccountLibrary = new \App\Libraries\Grants\IncomeAccountLibrary();
        $financeReportLibrary = new \App\Libraries\Grants\FinancialReportLibrary();
        
        $office_voucher_date = $voucherLibrary->getOfficeVoucherDate($office_id);

        $voucher_date = $office_voucher_date['next_vouching_date'];
    
        $income_account_id = $account_id;
    
        if($funds_transfer_type == 2){
          $income_account_id = $incomeAccountLibrary->getExpenseIncomeAccount($account_id)->income_account_id;
        }
    
        $project_id = 0;
        $projectAllocationBuilder = $this->read_db->table('project_allocation');
        $projectAllocationBuilder->where(array('project_allocation_id' => $project_allocation_id));
        $project_allocation_obj = $projectAllocationBuilder->get();
    
        if($project_allocation_obj->getNumRows() > 0){
          $project_id = $project_allocation_obj->getRow()->fk_project_id;
        }
        
        $fund_balance_amount  = $financeReportLibrary->getFundBalanceByAccount($office_id,$income_account_id, date('Y-m-01',strtotime($voucher_date)), $project_id);
        
        return $fund_balance_amount;
      }

    function postFundsTransfer($funds_transfer_id = 0)
    {
        $post = $this->request->getPost();
        $statusLibrary = new \App\Libraries\Core\StatusLibrary();
        $initial_status = $statusLibrary->initialItemStatus('funds_transfer');

        //echo json_encode($post);
        $source_account = $post['source_account'];
        $source_project_allocation = $post['source_allocation'];

        $destination_account = $post['destination_account'];
        $destination_project_allocation = $post['destination_allocation'];

        $itemTrackNumberAndName = $this->libs->generateItemTrackNumberAndName('funds_transfer');

        $data['funds_transfer_track_number'] = $itemTrackNumberAndName['funds_transfer_track_number'];
        $data['funds_transfer_name'] = $itemTrackNumberAndName['funds_transfer_name'];
        $data['fk_office_id'] = $post['office_id'];
        $data['funds_transfer_source_account_id'] = $source_account;
        $data['funds_transfer_target_account_id'] = $destination_account;
        $data['funds_transfer_source_project_allocation_id'] = $source_project_allocation;
        $data['funds_transfer_target_project_allocation_id'] = $destination_project_allocation;
        $data['funds_transfer_type'] = $post['transfer_type'];
        $data['funds_transfer_amount'] = $post['transfer_amount'];
        $data['funds_transfer_description'] = $post['transfer_description'];
        $data['fk_voucher_id'] = 0;

        if ($funds_transfer_id == 0) {
            $data['fk_status_id'] = $initial_status;
        }

        // $data['fk_approval_id'] = $this->grants_model->insert_approval_record('funds_transfer'); //3230888
        $data['funds_transfer_created_date'] = date('Y-m-d');
        $data['funds_transfer_created_by'] = $this->session->user_id;
        $data['funds_transfer_last_modified_by'] = $this->session->user_id;
        $data['funds_transfer_last_modified_date'] = date('Y-m-d h:i:s');

        $message = "Request not created/updated";

        $fundsTransferWriteBuilder = $this->write_db->table('funds_transfer');

        if ($funds_transfer_id == 0) {
            $fundsTransferWriteBuilder->insert($data);

            if ($this->write_db->affectedRows() > 0) {
                $message = "Request created successful";

                $funds_transfer_id = $this->write_db->insertId();
            }
        } else {
    
            $fundsTransferWriteBuilder->where(array('funds_transfer_id' => $funds_transfer_id));
            //$data['accepted'] = 2;
            $fundsTransferWriteBuilder->update($data);

            if ($this->write_db->affectedRows() > 0) {
                $message = "Request Updated successful";
            }

        }


        if ($this->write_db->affectedRows() > 0) {

            $status_id = $fundsTransferWriteBuilder
            ->where(array('funds_transfer_id' => $funds_transfer_id))
            ->get()
            ->getRow()->fk_status_id;

            $next_status_id = $this->libs->nextStatus($status_id);
            $item['item'] = 'funds_transfer';
            $item['post']['next_status'] = $next_status_id;
            $item['post']['item_id'] = $funds_transfer_id;
            $item['post']['current_status'] = $status_id;

            $fundsTransferLibrary = new \App\Libraries\Grants\FundsTransferLibrary();

            $fundsTransferLibrary->postApprovalActionEvent($item);

        }

        return $message;
    }
}
