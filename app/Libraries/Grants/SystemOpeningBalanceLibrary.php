<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\SystemOpeningBalanceModel;
class SystemOpeningBalanceLibrary extends GrantsLibrary
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new SystemOpeningBalanceModel();

        $this->table = 'grants';
    }


   
}