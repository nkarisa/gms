<?php

namespace App\Controllers\Web\Grants;

use App\Controllers\Web\WebController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class SystemOpeningBalance extends WebController
{

    private $systemOpeningBalanceReadBuilder;
    private $openingBankBalanceReadBuilder;
    private $openingBankBalanceWriteBuilder;
    private $openingCashBalanceReadBuilder;
    private $openingCashBalanceWriteBuilder;
    private $openingFundBalanceReadBuilder;
    private $openingFundBalanceWriteBuilder;
    private $outstandingChequeReadBuilder;
    private $outstandingChequeWriteBuilder;
    private $openingDepositTransitReadBuilder;
    private $openingDepositTransitWriteBuilder;
    private $projectReadBuilder;
    private $officeCashReadBuilder;
    private $financialReportLibrary;
    private $statusLibrary;
    private $approvalLibrary;
    private $attachmentLibrary;

    function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->systemOpeningBalanceReadBuilder = $this->read_db->table('system_opening_balance');
        $this->openingBankBalanceReadBuilder = $this->read_db->table('opening_bank_balance');
        $this->openingBankBalanceWriteBuilder = $this->write_db->table('opening_bank_balance');
        $this->openingCashBalanceReadBuilder = $this->read_db->table('opening_cash_balance');
        $this->openingCashBalanceWriteBuilder = $this->write_db->table('opening_cash_balance');
        $this->officeCashReadBuilder = $this->read_db->table('office_cash');
        $this->openingFundBalanceReadBuilder = $this->read_db->table('opening_fund_balance');
        $this->openingFundBalanceWriteBuilder = $this->write_db->table('opening_fund_balance');
        $this->outstandingChequeReadBuilder  = $this->read_db->table('opening_outstanding_cheque');
        $this->outstandingChequeWriteBuilder  = $this->write_db->table('opening_outstanding_cheque');
        $this->openingDepositTransitReadBuilder = $this->read_db->table('opening_deposit_transit');
        $this->openingDepositTransitWriteBuilder = $this->write_db->table('opening_deposit_transit');
        $this->projectReadBuilder = $this->read_db->table('project');

        $this->statusLibrary = new \App\Libraries\Core\StatusLibrary();
        $this->approvalLibrary = new \App\Libraries\Core\ApprovalLibrary();
        $this->financialReportLibrary = new \App\Libraries\Grants\FinancialReportLibrary();
        $this->attachmentLibrary = new \App\Libraries\Core\AttachmentLibrary();

    }

    function result($id = null, $parentTable = null)
    {
        $result = parent::result($id, $parentTable);

        if ($this->action == 'edit') {
            $result['header'] = $this->masterTable();

            $system_opening_balance_id = hash_id($this->id, 'decode');
            $result['cash_boxes'] = $this->getCashBoxesForOfficeBySystemOpeningBalanceId($system_opening_balance_id);
            $result['office_projects'] = $this->getOfficeProjectsBySystemOpeningBalanceId($system_opening_balance_id);
            $result['income_accounts'] = [];

            $office_id = $this->systemOpeningBalanceReadBuilder->where(['system_opening_balance_id' => hash_id($this->id, 'decode')])
                ->get()
                ->getRow()->fk_office_id;

            $result['office_banks'] = $this->financialReportLibrary->getOfficeBanks([$office_id]);

        }

        return $result;
    }

    function getOfficeProjectsBySystemOpeningBalanceId($system_opening_balance_id)
    {

        $this->projectReadBuilder->select(array('project_id', 'project_name'));
        $this->projectReadBuilder->where(array('system_opening_balance_id' => $system_opening_balance_id));
        $this->projectReadBuilder->join('project_allocation', 'project_allocation.fk_project_id=project.project_id');
        $this->projectReadBuilder->join('office', 'office.office_id=project_allocation.fk_office_id');
        $this->projectReadBuilder->join('system_opening_balance', 'system_opening_balance.fk_office_id=office.office_id');
        $projects = $this->projectReadBuilder->get()->getResultArray();

        $trimed_projects = [];
        foreach ($projects as $project) {
            $trimed_projects[] = cleanStringForJson($project['project_name']); // trim($project['project_name']);
        }

        $ids = array_column($projects, 'project_id');
        $names = $trimed_projects;// array_column($projects, 'project_name');

        return array_combine($ids, $names);
    }

    function getCashBoxesForOfficeBySystemOpeningBalanceId($system_opening_balance_id)
    {

        $this->officeCashReadBuilder->select(array('office_cash_id', 'office_cash_name'));
        $this->officeCashReadBuilder->where(array('system_opening_balance_id' => $system_opening_balance_id));
        $this->officeCashReadBuilder->join('account_system', 'account_system.account_system_id=office_cash.fk_account_system_id');
        $this->officeCashReadBuilder->join('office', 'office.fk_account_system_id=account_system.account_system_id');
        $this->officeCashReadBuilder->join('system_opening_balance', 'system_opening_balance.fk_office_id=office.office_id');
        $office_cash = $this->officeCashReadBuilder->get()->getResultArray();

        $ids = array_column($office_cash, 'office_cash_id');
        $names = array_column($office_cash, 'office_cash_name');

        return array_combine($ids, $names);
    }

    function masterTable()
    {

        $this->systemOpeningBalanceReadBuilder->select(array(
            'system_opening_balance_track_number',
            'system_opening_balance_name',
            'system_opening_balance_created_date',
            'CONCAT(user_firstname," ", user_lastname) as system_opening_balance_created_by',
            'system_opening_balance_last_modified_date',
            'office_name'
        ));
        $this->systemOpeningBalanceReadBuilder->join('office', 'office.office_id=system_opening_balance.fk_office_id');
        $this->systemOpeningBalanceReadBuilder->join('user', 'user.user_id=system_opening_balance.system_opening_balance_created_by');
        $this->systemOpeningBalanceReadBuilder->where(array('system_opening_balance_id' => hash_id($this->id, 'decode')));
        $result = $this->systemOpeningBalanceReadBuilder->get()->getRowArray();

        return $result;
    }

    private function getSystemOpeningBalanceOfficeId($system_opening_balance_id)
    {
        $office_id = $this->systemOpeningBalanceReadBuilder->where(['system_opening_balance_id' => $system_opening_balance_id])
            ->get()
            ->getRow()->fk_office_id;

        return $office_id;
    }

    function openingBankBalance($office_id, $office_bank_id)
    {

        $system_opening_bank_balance = $this->financialReportLibrary->systemOpeningBankBalance([$office_id], [], [$office_bank_id]);

        return $system_opening_bank_balance;
    }

    function openingCashBalance($system_opening_balance_id, $office_bank_id)
    {

        $this->openingCashBalanceReadBuilder->select(array('fk_office_cash_id as office_cash_id'));
        $this->openingCashBalanceReadBuilder->selectSum('opening_cash_balance_amount');
        $this->openingCashBalanceReadBuilder->where(array('fk_system_opening_balance_id' => $system_opening_balance_id, 'fk_office_bank_id' => $office_bank_id));
        $this->openingCashBalanceReadBuilder->groupBy(array('fk_office_cash_id'));
        $obj = $this->openingCashBalanceReadBuilder->get();

        $balances = [];

        if ($obj->getNumRows() > 0) {
            $rec = $obj->getResultArray();

            for ($i = 0; $i < count($rec); $i++) {
                $balances[$rec[$i]['office_cash_id']]['office_cash_id'] = $rec[$i]['office_cash_id'];
                $balances[$rec[$i]['office_cash_id']]['amount'] = $rec[$i]['opening_cash_balance_amount'];
            }
        }

        return $balances;
    }

    function openingBankStatements($office_id): array
    {
        $approveItemReadBuilder = $this->read_db->table('approve_item');

        $system_opening_balance_ids = [];

        $this->systemOpeningBalanceReadBuilder->select(array('system_opening_balance_id'));
        $this->systemOpeningBalanceReadBuilder->where(array('fk_office_id' => $office_id));
        $system_opening_balance_obj = $this->systemOpeningBalanceReadBuilder->get();

        if ($system_opening_balance_obj->getNumRows() > 0) {
            $system_opening_balance_ids = $system_opening_balance_obj->getResultArray();
        }

        $attachment_where_condition_array = [];

        $approve_item_name = 'system_opening_balance';

        $approve_item_id = $approveItemReadBuilder->where(array('approve_item_name' => $approve_item_name))
            ->get()
            ->getRow()->approve_item_id;


        $attachment_where_condition_array['fk_approve_item_id'] = $approve_item_id;
        $attachment_where_condition_array['attachment_primary_id'] = array_column($system_opening_balance_ids, 'system_opening_balance_id');

        return $this->awsAttachmentLibrary->retrieveFileUploadsInfo($attachment_where_condition_array);
    }

    function loadOfficeBankBalances()
    {
        $openingFundBalanceReadBuilder = $this->read_db->table('opening_fund_balance');
        $openingOutstandingChequeReadBuilder = $this->read_db->table('opening_outstanding_cheque');
        $openingBankBalanceReadBuilder = $this->read_db->table('opening_bank_balance');
        $openingDepositTransitReadBuilder = $this->read_db->table('opening_deposit_transit');

        $post = $this->request->getPost();
        $system_opening_balance_id = $post['system_opening_balance_id'];

        $office_id = $this->getSystemOpeningBalanceOfficeId($system_opening_balance_id);

        // Proof of Cash
        $office_bank_id = $post['office_bank_id'];
        $result['opening_bank_balance'] = $this->openingBankBalance($office_id, $office_bank_id);
        $system_opening_cash_balance = $this->openingCashBalance($system_opening_balance_id, $office_bank_id);
        $result['opening_cash_balance'] = $system_opening_cash_balance;
        $total_petty_cash_balance = array_sum(array_column($result['opening_cash_balance'], 'amount'));
        $result['opening_total_cash'] = $result['opening_bank_balance'] + $total_petty_cash_balance;
        $bank_statements_uploads = $this->openingBankStatements($office_id);

        $result['bank_statements_uploads'] = view('system_opening_balance/list_statements', ['bank_statements_uploads' => $bank_statements_uploads]);

        $openingFundBalanceReadBuilder->select(array('fk_project_id as  project_id', 'fk_income_account_id as income_account_id', 'income_account_name', 'opening_fund_balance_opening as opening', 'opening_fund_balance_income income', 'opening_fund_balance_expense expense', 'opening_fund_balance_amount closing'));
        $openingFundBalanceReadBuilder->where(array('opening_fund_balance.fk_system_opening_balance_id' => $system_opening_balance_id, 'fk_project_id >' => 0));
        $openingFundBalanceReadBuilder->join('income_account', 'income_account.income_account_id=opening_fund_balance.fk_income_account_id');
        $opening_fund_balance = $openingFundBalanceReadBuilder->get()->getResultArray();

        $fund_balance = [];
        $i = 0;
        foreach ($opening_fund_balance as $balance) {
            $fund_balance[$i]['project_id'] = $balance['project_id'];
            $fund_balance[$i]['income_account_id'] = $balance['income_account_id'];
            $fund_balance[$i]['income_account_name'] = $balance['income_account_name'];
            $fund_balance[$i]['opening'] = ($balance['opening'] == NULL || $balance['opening'] == 0) && ($balance['income'] == NULL || $balance['income'] == 0) && ($balance['expense'] == NULL || $balance['expense'] == 0) ? $balance['closing'] : $balance['opening'];
            $fund_balance[$i]['income'] = $balance['income'] != NULL ? $balance['income'] : 0;
            $fund_balance[$i]['expense'] = $balance['expense'] != NULL ? $balance['expense'] : 0;
            $fund_balance[$i]['closing'] = $balance['closing'] != NULL ? $balance['closing'] : 0;
            $i++;
        }

        // Fund Balance
        $result['fund_balance'] = $fund_balance;

        // Outstanding Cheques
        $result['outstanding_cheques'] = [];
        $openingOutstandingChequeReadBuilder->select(array('opening_outstanding_cheque_date as transaction_date', 'opening_outstanding_cheque_number as cheque_number'));
        $openingOutstandingChequeReadBuilder->select(array('opening_outstanding_cheque_description as description', 'opening_outstanding_cheque_amount as amount'));
        $openingOutstandingChequeReadBuilder->where(array('opening_outstanding_cheque.fk_system_opening_balance_id' => $system_opening_balance_id));
        $opening_outstanding_cheque_obj = $openingOutstandingChequeReadBuilder->get();

        if ($opening_outstanding_cheque_obj->getNumRows() > 0) {
            $result['outstanding_cheques'] = $opening_outstanding_cheque_obj->getResultArray();
        }

        // Deposit Transit
        $result['deposit_transit'] = [];

        $openingDepositTransitReadBuilder->select(array('opening_deposit_transit_date as transaction_date', 'opening_deposit_transit_description as description'));
        $openingDepositTransitReadBuilder->select(array('opening_deposit_transit_amount as amount'));
        $openingDepositTransitReadBuilder->where(array('opening_deposit_transit.fk_system_opening_balance_id' => $system_opening_balance_id));
        $opening_deposit_transit_obj = $openingDepositTransitReadBuilder->get();

        if ($opening_deposit_transit_obj->getNumRows() > 0) {
            $result['deposit_transit'] = $opening_deposit_transit_obj->getResultArray();
        }

        // Reconciliation data - Missing thus shall be computed for past deployments
        $result['reconciliation_statement'] = [];

        $openingBankBalanceReadBuilder->select(array('opening_bank_balance_statement_amount as statement_balance', 'opening_bank_balance_statement_date as statement_date'));
        $openingBankBalanceReadBuilder->where(array('opening_bank_balance.fk_system_opening_balance_id' => $system_opening_balance_id));
        $opening_bank_balance_obj = $openingBankBalanceReadBuilder->get();

        if ($opening_bank_balance_obj->getNumRows() > 0) {
            $opening_bank_balance = $opening_bank_balance_obj->getRow();
            $result['reconciliation_statement'] = [
                'statement_date' => $opening_bank_balance->statement_date,
                'statement_balance' => $opening_bank_balance->statement_balance
            ];
        }

        return $this->response->setJSON($result);
    }

    public function getProjectIncomeAccounts($project_id)
    {
        $projectIncomeAccountReadBuilder = $this->read_db->table('project_income_account');

        $income_accounts = [];
        $projectIncomeAccountReadBuilder->select(array('income_account_id', 'income_account_name'));
        $projectIncomeAccountReadBuilder->where(array('fk_project_id' => $project_id));
        $projectIncomeAccountReadBuilder->join('income_account', 'income_account.income_account_id=project_income_account.fk_income_account_id');
        $income_account_obj = $projectIncomeAccountReadBuilder->get();

        if ($income_account_obj->getNumRows() > 0) {
            $income_accounts_raw = $income_account_obj->getResultArray();

            $income_account_ids = array_column($income_accounts_raw, 'income_account_id');
            $income_account_names = array_column($income_accounts_raw, 'income_account_name');

            $income_accounts = array_combine($income_account_ids, $income_account_names);
        }

        return $this->response->setJSON($income_accounts);
    }


    private function upsertBankOpeningBalance($system_opening_balance_id, $office_bank_id, $book_bank_balance){

        // Get opening bank book balances
        $opening_bank_balance = [];
        $this->openingBankBalanceReadBuilder->where(array('fk_office_bank_id' => $office_bank_id));
        $opening_bank_balance_obj = $this->openingBankBalanceReadBuilder->get();
    
        if($opening_bank_balance_obj->getNumRows() > 0){
          $opening_bank_balance = $opening_bank_balance_obj->getRowArray();
        }
    
        // Insert Book Bank Balance
        if(empty($opening_bank_balance)){
          // Insert
          $track_number_and_name = $this->libs->generateItemTrackNumberAndName('opening_bank_balance');
    
          $opening_bank_balance_insert_data = [
            'fk_system_opening_balance_id' => $system_opening_balance_id,
            'opening_bank_balance_track_number' => $track_number_and_name['opening_bank_balance_track_number'],
            'opening_bank_balance_name' => $track_number_and_name['opening_bank_balance_name'],
            'opening_bank_balance_amount' => $book_bank_balance,
            'fk_office_bank_id' => $office_bank_id,
            'opening_bank_balance_created_date' => date('Y-m-d'),
            'opening_bank_balance_created_by' => $this->session->user_id,
            'opening_bank_balance_last_modified_by' => $this->session->user_id,
            'fk_status_id' => $this->statusLibrary->initialItemStatus('opening_bank_balance'),
            'fk_approval_id' => $this->approvalLibrary->insertApprovalRecord('opening_bank_balance')
          ];
    
          $this->openingBankBalanceWriteBuilder->insert($opening_bank_balance_insert_data);
        }else{
          // Update
          $opening_bank_balance_update_data = [
            'opening_bank_balance_amount' => $book_bank_balance,
            'opening_bank_balance_last_modified_date' => date('Y-m-d h:i:s'),
            'opening_bank_balance_last_modified_by' => $this->session->user_id,
            'fk_status_id' => $this->statusLibrary->initialItemStatus('opening_bank_balance'),
            'fk_approval_id' => $this->approvalLibrary->insertApprovalRecord('opening_bank_balance')
          ];
    
          $this->openingBankBalanceWriteBuilder->where(array('fk_office_bank_id' => $office_bank_id, 'opening_bank_balance_id' => $opening_bank_balance['opening_bank_balance_id']));
          $this->openingBankBalanceWriteBuilder->update($opening_bank_balance_update_data);
        }
  }


  private function upsertOpeningCashBalances($system_opening_balance_id, $office_bank_id, $cash_balances){

    $account_system_office_cash_ids = array_keys($this->getCashBoxesForOfficeBySystemOpeningBalanceId($system_opening_balance_id));

    if(!empty($cash_balances)){
      $opening_cash_balances = [];
      $this->openingCashBalanceReadBuilder->where(array('fk_system_opening_balance_id' => $system_opening_balance_id));
      $opening_cash_balance_obj = $this->openingCashBalanceReadBuilder->get();

      if($opening_cash_balance_obj->getNumRows() > 0){
        $opening_cash_balances = $opening_cash_balance_obj->getResultArray();
      }

      if(empty($opening_cash_balances)){
        // Insert
        $insert_opening_cash_balance_data = [];
        $i = 0;
        foreach($cash_balances as $office_cash_id => $cash_balance_amount){

          if($cash_balance_amount == 0) continue;

          $track_number_and_name = $this->libs->generateItemTrackNumberAndName('opening_cash_balance');

          $insert_opening_cash_balance_data[$i]['opening_cash_balance_track_number'] = $track_number_and_name['opening_cash_balance_track_number'];
          $insert_opening_cash_balance_data[$i]['opening_cash_balance_name'] = $track_number_and_name['opening_cash_balance_name'];
          $insert_opening_cash_balance_data[$i]['fk_system_opening_balance_id'] = $system_opening_balance_id;
          $insert_opening_cash_balance_data[$i]['fk_office_bank_id'] = $office_bank_id;
          $insert_opening_cash_balance_data[$i]['fk_office_cash_id'] = $office_cash_id;
          $insert_opening_cash_balance_data[$i]['opening_cash_balance_amount'] = $cash_balance_amount;

          $insert_opening_cash_balance_data[$i]['opening_cash_balance_created_date'] = date('Y-m-d');
          $insert_opening_cash_balance_data[$i]['opening_cash_balance_created_by'] = $this->session->user_id;
          $insert_opening_cash_balance_data[$i]['opening_cash_balance_last_modified_by'] = $this->session->user_id;
          $insert_opening_cash_balance_data[$i]['fk_approval_id'] = $this->statusLibrary->insertApprovalRecord('opening_cash_balance');
          $insert_opening_cash_balance_data[$i]['fk_status_id'] = $this->approvalLibrary->initialItemStatus('opening_cash_balance');

          $i++;
        }

        $this->openingCashBalanceWriteBuilder->insertBatch($insert_opening_cash_balance_data);

      }else{

        $recorded_office_cash_ids = array_column($opening_cash_balances,'fk_office_cash_id');
        $recorded_office_cash_amounts = array_column($opening_cash_balances,'opening_cash_balance_amount');

        $recorded_office_cash = array_combine($recorded_office_cash_ids, $recorded_office_cash_amounts);

        foreach($account_system_office_cash_ids as $office_cash_id){
            if(array_key_exists($office_cash_id, $recorded_office_cash)){
              if($recorded_office_cash[$office_cash_id] != $cash_balances[$office_cash_id]){ // Amount updated
                // Update change
                $update_opening_cash_balance_data['opening_cash_balance_amount'] = $cash_balances[$office_cash_id];
                $update_opening_cash_balance_data['opening_cash_balance_last_modified_by'] = $this->session->user_id;
                $update_opening_cash_balance_data['opening_cash_balance_last_modified_date'] = date('Y-m-d h:i:s');

                $this->openingCashBalanceWriteBuilder->where(['fk_office_cash_id' => $office_cash_id, 'fk_system_opening_balance_id' => $system_opening_balance_id]);
                $this->openingCashBalanceWriteBuilder->update($update_opening_cash_balance_data);
              }elseif($cash_balances[$office_cash_id] == 0){ // Amount set to zero
                // Delete record
                $this->openingCashBalanceWriteBuilder->where(['fk_office_cash_id' => $office_cash_id, 'fk_system_opening_balance_id' => $system_opening_balance_id]);
                $this->openingCashBalanceWriteBuilder->delete();
              }
            }else{
              $track_number_and_name = $this->libs->generateItemTrackNumberAndName('opening_cash_balance');

              $single_insert_opening_cash_balance_data['opening_cash_balance_track_number'] = $track_number_and_name['opening_cash_balance_track_number'];
              $single_insert_opening_cash_balance_data['opening_cash_balance_name'] = $track_number_and_name['opening_cash_balance_name'];
              $single_insert_opening_cash_balance_data['fk_system_opening_balance_id'] = $system_opening_balance_id;
              $single_insert_opening_cash_balance_data['fk_office_bank_id'] = $office_bank_id;
              $single_insert_opening_cash_balance_data['fk_office_cash_id'] = $office_cash_id;
              $single_insert_opening_cash_balance_data['opening_cash_balance_amount'] = $cash_balances[$office_cash_id];

              $single_insert_opening_cash_balance_data['opening_cash_balance_created_date'] = date('Y-m-d');
              $single_insert_opening_cash_balance_data['opening_cash_balance_created_by'] = $this->session->user_id;
              $single_insert_opening_cash_balance_data['opening_cash_balance_last_modified_by'] = $this->session->user_id;
              $single_insert_opening_cash_balance_data['fk_approval_id'] = $this->approvalLibrary->insertApprovalRecord('opening_cash_balance');
              $single_insert_opening_cash_balance_data['fk_status_id'] = $this->statusLibrary->initialItemStatus('opening_cash_balance');

              $this->openingCashBalanceWriteBuilder->insert($single_insert_opening_cash_balance_data);
            

            }
        }
      }
    }
  }

  private function upsertFundBalances(
    $system_opening_balance_id,
    $office_bank_id, 
    $project_ids,
    $income_account_ids, 
    $opening_amounts, 
    $income_amounts, 
    $expense_amounts
    )
  {
    $opening_fund_balances = [];
    $this->openingFundBalanceReadBuilder->where(array('fk_system_opening_balance_id' => $system_opening_balance_id));
    $opening_fund_balance_obj = $this->openingFundBalanceReadBuilder->get();

    if($opening_fund_balance_obj->getNumRows() > 0){
      $opening_fund_balances = $opening_fund_balance_obj->getResultArray();
    }

    $track_number_and_name = $this->libs->generateItemTrackNumberAndName('opening_fund_balance');

    for($i = 0; $i < sizeof($project_ids); $i++){
      $insert_fund_balance_batch[$i]['fk_system_opening_balance_id'] = $system_opening_balance_id;
      $insert_fund_balance_batch[$i]['opening_fund_balance_track_number'] = $track_number_and_name['opening_fund_balance_track_number'];
      $insert_fund_balance_batch[$i]['opening_fund_balance_name'] = $track_number_and_name['opening_fund_balance_name'];
      $insert_fund_balance_batch[$i]['fk_income_account_id'] = $income_account_ids[$i];
      $insert_fund_balance_batch[$i]['fk_office_bank_id'] = $office_bank_id;
      $insert_fund_balance_batch[$i]['fk_project_id'] = $project_ids[$i];
      $insert_fund_balance_batch[$i]['opening_fund_balance_opening'] = $opening_amounts[$i];
      $insert_fund_balance_batch[$i]['opening_fund_balance_income'] = $income_amounts[$i];
      $insert_fund_balance_batch[$i]['opening_fund_balance_expense'] = $expense_amounts[$i];
      $insert_fund_balance_batch[$i]['opening_fund_balance_amount'] = $opening_amounts[$i] + $income_amounts[$i] - $expense_amounts[$i];

      $insert_fund_balance_batch[$i]['opening_fund_balance_created_date'] = date('Y-m-d');
      $insert_fund_balance_batch[$i]['opening_fund_balance_created_by'] = $this->session->user_id;
      $insert_fund_balance_batch[$i]['fk_approval_id'] = $this->approvalLibrary->insertApprovalRecord('opening_fund_balance');
      $insert_fund_balance_batch[$i]['fk_status_id'] = $this->statusLibrary->initialItemStatus('opening_fund_balance');
    }

    if(!empty($opening_fund_balances)){
      $this->openingFundBalanceWriteBuilder->where(array('fk_system_opening_balance_id' => $system_opening_balance_id, 'fk_office_bank_id' => $office_bank_id));
      $this->openingFundBalanceWriteBuilder->delete();
    }
      $this->openingFundBalanceWriteBuilder->insertBatch($insert_fund_balance_batch);
  }

  private function upsertOpeningOutstandingCheque(
    $system_opening_balance_id, 
    $office_bank_id,
    $cheque_transaction_date,
    $cheque_number,
    $cheque_description,
    $cheque_amount
    )
  {
    $opening_outstanding_cheque = [];
    $this->outstandingChequeReadBuilder->where(array('fk_system_opening_balance_id' => $system_opening_balance_id));
    $opening_outstanding_cheque_obj = $this->outstandingChequeReadBuilder->get();

    if($opening_outstanding_cheque_obj->getNumRows() > 0){
      $opening_outstanding_cheque = $opening_outstanding_cheque_obj->getResultArray();
    }

    $track_number_and_name = $this->libs->generateItemTrackNumberAndName('opening_outstanding_cheque');

    for($i = 0; $i < sizeof($cheque_number);$i++){
      $insert_outstanding_cheque_batch[$i]['fk_system_opening_balance_id'] = $system_opening_balance_id;
      $insert_outstanding_cheque_batch[$i]['opening_outstanding_cheque_name'] = $track_number_and_name['opening_outstanding_cheque_name'];
      $insert_outstanding_cheque_batch[$i]['opening_outstanding_cheque_track_number'] = $track_number_and_name['opening_outstanding_cheque_track_number'];
      $insert_outstanding_cheque_batch[$i]['opening_outstanding_cheque_description'] = $cheque_description[$i];
      $insert_outstanding_cheque_batch[$i]['opening_outstanding_cheque_date'] = $cheque_transaction_date[$i];
      $insert_outstanding_cheque_batch[$i]['fk_office_bank_id'] = $office_bank_id;
      $insert_outstanding_cheque_batch[$i]['opening_outstanding_cheque_number'] = $cheque_number[$i];
      $insert_outstanding_cheque_batch[$i]['opening_outstanding_cheque_amount'] = $cheque_amount[$i];

      $insert_outstanding_cheque_batch[$i]['opening_outstanding_cheque_created_date'] = date('Y-m-d');
      $insert_outstanding_cheque_batch[$i]['opening_outstanding_cheque_created_by'] = $this->session->user_id;
      $insert_outstanding_cheque_batch[$i]['opening_outstanding_cheque_last_modified_by'] = $this->session->user_id;
      $insert_outstanding_cheque_batch[$i]['fk_approval_id'] = $this->approvalLibrary->insertApprovalRecord('opening_outstanding_cheque');
      $insert_outstanding_cheque_batch[$i]['fk_status_id'] = $this->statusLibrary->initialItemStatus('opening_outstanding_cheque');
    }

    if(!empty($opening_outstanding_cheque)){
      $this->outstandingChequeWriteBuilder->where(array('fk_system_opening_balance_id' => $system_opening_balance_id, 'fk_office_bank_id' => $office_bank_id));
      $this->outstandingChequeWriteBuilder->delete();
    }
      $this->outstandingChequeWriteBuilder->insertBatch($insert_outstanding_cheque_batch);
  }

  private function upsertOpeningDepositTransit(
    $system_opening_balance_id, 
    $office_bank_id,
    $deposit_transaction_date,
    $transaction_description,
    $transaction_amount
  ){
    $opening_deposit_transit = [];
    $this->openingDepositTransitReadBuilder->where(array('fk_system_opening_balance_id' => $system_opening_balance_id));
    $opening_deposit_transit_obj = $this->openingDepositTransitReadBuilder->get();

    if($opening_deposit_transit_obj->getNumRows() > 0){
      $opening_deposit_transit = $opening_deposit_transit_obj->getResultArray();
    }

    $track_number_and_name = $this->libs->generateItemTrackNumberAndName('opening_deposit_transit');

    for($i = 0; $i < sizeof($transaction_amount);$i++){
      $insert_deposit_transit_batch[$i]['fk_system_opening_balance_id'] = $system_opening_balance_id;
      $insert_deposit_transit_batch[$i]['opening_deposit_transit_name'] = $track_number_and_name['opening_deposit_transit_name'];
      $insert_deposit_transit_batch[$i]['opening_deposit_transit_track_number'] = $track_number_and_name['opening_deposit_transit_track_number'];
      $insert_deposit_transit_batch[$i]['opening_deposit_transit_description'] = $transaction_description[$i];
      $insert_deposit_transit_batch[$i]['opening_deposit_transit_date'] = $deposit_transaction_date[$i];
      $insert_deposit_transit_batch[$i]['fk_office_bank_id'] = $office_bank_id;
      $insert_deposit_transit_batch[$i]['opening_deposit_transit_amount'] = $transaction_amount[$i];

      $insert_deposit_transit_batch[$i]['opening_deposit_transit_created_date'] = date('Y-m-d');
      $insert_deposit_transit_batch[$i]['opening_deposit_transit_created_by'] = $this->session->user_id;
      $insert_deposit_transit_batch[$i]['opening_deposit_transit_last_modified_by'] = $this->session->user_id;
      $insert_deposit_transit_batch[$i]['fk_approval_id'] = $this->approvalLibrary->insertApprovalRecord('opening_deposit_transit');
      $insert_deposit_transit_batch[$i]['fk_status_id'] = $this->statusLibrary->initialItemStatus('opening_deposit_transit');
    }

    if(!empty($opening_deposit_transit)){
      $this->openingDepositTransitWriteBuilder->where(array('fk_system_opening_balance_id' => $system_opening_balance_id, 'fk_office_bank_id' => $office_bank_id));
      $this->openingDepositTransitWriteBuilder->delete();
    }
      $this->openingDepositTransitWriteBuilder->insertBatch($insert_deposit_transit_batch);
  }

  private function upsertReconciliationStatement(
    $system_opening_balance_id,
    $office_bank_id,
    $statement_date,
    $book_bank_balance,
    $statement_balance,
    $bank_reconciled_difference
  ){
    
    // Get opening bank book balances
    $opening_bank_balance = [];
    $this->openingBankBalanceReadBuilder->where(array('fk_office_bank_id' => $office_bank_id));
    $opening_bank_balance_obj = $this->openingBankBalanceReadBuilder->get();

    if($opening_bank_balance_obj->getNumRows() > 0){
      $opening_bank_balance = $opening_bank_balance_obj->getRowArray();
    }

    // Insert Book Bank Balance
    if(empty($opening_bank_balance)){
      // Insert
      $track_number_and_name = $this->libs->generateItemTrackNumberAndName('opening_bank_balance');

      $opening_bank_balance_insert_data = [
        'fk_system_opening_balance_id' => $system_opening_balance_id,
        'opening_bank_balance_track_number' => $track_number_and_name['opening_bank_balance_track_number'],
        'opening_bank_balance_name' => $track_number_and_name['opening_bank_balance_name'],
        'opening_bank_balance_amount' => $book_bank_balance,
        'opening_bank_balance_statement_amount' => $statement_balance,
        'opening_bank_balance_statement_date' => $statement_date,
        'opening_bank_balance_is_reconciled' => $bank_reconciled_difference != 0 ? 0 : 1,
        'fk_office_bank_id' => $office_bank_id,
        'opening_bank_balance_created_date' => date('Y-m-d'),
        'opening_bank_balance_created_by' => $this->session->user_id,
        'opening_bank_balance_last_modified_by' => $this->session->user_id,
        'fk_status_id' => $this->statusLibrary->initialItemStatus('opening_bank_balance'),
        'fk_approval_id' => $this->approvalLibrary->insertApprovalRecord('opening_bank_balance')
      ];

      $this->openingBankBalanceWriteBuilder->insert($opening_bank_balance_insert_data);
    }else{
        // Update
        $opening_bank_balance_update_data = [
        'opening_bank_balance_statement_amount' => $statement_balance,
        'opening_bank_balance_statement_date' => $statement_date,
        'opening_bank_balance_is_reconciled' => $bank_reconciled_difference != 0 ? 0 : 1,
        'opening_bank_balance_last_modified_date' => date('Y-m-d h:i:s'),
        'opening_bank_balance_last_modified_by' => $this->session->user_id,
        'fk_status_id' => $this->statusLibrary->initialItemStatus('opening_bank_balance'),
        'fk_approval_id' => $this->approvalLibrary->insertApprovalRecord('opening_bank_balance')
      ];

      $this->openingBankBalanceWriteBuilder->where(array('fk_office_bank_id' => $office_bank_id, 'opening_bank_balance_id' => $opening_bank_balance['opening_bank_balance_id']));
      $this->openingBankBalanceWriteBuilder->update($opening_bank_balance_update_data);
    }
  }

    function saveOpeningBalances($system_opening_balance_id){
        $post = $this->request->getPost();
        
        // Insert and Update Bank Opening Balance
        $this->upsertBankOpeningBalance($system_opening_balance_id, $post['office_bank_id'], $post['book_bank_balance']);
    
        // Insert and Update Cash Opening balances
        $this->upsertOpeningCashBalances($system_opening_balance_id, $post['office_bank_id'], $post['cash_balance']);
    
        // Insert or Update fund balances
        if(isset($post['income_account_ids'])){
          $this->upsertFundBalances(
            $system_opening_balance_id, 
            $post['office_bank_id'], 
            $post['project_ids'], 
            $post['income_account_ids'], 
            $post['opening_amounts'], 
            $post['income_amounts'], 
            $post['expense_amounts']
          );
        }
    
        // Insert and Update Opening Outstanding Cheques
        if(isset($post['cheque_transaction_date'])){
          $this->upsertOpeningOutstandingCheque(
            $system_opening_balance_id, 
            $post['office_bank_id'],
            $post['cheque_transaction_date'],
            $post['cheque_number'],
            $post['cheque_description'],
            $post['cheque_amount']
          );
        }
    
        // Insert and Update Opening Deposit in Transit
        if(isset($post['deposit_transaction_date'])){
          $this->upsertOpeningDepositTransit(
            $system_opening_balance_id, 
            $post['office_bank_id'],
            $post['deposit_transaction_date'],
            $post['transaction_description'],
            $post['transaction_amount']
          );
        }
    
        // Insert and Update reconciliation statement
        $this->upsertReconciliationStatement(
          $system_opening_balance_id,
          $post['office_bank_id'],
          $post['statement_date'],
          $post['book_bank_balance'],
          $post['statement_balance'],
          $post['bank_reconciled_difference']
        );
    
        // Upload a bank statement
        // log_message('error', json_encode($_FILES));
        $storeFolder = upload_url('system_opening_balance', $system_opening_balance_id);
        
        // log_message('error', json_encode($_FILES['file']['name'][0]));
        if(isset($_FILES) && $_FILES['file']['name'][0] != ""){
          if (
              is_array($this->attachmentLibrary->uploadFiles($storeFolder)) &&
              count($this->attachmentLibrary->uploadFiles($storeFolder)) > 0
            ) {
              $this->attachmentLibrary->uploadFiles($storeFolder);
          }
        }
    
        // Get uploaded files
        $bank_statements_uploads = [];
    
        $this->systemOpeningBalanceReadBuilder->where(['system_opening_balance_id' => $system_opening_balance_id]);
        $office_id = $this->systemOpeningBalanceReadBuilder->get()->getRow()->fk_office_id;
      
        $bank_statements_uploads = $this->openingBankStatements($office_id);
        $bank_statements_uploads = view('system_opening_balance/list_statements',['bank_statements_uploads' => $bank_statements_uploads]);
    
        // The value 1 has to be controlled instead of being hard coded
        return $this->response->setJSON(['success' => 1, 'bank_statements_uploads' => $bank_statements_uploads]);
      }
}
