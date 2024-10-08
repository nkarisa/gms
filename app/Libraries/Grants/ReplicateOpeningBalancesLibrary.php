<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\ReplicateOpeningBalancesModel;
class ReplicateOpeningBalancesLibrary extends GrantsLibrary
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new ReplicateOpeningBalancesModel();

        $this->table = 'grants';
    }


   
}