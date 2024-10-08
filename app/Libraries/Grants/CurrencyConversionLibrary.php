<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\CurrencyConversionModel;
class CurrencyConversionLibrary extends GrantsLibrary
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new CurrencyConversionModel();

        $this->table = 'grants';
    }


   
}