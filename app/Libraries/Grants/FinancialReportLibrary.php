<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\FinancialReportModel;
use App\Libraries\Core\StatusLibrary;

class FinancialReportLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{
    protected $table;
    protected $financialReportModel;
    protected $customFinancialYearLibrary;
    protected $statusLibrary;

    function __construct()
    {
        parent::__construct();

        $this->financialReportModel = new FinancialReportModel();
        $this->statusLibrary = new StatusLibrary();
        $this->customFinancialYearLibrary = new \App\Libraries\Grants\CustomFinancialYearLibrary();
        
        $this->table = 'financial_report';
    }

    /**
     *getOffice():This method return and office/fcp.
     * @author Livingstone Onduso: Dated 06-05-2024
     * @access public
     * @return object 
     * @param float $budget_id
     */
    public function getOffice(float  $budget_id): object
    {
        // Query
        $builder = $this->read_db->table('office');
        $builder->select([
            'office_id',
            'office_name',
            'office_code',
            'budget_year',
            'office.fk_account_system_id as account_system_id',
            'budget.fk_status_id as budget_status_id',
            'fk_custom_financial_year_id as custom_financial_year_id'
        ]);
        $builder->join('budget', 'budget.fk_office_id = office.office_id');
        $builder->where('budget_id', $budget_id);
        $office = $builder->get()->getRow();

        return $office;
    }

    /**
     *allOfficeFinancialReportSubmitted():This method true or false if office has the financial reports.
     * @author Livingstone Onduso: Dated 06-05-2024
     * @access public
     * @return bool 
     * @param float $office_id
     */
    public function allOfficeFinancialReportSubmitted(float $office_id): bool
    {
        // Query Builder
        $builder = $this->read_db->table('financial_report');
        $builder->where('fk_office_id', $office_id);
        $builder->where('financial_report_is_submitted', 0);

        // Count the rows
        $not_submitted_mfrs_count = $builder->countAllResults();

        // Return the result
        return $not_submitted_mfrs_count > 0 ? false : true;
    }


    function compute_cash_at_bank($office_ids, $reporting_month, $project_ids = [], $office_bank_ids = [], $retrieve_only_max_approved = true)
    {
        $to_date_cancelled_opening_oustanding_cheques = $this->getMonthCancelledOpeningOutstandingCheques($office_ids, $reporting_month, $project_ids, $office_bank_ids, 'to_date');

        $office_ids = array_unique($office_ids); // Find out why office_ids come in duplicates

        $opening_bank_balance = $this->openingCashBalance($office_ids, $reporting_month, $project_ids, $office_bank_ids)['bank'];

        $bank_to_bank_contra_receipts = $this->bankToBankContraReceipts($office_bank_ids, $reporting_month);
        $bank_to_bank_contra_contributions = $this->bankToBankContraContributions($office_bank_ids, $reporting_month);

        $cash_transactions_to_date = $this->cashTransactionsToDate($office_ids, $reporting_month, $project_ids, $office_bank_ids, 0, $retrieve_only_max_approved);

        $bank_income_to_date = $cash_transactions_to_date['bank']['income'] ?? 0;
        $bank_expenses_to_date = $cash_transactions_to_date['bank']['expense'] ?? 0;

        $computed_cash_at_bank = $opening_bank_balance + $bank_income_to_date - $bank_expenses_to_date;

        if ($bank_to_bank_contra_receipts > 0) {
            $computed_cash_at_bank = $computed_cash_at_bank + array_sum($bank_to_bank_contra_receipts);
        }

        if ($bank_to_bank_contra_contributions > 0) {
            $computed_cash_at_bank = $computed_cash_at_bank - array_sum($bank_to_bank_contra_contributions);
        }

        $computed_cash_at_bank = $computed_cash_at_bank + $to_date_cancelled_opening_oustanding_cheques;

        return $computed_cash_at_bank;
    }

    function getMonthCancelledOpeningOutstandingCheques($office_ids, $start_date_of_month, $project_ids, $office_bank_ids, $aggregation_period = 'current_month')
    { // Options: current_month, past_months, to_date

        $sum_cancelled_cheques = 0;

        $first_month_date = date('Y-m-01', strtotime($start_date_of_month));
        $end_month_date = date('Y-m-t', strtotime($start_date_of_month));

        $builder = $this->read_db->table("opening_outstanding_cheque");
        $builder->selectSum('opening_outstanding_cheque_amount');
        $builder->whereIn('fk_office_id', $office_ids);
        $builder->where(['opening_outstanding_cheque_bounced_flag' => 1]);

        $condition = ['opening_outstanding_cheque_cleared_date' => $end_month_date];

        if ($aggregation_period == 'past_months') {
            $condition = ['opening_outstanding_cheque_cleared_date < ' => $first_month_date];
        }

        if ($aggregation_period == 'to_date') {
            $condition = ['opening_outstanding_cheque_cleared_date <= ' => $end_month_date];
        }

        $builder->where($condition);

        if (!empty($office_bank_ids)) {
            $builder->whereIn('fk_office_bank_id', $office_bank_ids);
        }

        $builder->groupBy(array('fk_system_opening_balance_id'));
        $builder->join('system_opening_balance', 'system_opening_balance.system_opening_balance_id=opening_outstanding_cheque.fk_system_opening_balance_id');
        $builder->join('office', 'office.office_id=system_opening_balance.fk_office_id');
        $opening_outstanding_cheque_obj = $builder->get();

        if ($opening_outstanding_cheque_obj->getNumRows() > 0) {

            $sum_cancelled_cheques = $opening_outstanding_cheque_obj->getRow()->opening_outstanding_cheque_amount;
        }

        return $sum_cancelled_cheques;
    }

    function openingCashBalance($office_ids, $reporting_month, array $project_ids = [], $office_bank_ids = [], $office_cash_id = 0)
    {
        $openingCashBalanceLibrary = new \App\Libraries\Grants\OpeningCashBalanceLibrary();
        $bank_balance_amount = $this->systemOpeningBankBalance($office_ids, $project_ids, $office_bank_ids);

        if (!isset($_POST['reporting_month'])) {
            $report = $this->financialReportInformation($this->id);
            extract($report);
            $report_month = $reporting_month;
        } else {
            $report_month = $_POST['reporting_month'];
        }

        //If the mfr has been submitted. Adjust the child support fund by taking away exact amount of bounced opening chqs This code was added during enhancement for cancelling opening outstanding chqs

        if ($this->checkIfFinancialReportIsSubmitted($office_ids, $reporting_month) == true) {
            $sum_of_bounced_cheques = $this->getTotalSumOfBouncedOpeningCheques($office_ids, $reporting_month, $project_ids, $office_bank_ids);
            $mfr_report_month = date('Y-m-t', strtotime($reporting_month));

            $total_amount_bounced = $sum_of_bounced_cheques[0]['opening_outstanding_cheque_amount'] ?? 0;
            $bounced_date = $sum_of_bounced_cheques[0]['opening_outstanding_cheque_cleared_date'] ?? NULL;

            if ($total_amount_bounced > 0 && $bounced_date > $mfr_report_month) {
                $bank_balance_amount = $bank_balance_amount - $total_amount_bounced;
            }
        }

        $balance = [
            'bank' => $bank_balance_amount,
            'cash' => $openingCashBalanceLibrary->systemOpeningCashBalance($office_ids, $project_ids, $office_bank_ids, $office_cash_id)
        ];

        return $balance;
    }

    function systemOpeningBankBalance($office_ids, array $project_ids = [], $office_bank_ids = [])
    {
        $builder = $this->read_db->table("opening_bank_balance");
        $builder->selectSum('opening_bank_balance_amount');
        $builder->join('system_opening_balance', 'system_opening_balance.system_opening_balance_id=opening_bank_balance.fk_system_opening_balance_id');
        $builder->join('office_bank', 'office_bank.office_bank_id=opening_bank_balance.fk_office_bank_id');
        $builder->whereIn('system_opening_balance.fk_office_id', $office_ids);

        if (!empty($project_ids)) {
            $builder->whereIn('project_allocation.fk_project_id', $project_ids);
            $builder->join('office_bank_project_allocation', 'office_bank_project_allocation.fk_office_bank_id=office_bank.office_bank_id');
            $builder->join('project_allocation', 'project_allocation.project_allocation_id=office_bank_project_allocation.fk_project_allocation_id');
        }

        if (!empty($office_bank_ids)) {
            $builder->whereIn('opening_bank_balance.fk_office_bank_id', $office_bank_ids);
        }

        $opening_bank_balance_obj = $builder->get();
        $opening_bank_balance = $opening_bank_balance_obj->getNumRows() > 0 ? $opening_bank_balance_obj->getRow()->opening_bank_balance_amount : 0;

        return $opening_bank_balance;
    }

    function financialReportInformation(string $id, array $offices_ids = [], string $reporting_month = '')
    {
        $report_id = hash_id($id, 'decode');
        $offices_information = [];

        $builder = $this->read_db->table("financial_report");
        $builder->select(array('financial_report_month', 'fk_office_id as office_id', 'office_name', 'financial_report.fk_status_id as status_id', 'fk_account_system_id as account_system_id'));
        $builder->join('office', 'office.office_id=financial_report.fk_office_id');

        if (count($offices_ids) > 0) {
            $builder->whereIn('fk_office_id', $offices_ids);

            if ($reporting_month != '') {
                $builder->where(array('financial_report_month' => date('Y-m-01', strtotime($reporting_month))));
            }
        } else {
            $builder->where(array('financial_report_id' => $report_id));
        }

        $offices_information = $builder->get()->getResultArray();

        return $offices_information;
    }

