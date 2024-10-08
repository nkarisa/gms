<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\IncomeVoteHeadsCategoryModel;
class IncomeVoteHeadsCategoryLibrary extends GrantsLibrary
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new IncomeVoteHeadsCategoryModel();

        $this->table = 'grants';
    }


   
}