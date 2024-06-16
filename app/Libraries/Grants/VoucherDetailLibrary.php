<?php 

namespace App\Libraries\Grants;

use App\Libraries\Core\GrantsLibrary;
use App\Models\Grants\VoucherDetailModel;

class VoucherDetailLibrary extends GrantsLibrary {
    protected $table;
    protected $voucherDetailModel;

    function __construct()
    {
        parent::__construct();

        $this->voucherDetailModel = new VoucherDetailModel();

        $this->table = 'voucher_detail';
    }
}