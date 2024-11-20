<?php 
namespace App\Controllers\Web\Grants;

use App\Controllers\Web\WebController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
class Budget extends WebController {
    function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

    }

    function checkOfficePeriodBudgetExists($office_id){
    
        $budgetLibrary = new \App\Libraries\Grants\BudgetLibrary();
        $budget = $budgetLibrary->getBudgetByOfficeCurrentTransactionDate($office_id);
            
        $check = false;
    
        if(count($budget) > 0){
          $check = true;
        }
    
        return $this->response->setJSON(compact('check'));
      }

 
}