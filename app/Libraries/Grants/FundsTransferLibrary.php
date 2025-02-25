<?php 

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\FundsTransferModel;

class FundsTransferLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface {
    protected $table;
    protected $fundsTransferModel;
    public $lookup_tables_with_null_values = ["voucher"];

    function __construct()
    {
        parent::__construct();

        $this->fundsTransferModel = new FundsTransferModel();

        $this->table = 'funds_transfer';
    }

    public function listTableVisibleColumns(): array
    {
        return [
            "funds_transfer_id",
            "funds_transfer_track_number",
            "status_name",
            "office_name",
            "funds_transfer_type",
            "funds_transfer_description",
            "funds_transfer_amount",
        ];
    }

    function changeFieldType(): array{
        $change_field_type = array();
    
        $change_field_type['funds_transfer_type']['field_type'] = 'select';
        $change_field_type['funds_transfer_type']['options'] = ['1' => get_phrase('income_type'), '2' => get_phrase('expense_type')];
    
        return $change_field_type;
      }

      function singleFormAddVisibleColumns(): array {
        return [
            "funds_transfer_track_number",
            "funds_transfer_type",
            "funds_transfer_description",
            "funds_transfer_amount"
        ];
      }

      function postApprovalActionEvent($item): void{

        // log_message('error', json_encode($item));
        $statusLibrary = new \App\Libraries\Core\StatusLibrary();
        $account_system_id = $statusLibrary->getStatusAccountSystem($item['post']['next_status']);

        $status_data = $this->actionButtonData($this->controller, $account_system_id);
        $item_max_approval_status_ids = $status_data['item_max_approval_status_ids'];
        $next_status = $item['post']['next_status'];
        $current_status = $item['post']['current_status'];

        $next_status_approval_direction = $status_data['item_status'][$next_status]['status_approval_direction'];
        
        if(in_array($next_status, $item_max_approval_status_ids)){
            // log_message('error', $item['post']['next_status']);
            $funds_transfer_id = $item['post']['item_id'];
            
            // Check if a hidden bank income and expense voucher types are available for this accounting system
            // Create them if missing
            $voucherTypeLibrary = new \App\Libraries\Grants\VoucherTypeLibrary();
            $is_hidden_voucher_type_available = $voucherTypeLibrary->checkIfHiddenBankIncomeExpenseVoucherTypePresent();
            
            if($is_hidden_voucher_type_available){
                $this->createFundsTransferVoucher($funds_transfer_id);
            }
            
        }elseif($next_status_approval_direction == -1 && in_array($current_status, $item_max_approval_status_ids)){

            $this->write_db->transStart();
            // Check if the transfer was fully approved by checking if it has a voucher id assigned
            $fundsTransferReadBuilder = $this->read_db->table('funds_transfer');
            $fundsTransferReadBuilder->where(array('fk_voucher_id > ' => 0,'funds_transfer_id' => $item['post']['item_id']));
            $funds_transfer = $fundsTransferReadBuilder->get();

            $is_approved_funds_transfer = $funds_transfer->getNumRows();

            if($is_approved_funds_transfer > 0){

                $voucherLibrary = new \App\Libraries\Grants\VoucherLibrary();
                // Reverse the voucher number. Append the funds transfer track number to the reversal voucher
                $voucherLibrary->reverseVoucher($funds_transfer->getRow()->fk_voucher_id);

                // Update the funds transfer record by removing the voucher id
                $fundsTransferWriteDb = $this->write_db->table('funds_transfer');
                $fundsTransferWriteDb->where(array('funds_transfer_id' => $item['post']['item_id']));
                $fundsTransferWriteDb->update(['fk_voucher_id' => 0]);
                
            }

            $this->write_db->transComplete();

            if ($this->write_db->transStatus() === FALSE)
            {
                // Return the approval status of the transfer upon failure
                // log_message('error', json_encode(['funds_transfer_id' => $item['post']['item_id'], 'fk_status_id' => $current_status]));
                // $this->write_db->where(array('funds_transfer_id' => $item['post']['item_id']));
                // $this->write_db->update('funds_transfer', ['fk_status_id' => $current_status]);

                log_message('error', 'Funds transfer update on declining from fully approved failed');
            }
        }

    }

