<?php
/*
 *	@author 	: Karisa
    @Modified By: Onduso
 *	@date		: 24th April, 2021
 *	Finance management system for NGOs
 *	Nkarisa@ke.ci.org/Londuso@ke.ci.org
 */
?>
<style>
    /* Style buttons */
    .btn_reverse {
        background-color: DodgerBlue;
        /* Blue background */
        border: none;
        /* Remove borders */
        color: white;
        /* White text */
        padding: 12px 16px;
        /* Some padding */
        font-size: 16px;
        /* Set a font size */
        cursor: pointer;
        /* Mouse pointer on hover */
    }

    /* Darker background on mouse-over */
    .btn_reverse:hover {
        background-color: RoyalBlue;
    }

    .edit_journal {
        cursor: pointer;
    }

    .table>tbody>tr:hover>td,
    .table>tbody>tr:hover>th {
        background-color: #CFF5FF;
    }

    .table>tbody>tr.active>td,
    .table>tbody>tr:active>th {
        background-color: #CFF5FF;
        color: blue;
    }
</style>

<?php
// echo json_encode($result);
// helper('journal');
$journalLibrary = new \App\Libraries\Grants\JournalLibrary();
$journal = new \App\Libraries\Grants\Builders\Journal($result);

// Unpacking/destructure controller view results

[
    'item_max_approval_status_ids' => $item_max_approval_status_ids,
    'item_status' => $item_status,
    'item_initial_item_status_id' => $item_initial_item_status_id,
    'permissions' => $permissions,
    'active_approval_actor' => $active_approval_actor
] = $result['status_data'];

[
    'transacting_month' => $transacting_month,
    'role_has_journal_update_permission' => $role_has_journal_update_permission,
    'check_if_financial_report_is_submitted' => $check_if_financial_report_is_submitted,
    'mfr_submited_status' => $mfr_submited_status,
    'month_used_accrual_ledgers' => $month_used_accrual_ledgers

] = $result;

$vouchers = $result['vouchers']['vouchers'];

// Create array of office_cash and office_bank ids keys with zero values
$bank_accounts = array_map(function ($elem) {
    return 0;
}, array_flip(array_keys($month_opening_balance['bank'])));
$cash_accounts = array_map(function ($elem) {
    return 0;
}, array_flip(array_keys($month_opening_balance['cash'])));
$accrual_accounts = array_map(function ($elem) {
    return 0;
}, $journal->getAccrualOpeningBalances());


// Instatiate ledger column variables
$running_receivables_balance = $journal->getAccrualOpeningBalances()['receivables'];
$sum_receivables_income = 0;
$sum_receivables_expense = 0;

$running_payables_balance = $journal->getAccrualOpeningBalances()['payables'];
$sum_payables_income = 0;
$sum_payables_expense = 0;

$running_prepayments_balance = $journal->getAccrualOpeningBalances()['prepayments'];
$sum_prepayments_income = 0;
$sum_prepayments_expense = 0;

$running_depreciation_balance = $journal->getAccrualOpeningBalances()['depreciation'];
$sum_depreciation_income = 0;
$sum_depreciation_expense = 0;

$running_payroll_liability_balance = $journal->getAccrualOpeningBalances()['payroll_liability'];
$sum_payroll_liability_income = 0;
$sum_payroll_liability_expense = 0;

$running_bank_balance = $journal->getInitialBankRunningBalance();
$sum_bank_income = $bank_accounts;
$sum_bank_expense = $bank_accounts;

$running_petty_cash_balance = $journal->getInitialCashRunningBalance();
$sum_petty_cash_income = $cash_accounts;
$sum_petty_cash_expense = $cash_accounts;

?>

<hr />

