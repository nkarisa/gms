<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\ReimbursementAppTypeModel;
class ReimbursementAppTypeLibrary extends GrantsLibrary
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new ReimbursementAppTypeModel();

        $this->table = 'grants';
    }


   
}