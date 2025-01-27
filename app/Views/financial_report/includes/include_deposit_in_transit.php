<?php
//print_r($deposit_in_transit);
?>
<table class="table table-striped tbl_cleared_transit_deposit_connector" id='tbl_transit_deposit'>
    <thead>
        <tr>
            <th class = 'no-print'><?= get_phrase('action'); ?></th>
            <th><?= get_phrase('date'); ?></th>
            <th><?= get_phrase('description'); ?></th>
            <th><?= get_phrase('voucher_number'); ?></th>
            <th><?= get_phrase('voucher_vendor'); ?></th>
            
            <th><?= get_phrase('amount'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($deposit_in_transit as $deposit_in_transit_row) { ?>
            <tr>
                <?php
                $deposit_in_transit_state_color = "danger";
                //$oustanding_state_disabled = "";
                $deposit_in_transit_state_clear_class = 'to_clear';
                $deposit_in_transit_state_label = get_phrase('clear');
                if ($deposit_in_transit_row['voucher_cleared'] == 1) {
                    $deposit_in_transit_state_color = "success";
                    //$oustanding_state_disabled = "disabled";
                    //$oustanding_state_clear_class = '';
                    //$deposit_in_transit_state_label = get_phrase('unclear');
                }
                ?>
                <td class = 'no-print'>
                    <div data-opening_outstanding_cheque_id="0" data-opening_deposit_transit_id="<?= isset($deposit_in_transit_row['opening_deposit_transit_id']) ? $deposit_in_transit_row['opening_deposit_transit_id'] : 0; ?>" id="<?= $deposit_in_transit_row['voucher_id']; ?>" class='btn btn-<?= $deposit_in_transit_state_color; ?> clear_btn <?= $allow_mfr_reconciliation && $this->user_model->check_role_has_permissions(ucfirst($this->controller), 'update') ? '' : 'disabled'; ?> <?= $deposit_in_transit_state_clear_class; ?> deposit_in_transit active_effect state_<?= $deposit_in_transit_row['voucher_cleared']; ?>'>
                        <?= $deposit_in_transit_state_label; ?>
                    </div>
                </td>
                <?php
                  //Voucher_id
                  $voucher_id=$deposit_in_transit_row['voucher_id'];
                ?>
                <td><?= $deposit_in_transit_row['voucher_date']; ?></td>
                <td><?= $deposit_in_transit_row['voucher_description']; ?></td>
                <td><a href="<?=base_url()?>voucher/view/<?=hash_id($voucher_id)?>"><?= $deposit_in_transit_row['voucher_number']; ?></a></td>
                <td><?= $deposit_in_transit_row['voucher_vendor']; ?></td>
                <td class='td_row_amount'><?= number_format($deposit_in_transit_row['voucher_detail_total_cost'], 2); ?></td>
            </tr>
        <?php } ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan='5'><?= get_phrase('total'); ?></td>
            <td class='td_effects_total total_dt'><?= number_format(array_sum(array_column($deposit_in_transit, 'voucher_detail_total_cost')), 2); ?></td>
        </tr>
    </tfoot>
</table>

<script>

</script>