<?php

namespace App\Libraries\Core;

use App\Libraries\System\GrantsLibrary;
use App\Models\Core\PermissionModel;

class PermissionLibrary extends GrantsLibrary
{

    protected $table;
    protected $permissionModel;

    function __construct()
    {
        parent::__construct();

        $this->permissionModel = new PermissionModel();

        $this->table = 'permission';
    }

}