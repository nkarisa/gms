<?php
namespace App\Libraries\Grants;

use App\Libraries\System\GrantsLibrary;
use App\Models\Grants\JournalModel;
use \App\Enums\VoucherTypeEffectEnum;

class JournalLibrary extends GrantsLibrary implements \App\Interfaces\LibraryInterface
{
    protected $table;
    protected $journalModel;
    protected $OfficeCashJournal;
    protected $OfficeBankJournal;

    public function __construct()
    {
        parent::__construct();

        $this->journalModel      = new JournalModel();
        $this->OfficeCashJournal = new \App\Libraries\Grants\OfficeCashLibrary();
        $this->OfficeBankJournal = new \App\Libraries\Grants\OfficeBankLibrary();

        $this->table = 'journal';
    }

    public function getOfficeDataFromJournal($journal_id)
    {
        $builder = $this->read_db->table("office");
        $builder->select(['office_id', 'office_name', 'journal_id', 'journal_month', 'fk_account_system_id']);
        $builder->join('journal', 'journal.fk_office_id=office.office_id');
        $builder->where(['journal_id' => $journal_id]);
        $row = $builder->get()->getRow();

        return $row;
    }

    public function getVouchersOfTheMonth($office_id, $transacting_month, $journal_id, $office_bank_id = 0, $project_allocation_ids = [])
    {

        $officeBankLibrary      = new \App\Libraries\Grants\OfficeBankLibrary();
        $financialReportLibrary = new \App\Libraries\Grants\FinancialReportLibrary();
        $chequeBookLibrary      = new \App\Libraries\Grants\ChequeBookLibrary();
        $statusLibrary          = new \App\Libraries\Core\StatusLibrary();

        $active_office_banks_by_reporting_month = $officeBankLibrary->getActiveOfficeBanksByReportingMonth([$office_id], $transacting_month);

        $result = [
            'active_office_banks'               => $active_office_banks_by_reporting_month,
            'office_bank_accounts'              => $officeBankLibrary->officeBankAccounts($office_id, $office_bank_id),
            'office_has_multiple_bank_accounts' => $officeBankLibrary->officeHasMultipleBankAccounts($office_id),
            'transacting_month'                 => $transacting_month,
            'office_id'                         => $office_id,
            'office_name'                       => $this->getOfficeDataFromJournal($journal_id)->office_name,
            'navigation'                        => $this->journalNavigation($office_id, $transacting_month),
            'accounts'                          => $this->financialAccounts($office_id, $transacting_month),
            'month_opening_balance'             => $this->monthOpeningBankCashBalance($office_id, $transacting_month, $office_bank_id),
            'vouchers'                          => $this->journalRecords($office_id, $transacting_month, $project_allocation_ids, $office_bank_id),
            'mfr_submited_status'               => $financialReportLibrary->checkIfFinancialReportIsSubmitted([$office_id], $transacting_month), //Line added by ONDUSO on DEC 20 2022 for avoiding cancelling a voucher once mfr is submitted.
            'allow_skipping_of_cheque_leaves'   => $chequeBookLibrary->allowSkippingOfChequeLeaves(),
            'financial_report_max_status'       => $statusLibrary->getMaxApprovalStatusId('financial_report')[0],
        ];

        return $result;
    }

    public function journalRecords($office_id, $transacting_month, $project_allocation_ids = [], $office_bank_id = 0)
    {
        return $this->reorder_office_month_vouchers($office_id, $transacting_month, $project_allocation_ids, $office_bank_id);
    }

    public function reorder_office_month_vouchers($office_id, $transacting_month, $project_allocation_ids = [], $office_bank_id = 0)
    {

        $raw_array_of_vouchers = $this->getAllOfficeMonthVouchers($office_id, $transacting_month, $project_allocation_ids, $office_bank_id);

        $voucher_record = [];

        foreach ($raw_array_of_vouchers as $voucher_detail) {
            extract($voucher_detail);
            $voucher_record[$voucher_id] = [
                'date'                              => $voucher_date,
                'payee'                             => $voucher_vendor,
                'voucher_type_abbrev'               => $voucher_type_abbrev,
                'voucher_type_name'                 => $voucher_type_name,
                'voucher_type_cash_account'         => $voucher_type_account_code,
                'voucher_type_transaction_effect'   => $voucher_type_effect_code,
                'voucher_number'                    => $voucher_number,
                'description'                       => $voucher_description,
                'cleared'                           => $this->checkIfVoucherIsClearedInMonth($voucher_cleared, $voucher_cleared_month, $transacting_month, $voucher_type_account_code, $voucher_type_effect_code),
                'cleared_month'                     => $voucher_cleared_month,
                'cheque_number'                     => $voucher_cheque_number,
                'office_bank_id'                    => $fk_office_bank_id,
                'office_cash_id'                    => $fk_office_cash_id,
                'status_id'                         => $fk_status_id,
                'receiving_office_bank_id'          => $receiving_office_bank_id,
                'receiving_office_cash_id'          => $receiving_office_cash_id,
                'voucher_is_reversed'               => $voucher_is_reversed,
                'voucher_reversal_from'             => $voucher_reversal_from,
                'voucher_reversal_to'               => $voucher_reversal_to,
                'voucher_is_cleared'                => $voucher_cleared,
                'voucher_type_is_cheque_referenced' => $voucher_type_is_cheque_referenced,
                'spread'                            => $this->getVoucherSpread($raw_array_of_vouchers, $voucher_id),

            ];
        }

        return $voucher_record;
    }

    public function getVoucherSpread($all_voucher_details, $current_voucher_id)
    {

        $spread = [];
        $count  = 0;

        foreach ($all_voucher_details as $voucher_details) {
            extract($voucher_details);
            if ($current_voucher_id == $voucher_id) {
                if ($voucher_type_effect_code == 'income' || VoucherTypeEffectEnum::RECEIVABLES->getCode() || VoucherTypeEffectEnum::RECEIVABLES_PAYMENTS->getCode()) {
                    $spread[$count]['account_id'] = $fk_income_account_id;
                } elseif ($voucher_type_effect_code == 'bank_contra' || $voucher_type_effect_code == 'cash_contra') {
                    $spread[$count]['account_id'] = $fk_contra_account_id;
                } else {
                    $spread[$count]['account_id'] = $fk_expense_account_id;
                }

                $spread[$count]['transacted_amount'] = $voucher_detail_total_cost;
                $count++;
            }
        }

        return $spread;
    }

