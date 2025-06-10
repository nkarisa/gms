<?php

if (!function_exists('journal')) {
    function journal()
    {
        // $journalData = json_decode(file_get_contents(APPPATH . 'Data/journalData.json'), true);
        return new \App\Libraries\Grants\Builders\Journal($journalData);
    }
}

if (!function_exists('navigation')) {
    function navigation()
    {
        return view('journal/components/navigation', journal()->getNavigationIds());
    }
}

if (!function_exists(function: 'title')) {
    function title()
    {
        return view('journal/components/title', [
            'office_name' => journal()->getJournalOfficeName(),
            'transacting_month' => journal()->getJournalTransactionMonth()
        ]);
    }
}

if (!function_exists('titleColspan')) {
    function titleColspan()
    {
        $count_of_month_used_accrual_ledgers = 5;
        return journal()->getMonthSumAccounts() + ($count_of_month_used_accrual_ledgers * 3) + journal()->journalDetailColumns + (count(journal()->getMonthBankOpeningBalance()) * 3) + (count(journal()->getMonthCashOpeningBalance()) * 3);
    }
}

if (!function_exists('bankLedgerColumnHeaders')) {
    function bankLedgerColumnHeaders()
    {
        return view('journal/components/bankLedgerColumnHeaders', [
            'month_opening_balance' => journal()->getMonthBankOpeningBalance()
        ]);
    }
}

if (!function_exists('bankLedgerOpeningBalance')) {
    function bankLedgerOpeningBalance()
    {
        return view('journal/components/bankLedgerOpeningBalance', [
            'month_opening_balance' => journal()->getMonthBankOpeningBalance()
        ]);
    }
}

if (!function_exists('cashLedgerColumnHeaders')) {
    function cashLedgerColumnHeaders()
    {
        return view('journal/components/cashLedgerColumnHeaders', [
            'month_opening_balance' => journal()->getMonthCashOpeningBalance()
        ]);
    }
}

if (!function_exists('cashLedgerOpeningBalance')) {
    function cashLedgerOpeningBalance()
    {
        return view('journal/components/cashLedgerOpeningBalance', [
            'month_opening_balance' => journal()->getMonthCashOpeningBalance()
        ]);
    }
}

if (!function_exists('accrualLedgerColumnHeaders')) {
    function accrualLedgerColumnHeaders()
    {
        return view('journal/components/accrualLedgerColumnHeaders', [
            'month_opening_balance' => journal()->getAccrualOpeningBalances()
        ]);
    }
}

if (!function_exists('accrualLedgerOpeningBalance')) {
    function accrualLedgerOpeningBalance()
    {
        return view('journal/components/accrualLedgerOpeningBalance', [
            'month_opening_balance' => journal()->getAccrualOpeningBalances()
        ]);
    }
}

if (!function_exists('accountSpreadEmpty')) {
    function accountSpreadEmpty()
    {
        return view('journal/components/accountSpreadEmpty');
    }
}

if (!function_exists('incomeAccountsHeaderTitle')) {
    function incomeAccountsHeaderTitle()
    {
        return view('journal/components/incomeAccountsHeaderTitle');
    }
}

if (!function_exists('expenseAccountsHeaderTitle')) {
    function expenseAccountsHeaderTitle()
    {
        return view('journal/components/expenseAccountsHeaderTitle');
    }
}


if (!function_exists('bankAccountsTitle')) {
    function bankAccountsTitle()
    {
        return view(
            'journal/components/bankAccountsTitle',
            ['month_opening_balance' => journal()->getMonthBankOpeningBalance()]
        );
    }
}


if (!function_exists('cashAccountsTitle')) {
    function cashAccountsTitle()
    {
        return view(
            'journal/components/cashAccountsTitle',
            ['month_opening_balance' => journal()->getMonthCashOpeningBalance()]
        );
    }
}


if (!function_exists('accrualAccountsTitle')) {
    function accrualAccountsTitle()
    {
        return view(
            'journal/components/accrualAccountsTitle',
            ['month_opening_balance' => journal()->getAccrualOpeningBalances()]
        );
    }
}

