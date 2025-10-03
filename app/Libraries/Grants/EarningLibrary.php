<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\EarningModel;
use App\Enums\EarningTypes;

class EarningLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $earningModel;

    function __construct()
    {
        parent::__construct();

        $this->earningModel = new EarningModel();

        $this->table = 'earning';
    }

    function singleFormAddVisibleColumns(): array {
        return [
            'pay_history_name',
            'earning_category_name',
            'earning_amount'
        ];
    }

    function editVisibleColumns(): array {
        return [
            'pay_history_name',
            'earning_category_name',
            'earning_amount'
        ];
    }

    function lookupValues(): array {
        $lookupValues = parent::lookupValues();

        $lookupValues['pay_history'] = [];

        $payHistoryBuilder = $this->read_db->table('pay_history');

        $payHistoryBuilder->select('pay_history.pay_history_id, CONCAT(user_firstname, " ", user_lastname, " ","[", pay_history.pay_history_start_date, " - ", pay_history.pay_history_end_date, "]") as pay_history_name');
        $payHistoryBuilder->join('user','user.user_id=pay_history.fk_user_id');
        $payHistoryBuilder->where('user_is_active','1');     
        
        if(!$this->session->system_admin){
            $payHistoryBuilder->whereIn('pay_history.fk_office_id', array_column($this->session->hierarchy_offices, 'office_id'));
        }

        $payHistoryObj = $payHistoryBuilder->get();
        
        if($payHistoryObj->getNumRows() > 0){
            $lookupValues['pay_history'] =  $payHistoryObj->getResultArray();
        }

        $lookupValues['earning_category'] = [];
        $payslipAuxiliaryPayCategoryBuilder = $this->read_db->table('earning_category');

        $payslipAuxiliaryPayCategoryBuilder->select(['earning_category_id','earning_category_name']);
        // $payslipAuxiliaryPayCategoryBuilder->where(['earning_category_is_recurring' => '1']);

        if(!$this->session->system_admin){
            $payslipAuxiliaryPayCategoryBuilder->where('earning_category.fk_account_system_id', $this->session->user_account_system_id);
        }

        $payslipAuxiliaryPayCategoryObj = $payslipAuxiliaryPayCategoryBuilder->get();

        if($payslipAuxiliaryPayCategoryObj->getNumRows() > 0){
            $lookupValues['earning_category'] = $payslipAuxiliaryPayCategoryObj->getResultArray();
        }

        return $lookupValues;
    }

    function detailListTableVisibleColumns(): array {
        return [
            'earning_track_number',
            'pay_history_name',
            'earning_category_name',
            'earning_amount',
            'earning_created_date'
        ];
    }

    function transactionValidateDuplicatesColumns(): array {
        return ['fk_pay_history_id','fk_earning_category_id'];
    }

    function actionAfterInsert(array $post_array, int|null $approval_id, int $header_id): bool {
        $payHistoryId = $post_array['fk_pay_history_id'];

        // Get total taxable auxilliary and add it to the gross pay to get taxable pay
        $auxilliaryPayBuilder = $this->write_db->table('earning');


        $auxilliaryPayBuilder->selectSum('earning_amount');
        $auxilliaryPayBuilder->join('earning_category','earning_category.earning_category_id=earning.fk_earning_category_id');
        $auxilliaryPayBuilder->where('fk_pay_history_id', $payHistoryId);
        $auxilliaryPayBuilder->where('earning_category.earning_category_is_taxable', '1');
        $auxilliaryPayBuilder->groupBy('fk_pay_history_id');
        $auxilliaryPayObj = $auxilliaryPayBuilder->get();

        $taxablePay = 0;

        if($auxilliaryPayObj->getNumRows() > 0){
            $taxablePay = $auxilliaryPayObj->getRowArray()['earning_amount'];
        }

        // Update the pay_history
        $payHistoryBuilder = $this->write_db->table('pay_history');
        $payHistoryBuilder->where('pay_history_id', $payHistoryId);
        $payHistoryBuilder->update([
            'pay_history_total_earning_amount' => $taxablePay,
            'pay_history_last_modified_by' => $this->session->user_id
        ]);

        // if($this->write_db->affectedRows() > 0){
            return true;
        // }

        // return false;
    }


    private function getPayslipSavedEarnings($postEarnings, $payHistoryId, EarningTypes $earningType){
        
        // Get all database earnings for a given payslip
        $earningBuilder = $this->read_db->table('earning');

        $earningBuilder->select('earning_id, fk_earning_category_id,earning_amount');
        $earningBuilder->where('fk_pay_history_id', $payHistoryId);
        $earningBuilder->join('earning_category','earning_category.earning_category_id=earning.fk_earning_category_id');
        $earningType->value == 'payable' ? $earningBuilder->where('earning_category_is_accrued', '0') : $earningBuilder->where('earning_category_is_accrued', '1');
        $earningObj = $earningBuilder->get();

        $availableEarnings = [];

        if ($earningObj->getNumRows() > 0) {
            $availableEarnings = $earningObj->getResultArray();
        }

        return $availableEarnings;
    }

    private function updateAvailableEarningsNewAmount($postEarnings, &$availableEarnings){
         // Add updated earnings to database available earnings
        foreach ($availableEarnings as $key => $availableEarning) {
            foreach ($postEarnings as $postEarning) {
                if (
                    isset($postEarning->earning_id) && 
                    $postEarning->earning_id == $availableEarning['earning_id']
                ) {
                    $availableEarnings[$key]['earning_amount'] = $postEarning->amount;
                    $availableEarnings[$key]['fk_earning_category_id'] = $postEarning->fk_earning_category_id;

                }
            }
        }
    }

    private function addNewEarningsToAvailableEarnings($postEarnings, &$availableEarnings){
        // Add new earnings to the database available earnings
        $cnt = sizeof($availableEarnings) + 1;
        foreach ($postEarnings as $postEarning) {
            if (!isset($postEarnings->earning_id) && !in_array($postEarning->fk_earning_category_id, array_column($availableEarnings, 'fk_earning_category_id'))) {
                $availableEarnings[$cnt]['fk_earning_category_id'] = $postEarning->fk_earning_category_id;
                $availableEarnings[$cnt]['earning_amount'] = $postEarning->amount;
            }
            $cnt++;
        }
    }

    private function saveEarningsToDatabase($payHistoryId, $availableEarnings){
        // Add history fields to the earnings records and save the earnings
        $earningModel = new EarningModel();
        $grantsLibrary = new GrantsLibrary();
        
        foreach($availableEarnings as $availableEarning){
            $result = [];
            $nameAndTrackNumber = $grantsLibrary->generateItemTrackNumberAndName('earning');
            
            if(isset($availableEarning['earning_id'])){
              $result['earning_id'] = $availableEarning['earning_id'];  
            } 

            $result['earning_name'] = $nameAndTrackNumber['earning_name'];
            $result['earning_track_number'] = $nameAndTrackNumber['earning_track_number'];
            $result['fk_pay_history_id'] = $payHistoryId;
            $result['fk_earning_category_id'] = $availableEarning['fk_earning_category_id'];
            $result['earning_amount'] = $availableEarning['earning_amount'];
            $result['earning_created_date'] = date('Y-m-t');
            $result['earning_created_by'] = $this->session->user_id;
            $result['earning_last_modified_by'] = $this->session->user_id;

            $earningModel->save($result);            
        }
    }

    private function deleteRemovedEarnings($payHistoryId, $postEarnings, $availableEarnings){
        $postEarningsEarningIds = array_column($postEarnings, 'earning_id');
        $availableEarningsEarningIds = array_column($availableEarnings, 'earning_id');

        $deletedEarningIds = array_values(array_diff($availableEarningsEarningIds, $postEarningsEarningIds));

        // log_message('error', json_encode(compact(
        //     'availableEarningsEarningIds',
        //     'postEarningsEarningIds',
        //     'deletedEarningIds'
        // )));

        if(!empty($deletedEarningIds)){
            // Remove the deduction to be deleted from the available deductions
            foreach($availableEarnings as $key => $availableEarning){
                if(in_array($availableEarning['earning_id'], $deletedEarningIds)){
                    unset($availableEarnings[$key]);
                }
            }
    
            $availableEarnings = array_values($availableEarnings);
    
            $this->earningModel->delete($deletedEarningIds);
        }        
    }

    public function earningUpsert(array $earnings, int $payHistoryId, EarningTypes $earningType)
    {
        $postEarningsReplacedCategoryId = renameObjectProperty($earnings, 'id', 'fk_earning_category_id');
        $postEarnings = renameObjectProperty($postEarningsReplacedCategoryId, 'record_id', 'earning_id');
        $availableEarnings = $this->getPayslipSavedEarnings($postEarnings, $payHistoryId, $earningType);
                
        $this->updateAvailableEarningsNewAmount($postEarnings, $availableEarnings);
        $this->addNewEarningsToAvailableEarnings($postEarnings, $availableEarnings);
        $this->deleteRemovedEarnings($payHistoryId, $postEarnings, $availableEarnings);
        $this->saveEarningsToDatabase($payHistoryId, $availableEarnings);
    }

   
}