    private function checkIfVoucherIsClearedInMonth($voucher_cleared, $voucher_cleared_month, $transacting_month, $voucher_type_account_code, $voucher_type_effect_code)
    {
        $is_cleared = false;

        if (
            ($voucher_cleared &&
                (strtotime(date('Y-m-01', strtotime($voucher_cleared_month))) <= strtotime(date('Y-m-01', strtotime($transacting_month)))))
            ||
            (
                (! strpos($voucher_type_effect_code, 'contra') && $voucher_type_account_code !== 'bank'))
        ) {
            $is_cleared = true;
        }

        return $is_cleared;
    }

    public function getAllOfficeMonthVouchers($office_id, $transacting_month, $project_allocation_ids = [], $office_bank_id = 0)
    {

        $statusLibrary = new \App\Libraries\Core\StatusLibrary();
        $result        = [];

        if (
            (count($project_allocation_ids) > 0 && $office_bank_id > 0) ||
            (count($project_allocation_ids) == 0 && $office_bank_id == 0) ||
            (count($project_allocation_ids) == 0 && $office_bank_id > 0)
        ) {

            $month_start_date        = date('Y-m-01', strtotime($transacting_month));
            $month_end_date          = date('Y-m-t', strtotime($transacting_month));
            $max_approval_status_ids = $statusLibrary->getMaxApprovalStatusId('voucher');

            $builder = $this->read_db->table("voucher");
            $builder->whereIn('voucher.fk_status_id', $max_approval_status_ids);

            $builder->select([
                'voucher_id',
                'voucher_number',
                'voucher_date',
                'voucher_vendor',
                'voucher_cleared',
                'voucher_cleared_month',
                'voucher_cheque_number',
                'voucher_description',
                'voucher_cleared_month',
                'voucher.fk_status_id as fk_status_id',
                'voucher_created_date',
                'voucher_is_reversed',
                'voucher_cleared',
                'voucher_cleared_month',
                'voucher_reversal_from',
                'voucher_reversal_to',
                'voucher_type_is_cheque_referenced',
            ]);
            $builder->select(['voucher_type_abbrev', 'voucher_type_name']);
            $builder->select(['voucher_type_account_code']);
            $builder->select(['voucher_type_effect_code']);
            $builder->select([
                'voucher_detail_total_cost',
                'fk_expense_account_id',
                'fk_income_account_id',
                'fk_contra_account_id',
                'voucher.fk_office_bank_id as fk_office_bank_id',
                'voucher.fk_office_cash_id as fk_office_cash_id',
                'cash_recipient_account.fk_office_bank_id as receiving_office_bank_id',
                'cash_recipient_account.fk_office_cash_id as receiving_office_cash_id',
            ]);

            $builder->where(['voucher_date >=' => $month_start_date, 'voucher_date <=' => $month_end_date, 'fk_office_id' => $office_id]);
            $builder->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
            $builder->join('voucher_type_account', 'voucher_type_account.voucher_type_account_id=voucher_type.fk_voucher_type_account_id');
            $builder->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
            $builder->join('voucher_detail', 'voucher_detail.fk_voucher_id=voucher.voucher_id');
            $builder->join('cash_recipient_account', 'cash_recipient_account.fk_voucher_id=voucher.voucher_id', 'LEFT');

            if (count($project_allocation_ids) > 0 && $office_bank_id > 0) {
                $builder->groupStart();
                $builder->whereIn('fk_project_allocation_id', $project_allocation_ids);
                $builder->where('voucher.fk_office_bank_id', $office_bank_id);
                $builder->groupEnd();
            } elseif (count($project_allocation_ids) == 0 && $office_bank_id > 0) {
                $builder->where('voucher.fk_office_bank_id', $office_bank_id);
            }

            $result = $builder->orderBy('voucher_id', 'ASC')->get()->getResultArray();
        }

        return $result;
    }

    public function monthOpeningBankCashBalance($office_id, $transacting_month, $office_bank_id = 0)
    {

        $system_opening_bank = $this->systemOpeningBankBalance($office_id, $office_bank_id);
        $system_opening_cash = $this->systemOpeningCashBalance($office_id, $office_bank_id);
        // $system_opening_accrual = $this->systemOpeningAccrualBalance($office_id, $office_bank_id);

        $bank_to_date_income  = [];
        $bank_to_date_expense = [];
        $month_bank_opening   = [];

        $cash_to_date_income  = [];
        $cash_to_date_expense = [];
        $month_cash_opening   = [];

        foreach ($system_opening_bank as $office_bank_id_in_loop => $balance_amount) {
            $bank_to_date_income[$office_bank_id_in_loop]                = $this->getCashIncomeOrExpenseToDate($office_id, $transacting_month, 'bank', 'income', $office_bank_id_in_loop);
            $bank_to_date_expense[$office_bank_id_in_loop]               = $this->getCashIncomeOrExpenseToDate($office_id, $transacting_month, 'bank', 'expense', $office_bank_id_in_loop);
            $month_bank_opening[$office_bank_id_in_loop]['account_name'] = $system_opening_bank[$office_bank_id_in_loop]['account_name'];
            $month_bank_opening[$office_bank_id_in_loop]['amount']       = $system_opening_bank[$office_bank_id_in_loop]['amount'] + ($bank_to_date_income[$office_bank_id_in_loop] - $bank_to_date_expense[$office_bank_id_in_loop]);
        }

        foreach ($system_opening_cash as $office_cash_id => $office_cash_balance) {
            $cash_to_date_income[$office_cash_id]                = $this->getCashIncomeOrExpenseToDate($office_id, $transacting_month, 'cash', 'income', $office_bank_id, $office_cash_id);
            $cash_to_date_expense[$office_cash_id]               = $this->getCashIncomeOrExpenseToDate($office_id, $transacting_month, 'cash', 'expense', $office_bank_id, $office_cash_id);
            $month_cash_opening[$office_cash_id]['account_name'] = $office_cash_balance['account_name'];
            $month_cash_opening[$office_cash_id]['amount']       = $office_cash_balance['amount'] + ($cash_to_date_income[$office_cash_id] - $cash_to_date_expense[$office_cash_id]);
        }

        // $month_used_accrual_ledgers = ['receivables' => 100,'payables' => 200,'prepayments' => 300,'depreciation' => 400,'payroll_liability' => 500];
        // foreach ($system_opening_accrual as $accrual_id => $accrual_balance) {
        //     $accrual_to_date_debit[$accrual_id] = 0;
        //     $accrual_to_date_credit[$accrual_id] = 0;
        //     $month_accrual_opening[$accrual_id]['account_name'] = $accrual_balance['account_name'];
        //     $month_accrual_opening[$accrual_id]['amount'] = $accrual_balance['amount'] + $accrual_to_date_debit[$accrual_id] - $accrual_to_date_credit[$accrual_id];
        // }


        return [
            'bank' => $month_bank_opening, 
            'cash' => $month_cash_opening, 
            'receivables' => ['account_name' => 'receivables', 'amount' => 100], 
            'payables' => ['account_name' => 'payables', 'amount' => 200],
            'prepayments' => ['account_name' => 'prepayments', 'amount' => 300],
            'depreciation' => ['account_name' => 'depreciation', 'amount' => 400],
            'payroll_liability' => ['account_name' => 'depreciation', 'amount' => 500]
        ];
    }