<div class='row'>
    <div class='col-xs-12'>
        <table class='table table-bordered' style='white-space:nowrap;' id="journal">
            <thead>
                <tr>
                    <!-- Navigation row -->
                    <th><?=$journal->navigation(); ?></th>
                    <th colspan="<?= $journal->titleColspan(); ?>" style='text-align:center;'><?= $journal->title(); ?></th>
                </tr>
                <tr>
                    <!-- Ledger columns headers row -->
                    <th colspan='<?= $journal->journalDetailColumns; ?>'></th>
                    <?= $journal->bankLedgerColumnHeaders(); ?>
                    <?= $journal->cashLedgerColumnHeaders(); ?>
                    <?= $journal->accrualLedgerColumnHeaders(); ?>
                    <?= $journal->accountSpreadEmpty(); ?>
                </tr>
                <tr>
                    <!-- Ledger Opening Balances row -->
                    <th colspan='7'><?= get_phrase('balance_b/f'); ?></th>
                    <?= $journal->bankLedgerOpeningBalance(); ?>
                    <?= $journal->cashLedgerOpeningBalance(); ?>
                    <?= $journal->accrualLedgerOpeningBalance(); ?>
                    <?= $journal->incomeAccountsHeaderTitle(); ?>
                    <?= $journal->expenseAccountsHeaderTitle(); ?>
                </tr>
                <tr>
                    <!-- Accounts Code Title row -->
                    <th><?= get_phrase('journal_action', 'Action'); ?></th>
                    <th><?= get_phrase('transaction_journal_date', 'Date'); ?></th>
                    <th><?= get_phrase('voucher_type', 'Voucher Type'); ?></th>
                    <th><?= get_phrase('journal_voucher_number', 'Voucher No.'); ?></th>
                    <th><?= get_phrase('journal_payee_vendor', 'Payee Or Vendor'); ?></th>
                    <th><?= get_phrase('journal_description', 'Description'); ?></th>
                    <th><?= get_phrase('cheque_no_or_eft_no', 'CHQ/EFT No.'); ?></th>

                    <?= $journal->bankAccountsTitle(); ?>
                    <?= $journal->cashAccountsTitle(); ?>
                    <?= $journal->accrualAccountsTitle(); ?>
                    <?= $journal->incomeCodesTitle(); ?>
                    <?= $journal->expenseCodesTitle(); ?>

                </tr>
            </thead>

            <tbody>
                <?php
                foreach ($vouchers as $voucher_id => $voucher) {
                    [
                        'date' => $date,
                        'payee' => $payee,
                        'voucher_type_abbrev' => $voucher_type_abbrev,
                        'voucher_type_name' => $voucher_type_name,
                        'voucher_type_cash_account' => $voucher_type_cash_account,
                        'voucher_type_transaction_effect' => $voucher_type_transaction_effect,
                        'voucher_number' => $voucher_number,
                        'description' => $description,
                        'cleared' => $cleared,
                        'cleared_month' => $cleared_month,
                        'cheque_number' => $cheque_number,
                        'office_bank_id' => $office_bank_id,
                        'office_cash_id' => $office_cash_id,
                        'status_id' => $status_id,
                        'receiving_office_bank_id' => $receiving_office_bank_id,
                        'receiving_office_cash_id' => $receiving_office_cash_id,
                        'voucher_is_reversed' => $voucher_is_reversed,
                        'voucher_reversal_from' => $voucher_reversal_from,
                        'voucher_reversal_to' => $voucher_reversal_to,
                        'voucher_is_cleared' => $voucher_is_cleared,
                        'voucher_type_is_cheque_referenced' => $voucher_type_is_cheque_referenced,
                        'spread' => $spread

                    ] = $voucher;

                    ?>
                    <!-- Action Column -->
                    <tr>
                        <td>
                            <?= $journal->journalAction(
                                $voucher,
                                $voucher_id,
                                $mfr_submited_status,
                                $role_has_journal_update_permission,
                                $item_status,
                                $item_initial_item_status_id,
                                $item_max_approval_status_ids,
                                $check_if_financial_report_is_submitted
                            ); ?>
                        </td>
                        <td><?= date('jS M Y', strtotime($date)); ?></td>
                        <td>
                            <?= $journal->voucherSelection($voucher_id, $voucher_type_abbrev, $voucher_type_name, $cleared); ?>
                        </td>
                        <td>
                            <?= $journal->voucherNumberButton($voucher_id, $voucher_number); ?>
                        </td>

                        <td title='<?= (strlen($payee) > 50) ? $description : ""; ?>'>
                            <?= $journal->payeeLabel($payee, $voucher_id, $role_has_journal_update_permission, $voucher_is_reversed, $check_if_financial_report_is_submitted); ?>
                        </td>

                        <td title='<?= (strlen($description) > 50) ? $description : ""; ?>'>
                            <?= $journal->voucherDescription($voucher_id, $description, $role_has_journal_update_permission, $voucher_is_reversed, $check_if_financial_report_is_submitted); ?>
                        </td>

                        <td class='align-right'>
                            <?= $journal->formatBankReference($cheque_number, $voucher_type_abbrev) ?>
                        </td>

                        <?php

                        // Compute bank and cash running balances
                        $voucher_amount = array_sum(array_column($spread, 'transacted_amount'));

                        $journal->computeBankRunningBalances(
                            $voucher,
                            $voucher_amount,
                            $sum_bank_income,
                            $sum_bank_expense,
                            $bank_income,
                            $bank_expense,
                            $running_bank_balance
                        );

                        $journal->computeCashRunningBalances(
                            $voucher,
                            $voucher_amount,
                            $sum_petty_cash_income,
                            $sum_petty_cash_expense,
                            $cash_income,
                            $cash_expense,
                            $running_petty_cash_balance
                        );

                        $journal->computePayablesRunningBalances(
                            $voucher,
                            $voucher_amount,
                            $sum_payables_income,
                            $sum_payables_expense,
                            $payables_income,
                            $payables_expense,
                            $running_payables_balance
                        );

                        $journal->computeReceivablesRunningBalances(
                            $voucher,
                            $voucher_amount,
                            $sum_receivables_income,
                            $sum_receivables_expense,
                            $receivables_income,
                            $receivables_expense,
                            $running_receivables_balance
                        );

                        $journal->computePrepaymentsRunningBalances(
                            $voucher,
                            $voucher_amount,
                            $sum_prepayments_income,
                            $sum_prepayments_expense,
                            $prepayments_income,
                            $prepayments_expense,
                            $running_prepayments_balance
                        );
                        
                        $journal->computeDepreciationRunningBalances(
                            $voucher,
                            $voucher_amount,
                            $sum_depreciation_income,
                            $sum_depreciation_expense,
                            $depreciation_income,
                            $depreciation_expense,
                            $running_depreciation_balance
                        );

                        $journal->computePayrollLiabilityRunningBalances(
                            $voucher,
                            $voucher_amount,
                            $sum_payroll_liability_income,
                            $sum_payroll_liability_expense,
                            $payroll_liability_income,
                            $payroll_liability_expense,
                            $running_payroll_liability_balance
                        );

                        ?>

                        <?php foreach ($month_opening_balance['bank'] as $bank_id => $bank_account) { ?>
                            <?php
                            [
                                'bank_inc' => $bank_inc,
                                'bank_exp' => $bank_exp,
                                'bank_bal' => $bank_bal
                            ] = $journal->computeCurrentJournalRowBankBalance(
                                $voucher,
                                $bank_id,
                                $bank_income,
                                $bank_expense,
                                $running_bank_balance
                            );
                            ?>

                            <td class='align-right'><?= number_format($bank_inc, 2); ?></td>
                            <td class='align-right'><?= number_format($bank_exp, 2); ?></td>
                            <td class='align-right'><?= number_format($bank_bal, 2); ?></td>

                        <?php } ?>

                        <?php foreach ($month_opening_balance['cash'] as $cash_id => $cash_account) { ?>

                            <?php
                            [
                                'cash_inc' => $cash_inc,
                                'cash_exp' => $cash_exp,
                                'cash_bal' => $cash_bal
                            ] = $journal->computeCurrentJournalRowCashBalance(
                                $voucher,
                                $cash_id,
                                $cash_income,
                                $cash_expense,
                                $running_petty_cash_balance
                            );
                            ?>

                            <td class='align-right'><?= number_format($cash_inc, 2); ?></td>
                            <td class='align-right'><?= number_format($cash_exp, 2); ?></td>
                            <td class='align-right'><?= number_format($cash_bal, 2); ?></td>
                        <?php 
                        }

                            ['receivables_inc' => $receivables_inc, 'receivables_exp' => $receivables_exp,'receivables_bal' => $receivables_bal] = $journal->computeCurrentJournalRowReceivablesBalance(
                                $voucher,
                                $receivables_income,
                                $receivables_expense,
                                $running_receivables_balance
                            );
                            
                            ['payables_inc' => $payables_inc, 'payables_exp' => $payables_exp, 'payables_bal' => $payables_bal] = $journal->computeCurrentJournalRowPayablesBalance(
                                $voucher,
                                $payables_income,
                                $payables_expense,
                                $running_payables_balance
                            );
                          
                            ['prepayments_inc' => $prepayments_inc, 'prepayments_exp' => $prepayments_exp, 'prepayments_bal' => $prepayments_bal] = $journal->computeCurrentJournalRowPrepaymentsBalance(
                                $voucher,
                                $prepayments_income,
                                $prepayments_expense,
                                $running_prepayments_balance
                            );

                            ['depreciation_inc' => $depreciation_inc, 'depreciation_exp' => $depreciation_exp, 'depreciation_bal' => $depreciation_bal] = $journal->computeCurrentJournalRowDepreciationBalance(
                                    $voucher, 
                                    $depreciation_income, 
                                    $depreciation_expense, 
                                    $running_depreciation_balance
                            );
                        
                            ['payroll_liability_inc' => $payroll_liability_inc, 'payroll_liability_exp' => $payroll_liability_exp, 'payroll_liability_bal' => $payroll_liability_bal]= $journal->computeCurrentJournalRowPayrollLiabilityBalance(
                                $voucher, 
                                $payroll_liability_income, 
                                $payroll_liability_expense, 
                                $running_payroll_liability_balance
                            );
                        ?>
                        
                        <td class='align-right'><?= number_format($receivables_inc, 2); ?></td>
                        <td class='align-right'><?= number_format($receivables_exp, 2); ?></td>
                        <td class='align-right'><?= number_format($receivables_bal, 2); ?></td>

                        <td class='align-right'><?= number_format($payables_inc, 2); ?></td>
                        <td class='align-right'><?= number_format($payables_exp, 2); ?></td>
                        <td class='align-right'><?= number_format($payables_bal, 2); ?></td>

                        <td class='align-right'><?= number_format($prepayments_inc, 2); ?></td>
                        <td class='align-right'><?= number_format($prepayments_exp, 2); ?></td>
                        <td class='align-right'><?= number_format($prepayments_bal, 2); ?></td>

                        <td class='align-right'><?= number_format($depreciation_inc, 2); ?></td>
                        <td class='align-right'><?= number_format($depreciation_exp, 2); ?></td>
                        <td class='align-right'><?= number_format($depreciation_bal, 2); ?></td>

                        <td class='align-right'><?= number_format($payroll_liability_inc, 2); ?></td>
                        <td class='align-right'><?= number_format($payroll_liability_exp, 2); ?></td>
                        <td class='align-right'><?= number_format($payroll_liability_bal, 2); ?></td>

                        <?php
                        echo $journal->journalSpread($office_id, $spread, $voucher_type_cash_account, $voucher_type_transaction_effect);
                        ?>

                    </tr>
                <?php } ?>

            </tbody>
            <tfoot>
                <tr>
                    <td colspan='7'><?= get_phrase('total_and_balance_b/d'); ?></td>
                    <?php foreach ($month_opening_balance['bank'] as $office_bank_id => $bank_account) { ?>
                        <td class='align-right'><?= number_format($sum_bank_income[$office_bank_id], 2); ?></td>
                        <td class='align-right'><?= number_format($sum_bank_expense[$office_bank_id], 2); ?></td>
                        <td class='align-right'>
                            <?= number_format(($running_bank_balance[$office_bank_id] == 0 && $sum_bank_expense[$office_bank_id] == 0) && isset($month_opening_balance['balance']) ? $month_opening_balance['balance'][$office_bank_id]['amount'] : $running_bank_balance[$office_bank_id], 2); ?>
                        </td>
                    <?php } ?>

                    <?php foreach ($month_opening_balance['cash'] as $office_cash_id => $cash_account) { ?>
                        <td class='align-right'><?= number_format($sum_petty_cash_income[$office_cash_id], 2); ?></td>
                        <td class='align-right'><?= number_format($sum_petty_cash_expense[$office_cash_id], 2); ?></td>
                        <td class='align-right'>
                            <?= number_format(($running_petty_cash_balance[$office_cash_id] == 0 && $sum_petty_cash_expense[$office_cash_id] == 0) ? $month_opening_balance['cash'][$office_cash_id]['amount'] : $running_petty_cash_balance[$office_cash_id], 2); ?>
                        </td>
                    <?php } ?>

                    <!-- Spread totals -->
                    <?php foreach ($accounts['income'] as $income_account_id => $income_account_code) { ?>
                        <td class='total_income total_income_<?= $income_account_id; ?>'>0</td>
                    <?php } ?>

                    <?php foreach ($accounts['expense'] as $expense_account_id => $expense_account_code) { ?>
                        <td class='total_expense total_expense_<?= $expense_account_id; ?>'>0</td>
                    <?php } ?>

                </tr>
            </tfoot>
        </table>
    </div>
