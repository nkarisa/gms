<?php 

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\VoucherModel;

class VoucherLibrary extends GrantsLibrary {
    protected $table;
    protected $voucherModel;

    function __construct()
    {
        parent::__construct();

        $this->voucherModel = new VoucherModel();

        $this->table = 'voucher';
    }
}