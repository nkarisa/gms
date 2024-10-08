<?php

namespace App\Libraries\Core;

use App\Libraries\System\GrantsLibrary;
use App\Models\Core\RoleGroupAssociationModel;
class RoleGroupAssociationLibrary extends GrantsLibrary
{

    protected $table;
    protected $coreModel;

    function __construct()
    {
        parent::__construct();

        $this->coreModel = new RoleGroupAssociationModel();

        $this->table = 'core';
    }


   
}