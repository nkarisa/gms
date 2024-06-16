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

class Dashboard extends BaseController
{

    function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

    }
  
    public function index(){
        // $accountSystem = ['account_system_id' => 3, 'account_system_name' => 'Kenya'];
        // $approvalFlowLibrary = new ApprovalFlowLibrary();
        // $approvalFlowLibrary->insertApprovalFlow($accountSystem, 1, 'account_system', 1);
        // log_message('error', json_encode($approvalFlowLibrary->mandatoryFields('abc')));
        // log_message('error', $approvalFlowLibrary->hasDependantTable('voucher'));

        // $menuLibrary = new MenuLibrary();
        // log_message('error',json_encode($menuLibrary->getUserMenuItems()));

        // $grantsLibrary = new GrantsLibrary();
        // $grantsLibrary->createResourceUploadDirectoryStructure();

        // $statusLibrary = new StatusLibrary();
        // $statusLibrary->insertStatusForApproveableItem('abc');

        $data['controller'] = 'dashboard';
        $data['action'] = 'list';
        return view('general/index', $data);
    }
}
