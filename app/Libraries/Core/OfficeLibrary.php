<?php

namespace App\Libraries\Core;

use App\Libraries\System\GrantsLibrary;
use App\Models\Core\OfficeModel;

class OfficeLibrary extends GrantsLibrary
{

    protected $table;
    protected $officeModel;

    function __construct()
    {
        parent::__construct();

        $this->officeModel = new OfficeModel();

        $this->table = 'office';
    }

}