<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\DashboardModel;
class DashboardLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
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