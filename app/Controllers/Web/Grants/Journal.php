<?php

namespace App\Controllers\Web\Grants;

use App\Controllers\Web\WebController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class Journal extends WebController
{

    protected $library;
    function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->library = new \App\Libraries\Grants\JournalLibrary();
    }

    function result($id = "", $parentId = null){
        $result = parent::result($id, $parentId);

        if($this->action == 'view'){
            $journal_id = hash_id($this->id,'decode');
            $office_data_from_journal = $this->library->getOfficeDataFromJournal($journal_id);
            $office_id = $office_data_from_journal->office_id;
            $transacting_month = $office_data_from_journal->journal_month;
            $account_system_id = $office_data_from_journal->fk_account_system_id;
      
            $status_data = $this->libs->actionButtonData('voucher', $account_system_id);
            $result['vouchers']=$this->library->getVouchersOfTheMonth($office_id,$transacting_month,$journal_id);
            $result['status_data'] = $status_data;
            $result['transacting_month']=$transacting_month;
  
          }

        return $result;
    }
}
