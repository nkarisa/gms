<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\ProjectRequestTypeModel;
class ProjectRequestTypeLibrary extends GrantsLibrary
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new ProjectRequestTypeModel();

        $this->table = 'grants';
    }


   
}