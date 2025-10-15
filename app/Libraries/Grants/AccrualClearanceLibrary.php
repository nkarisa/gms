<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\AccrualClearanceModel;
class AccrualClearanceLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $accrualclearanceModel;

    function __construct()
    {
        parent::__construct();

        $this->accrualclearanceModel = new AccrualClearanceModel();

        $this->table = 'accrualclearance';
    }


   
}