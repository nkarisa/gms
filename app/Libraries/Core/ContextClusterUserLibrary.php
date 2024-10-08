<?php

namespace App\Libraries\Core;

use App\Libraries\System\GrantsLibrary;
use App\Models\Core\ContextClusterUserModel;
class ContextClusterUserLibrary extends GrantsLibrary
{

    protected $table;
    protected $coreModel;

    function __construct()
    {
        parent::__construct();

        $this->coreModel = new ContextClusterUserModel();

        $this->table = 'core';
    }


   
}