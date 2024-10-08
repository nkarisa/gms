<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\FundBalanceSummaryReportModel;
class FundBalanceSummaryReportLibrary extends GrantsLibrary
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new FundBalanceSummaryReportModel();

        $this->table = 'grants';
    }


   
}