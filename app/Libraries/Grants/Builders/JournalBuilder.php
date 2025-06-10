<?php

namespace App\Libraries\Grants\Builders;

use \App\Libraries\Grants\JournalLibrary;
use \App\Enums\VoucherTypeEffectEnum;
trait JournalBuilder
{

    public function navigation()
    {
        return view('journal/components/navigation', $this->getNavigationIds());
    }

    public function title()
    {
        return view('journal/components/title', [
            'office_name' => $this->getJournalOfficeName(),
            'transacting_month' => $this->getJournalTransactionMonth()
        ]);
    }

    public function titleColspan()
    {
        $count_of_month_used_accrual_ledgers = 5;
        return $this->getMonthSumAccounts() + ($count_of_month_used_accrual_ledgers * 3) + $this->journalDetailColumns + (count($this->getMonthBankOpeningBalance()) * 3) + (count($this->getMonthCashOpeningBalance()) * 3);
    }

    public function bankLedgerColumnHeaders()
    {
        return view('journal/components/bankLedgerColumnHeaders', [
            'month_opening_balance' => $this->getMonthBankOpeningBalance()
        ]);
    }

    public function bankLedgerOpeningBalance()
    {
        return view('journal/components/bankLedgerOpeningBalance', [
            'month_opening_balance' => $this->getMonthBankOpeningBalance()
        ]);
    }

    public function cashLedgerColumnHeaders()
    {
        return view('journal/components/cashLedgerColumnHeaders', [
            'month_opening_balance' => $this->getMonthCashOpeningBalance()
        ]);
    }

    public function cashLedgerOpeningBalance()
    {
        return view('journal/components/cashLedgerOpeningBalance', [
            'month_opening_balance' => $this->getMonthCashOpeningBalance()
        ]);
    }

    public function accrualLedgerColumnHeaders()
    {
        return view('journal/components/accrualLedgerColumnHeaders', [
            'month_opening_balance' => $this->getAccrualOpeningBalances()
        ]);
    }

    public function accrualLedgerOpeningBalance()
    {
        return view('journal/components/accrualLedgerOpeningBalance', [
            'month_opening_balance' => $this->getAccrualOpeningBalances()
        ]);
    }

    public function accountSpreadEmpty()
    {
        return view('journal/components/accountSpreadEmpty', ['journal' => $this]);
    }

    public function incomeAccountsHeaderTitle()
    {
        return view('journal/components/incomeAccountsHeaderTitle', ['journal' => $this]);
    }

    public function expenseAccountsHeaderTitle()
    {
        return view('journal/components/expenseAccountsHeaderTitle', ['journal' => $this]);
    }

    public function bankAccountsTitle()
    {
        return view(
            'journal/components/bankAccountsTitle',
            ['month_opening_balance' => $this->getMonthBankOpeningBalance()]
        );
    }

    public function cashAccountsTitle()
    {
        return view(
            'journal/components/cashAccountsTitle',
            ['month_opening_balance' => $this->getMonthCashOpeningBalance()]
        );
    }

    public function accrualAccountsTitle()
    {
        return view(
            'journal/components/accrualAccountsTitle',
            ['month_opening_balance' => $this->getAccrualOpeningBalances()]
        );
    }

    public function incomeCodesTitle()
    {
        return view(
            'journal/components/incomeCodesTitle',
            ['accounts' => $this->getMonthAccounts()]
        );
    }

    public function expenseCodesTitle()
    {
        return view(
            'journal/components/expenseCodesTitle',
            ['accounts' => $this->getMonthAccounts()]
        );
    }

    public function journalActionRelatedVouchers($voucher_reversal_from, $voucher_reversal_to)
    {
        $related_voucher_id = hash_id($voucher_reversal_from, 'encode');
        $reverse_btn_label = get_phrase('linked_source');

        if (!$voucher_reversal_from) {
            $related_voucher_id = hash_id($voucher_reversal_to, 'encode');
            $reverse_btn_label = get_phrase('linked_destination');
        }
        return view(
            'journal/components/journalActionRelatedVouchers',
            compact('voucher_reversal_from', 'voucher_reversal_to', 'related_voucher_id', 'reverse_btn_label')
        );
    }

