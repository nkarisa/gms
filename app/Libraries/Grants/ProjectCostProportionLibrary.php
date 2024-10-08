<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\ProjectCostProportionModel;
class ProjectCostProportionLibrary extends GrantsLibrary
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new ProjectCostProportionModel();

        $this->table = 'grants';
    }


   
}