    public function getOfficeBankProjectAllocation($office_bank_id)
    {
        $office_bank_project_allocations = $this->read_db->table("office_bank_project_allocation")
            ->where(['fk_office_bank_id' => $office_bank_id])
            ->get()->getResultArray();

        return $office_bank_project_allocations;
    }

    public function getCashIncomeOrExpenseToDate($office_id, $transacting_month, $cash_account, $transaction_effect, $office_bank_id = 0, $office_cash_id = 0)
    {

        $office_bank_project_allocations = $this->getOfficeBankProjectAllocation($office_bank_id);
        $office_bank_ids                 = array_unique(array_column($office_bank_project_allocations, 'fk_office_bank_id'));

        $builder = $this->read_db->table("voucher_detail");
        $builder->selectSum('voucher_detail_total_cost');
        $builder->where('voucher_date < ', date('Y-m-01', strtotime($transacting_month)));
        $builder->join('voucher', 'voucher.voucher_id=voucher_detail.fk_voucher_id');
        $builder->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
        $builder->join('voucher_type_account', 'voucher_type_account.voucher_type_account_id=voucher_type.fk_voucher_type_account_id');
        $builder->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');

        if ($office_bank_id) {
            $builder->groupStart();
            $builder->where(['fk_office_bank_id' => $office_bank_id]);
            $allocation_ids = array_column($office_bank_project_allocations, 'fk_project_allocation_id');

            if (in_array($office_bank_id, $office_bank_ids)) {
                $builder->orWhereIn('fk_project_allocation_id', $allocation_ids);
                $builder->where(['fk_office_bank_id' => $office_bank_id]);
            }

            $builder->groupEnd();
        }

        if ($office_cash_id) {
            $builder->where(['fk_office_cash_id' => $office_cash_id, 'fk_office_id' => $office_id]);
        }

        /*1: Cash income has [voucher_type_account_code of cash and a voucher_type_effect_code of income]
          OR [voucher_type_account_code of  bank and a voucher_type_effect_code of contra]

          2: Cash expense has [voucher_type_account_code of cash and a voucher_type_effect_code of expense]
          OR [voucher_type_account_code of  cash and a voucher_type_effect_code of contra]

          3: Bank income has [voucher_type_account_code of bank and a voucher_type_effect_code of income]
          OR [voucher_type_account_code of  cash and a voucher_type_effect_code of contra]

          4: Bank expense has [voucher_type_account_code of bank and a voucher_type_effect_code of expense]
          OR [voucher_type_account_code of  bank and a voucher_type_effect_code of contra]

        */

        if (($cash_account == 'cash' && $transaction_effect == 'income') || ($cash_account == 'bank' && $transaction_effect == 'bank_contra')) {
            $builder->groupStart();
            $builder->where(['voucher_type_account_code' => 'cash', 'voucher_type_effect_code' => 'income']);

            $builder->orGroupStart();
            $builder->where(['voucher_type_account_code' => 'bank', 'voucher_type_effect_code' => 'bank_contra']);
            $builder->groupEnd();

            $builder->groupEnd();
        } elseif (($cash_account == 'cash' && $transaction_effect == 'expense') || ($cash_account == 'cash' && $transaction_effect == 'cash_contra')) {

            $builder->groupStart();
            $builder->where(['voucher_type_account_code' => 'cash', 'voucher_type_effect_code' => 'expense']);

            $builder->orGroupStart();
            $builder->where(['voucher_type_account_code' => 'cash', 'voucher_type_effect_code' => 'cash_contra']);
            $builder->groupEnd();

            $builder->groupEnd();
        } elseif (($cash_account == 'bank' && $transaction_effect == 'income') || ($cash_account == 'cash' && $transaction_effect == 'cash_contra')) {

            $builder->groupStart();
            $builder->where(['voucher_type_account_code' => 'bank', 'voucher_type_effect_code' => 'income']);

            $builder->orGroupStart();
            $builder->where(['voucher_type_account_code' => 'cash', 'voucher_type_effect_code' => 'cash_contra']);
            $builder->groupEnd();

            $builder->groupEnd();
        } elseif (($cash_account == 'bank' && $transaction_effect == 'expense') || ($cash_account == 'bank' && $transaction_effect == 'bank_contra')) {

            $builder->groupStart();
            $builder->where(['voucher_type_account_code' => 'bank', 'voucher_type_effect_code' => 'expense']);

            $builder->orGroupStart();
            $builder->where(['voucher_type_account_code' => 'bank', 'voucher_type_effect_code' => 'bank_contra']);
            $builder->groupEnd();

            $builder->groupEnd();
        }

        $total_cost     = 0;
        $total_cost_obj = $builder->get();

        if ($total_cost_obj->getNumRows() > 0) {
            $total_cost = $total_cost_obj->getRow()->voucher_detail_total_cost;
        }
        return $total_cost;
    }

