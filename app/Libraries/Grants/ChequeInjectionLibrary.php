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


   
}