    public function journalActionApprovalAndReturn($voucher, $voucher_id, $status_id, $item_initial_item_status_id, $item_max_approval_status_ids, $role_has_journal_update_permission, $item_status)
    {
        $voucher_is_cleared = $voucher['voucher_is_cleared'];
        $voucher_is_reversed = $voucher['voucher_is_reversed'];
        $return_string = '';
        $disable_flag = !$role_has_journal_update_permission ? true : false;

        if ($disable_flag) {
            $return_string .= view(
                'journal/components/journalActionReturnVoucher',
                compact('voucher_id', 'role_has_journal_update_permission', 'voucher_is_cleared', 'voucher_is_reversed')
            );
        }

        $return_string .= approval_action_button('voucher', $item_status, $voucher_id, $status_id, $item_initial_item_status_id, $item_max_approval_status_ids);

        return $return_string;
    }

    function journalActionCancelAndReuse($voucher_id, $cheque_number, $role_has_journal_update_permission, $voucher_is_cleared, $voucher_is_reversed, $mfr_submited_status)
    {
        $cancel_cheque_class = $cheque_number != 0 ? 'cancel_cheque' : '';
        $return_string = view(
            'journal/components/journalActionCancelTransaction',
            compact(
                'voucher_id',
                'cancel_cheque_class',
                'role_has_journal_update_permission',
                'mfr_submited_status',
                'voucher_is_reversed',
                'voucher_is_cleared'
            )
        );

        $return_string .= view(
            'journal/components/journalActionReUseCheque',
            compact(
                'voucher_id',
                'role_has_journal_update_permission',
                'mfr_submited_status',
                'voucher_is_reversed',
                'voucher_is_cleared'
            )
        );
        return $return_string;
    }

    function journalAction(
        $voucher,
        $voucher_id,
        $mfr_submited_status,
        $role_has_journal_update_permission,
        $item_status,
        $item_initial_item_status_id,
        $item_max_approval_status_ids,
        $check_if_financial_report_is_submitted
    ) {
        $return_string = '';
        $voucher_is_reversed = $voucher['voucher_is_reversed'];
        $voucher_reversal_from = $voucher['voucher_reversal_from'];
        $voucher_reversal_to = $voucher['voucher_reversal_to'];
        $voucher_is_cleared = $voucher['voucher_is_cleared'];
        $status_id = $voucher['status_id'];
        $voucher_type_is_cheque_referenced = $voucher['voucher_type_is_cheque_referenced'];
        $cheque_number = $voucher['cheque_number'];

        if ($voucher_is_reversed && ($voucher_reversal_from || $voucher_reversal_to)) {
            $return_string .= $this->journalActionRelatedVouchers($voucher_reversal_from, $voucher_reversal_to);
        }

        if ($voucher_is_reversed != 1 && $check_if_financial_report_is_submitted != 1) {
            $return_string .= $this->journalActionApprovalAndReturn($voucher, $voucher_id, $status_id, $item_initial_item_status_id, $item_max_approval_status_ids, $role_has_journal_update_permission, $item_status);
        }

        if ($voucher_type_is_cheque_referenced && $check_if_financial_report_is_submitted) {
            $return_string .= $this->journalActionCancelAndReuse($voucher_id, $cheque_number, $role_has_journal_update_permission, $voucher_is_cleared, $voucher_is_reversed, $mfr_submited_status);
        }

    }

    public function formatBankReference($cheque_number, $voucher_type_abbrev)
    {
        $eft_or_chq = '';
        if (!is_numeric($cheque_number)) {
            $eft_or_chq = $cheque_number . ' [' . $voucher_type_abbrev . ']';
        } else if (is_numeric($cheque_number)) {

            $eft_or_chq = $cheque_number != 0 ? $cheque_number . ' [' . $voucher_type_abbrev . ']' : '';
        }
        return $eft_or_chq;
    }

