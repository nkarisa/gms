<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\VoucherTypeEffectModel;
class VoucherTypeEffectLibrary extends GrantsLibrary
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new VoucherTypeEffectModel();

        $this->table = 'grants';
    }


   
}