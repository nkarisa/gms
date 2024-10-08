<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\VoucherTypeAccountModel;
class VoucherTypeAccountLibrary extends GrantsLibrary
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new VoucherTypeAccountModel();

        $this->table = 'grants';
    }


   
}