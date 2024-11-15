<?php 

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\ChequeBookModel;

class ChequeBookLibrary extends GrantsLibrary {
    protected $table;
    protected $chequeBookModel;

    function __construct()
    {
        parent::__construct();

        $this->chequeBookModel = new ChequeBookModel();

        $this->table = 'cheque_book';
    }

    function allowSkippingOfChequeLeaves()
    {
        $is_skipping_of_cheque_leaves_allowed = true;
        if (service("settings")->get("GrantsConfig.allow_skipping_of_cheque_leaves") == false) {
            $is_skipping_of_cheque_leaves_allowed = false;
        }
        return $is_skipping_of_cheque_leaves_allowed;
    }
}