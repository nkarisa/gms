<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\CapitalAssetModel;
use App\Libraries\System\Types\PostData;
class CapitalAssetLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $capitalassetModel;
    public $lookup_tables_with_null_values = ['voucher'];

    public function __construct()
    {
        parent::__construct();

        $this->capitalassetModel = new CapitalAssetModel();

        $this->table = 'capital_asset';
    }

    public function detailTables(): array {
        return ['asset_depreciation'];
    }

    public function singleFormAddVisibleColumns(): array {
        return [
            'office_name',
            'capital_asset_name',
            'capital_asset_description',
            // 'voucher_name',
            'capital_asset_cost',
            'capital_asset_residual_value',
            'asset_category_name',
            'capital_asset_serial',
            'asset_state_name',
            'capital_asset_purchase_date',
        ];
    }

    public function editVisibleColumns(): array {
        return [
            'office_name',
            'capital_asset_name',
            'capital_asset_description',
            'voucher_name',
            'capital_asset_serial',
            'asset_state_name',
        ];
    }

    public function listTableVisibleColumns(): array {
        return [
            'capital_asset_track_number',
            'capital_asset_name',
            'capital_asset_serial',
            'office_name',
            'asset_category_name',
            'capital_asset_purchase_date',
            'capital_asset_cost',
            'capital_asset_residual_value',
            'asset_state_name' // A PHP fatal error occurs when showing this column on capital asset list page
        ];
    }

    public function lookupValues(): array {
        $lookUpValues = parent::lookupValues();

        if(!$this->session->system_admin){
            $officeIds = array_column($this->session->hierarchy_offices,'office_id');
            $officeBuilder = $this->read_db->table('office');

            $officeBuilder->select(['office_id','office_name']);
            $officeBuilder->where(['office.fk_context_definition_id' => 1]);
            $officeBuilder->whereIn('office_id', $officeIds);
            $officeObj = $officeBuilder->get();

            if($officeObj->getNumRows() > 0){
                $lookUpValues['office'] = $officeObj->getResultArray();
            }

            // $assetStatusBuilder = $this->read_db->table('asset_state');
            // $lookUpValues['asset_state'] = $assetStatusBuilder->get()->getResultArray();

            // Get FCP vouchers for expense vouchers with expense_vote_heads_category_code as asset_acquisition
            // And not yet entered in the inventory. Ssystem assumes each voucher will be used to record as single asset purchased

            $lookUpValues['voucher'] = $this->getCapitalAssetPurchasedButNotRegisteredAsInventory($officeIds);
            
        }

        return $lookUpValues;
    }

    function getCapitalAssetPurchasedButNotRegisteredAsInventory($officeIds){
        $builder = $this->read_db->table('voucher_detail');

        $assignedAssets = $this->getAssignedVoucherIds();

        $builder->select('voucher_id, voucher_number as voucher_name');
        $builder->join('voucher', 'voucher.voucher_id = voucher_detail.fk_voucher_id', 'LEFT');
        $builder->join('expense_account', 'expense_account.expense_account_id = voucher_detail.fk_expense_account_id', 'LEFT');
        $builder->join('expense_vote_heads_category', 'expense_vote_heads_category.expense_vote_heads_category_id = expense_account.fk_expense_vote_heads_category_id', 'LEFT');
        $builder->where('expense_vote_heads_category_code', 'asset_acquisition');
        $builder->whereIn('voucher.fk_office_id', $officeIds);

        if(count($assignedAssets) > 0){
            $builder->whereNotIn('voucher_id', $this->getAssignedVoucherIds());
        }

        $builder->orderBy('voucher_id DESC');
        $query = $builder->get();
        return $query->getResultArray();
    }    
    function getAssignedVoucherIds() {
        $builder = $this->read_db->table('capital_asset');
        $builder->select('fk_voucher_id');
        $builder->where('fk_voucher_id IS NOT NULL');
        $query = $builder->get();
        return array_column($query->getResultArray(), 'fk_voucher_id');
    }

    function actionAfterInsert(array $post_array, int|null $approval_id, int $header_id): bool {
        $assetDepreciationLibrary = new \App\Libraries\Grants\AssetDepreciationLibrary();
        $assetCategoryLibrary = new \App\Libraries\Grants\AssetCategoryLibrary();
        
        $assetOfficeId = $post_array['fk_office_id'];
        $assetId = $header_id; 
        $costOfAsset = $post_array['capital_asset_cost']; 
        $salvageValue = $post_array['capital_asset_residual_value'];
        $usefulLifeInYears = $assetCategoryLibrary->getAssetUsefulLifeInYearsAndMethod($header_id)['asset_category_useful_years'] ?? 1; 
        $purchasedDate = $post_array['capital_asset_purchase_date'];

        // Check the Depreciation method appropriate to create a schedule in the future
        // Use getAssetUsefulLifeInYearsAndMethod method key asset_category_depreciation_method
        return $assetDepreciationLibrary->computeDepreciationSchedule($assetOfficeId, $assetId, $costOfAsset, $salvageValue, $usefulLifeInYears, $purchasedDate);
    }

    function formatColumnsValues(string $columnName, mixed $columnValue, array $rowArray, array $dependancyData = []): mixed {

        if(in_array($columnName,['capital_asset_cost', 'capital_asset_residual_value'])){
            $columnValue = number_format($columnValue,2);
        }

        return $columnValue;
    }

    function listTableWhere(\CodeIgniter\Database\BaseBuilder $queryBuilder): void{
        if(!$this->session->system_admin){
            $queryBuilder->whereIn('capital_asset.fk_office_id', array_column($this->session->hierarchy_offices,'office_id'));
        }
    }

}