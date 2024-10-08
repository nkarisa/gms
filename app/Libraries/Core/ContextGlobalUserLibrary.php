<?php

namespace App\Libraries\Core;

use App\Libraries\System\GrantsLibrary;
use App\Models\Core\ContextGlobalUserModel;
class ContextGlobalUserLibrary extends GrantsLibrary
{

    protected $table;
    protected $coreModel;

    function __construct()
    {
        parent::__construct();

        $this->coreModel = new ContextGlobalUserModel();

        $this->table = 'core';
    }


   
}