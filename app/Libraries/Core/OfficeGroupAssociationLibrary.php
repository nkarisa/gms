<?php

namespace App\Libraries\Core;

use App\Libraries\System\GrantsLibrary;
use App\Models\Core\OfficeGroupAssociationModel;
class OfficeGroupAssociationLibrary extends GrantsLibrary
{

    protected $table;
    protected $coreModel;

    function __construct()
    {
        parent::__construct();

        $this->coreModel = new OfficeGroupAssociationModel();

        $this->table = 'core';
    }


   
}