    private function systemOpeningCashBalance($office_id, $office_bank_id = 0)
    {

        $account_system_id = $this->getTypeNameById('office', $office_id, 'fk_account_system_id');

        $builder = $this->read_db->table('opening_cash_balance');
        if ($office_bank_id > 0) {
            $builder->where(['opening_cash_balance.fk_office_bank_id' => $office_bank_id]);
        }

        $builder->selectSum('opening_cash_balance_amount');
        $builder->groupBy('office_cash_id');
        $builder->select(['office_cash_name', 'fk_office_cash_id']);
        $builder->join('office_cash', 'office_cash.office_cash_id=opening_cash_balance.fk_office_cash_id');
        $builder->join('system_opening_balance', 'system_opening_balance.system_opening_balance_id=opening_cash_balance.fk_system_opening_balance_id');
        $petty_cash_accounts = $builder->getWhere(
            ['system_opening_balance.fk_office_id' => $office_id, 'office_cash.fk_account_system_id' => $account_system_id]
        )->getResultArray();

        $result = [];

        foreach ($petty_cash_accounts as $petty_cash_account) {
            $result[$petty_cash_account['fk_office_cash_id']]['account_name'] = $petty_cash_account['office_cash_name'];
            $result[$petty_cash_account['fk_office_cash_id']]['amount']       = $petty_cash_account['opening_cash_balance_amount'];
        }

        // Get all office cash boxes
        $builder = $this->read_db->table('office_cash');
        $builder->select(['office_cash_id', 'office_cash_name']);
        $builder->join('account_system', 'account_system.account_system_id=office_cash.fk_account_system_id');
        $builder->join('office', 'office.fk_account_system_id=account_system.account_system_id');
        $office_cash_obj = $builder->getWhere(['office_id' => $office_id]);

        if ($office_cash_obj->getNumRows() > 0) {
            $office_cash = $office_cash_obj->getResultArray();

            foreach ($office_cash as $box) {
                if (! array_key_exists($box['office_cash_id'], $result)) {
                    $result[$box['office_cash_id']]['account_name'] = $box['office_cash_name'];
                    $result[$box['office_cash_id']]['amount']       = 0;
                }
            }
        }

        return $result;
    }

    private function systemOpeningBankBalance($office_id, $office_bank_id = 0)
    {
        $balances = [];

        $builder = $this->read_db->table("opening_bank_balance");
        $builder->select(['opening_bank_balance_amount', 'office_bank_id', 'office_bank_name']);
        $builder->join('system_opening_balance', 'system_opening_balance.system_opening_balance_id=opening_bank_balance.fk_system_opening_balance_id');
        $builder->join('office_bank', 'office_bank.office_bank_id=opening_bank_balance.fk_office_bank_id');

        if ($office_bank_id > 0) {
            $builder->where(['office_bank_id' => $office_bank_id]);
        }

        $opening_bank_balance_obj = $builder->getWhere(['office_bank.fk_office_id' => $office_id, 'system_opening_balance.fk_office_id' => $office_id]);

        if ($opening_bank_balance_obj->getNumRows() > 0) {
            $opening_bank_balances = $opening_bank_balance_obj->getResultArray();

            foreach ($opening_bank_balances as $opening_bank_balance) {
                $balances[$opening_bank_balance['office_bank_id']] = ['account_name' => $opening_bank_balance['office_bank_name'], 'amount' => $opening_bank_balance['opening_bank_balance_amount']];
            }
        }

        // Get all office banks - Fill up banks without opening system balance
        $builder = $this->read_db->table("office_bank");
        $builder->select(['office_bank_id', 'office_bank_name']);
        if ($office_bank_id > 0) {
            $builder->where(['office_bank_id' => $office_bank_id]);
        }
        $office_banks_obj = $builder->getWhere(['fk_office_id' => $office_id]);

        if ($office_banks_obj->getNumRows() > 0) {
            $office_banks = $office_banks_obj->getResultArray();

            foreach ($office_banks as $office_bank) {
                if (! array_key_exists($office_bank['office_bank_id'], $balances)) {
                    $balances[$office_bank['office_bank_id']] = ['account_name' => $office_bank['office_bank_name'], 'amount' => 0];
                }
            }
        }

        return $balances;
    }

    public function journalNavigation($office_id, $transacting_month)
    {

        $prev = $this->navigateMonthJournal($office_id, $transacting_month, 'previous');
        $next = $this->navigateMonthJournal($office_id, $transacting_month, 'next');

        $prev = $prev != null ? $prev->journal_id : null;
        $next = $next != null ? $next->journal_id : null;

        return ['previous' => $prev, 'next' => $next];
    }

    private function navigateMonthJournal($office_id, $transacting_month, $direction = 'next')
    {
        $journal          = null;
        $direction_phrase = 'first day of next month';

        if ($direction == 'previous') {
            $direction_phrase = 'first day of last month';
        }

        $month       = date('Y-m-01', strtotime($direction_phrase, strtotime($transacting_month)));
        $journal_obj = $this->read_db->table("journal")->getWhere(
            ['journal_month' => $month, 'fk_office_id' => $office_id]
        );

        if ($journal_obj->getNumRows() > 0) {
            $journal = $journal_obj->getRow();
        }

        return $journal;
    }

    public function financialAccounts($office_id, $transacting_month)
    {
        $accounts = [
            'income'  => $this->monthOfficeUsedIncomeAccounts($office_id, $transacting_month),
            'expense' => $this->monthOfficeUsedExpenseAccounts($office_id, $transacting_month),
            VoucherTypeEffectEnum::RECEIVABLES->getCode() => $this->monthOfficeUsedReceivableAccounts($office_id, $transacting_month),
            VoucherTypeEffectEnum::PAYABLES->getCode() => $this->monthOfficeUsedPayableAccounts($office_id, $transacting_month),
            VoucherTypeEffectEnum::PREPAYMENTS->getCode() => $this->monthOfficeUsedPrepaymentAccounts($office_id, $transacting_month),
            VoucherTypeEffectEnum::PREPAYMENT_SETTLEMENTS->getCode() => $this->monthOfficeUsedPrepaymentAccounts($office_id, $transacting_month),
            VoucherTypeEffectEnum::DEPRECIATION->getCode() => $this->monthOfficeUsedDepreciationAccounts($office_id, $transacting_month),
            VoucherTypeEffectEnum::PAYROLL_LIABILITY->getCode() => $this->monthOfficeUsedPayrollLiabilityAccounts($office_id, $transacting_month),
        ];

        return $accounts;
    }

