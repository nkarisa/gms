<?php

namespace App\Controllers\Web\Grants;

use App\Controllers\Web\WebController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class Voucher extends WebController
{
    protected $library;

    function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->library = new \App\Libraries\Grants\VoucherLibrary();
    }

    function result($id = "", $parentTable = null){
        $result = parent::result($id, $parentTable);
        
        $statusLibrary = new \App\Libraries\Core\StatusLibrary();
        $requestLibrary = new \App\Libraries\Grants\RequestLibrary();

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
}
