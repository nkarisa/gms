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

        $this->table = 'asset_category';
    }

    public function listTableVisibleColumns(): array {
        return [
            'asset_category_track_number',
            'asset_category_name',
            'asset_category_useful_years',
            'asset_category_depreciation_method',
            'account_system_name',
            'asset_category_created_date'
        ];
    }

    public function singleFormAddVisibleColumns(): array {
        return [
            'asset_category_name',
            'asset_category_useful_years',
            'asset_category_depreciation_method',
            'account_system_name'
        ];
    }

    public function editVisibleColumns(): array {
        return [
            'asset_category_name',
            // 'asset_category_useful_years',
            // 'asset_category_depreciation_method',
            'account_system_name'
        ];
    }

    public function changeFieldType(): array {
        $change_field_type['asset_category_depreciation_method']['field_type'] = 'select';
        $change_field_type['asset_category_depreciation_method']['options'] = [
            'straight' => 'straight',
            'declining' => 'declining',
            'sum_of_years_digits' => 'sum_of_years_digits'
        ];

        return $change_field_type;
    }

    public function getAssetUsefulLifeInYearsAndMethod($assetId){
        $assetCategoryBuilder = $this->write_db->table('asset_category');

        $assetCategoryBuilder->select(['asset_category_useful_years','asset_category_depreciation_method']);
        $assetCategoryBuilder->join('capital_asset','capital_asset.fk_asset_category_id=asset_category.asset_category_id');
        $assetCategoryBuilder->where(['capital_asset_id' => $assetId]);
        $assetCategoryObj = $assetCategoryBuilder->get();

        $useFulLifeInYearsAndMethod = [];

        if($assetCategoryObj->getNumRows() > 0){
          $useFulLifeInYearsAndMethod = $assetCategoryObj->getRowArray();
        }

        return $useFulLifeInYearsAndMethod;
    }

   
}