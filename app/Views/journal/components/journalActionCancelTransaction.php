<div data-voucher_id='<?= $voucher_id; ?>'
    class='btn btn_reverse <?= $cancel_cheque_class; ?> <?= !$role_has_journal_update_permission || $mfr_submited_status == 1 ? "disabled" : ''; ?> <?= $voucher_is_reversed || $voucher_is_cleared ? "hidden" : ""; ?> <?= $voucher_is_cleared && $mfr_submited_status == 0 && !$role_has_journal_update_permission ? "disabled" : ""; ?>'>
    <i class='fa fa-close' style='cursor:pointer; font-size:18px;color:red'></i>
    <?= get_phrase('cancel_transaction', 'Cancel'); ?>
</div>