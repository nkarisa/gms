<?php

if (!function_exists('journal')) {
    function journal()
    {
        $journalData = json_decode(file_get_contents(APPPATH . 'Data/journalData.json'), true);
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
    function journalActionApprovalAndReturn($voucher_id, $status_id, $item_initial_item_status_id, $item_max_approval_status_ids, $role_has_journal_update_permission, $item_status)
    {
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
            $return_string .= journalActionApprovalAndReturn($voucher_id, $status_id, $item_initial_item_status_id, $item_max_approval_status_ids, $role_has_journal_update_permission, $item_status);
        }

        if ($voucher_type_is_cheque_referenced && $check_if_financial_report_is_submitted) {
            $return_string .= journalActionCancelAndReuse($voucher_id, $cheque_number, $role_has_journal_update_permission, $voucher_is_cleared, $voucher_is_reversed, $mfr_submited_status);
        }

    }
}