    private function monthOfficeUsedReceivableAccounts(int $office_id, string $transacting_month){
        $all_income_accounts = $this->incomeAccounts($office_id);

        $start_date = date('Y-m-01', strtotime($transacting_month));
        $end_date   = date('Y-m-t', strtotime($transacting_month));

        $builder = $this->read_db->table("voucher_detail");
        $builder->select(['fk_income_account_id income_account_id']);
        $builder->where([
            'voucher_date >='                        => $start_date,
            'voucher_date <='                        => $end_date,
            'voucher_detail.fk_income_account_id > ' => 0,
            'fk_office_id'                           => $office_id
        ]);
        $builder->whereIn('voucher_type_effect_code',[VoucherTypeEffectEnum::RECEIVABLES->getCode(),VoucherTypeEffectEnum::RECEIVABLES_PAYMENTS->getCode()]);
        $builder->join('voucher', 'voucher.voucher_id=voucher_detail.fk_voucher_id');
        $builder->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
        $builder->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
        $month_used_income_accounts_obj = $builder->get();

        $month_used_income_account_ids = [];

        if ($month_used_income_accounts_obj->getNumRows() > 0) {
            $month_used_income_accounts_array = $month_used_income_accounts_obj->getResultArray();
            $month_used_income_account_ids    = array_column($month_used_income_accounts_array, 'income_account_id', 'income_account_id');
        }

        $array_of_common_ids = array_intersect_key($all_income_accounts, $month_used_income_account_ids);

        return $array_of_common_ids;
    }

    private function monthOfficeUsedPayableAccounts(int $office_id, string $transacting_month){
        $all_expense_accounts = $this->expenseAccounts($office_id);

        $start_date = date('Y-m-01', strtotime($transacting_month));
        $end_date   = date('Y-m-t', strtotime($transacting_month));

        $builder = $this->read_db->table("voucher_detail");
        $builder->select(['fk_expense_account_id expense_account_id']);
        $builder->where([
            'voucher_date >='                         => $start_date,
            'voucher_date <='                         => $end_date,
            'voucher_detail.fk_expense_account_id > ' => 0,
            'fk_office_id'                            => $office_id,
        ]);
        $builder->whereIn('voucher_type_effect_code',[
            VoucherTypeEffectEnum::PAYABLES->getCode(),
            VoucherTypeEffectEnum::PAYABLE_DISBURSEMENTS->getCode(),
            // VoucherTypeEffectEnum::PREPAYMENTS->getCode(),
            // VoucherTypeEffectEnum::PREPAYMENT_SETTLEMENTS->getCode(),
            // VoucherTypeEffectEnum::DEPRECIATION->getCode(),
            // VoucherTypeEffectEnum::PAYROLL_LIABILITY->getCode(),
        ]);
        $builder->join('voucher', 'voucher.voucher_id=voucher_detail.fk_voucher_id');
        $builder->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
        $builder->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
        $month_used_expense_accounts_obj = $builder->get();

        $month_used_expense_account_ids = [];

        if ($month_used_expense_accounts_obj->getNumRows() > 0) {
            $month_used_expense_accounts_array = $month_used_expense_accounts_obj->getResultArray();
            $month_used_expense_account_ids    = array_column($month_used_expense_accounts_array, 'expense_account_id', 'expense_account_id');
        }

        $array_of_common_ids = array_intersect_key($all_expense_accounts, $month_used_expense_account_ids);
        return $array_of_common_ids;
    }

    private function monthOfficeUsedPrepaymentAccounts(int $office_id, string $transacting_month){
        $all_expense_accounts = $this->expenseAccounts($office_id);

        $start_date = date('Y-m-01', strtotime($transacting_month));
        $end_date   = date('Y-m-t', strtotime($transacting_month));

        $builder = $this->read_db->table("voucher_detail");
        $builder->select(['fk_expense_account_id expense_account_id']);
        $builder->where([
            'voucher_date >='                         => $start_date,
            'voucher_date <='                         => $end_date,
            'voucher_detail.fk_expense_account_id > ' => 0,
            'fk_office_id'                            => $office_id,
        ]);
        $builder->whereIn('voucher_type_effect_code',[
            VoucherTypeEffectEnum::PREPAYMENTS->getCode(),
            VoucherTypeEffectEnum::PREPAYMENT_SETTLEMENTS->getCode(),
            // VoucherTypeEffectEnum::DEPRECIATION->getCode(),
            // VoucherTypeEffectEnum::PAYROLL_LIABILITY->getCode(),
        ]);
        $builder->join('voucher', 'voucher.voucher_id=voucher_detail.fk_voucher_id');
        $builder->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
        $builder->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
        $month_used_expense_accounts_obj = $builder->get();

        $month_used_expense_account_ids = [];

        if ($month_used_expense_accounts_obj->getNumRows() > 0) {
            $month_used_expense_accounts_array = $month_used_expense_accounts_obj->getResultArray();
            $month_used_expense_account_ids    = array_column($month_used_expense_accounts_array, 'expense_account_id', 'expense_account_id');
        }

        $array_of_common_ids = array_intersect_key($all_expense_accounts, $month_used_expense_account_ids);
        return $array_of_common_ids;
    }

    private function monthOfficeUsedDepreciationAccounts(int $office_id, string $transacting_month){
        return [];
    }

    private function monthOfficeUsedPayrollLiabilityAccounts(int $office_id, string $transacting_month){
        return [];
    }

    public function monthOfficeUsedExpenseAccounts($office_id, $transacting_month)
    {
        $all_expense_accounts = $this->expenseAccounts($office_id);

        $start_date = date('Y-m-01', strtotime($transacting_month));
        $end_date   = date('Y-m-t', strtotime($transacting_month));

        $builder = $this->read_db->table("voucher_detail");
        $builder->select(['fk_expense_account_id expense_account_id']);
        $builder->where([
            'voucher_date >='                         => $start_date,
            'voucher_date <='                         => $end_date,
            'voucher_detail.fk_expense_account_id > ' => 0,
            'fk_office_id'                            => $office_id,
        ]);
        $builder->join('voucher', 'voucher.voucher_id=voucher_detail.fk_voucher_id');
        $month_used_expense_accounts_obj = $builder->get();

        $month_used_expense_account_ids = [];

        if ($month_used_expense_accounts_obj->getNumRows() > 0) {
            $month_used_expense_accounts_array = $month_used_expense_accounts_obj->getResultArray();
            $month_used_expense_account_ids    = array_column($month_used_expense_accounts_array, 'expense_account_id', 'expense_account_id');
        }

        $array_of_common_ids = array_intersect_key($all_expense_accounts, $month_used_expense_account_ids);
        return $array_of_common_ids;
    }

