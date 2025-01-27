<table class="table table-striped tbl_cleared_outstanding_cheque_connector" id='tbl_outstanding_cheque'>
    <thead>
        <tr>
            <th class = 'no-print'><?= get_phrase('action'); ?></th>

            <th><?= get_phrase('date'); ?></th>
            <th><?= get_phrase('description'); ?></th>
            <th><?= get_phrase('cheque_number'); ?></th>

            <th><?= get_phrase('voucher_number'); ?></th>
            <th><?= get_phrase('voucher_vendor'); ?></th>
            <th><?= get_phrase('amount'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php
        $oustanding_state_color = "danger";
        //     print_r($this->general_model->get_max_approval_status_id('voucher'));
        //     echo '<br>';
        //print_r($outstanding_cheques);
        if (sizeof($outstanding_cheques) > 0) {


            foreach ($outstanding_cheques as $outstanding_cheque) {
                $style = $outstanding_cheque['voucher_id'] == 0 ? "color:#FF0000" : '';
        ?>

                <tr style="<?= $style; ?>">
                    <?php

                    $oustanding_state_color = "danger";
                    //$oustanding_state_disabled = "";
                    $oustanding_state_clear_class = 'to_clear';
                    $oustanding_state_label = get_phrase('clear');
                    // print_r($outstanding_cheque);
                    if (isset($outstanding_cheque['bounce_flag']) && $outstanding_cheque['bounce_flag'] == 1 && $outstanding_cheque['voucher_cleared'] == 1) {
                        $oustanding_state_label = get_phrase('cancelled');
                    }



                    if ($outstanding_cheque['voucher_cleared'] == 1) {
                        $oustanding_state_color = "success";

                        //$oustanding_state_disabled = "disabled";
                        $oustanding_state_clear_class = '';
                        //$oustanding_state_label = get_phrase('unclear');
                    } ?>
                    <td nowrap class = 'no-print'>
                        <div data-data-opening_deposit_transit_id="0" data-opening_outstanding_cheque_id="<?= isset($outstanding_cheque['opening_outstanding_cheque_id']) ? $outstanding_cheque['opening_outstanding_cheque_id'] : 0; ?>" id="<?= $outstanding_cheque['voucher_id']; ?>" class='btn btn-<?= $oustanding_state_color; ?> <?= $allow_mfr_reconciliation && $this->user_model->check_role_has_permissions(ucfirst($this->controller), 'update') ? '' : 'disabled'; ?> clear_btn <?= $oustanding_state_clear_class; ?> outstanding_cheque active_effect state_<?= $outstanding_cheque['voucher_cleared']; ?>'>
                            <?= $oustanding_state_label; ?>
                        </div>
                        <?php
                        //Openning Outstanding Label
                        if ($outstanding_cheque['voucher_id'] == 0 && $financial_report_submitted != true) { ?>
                            <div data-data-opening_deposit_transit_id="0" data-opening_outstanding_cheque_id="<?= isset($outstanding_cheque['opening_outstanding_cheque_id']) ? $outstanding_cheque['opening_outstanding_cheque_id'] . '_bounce' : 0; ?>" id="<?= $outstanding_cheque['voucher_id']; ?>" class='btn btn-<?= $oustanding_state_color; ?> <?= $allow_mfr_reconciliation && $this->user_model->check_role_has_permissions(ucfirst($this->controller), 'update') ? '' : 'disabled'; ?> cancel_btn <?= $oustanding_state_clear_class; ?> outstanding_cheque active_effect state_<?= $outstanding_cheque['voucher_cleared']; ?>'>
                                <?= get_phrase('cancel'); ?>
                            </div>
                        <?php } ?>
                    </td>

                    <td><?= $outstanding_cheque['voucher_date']; ?></td>
                    <td><?= $outstanding_cheque['voucher_description']; ?></td>
                    <td><?= $outstanding_cheque['voucher_cheque_number']; ?></td>
                   <?php 
                     //Voucher_id
                     $voucher_id=$outstanding_cheque['voucher_id'];
                     
                    ?>
                    <td><a href="<?=base_url()?>voucher/view/<?=hash_id($voucher_id)?>"><?= isset($outstanding_cheque['voucher_number'])?$outstanding_cheque['voucher_number']:0; ?></a></td>
                    <td><?= isset($outstanding_cheque['voucher_vendor'])?$outstanding_cheque['voucher_vendor']:$outstanding_cheque['voucher_description']; ?></td>

                    <td class='td_row_amount'><?= number_format($outstanding_cheque['voucher_detail_total_cost'], 2); ?></td>
                </tr>
        <?php }
        } else {
            $no_data = get_phrase('no_uncleared_cheques_/_eft');
        }
        ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan='6'><?= get_phrase('total'); ?></td>

            <td class='td_effects_total total_oc' id='total_oc_from_list'><?= empty($outstanding_cheques) ? $no_data : number_format(array_sum(array_column($outstanding_cheques, 'voucher_detail_total_cost')), 2); ?></td>
        </tr>
    </tfoot>
</table>