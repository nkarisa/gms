<?php

namespace App\Controllers\Web\Core;

use App\Controllers\Web\WebController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class AccountSystem extends WebController
{

    function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

    }

    public function getValidReportingAccountSystems(): ResponseInterface {
        $post = $this->request->getPost();
        $account_system_level = (int)$post['account_system_level'];
        $accountSystemReadBuilder = $this->read_db->table('account_system');

        $accountSystemReadBuilder->select(['account_system_id','account_system_name']);
        $accountSystemReadBuilder->where(['account_system_level' => $account_system_level + 1, 'account_system_is_active' => 1]);
        $activeAccountSystemObj = $accountSystemReadBuilder->get();

        $activeReportingAccountSystems = [];
        if($activeAccountSystemObj->getNumRows() > 0){
            $activeReportingAccountSystems = $activeAccountSystemObj->getResultArray();
        }

        return $this->response->setJSON($activeReportingAccountSystems);
    }
}