if (!function_exists('incomeCodesTitle')) {
    function incomeCodesTitle()
    {
        return view(
            'journal/components/incomeCodesTitle',
            ['accounts' => journal()->getMonthAccounts()]
        );
    }
}

if (!function_exists('expenseCodesTitle')) {
    function expenseCodesTitle()
    {
        return view(
            'journal/components/expenseCodesTitle',
            ['accounts' => journal()->getMonthAccounts()]
        );
    }
}

if (!function_exists('journalActionRelatedVouchers')) {
    function journalActionRelatedVouchers($voucher_reversal_from, $voucher_reversal_to)
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
}

if (!function_exists('journalActionApprovalAndReturn')) {
    function journalActionApprovalAndReturn($voucher, $voucher_id, $status_id, $item_initial_item_status_id, $item_max_approval_status_ids, $role_has_journal_update_permission, $item_status)
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
}

if (!function_exists('journalActionCancelAndReuse')) {
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
}

if (!function_exists('journalAction')) {
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
            $return_string .= journalActionRelatedVouchers($voucher_reversal_from, $voucher_reversal_to);
        }

        if ($voucher_is_reversed != 1 && $check_if_financial_report_is_submitted != 1) {
            $return_string .= journalActionApprovalAndReturn($voucher, $voucher_id, $status_id, $item_initial_item_status_id, $item_max_approval_status_ids, $role_has_journal_update_permission, $item_status);
        }

        if ($voucher_type_is_cheque_referenced && $check_if_financial_report_is_submitted) {
            $return_string .= journalActionCancelAndReuse($voucher_id, $cheque_number, $role_has_journal_update_permission, $voucher_is_cleared, $voucher_is_reversed, $mfr_submited_status);
        }

    }
}

if (!function_exists('formatBankReference')) {
    function formatBankReference($cheque_number, $voucher_type_abbrev)
    {
        $eft_or_chq = '';
        if (!is_numeric($cheque_number)) {
            $eft_or_chq = $cheque_number . ' [' . $voucher_type_abbrev . ']';
        } else if (is_numeric($cheque_number)) {

            $eft_or_chq = $cheque_number != 0 ? $cheque_number . ' [' . $voucher_type_abbrev . ']' : '';
        }
        echo $eft_or_chq;
    }
}

if (!function_exists('voucherDescription')) {
    function voucherDescription($voucher_id, $description, $role_has_journal_update_permission, $voucher_is_reversed, $check_if_financial_report_is_submitted)
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
}

if (!function_exists('payeeLabel')) {
    function payeeLabel($payee, $voucher_id, $role_has_journal_update_permission, $voucher_is_reversed, $check_if_financial_report_is_submitted)
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
}

if (!function_exists('voucherSelection')) {
    function voucherSelection($voucher_id, $voucher_type_abbrev, $voucher_type_name, $cleared)
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
}

if (!function_exists('voucherNumberButton')) {
    function voucherNumberButton($voucher_id, $voucher_number)
    {
        return view(
            'journal/components/voucherNumberButton',
            compact(
                'voucher_id',
                'voucher_number'
            )
        );
    }
}

if (!function_exists('computeBankRunningBalances')) {
    function computeBankRunningBalances($voucher, $voucher_amount, $sum_bank_income, $sum_bank_expense, &$bank_income, &$bank_expense, &$running_bank_balance)
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
            $bank_income[$office_bank_id] = (($voucher_type_cash_account == 'bank' && $voucher_type_transaction_effect == 'income') || $voucher_type_transaction_effect == 'payments' || ($voucher_type_cash_account == 'cash' && $voucher_type_transaction_effect == 'cash_contra')) ? $voucher_amount : 0;
            $bank_expense[$office_bank_id] = (($voucher_type_cash_account == 'bank' && $voucher_type_transaction_effect == 'expense') || $voucher_type_transaction_effect == 'prepayments' || $voucher_type_transaction_effect == 'disbursements' || ($voucher_type_cash_account == 'bank' && ($voucher_type_transaction_effect == 'bank_contra' || $voucher_type_transaction_effect == 'bank_to_bank_contra'))) ? $voucher_amount : 0;

            $sum_bank_income[$office_bank_id] += $bank_income[$office_bank_id];
            $sum_bank_expense[$office_bank_id] += $bank_expense[$office_bank_id];

            $running_bank_balance[$office_bank_id] = journal()->getMonthBankOpeningBalance()[$office_bank_id]['amount'] + ($sum_bank_income[$office_bank_id] - $sum_bank_expense[$office_bank_id]);
        }
    }
}

