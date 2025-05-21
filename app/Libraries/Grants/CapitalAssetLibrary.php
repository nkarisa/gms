<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\CapitalAssetModel;
class CapitalAssetLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $capitalassetModel;
    public $lookup_tables_with_null_values = ['voucher'];

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
            'capital_asset_serial',
            'asset_category_name',
            'office_name',
            'capital_asset_purchase_date',
            'asset_status_name',
            'capital_asset_end_term_date',
            'capital_asset_cost',
            'capital_asset_total_depreciation',
            'capital_asset_location',
            
        ];
    }

    function listTableVisibleColumns(): array {
        return [
            'capital_asset_track_number',
            'capital_asset_name',
            'capital_asset_serial',
            'office_name',
            'asset_category_name',
            'capital_asset_purchase_date',
            'capital_asset_cost',
            'capital_asset_total_depreciation',
            'asset_status_name'
        ];
    }
}