    function getSingleFundsTransfer($funds_transfer_id){

        $fundsTransferReadBuilder = $this->read_db->table('funds_transfer');

        $fundsTransferReadBuilder->select(
            [
                'office_name',
                'office_id',
                'office_bank_id',
                'funds_transfer_id',
                'funds_transfer_source_account_id',
                'funds_transfer_target_account_id',
                'funds_transfer_source_project_allocation_id',
                'funds_transfer_target_project_allocation_id',
                'funds_transfer_type',
                'funds_transfer_amount',
                'funds_transfer_description',
                'funds_transfer.fk_status_id as fk_status_id',
                'funds_transfer_created_date',
                'funds_transfer_created_by',
            ]
        );
        $fundsTransferReadBuilder->join('office','office.office_id=funds_transfer.fk_office_id');
        $fundsTransferReadBuilder->join('office_bank','office_bank.fk_office_id=office.office_id');
        $fundsTransferReadBuilder->where(array('funds_transfer_id' => $funds_transfer_id,'office_bank_is_default' => 1, 'office_bank_is_active' => 1));
        $funds_transfer = $fundsTransferReadBuilder->get()->getRow();

        return $funds_transfer;
    }

    function createFundsTransferVoucher($funds_transfer_id){
        
        // Get the Funds Transfer
        $funds_transfer = $this->getSingleFundsTransfer($funds_transfer_id);
        $office_id = $funds_transfer->office_id;
        $office_name = $funds_transfer->office_name;
        $funds_transfer_creator = $funds_transfer->funds_transfer_created_by;
        $office_default_bank = $funds_transfer->office_bank_id;
        $funds_transfer_description = $funds_transfer->funds_transfer_description;
        $funds_transfer_amount = $funds_transfer->funds_transfer_amount;
        $funds_transfer_type = $funds_transfer->funds_transfer_type;
        $funds_transfer_source_account_id = $funds_transfer->funds_transfer_source_account_id;
        $funds_transfer_target_account_id = $funds_transfer->funds_transfer_target_account_id;
        $funds_transfer_source_project_allocation_id = $funds_transfer->funds_transfer_source_project_allocation_id;
        $funds_transfer_target_project_allocation_id = $funds_transfer->funds_transfer_target_project_allocation_id;

        // Get Current Voucher Date
        $voucherLibrary = new \App\Libraries\Grants\VoucherLibrary(); 
        $office_voucher_date = $voucherLibrary->getOfficeVoucherDate($office_id);
        $voucher_date = $office_voucher_date['next_vouching_date'];
        $last_vouching_month_date = $office_voucher_date['last_vouching_month_date'];

        // Get Current Voucher Number
        $voucher_number = $voucherLibrary->getVoucherNumber($office_id);

        // Construct the Voucher Records Array

        $details = [
            [
                'voucher_detail_description' => get_phrase('funds_transfer_source_account'),
                'voucher_detail_unit_cost' => -$funds_transfer_amount,
                'voucher_detail_total_cost' => -$funds_transfer_amount,
                'fk_expense_account_id' => $funds_transfer_type == 2 ? $funds_transfer_source_account_id : 0,
                'fk_income_account_id' => $funds_transfer_type == 1 ? $funds_transfer_source_account_id : 0,
                'fk_project_allocation_id' => $funds_transfer_source_project_allocation_id,

            ],
            [
                'voucher_detail_description' => get_phrase('funds_transfer_target_account'),
                'voucher_detail_unit_cost' => $funds_transfer_amount,
                'voucher_detail_total_cost' => $funds_transfer_amount,
                'fk_expense_account_id' => $funds_transfer_type == 2 ? $funds_transfer_target_account_id : 0,
                'fk_income_account_id' => $funds_transfer_type == 1 ? $funds_transfer_target_account_id : 0,
                'fk_project_allocation_id' => $funds_transfer_target_project_allocation_id,
            ]
        ];

        $officeLibrary = new \App\Libraries\Core\OfficeLibrary();
        $office = $officeLibrary->getOfficeById($office_id);
      
        $voucherTypeLibrary = new \App\Libraries\Grants\VoucherTypeLibrary();
        $cash_received_voucher_type = $voucherTypeLibrary->getHiddenVoucherType('IFTR', $office['account_system_id'])->voucher_type_id;
        $cash_expense_voucher_type = $voucherTypeLibrary->getHiddenVoucherType('EFTR', $office['account_system_id'])->voucher_type_id;

        $userLibrary = new \App\Libraries\Core\UserLibrary();  // for getting user full name
        $requestor = $userLibrary->getUserFullName($funds_transfer_creator);

        $voucher_type_id = $funds_transfer->funds_transfer_type == 1 ? $cash_received_voucher_type : $cash_expense_voucher_type;

        $action_button_data = $this->actionButtonData('voucher', $office['account_system_id']);

        $voucher_fully_approved_status = $action_button_data['item_max_approval_status_ids'][0];
        $voucher_detail_fully_approved_status = $action_button_data['item_max_approval_status_ids'][0];

        $itemTrackNumberAndName = $this->generateItemTrackNumberAndName('voucher');
        $data['header']['voucher_track_number'] = $itemTrackNumberAndName['voucher_track_number'];
        $data['header']['voucher_name'] = $itemTrackNumberAndName['voucher_name'];
        $data['header']['voucher_date'] = $voucher_date;
        $data['header']['voucher_number'] = $voucher_number;
        $data['header']['fk_office_id'] = $office_id;
        $data['header']['fk_voucher_type_id'] = $voucher_type_id;
        $data['header']['voucher_cleared'] = 1;
        $data['header']['voucher_cleared_month'] = $last_vouching_month_date;
        $data['header']['fk_office_bank_id'] = $office_default_bank;
        $data['header']['fk_office_cash_id'] = 0;
        $data['header']['voucher_cheque_number'] = 'FTR';
        $data['header']['voucher_vendor'] = $requestor;
        $data['header']['voucher_vendor_address'] = $office_name;
        $data['header']['voucher_description'] = $funds_transfer_description;
        $data['header']['voucher_allow_edit'] = 0;
        $data['header']['voucher_is_reversed'] = 0;
        $data['header']['voucher_reversal_from'] = 0;
        $data['header']['voucher_reversal_to'] = 0;
        $data['header']['voucher_created_date'] = date('Y-m-d');
        $data['header']['voucher_last_modified_date'] = date('Y-m-d h:i:s');
        $data['header']['voucher_created_by'] = $funds_transfer_creator;
        $data['header']['voucher_last_modified_by'] = $funds_transfer_creator;
        $data['header']['fk_status_id'] = $voucher_fully_approved_status;

        $voucherDetailitemTrackNumberAndName = $this->generateItemTrackNumberAndName('voucher_detail');
        for($i = 0; $i < sizeof($details); $i++){
            $data['detail'][$i]['voucher_detail_track_number'] = $voucherDetailitemTrackNumberAndName['voucher_detail_track_number'];
            $data['detail'][$i]['voucher_detail_name'] = $voucherDetailitemTrackNumberAndName['voucher_detail_name'];
            $data['detail'][$i]['voucher_detail_description'] = $details[$i]['voucher_detail_description'];
            $data['detail'][$i]['voucher_detail_quantity'] = 1;
            $data['detail'][$i]['voucher_detail_unit_cost'] = $details[$i]['voucher_detail_unit_cost'];
            $data['detail'][$i]['voucher_detail_total_cost'] = $details[$i]['voucher_detail_total_cost'];
            $data['detail'][$i]['fk_expense_account_id'] = $details[$i]['fk_expense_account_id'];
            $data['detail'][$i]['fk_income_account_id'] = $details[$i]['fk_income_account_id'];
            $data['detail'][$i]['fk_contra_account_id'] = 0;
            $data['detail'][$i]['fk_status_id'] = $voucher_detail_fully_approved_status;
            $data['detail'][$i]['fk_request_detail_id'] = 0;
            $data['detail'][$i]['fk_project_allocation_id'] = $details[$i]['fk_project_allocation_id'];;
            $data['detail'][$i]['voucher_detail_last_modified_date'] = date('Y-m-d h:i:s');
            $data['detail'][$i]['voucher_detail_last_modified_by'] = $funds_transfer_creator;
            $data['detail'][$i]['voucher_detail_created_by'] = $funds_transfer_creator;
            $data['detail'][$i]['voucher_detail_created_date'] = date('Y-m-d');
        }

        // Insert the Voucher Record If it doesn't exists and update funds transfer voucher id
        $voucher_id = $voucherLibrary->createVoucher($data);

        if($voucher_id > 0){
            $this->updateFundsTransferVoucherId($funds_transfer_id,$voucher_id);

            // If the first voucher of the month, create a financial report and journal
            $voucherLibrary->createReportAndJournal($office_id, $last_vouching_month_date);
        }
    }

