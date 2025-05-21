<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\AssetStatusModel;
class AssetStatusLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $assetstatusModel;

    function __construct()
    {
        parent::__construct();

        $this->assetstatusModel = new AssetStatusModel();

        $this->table = 'assetstatus';
    }


   
}