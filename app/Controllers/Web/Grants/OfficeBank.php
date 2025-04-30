<?php

namespace App\Controllers\Web\Grants;

use App\Controllers\Web\WebController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class OfficeBank extends WebController
{

    function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

    }

    public function validateOfficeBankAccount($office_bank_id){

        $has_account_balance = false;
        $has_active_cheque_book = false;

        $builder = $this->read_db->table('office_bank');
        $builder->where(['office_bank_id' => $office_bank_id]);
        $officeIdObj = $builder->get();

        if($officeIdObj->getNumRows() > 0){
          $office_id = $officeIdObj->getRow()->fk_office_id;
          $voucherLibrary = new \App\Libraries\Grants\VoucherLibrary();
          $officeBankLibrary = new \App\Libraries\Grants\OfficeBankLibrary();
  
          $reporting_month = date('Y-m-01',strtotime($voucherLibrary->getVoucherDate($office_id)));
          $account_balance = $officeBankLibrary->officeBankAccountBalance($office_bank_id, $reporting_month);
      
          $chequeBookLibrary = new \App\Libraries\Grants\ChequeBookLibrary();
          $leaves = $chequeBookLibrary->getRemainingUnusedChequeLeaves($office_bank_id);
          
          $has_account_balance = $account_balance != 0 ? true : false;
          $has_active_cheque_book = count($leaves) > 0 ? true : false;
        }
    
    
        $result = compact('has_account_balance','has_active_cheque_book');
    
        return $this->response->setJSON($result);
      }

      function incomeAccountRequiringAllocation($office_id){
        $office_bank_ids = [];
        $office_banks = [];
    
        $builder = $this->read_db->table('office_bank');
        $builder->select(array('office_bank_id','office_bank_name'));
        $builder->where(array('fk_office_id' => $office_id));
        $office_bank_ids_obj = $builder->get();
    
        if($office_bank_ids_obj->getNumRows() > 0){
          $office_bank_ids_raw = $office_bank_ids_obj->getResultArray();
    
          $office_bank_ids = array_column($office_bank_ids_raw, 'office_bank_id');
          $office_banks = $office_bank_ids_raw;
        }
    
        $incomeAccountLibrary = new \App\Libraries\Grants\IncomeAccountLibrary();
        $income_accounts = $incomeAccountLibrary->incomeAccountMissingProjectAllocation($office_id, $office_bank_ids);
    
        $accounts = [];
    
        if(count($income_accounts) > 0){
          $builder = $this->read_db->table("income_account");
          $builder->select(array('income_account_id','income_account_name'));
          $builder->whereIn('income_account_id', $income_accounts);
          $accounts = $builder->get()->getResultArray();
        }
        
    
        return $this->response->setJSON(['unallocated_income_account' => $accounts, 'existing_office_banks' => $office_banks]);
      }

      function countActiveOfficeBanks($office_id){
        $officeBankLibrary = new \App\Libraries\Grants\OfficeBankLibrary();
        $count  = $officeBankLibrary->getActiveOfficeBank($office_id);
        return $this->response->setJSON(compact('count'));
      }
}