    public function voucherDescription($voucher_id, $description, $role_has_journal_update_permission, $voucher_is_reversed, $check_if_financial_report_is_submitted)
    {
        return view(
            'journal/components/voucherDescription',
            compact(
                'voucher_id',
                'role_has_journal_update_permission',
                'voucher_is_reversed',
                'check_if_financial_report_is_submitted',
                'description'
            )
        );
    }

    public function payeeLabel($payee, $voucher_id, $role_has_journal_update_permission, $voucher_is_reversed, $check_if_financial_report_is_submitted)
    {
        return view(
            'journal/components/payeeLabel',
            compact(
                'voucher_id',
                'role_has_journal_update_permission',
                'voucher_is_reversed',
                'check_if_financial_report_is_submitted',
                'payee'
            )
        );
    }

    public function voucherSelection($voucher_id, $voucher_type_abbrev, $voucher_type_name, $cleared)
    {
        return view(
            'journal/components/voucherSelection',
            compact(
                'voucher_id',
                'voucher_type_abbrev',
                'voucher_type_name',
                'cleared'
            )
        );
    }

    public function voucherNumberButton($voucher_id, $voucher_number)
    {
        return view(
            'journal/components/voucherNumberButton',
            compact(
                'voucher_id',
                'voucher_number'
            )
        );
    }

    public function computeBankRunningBalances($voucher, $voucher_amount, $sum_bank_income, $sum_bank_expense, &$bank_income, &$bank_expense, &$running_bank_balance)
    {
        $voucher_type_transaction_effect = $voucher['voucher_type_transaction_effect'];
        $voucher_type_cash_account = $voucher['voucher_type_cash_account'];
        $office_bank_id = $voucher['office_bank_id'];
        $receiving_office_bank_id = $voucher['receiving_office_bank_id'];

        if ($office_bank_id && isset($sum_bank_income[$office_bank_id])) {
            $office_bank_id = $voucher['office_bank_id'];
        } elseif ($receiving_office_bank_id && is_int($receiving_office_bank_id) && isset($sum_bank_income[$receiving_office_bank_id])) {
            $office_bank_id = $voucher['receiving_office_bank_id'];
        }

        if ($office_bank_id) {
            $bank_income[$office_bank_id] = (($voucher_type_cash_account == 'bank' && $voucher_type_transaction_effect == 'income') ||  ($voucher_type_cash_account == 'cash' && $voucher_type_transaction_effect == 'cash_contra')) ||
            $voucher_type_transaction_effect == 'payments' ? $voucher_amount : 0;

            $bank_expense[$office_bank_id] = (($voucher_type_cash_account == 'bank' && $voucher_type_transaction_effect == 'expense') || $voucher_type_transaction_effect == 'prepayments' || $voucher_type_transaction_effect == 'disbursements' || ($voucher_type_cash_account == 'bank' && ($voucher_type_transaction_effect == 'bank_contra' || $voucher_type_transaction_effect == 'bank_to_bank_contra'))) ? $voucher_amount : 0;

            $sum_bank_income[$office_bank_id] += $bank_income[$office_bank_id];
            $sum_bank_expense[$office_bank_id] += $bank_expense[$office_bank_id];

            $running_bank_balance[$office_bank_id] = $running_bank_balance[$office_bank_id] + ($sum_bank_income[$office_bank_id] - $sum_bank_expense[$office_bank_id]);
        }
    }

