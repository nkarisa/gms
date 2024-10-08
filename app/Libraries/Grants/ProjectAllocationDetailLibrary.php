<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\ProjectAllocationDetailModel;
class ProjectAllocationDetailLibrary extends GrantsLibrary
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new ProjectAllocationDetailModel();

        $this->table = 'grants';
    }


   
}