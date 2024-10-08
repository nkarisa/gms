<?php 

namespace App\Handlers\Core;

use App\Interfaces\HandlerInterface;
use CodeIgniter\HTTP\RequestInterface;
class UserHandler implements HandlerInterface{

    protected $request;

    public function __construct(RequestInterface $request)
    {
        $this->request = $request;
    }

    public function create(RequestInterface $requestBody){}
    public function readOne(int $id){}
    public function readMany(array $params){}
    public function update(RequestInterface $requestBody, $id){}
    public function delete(int $id){}
}