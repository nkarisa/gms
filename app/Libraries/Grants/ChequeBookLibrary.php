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
}