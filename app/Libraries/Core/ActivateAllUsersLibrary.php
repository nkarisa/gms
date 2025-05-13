<?php

namespace App\Libraries\Core;

use App\Libraries\System\GrantsLibrary;
use App\Models\Core\ActivateAllUsersModel;
class ActivateAllUsersLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $activateallusersModel;

    function __construct()
    {
        parent::__construct();

        $this->activateallusersModel = new ActivateAllUsersModel();

        $this->table = 'activateallusers';
    }


   
}