    public function computeCashRunningBalances($voucher, $voucher_amount, $sum_petty_cash_income, $sum_petty_cash_expense, &$cash_income, &$cash_expense, &$running_petty_cash_balance)
    {
        $voucher_type_cash_account = $voucher['voucher_type_cash_account'];
        $voucher_type_transaction_effect = $voucher['voucher_type_transaction_effect'];
        $office_cash_id = $voucher['office_cash_id'];
        $receiving_office_cash_id = $voucher['receiving_office_cash_id'];

        if ($office_cash_id && isset($sum_petty_cash_income[$office_cash_id])) {
            $office_cash_id = $voucher['office_cash_id'];
        } elseif ($receiving_office_cash_id && is_int($receiving_office_cash_id) && isset($sum_petty_cash_income[$receiving_office_cash_id])) {
            $office_cash_id = $voucher['receiving_office_cash_id'];
        }

        if ($office_cash_id) {
            $cash_income[$office_cash_id] = (($voucher_type_cash_account == 'cash' && $voucher_type_transaction_effect == 'income') || ($voucher_type_cash_account == 'bank' && $voucher_type_transaction_effect == 'bank_contra')) ? $voucher_amount : 0;

            $cash_expense[$office_cash_id] = (($voucher_type_cash_account == 'cash' && $voucher_type_transaction_effect == 'expense') || ($voucher_type_cash_account == 'cash' && $voucher_type_transaction_effect == 'cash_contra' || $voucher_type_transaction_effect == 'cash_to_cash_contra')) ? $voucher_amount : 0;

            $sum_petty_cash_income[$office_cash_id] += $cash_income[$office_cash_id];
            $sum_petty_cash_expense[$office_cash_id] += $cash_expense[$office_cash_id];
            
            $running_petty_cash_balance[$office_cash_id] =  $running_petty_cash_balance[$office_cash_id] + ($sum_petty_cash_income[$office_cash_id] - $sum_petty_cash_expense[$office_cash_id]);
        }

    }

    public function computePayablesRunningBalances($voucher, $voucher_amount, $sum_payables_income, $sum_payables_expense, &$payables_income, &$payables_expense, &$running_payables_balance)
    {
        $voucher_type_transaction_effect = $voucher['voucher_type_transaction_effect'];

        if ($voucher_type_transaction_effect == 'payables' || $voucher_type_transaction_effect == 'disbursements') {
            $payables_income = $voucher_type_transaction_effect == 'disbursements' ? $voucher_amount : 0;
            $payables_expense = $voucher_type_transaction_effect == 'payables' ? $voucher_amount : 0;

            $sum_payables_income += $payables_income;
            $sum_payables_expense += $payables_expense;

            $running_payables_balance = $this->getAccrualOpeningBalances()['payables'] + ($sum_payables_income - $sum_payables_expense);
        }
    }

    public function computeReceivablesRunningBalances($voucher, $voucher_amount, $sum_receivables_income, $sum_receivables_expense, &$receivables_income, &$receivables_expense, &$running_receivables_balance)
    {
        $voucher_type_transaction_effect = $voucher['voucher_type_transaction_effect'];

        if ($voucher_type_transaction_effect == 'receivables' || $voucher_type_transaction_effect == 'payments') {
            $receivables_income = $voucher_type_transaction_effect == 'receivables' ? $voucher_amount : 0;
            $receivables_expense = $voucher_type_transaction_effect == 'payments' ? $voucher_amount : 0;

            $sum_receivables_income += $receivables_income;
            $sum_receivables_expense += $receivables_expense;

            $running_receivables_balance = $this->getAccrualOpeningBalances()['receivables'] + ($sum_receivables_income - $sum_receivables_expense);
        }

    }

    public function computePrepaymentsRunningBalances($voucher, $voucher_amount, $sum_prepayments_income, $sum_prepayments_expense, &$prepayments_income, &$prepayments_expense, &$running_prepayments_balance)
    {
        $voucher_type_transaction_effect = $voucher['voucher_type_transaction_effect'];

        if ($voucher_type_transaction_effect == 'prepayments' || $voucher_type_transaction_effect == 'settlements') {
            $prepayments_income = $voucher_type_transaction_effect == 'prepayments' ? $voucher_amount : 0;
            $prepayments_expense = $voucher_type_transaction_effect == 'settlements' ? $voucher_amount : 0;

            $sum_prepayments_income += $prepayments_income;
            $sum_prepayments_expense += $prepayments_expense;

            $running_prepayments_balance = $this->getAccrualOpeningBalances()['prepayments'] + ($sum_prepayments_income - $sum_prepayments_expense);
        }

    }

