<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\UserAccountActivationModel;
class UserAccountActivationLibrary extends GrantsLibrary
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new UserAccountActivationModel();

        $this->table = 'grants';
    }


   
}