<?php 

namespace App\Traits\System;

trait OutputTrait {
    protected function listOutput($module, ...$args){
        $listOutput = new \App\Libraries\System\Outputs\ListOutput($module);
        return $listOutput->getOutput($args);
    }

    protected function viewOutput($module, ...$args){
        $listOutput = new \App\Libraries\System\Outputs\ViewOutput($module);
        return $listOutput->getOutput($args[0]);
    }

    protected function editOutput($module, ...$args){
        $listOutput = new \App\Libraries\System\Outputs\EditOutput($module);
        return $listOutput->getOutput($args[0]);
    }

    protected function SingleFormAddOutput($module, ...$args){
        $listOutput = new \App\Libraries\System\Outputs\SingleFormAddOutput($module);
        return $listOutput->getOutput($args);
    }

    function currencyFields(){
        return [];
    }

    function showListEditAction(array $record): bool{
        return true;
    }

}