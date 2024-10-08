<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\CashRecipientAccountModel;
class CashRecipientAccountLibrary extends GrantsLibrary
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new CashRecipientAccountModel();

        $this->table = 'grants';
    }


   
}