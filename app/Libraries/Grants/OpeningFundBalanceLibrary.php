<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\OpeningFundBalanceModel;
class OpeningFundBalanceLibrary extends GrantsLibrary
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new OpeningFundBalanceModel();

        $this->table = 'grants';
    }


   
}