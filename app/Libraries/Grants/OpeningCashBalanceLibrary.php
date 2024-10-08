<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\OpeningCashBalanceModel;
class OpeningCashBalanceLibrary extends GrantsLibrary
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new OpeningCashBalanceModel();

        $this->table = 'grants';
    }


   
}