<div data-voucher_id='<?= $voucher_id; ?>'
    class='btn btn_reverse re_use_cheque <?= !$role_has_journal_update_permission || $mfr_submited_status == 1 ? "disabled" : ''; ?> <?= $voucher_is_reversed || $voucher_is_cleared ? "hidden" : ""; ?> '>
    <i class='fa fa-undo' style='cursor:pointer; font-size:20px;color:yellow'></i>
    <?= get_phrase('re_use_cheque'); ?>
</div>