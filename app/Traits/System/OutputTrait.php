<?php 

namespace App\Traits\System;

trait OutputTrait {
    protected function listOutput($module = ''){
        $listOutput = new \App\Libraries\System\Outputs\ListOutput($module);
        return $listOutput->getOutput();
    }

    protected function viewOutput($module){
        $listOutput = new \App\Libraries\System\Outputs\ViewOutput($module);
        return $listOutput->getOutput();
    }

    protected function editOutput($module){
        $listOutput = new \App\Libraries\System\Outputs\EditOutput($module);
        return $listOutput->getOutput();
    }

    protected function SingleFormAddOutput($module){
        $listOutput = new \App\Libraries\System\Outputs\SingleFormAddOutput($module);
        return $listOutput->getOutput();
    }


}