    public function computeDepreciationRunningBalances($voucher, $voucher_amount, $sum_depreciation_income, $sum_depreciation_expense, &$depreciation_income, &$depreciation_expense, &$running_depreciation_balance)
    {
        $voucher_type_transaction_effect = $voucher['voucher_type_transaction_effect'];

        if ($voucher_type_transaction_effect == 'depreciation') {
            $depreciation_income = 0;
            $depreciation_expense = $voucher_type_transaction_effect == 'depreciation' ? $voucher_amount : 0;

            $sum_depreciation_income += $depreciation_income;
            $sum_depreciation_expense += $depreciation_expense;

            $running_depreciation_balance = $this->getAccrualOpeningBalances()['depreciation'] + ($sum_depreciation_income - $sum_depreciation_expense);
        }
    }

    public function computePayrollLiabilityRunningBalances($voucher, $voucher_amount, $sum_payroll_liability_income, $sum_payroll_liability_expense, &$payroll_liability_income, &$payroll_liability_expense, &$running_payroll_liability_balance)
    {
        $voucher_type_transaction_effect = $voucher['voucher_type_transaction_effect'];

        if ($voucher_type_transaction_effect == 'payroll_liability') {
            $payroll_liability_income = 0;
            $payroll_liability_expense = $voucher_type_transaction_effect == 'payroll_liability' ? $voucher_amount : 0;

            $sum_payroll_liability_income += $payroll_liability_income;
            $sum_payroll_liability_expense += $payroll_liability_expense;

            $running_payroll_liability_balance = $this->getAccrualOpeningBalances()['payroll_liability'] + ($sum_payroll_liability_income - $sum_payroll_liability_expense);
        }

    }

    public function computeCurrentJournalRowBankBalance($voucher, $bank_id, $bank_income, $bank_expense, $running_bank_balance)
    {
        $bank_inc = 0;
        $bank_exp = 0;
        $bank_bal = 0;

        $office_bank_id = $voucher['office_bank_id'];
        $voucher_type_transaction_effect = $voucher['voucher_type_transaction_effect'];
        $receiving_office_bank_id = $voucher['receiving_office_bank_id'];

        if ($bank_id == $office_bank_id) {
            $office_bank_id = $voucher['office_bank_id'];
        } elseif ($bank_id == $receiving_office_bank_id) {
            $office_bank_id = $voucher['receiving_office_bank_id'];
        }

        if ($office_bank_id) {
            $bank_inc = $bank_income[$office_bank_id];
            $bank_exp = $bank_expense[$office_bank_id];
            $bank_bal = $running_bank_balance[$office_bank_id];
        }

        return compact('bank_inc', 'bank_exp', 'bank_bal');
    }

    public function computeCurrentJournalRowCashBalance($voucher, $cash_id, $cash_income, $cash_expense, $running_petty_cash_balance)
    {
        $cash_inc = 0;
        $cash_exp = 0;
        $cash_bal = 0;

        $office_cash_id = $voucher['office_cash_id'];
        $receiving_office_cash_id = $voucher['receiving_office_cash_id'];

        if ($cash_id == $office_cash_id) {
            $office_cash_id = $voucher['office_cash_id'];
        } elseif ($cash_id == $receiving_office_cash_id) {
            $office_cash_id = $voucher['receiving_office_cash_id'];
        }

        if ($office_cash_id) {
            $cash_inc = $cash_income[$office_cash_id];
            $cash_exp = $cash_expense[$office_cash_id];
            $cash_bal = $running_petty_cash_balance[$office_cash_id];
        }

        return compact('cash_inc', 'cash_exp', 'cash_bal');
    }

