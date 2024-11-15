<?php 
use \App\Libraries\System\Widgets\WidgetBase;
?>
<style>
    .span_label {
        font-weight: bold;
    }
</style>

<?php
extract($result);
$logged_role_id = $session->role_ids;
$table = 'voucher';
$primary_key = hash_id($id, 'decode');
?>

<div class="row">
    <div class="col-xs-12">
        <?= WidgetBase::load('comment'); ?>
    </div>
</div>


<div class='row'>
    <div class="col-xs-12">
        <div class="panel panel-default" data-collapsed="0">
            <div class="panel-heading">
                <div class="panel-title">
                    <i class="entypo-plus-circled"></i>
                    <?php echo get_phrase('transaction_voucher'); ?>
                </div>
            </div>

            <div class="panel-body" style="max-width:50; overflow: auto;padding-left: 60px;padding-right: 30px;">

                <div class="row form_rows">
                    <div class='col-xs-12'>
                        <div onclick="PrintElem('#voucher_print')" class="btn btn-default <?=!$voucher_status_is_max ? 'hidden': '';?>"><?= get_phrase('print'); ?></div>
                        <?php
                        extract($status_data);
                        echo approval_action_button($controller, $item_status, $primary_key, $header['voucher_status_id'], $item_initial_item_status_id, $item_max_approval_status_ids);
                        $voucher_id = $header['voucher_id'];

                        if ($is_voucher_cancellable) {
                            $is_expense_greater = $check_expenses_aganist_income ? 'yes' : 'no';
                        ?>

                            <a class='btn btn-primary edit' id="btn_edit" href='<?= base_url() . $controller; ?>/edit/<?= hash_id($voucher_id); ?>'><?= get_phrase('Edit'); ?></a>

                            <button class='btn btn-default <?= $is_expense_greater; ?>' id="btn_cancel"><?= get_phrase('cancel_voucher'); ?></button>



                        <?php } else { ?>

                            <a class='btn btn-primary edit disabled' id="btn_edit" href='<?= base_url() . $controller; ?>/edit/<?= hash_id($voucher_id); ?>'><?= get_phrase('Edit'); ?></a>
                        <?php } ?>
                    </div>
                </div>

                <hr />
                <div id="voucher_print">
                    <?php 
                        include "common_view.php";
                    ?>
                </div>
                <div>
                    <i class = 'fa fa-expand' id = 'show_approval_history' style = 'cursor:pointer;'> <?=get_phrase('click_toggle_approval_history')?></i>
                    <div class = 'hidden' id = 'approval_history'>
                        <table class = 'table table-striped'>
                            <thead>
                                <tr>
                                    <th><?=get_phrase('actor');?></th>
                                    <th><?=get_phrase('role');?></th>
                                    <th><?=get_phrase('action');?></th>
                                    <th><?=get_phrase('date');?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($ordered_voucher_approvers as $approver):?>
                                <tr>
                                    <td><?=$approver->fullname;?></td>
                                    <td><?=$approver->user_role_name;?></td>
                                    <td><?=$approver->status_name;?></td>
                                    <td><?=$approver->approval_date;?></td>
                                </tr>
                                <?php endforeach;?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>


        <script type="text/javascript">
            $("#btn_cancel").on('click', function() {


                let btn = $(this);

                var voucher_id = <?= hash_id($id, 'decode'); ?>;

                let inform_message = "<?php echo get_phrase('cancel_when_expenses_exceed_income', "You have pending expenses and you can't cancel this income voucher; cancel related expenses first"); ?>";

                if (btn.hasClass('yes')) {
                    //alert(voucher_id);
                    alert(inform_message);

                    return false;
                }

                //return false;

                var cnfrm = confirm('Are you sure you want to cancel this voucher?');

                if (cnfrm) {
                    // Zero is a flag meanign that the request is for a cancelled voucher, otherwise 1 would mean the request is for a re-use voucher
                    var url = "<?= base_url(); ?>Journal/reverse_voucher/" + voucher_id + "/0"; 

                    $.get(url, function(response) {

                        const obj = JSON.parse(response);

                        alert(obj.message);

                        btn.remove();


                        location.replace(location.href);
                    });

                } else {
                    alert('Cancelling process aborted');
                }

            });


            function PrintElem(elem) {
                $(elem).printThis({
                    debug: false,
                    importCSS: true,
                    importStyle: true,
                    printContainer: false,
                    loadCSS: "",
                    pageTitle: "<?php echo get_phrase('payment_voucher'); ?>",
                    removeInline: false,
                    printDelay: 333,
                    header: null,
                    formValues: true
                });
            }

            $('#show_approval_history').on('click', function () {
                if($('#approval_history').hasClass('hidden')){
                    $('#approval_history').removeClass('hidden') 
                }else{
                    $('#approval_history').addClass('hidden')
                }
            })
        </script>