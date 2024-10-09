<?php 

namespace App\Libraries\System\Outputs;

class SingleFormAddOutput extends OutputTemplate{
    function __construct($module){
        parent::__construct($module);
    }

    function getOutput(): array {
        return [];
    }
}