    private function updateFundsTransferVoucherId($funds_transfer_id,$voucher_id){

        $data['fk_voucher_id'] = $voucher_id;
        $builder = $this->write_db->table('funds_transfer');
        $builder->where(array('funds_transfer_id' => $funds_transfer_id));
        $builder->update($data);
    }

    function formatFundsTransferRequest($funds_transfer_request){
        // Use the status name rather than status_id
        $actions = $this->actionButtonData($this->controller, $funds_transfer_request['fk_account_system_id']);
        $status_data = $actions['item_status'];
        $funds_transfer_status = $funds_transfer_request['request_status'];
        $funds_transfer_request['request_status'] = $status_data[$funds_transfer_status]['status_name'];
  
        //Source account name
        $source_account = '';
        $destination_account = '';
  
        if($funds_transfer_request['funds_transfer_type'] == 1){
  
          $incomeAccountReadBuilder = $this->read_db->table('income_account');
          $incomeAccountReadBuilder->select(array('income_account_id as account_id','income_account_name as account_name'));
          $incomeAccountReadBuilder->join('project_income_account','project_income_account.fk_income_account_id=income_account.income_account_id');
          $incomeAccountReadBuilder->join('project','project.project_id=project_income_account.fk_project_id');
          $incomeAccountReadBuilder->whereIn('income_account_id', [$funds_transfer_request['source_account'], $funds_transfer_request['destination_account']]);
          $accounts = $incomeAccountReadBuilder->get()->getResultArray();
  
          $account_ids = array_column($accounts,'account_id');
          $account_names = array_column($accounts,'account_name');
          
          $source_and_destination_account = array_combine($account_ids, $account_names);
  
          $source_account = $source_and_destination_account[$funds_transfer_request['source_account']];
          $destination_account  = $source_and_destination_account[$funds_transfer_request['destination_account']];
         
  
        }else{
          $expenseAccountReadBuilder = $this->read_db->table('expense_account');
          $expenseAccountReadBuilder->select(array('expense_account_id as account_id','expense_account_name as account_name'));
          $expenseAccountReadBuilder->join('income_account','income_account.income_account_id=expense_account.fk_income_account_id');
          $expenseAccountReadBuilder->join('project_income_account','project_income_account.fk_income_account_id=income_account.income_account_id');
          $expenseAccountReadBuilder->join('project','project.project_id=project_income_account.fk_project_id');
          $expenseAccountReadBuilder->whereIn('expense_account_id', [$funds_transfer_request['source_account'], $funds_transfer_request['destination_account']]);
          $accounts = $expenseAccountReadBuilder->get()->getResultArray();
  
          $account_ids = array_column($accounts,'account_id');
          $account_names = array_column($accounts,'account_name');
          
          $source_and_destination_account = array_combine($account_ids, $account_names);
  
          $source_account = $source_and_destination_account[$funds_transfer_request['source_account']];
          $destination_account  = $source_and_destination_account[$funds_transfer_request['destination_account']];
          
        }
  
        $projectAllocationReadBuilder = $this->read_db->table('project_allocation');
        $projectAllocationReadBuilder->select(array('project_allocation_id','project_name'));
        $projectAllocationReadBuilder->join('project','project.project_id=project_allocation.fk_project_id');
        $projectAllocationReadBuilder->whereIn('project_allocation_id', [$funds_transfer_request['source_project_allocation_id'], $funds_transfer_request['target_project_allocation_id']]);
        $project_allocations = $projectAllocationReadBuilder->get()->getResultArray();
  
        $project_allocation_ids = array_column($project_allocations,'project_allocation_id');
        $project_names = array_column($project_allocations,'project_name');
          
        $source_and_destination_allocation = array_combine($project_allocation_ids, $project_names);
  
        $source_allocation = $source_and_destination_allocation[$funds_transfer_request['source_project_allocation_id']];
        $destination_allocation  = $source_and_destination_allocation[$funds_transfer_request['target_project_allocation_id']];
  
  
        $final_approver = $this->getFinalApprover($funds_transfer_request, $actions);
  
        $funds_transfer_request['funds_transfer_approved_by'] = $final_approver['funds_transfer_approved_by'];
        $funds_transfer_request['funds_transfer_approval_date'] = $final_approver['funds_transfer_approval_date'];

        $funds_transfer_request['funds_transfer_raise_date'] = $funds_transfer_request['raise_date'];
  
        $funds_transfer_request['source_account'] = $source_account;
        $funds_transfer_request['destination_account'] = $destination_account;
  
        $funds_transfer_request['source_allocation'] = $source_allocation;
        $funds_transfer_request['destination_allocation'] = $destination_allocation;
      
      return $funds_transfer_request;
    }

    private function getFinalApprover($funds_transfer_request, $status_data){
  
        $approver_data = [];
    
        $approver_data['funds_transfer_approved_by'] = '';
        $approver_data['funds_transfer_approval_date'] = '';
    
        $funds_transfer_last_modified_by = $funds_transfer_request['funds_transfer_last_modified_by'];
        $funds_transfer_status_id = $funds_transfer_request['funds_transfer_status_id'];
        $funds_transfer_last_modified_date = $funds_transfer_request['funds_transfer_last_modified_date'];
        $item_max_approval_status_ids = $status_data['item_max_approval_status_ids'];
    
        if(in_array($funds_transfer_status_id, $item_max_approval_status_ids)){
          $userLibrary = new \App\Libraries\Core\UserLibrary();
          $approver_data['funds_transfer_approved_by'] = $userLibrary->getUserFullName($funds_transfer_last_modified_by);
          $approver_data['funds_transfer_approval_date'] = $funds_transfer_last_modified_date;
        }
        
        return $approver_data;
      }

    function showListEditAction($row): bool{
        $bool = false;
        
        if($row['status_approval_sequence'] == 1){
            $bool = true;
        }

        return $bool;
    }
    
}