    function checkIfFinancialReportIsSubmitted($office_ids, $reporting_month)
    {
        $report_is_submitted = false;
        $builder = $this->read_db->table("financial_report");
        $builder->select(['financial_report_is_submitted']);
        $builder->where(['financial_report_month' => date('Y-m-01', strtotime($reporting_month)), 'fk_office_id' => $office_ids[0]]);
        $financial_report_is_submitted_obj = $builder->get();

        if ($financial_report_is_submitted_obj->getNumRows() > 0) {

            if ($financial_report_is_submitted_obj->getRow()->financial_report_is_submitted == 1) {
                $report_is_submitted = true;
            }
        }

        return $report_is_submitted;
    }

    public function getTotalSumOfBouncedOpeningCheques($office_ids, $reporting_month, $project_ids = [], $office_bank_ids = [])
    {
        $reporting_month = date('Y-m-t', strtotime($reporting_month));
        $officeBankLibrary = new \App\Libraries\Grants\OfficeBankLibrary();

        if (sizeof($office_bank_ids) == 0) {
            $office_bank = $officeBankLibrary->getOfficeBanks($office_ids);
            $office_bank_ids = array_column($office_bank, 'office_bank_id');
        }

        $builder = $this->read_db->table("opening_outstanding_cheque");
        $builder->selectSum('opening_outstanding_cheque_amount');
        $builder->select(array('opening_outstanding_cheque_cleared_date'));
        $builder->whereIn('fk_office_bank_id', $office_bank_ids);
        $builder->where(array('opening_outstanding_cheque_bounced_flag' => 1));
        $builder->where(array('opening_outstanding_cheque_cleared_date' => $reporting_month));
        $builder->groupBy(array('fk_office_bank_id', 'opening_outstanding_cheque_cleared_date')); // Modified by Nicodemus Karisa on 13th May 2022
        return $builder->get()->getResultArray();
    }

    function bankToBankContraReceipts(array $office_bank_ids, string $reporting_month): array
    {
        $bank_to_bank_contra_received_amounts = [];
        $end_of_reporting_month = date('Y-m-t', strtotime($reporting_month));

        if (count($office_bank_ids) > 0) {
            $builder = $this->read_db->table("bank_to_bank_contra_receipts");
            $builder->select(array('income_account_id'));
            $builder->selectSum('voucher_detail_total_cost');
            $builder->groupBy(array('income_account_id'));
            $builder->whereIn('office_bank_id', $office_bank_ids);
            $builder->where(array('voucher_date <=' => $end_of_reporting_month));
            $voucher_detail_total_cost_obj = $builder->get();


            if ($voucher_detail_total_cost_obj->getNumRows() > 0) {

                $income_account_grouped = $voucher_detail_total_cost_obj->getResultArray();

                foreach ($income_account_grouped as $row) {
                    if ($row['income_account_id'] != null && $row['voucher_detail_total_cost'] > 0) {
                        $bank_to_bank_contra_received_amounts[$row['income_account_id']] = $row['voucher_detail_total_cost'] ? $row['voucher_detail_total_cost'] : 0;
                    }
                }
            }
        }

        return $bank_to_bank_contra_received_amounts;
    }

    function bankToBankContraContributions($office_bank_ids = [], string $reporting_month = ""): array
    {
        $bank_to_bank_contra_contributed_amounts = [];
        $end_of_reporting_month = date('Y-m-t', strtotime($reporting_month));

        if (count($office_bank_ids) > 0) {
            $builder = $this->read_db->table("bank_to_bank_contra_contributions");
            $builder->select(array('income_account_id'));
            $builder->selectSum('voucher_detail_total_cost');
            $builder->groupBy(array('income_account_id'));
            $builder->whereIn('office_bank_id', $office_bank_ids);
            $builder->where(array('voucher_date <=' => $end_of_reporting_month));
            $voucher_detail_total_cost_obj = $builder->get();

            if ($voucher_detail_total_cost_obj->getNumRows() > 0) {

                $income_account_grouped = $voucher_detail_total_cost_obj->getResultArray();

                foreach ($income_account_grouped as $row) {
                    if ($row['income_account_id'] != null) {
                        $bank_to_bank_contra_contributed_amounts[$row['income_account_id']] = $row['voucher_detail_total_cost'] ? $row['voucher_detail_total_cost'] : 0;
                    }
                }
            }
        }

        return $bank_to_bank_contra_contributed_amounts;
    }

    function cashTransactionsToDate($office_ids, $reporting_month, $project_ids = [], $office_bank_ids = [], $office_cash_id = 0, $retrieve_only_max_approved = true)
    {
        $statusLibrary = new StatusLibrary();
        $cash_transactions_to_date = [];
        $max_approval_status_ids = $statusLibrary->getMaxApprovalStatusId('voucher', $office_ids);
        $start_of_reporting_month = date('Y-m-01', strtotime($reporting_month));

        $builder = $this->read_db->table("monthly_sum_transactions_by_account_effect");
        if (!empty($office_bank_ids)) {
            $builder->whereIn('fk_office_bank_id', $office_bank_ids);
        }

        if ($office_cash_id > 0) {
            $builder->where(array('fk_office_cash_id' => $office_cash_id));
        }

        $builder->where(array('voucher_month <=' => $start_of_reporting_month));

        $builder->select(array('voucher_type_account_code', 'voucher_type_effect_code', 'amount'));
        $builder->selectSum('amount');

        $builder->whereIn('fk_office_id', $office_ids);

        if ($retrieve_only_max_approved) {
            $builder->whereIn('fk_status_id', $max_approval_status_ids);
        }

        $builder->groupBy(array('voucher_type_account_code', 'voucher_type_effect_code', 'fk_office_bank_id'));

        $cash_transactions_to_date_obj = $builder->get();

        if ($cash_transactions_to_date_obj->getNumRows() > 0) {
            $cash_transactions_to_date_arr = $cash_transactions_to_date_obj->getResultArray();

            $cash_transactions_to_date['bank']['income'] = 0;
            $cash_transactions_to_date['bank']['expense'] = 0;
            $cash_transactions_to_date['cash']['income'] = 0;
            $cash_transactions_to_date['cash']['expense'] = 0;

            foreach ($cash_transactions_to_date_arr as $row) {

                if (($row['voucher_type_account_code'] == 'bank' && $row['voucher_type_effect_code'] == 'income') || ($row['voucher_type_account_code'] == 'accrual' && $row['voucher_type_effect_code'] == 'payments') || ($row['voucher_type_account_code'] == 'cash' && $row['voucher_type_effect_code'] == 'cash_contra')) {
                    $cash_transactions_to_date['bank']['income'] += $row['amount'];
                }

                if (($row['voucher_type_account_code'] == 'bank' && $row['voucher_type_effect_code'] == 'expense')  || ($row['voucher_type_account_code'] == 'accrual' && ($row['voucher_type_effect_code'] == 'disbursements' || $row['voucher_type_effect_code'] == 'prepayments')) || ($row['voucher_type_account_code'] == 'bank' && $row['voucher_type_effect_code'] == 'bank_contra')) {
                    $cash_transactions_to_date['bank']['expense'] += $row['amount'];
                }

                if (($row['voucher_type_account_code'] == 'cash' && $row['voucher_type_effect_code'] == 'income') || ($row['voucher_type_account_code'] == 'bank' && $row['voucher_type_effect_code'] == 'bank_contra')) {
                    $cash_transactions_to_date['cash']['income'] += $row['amount'];

                    if ($office_cash_id > 0) {
                        $cash_transactions_to_date['cash']['income'] = $cash_transactions_to_date['cash']['income'] + $this->incomeFromOtherBoxes($office_cash_id, $start_of_reporting_month);
                    }
                }

                if (($row['voucher_type_account_code'] == 'cash' && $row['voucher_type_effect_code'] == 'expense') || ($row['voucher_type_account_code'] == 'cash' && $row['voucher_type_effect_code'] == 'cash_to_cash_contra') || ($row['voucher_type_account_code'] == 'cash' && $row['voucher_type_effect_code'] == 'cash_contra')) {
                    $cash_transactions_to_date['cash']['expense'] += $row['amount'];
                }
            }
        }

        return $cash_transactions_to_date;
    }

    function incomeFromOtherBoxes($office_cash_id, $reporting_month)
    {

        $builder = $this->read_db->table("month_cash_recipient_sum_amount");
        $builder->select(array('amount'));
        $builder->where(array('source_office_cash_id' => $office_cash_id, 'voucher_month <= ' => date('Y-m-t', strtotime($reporting_month)), 'voucher_month >= ' => date('Y-m-01', strtotime($reporting_month))));
        $sumObj = $builder->get();

        $amount = 0;

        if ($sumObj->getNumRows() > 0) {
            $amount = $sumObj->getRow()->amount;
        }

        return $amount;
    }


