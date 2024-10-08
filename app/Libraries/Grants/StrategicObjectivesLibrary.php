<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\StrategicObjectivesModel;
class StrategicObjectivesLibrary extends GrantsLibrary
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new StrategicObjectivesModel();

        $this->table = 'grants';
    }


   
}