<?php 

namespace App\Interfaces;

use CodeIgniter\HTTP\RequestInterface;

interface HandlerInterface {
    public function create(RequestInterface $requestBody);
    public function readOne(int $id);
    public function readMany(array $params);
    public function update(RequestInterface $requestBody, $id);
    public function delete(int $id);
}