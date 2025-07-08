<?php if ($hasVoucherCreatePermission) { ?>
        <div 
            data-voucher_id = "<?= $voucherId; ?>" 
            class = "btn btn-success clear_accrual"
            data-toggle="modal"
        >
            <?= get_phrase('clear_accrual'); ?>
        </div>
<?php } else { ?>
        <div class = "btn btn-info disabled">
            <?= get_phrase('clear_accrual'); ?>
        </div>
<?php } ?>