</div>


<script>



    $(document).ready(function () {

        //Modify the button
        var returnButtonClass = $('.item_action');

        $.each(returnButtonClass, function (i, e) {

            $(this).html("<i class='fa fa-arrow-left' style='cursor:pointer; font-size:13px;color:white'></i> Return");
        });

        //Fully approved hide
        $('.final_status').hide();

    });

    $('.btn_action').on('click', function () {

        var has_btn_danger = $(this).hasClass('btn-danger') ? true : false;

        if (has_btn_danger) {
            $(this).toggleClass('btn-success');
            alert('Cleared completed');
        } else {
            alert('Transaction cannot be uncleared. Use the financial report');
        }

    });

    $('.table').DataTable({
        dom: 'Bfrtip',
        //fixedHeader: true,
        "paging": false,
        stateSave: true,
        bSort: false,
        buttons: [{
            extend: 'excelHtml5',
            text: '<?= get_phrase('export_in_excel'); ?>',
            className: 'btn btn-default',
            exportOptions: {
                columns: 'th:not(:first-child)'
            }
        },
        {
            extend: 'pdfHtml5',
            className: 'btn btn-default',
            text: '<?= get_phrase('export_in_pdf'); ?>',
            orientation: 'landscape',
            exportOptions: {
                columns: 'th:not(:first-child)'
            }
        }
        ],
        "pagingType": "full_numbers"
    });

    $(".btn_reverse").on('click', function () {

        journal_month = '<?= $result['transacting_month']; ?>' // $('#transacting_month_id').val();


        var btn = $(this);
        var voucher_id = btn.data('voucher_id');


        //Check if the voucher has been reversed

        let reuse_cheque = btn.hasClass('re_use_cheque') ? 1 : 0;

        cancel_cheque = btn.hasClass('cancel_cheque') ? 1 : 0;

        let reuse_eft = btn.hasClass('re_use_eft') ? 1 : 0;

        let cancel_eft = btn.hasClass('cancel_eft') ? 1 : 0;

        let cnfrm = '';
        // let reuse_transaction = 0; 
        let is_reuse_cheque_transaction = 0; // Zero mean its a cancellation while 1 is a reuse

        let reusing_eft_or_chq_number = ''

        let aborted_message = ''

        if (reuse_cheque) {
            cnfrm = confirm('<?= get_phrase("reuse_chq", "Are you sure you want to reverse this voucher and reuse it\'s cheque number?") ?>');

            is_reuse_cheque_transaction = reuse_cheque;

            reusing_eft_or_chq_number = 'cheque';

            aborted_message = 'Reusing Cheque Transaction Aborted';

        } else if (cancel_cheque) {
            cnfrm = confirm('<?= get_phrase('cancel_chq', 'Are you sure you want to cancel cheque number and NEVER use it?') ?>');

            //reuse_transaction=cancel_cheque;

            reusing_eft_or_chq_number = 'cheque';

            aborted_message = 'Cancelling Cheque Transaction Aborted';

        } else if (reuse_eft) {
            cnfrm = confirm('<?= get_phrase('reuse_eft', 'Are you sure you want to reverse this voucher and reuse EFT number?'); ?>');

            reuse_transaction = reuse_eft;

            reusing_eft_or_chq_number = 'eft';

            aborted_message = 'Reusing EFT Transaction Aborted';

        } else if (cancel_eft) {
            cnfrm = confirm('<?= get_phrase('cancel_eft', 'Are you sure you want to cancel EFT number and NEVER use it?'); ?>');

            //reuse_transaction=cancel_eft;

            reusing_eft_or_chq_number = 'eft';

            aborted_message = 'Cancelling EFT Transaction Aborted';
        } else {
            cnfrm = confirm('<?= get_phrase('cancel_voucher', 'Are you sure you want to cancel the voucher?') ?>');

            aborted_message = 'Cancelling Voucher Transaction Aborted';
        }

        if (cnfrm) {

            btn.closest('td').find('.btn_reverse').addClass('disabled');
            //btn.remove();

            var url_check = "<?= base_url(); ?>ajax/journal/checkIfVoucherIsReversedOrCancelled/" + voucher_id

            $.get(url_check, function (response_voucher_cancelled) {

                if (parseInt(response_voucher_cancelled) == 1) {

                    alert('The voucher has been already cancelled or reused');

                    window.location.reload();

                    return false;

                } else {

                    if (reusing_eft_or_chq_number == '') {
                        reusing_eft_or_chq_number = 0;
                    }
                    if (journal_month == '') {
                        journal_month = 0;
                    }
                    var url = "<?= base_url(); ?>ajax/journal/reverseVoucher/" + voucher_id + "/" + is_reuse_cheque_transaction + "/" + reusing_eft_or_chq_number + "/" + journal_month;


                    console.log(url);
                    $.get(url, function (response) {

                        const obj = JSON.parse(response);
                        // console.log(obj);
                        // console.log(obj.message_code);
                        if (obj.message_code == 'success') {
                            window.location.reload();
                        } else {
                            btn.closest('td').find('.btn_reverse').removeClass('disabled');
                        }

                        alert(obj.message);


                    });
                }
            });

        } else {
            alert(aborted_message);
        }

    });

    $('.edit_journal').on('dblclick', function () {
        var parent_td = $(this).closest('td');
        var parent_td_content = parent_td.find('span.cell_content').html();
        var voucher_id = $(this).data('voucher_id');
        var reference_column = $(this).data('reference_column');


        parent_td.html("<input type='text' data-voucher_id = '" + voucher_id + "' data-reference_column = '" + reference_column + "' class='form-control input_content' value='" + parent_td_content + "' />");

    });

    $(document).on('change', '.input_content', function () {
        var voucher_id = $(this).data('voucher_id');
        var content = $(this).val();
        var reference_column = $(this).data('reference_column');
        var data = {
            'voucher_id': voucher_id,
            'column': reference_column,
            'content': content
        };
        var url = "<?= base_url(); ?>Journal/edit_journal_description";

        $.post(url, data, function (response) {
            alert(response);
        });

    });

    $('#journal tbody tr').click(function () {
        $(this).addClass('active').siblings().removeClass('active');
    });

    $('#select_all_vouchers').on('click', function () {
        // voucher_unselected
        let voucher_selection_status = $(this).hasClass('voucher_unselected')

        if (voucher_selection_status) {
            $('.select_voucher').prop('checked', true);
            $(this).removeClass('voucher_unselected')
            $(this).addClass('voucher_selected')
            $(this).text('<?= get_phrase('unselect_vouchers'); ?>')
            $('#print_vouchers').removeClass('hidden')
        } else {
            $('.select_voucher').prop('checked', false);
            $(this).removeClass('voucher_selected')
            $(this).addClass('voucher_unselected')
            $(this).text('<?= get_phrase('select_all_vouchers'); ?>')
            $('#print_vouchers').addClass('hidden')
        }
    })

    $('.select_voucher').on('change', function () {

        let anyChecked = $('.select_voucher:checked').length > 0;

        if ($(this).is(':checked')) {
            // If the checkbox is checked, remove the 'hidden' class from the button
            $('#print_vouchers').removeClass('hidden');
        } else {
            // If the checkbox is not checked, add the 'hidden' class to the button
            if (!anyChecked) {
                $('#print_vouchers').addClass('hidden');
            }
        }
    });
</script>