<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\AssetCategoryModel;
class AssetCategoryLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $assetcategoryModel;

    function __construct()
    {
        parent::__construct();

        $this->assetcategoryModel = new AssetCategoryModel();

        $this->table = 'assetcategory';
    }


   
}