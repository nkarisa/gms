<?php 
namespace App\Libraries\System\Outputs;

class MultiFormAddOutput extends OutputTemplate{
    function __construct($module){
        parent::__construct($module);
    }

    public function getOutput($args): array|\CodeIgniter\HTTP\Response {
        $response = [];

        return $response;
    }
}