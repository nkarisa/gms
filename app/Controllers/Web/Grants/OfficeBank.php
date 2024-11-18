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
        $builder = $this->read_db->table('office_bank');
        $builder->where(['office_bank_id' => $office_bank_id]);
        $office_id = $builder->get()->getRow()->fk_office_id;
    
        $voucherLibrary = new \App\Libraries\Grants\VoucherLibrary();
        $officeBankLibrary = new \App\Libraries\Grants\OfficeBankLibrary();

        $reporting_month = date('Y-m-01',strtotime($voucherLibrary->getVoucherDate($office_id)));
        $account_balance = $officeBankLibrary->officeBankAccountBalance($office_bank_id, $reporting_month);
    
        $chequeBookLibrary = new \App\Libraries\Grants\ChequeBookLibrary();
        $leaves = $chequeBookLibrary->getRemainingUnusedChequeLeaves($office_bank_id);
        
        $has_account_balance = $account_balance != 0 ? true : false;
        $has_active_cheque_book = count($leaves) > 0 ? true : false;
    
        $result = compact('has_account_balance','has_active_cheque_book');
    
        return $this->response->setJSON($result);
      }
}
