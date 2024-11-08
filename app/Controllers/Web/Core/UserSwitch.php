<?php

namespace App\Controllers\Web\Core;

use App\Controllers\Web\WebController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class UserSwitch extends WebController
{

    function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

    }


    public function result($id = "", $aprentTable = null)
    {
     
        $userSwitchLibary = new \App\Libraries\Core\UserSwitchLibrary();
        $users = $userSwitchLibary->getSwitchableUsers();
  
        return $users;
    }
}
