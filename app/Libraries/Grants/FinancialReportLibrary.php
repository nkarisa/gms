<?php

namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\FinancialReportModel;

class FinancialReportLibrary extends GrantsLibrary
{
    protected $table;
    protected $financialReportModel;

    function __construct()
    {
        parent::__construct();

        $this->financialReportModel = new FinancialReportModel();

        $this->table = 'financial_report';
    }

    function compute_cash_at_bank($office_ids, $reporting_month, $project_ids = [], $office_bank_ids = [], $retrieve_only_max_approved = true)
    {
        $to_date_cancelled_opening_oustanding_cheques = $this->getMonthCancelledOpeningOutstandingCheques($office_ids, $reporting_month, $project_ids, $office_bank_ids, 'to_date');

        $office_ids = array_unique($office_ids); // Find out why office_ids come in duplicates

        $opening_bank_balance = $this->openingCashBalance($office_ids, $reporting_month, $project_ids, $office_bank_ids)['bank'];

        $bank_to_bank_contra_receipts = $this->bankToBankContraReceipts($office_bank_ids, $reporting_month);
        $bank_to_bank_contra_contributions = $this->bankToBankContraContributions($office_bank_ids, $reporting_month);

        $cash_transactions_to_date = $this->cashTransactionsToDate($office_ids, $reporting_month, $project_ids, $office_bank_ids, 0, $retrieve_only_max_approved);

        $bank_income_to_date = isset($cash_transactions_to_date['bank']['income']) ? $cash_transactions_to_date['bank']['income'] : 0;
        $bank_expenses_to_date = isset($cash_transactions_to_date['bank']['expense']) ? $cash_transactions_to_date['bank']['expense'] : 0;

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

            $total_amount_bounced = isset($sum_of_bounced_cheques[0]['opening_outstanding_cheque_amount']) ? $sum_of_bounced_cheques[0]['opening_outstanding_cheque_amount'] : 0;
            $bounced_date = isset($sum_of_bounced_cheques[0]['opening_outstanding_cheque_cleared_date']) ? $sum_of_bounced_cheques[0]['opening_outstanding_cheque_cleared_date'] : NULL;

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
        $statusLibrary = new \App\Libraries\Core\StatusLibrary();
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

                if (($row['voucher_type_account_code'] == 'bank' && $row['voucher_type_effect_code'] == 'income') || ($row['voucher_type_account_code'] == 'cash' && $row['voucher_type_effect_code'] == 'cash_contra')) {
                    $cash_transactions_to_date['bank']['income'] += $row['amount'];
                }

                if (($row['voucher_type_account_code'] == 'bank' && $row['voucher_type_effect_code'] == 'expense') || ($row['voucher_type_account_code'] == 'bank' && $row['voucher_type_effect_code'] == 'bank_contra')) {
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

        $builder->whereIn('system_opening_balance.fk_office_id', $office_ids);

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
            $builder->whereIn('opening_deposit_transit.fk_office_bank_id', $office_bank_ids);
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
        $bank_income_to_date = isset($cash_transactions_to_date['bank']['income']) ? $cash_transactions_to_date['bank']['income'] : 0;
        $bank_expenses_to_date = isset($cash_transactions_to_date['bank']['expense']) ? $cash_transactions_to_date['bank']['expense'] : 0;

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
        $cash_income_to_date = isset($cash_transactions_to_date['cash']['income']) ? $cash_transactions_to_date['cash']['income'] : 0;
        $cash_expenses_to_date = isset($cash_transactions_to_date['cash']['expense']) ? $cash_transactions_to_date['cash']['expense'] : 0;

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


    function monthUtilizedIncomeAccounts($office_ids, $start_date_of_month, $project_ids = [], $office_bank_ids = []){

        $income_accounts =  $this->incomeAccounts($office_ids, $project_ids, $office_bank_ids);
        
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
    $statusLibrary = new \App\Libraries\Core\StatusLibrary();
    $budgetLibrary = new BudgetLibrary();

    $initial_status = $statusLibrary->initialItemStatus('financial_report');

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

      $this->write_db->table("financial_report")->insert( $new_mfr_to_insert);

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
        $result = $builder->select(array('income_account_id', 'income_account_name','income_account_is_budgeted'))
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

        $account_opening_balance=[];
        
        foreach($income_account_ids as $income_account_id){
            $opening = isset($initial_account_opening_balance[$income_account_id]) ? $initial_account_opening_balance[$income_account_id] : 0;
            $income = isset($account_last_month_income_to_date[$income_account_id]) ? $account_last_month_income_to_date[$income_account_id] : 0;
            $expense = isset($account_last_month_expense_to_date[$income_account_id]) ? $account_last_month_expense_to_date[$income_account_id] : 0;

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

            if(isset($this->bankToBankContraReceipts($office_bank_ids, $start_date_of_month)[$income_account['income_account_id']])){
                $month_income[$income_account['income_account_id']] = $month_income[$income_account['income_account_id']] + $bank_to_bank_contra_receipts[$income_account['income_account_id']];
            }

            if(isset($this->bankToBankContraContributions($office_bank_ids, $start_date_of_month)[$income_account['income_account_id']])){
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
            
            foreach($account_opening_balance_array as $row){
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
            
            foreach($previous_months_income_to_date_arr as $row){
                $previous_months_income_to_date[$row['income_account_id']] = $row['amount'];
            }
        }

        return $previous_months_income_to_date;
    }

    function getAccountLastMonthExpenseToDate($office_ids, $start_date_of_month,$max_approval_status_ids, $project_ids = [], $office_bank_ids = [])
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
            
            foreach($previous_months_expense_to_date_arr as $row){
                $previous_months_expense_to_date[$row['income_account_id']] = $row['amount'];
            }
        }

        return $previous_months_expense_to_date;
    }

    function getAccountMonthIncome($office_ids, $start_date_of_month, $project_ids = [], $office_bank_ids = []){
        
        $statusLibrary = new \App\Libraries\Core\StatusLibrary();

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
            foreach($month_income_arr as $row){
                $month_income[$row['income_account_id']] = $row['amount'];
            }
        }
        return $month_income;
    }

    function getIncomeAccountMonthExpense($office_ids, $start_date_of_month, $project_ids = [], $office_bank_ids = [])
    {
        $statusLibrary = new \App\Libraries\Core\StatusLibrary();
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

            foreach($expense_income_arr as $row){
                $expense_income[$row['income_account_id']] = $row['amount'];
            }
        }

        return $expense_income;
    }
}