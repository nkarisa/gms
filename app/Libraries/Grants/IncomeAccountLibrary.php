<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\IncomeAccountModel;
class IncomeAccountLibrary extends GrantsLibrary
{

    protected $table;
    protected $incomeAccountModel;

    function __construct()
    {
        parent::__construct();

        $this->incomeAccountModel = new IncomeAccountModel();

        $this->table = 'income_account';
    }

    function detailTables(): array {
        return ['expense_account'];
    }
   
}