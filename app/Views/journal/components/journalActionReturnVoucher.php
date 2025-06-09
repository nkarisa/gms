<div data-voucher_id='<?= $voucher_id; ?>'
    class='btn btn-info   <?= !$role_has_journal_update_permission ? "disabled" : ''; ?>  <?= $voucher_is_reversed || $voucher_is_cleared  ? "hidden" : ""; ?>'>
    <i class='fa fa-arrow-left' style='cursor:pointer; font-size:18px;color:white'></i>
    <?= get_phrase('return'); ?>
</div>