    public function computeCurrentJournalRowReceivablesBalance($voucher, $receivables_income, $receivables_expense, $running_receivables_balance)
    {

        $voucher_type_transaction_effect = $voucher['voucher_type_transaction_effect'];
        $receivables_inc = 0;
        $receivables_exp = 0;
        $receivables_bal = 0;

        if ($voucher_type_transaction_effect == 'receivables' || $voucher_type_transaction_effect == 'payments') {
            $receivables_inc = $receivables_income;
            $receivables_exp = $receivables_expense;
            $receivables_bal = $running_receivables_balance;
        }
        return compact('receivables_inc', 'receivables_exp', 'receivables_bal');
    }

    public function computeCurrentJournalRowPayablesBalance($voucher, $payables_income, $payables_expense, $running_payables_balance)
    {

        $voucher_type_transaction_effect = $voucher['voucher_type_transaction_effect'];
        $payables_inc = 0;
        $payables_exp = 0;
        $payables_bal = 0;

        if ($voucher_type_transaction_effect == 'payables' || $voucher_type_transaction_effect == 'disbursements') {
            $payables_inc = $payables_income;
            $payables_exp = $payables_expense;
            $payables_bal = $running_payables_balance;
        }
        return compact('payables_inc', 'payables_exp', 'payables_bal');
    }

    public function computeCurrentJournalRowPrepaymentsBalance($voucher, $prepayments_income, $prepayments_expense, $running_prepayments_balance)
    {

        $voucher_type_transaction_effect = $voucher['voucher_type_transaction_effect'];
        $prepayments_inc = 0;
        $prepayments_exp = 0;
        $prepayments_bal = 0;

        if ($voucher_type_transaction_effect == 'prepayments' || $voucher_type_transaction_effect == 'settlements') {
            $prepayments_inc = $prepayments_income;
            $prepayments_exp = $prepayments_expense;
            $prepayments_bal = $running_prepayments_balance;
        }
        return compact('prepayments_inc', 'prepayments_exp', 'prepayments_bal');
    }

    public function computeCurrentJournalRowDepreciationBalance($voucher, $depreciation_income, $depreciation_expense, $running_depreciation_balance)
    {

        $voucher_type_transaction_effect = $voucher['voucher_type_transaction_effect'];
        $depreciation_inc = 0;
        $depreciation_exp = 0;
        $depreciation_bal = 0;

        if ($voucher_type_transaction_effect == 'depreciation') {
            $depreciation_inc = $depreciation_income;
            $depreciation_exp = $depreciation_expense;
            $depreciation_bal = $running_depreciation_balance;
        }
        return compact('depreciation_inc', 'depreciation_exp', 'depreciation_bal');
    }

    public function computeCurrentJournalRowPayrollLiabilityBalance($voucher, $payroll_liability_income, $payroll_liability_expense, $running_payroll_liability_balance)
    {

        $voucher_type_transaction_effect = $voucher['voucher_type_transaction_effect'];
        $payroll_liability_inc = 0;
        $payroll_liability_exp = 0;
        $payroll_liability_bal = 0;

        if ($voucher_type_transaction_effect == 'payroll_liability') {
            $payroll_liability_inc = $payroll_liability_income;
            $payroll_liability_exp = $payroll_liability_expense;
            $payroll_liability_bal = $running_payroll_liability_balance;
        }
        return compact('payroll_liability_inc', 'payroll_liability_exp', 'payroll_liability_bal');
    }