    function listOustandingChequesAndDeposits($office_ids, $reporting_month, $transaction_type, $contra_type, $voucher_type_account_code, $project_ids = [], $office_bank_ids = [])
    {
        $statusLibrary = new \App\Libraries\Core\StatusLibrary();
        $max_voucher_approval_ids = $statusLibrary->getMaxApprovalStatusId('voucher', $office_ids);

        $builder = $this->read_db->table("voucher_detail");
        if (count($project_ids) > 0) {
            $builder->select(array('office_bank.office_bank_id'));
            $builder->join('office_bank_project_allocation', 'office_bank_project_allocation.fk_office_bank_id=office_bank.office_bank_id');
            $builder->join('project_allocation', 'project_allocation.project_allocation_id=office_bank_project_allocation.fk_project_allocation_id');
            $builder->whereIn('fk_project_id', $project_ids);
        }

        if (!empty($office_bank_ids)) {
            $builder->whereIn('voucher.fk_office_bank_id', $office_bank_ids);
        }

        $list_oustanding_cheques_and_deposit = [];

        $builder->selectSum('voucher_detail_total_cost');
        $builder->select(array(
            'voucher_id',
            'voucher_number',
            'voucher_cheque_number',
            'voucher_vendor',
            'voucher_description',
            'voucher_cleared',
            'office_code',
            'office_name',
            'voucher_date',
            'voucher_cleared',
            'fk_office_bank_id',
            'office_bank_name'
        ));

        $builder->groupBy(array('voucher_id'));


        $builder->whereIn('voucher.fk_office_id', $office_ids);

        if ($transaction_type == 'expense') {
            $builder->whereIn('voucher_type_effect_code', [$transaction_type, $contra_type]); // contra, expense , income
            $builder->where(array('voucher_type_account_code' => $voucher_type_account_code)); // bank, cash
        } elseif (($contra_type == 'cash_contra' || $contra_type = 'bank_contra') && $transaction_type == 'income') {
            $builder->whereIn('voucher_type_effect_code', [$transaction_type, $contra_type]);
        } else {
            $builder->whereIn('voucher_type_effect_code', [$transaction_type, $contra_type]); // contra, expense , income
            $builder->where(array('voucher_type_account_code' => $voucher_type_account_code)); // bank, cash
        }

        $builder->groupStart();
        $builder->where(array(
            'voucher_cleared' => 0,
            'voucher_date <=' => date('Y-m-t', strtotime($reporting_month))
        ));
        $builder->oRGroupStart();
        $builder->where(array(
            'voucher_cleared' => 1,
            'voucher_date <=' => date('Y-m-t', strtotime($reporting_month)),
            'voucher_cleared_month > ' => date('Y-m-t', strtotime($reporting_month))
        ));
        $builder->groupEnd();
        $builder->groupEnd();

        $builder->join('voucher', 'voucher.voucher_id=voucher_detail.fk_voucher_id');
        $builder->join('office', 'office.office_id=voucher.fk_office_id');
        $builder->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
        $builder->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
        $builder->join('voucher_type_account', 'voucher_type_account.voucher_type_account_id=voucher_type.fk_voucher_type_account_id');
        $builder->join('office_bank', 'office_bank.office_bank_id=voucher.fk_office_bank_id');

        $builder->whereIn('voucher.fk_status_id', $max_voucher_approval_ids);

        $list_oustanding_cheques_and_deposit = $builder->get()->getResultArray();

        if ($transaction_type == 'expense') {
            $cleared_and_uncleared_opening_outstanding_cheques = $this->getUnclearedAndClearedOpeningOutstandingCheques($office_ids, $reporting_month, 'uncleared', $office_bank_ids);
            $list_oustanding_cheques_and_deposit = array_merge($list_oustanding_cheques_and_deposit, $cleared_and_uncleared_opening_outstanding_cheques);
        } else {
            $cleared_and_uncleared_deposit_in_transit = $this->getUnclearedAndClearedDepositInTransit($office_ids, $reporting_month, 'uncleared', $office_bank_ids);
            $list_oustanding_cheques_and_deposit = array_merge($list_oustanding_cheques_and_deposit, $cleared_and_uncleared_deposit_in_transit);
        }


        return $list_oustanding_cheques_and_deposit;
    }

    private function getUnclearedAndClearedDepositInTransit($office_ids, $reporting_month, $state = 'uncleared', $office_bank_ids = [])
    {
        $opening_deposit_in_transits = [];

        $builder = $this->read_db->table("opening_deposit_transit");
        $builder->select(
            [
                'opening_deposit_transit_amount as voucher_detail_total_cost',
                'opening_deposit_transit_description as voucher_description',
                'opening_deposit_transit_date as voucher_date',
                'fk_office_bank_id',
                'opening_deposit_transit_is_cleared as voucher_cleared',
                'opening_deposit_transit_cleared_date as voucher_cleared_month',
                'office_bank_name',
                'opening_deposit_transit_id'
            ]
        );

        $builder->where('system_opening_balance.fk_office_id', $office_ids);

        if ($state == 'uncleared') {
            $builder->groupStart();
            $builder->where(array('opening_deposit_transit_is_cleared' => 0));

            $builder->orGroupStart();
            $builder->where(array(
                'opening_deposit_transit_is_cleared' => 1,
                'opening_deposit_transit_cleared_date > ' => date('Y-m-t', strtotime($reporting_month))
            ));
            $builder->groupEnd();
            $builder->groupEnd();
        } else {
            $builder->where(array(
                'opening_deposit_transit_is_cleared' => 1,
                'opening_deposit_transit_cleared_date ' => date('Y-m-t', strtotime($reporting_month))
            ));
        }

        if (!empty($office_bank_ids)) {
            $builder->where('opening_deposit_transit.fk_office_bank_id', $office_bank_ids);
        }

        $builder->join('system_opening_balance', 'system_opening_balance.system_opening_balance_id=opening_deposit_transit.fk_system_opening_balance_id');
        $builder->join('office_bank', 'office_bank.office_bank_id=opening_deposit_transit.fk_office_bank_id');
        $opening_deposit_in_transits_obj = $builder->get();

        if ($opening_deposit_in_transits_obj->getNumRows() > 0) {
            $opening_deposit_in_transits = $opening_deposit_in_transits_obj->getResultArray();
        }

        $modified_opening_deposit_in_transits = [];

        foreach ($opening_deposit_in_transits as $opening_deposit_in_transit) {
            $modified_opening_deposit_in_transits[] = array_merge($opening_deposit_in_transit, ['voucher_id' => 0]);
        }

        return $modified_opening_deposit_in_transits;
    }

    function getUnclearedAndClearedOpeningOutstandingCheques($office_ids, $reporting_month, $state = 'uncleared', $office_bank_ids = [])
    {
        $opening_outstanding_cheques = [];

        $builder = $this->read_db->table("opening_outstanding_cheque");
        $builder->select(
            [
                'opening_outstanding_cheque_amount as voucher_detail_total_cost',
                'opening_outstanding_cheque_number as voucher_cheque_number',
                'opening_outstanding_cheque_description as voucher_description',
                'opening_outstanding_cheque_bounced_flag as bounce_flag',
                'opening_outstanding_cheque_date as voucher_date',
                'fk_office_bank_id',
                'opening_outstanding_cheque_is_cleared as voucher_cleared',
                'opening_outstanding_cheque_cleared_date as voucher_cleared_month',
                'office_bank_name',
                'opening_outstanding_cheque_id'
            ]
        );

        $builder->whereIn('system_opening_balance.fk_office_id', $office_ids);

        if ($state == 'uncleared') {
            $builder->groupStart();
            $builder->where(array('opening_outstanding_cheque_is_cleared' => 0));
            $builder->oRGroupStart();
            $builder->where(array(
                'opening_outstanding_cheque_is_cleared' => 1,
                'opening_outstanding_cheque_cleared_date > ' => date('Y-m-t', strtotime($reporting_month))
            ));
            $builder->groupEnd();
            $builder->groupEnd();
        } else {
            $builder->where(array(
                'opening_outstanding_cheque_is_cleared' => 1,
                'opening_outstanding_cheque_cleared_date ' => date('Y-m-t', strtotime($reporting_month))
            ));
        }

        if (!empty($office_bank_ids)) {
            $builder->whereIn('opening_outstanding_cheque.fk_office_bank_id', $office_bank_ids);
        }

        $builder->join('system_opening_balance', 'system_opening_balance.system_opening_balance_id=opening_outstanding_cheque.fk_system_opening_balance_id');
        $builder->join('office_bank', 'office_bank.office_bank_id=opening_outstanding_cheque.fk_office_bank_id');
        $opening_outstanding_cheque_obj = $builder->get();

        if ($opening_outstanding_cheque_obj->getNumRows() > 0) {
            $opening_outstanding_cheques = $opening_outstanding_cheque_obj->getResultArray();
        }

        $modified_opening_outstanding_cheques = [];
        foreach ($opening_outstanding_cheques as $opening_outstanding_cheque) {
            $modified_opening_outstanding_cheques[] = array_merge($opening_outstanding_cheque, ['voucher_id' => 0]);
        }

        return $modified_opening_outstanding_cheques;
    }

