<div data-voucher_id='<?= $voucher_id; ?>'
    class='btn btn_reverse  eft <?= $reuse_flag_when_eft_used; ?> <?= !$role_has_journal_update_permission || $mfr_submited_status == 1 ? "disabled" : ''; ?> <?= $voucher_is_reversed || $voucher_is_cleared ? "hidden" : ""; ?> <?= $voucher_is_cleared ? "hidden" : ""; ?>'>
    <i class='fa fa-undo' style='cursor:pointer; font-size:20px;color:white'></i>

    <?= get_phrase('use_eft', $reuse_flag_when_eft_used); ?>

</div>