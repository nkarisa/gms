<a class='btn btn-danger' target="__blank"
    href='<?= base_url() . 'voucher/view/' . $related_voucher_id; ?>'><?= $reverse_btn_label; ?>
    [<?= get_related_voucher($voucher_reversal_to > 0 ? $voucher_reversal_to : $voucher_reversal_from); ?>]
</a>