    function computeCashAtBank($office_ids, $reporting_month, $project_ids = [], $office_bank_ids = [], $retrieve_only_max_approved = true)
    {
        $to_date_cancelled_opening_oustanding_cheques = $this->getMonthCancelledOpeningOutstandingCheques($office_ids, $reporting_month, $project_ids, $office_bank_ids, 'to_date');
        $office_ids = array_unique($office_ids); // Find out why office_ids come in duplicates
        $opening_bank_balance = $this->openingCashBalance($office_ids, $reporting_month, $project_ids, $office_bank_ids)['bank'];
        $bank_to_bank_contra_receipts = $this->bankToBankContraReceipts($office_bank_ids, $reporting_month);
        $bank_to_bank_contra_contributions = $this->bankToBankContraContributions($office_bank_ids, $reporting_month);
        $cash_transactions_to_date = $this->cashTransactionsToDate($office_ids, $reporting_month, $project_ids, $office_bank_ids, 0, $retrieve_only_max_approved);
        $bank_income_to_date = $cash_transactions_to_date['bank']['income'] ?? 0;
        $bank_expenses_to_date = $cash_transactions_to_date['bank']['expense'] ?? 0;

        $computed_cash_at_bank = $opening_bank_balance + $bank_income_to_date - $bank_expenses_to_date;

        if ($bank_to_bank_contra_receipts > 0) {
            $computed_cash_at_bank = $computed_cash_at_bank + array_sum($bank_to_bank_contra_receipts);
        }

        if ($bank_to_bank_contra_contributions > 0) {
            $computed_cash_at_bank = $computed_cash_at_bank - array_sum($bank_to_bank_contra_contributions);
        }

        $computed_cash_at_bank = $computed_cash_at_bank + $to_date_cancelled_opening_oustanding_cheques;

        return $computed_cash_at_bank;
    }


    function computeCashAtHand($office_ids, $reporting_month, $project_ids = [], $office_bank_ids = [], $office_cash_id = 0, $retrieve_only_max_approved = true)
    {
        $cash_transactions_to_date = $this->cashTransactionsToDate($office_ids, $reporting_month, $project_ids, $office_bank_ids, $office_cash_id, $retrieve_only_max_approved);
        $opening_cash_balance = $this->openingCashBalance($office_ids, $reporting_month, $project_ids, $office_bank_ids, $office_cash_id)['cash'];
        $cash_income_to_date = $cash_transactions_to_date['cash']['income'] ?? 0;
        $cash_expenses_to_date = $cash_transactions_to_date['cash']['expense'] ?? 0;

        return $opening_cash_balance + $cash_income_to_date - $cash_expenses_to_date;
    }


    function checkIfFinancialReportIsBubmitted($office_ids, $reporting_month)
    {
        $report_is_submitted = false;

        $builder = $this->read_db->table('financial_report');
        $builder->select(['financial_report_is_submitted']);
        $builder->where(['financial_report_month' => date('Y-m-01', strtotime($reporting_month)), 'fk_office_id' => $office_ids[0]]);
        $financial_report_is_submitted_obj = $builder->get();

        if ($financial_report_is_submitted_obj->getNumRows() > 0) {

            if ($financial_report_is_submitted_obj->getRow()->financial_report_is_submitted == 1) {
                $report_is_submitted = true;
            }
        }

        return $report_is_submitted;
    }


    function monthUtilizedIncomeAccounts($office_ids, $start_date_of_month, $project_ids = [], $office_bank_ids = [])
    {

        $income_accounts =  $this->incomeAccounts($office_ids, $project_ids, $office_bank_ids);

        $all_accounts_month_opening_balance = $this->monthIncomeOpeningBalance($office_ids, $start_date_of_month, $project_ids, $office_bank_ids);
        $all_accounts_month_income = $this->monthIncomeAccountReceipts($office_ids, $start_date_of_month, $project_ids, $office_bank_ids);
        $all_accounts_month_expense = $this->monthIncomeAccountExpenses($office_ids, $start_date_of_month, $project_ids, $office_bank_ids);

        $report = array();

        foreach ($income_accounts as $account) {

            $month_opening_balance = $all_accounts_month_opening_balance[$account['income_account_id']] ?? 0;
            $month_income = $all_accounts_month_income[$account['income_account_id']] ?? 0;
            $month_expense = $all_accounts_month_expense[$account['income_account_id']] ?? 0;

            if ($month_opening_balance == 0 && $month_income == 0 && $month_expense == 0) {
                continue;
            }

            $report[] = [
                'income_account_id' => $account['income_account_id'],
                'income_account_name' => $account['income_account_name'],
                'month_opening_balance' => $month_opening_balance,
                'month_income' => $month_income,
                'month_expense' => $month_expense,
            ];
        }

        return $report;
    }

    public function createFinancialReport($financial_report_date, $office_id)
    {
        // Check if MFR exists
        $budgetLibrary = new BudgetLibrary();

        $initial_status = $this->statusLibrary->initialItemStatus('financial_report');

        $financial_report_date = date('Y-m-01', strtotime($financial_report_date));

        // Check if a journal for the same month and FCP exists
        $builder = $this->write_db->table("financial_report");
        $builder->where(array('fk_office_id' => $office_id, 'financial_report_month' => $financial_report_date));
        $count_financial_report = $builder->get()->getNumRows();

        if ($count_financial_report == 0) {
            $new_mfr['financial_report_month'] = $financial_report_date;
            $new_mfr['fk_office_id'] = $office_id;
            $new_mfr['fk_status_id'] = $initial_status; //$this->grants->initial_item_status('financial_report');

            $new_mfr_to_insert = $this->mergeWithHistoryFields('financial_report', $new_mfr);

            $this->write_db->table("financial_report")->insert($new_mfr_to_insert);

            $report_id = $this->write_db->insertId();

            $current_budget = $budgetLibrary->getBudgetByOfficeCurrentTransactionDate($office_id);

            // Update the budget id for the newly created MFR
            $update_data['fk_budget_id'] = $current_budget['budget_id'];

            $builder = $this->write_db->table('financial_report');
            $builder->where(array('financial_report_id' => $report_id));
            $builder->update($update_data);
        }
    }

    function incomeAccounts($office_ids, $project_ids = [], $office_bank_ids = [])
    {
        // Array of account system
        $builder = $this->read_db->table("office");
        $builder->select('fk_account_system_id');
        $builder->whereIn('office_id', $office_ids);
        $office_account_system_ids = $builder->get()->getResultArray();

        $builder = $this->read_db->table("income_account");

        if (count($project_ids) > 0) {
            $builder->whereIn('project.project_id', $project_ids);
            $builder->join('project_income_account', 'project_income_account.fk_income_account_id=income_account.income_account_id');
            $builder->join('project', 'project.project_id=project_income_account.fk_project_id');
        }

        if (count($office_bank_ids) > 0) {
            $builder->join('project_income_account', 'project_income_account.fk_income_account_id=income_account.income_account_id');
            $builder->join('project', 'project.project_id=project_income_account.fk_project_id');

            $builder->join('project_allocation', 'project_allocation.fk_project_id=project.project_id');
            $builder->join('office_bank_project_allocation', 'office_bank_project_allocation.fk_project_allocation_id=project_allocation.project_allocation_id');
            $builder->whereIn('fk_office_bank_id', $office_bank_ids);
        }

        $builder->whereIn('income_account.fk_account_system_id', array_column($office_account_system_ids, 'fk_account_system_id'));
        $builder->groupBy(array('income_account_id'));
        $result = $builder->select(array('income_account_id', 'income_account_name', 'income_account_is_budgeted'))
            ->get()->getResultArray();

        return $result;
    }

    function monthIncomeOpeningBalance($office_ids, $start_date_of_month, $project_ids = [], $office_bank_ids = [])
    {
        $statusLibrary = new \App\Libraries\Core\StatusLibrary();

        $max_approval_status_ids = $statusLibrary->getMaxApprovalStatusId('voucher', $office_ids);
        $initial_account_opening_balance = $this->initialOpeningAccountBalance($office_ids, $project_ids, $office_bank_ids);
        $account_last_month_income_to_date = $this->getAccountLastMonthIncomeToDate($office_ids, $start_date_of_month, $max_approval_status_ids, $project_ids, $office_bank_ids);
        $account_last_month_expense_to_date = $this->getAccountLastMonthExpenseToDate($office_ids, $start_date_of_month, $max_approval_status_ids, $project_ids, $office_bank_ids);
        $income_account_ids = array_unique(array_merge(array_keys($initial_account_opening_balance), array_keys($account_last_month_income_to_date), array_keys($account_last_month_expense_to_date)));

        $account_opening_balance = [];

        foreach ($income_account_ids as $income_account_id) {
            $opening = $initial_account_opening_balance[$income_account_id] ?? 0;
            $income = $account_last_month_income_to_date[$income_account_id] ?? 0;
            $expense = $account_last_month_expense_to_date[$income_account_id] ?? 0;

            $account_opening_balance[$income_account_id] = $opening  + ($income - $expense);
        }

        return $account_opening_balance;
    }

    function monthIncomeAccountReceipts($office_ids, $start_date_of_month, $project_ids = [], $office_bank_ids = [])
    {

        $income_accounts = $this->incomeAccounts($office_ids, $project_ids);

        $month_income = [];

        $bank_to_bank_contra_receipts = $this->bankToBankContraReceipts($office_bank_ids, $start_date_of_month);
        $bank_to_bank_contra_contributions = $this->bankToBankContraContributions($office_bank_ids, $start_date_of_month);
        $account_month_income = $this->getAccountMonthIncome($office_ids, $start_date_of_month, $project_ids, $office_bank_ids);

        foreach ($income_accounts as $income_account) {
            $month_income[$income_account['income_account_id']] = isset($account_month_income[$income_account['income_account_id']]) ? $account_month_income[$income_account['income_account_id']] : 0;

            if (isset($this->bankToBankContraReceipts($office_bank_ids, $start_date_of_month)[$income_account['income_account_id']])) {
                $month_income[$income_account['income_account_id']] = $month_income[$income_account['income_account_id']] + $bank_to_bank_contra_receipts[$income_account['income_account_id']];
            }

            if (isset($this->bankToBankContraContributions($office_bank_ids, $start_date_of_month)[$income_account['income_account_id']])) {
                $month_income[$income_account['income_account_id']] = $month_income[$income_account['income_account_id']] - $bank_to_bank_contra_contributions[$income_account['income_account_id']];
            }
        }

        return $month_income;
    }

