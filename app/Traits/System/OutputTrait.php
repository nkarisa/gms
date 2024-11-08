<?php 

namespace App\Traits\System;

use \App\Libraries\System\Outputs;

trait OutputTrait {
    protected function listOutput($module, ...$args){
        $listOutput = new Outputs\ListOutput($module);
        return $listOutput->getOutput($args);
    }

    protected function viewOutput($module, ...$args){
        $listOutput = new Outputs\ViewOutput($module);
        return $listOutput->getOutput($args[0]);
    }

    protected function editOutput($module, ...$args){
        $listOutput = new Outputs\EditOutput($module);
        return $listOutput->getOutput($args[0]);
    }

    protected function SingleFormAddOutput($module, ...$args){
        $listOutput = new Outputs\SingleFormAddOutput($module);
        return $listOutput->getOutput($args);
    }

}