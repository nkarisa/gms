<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\ReimbursementClaimModel;
class ReimbursementClaimLibrary extends GrantsLibrary
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new ReimbursementClaimModel();

        $this->table = 'grants';
    }


   
}