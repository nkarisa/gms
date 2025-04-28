<?php

namespace App\Controllers\Web\Grants;

use App\Controllers\Web\WebController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class ProjectAllocation extends WebController
{

    function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

    }

    protected function result($id = null, $parentTable = null)
    {
        $result = parent::result($id, $parentTable);
        $projectLibrary = new \App\Libraries\Grants\ProjectLibrary();

        if($this->action == 'edit'){
            $project = $projectLibrary->getProjectByProjectAllocationId(hash_id($this->id, 'decode'));
            $result['project_end_date'] = isset($project['project_end_date']) ? date('Y-m-d', strtotime('+1 days', strtotime($project['project_end_date']))) : date('Y-m-d');
        }

        return $result;
    }   
}