    function monthIncomeAccountExpenses($office_ids, $start_date_of_month, $project_ids = [], $office_bank_ids = [])
    {

        $income_accounts = $this->incomeAccounts($office_ids, $project_ids);
        $expense_income = [];
        $income_account_month_expense = $this->getIncomeAccountMonthExpense($office_ids, $start_date_of_month, $project_ids, $office_bank_ids);

        foreach ($income_accounts as $income_account) {
            $expense_income[$income_account['income_account_id']] = isset($income_account_month_expense[$income_account['income_account_id']]) ? $income_account_month_expense[$income_account['income_account_id']] : 0;
        }

        return $expense_income;
    }

    function initialOpeningAccountBalance($office_ids, $project_ids = [], $office_bank_ids = [])
    {
        $account_opening_balance = [];

        $builder = $this->read_db->table('system_opening_balance');
        $builder->select(array('opening_fund_balance.fk_income_account_id as fk_income_account_id'));
        $builder->selectSum('opening_fund_balance_amount');
        $builder->join('opening_fund_balance', 'opening_fund_balance.fk_system_opening_balance_id=system_opening_balance.system_opening_balance_id');

        if (count($office_bank_ids) > 0) {
            $builder->whereIn('opening_fund_balance.fk_office_bank_id', $office_bank_ids);
        }

        if (count($project_ids) > 0) {
            $builder->whereIn('project.project_id', $project_ids);
            $builder->join('income_account', 'income_account.income_account_id=opening_fund_balance.fk_income_account_id');
            $builder->join('project_income_account', 'project_income_account.fk_income_account_id=income_account.income_account_id');
            $builder->join('project', 'project.project_id=project_income_account.fk_project_id');
        }

        $builder->groupBy(array('fk_income_account_id'));
        $builder->whereIn('system_opening_balance.fk_office_id', $office_ids);
        $initial_account_opening_balance_obj = $builder->get();


        if ($initial_account_opening_balance_obj->getNumRows() > 0) {
            $account_opening_balance_array = $initial_account_opening_balance_obj->getResultArray();

            foreach ($account_opening_balance_array as $row) {
                $account_opening_balance[$row['fk_income_account_id']] = $row['opening_fund_balance_amount'];
            }
        }

        return $account_opening_balance;
    }

    function getAccountLastMonthIncomeToDate($office_ids, $start_date_of_month, $max_approval_status_ids, $project_ids = [], $office_bank_ids = [])
    {

        $previous_months_income_to_date = [];

        $builder = $this->read_db->table("monthly_sum_income_per_center");
        $builder->select(array('income_account_id'));
        $builder->selectSum('amount');
        $builder->whereIn('fk_office_id', $office_ids);

        if (!empty($office_bank_ids)) {
            $builder->whereIn('fk_office_bank_id', $office_bank_ids);
        }

        $builder->where(array('voucher_month < ' => $start_date_of_month));
        $builder->groupBy(array('income_account_id'));
        $builder->whereIn('fk_status_id', $max_approval_status_ids);
        $previous_months_income_obj = $builder->get();

        if ($previous_months_income_obj->getNumRows() > 0) {
            $previous_months_income_to_date_arr = $previous_months_income_obj->getResultArray();

            foreach ($previous_months_income_to_date_arr as $row) {
                $previous_months_income_to_date[$row['income_account_id']] = $row['amount'];
            }
        }

        return $previous_months_income_to_date;
    }

    function getAccountLastMonthExpenseToDate($office_ids, $start_date_of_month, $max_approval_status_ids, $project_ids = [], $office_bank_ids = [])
    {

        $previous_months_expense_to_date = [];

        $builder = $this->read_db->table("monthly_sum_income_expense_per_center");
        $builder->select(array('income_account_id'));
        $builder->selectSum('amount');

        $builder->whereIn('fk_office_id', $office_ids);

        if (!empty($office_bank_ids)) {
            $builder->whereIn('fk_office_bank_id', $office_bank_ids);
        }

        $builder->where(array('voucher_month < ' => $start_date_of_month));
        $builder->groupBy(array('income_account_id'));
        $builder->whereIn('fk_status_id', $max_approval_status_ids);
        $previous_months_expense_obj = $builder->get();

        if ($previous_months_expense_obj->getNumRows() > 0) {
            $previous_months_expense_to_date_arr = $previous_months_expense_obj->getResultArray();

            foreach ($previous_months_expense_to_date_arr as $row) {
                $previous_months_expense_to_date[$row['income_account_id']] = $row['amount'];
            }
        }

        return $previous_months_expense_to_date;
    }

    function getAccountMonthIncome($office_ids, $start_date_of_month, $project_ids = [], $office_bank_ids = [])
    {

        $statusLibrary = new StatusLibrary();

        $max_approval_status_ids = $statusLibrary->getMaxApprovalStatusId('voucher', $office_ids);
        $month_income = [];

        $builder = $this->read_db->table('monthly_sum_income_per_center');
        $builder->select(array('income_account_id'));
        $builder->selectSum('amount');

        if (count($project_ids) > 0) {
            $builder->whereIn('fk_project_id', $project_ids);
        }

        if (count($office_bank_ids) > 0) {
            $builder->whereIn('fk_office_bank_id', $office_bank_ids);
        }

        $builder->whereIn('fk_status_id', $max_approval_status_ids);
        $builder->whereIn('fk_office_id', $office_ids);
        $condition_array = array(
            'voucher_month' => $start_date_of_month,
        );
        $builder->where($condition_array);
        $builder->groupBy(array('income_account_id'));
        $month_income_obj = $builder->get();

        if ($month_income_obj->getNumRows() > 0) {
            $month_income_arr = $month_income_obj->getResultArray();
            foreach ($month_income_arr as $row) {
                $month_income[$row['income_account_id']] = $row['amount'];
            }
        }
        return $month_income;
    }

    function getIncomeAccountMonthExpense($office_ids, $start_date_of_month, $project_ids = [], $office_bank_ids = [])
    {
        $statusLibrary = new StatusLibrary();
        $expense_income = [];
        $max_approval_status_ids = $statusLibrary->getMaxApprovalStatusId('voucher', $office_ids);

        $builder = $this->read_db->table('monthly_sum_income_expense_per_center');
        $builder->select(array('income_account_id'));
        $builder->selectSum('amount');
        $builder->whereIn('fk_status_id', $max_approval_status_ids);

        if (count($office_bank_ids) > 0) {
            $builder->whereIn('fk_office_bank_id', $office_bank_ids);
        }

        $builder->where(array('voucher_month' => $start_date_of_month));
        $builder->groupBy(array('income_account_id'));
        $builder->whereIn('fk_office_id', $office_ids);
        $expense_income_obj = $builder->get();


        if ($expense_income_obj->getNumRows() > 0) {
            $expense_income_arr = $expense_income_obj->getResultArray();

            foreach ($expense_income_arr as $row) {
                $expense_income[$row['income_account_id']] = $row['amount'];
            }
        }

        return $expense_income;
    }

