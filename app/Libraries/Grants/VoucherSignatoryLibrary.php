<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\VoucherSignatoryModel;
class VoucherSignatoryLibrary extends GrantsLibrary
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new VoucherSignatoryModel();

        $this->table = 'grants';
    }


   
}