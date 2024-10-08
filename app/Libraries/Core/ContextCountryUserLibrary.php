<?php

namespace App\Libraries\Core;

use App\Libraries\System\GrantsLibrary;
use App\Models\Core\ContextCountryUserModel;
class ContextCountryUserLibrary extends GrantsLibrary
{

    protected $table;
    protected $coreModel;

    function __construct()
    {
        parent::__construct();

        $this->coreModel = new ContextCountryUserModel();

        $this->table = 'core';
    }


   
}