    private function emptyJournalCells($account_type = 'income')
    {
        $spread_cells = '';
        $financial_accounts = $this->getMonthAccounts();
        for ($i = 0; $i < count($financial_accounts[$account_type]); $i++) {
            $spread_cells .= "<td class='align-right'>0.00</td>";
        }
        return $spread_cells;
    }
    public function journalSpread($office_id, $spread, $account_type = 'bank', $transaction_effect = 'income')
    {
        // $journalLibrary = new JournalLibrary();
        $financial_accounts = $this->getMonthAccounts();

        $accounts = match ($transaction_effect) {
            'income',
            VoucherTypeEffectEnum::RECEIVABLES->getCode(),
            VoucherTypeEffectEnum::RECEIVABLES_PAYMENTS->getCode() => $financial_accounts['income'],
            'expense',
            VoucherTypeEffectEnum::PREPAYMENT_SETTLEMENTS->getCode(),
            VoucherTypeEffectEnum::PAYABLES->getCode(),
            VoucherTypeEffectEnum::PAYABLE_DISBURSEMENTS->getCode(),
            VoucherTypeEffectEnum::PREPAYMENTS->getCode(),
            VoucherTypeEffectEnum::DEPRECIATION->getCode(),
            VoucherTypeEffectEnum::PAYROLL_LIABILITY->getCode() => $financial_accounts['expense'],
            'bank_contra' => $financial_accounts['bank_contra'] ?? [],
            'cash_contra' => $financial_accounts['cash_contra'] ?? [],
            'bank_to_bank_contra' => $financial_accounts['bank_to_bank_contra'] ?? [],
            'cash_to_cash_contra' => $financial_accounts['cash_to_cash_contra'] ?? []
        };

        if (
            $transaction_effect == 'expense' || 
            $transaction_effect == 'settlements' ||  
            $transaction_effect == 'payables'
        ) {
            $spread_cells = $this->expenseAccountsSpreading($accounts, $spread, $transaction_effect);
        } elseif (
            $transaction_effect == 'income' || 
            $transaction_effect == 'receivables'
        ) {
            $spread_cells = $this->incomeAccountsSpreading($accounts, $spread, $transaction_effect);
        } elseif (
            $transaction_effect == 'cash_contra' || 
            $transaction_effect == 'bank_contra' || 
            $transaction_effect == 'bank_to_bank_contra' || 
            $transaction_effect == 'cash_to_cash_contra' ||
            $transaction_effect == 'prepayments' ||
            $transaction_effect == 'payments' || 
            $transaction_effect == 'disbursements'
            ) {
            $spread_cells = $this->contraAccountsSpreading();
        }
     
        return $spread_cells ?? '';
    }

    private function contraAccountsSpreading()
    {
        return $this->emptyJournalCells('income') . $this->emptyJournalCells('expense');
    }

    private function incomeAccountsSpreading($accounts, $spread, $transaction_effect)
    {
        $spread_cells = "";
        foreach ($accounts as $account_id => $account_code) {
            $transacted_amount = 0;
            foreach ($spread as $spread_transaction) {
                if (in_array($account_id, $spread_transaction) && ($transaction_effect == 'income' || $transaction_effect == 'receivables')) {
                    $transacted_amount += $spread_transaction['transacted_amount'];
                }
            }

            $spread_cells .= "<td class='align-right spread_" . $transaction_effect . " spread_" . $transaction_effect . "_" . $account_id . "'>" . number_format($transacted_amount, 2) . "</td>";
        }
        // Fill up empty cells in spread when the account type is an income type
        $spread_cells .= $this->emptyJournalCells('expense');

        return $spread_cells;
    }

    private function expenseAccountsSpreading($accounts, $spread, $transaction_effect): string
    {
        // Fill up empty cells in spread when the account type is an expense type
        $spread_cells = $this->emptyJournalCells('income');

        foreach ($accounts as $account_id => $account_code) {
            $transacted_amount = 0;
            foreach ($spread as $spread_transaction) {
                if (in_array($account_id, $spread_transaction) && ($transaction_effect == 'expense' || $transaction_effect == 'settlements' || $transaction_effect == 'payables')) {
                    $transacted_amount += $spread_transaction['transacted_amount'];
                }
            }
            $spread_cells .= "<td class='align-right spread_" . $transaction_effect . " spread_" . $transaction_effect . "_" . $account_id . "'>" . number_format($transacted_amount, 2) . "</td>";
        }

        return $spread_cells;
    }
}