if (!function_exists('computeCashRunningBalances')) {
    function computeCashRunningBalances($voucher, $voucher_amount, $sum_petty_cash_income, $sum_petty_cash_expense, &$cash_income, &$cash_expense, &$running_petty_cash_balance)
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

            $running_petty_cash_balance[$office_cash_id] = journal()->getMonthCashOpeningBalance()[$office_cash_id]['amount'] + ($sum_petty_cash_income[$office_cash_id] - $sum_petty_cash_expense[$office_cash_id]);
        }

    }
}

if (!function_exists('computePayablesRunningBalances')) {
    function computePayablesRunningBalances($voucher, $voucher_amount, $sum_payables_income, $sum_payables_expense, &$payables_income, &$payables_expense, &$running_payables_balance)
    {
        $voucher_type_transaction_effect = $voucher['voucher_type_transaction_effect'];

        if ($voucher_type_transaction_effect == 'payables' || $voucher_type_transaction_effect == 'disbursements') {
            $payables_income = $voucher_type_transaction_effect == 'payables' ? $voucher_amount : 0;
            $payables_expense = $voucher_type_transaction_effect == 'disbursements' ? $voucher_amount : 0;

            $sum_payables_income += $payables_income;
            $sum_payables_expense += $payables_expense;

            $running_payables_balance = journal()->getAccrualOpeningBalances()['payables'] + ($sum_payables_income - $sum_payables_expense);
        }
    }
}


if (!function_exists('computeReceivablesRunningBalances')) {
    function computeReceivablesRunningBalances($voucher, $voucher_amount, $sum_receivables_income, $sum_receivables_expense, &$receivables_income, &$receivables_expense, &$running_receivables_balance)
    {
        $voucher_type_transaction_effect = $voucher['voucher_type_transaction_effect'];

        if ($voucher_type_transaction_effect == 'receivables' || $voucher_type_transaction_effect == 'payments') {
            $receivables_income = $voucher_type_transaction_effect == 'receivables' ? $voucher_amount : 0;
            $receivables_expense = $voucher_type_transaction_effect == 'payments' ? $voucher_amount : 0;

            $sum_receivables_income += $receivables_income;
            $sum_receivables_expense += $receivables_expense;

            $running_receivables_balance = journal()->getAccrualOpeningBalances()['receivables'] + ($sum_receivables_income - $sum_receivables_expense);
        }


    }
}


if (!function_exists('computePrepaymentsRunningBalances')) {
    function computePrepaymentsRunningBalances($voucher, $voucher_amount, $sum_prepayments_income, $sum_prepayments_expense, &$prepayments_income, &$prepayments_expense, &$running_prepayments_balance)
    {
        $voucher_type_transaction_effect = $voucher['voucher_type_transaction_effect'];

        if ($voucher_type_transaction_effect == 'prepayments' || $voucher_type_transaction_effect == 'setlements') {
            $prepayments_income = $voucher_type_transaction_effect == 'prepayments' ? $voucher_amount : 0;
            $prepayments_expense = $voucher_type_transaction_effect == 'setlements' ? $voucher_amount : 0;

            $sum_prepayments_income += $prepayments_income;
            $sum_prepayments_expense += $prepayments_expense;

            $running_prepayments_balance = journal()->getAccrualOpeningBalances()['prepayments'] + ($sum_prepayments_income - $sum_prepayments_expense);
        }


    }
}


