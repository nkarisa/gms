<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\OpeningAccrualBalanceModel;
class OpeningAccrualBalanceLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $openingaccrualbalanceModel;

    function __construct()
    {
        parent::__construct();

        $this->openingaccrualbalanceModel = new OpeningAccrualBalanceModel();

        $this->table = 'openingaccrualbalance';
    }


   
}