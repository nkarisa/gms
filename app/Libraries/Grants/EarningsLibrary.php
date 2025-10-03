<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\EarningsModel;
class EarningsLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{

    protected $table;
    protected $earningsModel;

    function __construct()
    {
        parent::__construct();

        $this->earningsModel = new EarningsModel();

        $this->table = 'earnings';
    }

    function singleFormAddVisibleColumns(): array {
        return [
            'pay_history_name',
            'earnings_category_name',
            'earnings_amount'
        ];
    }

    function editVisibleColumns(): array {
        return [
            'pay_history_name',
            'earnings_category_name',
            'earnings_amount'
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

        $lookupValues['earnings_category'] = [];
        $payslipAuxiliaryPayCategoryBuilder = $this->read_db->table('earnings_category');

        $payslipAuxiliaryPayCategoryBuilder->select(['earnings_category_id','earnings_category_name']);
        // $payslipAuxiliaryPayCategoryBuilder->where(['earnings_category_is_recurring' => '1']);

        if(!$this->session->system_admin){
            $payslipAuxiliaryPayCategoryBuilder->where('earnings_category.fk_account_system_id', $this->session->user_account_system_id);
        }

        $payslipAuxiliaryPayCategoryObj = $payslipAuxiliaryPayCategoryBuilder->get();

        if($payslipAuxiliaryPayCategoryObj->getNumRows() > 0){
            $lookupValues['earnings_category'] = $payslipAuxiliaryPayCategoryObj->getResultArray();
        }

        return $lookupValues;
    }

    function detailListTableVisibleColumns(): array {
        return [
            'earnings_track_number',
            'pay_history_name',
            'earnings_category_name',
            'earnings_amount',
            'earnings_created_date'
        ];
    }

    function transactionValidateDuplicatesColumns(): array {
        return ['fk_pay_history_id','fk_earnings_category_id'];
    }

    function actionAfterInsert(array $post_array, int|null $approval_id, int $header_id): bool {
        $payHistoryId = $post_array['fk_pay_history_id'];

        // Get total taxable auxilliary and add it to the gross pay to get taxable pay
        $auxilliaryPayBuilder = $this->write_db->table('earnings');


        $auxilliaryPayBuilder->selectSum('earnings_amount');
        $auxilliaryPayBuilder->join('earnings_category','earnings_category.earnings_category_id=earnings.fk_earnings_category_id');
        $auxilliaryPayBuilder->where('fk_pay_history_id', $payHistoryId);
        $auxilliaryPayBuilder->where('earnings_category.earnings_category_is_taxable', '1');
        $auxilliaryPayBuilder->groupBy('fk_pay_history_id');
        $auxilliaryPayObj = $auxilliaryPayBuilder->get();

        $taxablePay = 0;

        if($auxilliaryPayObj->getNumRows() > 0){
            $taxablePay = $auxilliaryPayObj->getRowArray()['earnings_amount'];
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
   
}