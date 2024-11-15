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

$financialReportLibrary = new \App\Libraries\Grants\FinancialReportLibrary();
$journalLibrary = new \App\Libraries\Grants\JournalLibrary();

extract($result['status_data']);
$result['mfr_submited_status'] = 0; // A stop gap waiting a discussion with Development Team on this matter so that ticket INC0218239 can be resolved. 
// Users should be able to reverse voucher even if the MFRs are submitted. This is important to allow handling stale cheques and invalid transactions


$sum_of_income_accounts = count($accounts['income']);
$sum_of_expense_accounts = count($accounts['expense']);
$sum_of_accounts = $sum_of_income_accounts + $sum_of_expense_accounts;

$role_has_journal_update_permission = $userLibrary->checkRoleHasPermissions(ucfirst($controller), 'update');
$check_if_financial_report_is_submitted = $financialReportLibrary->checkIfFinancialReportIsSubmitted([$office_id], $transacting_month);

?>
<input type='text' id='transacting_month_id' class='hidden' value='<?= $result['transacting_month']; ?>'>
<?php if (isset($office_bank_name)) { ?>
    <div class='row'>
        <div class='col-xs-12' style='font-weight:bold;text-align:center;'>
            <?= get_phrase('office_bank_cash_journal'); ?> : <?= $office_bank_name; ?>
        </div>
    </div>
<?php } ?>

<hr />

