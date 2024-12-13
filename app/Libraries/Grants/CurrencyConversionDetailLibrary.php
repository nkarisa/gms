<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\CurrencyConversionDetailModel;
class CurrencyConversionDetailLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $grantsModel;

    function __construct()
    {
        parent::__construct();

        $this->grantsModel = new CurrencyConversionDetailModel();

        $this->table = 'grants';
    }


   
}