<?php

namespace App\Controllers\Web\Grants;

use App\Controllers\Web\WebController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Libraries\Grants;
use Config\App;

class Office extends WebController
{

    protected $officeLib;
    
    //protected $libStatus;

    // function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    // {
    //     parent::initController($request, $response, $logger);

    //     $this->officeLib = new Grants\Office;

    //     $this->libStatus = new  \App\Libraries\Core\StatusLibrary();
    // }


    
}
