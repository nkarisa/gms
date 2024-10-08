<?php

namespace App\Libraries\Core;

use App\Libraries\System\GrantsLibrary;
use App\Models\Core\AccountSystemSettingModel;
class AccountSystemSettingLibrary extends GrantsLibrary
{

    protected $table;
    protected $coreModel;

    function __construct()
    {
        parent::__construct();

        $this->coreModel = new AccountSystemSettingModel();

        $this->table = 'core';
    }


   
}