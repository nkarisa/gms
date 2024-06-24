<?php 

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\BudgetModel;

class BudgetLibrary extends GrantsLibrary {
    protected $table;
    protected $budgetModel;

    function __construct()
    {
        parent::__construct();

        $this->budgetModel = new BudgetModel();

        $this->table = 'budget';
    }
}