     function listClearedEffects($office_ids, $reporting_month, $transaction_type, $contra_type, $voucher_type_account_code, $project_ids = [], $office_bank_ids = [])
     {
        
 
         if (count($project_ids) > 0) {
            $builder = $this->read_db->table('office_bank');
            $builder->select(array('office_bank.office_bank_id'));
            $builder->join('office_bank_project_allocation', 'office_bank_project_allocation.fk_office_bank_id=office_bank.office_bank_id');
            $builder->join('project_allocation', 'project_allocation.project_allocation_id=office_bank_project_allocation.fk_project_allocation_id');
            $builder->whereIn('fk_project_id', $project_ids);
            $office_bank_ids = array_column($builder->get()->getResultArray(), 'office_bank_id');
         }
 
        //  if (!empty($office_bank_ids)) {
        //      $builder->whereIn('office_bank_id', $office_bank_ids);
        //  }
 
 
         $list_cleared_effects = [];
         $builder2 = $this->read_db->table('voucher_detail');
         //return 145890.00;
         //$cleared_condition = " `voucher_cleared` = 1 AND `voucher_cleared_month` = '".date('Y-m-t',strtotime($reporting_month))."' ";
         $builder2->selectSum('voucher_detail_total_cost');
 
         $builder2->groupStart();
           $builder2->where(array('voucher_type_is_hidden' => 0));
           $builder2->orWhere(['voucher_type_is_hidden' => 2]);//Voided cheque
         $builder2->groupEnd();
 
         $builder2->select(array(
             'voucher_id', 'voucher_number', 'voucher_cheque_number', 'voucher_description','voucher_vendor',
             'voucher_cleared', 'office_code', 'office_name', 'voucher_date', 'voucher_cleared',
             'office_bank_id', 'office_bank_name', 'voucher_is_reversed','voucher_type_name'
         ));
         $builder2->groupBy('voucher_id');
         $builder2->whereIn('voucher.fk_office_id', $office_ids);
         
         //$this->read_db->where_in('voucher.fk_office_id', $office_ids);
 
     
         if ($voucher_type_account_code == 'bank' && $transaction_type == 'income') {
             $cond_string = "((voucher_type_account_code = 'bank' AND  voucher_type_effect_code = '" . $transaction_type . "') OR (voucher_type_account_code = 'cash' AND  voucher_type_effect_code = 'cash_contra'))";
             $builder2->where($cond_string);
         } elseif ($voucher_type_account_code == 'bank' && $transaction_type == 'expense') {
             $cond_string = "((voucher_type_account_code = 'bank' AND  voucher_type_effect_code = '" . $transaction_type . "') OR (voucher_type_account_code = 'bank' AND  voucher_type_effect_code = 'bank_contra') OR (voucher_type_account_code = 'bank' AND  voucher_type_effect_code = 'bank_refund'))";
             $builder2->where($cond_string);
         } elseif ($voucher_type_account_code == 'cash' && $transaction_type == 'income') {
             $cond_string = "((voucher_type_account_code = 'cash' AND  voucher_type_effect_code = '" . $transaction_type . "') OR (voucher_type_account_code = 'bank' AND  voucher_type_effect_code = 'bank_contra'))";
             $builder2->where($cond_string);
         } elseif ($voucher_type_account_code == 'cash' && $transaction_type == 'expense') {
             $cond_string = "((voucher_type_account_code = 'cash' AND  voucher_type_effect_code = '" . $transaction_type . "') OR (voucher_type_account_code = 'cash' AND  voucher_type_effect_code = 'cash_contra'))";
             $builder2->where($cond_string);
         }
         
         //$this->read_db->where(array('voucher_cleared' => 1 , 'voucher_date<=' => date('Y-m-t', strtotime($reporting_month)), 'voucher_cleared_month' => date('Y-m-t', strtotime($reporting_month))));
         $builder2->where(['voucher_cleared' => 1]);
         $builder2->where(['voucher_date<=' => date('Y-m-t', strtotime($reporting_month))]);
         $builder2->where(['voucher_cleared_month' => date('Y-m-t', strtotime($reporting_month))]);
 
         $builder2->join('voucher', 'voucher.voucher_id=voucher_detail.fk_voucher_id');
         $builder2->join('office', 'office.office_id=voucher.fk_office_id');
         $builder2->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
         $builder2->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
         $builder2->join('voucher_type_account', 'voucher_type_account.voucher_type_account_id=voucher_type.fk_voucher_type_account_id');
         $builder2->join('office_bank', 'office_bank.office_bank_id=voucher.fk_office_bank_id');
 
         if (count($project_ids) > 0) {
             $builder2->whereIn('voucher.fk_office_bank_id', $office_bank_ids);
         }
 
         $list_cleared_effects = $builder2->get()->getresultArray();
 
         if ($transaction_type == 'expense') {
             $list_cleared_effects = array_merge($list_cleared_effects, $this->getUnclearedAndClearedOpeningOutstandingCheques($office_ids, $reporting_month, 'cleared', $office_bank_ids));
         } else {
             $list_cleared_effects = array_merge($list_cleared_effects, $this->getUnclearedAndClearedDepositInTransit($office_ids, $reporting_month, $office_bank_ids, 'cleared'));
         }
 
         return $list_cleared_effects;
     }

     function monthExpenseByExpenseAccount($office_ids, $reporting_month, $project_ids = [], $office_bank_ids = [])
     {
 
         $max_approval_status_ids = $this->statusLibrary->getMaxApprovalStatusId('voucher', $office_ids);
 
         $start_date_of_reporting_month = date('Y-m-01', strtotime($reporting_month));
         $end_date_of_reporting_month = date('Y-m-t', strtotime($reporting_month));
         $get_office_bank_project_allocation = $this->getOfficeBankProjectAllocation($office_bank_ids);
 
         $builder = $this->read_db->table('voucher_detail');
         $builder->selectSum('voucher_detail_total_cost');
         $builder->select(array('income_account_id', 'expense_account_id'));
         $builder->groupBy('expense_account_id');
         $builder->whereIn('voucher.fk_office_id', $office_ids);
         $builder->where(array('voucher_date>=' => $start_date_of_reporting_month,
             'voucher_date<=' => $end_date_of_reporting_month
         ));
         $builder->whereIn('voucher_type_effect_code', ['expense', 'bank_refund','disbursements','prepayments']);
 
         $builder->join('voucher', 'voucher.voucher_id=voucher_detail.fk_voucher_id');
         $builder->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
         $builder->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
         $builder->join('expense_account', 'expense_account.expense_account_id=voucher_detail.fk_expense_account_id');
         $builder->join('income_account', 'income_account.income_account_id=expense_account.fk_income_account_id');
 
         if (count($project_ids) > 0) {
             $builder->whereIn('fk_project_id', $project_ids);
             $builder->join('project_allocation', 'project_allocation.project_allocation_id=voucher_detail.fk_project_allocation_id');
         }
 
         if (!empty($office_bank_ids)) {
             $builder->groupStart();
             $builder->whereIn('voucher.fk_office_bank_id', $office_bank_ids);
             $builder->whereIn('voucher_detail.fk_project_allocation_id', $get_office_bank_project_allocation);
             $builder->groupEnd();
         }
 
         $builder->whereIn('voucher.fk_status_id', $max_approval_status_ids);
 
         $result = $builder->get();
         
 
         $order_array = [];
 
         if ($result->getNumRows() > 0) {
             $rows = $result->getResultArray();
 
             foreach ($rows as $record) {
                 $order_array[$record['income_account_id']][$record['expense_account_id']] = $record['voucher_detail_total_cost'];
             }
         }
 
         return $order_array;
     }

     function expenseToDateByExpenseAccount($office_ids, $reporting_month, $project_ids = [], $office_bank_ids = [])
     {
 
         $custom_financial_year = $this->customFinancialYearLibrary->getDefaultCustomFinancialYearIdByOffice($office_ids[0], true);
 
         // $max_approval_status_ids = $this->general_model->get_max_approval_status_id('voucher');
         $max_approval_status_ids = $this->statusLibrary->getMaxApprovalStatusId('voucher', $office_ids);
         
         $fy_start_date = fy_start_date($reporting_month, $custom_financial_year);
         $end_date_of_reporting_month = date('Y-m-t', strtotime($reporting_month));
         $get_office_bank_project_allocation = $this->getOfficeBankProjectAllocation($office_bank_ids);
 
         $builder = $this->read_db->table('voucher_detail');

         $builder->selectSum('voucher_detail_total_cost');
         $builder->select(array('income_account_id', 'expense_account_id'));
         $builder->groupBy('expense_account_id');
         $builder->whereIn('voucher.fk_office_id', $office_ids);
         $builder->where(array('voucher_date>=' => $fy_start_date,
             'voucher_date<=' => $end_date_of_reporting_month
         ));
         
         $builder->whereIn('voucher_type_effect_code', ['expense','bank_refund','disbursements','prepayments']);
 
         $builder->join('voucher', 'voucher.voucher_id=voucher_detail.fk_voucher_id');
         $builder->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
         $builder->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
         $builder->join('expense_account', 'expense_account.expense_account_id=voucher_detail.fk_expense_account_id');
         $builder->join('income_account', 'income_account.income_account_id=expense_account.fk_income_account_id');
 
         if (count($project_ids) > 0) {
             $builder->whereIn('fk_project_id', $project_ids);
             $builder->join('project_allocation', 'project_allocation.project_allocation_id=voucher_detail.fk_project_allocation_id');
         }
 
         if (!empty($office_bank_ids)) {
             $builder->groupStart();
             $builder->whereIn('voucher.fk_office_bank_id', $office_bank_ids);
             $builder->whereIn('voucher_detail.fk_project_allocation_id', $get_office_bank_project_allocation);
             $builder->groupEnd();
         }
 
         $builder->whereIn('voucher.fk_status_id', $max_approval_status_ids);
 
         $result = $builder->get();
 
         $order_array = [];
 
         if ($result->getNumRows() > 0) {
             $rows = $result->getResultArray();
 
             foreach ($rows as $record) {
                 $order_array[$record['income_account_id']][$record['expense_account_id']] = $record['voucher_detail_total_cost'];
             }
         }
 
         return $order_array;
     }

