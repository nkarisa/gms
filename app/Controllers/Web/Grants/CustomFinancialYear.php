<?php

namespace App\Controllers\Web\Grants;

use App\Controllers\Web\WebController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Libraries\Grants;


class CustomFinancialYear extends WebController
{

    function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->library = new Grants\CustomFinancialYearLibrary();

    }

    function index(){}

    function result($id = "",  $parentTable = null){

        $result = parent::result($id, $parentTable);
    
        if($this->action == 'list'){
          $columns = alias_columns($this->library->listTableVisibleColumns());
          array_shift($columns);
          $result['columns'] = array_column($columns,'list_columns');
          $result['has_details_table'] = false; 
          $result['has_details_listing'] = false;
          $result['is_multi_row'] = false;
          $result['show_add_button'] = true;
        }
    
        return $result;
      }

      

}