    private function expenseAccounts($office_id)
    {
        $builder = $this->read_db->table("expense_account");
        $builder->select(['expense_account_id', 'expense_account_code']);
        $builder->join('income_account', 'income_account.income_account_id=expense_account.fk_income_account_id');
        $builder->join('account_system', 'account_system.account_system_id=income_account.fk_account_system_id');
        $builder->join('office', 'office.fk_account_system_id=account_system.account_system_id');
        $builder->where(['office_id' => $office_id]);
        $accounts = $builder->get()->getResultArray();

        $ids  = array_column($accounts, 'expense_account_id');
        $code = array_column($accounts, 'expense_account_code');

        return array_combine($ids, $code);
    }

    public function monthOfficeUsedIncomeAccounts($office_id, $transacting_month)
    {
        $all_income_accounts = $this->incomeAccounts($office_id);

        $start_date = date('Y-m-01', strtotime($transacting_month));
        $end_date   = date('Y-m-t', strtotime($transacting_month));

        $builder = $this->read_db->table("voucher_detail");
        $builder->select(['fk_income_account_id income_account_id']);
        $builder->where([
            'voucher_date >='                        => $start_date,
            'voucher_date <='                        => $end_date,
            'voucher_detail.fk_income_account_id > ' => 0,
            'fk_office_id'                           => $office_id,
        ]);
        $builder->join('voucher', 'voucher.voucher_id=voucher_detail.fk_voucher_id');
        $month_used_income_accounts_obj = $builder->get();

        $month_used_income_account_ids = [];

        if ($month_used_income_accounts_obj->getNumRows() > 0) {
            $month_used_income_accounts_array = $month_used_income_accounts_obj->getResultArray();
            $month_used_income_account_ids    = array_column($month_used_income_accounts_array, 'income_account_id', 'income_account_id');
        }

        $array_of_common_ids = array_intersect_key($all_income_accounts, $month_used_income_account_ids);

        return $array_of_common_ids;
    }

    private function incomeAccounts($office_id)
    {
        $builder = $this->read_db->table("income_account");
        $builder->select(['income_account_id', 'income_account_code']);
        $builder->join('account_system', 'account_system.account_system_id=income_account.fk_account_system_id');
        $builder->join('office', 'office.fk_account_system_id=account_system.account_system_id');
        $builder->where(['office_id' => $office_id]);
        $accounts = $builder->get()->getResultArray();

        $ids  = array_column($accounts, 'income_account_id');
        $code = array_column($accounts, 'income_account_code');

        return array_combine($ids, $code);
    }

    // private function emptyJournalCells($office_id, $transacting_month, $account_type = 'income')
    // {
    //     $spread_cells       = '';
    //     $financial_accounts = $this->financialAccounts($office_id, $transacting_month);
    //     for ($i = 0; $i < count($financial_accounts[$account_type]); $i++) {
    //         $spread_cells .= "<td class='align-right'>0.00</td>";
    //     }
    //     return $spread_cells;
    // }

    // public function journalSpread($office_id, $spread, $transacting_month, $account_type = 'bank', $transaction_effect = 'income')
    // {
    //     $financial_accounts = $this->financialAccounts($office_id, $transacting_month);

    //     $accounts = match($transaction_effect){
    //         'income',
    //         VoucherTypeEffectEnum::RECEIVABLES->getCode(),
    //         VoucherTypeEffectEnum::RECEIVABLES_PAYMENTS->getCode() => $financial_accounts['income'],
    //         'expense',
    //         VoucherTypeEffectEnum::PREPAYMENT_SETTLEMENTS->getCode(),
    //         VoucherTypeEffectEnum::PAYABLES->getCode(),
    //         VoucherTypeEffectEnum::PAYABLE_DISBURSEMENTS->getCode(),
    //         VoucherTypeEffectEnum::PREPAYMENTS->getCode(),
    //         VoucherTypeEffectEnum::DEPRECIATION->getCode(),
    //         VoucherTypeEffectEnum::PAYROLL_LIABILITY->getCode() => $financial_accounts['expense'],
    //         'bank_contra' => $financial_accounts['bank_contra']??[],
    //         'cash_contra' => $financial_accounts['cash_contra']??[],
    //         'bank_to_bank_contra' => $financial_accounts['bank_to_bank_contra']??[],
    //         'cash_to_cash_contra' => $financial_accounts['cash_to_cash_contra']??[]
    //     };

    //     $spread_cells       = "";

    //     if ($transaction_effect == 'expense') {
    //         $spread_cells = "";
    //         // Fill up empty cells in spread when the account type is an expense type
    //         $spread_cells .= $this->emptyJournalCells($office_id, $transacting_month, 'income');

    //         foreach ($accounts as $account_id => $account_code) {
    //             $transacted_amount = 0;
    //             foreach ($spread as $spread_transaction) {
    //                 if (in_array($account_id, $spread_transaction) && $transaction_effect == 'expense') {
    //                     $transacted_amount += $spread_transaction['transacted_amount'];
    //                 }
    //             }
    //             $spread_cells .= "<td class='align-right spread_" . $transaction_effect . " spread_" . $transaction_effect . "_" . $account_id . "'>" . number_format($transacted_amount, 2) . "</td>";
    //         }

    //     } elseif ($transaction_effect == 'income') {
    //         $spread_cells = "";
    //         foreach ($accounts as $account_id => $account_code) {
    //             $transacted_amount = 0;
    //             foreach ($spread as $spread_transaction) {
    //                 if (in_array($account_id, $spread_transaction) && $transaction_effect == 'income') {
    //                     $transacted_amount += $spread_transaction['transacted_amount'];
    //                 }
    //             }

    //             $spread_cells .= "<td class='align-right spread_" . $transaction_effect . " spread_" . $transaction_effect . "_" . $account_id . "'>" . number_format($transacted_amount, 2) . "</td>";
    //         }
    //         // Fill up empty cells in spread when the account type is an income type
    //         $spread_cells .= $this->emptyJournalCells($office_id, $transacting_month, 'expense');
        