     function budgetToDateByExpenseAccount($office_ids, $reporting_month, $project_ids = [], $office_bank_ids = []){

        $max_approval_status_ids = $this->statusLibrary->getMaxApprovalStatusId('budget_item', $office_ids);

        $budget_ids = [];

        $builder = $this->read_db->table('financial_report');
        $builder->select(array('fk_budget_id'));
        $builder->whereIn('fk_office_id', $office_ids);
        $builder->where(array('financial_report_month' => date('Y-m-01',strtotime($reporting_month))));
        $financial_report_obj = $builder->get();

        if($financial_report_obj->getNumRows() > 0){
            $budget_ids = array_column($financial_report_obj->getResultArray(),'fk_budget_id');
        }

        $month_number = date('m', strtotime($reporting_month));
        $custom_financial_year = $this->customFinancialYearLibrary->getDefaultCustomFinancialYearIdByOffice($office_ids[0], true);
        $month_list = year_month_order($custom_financial_year);
        
        // log_message('error', json_encode($month_list));
        // log_message('error', json_encode($reporting_month));
        $current_month = date('m', strtotime($reporting_month));
        // log_message('error', json_encode($current_month));

        $listed_months = [];

        for($i = 0; $i < count($month_list); $i++){
            $listed_months[$i] = $month_list[$i];
            if($month_list[$i] == $month_number){
                break;
            }
        }

        $get_office_bank_project_allocation = $this->getOfficeBankProjectAllocation($office_bank_ids);

        $builder2 = $this->read_db->table('budget_item_detail');
        $builder2->selectSum('budget_item_detail_amount');
        $builder2->select(array(
            'month_number',
            'income_account.income_account_id as income_account_id',
            'expense_account.expense_account_id as expense_account_id'
        ));

        $builder2->groupBy('month_number,expense_account.expense_account_id');
        $builder2->whereIn('budget.fk_office_id', $office_ids);

        // $this->read_db->where(array('month_order<=' => $month_order));
        $builder2->whereIn('month_id',  $listed_months);
        
        $builder2->whereIn('budget_id', $budget_ids);

        $builder2->join('budget_item', 'budget_item.budget_item_id=budget_item_detail.fk_budget_item_id');
        $builder2->join('budget', 'budget.budget_id=budget_item.fk_budget_id');
        $builder2->join('month', 'month.month_id=budget_item_detail.fk_month_id');
        $builder2->join('expense_account', 'expense_account.expense_account_id=budget_item.fk_expense_account_id');
        $builder2->join('income_account', 'income_account.income_account_id=expense_account.fk_income_account_id');

        if (count($project_ids) > 0) {
            $builder2->whereIn('project_allocation.fk_project_id', $project_ids);
            $builder2->join('project_allocation', 'project_allocation.project_allocation_id=budget_item.fk_project_allocation_id');
        }

        if (!empty($office_bank_ids)) {
            $builder2->whereIn('budget_item.fk_project_allocation_id', $get_office_bank_project_allocation);
        }


        $builder2->whereIn('budget_item.fk_status_id', $max_approval_status_ids);

        $result = $builder2->get();

        $order_array = [];

        if ($result->getNumRows() > 0) {
            $rows = $result->getResultArray();
            foreach ($rows as $record) {
                $order_array[$record['income_account_id']][$record['month_number']][$record['expense_account_id']] = $record['budget_item_detail_amount'];
            }
        }
        
        // log_message('error', json_encode($order_array));
        // return $order_array;

        $rst = [];
        foreach($order_array as $income_account_id => $expense_month_listing){
            $rst['month'][$income_account_id] = [];
            $rst['to_date'][$income_account_id] = [];
            foreach($expense_month_listing as $listed_month_number => $expense_budget){
                if($month_number == $listed_month_number){
                    $rst['month'][$income_account_id] = $expense_budget;
                }
                foreach($expense_budget as $expense_account_id => $month_expense_budget_amount){
                    $rst['to_date'][$income_account_id][$expense_account_id][] = $month_expense_budget_amount;
                }
            }
        }

        if(!empty($rst['to_date'])){
            foreach($rst['to_date'] as $income_account_id => $expense_budget){
                foreach($expense_budget as $expense_account_id => $budget_items){
                    $rst['to_date'][$income_account_id][$expense_account_id] = array_sum($budget_items);
                }
            }
        }
        
        return $rst;
    }

    function getMonthActiveProjects($office_ids, $reporting_month, $show_active_only = false)
    {

        $date_condition_string = "(project_end_date >= '" . $reporting_month . "' OR  project_allocation_extended_end_date >= '" . $reporting_month . "')";
        $builder = $this->read_db->table('project');
        $builder->select(array('project_id', 'project_name'));

        if ($show_active_only) {
            $builder->where($date_condition_string);
        }

        $builder->whereIn('fk_office_id', $office_ids);
        $builder->join('project_allocation', 'project_allocation.fk_project_id=project.project_id');
        $projects = $builder->get()->getResultArray();

        return $projects;
    }

    function getOfficeBanks($office_ids, $project_ids = [], $office_bank_ids = []){

        $builder = $this->read_db->table('office_bank_project_allocation');
        $builder->select(array('DISTINCT(office_bank_id)', 'office_bank_name'));
        $builder->whereIn('fk_office_id' ,$office_ids);
        $builder->join('office_bank', 'office_bank.office_bank_id=office_bank_project_allocation.fk_office_bank_id');

        if (!empty($office_bank_ids)) {
            $builder->whereIn('fk_office_bank_id', $office_bank_ids);
        }

        $office_banks = $builder->get()->getResultArray();
        
    
        return $office_banks;
    }

    function getOpeningOustandingCheque($cheque_id)
    {
        $builder = $this->read_db->table('opening_outstanding_cheque');
        $builder->select(array('opening_outstanding_cheque_amount','opening_outstanding_cheque_id','opening_outstanding_cheque_bounced_flag','opening_outstanding_cheque_description','opening_outstanding_cheque_is_cleared','opening_outstanding_cheque_cleared_date','opening_outstanding_cheque_number'));
        $builder->where(array('opening_outstanding_cheque_id' => $cheque_id));
        $bounced_chq_record = $builder->get()->getRowArray();


        return $bounced_chq_record;
    }

    function updateBankSupportFundsAndOustandingChequeOpeningBalances($office_bank_id, $cheque_id,$reporting_month, $bounce_chq)
    {
        //Get the a given selected outstanding cheque and compute the the total amount for funds
        $bounced_chq_record = $this->getOpeningOustandingCheque($cheque_id);

        
        $builder = $this->read_db->table('opening_outstanding_cheque');
        $this->read_db->transBegin();



        
        //Update Openning outstanding balance
        
        $cheque_cleared=$bounced_chq_record['opening_outstanding_cheque_is_cleared']==1?0:1;
        $bounce_flag=$bounced_chq_record['opening_outstanding_cheque_bounced_flag']==1?0:1;       
        $cheque_cleared_date = $bounced_chq_record['opening_outstanding_cheque_cleared_date']== '0000-00-00' || $bounced_chq_record['opening_outstanding_cheque_cleared_date'] == NULL ? date('Y-m-t', strtotime($reporting_month)): NULL;

        $opening_oustanding_chq_balance_data = array(
            'opening_outstanding_cheque_is_cleared'=>$cheque_cleared,
            'opening_outstanding_cheque_cleared_date' => $cheque_cleared_date,
            'opening_outstanding_cheque_bounced_flag'=>$bounce_flag
        );

        $builder->where('opening_outstanding_cheque_id', $cheque_id);
        $builder->update('opening_outstanding_cheque',  $opening_oustanding_chq_balance_data);

        $this->read_db->transComplete();

        if ($this->write_db->transStatus() == FALSE) {
            $this->write_db->transRollback();
             return false;
        } else {

            $this->write_db->transCommit();
            return true;
        }
    }

    function getOfficeBankProjectAllocation($office_bank_ids)
    {

        if (!empty($office_bank_ids)) {
            $builder = $this->read_db->table('office_bank_project_allocation');
            $builder->select(array('fk_project_allocation_id'));
            $builder->whereIn('fk_office_bank_id', $office_bank_ids);
            $result =  $builder->get()->getResultArray();

            return array_column($result, 'fk_project_allocation_id');
        } else {
            return [];
        }
    }

    public function showAddButton(): bool
    {
      return false;
    }

    function showListEditAction(array $record, array $dependancyData = []): bool
    {
      return false;
    }

    public function listTableVisibleColumns(): array
    {
      return [
                'financial_report_track_number', 
                'office_name', 'financial_report_is_submitted', 
                'financial_report_month', 
                'financial_report_submitted_date', 
                'status_name'
            ];
    }

    function pagePosition(){
        $widget['position_1']['list'] = view("financial_report/show_hide_columns");
        return $widget;
      }

      function getFundBalanceByAccount($office_id, $income_account_id,$reporting_month, $project_id = 0){
        
        $null_balances = ['month_opening_balance' => 0, 'month_income' => 0, 'month_expense' => 0];

        $fund_balance_report = $this->fundBalanceReport([$office_id],$reporting_month, [$project_id]);
        
        // log_message('error', json_encode($fund_balance_report));

        $income_account_balances = isset($fund_balance_report[$income_account_id]) ? $fund_balance_report[$income_account_id] : $null_balances;

        $income_account_month_opening_balance = $income_account_balances['month_opening_balance'];
        $income_account_month_sum_income = $income_account_balances['month_income'];
        $income_account_month_sum_expense = $income_account_balances['month_expense'];
        $income_account_month_closing_balance = $income_account_month_opening_balance + $income_account_month_sum_income - $income_account_month_sum_expense;

        return $income_account_month_closing_balance;
      }


      function fundBalanceReport($office_ids, $start_date_of_month, $project_ids = [], $office_bank_ids = [])
      {

        $income_accounts =  $this->incomeAccounts($office_ids, $project_ids, $office_bank_ids);
        //print_r($income_accounts);exit;
        $all_accounts_month_opening_balance = $this->monthIncomeOpeningBalance($office_ids, $start_date_of_month, $project_ids, $office_bank_ids);
        $all_accounts_month_income = $this->monthIncomeAccountReceipts($office_ids, $start_date_of_month, $project_ids, $office_bank_ids);
        $all_accounts_month_expense = $this->monthIncomeAccountExpenses($office_ids, $start_date_of_month, $project_ids, $office_bank_ids);
    
        $report = array();
    
        foreach ($income_accounts as $account) {
    
          $month_opening_balance = isset($all_accounts_month_opening_balance[$account['income_account_id']]) ? $all_accounts_month_opening_balance[$account['income_account_id']] : 0;
          $month_income = isset($all_accounts_month_income[$account['income_account_id']]) ? $all_accounts_month_income[$account['income_account_id']] : 0;
          $month_expense = isset($all_accounts_month_expense[$account['income_account_id']]) ? $all_accounts_month_expense[$account['income_account_id']] : 0;
    
          if ($month_opening_balance == 0 && $month_income == 0 && $month_expense == 0) {
            continue;
          }
    
          $report[$account['income_account_id']] = [
            'account_name' => $account['income_account_name'],
            'month_opening_balance' => $month_opening_balance,
            'month_income' => $month_income,
            'month_expense' => $month_expense,
          ];
        }

    
        //If the mfr has been submitted. Adjust the child support fund by taking away exact amount of bounced opening chqs This code was added during enhancement for cancelling opening outstanding chqs
    
        if ($this->checkIfFinancialReportIsSubmitted($office_ids, $start_date_of_month) == true) {
    
          $sum_of_bounced_cheques=$this->getTotalSumOfBouncedOpeningCheques($office_ids, $start_date_of_month);
    
          $total_amount_bounced=$sum_of_bounced_cheques[0]['opening_outstanding_cheque_amount'];
          $bounced_date=$sum_of_bounced_cheques[0]['opening_outstanding_cheque_cleared_date'];
          $mfr_report_month= date('Y-m-t', strtotime($start_date_of_month));
          
          if($total_amount_bounced>0 &&  $bounced_date > $mfr_report_month ){
    
            $month_opening=$report[0]['month_opening_balance'];
          
            $report[0]['month_opening_balance']=$month_opening-$total_amount_bounced;
          }
         
        }
        
        return $report;
      }

