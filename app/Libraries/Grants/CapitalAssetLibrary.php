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

        $this->table = 'capital_asset';
    }

    function detailTables(): array {
        return [
            'asset_depreciation'
        ];
    }

    function singleFormAddVisibleColumns(): array {
        return [
            'capital_asset_name',
            'capital_asset_description',
            'office_name',
            'capital_asset_purchase_date',
            'capital_asset_cost'
        ];
    }
}