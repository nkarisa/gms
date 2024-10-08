<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\WorkplanTaskModel;
class WorkplanTaskLibrary extends GrantsLibrary
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new WorkplanTaskModel();

        $this->table = 'grants';
    }


   
}