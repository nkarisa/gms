<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\PayrollDeductionModel;
use CodeIgniter\Database\RawSql;
class PayrollDeductionLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $payrolldeductionModel;

    function __construct()
    {
        parent::__construct();

        $this->payrolldeductionModel = new PayrollDeductionModel();

        $this->table = 'payroll_deduction';
    }

    function singleFormAddVisibleColumns(): array {
        return [
            'payslip_name',
            'payroll_deduction_category_name',
            'payroll_deduction_amount'
        ];
    }

    function transactionValidateDuplicatesColumns(): array {
        return ['fk_payroll_deduction_category_id','fk_payslip_id'];
    }

    function detailListTableVisibleColumns(): array {
        return [
            'payroll_deduction_track_number',
            'payslip_name',
            'payroll_deduction_category_name',
            'payroll_deduction_amount',
            'payroll_deduction_created_date'
        ];
    }

    function actionAfterInsert(array $post_array, int|null $approval_id, int $header_id): bool{
        $payslipId = $post_array['fk_payslip_id'];

        // Get the sum of deductions for the current payslip
        $payslipDeductionBuilder = $this->write_db->table('payroll_deduction');

        $payslipDeductionBuilder->selectSum('payroll_deduction_amount');
        $payslipDeductionBuilder->where('fk_payslip_id', $payslipId);
        $payslipDeductionBuilder->groupBy('fk_payslip_id');
        $payslipDeductionObj = $payslipDeductionBuilder->get();

        $totalPayslipDeductions = 0;

        if($payslipDeductionObj->getNumRows() > 0){
            $totalPayslipDeductions = $payslipDeductionObj->getRow()->payroll_deduction_amount;
        }

        // Update payslip with total deduction
        $payslipBuilder = $this->write_db->table('payslip');
        
        $rawSql = new RawSql("payslip_basic_pay + payslip_total_earning - " . $totalPayslipDeductions);
        
        $payslipBuilder->set('payslip_total_deduction', $totalPayslipDeductions);
        $payslipBuilder->set('payslip_net_pay', $rawSql);
        // $payslipBuilder->set('payslip_net_pay', 'payslip_basic_pay - ' . $totalPayslipDeductions, FALSE);
        $payslipBuilder->where('payslip_id', $payslipId);
        $payslipBuilder->update();

        if($this->write_db->affectedRows() > 0){
            return true;
        }

        return false;
    }

        private function getAvailableDeductions($payHistoryId){
        
        $deductionBuilder = $this->read_db->table('payroll_deduction');
        $deductionBuilder->select('payroll_deduction_id, fk_payroll_deduction_category_id,payroll_deduction_amount');
        $deductionBuilder->join('payslip', 'payslip.payslip_id=payroll_deduction.fk_payslip_id');
        $deductionBuilder->where('payslip.fk_pay_history_id', $payHistoryId);
        $deductionObj = $deductionBuilder->get();

        $availableDeductions = [];

        if ($deductionObj->getNumRows() > 0) {
            $availableDeductions = $deductionObj->getResultArray();
        }

        return $availableDeductions;
    }

    private function updateAvailableDeductionsNewAmountAndCategory($postDeductions, &$availableDeductions){
        // Update new earnings to available ones
        foreach ($availableDeductions as $key => $availableDeduction) {
            foreach ($postDeductions as $postDeduction) {
                if (
                    isset($postDeduction->payroll_deduction_id) && 
                    $postDeduction->payroll_deduction_id == $availableDeduction['payroll_deduction_id']
                ) {
                    $availableDeductions[$key]['payroll_deduction_amount'] = $postDeduction->amount;
                    $availableDeductions[$key]['fk_payroll_deduction_category_id'] = $postDeduction->fk_payroll_deduction_category_id;
                }
            }
        }

    }

    private function addNewDeductionsToAvailableDeductions($postDeductions, &$availableDeductions){
        $cnt = random_int(100, 1000);
        foreach ($postDeductions as $postDeduction) {
            if (!isset($postDeduction->payroll_deduction_id) && !in_array($postDeduction->fk_payroll_deduction_category_id, array_column($availableDeductions, 'fk_payroll_deduction_category_id'))) {
                $availableDeductions[$cnt]['fk_payroll_deduction_category_id'] = $postDeduction->fk_payroll_deduction_category_id;
                $availableDeductions[$cnt]['payroll_deduction_amount'] = $postDeduction->amount;
            }
            $cnt++;
        }
    }

    private function saveDeductionsToDatabase($availableDeductions, $payslipId){
   
        $grantsLibrary = new GrantsLibrary();
        $deductionModel = new PayrollDeductionModel();

        foreach($availableDeductions as $availableDeduction){

            $result = [];

            $nameAndTrackNumber = $grantsLibrary->generateItemTrackNumberAndName('payroll_deduction');

            if(isset($availableDeduction['payroll_deduction_id'])){
              $result['payroll_deduction_id'] = $availableDeduction['payroll_deduction_id'];
            }

            $result['payroll_deduction_name'] = $nameAndTrackNumber['payroll_deduction_name'];
            $result['payroll_deduction_track_number'] = $nameAndTrackNumber['payroll_deduction_track_number'];
            $result['fk_payroll_deduction_category_id'] = $availableDeduction['fk_payroll_deduction_category_id'];
            $result['fk_payslip_id'] = $payslipId;
            $result['payroll_deduction_amount'] = $availableDeduction['payroll_deduction_amount'];
            $result['payroll_deduction_created_date'] = date('Y-m-d');
            $result['payroll_deduction_created_by'] = $this->session->user_id;
            $result['payroll_deduction_last_modified_by'] = $this->session->user_id;

            $deductionModel->save($result);
        }
    }

   
     private function deleteRemovedDeductions($payHistoryId, $postDeductions, &$availableDeductions)
    {
        $postDeductionDeductionIds = array_column($postDeductions, 'payroll_deduction_id');
        $availableDeductionDeductionIds = array_column($availableDeductions, 'payroll_deduction_id');

        $deletedPayrollDeductionIds = array_values(array_diff($availableDeductionDeductionIds, $postDeductionDeductionIds));

        if(!empty($deletedPayrollDeductionIds)){
            // Remove the deduction to be deleted from the available deductions
            foreach($availableDeductions as $key => $availableDeduction){
                if(in_array($availableDeduction['payroll_deduction_id'], $deletedPayrollDeductionIds)){
                    unset($availableDeductions[$key]);
                }
            }
    
            $availableDeductions = array_values($availableDeductions);
    
            $this->payrolldeductionModel->delete($deletedPayrollDeductionIds);
        }
        
    }


    public function deductionUpsert($deductions, $payHistoryId, $payslipId)
    {
        $postDeductionsReplacedCategoryId = renameObjectProperty($deductions, 'id', 'fk_payroll_deduction_category_id');
        $postDeductions = renameObjectProperty($postDeductionsReplacedCategoryId, 'record_id', 'payroll_deduction_id');
        $availableDeductions = $this->getAvailableDeductions($payHistoryId);

        
        $this->updateAvailableDeductionsNewAmountAndCategory($postDeductions, $availableDeductions);
        $this->addNewDeductionsToAvailableDeductions($postDeductions, $availableDeductions);
        $this->deleteRemovedDeductions($payHistoryId, $postDeductions, $availableDeductions);
        $this->saveDeductionsToDatabase($availableDeductions, $payslipId);
    }
   
}