<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\AssetDepreciationModel;
use \App\Libraries\Grants\Builders\Depreciation\DepreciationMethodFactory;
class AssetDepreciationLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $assetdepreciationModel;

    public $lookup_tables_with_null_values = ['voucher'];

    function __construct()
    {
        parent::__construct();

        $this->assetdepreciationModel = new AssetDepreciationModel();

        $this->table = 'asset_depreciation';
    }

    private function getDateDifferenceInMonths($firstdate, $secondDate){
        $date1 = new \DateTime($firstdate);
        $date2 = new \DateTime($secondDate);

        $interval = $date1->diff($date2);

        $months = $interval->m;
        $years = $interval->y;

        // To get the total number of months, including years
        $totalMonths = ($years * 12) + $months;

        return $totalMonths;
    }

    public function computeStraightLineDepreciationSchedule($assetOfficeId, $assetId, $costOfAsset, $salvageValue, $usefulLifeInYears, $purchasedDate): bool{

        $voucherLibrary = new \App\Libraries\Grants\VoucherLibrary();
        $currentReportingMonth = date('Y-m-01',strtotime($voucherLibrary->getVoucherDate($assetOfficeId)));

        // Check pastServicePeriodInMonths is not more than usefulLifeInYears
        $pastServicePeriodInMonths = $this->getDateDifferenceInMonths($purchasedDate, $currentReportingMonth); 
        $usefulLifeInMonths = $usefulLifeInYears * 12;
        
        // Do not insert the schedule if the pastServicePeriodInMonths is greater than usefulLifeInYears
        if($pastServicePeriodInMonths >= $usefulLifeInMonths) {
            return false;
        }

        // Instantiate the library - A Factory Instance
        $depreciationObj = new DepreciationMethodFactory();

        // Get Preferred Depreciation Method
        $assetCategoryLibrary = new \App\Libraries\Grants\AssetCategoryLibrary();
        $assetCategory = $assetCategoryLibrary->getAssetUsefulLifeInYearsAndMethod($assetId);

        $depreciationMethodTag = $assetCategory['asset_category_depreciation_method'];
        $depreciationObj->setDepreciationMethod($depreciationMethodTag);
        $depreciationObj->setAssetValue($costOfAsset);
        $depreciationObj->setSalvageValue($salvageValue);
        $depreciationObj->setUsefulLife($usefulLifeInYears);

        if($depreciationMethodTag == 'declining'){
            $depreciationObj->setMultiplier(2); // 2 = Double Declining Depreciation. Introduce the multiplier effect by account system
        }

        $depreciation = $depreciationObj->createDepreciationMethod();

        // Get the depreciation schedule
        $schedule = $depreciation->getDepreciationSchedule();

        return $this->insertScheduleToDatabase($schedule, $assetId, $pastServicePeriodInMonths, $currentReportingMonth);
    }



    private function insertScheduleToDatabase($data, $assetId, $pastServicePeriodInMonths, $currentReportingMonth): bool{

        $insertArray = [];

        $cnt = 0;
        foreach($data as $scheduleRow){
            
            // Do not record in the depreciation schedule past service months
            if($scheduleRow['month'] <= $pastServicePeriodInMonths && $pastServicePeriodInMonths != 0) continue;
            
            $depreciationDate = date('Y-m-01', strtotime("+".$cnt." month", strtotime($currentReportingMonth)));

            $nameAndTrackNumber = $this->generateItemTrackNumberAndName('asset_depreciation');

            $insertArray[$cnt]['asset_depreciation_name'] = $nameAndTrackNumber['asset_depreciation_name'];
            $insertArray[$cnt]['asset_depreciation_track_number'] = $nameAndTrackNumber['asset_depreciation_track_number'];
            $insertArray[$cnt]['asset_depreciation_month_count'] = $scheduleRow['month'];
            $insertArray[$cnt]['asset_depreciation_month'] = $depreciationDate;
            $insertArray[$cnt]['asset_depreciation_start_value'] = $scheduleRow['month_beginning_asset_value'];
            $insertArray[$cnt]['asset_depreciation_expense'] = $scheduleRow['month_depreciation_expense'];
            $insertArray[$cnt]['asset_depreciation_accumulated'] = $scheduleRow['accumulated_depreciation_expense'];
            $insertArray[$cnt]['asset_depreciation_end_value'] = $scheduleRow['month_end_asset_value']; 
            $insertArray[$cnt]['fk_capital_asset_id'] = $assetId;
            $insertArray[$cnt]['asset_depreciation_created_date'] = date('Y-m-d');
            $insertArray[$cnt]['asset_depreciation_created_by'] = $this->session->user_id;
            $insertArray[$cnt]['asset_depreciation_last_modified_date'] = date('Y-m-d h:i:s');
            $insertArray[$cnt]['asset_depreciation_last_modified_by'] = $this->session->user_id;

            $cnt++;
        }

        if(count($insertArray) > 0){
            $builder = $this->write_db->table('asset_depreciation');
            $builder->insertBatch($insertArray);
        }

        return $this->write_db->affectedRows() > 0 ? true : false;
    }

    function showAddButton(): bool {
        return false;
    }

    
    function singleFormAddVisibleColumns(): array
    {
        return [
            'capital_asset_name',
            'asset_depreciation_expense',
        ];
    }

    function detailListTableVisibleColumns(): array {
        return [
            'asset_depreciation_month_count',
            'asset_depreciation_start_value',
            'asset_depreciation_expense',
            'asset_depreciation_accumulated',
            'asset_depreciation_end_value',
            'asset_depreciation_month',
            'voucher_name',
        ];
    }
   
    public function orderListPage(): string
    {
        return 'asset_depreciation_month_count ASC';
    }


    public function changeFieldType(): array {
        $change_field_type['asset_depreciation_month_count']['field_type'] = 'text';

        return $change_field_type;
    }

    function calculateMonthsDepreciationExpense(int $officeId, string $reportingMonth = ''){
        $voucherLibrary = new \App\Libraries\Grants\VoucherLibrary();
        $currentReportingMonth = !empty($reportingMonth) ? $reportingMonth : date('Y-m-01',strtotime($voucherLibrary->getVoucherDate($officeId)));
        $totalDepreciationExpense = 0;
        $assetDepreciationIds = [];
        
        // Get all assets that have depreciation schedules for the current reporting month
        $builder = $this->read_db->table('asset_depreciation');
        $builder->select([
            'asset_depreciation.asset_depreciation_id',
            'asset_depreciation.asset_depreciation_expense',
            'asset_depreciation.fk_capital_asset_id',
            'capital_asset.capital_asset_name'
        ]);

        $builder->join('capital_asset', 'capital_asset.capital_asset_id = asset_depreciation.fk_capital_asset_id');
        $builder->join('asset_state','asset_state.asset_state_id=capital_asset.fk_asset_state_id');
        $builder->where('asset_depreciation.asset_depreciation_month <= ', $currentReportingMonth);
        $builder->where('asset_depreciation.fk_voucher_id IS NULL');
        $builder->where('capital_asset.fk_office_id', $officeId);
        $builder->whereIn('asset_state.asset_state_operation', ['operational','pending']);

        $depreciationSchedulesObj = $builder->get();

        if($depreciationSchedulesObj->getNumRows() > 0){
            $depreciationSchedules = $depreciationSchedulesObj->getResultArray();

            foreach($depreciationSchedules as $schedule) {
                $totalDepreciationExpense += $schedule['asset_depreciation_expense'];
            }
            
            $assetDepreciationIds = array_column($depreciationSchedules, 'asset_depreciation_id');
        }

        return compact('totalDepreciationExpense', 'assetDepreciationIds');

    }

    function formatColumnsValues(string $columnName, mixed $columnValue, array $rowArray, array $dependancyData = []): mixed {

        if($columnName == 'voucher_name' && isset($rowArray['voucher_id']) && $rowArray['voucher_id'] > 0){
            $hashedId = hash_id($rowArray['voucher_id'], 'encode');
            $columnValue = "<a target='__blank' href='".base_url()."voucher/view/".$hashedId."'>".$rowArray['voucher_number']."</a>";
        }

        return $columnValue;
    }
}