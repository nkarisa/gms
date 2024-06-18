<?php

namespace App\Controllers\Core;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

use App\Libraries\Core\UserLibrary;
use App\Libraries\Core\ApprovalFlowLibrary;
use App\Libraries\Core\MenuLibrary;
use App\Libraries\Core\GrantsLibrary;
use App\Libraries\Core\StatusLibrary;
use App\Libraries\Core\ApproveItemLibrary;

class Dashboard extends BaseController
{

    function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

    }
  
    public function index(){

        // log_message('error', json_encode($this->session->hierarchy_offices));

        $data['controller'] = 'dashboard';
        $data['action'] = 'list';
        return view('general/index', $data);
    }
}
