<?php

namespace App\Libraries\Core;

use App\Libraries\System\GrantsLibrary;
use App\Models\Core\PermissionTemplateModel;
class PermissionTemplateLibrary extends GrantsLibrary
{

    protected $table;
    protected $coreModel;

    function __construct()
    {
        parent::__construct();

        $this->coreModel = new PermissionTemplateModel();

        $this->table = 'core';
    }


   
}