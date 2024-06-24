<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\CustomFinancialYearModel;

class CustomFinancialYearLibrary extends GrantsLibrary
{

    protected $table;
    protected $customFinancialYearModel;

    function __construct()
    {
        parent::__construct();

        $this->customFinancialYearModel = new CustomFinancialYearModel();

        $this->table = 'custom_financial_year';
    }
    
}