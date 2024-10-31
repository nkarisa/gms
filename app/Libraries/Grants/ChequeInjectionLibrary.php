<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\ChequeInjectionModel;
class ChequeInjectionLibrary extends GrantsLibrary
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new ChequeInjectionModel();

        $this->table = 'grants';
    }


    public function showListEditAction(array $record): bool {
        if(!isset($record['cheque_injection_is_active']) || $record['cheque_injection_is_active'] == 0){
            return false;
        }
        return true;
    }
}