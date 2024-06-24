<?php 

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\FinancialReportModel;

class FinancialReportLibrary extends GrantsLibrary {
    protected $table;
    protected $financialReportModel;

    function __construct()
    {
        parent::__construct();

        $this->financialReportModel = new FinancialReportModel();

        $this->table = 'financial_report';
    }
}