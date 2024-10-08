<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\OpeningBankBalanceModel;
class OpeningBankBalanceLibrary extends GrantsLibrary
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new OpeningBankBalanceModel();

        $this->table = 'grants';
    }


   
}