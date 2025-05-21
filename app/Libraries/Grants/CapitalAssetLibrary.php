<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\CapitalAssetModel;
class CapitalAssetLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $capitalassetModel;

    function __construct()
    {
        parent::__construct();

        $this->capitalassetModel = new CapitalAssetModel();

        $this->table = 'capitalasset';
    }


   
}