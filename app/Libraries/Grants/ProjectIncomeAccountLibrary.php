<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\ProjectIncomeAccountModel;
class ProjectIncomeAccountLibrary extends GrantsLibrary
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new ProjectIncomeAccountModel();

        $this->table = 'grants';
    }


   
}