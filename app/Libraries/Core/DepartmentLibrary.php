<?php

namespace App\Libraries\Core;

use App\Libraries\System\GrantsLibrary;
use App\Models\Core\DepartmentModel;
class DepartmentLibrary extends GrantsLibrary
{

    protected $table;
    protected $coreModel;

    function __construct()
    {
        parent::__construct();

        $this->coreModel = new DepartmentModel();

        $this->table = 'core';
    }


   
}