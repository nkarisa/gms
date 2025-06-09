<div data-voucher_id='<?= $voucher_id; ?>'
    class='btn btn_reverse  <?= $cancel_eft_class; ?> <?= !$role_has_journal_update_permission ? "disabled" : ''; ?> <?= $voucher_is_reversed || $voucher_is_cleared ? "hidden" : ""; ?> <?= $voucher_is_cleared ? "hidden" : ""; ?>'>
    <i class='fa fa-close' style='cursor:pointer; font-size:20px;color:red'></i>
    <?= get_phrase('cancel'); ?>
</div>