<i data-voucher_id='<?= $voucher_id; ?>' data-reference_column='voucher_vendor'
    class='fa fa-pencil edit_journal  <?= (!$role_has_journal_update_permission || $voucher_is_reversed || $check_if_financial_report_is_submitted) ? 'hidden' : ''; ?> '></i>
<span class='cell_content'><?= strlen($payee) > 50 ? substr($payee, 0, 50) . '...' : $payee; ?></span>