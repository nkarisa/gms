<?php

namespace App\Libraries\Core;

use App\Libraries\System\GrantsLibrary;
use App\Models\Core\DashboardModel;
class DashboardLibrary extends GrantsLibrary
{

    protected $table;
    protected $dashboardModel;

    function __construct()
    {
        parent::__construct();

        $this->dashboardModel = new DashboardModel();

        $this->table = 'dashboard';
    }


   
}