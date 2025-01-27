<?php

use \App\Libraries\System\Widgets\WidgetBase;

//print_r($result);
extract($result);
//extract($result2);
extract($budget_status_data);

$months = array_column($months, 'month_name');
?>

<style>
    .row {
        margin: 20px;
    }
</style>

<div class="row">
    <div class="col-xs-12">
        <?= WidgetBase::load('comment'); ?>
    </div>
</div>

<div class='row'>

    <div class='col-xs-offset-2 col-xs-8 col-xs-offset-2' style='text-align:center;'>

        <?php 
          //$statusLib=new \App\Libraries\Core\StatusLibrary();
           $userLib=new \App\Libraries\Core\UserLibrary();

          //if (($statusLib->initialItemStatus($controller) == $status_id || $is_declined_state) && $userLib->checkRoleHasPermissions('budget_item', 'create')) { 
            if (($initial_status == $status_id || $is_declined_state) && $userLib->checkRoleHasPermissions('budget_item', 'create')) { 
            ?>
            <a href="<?= base_url(); ?>budget_item/multiFormAdd/<?= $id; ?>/budget">
                <div class='btn btn-default'><?= get_phrase('add_new_budget_item'); ?></div>
            </a>
        <?php } ?>

        <?php
        $logged_role_id = $session->role_ids;

        echo approval_action_button($controller,$item_status, hash_id($id, 'decode'), $budget_status_id, $item_initial_item_status_id, $item_max_approval_status_ids, $action_button_disabled);

        if($user_has_create_budget_limit_permission){
        ?>

            <a href = "<?=base_url();?>budget_limit/singleFormAdd/<?=$id;?>/budget" class = "btn btn-default"><?=get_phrase('add_budget_limit');?></a>
        <?php 
        }
        ?>
        </div>

</div>

<div class='row warning <?=$budget_message == '' ? 'hidden': 'show'?>'>
    <div class='col-xs-12'>
        <?=$budget_message;?>
    </div>
</div>

<div class='row hidden'>
    <div class='col-xs-6'>
        <div class='form-group'>
            <label class='control-label col-xs-4'><?= get_phrase('scanned_budget_upload'); ?></label>
            <div class='col-xs-4'>
                <input type='file' name='file' multiple />
            </div>

        </div>
    </div>
</div>

<?php 
    if(isset($session->system_settings['use_pca_objectives']) && $this->session->system_settings['use_pca_objectives']){
?>
<div class='row'>
    <div class='col-xs-12'>
        <?php 
            echo $strategic_objectives_costing_view;
        ?>
    </div>
</div>

<?php 
    }
?>

<div class='row'>
    <div class='col-xs-12'>
        <?php 

        //    $data['data'] = $budget_limit;
        //    $data['userLib']=$user_has_update_budget_limit_permission;

            echo  view('budget_limit/budget_limit_list', $data);
        ?>
    </div>
</div>


<div class='row'>
    <div class='col-xs-12'>

        <?php
        if(empty($summary)){
        ?>
            <div style="font-weight: bold;" class = "info"><?=get_phrase('missing_budget_items','No Budget Items Posted to this Budget');?></div>
        <?php
        }
        //print_r($budget_details);
        foreach ($summary as $income_ac) {
            $expense_spread = $income_ac['spread_expense_account'];

            //print_r($income_ac);

            extract($income_ac);
            // print_r($months);


        ?>

            <table class="table table-bordered datatable">
                <thead>
                    <tr>
                        <th colspan='14' style='text-align:center'>
                            <?= get_phrase('year'); ?> <?= $current_year; ?> <?= $budget_tag; ?> : <?= $office ?> <?= $income_account['income_account_name'] . ' (' . $income_account['income_account_code'] . ')'; ?> <?= get_phrase('budget_summary'); ?> (<a href='<?= base_url(); ?>budget/view/<?=$id; ?>/schedule/<?= hash_id($income_account['income_account_id'], 'encode'); ?>'><?= get_phrase('show_budget_schedule'); ?></a>) &nbsp;
    </div>
    </th>
    </tr>
    <tr>
        <th><?= get_phrase('account'); ?></th>
        <th><?= get_phrase('total_cost'); ?></th>

        
        <?php  foreach ($months as $month) { ?>
            <th><?= $month; ?></th>
        <?php } ?>
    </tr>
    </thead>

    <tbody>
        <?php
            //$_months = ['July','August','September','November','December','January','February','March','April','May','June'];
            //print_r($expense_spread);
            foreach ($expense_spread as $expense_spreading) {
                extract($expense_spreading);
                // print_r($months);
        ?>
            <tr>
                <td><?= $expense_account['account_code'] . ' - ' . $expense_account['account_name']; ?></td>
                <td><?= number_format(array_sum($spread), 2); ?></td>

                <?php
                foreach ($months as $month) {
                    $amount = 0;
                    foreach ($spread as $month_name => $month_amount) {
                        if ($month == $month_name) {
                            $amount = $month_amount;
                        }
                    }
                ?>
                    <td><?= number_format($amount, 2); ?></td>
                <?php
                }
                ?>
            </tr>
        <?php } ?>
    </tbody>
    <tfoot>
        <tr>
            <td><?= $income_account['income_account_name']; ?> <?= get_phrase('total'); ?></td>

            <?php
            $spreads = array_column($expense_spread, 'spread');
            // echo json_encode($spreads);
            $months_names = array_keys($spreads[0]);
            // print_r($months);
            $total = 0;

            foreach ($spreads as $spread) {
                $total += array_sum($spread);
                $spreading_and_grand_totals['grand_total'] = $total;
            }

            foreach ($months as $month) {
                $spreading_and_grand_totals['spreading_totals'][$month] = array_sum(array_column($spreads, $month));
            }
            ?>

            <td><?= number_format($spreading_and_grand_totals['grand_total'], 2); ?></td>

            <?php
            foreach ($months as $month) {
                $cell_amount = $spreading_and_grand_totals['spreading_totals'][$month];
            ?>
                <td><?= number_format($cell_amount, 2); ?></td>
            <?php
            }
            ?>


        </tr>
        <tr>
            <?php
            $limit = isset($budget_limits[$income_account['income_account_id']]) ? $budget_limits[$income_account['income_account_id']] : 0;
            ?>
            <td><?= get_phrase('budget_limit'); ?></td>
            <td colspan="13"><?= number_format($limit, 2); ?></td>
        </tr>
        <tr>
            <td><?= get_phrase('budget_limit_remaining_amount'); ?></td>
            <td colspan="13"><?= number_format(($limit - $total), 2); ?></td>
        </tr>
    </tfoot>
    </table>

<?php } ?>

</div>
</div>

<script>
    $('#action_btn').on('click', function() {

        alert('Button function still under construction');
        return false;

        // var budget_id = $(this).data('budget_id');
        // var next_status = $(this).data('next_status');
        // var data = {'budget_id':budget_id,'next_status':next_status};
        // var url = "<?= base_url(); ?>Budget/update_budget_status";
        // var btn = $(this);

        // $.post(url,data,function(response){
        //     action_button = JSON.parse(response);
        //     btn.html(action_button.button_label);
        //     btn.addClass('disabled');
        // });
    });

    $(document).ready(function() {
        $('.datatable').DataTable();
    });

   
</script>