    //     } elseif ($transaction_effect == 'cash_contra' || $transaction_effect == 'bank_contra' || $transaction_effect == 'bank_to_bank_contra' || $transaction_effect == 'cash_to_cash_contra') {

    //         $spread_cells = "";
    //         $spread_cells .= $this->emptyJournalCells($office_id, $transacting_month, 'income');
    //         $spread_cells .= $this->emptyJournalCells($office_id, $transacting_month, 'expense');

    //     } elseif ($transaction_effect == VoucherTypeEffectEnum::RECEIVABLES->getCode()) {
    //         $spread_cells = "";
    //         foreach ($accounts as $account_id => $account_code) {
    //             $transacted_amount = 0;
    //             foreach ($spread as $spread_transaction) {
    //                 if (in_array($account_id, $spread_transaction) && $transaction_effect == VoucherTypeEffectEnum::RECEIVABLES->getCode()) {
    //                     $transacted_amount += $spread_transaction['transacted_amount'];
    //                 }
    //             }

    //             $spread_cells .= "<td class='align-right spread_" . $transaction_effect . " spread_" . $transaction_effect . "_" . $account_id . "'>" . number_format($transacted_amount, 2) . "</td>";
    //         }
    //         // Fill up empty cells in spread when the account type is an income type
    //         $spread_cells .= $this->emptyJournalCells($office_id, $transacting_month, 'expense');
        
    //     }elseif ($transaction_effect == VoucherTypeEffectEnum::PAYABLES->getCode()) {
    //         $spread_cells = "";
    //         // Fill up empty cells in spread when the account type is an expense type
    //         $spread_cells .= $this->emptyJournalCells($office_id, $transacting_month, 'income');
    //         foreach ($accounts as $account_id => $account_code) {
    //             $transacted_amount = 0;
    //             foreach ($spread as $spread_transaction) {
    //                 if (in_array($account_id, $spread_transaction) && $transaction_effect == VoucherTypeEffectEnum::PAYABLES->getCode()) {
    //                     $transacted_amount += $spread_transaction['transacted_amount'];
    //                 }
    //             }
    //             $spread_cells .= "<td class='align-right spread_" . $transaction_effect . " spread_" . $transaction_effect . "_" . $account_id . "'>" . number_format($transacted_amount, 2) . "</td>";
    //         }

    //     }elseif ($transaction_effect == VoucherTypeEffectEnum::PREPAYMENTS->getCode()) {
    //         $spread_cells = "";
    //         // Fill up empty cells in spread when the account type is an expense type
    //         $spread_cells .= $this->emptyJournalCells($office_id, $transacting_month, 'income');
    //         foreach ($accounts as $account_id => $account_code) {
    //             $transacted_amount = 0;
    //             foreach ($spread as $spread_transaction) {
    //                 if (in_array($account_id, $spread_transaction) && $transaction_effect == VoucherTypeEffectEnum::PREPAYMENTS->getCode()) {
    //                     $transacted_amount += $spread_transaction['transacted_amount'];
    //                 }
    //             }
    //             $spread_cells .= "<td class='align-right spread_" . $transaction_effect . " spread_" . $transaction_effect . "_" . $account_id . "'>" . number_format($transacted_amount, 2) . "</td>";
    //         }

    //     }elseif ($transaction_effect == 'payments' || $transaction_effect == 'settlements' || $transaction_effect == 'disbursements') {

    //         $spread_cells = "";
    //         $spread_cells .= $this->emptyJournalCells($office_id, $transacting_month, 'income');
    //         $spread_cells .= $this->emptyJournalCells($office_id, $transacting_month, 'expense');

    //     }elseif ($transaction_effect == VoucherTypeEffectEnum::PREPAYMENT_SETTLEMENTS->getCode()) {
    //         $spread_cells = "";
    //         // Fill up empty cells in spread when the account type is an expense type
    //         $spread_cells .= $this->emptyJournalCells($office_id, $transacting_month, 'income');
    //         foreach ($accounts as $account_id => $account_code) {
    //             $transacted_amount = 0;
    //             foreach ($spread as $spread_transaction) {
    //                 if (in_array($account_id, $spread_transaction) && $transaction_effect == VoucherTypeEffectEnum::PREPAYMENT_SETTLEMENTS->getCode()) {
    //                     $transacted_amount += $spread_transaction['transacted_amount'];
    //                 }
    //             }
    //             $spread_cells .= "<td class='align-right spread_" . $transaction_effect . " spread_" . $transaction_effect . "_" . $account_id . "'>" . number_format($transacted_amount, 2) . "</td>";
    //         }

    //     }

    //     return $spread_cells;
    // }

    public function createNewJournal($journal_date, $office_id)
    {
        $approvalLibrary = new \App\Libraries\Core\ApprovalLibrary();
        $statusLibrary   = new \App\Libraries\Core\StatusLibrary();

        $new_journal = [];

        $journal_date = date('Y-m-01', strtotime($journal_date));

        // Check if a journal for the same month and FCP exists
        $builder = $this->write_db->table('journal');
        $builder->where(['fk_office_id' => $office_id, 'journal_month' => $journal_date]);
        $count_journals = $builder->get()->getNumRows();

        //Create if CJ has not been created Other delete the duplicate record when > than 1
        if ($count_journals == 0) {
            $new_journal['journal_track_number']     = $this->generateItemTrackNumberAndName('journal')['journal_track_number'];
            $new_journal['journal_name']             = "Journal for the month of " . $journal_date;
            $new_journal['journal_month']            = $journal_date;
            $new_journal['fk_office_id']             = $office_id;
            $new_journal['journal_created_date']     = date('Y-m-d');
            $new_journal['journal_created_by']       = $this->session->user_id;
            $new_journal['journal_last_modified_by'] = $this->session->user_id;
            $new_journal['fk_approval_id']           = $approvalLibrary->insertApprovalRecord('journal');
            $new_journal['fk_status_id']             = $statusLibrary->initialItemStatus('journal');

            $this->write_db->table('journal')->insert($new_journal);
        }
    }

