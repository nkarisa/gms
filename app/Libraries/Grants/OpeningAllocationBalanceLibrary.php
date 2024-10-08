<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\OpeningAllocationBalanceModel;
class OpeningAllocationBalanceLibrary extends GrantsLibrary
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new OpeningAllocationBalanceModel();

        $this->table = 'grants';
    }


   
}