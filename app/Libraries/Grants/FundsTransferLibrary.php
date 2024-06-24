<?php 

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\FundsTransferModel;

class FundsTransferLibrary extends GrantsLibrary {
    protected $table;
    protected $fundsTransferModel;

    function __construct()
    {
        parent::__construct();

        $this->fundsTransferModel = new FundsTransferModel();

        $this->table = 'funds_transfer';
    }
}