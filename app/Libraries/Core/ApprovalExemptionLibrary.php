<?php

namespace App\Libraries\Core;

use App\Libraries\System\GrantsLibrary;
use App\Models\Core\ApprovalExemptionModel;
class ApprovalExemptionLibrary extends GrantsLibrary
{

    protected $table;
    protected $coreModel;

    function __construct()
    {
        parent::__construct();

        $this->coreModel = new ApprovalExemptionModel();

        $this->table = 'core';
    }


   
}