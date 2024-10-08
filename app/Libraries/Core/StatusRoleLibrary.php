<?php

namespace App\Libraries\Core;

use App\Libraries\System\GrantsLibrary;
use App\Models\Core\StatusRoleModel;
class StatusRoleLibrary extends GrantsLibrary
{

    protected $table;
    protected $coreModel;

    function __construct()
    {
        parent::__construct();

        $this->coreModel = new StatusRoleModel();

        $this->table = 'core';
    }


   
}