    // public function fundBalanceReport()
    // {

    //     $post = $this->request->getPost();

    //     $office_ids = [$post['office_id']];
    //     $reporting_month = $post['reporting_month'];
    //     $project_ids = [];
    //     $office_bank_ids = [];

    //     $office_banks = $this->getOfficeBanks($office_ids, $reporting_month);

    //     if (count($office_banks) > 1) {
    //         // log_message('error', json_encode($office_banks));
    //         $project_ids = isset($post['project_ids']) && $post['project_ids'] != "" ? explode(",", $post['project_ids']) : [];
    //         $office_bank_ids = isset($post['office_bank_ids']) && $post['office_bank_ids'] != "" ? explode(",", $post['office_bank_ids']) : [];
    //     }

    //     // log_message('error', json_encode($office_banks));

    //     $data['result']['fund_balance_report'] = $this->_fundBalanceReport($office_ids, $reporting_month, $project_ids, $office_bank_ids);

    //     return view('financial_report/includes/include_fund_balance_report.php', $data);
    // }

    private function _fundBalanceReport($office_ids, $start_date_of_month, $project_ids = [], $office_bank_ids = [])
  {

    // log_message('error', json_encode($office_bank_ids));
    $income_accounts =  $this->incomeAccounts($office_ids, $project_ids, $office_bank_ids);
    // log_message('error', json_encode($income_accounts));
    $all_accounts_month_opening_balance = $this->monthIncomeOpeningBalance($office_ids, $start_date_of_month, $project_ids, $office_bank_ids);
    $all_accounts_month_income = $this->monthIncomeAccountReceipts($office_ids, $start_date_of_month, $project_ids, $office_bank_ids);
    $all_accounts_month_expense = $this->monthIncomeAccountExpenses($office_ids, $start_date_of_month, $project_ids, $office_bank_ids);

    $report = array();

    $month_cancelled_opening_oustanding_cheques = $this->getMonthCancelledOpeningOutstandingCheques($office_ids, $start_date_of_month, $project_ids, $office_bank_ids);
    $past_months_cancelled_opening_oustanding_cheques = $this->getMonthCancelledOpeningOutstandingCheques($office_ids, $start_date_of_month, $project_ids, $office_bank_ids, 'past_months');

    $itr = 0;

    foreach ($income_accounts as $account) {

      $month_opening_balance = isset($all_accounts_month_opening_balance[$account['income_account_id']]) ? $all_accounts_month_opening_balance[$account['income_account_id']] : 0;
      $month_income = isset($all_accounts_month_income[$account['income_account_id']]) ? $all_accounts_month_income[$account['income_account_id']] : 0;
      $month_expense = isset($all_accounts_month_expense[$account['income_account_id']]) ? $all_accounts_month_expense[$account['income_account_id']] : 0;

      if ($month_opening_balance == 0 && $month_income == 0 && $month_expense == 0) {
        continue;
      }

      if ($itr == 0) {
        $month_opening_balance = $month_opening_balance + $past_months_cancelled_opening_oustanding_cheques;
        $month_income = $month_income + $month_cancelled_opening_oustanding_cheques;
      }

      $report[] = [
        'account_id' => $account['income_account_id'],
        'account_name' => $account['income_account_name'],
        'month_opening_balance' => $month_opening_balance,
        'month_income' => $month_income,
        'month_expense' => $month_expense,
        'month_closing_balance' => ($month_opening_balance + $month_income - $month_expense)
      ];

      $itr++;
    }

    //If the mfr has been submitted. Adjust the child support fund by taking away exact amount of bounced opening chqs This code was added during enhancement for cancelling opening outstanding chqs

    if ($this->checkIfFinancialReportIsSubmitted($office_ids, $start_date_of_month) == true) {

      $sum_of_bounced_cheques = $this->getTotalSumOfBouncedOpeningCheques($office_ids, $start_date_of_month);

      $total_amount_bounced = isset($sum_of_bounced_cheques[0]['opening_outstanding_cheque_amount']) ? $sum_of_bounced_cheques[0]['opening_outstanding_cheque_amount'] : 0;
      $bounced_date = isset($sum_of_bounced_cheques[0]['opening_outstanding_cheque_cleared_date']) ? $sum_of_bounced_cheques[0]['opening_outstanding_cheque_cleared_date'] : NULL;
      $mfr_report_month = date('Y-m-t', strtotime($start_date_of_month));

      if ($total_amount_bounced > 0 &&  $bounced_date > $mfr_report_month && sizeof($report) > 0) {

        $month_opening = $report[0]['month_opening_balance'];

        $report[0]['month_opening_balance'] = $month_opening - $total_amount_bounced;
      }

    }

    return $report;
  }

  
  function postApprovalActionEvent($event_payload):void{
    
    //log_message('error', json_encode($event_payload));
    // Check if the status is a decline step
    $status_approval_direction = 0; // Zero mean the status is a reinstating status

    $builder = $this->read_db->table('status');
    $builder->select(array('status_approval_direction'));
    $builder->join('approval_flow','approval_flow.approval_flow_id=status.fk_approval_flow_id');
    $builder->join('approve_item','approve_item.approve_item_id=approval_flow.fk_approve_item_id');
    $builder->where(array('approve_item_name' => $event_payload['item'], 'status_id' => $event_payload['post']['next_status'] ));
    $status_obj = $builder->get();
    if($status_obj->getNumRows() > 0){
        $status_approval_direction = $status_obj->getRow()->status_approval_direction;
    }

    if($status_approval_direction == -1){ // -1 mean that it is a decline status
        //Unsubmit the current financial report
        $data['financial_report_is_submitted'] = 0;
        $builder2 = $this->write_db->table('financial_report');
        $builder2->where(['financial_report_id' => $event_payload['post']['item_id']]);
        $builder2->update($data);


        // Decline subsequent submitted financial reports
        $this->declineSubsequentFinancialReports($event_payload['post']['item_id'], $event_payload['post']['next_status']);
    }
    
  }

  function declineSubsequentFinancialReports($financial_report_id, $decline_status){
    
    $builder = $this->read_db->table('financial_report');
    $builder->select(array('financial_report_month','fk_office_id'));
    $builder->Where(array('financial_report_id' => $financial_report_id));
    $financial_report_obj = $builder->get();
    
    if($financial_report_obj->getNumRows() > 0){

        $financial_report = $financial_report_obj->getRowArray(); 
        // Check if we have subsequent submit reports and unsubmit them and reset their approval status to step 1
        // $initial_item_status=$this->grants_model->initial_item_status('financial_report');
        $initial_item_status = $this->statusLibrary->initialItemStatus('financial_report');

        $subsequent_mfr_data = [
            'fk_status_id' => $initial_item_status,//$decline_status, // Immediate Decline status
            'financial_report_is_submitted' => 0,
        ];
        $builder2 = $this->write_db->table('financial_report');
        $builder2->where([
            'fk_office_id' => $financial_report['fk_office_id'], 
            'financial_report_month > ' => $financial_report['financial_report_month']
        ]);
        $builder2->whereNotIn('fk_status_id', [$initial_item_status, $decline_status]);
        $builder2->update($subsequent_mfr_data);        
    }
  }

  public function accruedBalanceReport($office_id, $reporting_month){
    $journalLibrary = new \App\Libraries\Grants\JournalLibrary();
    $monthOpeningAccrualBalance = $journalLibrary->monthOpeningAccrualBalance($office_id, $reporting_month);
    $accrualLedgers = ['receivables', 'payables', 'prepayments', 'depreciation', 'payroll_liability'];

    $balanceReport = [];
    foreach($accrualLedgers as $accrualLedger){
        if(array_key_exists($accrualLedger, $monthOpeningAccrualBalance)){
           $balanceReport[$accrualLedger]['opening'] = $monthOpeningAccrualBalance[$accrualLedger]['amount'];
           $balanceReport[$accrualLedger]['debit'] = 0;
           $balanceReport[$accrualLedger]['credit'] = 0;

           $balanceReport[$accrualLedger]['closing'] = $balanceReport[$accrualLedger]['opening'] + $balanceReport[$accrualLedger]['debit'] - $balanceReport[$accrualLedger]['credit'];
        }
    }

    return $balanceReport;
  }

  function lookupTables(): array
  {
    return ['office', 'status'];
  }

}
