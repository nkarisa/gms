<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\OpeningOutstandingChequeModel;
class OpeningOutstandingChequeLibrary extends GrantsLibrary
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new OpeningOutstandingChequeModel();

        $this->table = 'grants';
    }


   
}