<div class='row'>
    <div class='col-xs-12'>
        <table class='table table-bordered' style='white-space:nowrap;' id="journal">
            <thead>
                <tr>
                    <th>
                        <?php if ($navigation['previous']) { ?>
                            <a class='pull-left'
                                href="<?= base_url(); ?>journal/view/<?= hash_id($navigation['previous']); ?>"
                                title='Previous Month'><i class='fa fa-minus-circle' style='font-size:20pt;'></i></a>
                        <?php } ?>

                        <?php if ($navigation['next']) { ?>
                            <a class='pull-right' href="<?= base_url(); ?>vournal/view/<?= hash_id($navigation['next']); ?>"
                                title='Next Month'><i class='fa fa-plus-circle' style='font-size:20pt;'></i></a>
                        <?php } ?>
                    </th>
                    <th colspan="<?= $sum_of_accounts + 5 + (count($month_opening_balance['bank']) * 3) + (count($month_opening_balance['cash']) * 3); ?>"
                        style='text-align:center;'>
                        <?= $office_name; ?></br>
                        <?= get_phrase('cash_journal'); ?> <br>
                        <?= date('F Y', strtotime($transacting_month)); ?>

                    </th>
                    <th>
                        <?php if ($navigation['next']) { ?>
                            <a class='pull-right' href="<?= base_url(); ?>journal/view/<?= hash_id($navigation['next']); ?>"
                                title='Next Month'><i class='fa fa-plus-circle' style='font-size:20pt;'></i></a>
                        <?php } ?>
                    </th>
                </tr>
                <tr>
                    <th colspan='7'></th>

                    <?php foreach ($month_opening_balance['bank'] as $office_bank_id => $bank_account) { ?>
                        <th colspan='3' style='text-align:center;'><?= get_phrase('bank'); ?>
                            (<?= $bank_account['account_name']; ?>)</th>
                    <?php } ?>

                    <?php foreach ($month_opening_balance['cash'] as $office_cash_id => $cash_account) { ?>
                        <th colspan='3' style='text-align:center;'><?= get_phrase('cash'); ?>
                            (<?= $cash_account['account_name']; ?>)</th>
                    <?php } ?>

                    <!-- <th colspan='3' style='text-align:center;'>Cash</th> -->
                    <?php if ($sum_of_accounts > 0) { ?>
                        <th colspan='<?= $sum_of_accounts; ?>'></th><?php } ?>
                </tr>
                <tr>
                    <th colspan='7'><?= get_phrase('balance_b/f'); ?></th>

                    <?php foreach ($month_opening_balance['bank'] as $office_bank_id => $bank_account) { ?>
                        <th colspan='3'><?= number_format($bank_account['amount'], 2); ?></th>
                    <?php } ?>

                    <?php foreach ($month_opening_balance['cash'] as $office_cash_id => $cash_account) { ?>
                        <th colspan='3'><?= number_format($cash_account['amount'], 2); ?></th>
                    <?php } ?>

                    <!-- <th colspan='3'><?= number_format(array_sum(array_column($month_opening_balance['cash'], 'amount')), 2); ?></th> -->

                    <?php if ($sum_of_income_accounts > 0) { ?>
                        <th colspan='<?= count($accounts['income']); ?>'><?= get_phrase('income'); ?></th><?php } ?>
                    <?php if ($sum_of_expense_accounts > 0) { ?>
                        <th colspan='<?= count($accounts['expense']); ?>'><?= get_phrase('expense'); ?></th><?php } ?>

                    <?php ?>
                </tr>
                <tr>
                    <th><?= get_phrase('journal_action', 'Action'); ?></th>
                    <th><?= get_phrase('transaction_journal_date', 'Date'); ?></th>
                    <th><?= get_phrase('voucher_type', 'Voucher Type'); ?></th>
                    <th><?= get_phrase('journal_voucher_number', 'Voucher No.'); ?></th>
                    <th><?= get_phrase('journal_payee_vendor', 'Payee Or Vendor'); ?></th>
                    <th><?= get_phrase('journal_description', 'Description'); ?></th>
                    <th><?= get_phrase('cheque_no_or_eft_no', 'CHQ/EFT No.'); ?></th>

                    <?php foreach ($month_opening_balance['bank'] as $office_bank_id => $bank_account) { ?>
                        <th><?= get_phrase('bank_income') . ' (' . $bank_account['account_name'] . ')'; ?></th>
                        <th><?= get_phrase('bank_expense') . ' (' . $bank_account['account_name'] . ')'; ?></th>
                        <th><?= get_phrase('balance') . ' (' . $bank_account['account_name'] . ')'; ?></th>
                    <?php } ?>

                    <?php foreach ($month_opening_balance['cash'] as $office_cash_id => $cash_account) { ?>
                        <th><?= $cash_account['account_name'] . ' ' . get_phrase('income'); ?></th>
                        <th><?= $cash_account['account_name'] . ' ' . get_phrase('expense'); ?></th>
                        <th><?= $cash_account['account_name'] . ' ' . get_phrase('balance'); ?></th>
                    <?php } ?>


                    <?php foreach ($accounts['income'] as $income_account_code) { ?>
                        <th><?= $income_account_code; ?></th>
                    <?php } ?>

                    <?php foreach ($accounts['expense'] as $expense_account_code) { ?>
                        <th><?= $expense_account_code; ?></th>
                    <?php } ?>

                </tr>
            </thead>

            <tbody>
                <?php
                // Create array of office_cash and office_bank ids keys with zero values
                
                $bank_accounts = array_map(function ($elem) {
                    return 0;
                }, array_flip(array_keys($month_opening_balance['bank'])));
                $cash_accounts = array_map(function ($elem) {
                    return 0;
                }, array_flip(array_keys($month_opening_balance['cash'])));

                // Imstantiate empty cash and bank balances
                $running_bank_balance = $bank_accounts;
                $sum_bank_income = $bank_accounts;
                $sum_bank_expense = $bank_accounts;

                $running_petty_cash_balance = $cash_accounts;
                $sum_petty_cash_income = $cash_accounts;
                $sum_petty_cash_expense = $cash_accounts;

                //print_r($vouchers);
                
                foreach ($vouchers as $voucher_id => $voucher) {
                    extract($voucher);
                    // echo json_encode($voucher);
                    ?>
                    <!-- Action Column -->
                    <tr>
                        <td>

                            <?php
                            if ($voucher_is_reversed && ($voucher_reversal_from || $voucher_reversal_to)) {

                                $related_voucher_id = hash_id($voucher_reversal_from, 'encode');
                                $reverse_btn_label = get_phrase('linked_source');

                                if (!$voucher_reversal_from) {
                                    $related_voucher_id = hash_id($voucher_reversal_to, 'encode');
                                    $reverse_btn_label = get_phrase('linked_destination');
                                }
                                ?>
                                <a class='btn btn-danger' target="__blank"
                                    href='<?= base_url() . 'voucher/view/' . $related_voucher_id; ?>'><?= $reverse_btn_label; ?>
                                    [<?= get_related_voucher($voucher_reversal_to > 0 ? $voucher_reversal_to : $voucher_reversal_from); ?>]</a>
                            <?php } ?>

                            <?php

                            //$logged_role_id = $this->session->role_ids;
                        
                            $disable_flag = '';

                            if ($voucher_is_reversed != 1 && $mfr_submited_status != 1) {

                                $disable_flag = !$role_has_journal_update_permission ? true : false;

                                if ($disable_flag) { ?>

                                    <div data-voucher_id='<?= $voucher_id; ?>'
                                        class='btn btn-info   <?= !$role_has_journal_update_permission ? "disabled" : ''; ?>  <?= $voucher_is_cleared || $voucher_is_cleared ? "hidden" : ""; ?>'>
                                        <i class='fa fa-arrow-left' style='cursor:pointer; font-size:18px;color:white'></i>
                                        <?= get_phrase('return'); ?>
                                    </div>

                                <?php }

                                echo approval_action_button('voucher', $item_status, $voucher_id, $status_id, $item_initial_item_status_id, $item_max_approval_status_ids);
                            } ?>



                            <?php if ($voucher_type_is_cheque_referenced == 0) {


                                //$reuse_flag_when_eft_used= $cheque_number!=0?'re_use':"";
                                $cancel_eft_class = $cheque_number != 0 && $cheque_number != '' ? 'cancel_eft' : '';

                                $reuse_flag_when_eft_used = '';

                                if (is_numeric($cheque_number)) {

                                    $reuse_flag_when_eft_used = $cheque_number != 0 ? 're_use_eft' : '';
                                } else if (!is_numeric($cheque_number) && $cheque_number != '') {
                                    $reuse_flag_when_eft_used = 're_use_eft';
                                }


                                //echo $eft_or_chq;
                        
                                ?>
                                <?php


                                if ($mfr_submited_status) { ?>

                                    <div data-voucher_id='<?= $voucher_id; ?>'
                                        class='btn btn_reverse  <?= $cancel_eft_class; ?> <?= !$role_has_journal_update_permission ? "disabled" : ''; ?> <?= $voucher_is_reversed || $voucher_is_cleared ? "hidden" : ""; ?> <?= $voucher_is_cleared ? "hidden" : ""; ?>'>
                                        <i class='fa fa-close' style='cursor:pointer; font-size:20px;color:red'></i>
                                        <?= get_phrase('cancel'); ?>
                                    </div>
                                <?php } ?>

                                <!-- Re-use -->
                                <!-- Show reuse if previous month and hide it in current months -->

                                <?php
                                if ($reuse_flag_when_eft_used != "") { ?>

                                    <div data-voucher_id='<?= $voucher_id; ?>'
                                        class='btn btn_reverse  eft <?= $reuse_flag_when_eft_used; ?> <?= !$role_has_journal_update_permission || $result['mfr_submited_status'] == 1 ? "disabled" : ''; ?> <?= $voucher_is_reversed || $voucher_is_cleared ? "hidden" : ""; ?> <?= $voucher_is_cleared ? "hidden" : ""; ?>'>
                                        <i class='fa fa-undo' style='cursor:pointer; font-size:20px;color:white'></i>

                                        <?= get_phrase('use_eft', $reuse_flag_when_eft_used); ?>

                                    </div>

                                <?php } ?>


                            <?php } else {
                                $cancel_cheque_class = $cheque_number != 0 ? 'cancel_cheque' : '';

                                if ($mfr_submited_status) { ?>

                                    <div data-voucher_id='<?= $voucher_id; ?>'
                                        class='btn btn_reverse <?= $cancel_cheque_class; ?> <?= !$role_has_journal_update_permission || $result['mfr_submited_status'] == 1 ? "disabled" : ''; ?> <?= $voucher_is_reversed || $voucher_is_cleared ? "hidden" : ""; ?> <?= $voucher_is_cleared && $result['mfr_submited_status'] == 0 && !$role_has_journal_update_permission ? "disabled" : ""; ?>'>
                                        <i class='fa fa-close' style='cursor:pointer; font-size:18px;color:red'></i>
                                        <?= get_phrase('cancel'); ?>
                                    </div>

                                <?php }

                                //Only display re-use button
                                if ($check_if_financial_report_is_submitted == 1) { ?>

                                    <div data-voucher_id='<?= $voucher_id; ?>'
                                        class='btn btn_reverse re_use_cheque <?= !$role_has_journal_update_permission || $result['mfr_submited_status'] == 1 ? "disabled" : ''; ?> <?= $voucher_is_reversed || $voucher_is_cleared ? "hidden" : ""; ?> '>
                                        <i class='fa fa-undo' style='cursor:pointer; font-size:20px;color:yellow'></i>
                                        <?= get_phrase('re_use_cheque'); ?>

                                    </div>
                                <?php }
                            } ?>



                        </td>
                        <td><?= date('jS M Y', strtotime($date)); ?></td>
                        <td><input type='checkbox' name='selected_voucher[]' class='select_voucher'
                                value='<?= $voucher_id; ?>' /> <span title="<?= $voucher_type_name; ?>"
                                class="label <?= $cleared ? 'btn-success' : 'btn-warning'; ?>"><?= service("settings")->get("GrantsConfig.use_voucher_type_abbreviation") ? $voucher_type_abbrev : $voucher_type_name; ?><span>
                        </td>
                        <td>
                            <a href="<?= base_url(); ?>voucher/view/<?= hash_id($voucher_id); ?>" target="__blank">
                                <div class='btn btn-default'><?= $voucher_number; ?></div>
                            </a>
                        </td>

                        <td title='<?php if (strlen($payee) > 50)
                            echo $description; ?>'>
                            <i data-voucher_id='<?= $voucher_id; ?>' data-reference_column='voucher_vendor'
                                class='fa fa-pencil edit_journal  <?= (!$role_has_journal_update_permission || $voucher_is_reversed || $check_if_financial_report_is_submitted) ? 'hidden' : ''; ?> '></i>
                            <span
                                class='cell_content'><?= strlen($payee) > 50 ? substr($payee, 0, 50) . '...' : $payee; ?></span>
                        </td>

                        <td title='<?php if (strlen($description) > 50)
                            echo $description; ?>'>
                            <i data-voucher_id='<?= $voucher_id; ?>' data-reference_column='voucher_description'
                                class='fa fa-pencil edit_journal  <?= (!$role_has_journal_update_permission || $voucher_is_reversed || $check_if_financial_report_is_submitted) ? 'hidden' : ''; ?> '></i>
                            <span
                                class='cell_content'><?= strlen($description) > 50 ? substr($description, 0, 50) . '...' : $description; ?></span>
                        </td>

                        <td class='align-right'>
                            <?php
                            $eft_or_chq = '';

                            //echo $cheque_number !=''  ? $cheque_number .' ['.$voucher_type_abbrev.']' : '';
                        
                            if (!is_numeric($cheque_number)) {

                                $eft_or_chq = $cheque_number . ' [' . $voucher_type_abbrev . ']';
                            } else if (is_numeric($cheque_number)) {

                                $eft_or_chq = $cheque_number != 0 ? $cheque_number . ' [' . $voucher_type_abbrev . ']' : '';
                            }
                            echo $eft_or_chq;

                            //!$voucher_is_reversed?(!$cheque_number?'':$cheque_number):$cheque_number;
                            ?>
                        </td>

                        <?php

                        // Compute bank and cash running balances
                        $voucher_amount = array_sum(array_column($spread, 'transacted_amount'));

                        if ($receiving_office_bank_id && is_int($receiving_office_bank_id) && isset($sum_bank_income[$receiving_office_bank_id])) {

                            $bank_income[$receiving_office_bank_id] = ($voucher_type_cash_account == 'bank' && $voucher_type_transaction_effect == 'bank_to_bank_contra') ? $voucher_amount : 0;
                            $bank_expense[$receiving_office_bank_id] = 0;

                            $sum_bank_income[$receiving_office_bank_id] = $sum_bank_income[$receiving_office_bank_id] + $bank_income[$receiving_office_bank_id];
                            $sum_bank_expense[$receiving_office_bank_id] = $sum_bank_expense[$receiving_office_bank_id] + $bank_expense[$receiving_office_bank_id];

                            $running_bank_balance[$receiving_office_bank_id] = $month_opening_balance['bank'][$receiving_office_bank_id]['amount'] + ($sum_bank_income[$receiving_office_bank_id] - $sum_bank_expense[$receiving_office_bank_id]);
                        }

                        if ($receiving_office_cash_id && is_int($receiving_office_cash_id) && isset($sum_petty_cash_income[$receiving_office_cash_id])) {

                            $cash_income[$receiving_office_cash_id] = ($voucher_type_cash_account == 'cash' && $voucher_type_transaction_effect == 'cash_to_cash_contra') ? $voucher_amount : 0;
                            $cash_expense[$receiving_office_cash_id] = 0;

                            $sum_petty_cash_income[$receiving_office_cash_id] = $sum_petty_cash_income[$receiving_office_cash_id] + $cash_income[$receiving_office_cash_id];
                            $sum_petty_cash_expense[$receiving_office_cash_id] = $sum_petty_cash_expense[$receiving_office_cash_id] + $cash_expense[$receiving_office_cash_id];

                            $running_petty_cash_balance[$receiving_office_cash_id] = $month_opening_balance['cash'][$receiving_office_cash_id]['amount'] + ($sum_petty_cash_income[$receiving_office_cash_id] - $sum_petty_cash_expense[$receiving_office_cash_id]);
                        }

                        if ($office_bank_id && isset($sum_bank_income[$office_bank_id])) {
                            $bank_income[$office_bank_id] = (($voucher_type_cash_account == 'bank' && $voucher_type_transaction_effect == 'income') || ($voucher_type_cash_account == 'cash' && $voucher_type_transaction_effect == 'cash_contra')) ? $voucher_amount : 0;
                            $bank_expense[$office_bank_id] = (($voucher_type_cash_account == 'bank' && $voucher_type_transaction_effect == 'expense') || ($voucher_type_cash_account == 'bank' && ($voucher_type_transaction_effect == 'bank_contra' || $voucher_type_transaction_effect == 'bank_to_bank_contra'))) ? $voucher_amount : 0;

                            $sum_bank_income[$office_bank_id] = $sum_bank_income[$office_bank_id] + $bank_income[$office_bank_id];
                            $sum_bank_expense[$office_bank_id] = $sum_bank_expense[$office_bank_id] + $bank_expense[$office_bank_id];

                            $running_bank_balance[$office_bank_id] = $month_opening_balance['bank'][$office_bank_id]['amount'] + ($sum_bank_income[$office_bank_id] - $sum_bank_expense[$office_bank_id]);
                        }

                        if ($office_cash_id && isset($sum_petty_cash_income[$office_cash_id])) {
                            $cash_income[$office_cash_id] = (($voucher_type_cash_account == 'cash' && $voucher_type_transaction_effect == 'income') || ($voucher_type_cash_account == 'bank' && $voucher_type_transaction_effect == 'bank_contra')) ? $voucher_amount : 0;
                            $cash_expense[$office_cash_id] = (($voucher_type_cash_account == 'cash' && $voucher_type_transaction_effect == 'expense') || ($voucher_type_cash_account == 'cash' && $voucher_type_transaction_effect == 'cash_contra' || $voucher_type_transaction_effect == 'cash_to_cash_contra')) ? $voucher_amount : 0;

                            $sum_petty_cash_income[$office_cash_id] = $sum_petty_cash_income[$office_cash_id] + $cash_income[$office_cash_id];
                            $sum_petty_cash_expense[$office_cash_id] = $sum_petty_cash_expense[$office_cash_id] + $cash_expense[$office_cash_id];

                            $running_petty_cash_balance[$office_cash_id] = $month_opening_balance['cash'][$office_cash_id]['amount'] + ($sum_petty_cash_income[$office_cash_id] - $sum_petty_cash_expense[$office_cash_id]);
                        }

                        ?>

                        <?php foreach ($month_opening_balance['bank'] as $bank_id => $bank_account) { ?>
                            <?php
                            $bank_inc = 0;
                            $bank_exp = 0;
                            $bank_bal = 0;

                            if ($bank_id == $office_bank_id) {
                                $bank_inc = $bank_income[$office_bank_id];
                                $bank_exp = $bank_expense[$office_bank_id];
                                $bank_bal = $running_bank_balance[$office_bank_id];
                            }

                            if ($bank_id == $receiving_office_bank_id) {
                                $bank_inc = $bank_income[$receiving_office_bank_id];
                                $bank_exp = $bank_expense[$receiving_office_bank_id];
                                $bank_bal = $running_bank_balance[$receiving_office_bank_id];
                            }
                            ?>

                            <td class='align-right'><?= number_format($bank_inc, 2); ?></td>
                            <td class='align-right'><?= number_format($bank_exp, 2); ?></td>
                            <td class='align-right'><?= number_format($bank_bal, 2); ?></td>

                        <?php } ?>

                        <?php foreach ($month_opening_balance['cash'] as $cash_id => $cash_account) { ?>

                            <?php
                            $cash_inc = 0;
                            $cash_exp = 0;
                            $cash_bal = 0;

                            if ($cash_id == $office_cash_id) {
                                $cash_inc = $cash_income[$office_cash_id];
                                $cash_exp = $cash_expense[$office_cash_id];
                                $cash_bal = $running_petty_cash_balance[$office_cash_id];
                            }

                            if ($cash_id == $receiving_office_cash_id) {
                                $cash_inc = $cash_income[$receiving_office_cash_id];
                                $cash_exp = $cash_expense[$receiving_office_cash_id];
                                $cash_bal = $running_petty_cash_balance[$receiving_office_cash_id];
                            }
                            ?>

                            <td class='align-right'><?= number_format($cash_inc, 2); ?></td>
                            <td class='align-right'><?= number_format($cash_exp, 2); ?></td>
                            <td class='align-right'><?= number_format($cash_bal, 2); ?></td>
                        <?php } ?>

                        <?php
                        echo $journalLibrary->journalSpread($office_id, $spread, $transacting_month, $voucher_type_cash_account, $voucher_type_transaction_effect);
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
                            <?= number_format(($running_bank_balance[$office_bank_id] == 0 && $sum_bank_expense[$office_bank_id] == 0) ? $month_opening_balance['balance'][$office_bank_id]['amount'] : $running_bank_balance[$office_bank_id], 2); ?>
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

        journal_month = $('#transacting_month_id').val();


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

            var url_check = "<?= base_url(); ?>Journal/check_if_voucher_is_reversed_or_cancelled/" + voucher_id

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
                    var url = "<?= base_url(); ?>Journal/reverse_voucher/" + voucher_id + "/" + is_reuse_cheque_transaction + "/" + reusing_eft_or_chq_number + "/" + journal_month;

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