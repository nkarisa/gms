<?php 

namespace App\Traits\System;

trait OutputTrait {
    protected function listOutput(){
        $listOutput = new \App\Libraries\System\Outputs\ListOutput();
        return $listOutput->getOutput();
    }
}