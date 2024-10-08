<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\HealthFacilityModel;
class HealthFacilityLibrary extends GrantsLibrary
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new HealthFacilityModel();

        $this->table = 'grants';
    }


   
}