if (!function_exists('computeDepreciationRunningBalances')) {
    function computeDepreciationRunningBalances($voucher, $voucher_amount, $sum_depreciation_income, $sum_depreciation_expense, &$depreciation_income, &$depreciation_expense, &$running_depreciation_balance)
    {
        $voucher_type_transaction_effect = $voucher['voucher_type_transaction_effect'];

        if ($voucher_type_transaction_effect == 'depreciation') {
            $depreciation_income = 0;
            $depreciation_expense = $voucher_type_transaction_effect == 'depreciation' ? $voucher_amount : 0;

            $sum_depreciation_income += $depreciation_income;
            $sum_depreciation_expense += $depreciation_expense;

            $running_depreciation_balance = journal()->getAccrualOpeningBalances()['depreciation'] + ($sum_depreciation_income - $sum_depreciation_expense);
        }

    }
}

if (!function_exists('computePayrollLiabilityRunningBalances')) {
    function computePayrollLiabilityRunningBalances($voucher, $voucher_amount, $sum_payroll_liability_income, $sum_payroll_liability_expense, &$payroll_liability_income, &$payroll_liability_expense, &$running_payroll_liability_balance)
    {
        $voucher_type_transaction_effect = $voucher['voucher_type_transaction_effect'];

        if ($voucher_type_transaction_effect == 'payroll_liability') {
            $payroll_liability_income = 0;
            $payroll_liability_expense = $voucher_type_transaction_effect == 'payroll_liability' ? $voucher_amount : 0;

            $sum_payroll_liability_income += $payroll_liability_income;
            $sum_payroll_liability_expense += $payroll_liability_expense;

            $running_payroll_liability_balance = journal()->getAccrualOpeningBalances()['payroll_liability'] + ($sum_payroll_liability_income - $sum_payroll_liability_expense);
        }

    }
}


if (!function_exists('computeCurrentJournalRowBankBalance')) {
    function computeCurrentJournalRowBankBalance($voucher, $bank_id, $bank_income, $bank_expense, $running_bank_balance)
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
}

if (!function_exists('computeCurrentJournalRowCashBalance')) {
    function computeCurrentJournalRowCashBalance($voucher, $cash_id, $cash_income, $cash_expense, $running_petty_cash_balance)
    {
        $cash_inc = 0;
        $cash_exp = 0;
        $cash_bal = 0;

        $office_cash_id = $voucher['office_cash_id'];
        $receiving_office_cash_id = $voucher['receiving_office_cash_id'];

        if($cash_id == $office_cash_id){
            $office_cash_id = $voucher['office_cash_id'];
        }elseif($cash_id == $receiving_office_cash_id){
            $office_cash_id = $voucher['receiving_office_cash_id'];
        }

        if ($office_cash_id) {
            $cash_inc = $cash_income[$office_cash_id];
            $cash_exp = $cash_expense[$office_cash_id];
            $cash_bal = $running_petty_cash_balance[$office_cash_id];
        }

        return compact('cash_inc', 'cash_exp', 'cash_bal');
    }
}

if(!function_exists('computeCurrentJournalRowReceivablesBalance')){
    function computeCurrentJournalRowReceivablesBalance($voucher, $receivables_income, $receivables_expense, $running_receivables_balance){
        
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
}


if(!function_exists('computeCurrentJournalRowPayablesBalance')){
    function computeCurrentJournalRowPayablesBalance($voucher, $payables_income, $payables_expense, $running_payables_balance){
        
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
}

if(!function_exists('computeCurrentJournalRowPrepaymentsBalance')){
    function computeCurrentJournalRowPrepaymentsBalance($voucher, $prepayments_income, $prepayments_expense, $running_prepayments_balance){
        
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
}


if(!function_exists('computeCurrentJournalRowDepreciationBalance')){
    function computeCurrentJournalRowDepreciationBalance($voucher, $depreciation_income, $depreciation_expense, $running_depreciation_balance){
        
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
}

if(!function_exists('computeCurrentJournalRowPayrollLiabilityBalance')){
    function computeCurrentJournalRowPayrollLiabilityBalance($voucher, $payroll_liability_income, $payroll_liability_expense, $running_payroll_liability_balance){
        
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
}