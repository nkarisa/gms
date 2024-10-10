<?php 

namespace App\Controllers\Api;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
class User extends ApiController{

    function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

    }
    public function index()
    {
        $users = [
            [
                'id' => 1,
                'name' => 'John Doe',
                'email' => 'john.doe@example.com'
            ],
            [
                'id' => 2,
                'name' => 'Jane Smith',
                'email' => 'jane.smith@example.com'   
            ]
        ];

        return $this->response->setJSON($users);
    }
}