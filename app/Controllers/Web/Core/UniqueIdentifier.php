<?php

namespace App\Controllers\Web\Core;

use App\Controllers\Web\WebController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class UniqueIdentifier extends WebController
{

    function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

    }

    function getOfficeAllowedUniqueIdentifierByAjax(){
        $post = $this->request->getPost();
    
        $context_definition_id = $post['context_definition_id'];
        $context_office_id = $post['context_office_id'];
    
        $uniqueIdentifierLibrary = new \App\Libraries\Core\UniqueIdentifierLibrary();
        $active_unique_identifier = $uniqueIdentifierLibrary->getOfficeContextAllowedUniqueIdentifier($context_definition_id, $context_office_id);
    
        return $this->response->setJSON($active_unique_identifier);
      }
}
