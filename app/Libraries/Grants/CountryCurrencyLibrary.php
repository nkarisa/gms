<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\CountryCurrencyModel;
class CountryCurrencyLibrary extends GrantsLibrary
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new CountryCurrencyModel();

        $this->table = 'grants';
    }


   
}