    public function cashBreakdown($office_id, $transacting_month)
    {

        $cash_breakdown     = [];
        $sum_cash_breakdown = [];

        $month_vouchers = $this->getAllOfficeMonthVouchers($office_id, $transacting_month);

        $all_office_cash_accounts = $this->OfficeCashJournal->getActiveOfficeCashByOfficeId($office_id);
        $all_office_bank_accounts = $this->OfficeBankJournal->getActiveOfficeBank($office_id);

        $month_opening_bank_cash_balance = $this->monthOpeningBankCashBalance($office_id, $transacting_month);

        foreach ($all_office_bank_accounts as $office_bank) {
            $sum_cash_breakdown['cash_at_bank'][$office_bank['office_bank_id']]['office_bank_name'] = $office_bank['office_bank_name'];
            $sum_cash_breakdown['cash_at_bank'][$office_bank['office_bank_id']]['opening']          = 0;

            if (isset($month_opening_bank_cash_balance['bank'][$office_bank['office_bank_id']])) {
                $sum_cash_breakdown['cash_at_bank'][$office_bank['office_bank_id']]['opening'] = $month_opening_bank_cash_balance['bank'][$office_bank['office_bank_id']]['amount'];
            }
        }

        foreach ($all_office_cash_accounts as $office_cash) {
            $sum_cash_breakdown['cash_at_hand'][$office_cash['office_cash_id']]['office_cash_name'] = $office_cash['office_cash_name'];
            $sum_cash_breakdown['cash_at_hand'][$office_cash['office_cash_id']]['opening']          = 0;

            if (isset($month_opening_bank_cash_balance['cash'][$office_cash['office_cash_id']])) {
                $sum_cash_breakdown['cash_at_hand'][$office_cash['office_cash_id']]['opening'] = $month_opening_bank_cash_balance['cash'][$office_cash['office_cash_id']]['amount'];
            }
        }

        foreach ($month_vouchers as $month_voucher) {
            if ($month_voucher['voucher_type_account_code'] == 'bank') {
                if ($month_voucher['voucher_type_effect_code'] == 'income' || $month_voucher['voucher_type_effect_code'] == 'cash_contra') {
                    $cash_breakdown['cash_at_bank'][$month_voucher['fk_office_bank_id']]['income'][] = $month_voucher['voucher_detail_total_cost'];
                    if (isset($cash_breakdown['cash_at_bank'][$month_voucher['fk_office_bank_id']]['income'])) {
                        $sum_cash_breakdown['cash_at_bank'][$month_voucher['fk_office_bank_id']]['income'] = array_sum($cash_breakdown['cash_at_bank'][$month_voucher['fk_office_bank_id']]['income']);
                    }
                } elseif ($month_voucher['voucher_type_effect_code'] == 'expense') {
                    $cash_breakdown['cash_at_bank'][$month_voucher['fk_office_bank_id']]['expense'][] = $month_voucher['voucher_detail_total_cost'];
                    if (isset($cash_breakdown['cash_at_bank'][$month_voucher['fk_office_bank_id']]['expense'])) {
                        $sum_cash_breakdown['cash_at_bank'][$month_voucher['fk_office_bank_id']]['expense'] = array_sum($cash_breakdown['cash_at_bank'][$month_voucher['fk_office_bank_id']]['expense']);
                    }
                } elseif ($month_voucher['voucher_type_effect_code'] == 'bank_contra') {
                    $cash_breakdown['cash_at_hand'][$month_voucher['fk_office_cash_id']]['income'][] = $month_voucher['voucher_detail_total_cost'];
                    if (isset($cash_breakdown['cash_at_hand'][$month_voucher['fk_office_cash_id']]['income'])) {
                        $sum_cash_breakdown['cash_at_hand'][$month_voucher['fk_office_cash_id']]['income'] = array_sum($cash_breakdown['cash_at_hand'][$month_voucher['fk_office_cash_id']]['income']);
                    }
                }
            } elseif ($month_voucher['voucher_type_account_code'] == 'cash') {
                if ($month_voucher['voucher_type_effect_code'] == 'income') {
                    $cash_breakdown['cash_at_hand'][$month_voucher['fk_office_cash_id']]['income'][] = $month_voucher['voucher_detail_total_cost'];
                    if (isset($cash_breakdown['cash_at_hand'][$month_voucher['fk_office_cash_id']]['income'])) {
                        $sum_cash_breakdown['cash_at_hand'][$month_voucher['fk_office_cash_id']]['income'] = array_sum($cash_breakdown['cash_at_hand'][$month_voucher['fk_office_cash_id']]['income']);
                    }
                } elseif ($month_voucher['voucher_type_effect_code'] == 'expense' || $month_voucher['voucher_type_effect_code'] == 'cash_contra') {
                    $cash_breakdown['cash_at_hand'][$month_voucher['fk_office_cash_id']]['expense'][] = $month_voucher['voucher_detail_total_cost'];
                    if (isset($cash_breakdown['cash_at_hand'][$month_voucher['fk_office_cash_id']]['expense'])) {
                        $sum_cash_breakdown['cash_at_hand'][$month_voucher['fk_office_cash_id']]['expense'] = array_sum($cash_breakdown['cash_at_hand'][$month_voucher['fk_office_cash_id']]['expense']);
                    }
                }
            }

        }

        $cash_breakdown_with_closing = [];

        foreach ($sum_cash_breakdown as $cash_type => $cash_type_details) {
            foreach ($cash_type_details as $detail_id => $detail) {
                $cash_breakdown_with_closing[$cash_type][$detail_id]            = $detail;
                $opening                                                        = isset($detail['opening']) ? $detail['opening'] : 0;
                $income                                                         = isset($detail['income']) ? $detail['income'] : 0;
                $expense                                                        = isset($detail['expense']) ? $detail['expense'] : 0;
                $cash_breakdown_with_closing[$cash_type][$detail_id]['closing'] = $opening + $income - $expense;
            }
        }

        return $cash_breakdown_with_closing;
    }

    public function checkIfVoucherIsReversedOrCancelled($voucher_id)
    {
        $voucher_has_been_cancelled_reused = 0;

        $builderReader=$this->read_db->table('voucher');
        $builderReader->where(['voucher_is_reversed' => 1, 'voucher_id' => $voucher_id]);
        $voucher_arr=$builderReader->get()->getResultArray();

        if (sizeof($voucher_arr) > 0) {
            $voucher_has_been_cancelled_reused = 1;
        }

        return $voucher_has_been_cancelled_reused;
    }
}
