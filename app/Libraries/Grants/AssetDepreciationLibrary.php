<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\AssetDepreciationModel;
class AssetDepreciationLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $assetdepreciationModel;

    function __construct()
    {
        parent::__construct();

        $this->assetdepreciationModel = new AssetDepreciationModel();

        $this->table = 'asset_depreciation';
    }

    function singleFormAddVisibleColumns(): array
    {
        return [
            'capital_asset_name',
            'asset_depreciation_cost',
            'asset_depreciation_month'
        ];
    }
   
}