<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\FinancialReviewDashboardModel;
class FinancialReviewDashboardLibrary extends GrantsLibrary
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new FinancialReviewDashboardModel();

        $this->table = 'grants';
    }


   
}