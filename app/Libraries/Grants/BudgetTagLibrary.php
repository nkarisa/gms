<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\BudgetTagModel;

class BudgetTagLibrary extends GrantsLibrary
{

    protected $table;
    protected $budgetTagModel;

    function __construct()
    {
        parent::__construct();

        $this->budgetTagModel = new BudgetTagModel();

        $this->table = 'office';
    }
    
}