<style>
    @media print {
        .no-print {
            display: none;
        }
    }
</style>

<div class='row form_rows visible-print'>
    <div class='col-xs-12' style='text-align:center;'>
        <?= show_logo($header['office_id']); ?>
    </div>
    <div class='col-xs-12' style='text-align:center;margin-top:60px;'>
        <?= $header['office_name'] ?> <?= get_phrase('payment_voucher'); ?>
    </div>
</div>

<hr class='visible-print' />

<div class="row form_rows">
    <div class="col-xs-4 no-print"><span class='span_label'><?= get_phrase('office_name'); ?>:</span> <?= $header['office_name'] ?></div>

    <div class="col-xs-4"><span class='span_label'><?= get_phrase('voucher_date'); ?>:</span> <?= $header['voucher_date'] ?></div>

    <div class="col-xs-4"><span class='span_label'><?= get_phrase('voucher_number'); ?>:</span> <?= service("settings")->get('GrantsConfig.append_office_code_to_voucher_number') ? $header['office_code'] . '-' : ""; ?><?= $header['voucher_number'] ?></div>
</div>

<hr />

<div class="row form_rows">

    <div class="col-xs-3"><span class='span_label'><?= get_phrase('voucher_type'); ?>:</span> <?= $header['voucher_type_name'] ?></div>

    <?php
    if ($header['source_account'] != "") {
    ?>
        <div class="col-xs-3"><span class='span_label'><?= get_phrase('source_account'); ?>:</span> <?= $header['source_account'] ?></div>
    <?php
    }

    if ($header['destination_account'] != "") {
    ?>
        <div class="col-xs-3"><span class='span_label'><?= get_phrase('destination_account'); ?>:</span> <?= $header['destination_account'] ?></div>
    <?php
    }
    ?>

    <?php
    if ($header['voucher_cheque_number'] > 0) {
    ?>
        <div class="col-xs-3"><span class='span_label'><?= get_phrase('cheque_number'); ?>:</span> <?= $header['voucher_cheque_number']; ?></div>
    <?php
    }
    ?>

</div>

<hr />

<div class="row form_rows">
    <div class="col-xs-12"><span class='span_label'><?= get_phrase('vendor'); ?>:</span> <?= $header['voucher_vendor'] ?></div>
</div>

<hr />

<div class="row form_rows">
    <div class="col-xs-12"><span class='span_label'><?= get_phrase('vendor_address'); ?>:</span> <?= $header['voucher_vendor_address'] ?></div>
</div>

<hr />

<div class="row form_rows">
    <div class="col-xs-12"><span class='span_label'><?= get_phrase('voucher_description'); ?>:</span> <?= $header['voucher_description'] ?> <?php echo $header['voucher_reversal_to'] > 0 ? ' [' . get_phrase('voucher_reversed_to_') . ' ' . get_related_voucher($header['voucher_reversal_to']) . ']' : ''; ?></div>
</div>

<hr />

<div class="row form_rows">
    <div class="col-xs-12">
        <table class='table table-striped'>
            <thead>
                <tr>
                    <th><?= get_phrase('quantity'); ?></th>
                    <th><?= get_phrase('description'); ?></th>
                    <th><?= get_phrase('unit_cost'); ?></th>
                    <th><?= get_phrase('total_cost'); ?></th>
                    <th><?= get_phrase('account'); ?></th>
                    <th><?= get_phrase('project_code'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($body as $row) { ?>
                    <tr>
                        <td><?= number_format($row['quantity'], 2) ?></td>
                        <td><?= $row['description'] ?></td>
                        <td><?= number_format($row['unitcost'], 2) ?></td>
                        <td><?= number_format($row['totalcost'], 2) ?></td>
                        <td><?= $row['account_code'] ?></td>
                        <td><?= $row['project_allocation_code'] ?></td>
                    </tr>
                <?php } ?>
            </tbody>

            <tfoot>
                <tr>
                    <td colspan='3'><?= get_phrase('voucher_total'); ?></td>
                    <td colspan='3'><?= number_format(array_sum(array_column($body, 'totalcost')), 2); ?></td>
                </tr>
                <tr>
                    <td colspan='2'><span style='font-weight:bold;'><?= get_phrase('raised_by'); ?>:</span> <?= ucwords($raiser_approver_info['full_name']).' ('.$raiser_approver_info['role_name'].')';?></td>
                    <td colspan='2'><span style='font-weight:bold;'><?= get_phrase('signature'); ?>:</span>___________________________</td>
                    <td colspan="2"><span style='font-weight:bold;'><?= get_phrase('date') ?>: </span><?=format_date($header['voucher_created_date']);?></td>
                </tr>

                <?php 
                    $approval_steps = approval_steps($account_system_id, 'voucher');
                    $voucher_approvers = $header['voucher_approvers'];
                    $ordered_voucher_approvers = [];
                    
                    if(!empty($voucher_approvers)) {
                        foreach($voucher_approvers as $voucher_approver){
                            $ordered_voucher_approvers[$voucher_approver->status_id] = $voucher_approver;
                        }
                    }
                    
                    $status_id = array_column($approval_steps,'status_id');

                    foreach($approval_steps as $approval_step){
                        $approver_label = $approval_step['status_signatory_label'] == NULL || $approval_step['status_signatory_label'] == "" ? $approval_step['status_name'] : $approval_step['status_signatory_label'];
                        $approver_name = array_key_exists($approval_step['status_id'], $ordered_voucher_approvers) ? ucwords($ordered_voucher_approvers[$approval_step['status_id']]->fullname).' ('.$ordered_voucher_approvers[$approval_step['status_id']]->user_role_name.') ' : '_______________________________';
                        $reinstatement_flag = array_key_exists($approval_step['status_id'], $ordered_voucher_approvers) && $ordered_voucher_approvers[$approval_step['status_id']]->reinstatement_status_id > 0  ? '<span style="color:red;">('.get_phrase('reinstatement').')</span>' : '';
                ?>
                    <tr>
                        <td colspan='2'><span style='font-weight:bold;'><?=$approver_label .' '. $reinstatement_flag;?>: </span><?=$approver_name;?></td>
                        <td colspan='2'><span style='font-weight:bold;'><?= get_phrase('signature'); ?>:</span>___________________________</td>
                        <td colspan="2"><span style='font-weight:bold;'><?= get_phrase('date') ?>: </span><?=array_key_exists($approval_step['status_id'], $ordered_voucher_approvers) ? format_date($ordered_voucher_approvers[$approval_step['status_id']]->approval_date) : '____ /____ /'.date('Y');?></td>
                    </tr>
                <?php 
                    }
                ?>

            </tfoot>
        </table>
    </div>
</div>