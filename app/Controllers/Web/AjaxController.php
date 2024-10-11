<?php

namespace App\Controllers\Web;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\HTTP\RequestInterface;
use Psr\Log\LoggerInterface;

class AjaxController extends WebController
{

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);
    }

    public function index()
    {
        // Post keys should be: controller, method and data
        // All ajax request will be received here and passed to library of a controller
        // All ajax responses MUST have a message key with either success or failure
        $vars = $this->request->getPost();
        extract($vars);
        
        $controllerLibrary = $this->libs->loadLibrary($controller);
        $response = $controllerLibrary->{$method}($data);

        // If the method returns a response, add a message key with either success or failure
        if (isset($response['message'])) {
            return $this->response->setJSON($response);
        } else {
            $response['message'] = 'An error occurred';
            return $this->response->setJSON($response);
        }
    }
}
