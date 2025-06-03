<?php 
use \App\Libraries\System\Widgets\WidgetBase;
?>

<style>
    .control-label {
        text-align: left;
    }

    .center {
        text-align: center;
    }
</style>


<?php
extract($result);
?>


<div class="row">
    <div class="col-xs-12">
        <?= WidgetBase::load('position', 'position_1'); ?>
    </div>
</div>

<div class='row' id="main_row">
    <div class='col-xs-12 split_screen'>
        <div class="panel panel-default" data-collapsed="0">
            <div class="panel-heading">
                <div class="panel-title">
                    <i class="entypo-plus-circled"></i>
                    <?php echo get_phrase('transaction_voucher'); ?>
                </div>
            </div>

            <div class="panel-body" style="max-width:50; overflow: auto;">
                <?php echo form_open("", array('id' => 'frm_voucher', 'class' => 'form-horizontal form-groups-bordered validate', 'enctype' => 'multipart/form-data')); ?>

                <div class='form-group'>
                    <div class='col-xs-12 center'>
                        <div class='btn btn-default btn-reset'><?= get_phrase('reset'); ?></div>
                        <div class='btn btn-default btn-insert'><?= get_phrase('insert_voucher_detail_row'); ?></div>
                        <div class='btn btn-default btn-save'><?= get_phrase('save'); ?></div>
                        <div class='btn btn-default btn-save-new'><?= get_phrase('save_and_new'); ?></div>
                        <div class='btn btn-default btn-retrieve-request <?= $office_has_request ? null : 'hidden'; ?>'><?= get_phrase('show_or_hide_requests'); ?> &nbsp; <span class='badge badge-secondary requests_badge'>0</span></div>
                    </div>
                </div>

                <div class='form-group'>
                    <label class='control-label col-xs-1'><?= get_phrase('office'); ?></label>
                    <div class='col-xs-2'>
                        <select class='form-control required' id='office' name='fk_office_id'>
                            <option value=""><?= get_phrase('select_office'); ?></option>
                            <?php
                            foreach ($session->hierarchy_offices as $office) {
                                if (!$office['office_is_active']) continue;
                            ?>
                                <option value="<?= $office['office_id']; ?>"><?= $office['office_name']; ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <label class='control-label col-xs-1 date-field'><?= get_phrase('transaction_date'); ?></label>
                    <div class='col-xs-2 date-field'>
                        <input id="transaction_date" type='text' name='voucher_date' onkeydown="return false" class='form-control required' autocomplete="off" />
                    </div>

                    <label class='control-label col-xs-1'><?= get_phrase('voucher_number'); ?></label>
                    <div class='col-xs-2'>
                        <input type='text' onkeydown="return false" class='form-control required' name='voucher_number' id="voucher_number" autocomplete="off"/>
                    </div>

                    <label class='control-label col-xs-1'><?= get_phrase('voucher_type'); ?></label>
                    <div class='col-xs-2'>
                        <select class='form-control required' disabled="disabled" name='fk_voucher_type_id' id='voucher_type'>
                            <option value=""><?= get_phrase('select_voucher_type'); ?></option>

                        </select>
                    </div>

                </div>

                <div class='form-group'>

                    <span class='hidden'>
                        <label class='control-label col-xs-1'><?= get_phrase('bank_account'); ?></label>
                        <div class='col-xs-2'>
                            <select onchange="get_bank_cash_information(this);" class="form-control required account_fields" disabled="disabled" name='fk_office_bank_id' id='bank'>
                                <option value=""><?= get_phrase('select_bank_account'); ?></option>
                            </select>
                        </div>
                    </span>

                    <span class='hidden'>
                        <label class='control-label col-xs-1'><?= get_phrase('cheque_number'); ?></label>
                        <div class='col-xs-2'>
                            <!-- <input type='text' name='voucher_cheque_number' id='cheque_number' disabled='disabled' class='form-control required account_fields' /> -->
                            <select class='form-control required account_fields' name='voucher_cheque_number' id='cheque_number' disabled='disabled'>
                                <option value=''><?= get_phrase('select_cheque_number'); ?></option>
                            </select>
                        </div>
                    </span>

                    <span class='hidden'>
                        <label class='control-label col-xs-1'><?= get_phrase('cash_account'); ?></label>
                        <div class='col-xs-2'>
                            <select class="form-control required account_fields" disabled="disabled" name='fk_office_cash_id' id='cash_account'>
                                <option value=""><?= get_phrase('select_cash_account'); ?></option>
                            </select>
                        </div>
                    </span>

                    <span class='hidden'>
                        <label class='control-label col-xs-1'><?= get_phrase('receiving_account'); ?></label>
                        <div class='col-xs-2'>
                            <select name='cash_recipient_account' disabled="disabled" id='cash_recipient_account' class='form-control required account_fields'>
                            </select>
                        </div>
                    </span>


                    <span class='hidden'>
                        <label class='control-label col-xs-1'><?= get_phrase('reference_from'); ?> </label>
                        <div class='col-xs-2'>
                            <select style="clear:right;float:left;max-width:90%;" type='text' name='bank_refund' id='bank_refund' disabled='disabled' class='form-control select2 required account_fields'>
                                <option value=""><?=get_phrase('select_voucher_number');?></option>
                            </select>
                            <!-- <i style="cursor: pointer;float:right" id="bank_refund_search" class = 'fa fa-search'></i> -->
                            <span id = "bank_refund_error" style = 'color:red;'></span>
                        </div>
                    </span>


                </div>

                <div class='form-group'>
                    <span class='hidden'>
                        <label class='control-label col-xs-1'><?= get_phrase('bank_balance'); ?></label>
                        <div class='col-xs-2'><input id='bank_balance' class='form-control' value='0' name='bank_balance' onkeydown="return false" /></div>
                    </span>
                    <!--  Total Unapproved and Approved vouchers-->
                    <span class='hidden'>
                        <label class='control-label col-xs-1'><?= get_phrase('total_cash_balance', 'Total Cash Bal.'); ?></label>
                        <div class='col-xs-2'><input id='unapproved_and_approved_vouchers_cash_balance' name='unapproved_and_approved_vouchers' class='form-control' value='0' onkeydown="return false" /></div>
                    </span>

                    <span class='hidden'>
                        <label class='control-label col-xs-1'><?= get_phrase('reference_voucher_amount'); ?></label>
                        <div class='col-xs-2'><input onkeydown="return false" id='refund_voucher_amount' name='refund_voucher_amount' class='form-control' value='0' onkeydown="return false" /></div>
                    </span>

                </div>

                <div class='form-group'>
                    <label class='col-xs-1'><?= get_phrase('payee/_vendor'); ?></label>
                    <div class='col-xs-11'>
                        <input type='text' id='voucher_vendor' name='voucher_vendor' class='form-control required' autocomplete="off" />
                    </div>
                </div>

                <div class='form-group'>
                    <label class='col-xs-1'><?= get_phrase('address'); ?></label>
                    <div class='col-xs-11'>
                        <input type='text' id='voucher_vendor_address' name='voucher_vendor_address' class='form-control required' autocomplete="off" />
                    </div>
                </div>

                <div class='form-group'>
                    <label class='col-xs-1'><?= get_phrase('description'); ?></label>
                    <div class='col-xs-11'>
                        <input type='text' id='voucher_description' name='voucher_description' class='form-control required' autocomplete="off" />
                    </div>
                </div>

                <div class='form-group'>
                    <div class='col-xs-12 center'>
                        <div class='btn btn-default btn-reset'><?= get_phrase('reset'); ?></div>
                        <div class='btn btn-default btn-insert'><?= get_phrase('insert_voucher_detail_row'); ?></div>
                        <div class='btn btn-default btn-save'><?= get_phrase('save'); ?></div>
                        <div class='btn btn-default btn-save-new'><?= get_phrase('save_and_new'); ?></div>
                        <div class='btn btn-default btn-retrieve-request <?= $office_has_request ? null : 'hidden'; ?>'><?= get_phrase('show_or_hide_requests'); ?> &nbsp; <span class='badge badge-secondary requests_badge'>0</span></div>
                    </div>
                </div>

                <div class='form-group'>
                    <div class='col-xs-12'>
                        <table class='table table-striped' id='tbl_voucher_body'>
                            <thead>
                                <tr>
                                    <th><?= get_phrase('action'); ?></th>
                                    <th><?= get_phrase('quantity'); ?></th>
                                    <th><?= get_phrase('description'); ?></th>
                                    <th><?= get_phrase('unit_cost'); ?></th>
                                    <th><?= get_phrase('total_cost'); ?></th>

                                    <?php
                                    $toggle_accounts_by_allocation = service("settings")->get("GrantsConfig.toggle_accounts_by_allocation");

                                    if ($toggle_accounts_by_allocation) {
                                    ?>
                                        <th><?= get_phrase('allocation_code'); ?></th>
                                        <th><?= get_phrase('account'); ?></th>
                                    <?php } else { ?>
                                        <th><?= get_phrase('account'); ?></th>
                                        <th><?= get_phrase('allocation_code'); ?></th>
                                    <?php } ?>

                                    <th class="<?= $office_has_request ? null : 'hidden'; ?>"><?= get_phrase('request_number'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="tbl_tbody">
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan='6'><?= get_phrase('total'); ?></td>
                                    <td><input type='text' id='voucher_total' class='form-control' readonly /></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class='form-group'>
                    <div class='col-xs-12 center'>
                        <div class='btn btn-default btn-reset'><?= get_phrase('reset'); ?></div>
                        <div class='btn btn-default btn-insert'><?= get_phrase('insert_voucher_detail_row'); ?></div>
                        <div class='btn btn-default btn-save'><?= get_phrase('save'); ?></div>
                        <div class='btn btn-default btn-save-new'><?= get_phrase('save_and_new'); ?></div>
                        <div class='btn btn-default btn-retrieve-request <?= $office_has_request ? null : 'hidden'; ?>'><?= get_phrase('show_or_hide_requests'); ?> &nbsp; <span class='badge badge-secondary requests_badge'>0</span></div>
                    </div>
                </div>

                </form>
            </div>
        </div>

        <div class="row">
            <div class="col-xs-12">
                <?php //echo Widget_base::load('upload');
                ?>
            </div>
        </div>


    </div>
</div>



<input type="text" class="form-control hidden" name="secondary_input" id="secondary_input" value="" />
<select class='form-control hidden' name='secondary_select' id='secondary_select' disabled='disabled'>
    <option value=''><?= get_phrase('select_cheque_number'); ?></option>
</select>


<script>
    var toggle_accounts_by_allocation = '<?= service("settings")->get("GrantsConfig.toggle_accounts_by_allocation"); ?>';

    $(document).ready(function() {
        //get_approved_unvouched_request_details
        $('.date-field').hide();
        $('.btn-insert').hide();
        $('.btn-save').hide();
        $('.btn-save-new').hide();
        $('.btn-retrieve-request').hide();


    });

    function load_approved_requests() {
        var office = $("#office").val();
        var url = "<?= base_url(); ?>Voucher/get_approve_request_details/" + office;

        $("#request_screen").html("");

        $.ajax({
            url: url,
            beforeSend: function() {

            },
            success: function(response) {

                if ($("#main_row").find('#request_screen').length == 0) {
                    $("#main_row").append("<div id='request_screen' class='col-xs-6'>" + response + "</div>");
                } else {
                    $("#request_screen").html(response);
                }

                $("#request_screen").css('overflow-x', 'auto');

                update_request_details_count_on_badge();
            },
            error: function() {
                alert("Error occurred!");
            }
        });
    }

    $(document).on('change', '#voucher_type', function() {

        remove_voucher_detail_rows();

        var office = $('#office').val();
        var voucher_type_id = $(this).val();
        var active_request_url = "<?= base_url(); ?>ajax/voucher/getCountOfUnvouchedRequest/" + office;
        var url = "<?= base_url(); ?>ajax/voucher/checkVoucherTypeAffectsBank/" + office + "/" + voucher_type_id;

        if (!voucher_type_id) {
            hide_buttons();
            $(".account_fields").closest('span').addClass('hidden');
            return false;
        }

        $("#bank_balance, #unapproved_and_approved_vouchers_cash_balance").closest('span').addClass('hidden');
        $("#bank_balance, #unapproved_and_approved_vouchers_cash_balance").val(0);

        $("#bank_balance, #approved_vouchers_cash_balance").closest('span').addClass('hidden');
        $("#bank_balance, #approved_vouchers_cash_balance").val(0);

        $("#refund_voucher_amount").closest('span').addClass('hidden');
        $("#refund_voucher_amount").val(0);

        $("#cash_account").val(0);

        checkIfDateIsSelected() ? getRequest(url, function(response_objects) {

            // var response_objects = JSON.parse(response);
            let response_office_cash = response_objects['office_cash'];
            let response_office_bank = response_objects['office_banks'];
            let response_is_transfer_contra = response_objects['is_transfer_contra'];
            let response_is_bank_payment = response_objects['is_bank_payment'];
            let response_is_bank_refund = response_objects['is_bank_refund'];
            let response_is_voucher_type_requires_cheque_referencing = response_objects['voucher_type_requires_cheque_referencing'];


            $.get(active_request_url, office, function(response) {
                let badges = $(".requests_badge");
                badges.html(response);

            })


            if (response_office_cash.length > 0) {

                add_options_to_cash_select(response_office_cash);

                if (response_office_bank.length > 0) {
                    add_options_to_bank_select(response_office_bank, true);
                }

            } else if (response_office_bank.length > 0) {

                add_options_to_bank_select(response_office_bank);

                if (response_office_cash.length > 0) {
                    add_options_to_cash_select(response_office_cash, true);
                }

            }

            if (response_is_transfer_contra) {
                $("#cash_recipient_account").closest('span').removeClass('hidden');
                $("#cash_recipient_account").removeAttr('disabled');
                $("#cash_recipient_account").html('');
            } else {
                $("#cash_recipient_account").closest('span').addClass('hidden');
            }

            if (response_is_bank_payment) {
                $("#cheque_number").closest('span').removeClass('hidden');

                change_voucher_number_field_to_eft_number(response_is_voucher_type_requires_cheque_referencing);

            } else {
                $("#cheque_number").closest('span').addClass('hidden');
                //$("#cheque_number").prop("selectedIndex", 0);
                $("#cheque_number option:first").attr('selected', 'selected');
            }

            if(response_is_bank_refund){
                $("#bank_refund, #refund_voucher_amount, #bank_balance").closest('span').removeClass('hidden');
                // Populate refund voucher list
                populateVoucherInRefundList(response_objects['valid_refund_vouchers']);
                $("#refund_voucher_amount").val(0)
            }else{
                $("#bank_refund").closest('span').addClass('hidden');
                $("#bank_refund option:first").attr('selected', 'selected');
            }

            if (voucher_type_id && !response_is_bank_refund) {
                //update_request_details_count_on_badge();
                $(".btn-insert").show();
                $(".btn-retrieve-request").show();
            } else {
                hide_buttons();
            }

        }) : alert("Choose a valid date");

    });


    function populateVoucherInRefundList(voucherList){
        // console.log(voucherList);
        const bankRefundElem = $("#bank_refund")
        bankRefundElem.children().remove();
        voucherList.forEach((voucherNumber) => {
            bankRefundElem.append('<option value = "' + voucherNumber + '">' + voucherNumber + '</option>')
        })
    }


    $(".btn-retrieve-request").on('click', function() {

        if ($(".split_screen").hasClass('col-xs-12')) {
            $(".split_screen").removeClass('col-xs-12').addClass('col-xs-6');

            var split_screen = $(".split_screen");

            load_approved_requests();

            remove_request_derived_voucher_details();

        } else {
            $("#request_screen").remove();
            $(".split_screen").removeClass('col-xs-6').addClass('col-xs-12');

            var tbl_body_rows = $("#tbl_voucher_body tbody tr");

            if (tbl_body_rows.length > 0) {
                $('.btn-save').show();
                $('.btn-save-new').show();
            }


        }

        $('.btn-save, .btn-save-new').show();
    });

    function remove_request_derived_voucher_details() {
        var tbl_voucher_body_rows = $("#tbl_voucher_body tbody tr");

        $.each(tbl_voucher_body_rows, function(i, el) {

            let row_request_id_input = $(el).find("td:last").find('input');

            if (parseInt(row_request_id_input.val()) > 0) {
                row_request_id_input.closest("tr").remove();

                //Adjust the voucher_total value
                let row_voucher_total = $(el).find('.totalcost').val();
                let voucher_total = $("#voucher_total").val();

                $update_voucher_total = parseFloat(voucher_total) - parseFloat(row_voucher_total);

                $("#voucher_total").val($update_voucher_total);
            }
        });
    }

    function hide_buttons() {
        $('.btn-insert').hide();
        $('.btn-save').hide();
        $('.btn-save-new').hide();
        $('.btn-retrieve-request').hide();
    }



    function populate_cash_transfer_recipient(elem) {
        var selected_option_index = elem.find(':selected').index();

        var elem_clone = elem.clone();
        elem_clone.find('option').eq(selected_option_index).remove();
        options = elem_clone.children();

        $("#cash_recipient_account").html(options);
        $("#cash_recipient_account").prop("selectedIndex", 0);

    }

    // $('#cash_account').on('change', function() {

    //     let total_unapproved_expense_vouchers = unapproved_current_month_vouchers('expense', 'cash');

    //     let total_unapproved_vouchers = unapproved_current_month_vouchers('income', 'bank');

    //     console.log(total_unapproved_expense_vouchers);

    //     console.log(total_unapproved_vouchers);


    // });

    // function unapproved_current_month_vouchers(effect_code, account_code) {

    //     let cash_account=$('#cash_account');

    //     let office_bank=$('#bank');

    //     let unapproved_expense = null;

    //     let office_id = $('#office').val();

    //     let transction_date = $('#transaction_date').val();

    //     let url;

    //     //Check if cash_office_id exists
    //     if($('#cash_account').length){

    //         var office_cash_id=cash_account.val();

    //         url = '<?= base_url() ?>voucher/unapproved_month_vouchers/' + office_id + '/' + transction_date + '/' + effect_code + '/' + account_code + '/' + office_cash_id+'/'+0;
    //     }

    //     //CHeck if bank_office_id exists
    //     if($('#bank').length){

    //         var office_bank_id=office_bank.val();

    //         url = '<?= base_url() ?>voucher/unapproved_month_vouchers/' + office_id + '/' + transction_date + '/' + effect_code + '/' + account_code + '/0' +'/'+ office_bank_id;

    //     }

    //     $.ajax({
    //         url: url,
    //         type: 'get',
    //         dataType: 'html',
    //         async: false,
    //         success: function(data) {
    //             unapproved_expense = data;
    //         }
    //     });
    //     return unapproved_expense;

    // }


    function unapproved_current_month_vouchers(effect_code, account_code) {

        let cash_account = $('#cash_account');

        let office_bank = $('#bank');

        let unapproved_expense = null;

        let office_id = $('#office').val();

        let transction_date = $('#transaction_date').val();

        let url;

        //Check if cash_office_id exists
        if ($('#cash_account').length) {

            var office_cash_id = $('#cash_account').val();

            url = '<?= base_url() ?>voucher/unapproved_month_vouchers/' + office_id + '/' + transction_date + '/' + effect_code + '/' + account_code + '/' + office_cash_id + '/' + 0;
        }

        //CHeck if bank_office_id exists
        if ($('#bank').length) {

            var office_bank_id = $('#bank').val();

            url = '<?= base_url() ?>voucher/unapproved_month_vouchers/' + office_id + '/' + transction_date + '/' + effect_code + '/' + account_code + '/' + 0 + '/' + office_bank_id;


        }

        $.ajax({
            url: url,
            type: 'get',
            dataType: 'html',
            async: false,
            success: function(data) {
                unapproved_expense = data;
            }
        });
        return unapproved_expense;

    }

    $("#cash_account").on('change', function() {
        populate_cash_transfer_recipient($(this));

        office_id = $('#office').val();
        office_cash_id = $(this).val();

        if ($(this).val() != '') {
            $('#unapproved_and_approved_vouchers_cash_balance').closest('span').removeClass('hidden');
            $('#approved_vouchers_cash_balance').closest('span').removeClass('hidden');
            //compute_cash_balance(office_id, office_cash_id);
            compute_cash_balance(office_id, office_cash_id);

        } else {
            $('#unapproved_and_approved_vouchers_cash_balance').closest('span').addClass('hidden');
            $('#approved_vouchers_cash_balance').closest('span').closest('span').addClass('hidden');
        }
    });

    // function prevent_bank_change_when_voucher_has_detail_row(){
    //     var tbl_body_rows = $("#tbl_voucher_body tbody tr");
    //     var count_body_rows = tbl_body_rows.length;

    //     if(count_body_rows > 0){
    //         alert("You can't change a bank account when voucher has detail rows");
    //         return false;
    //     }else{
    //         return true;
    //     }

    // }

    $("#bank").on("change", function(ev) {

        //alert('Yes Here');
        const office = $('#office').val();
        const bank = $(this).val();


        // Toogle Disable when a bank account is selected or not
        if ($(this).val() != '') {
            // alert('Hellooo');
            $("#cheque_number").removeAttr('disabled');
            $("#cheque_number").removeAttr('readonly');

            $("#bank_refund").removeAttr('disabled');
            $("#bank_refund").removeAttr('readonly');

            $("#bank_balance").closest('span').removeClass('hidden');

            compute_bank_balance(office, bank);

        } else {
            $("#cheque_number").val("");
            $("#cheque_number").prop('disabled', 'disabled');
            $("#bank_balance").closest('span').addClass('hidden');
        }


        // Populate transfer account list
        if (!$("#cash_recipient_account").closest('span').hasClass('hidden')) {
            populate_cash_transfer_recipient($(this));
        }

        if ($("#cheque_number").is('input') && $("#cheque_number").val() != "") {
            checkIfEftREfIsValid(office, bank, $("#cheque_number").val());
        } else {
            check_cheque_validity();
        }

    });

    function compute_bank_balance(office_id, office_bank_id) {
        const url = '<?= base_url(); ?>ajax/voucher/computeBankBalance';
        const transaction_date = $('#transaction_date').val();
        const data = {
            office_id,
            office_bank_id,
            transaction_date
        };


        $.post(url, data, function(bank_balance_amount) {

            // const bank_balance_amount = JSON.parse(bank_balance_response);

            //console.log(bank_balance_amount);

            bank_amount = bank_balance_amount.approved_and_unapproved_vouchers_bank_bal;

            let put_commas_on_bank_amount = bank_amount.toString().split(".");

            put_commas_on_bank_amount[0] = put_commas_on_bank_amount[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");

            $('#bank_balance').val(put_commas_on_bank_amount.join('.'));

            //console.log(JSON.parse(response));
        })
    }

    function compute_cash_balance(office_id, office_cash_id) {
        const url = '<?= base_url(); ?>ajax/voucher/computeCashBalance';
        const transaction_date = $('#transaction_date').val();
        const data = {
            office_id,
            office_cash_id,
            transaction_date
        };

        $.post(url, data, function(cash_balance) {
            //$('#cash_balance').val(cash_balance);
            // const cash_balance = JSON.parse(response);

            cash_amount = cash_balance.approved_and_unapproved_vouchers_cash_bal;

            let put_commas_on_cash_amount = cash_amount.toString().split(".");

            put_commas_on_cash_amount[0] = put_commas_on_cash_amount[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");

            $('#unapproved_and_approved_vouchers_cash_balance').val(put_commas_on_cash_amount.join('.'));
            $('#approved_vouchers_cash_balance').val(cash_balance.approved_vouchers_journal_bal);
            // console.log(JSON.parse(response));
        })
    }

    function check_cheque_validity() {

        // alert('Yes Here');
        var office = $("#office").val();
        var bank = $("#bank").val();
        var cheque_number = $("#cheque_number").val();

        var url = "<?= base_url(); ?>ajax/voucher/checkChequeValidity";
        var data = {
            'office_id': office,
            'bank_id': bank,
            'cheque_number': cheque_number
        };


        if ($("#bank").val() == "") {
            alert("Choose a valid bank account");
            $('#cheque_number').val('');
            return false;
        }

        $.post(url, data, function(obj) {
            var options = '<option value=""><?= get_phrase('select_cheque_number'); ?></option>';
            if (obj.leaves == 0) {
                alert('The bank account selected lacks a cheque book');
            } else {
                // var obj = JSON.parse(response);
                // console.log(obj);
                $.each(obj.leaves, function(i, elem) {
                    options += "<option value='" + elem.cheque_id + "'>" + elem.cheque_number + "</option>";
                });
            }

            $("#cheque_number").html(options);
        });
    }

    function checkIfEftREfIsValid(office, bank, cheque_number) {

        var url = "<?= base_url(); ?>Voucher/check_eft_validity";
        var data = {
            'office_id': office,
            'bank_id': bank,
            'cheque_number': cheque_number
        };

        if ($("#bank").val() == "") {
            alert("Choose a valid bank account");
            $('#cheque_number').val('');
            return false;
        }

        $.post(url, data, function(response) {
            if (!response) {
                alert("The reference number given (" + cheque_number + ") is not valid");
                $("#cheque_number").val("");
            }
        });
    }

    $(document).on('change', "#cheque_number", function() {
        var office = $("#office").val();
        var bank = $("#bank").val();
        var cheque_number = $("#cheque_number").val();

        //console.log($(this));

        if ($("#cheque_number").is('input')) {
            checkIfEftREfIsValid(office, bank, cheque_number);
        }

    });

    function computeNextVoucherNumber(office_id) {

        var url = "<?= base_url(); ?>ajax/voucher/computeNextVoucherNumber/";

        $.ajax({
            url: url,
            data: {
                'office_id': office_id
            },
            type: "POST",
            beforeSend: function() {

            },
            success: function(response) {
                $("#voucher_number").val(response.voucherNumber);
            },
            error: function() {
                alert('Error occurred');
            }
        });
    }

    function computeCurrentTransactingDate(office_id) {

        var url = "<?= base_url(); ?>ajax/voucher/getOfficeVoucherDate/";

        $.ajax({
            url: url,
            type: "POST",
            data: {
                'office_id': office_id
            },
            beforeSend: function() {

            },
            success: function(obj) {
                $('.date-field').show();
                //$("#transaction_date").val(obj.next_vouching_date);
                $("#transaction_date").val();

                $('#transaction_date').datepicker({
                    format: 'yyyy-mm-dd',
                    startDate: obj.next_vouching_date,
                    endDate: obj.last_vouching_month_date
                });


            },
            error: function() {
                alert('Error occurred');
            }
        });
    }

    function checkIfBudgetIsPresent(office_id){
     
        const url = '<?=base_url();?>ajax/budget/checkOfficePeriodBudgetExists/' + office_id
        let check = false
        
        getRequest(url, function (resp) {
            if(!resp.check){
                alert('<?=get_phrase('missing_period_budget','You have a missing or unapproved budget review for the period')?>');
            }
            check = resp.check;
        })
        return check;
    }

    $("#office").on('change', function() {
        let clear_office_selector = false;
        const office_id = $(this).val()
        const url = '<?=base_url();?>ajax/budget/checkOfficePeriodBudgetExists/' + office_id
        const elem = $(this)

        if(office_id == ""){
            resetVoucher(clear_office_selector);
            return false;
        }

        getRequest(url, function (resp) {
            if(!resp.check){
                alert('<?=get_phrase('missing_period_budget','You have a missing or unapproved budget review for the period')?>');
            }else{
                resetVoucher(clear_office_selector);

                if (elem.val() == "") {
                    return false;
                }

                if ($(".split_screen").hasClass('col-xs-6')) load_approved_requests();

                computeNextVoucherNumber(elem.val());

                computeCurrentTransactingDate(elem.val());

                // getActiveVoucherTypes();
            }
        })
    });

    $('#transaction_date').on('change', function() {
        getActiveVoucherTypes();

        if($('#bank_refund').length > 0){
            hide_buttons()
            $('#bank_refund').val('')
            $("#refund_voucher_amount").val(0);
            $('#bank_refund_error').html('')  
        }
    })

    function getActiveVoucherTypes() {

        var office_id = $("#office").val();
        let transaction_date = $('#transaction_date').val();

        // alert(transaction_date)

        var url = "<?= base_url(); ?>ajax/voucher/getActiveVoucherTypes/" + office_id + "/" + transaction_date;

        $.ajax({
            url: url,
            success: function(response_voucher_type) {

                $("#voucher_type").removeAttr('disabled');

                var voucher_type_option = "<option value=''><?= get_phrase('select_a_voucher_type'); ?></option>";

                // var response_voucher_type = JSON.parse(response);

                if (response_voucher_type.length > 0) {
                    $.each(response_voucher_type, function(i, el) {
                        voucher_type_option += "<option value='" + response_voucher_type[i].voucher_type_id + "'>" + response_voucher_type[i].voucher_type_name + "</option>";
                    });
                }

                $("#voucher_type").html(voucher_type_option);
            }
        });

    }

    function checkIfDateIsSelected() {

        var checkIfDateIsSelected = true;

        if ($("#transaction_date").val() == "") {
            //alert("Choose a valid transaction date");
            $("#voucher_type").val("");
            checkIfDateIsSelected = false
        };

        return checkIfDateIsSelected;
    }

    function populate_select(obj, parent_elem, default_html_text = 'Select an option') {

        parent_elem.children().remove();

        var option_html = "<option value=''>" + default_html_text + "</option>";

        $.each(obj, function(i, elem) {
            option_html += "<option value='" + elem.item_id + "'>" + elem.item_name + "</option>";
        });

        parent_elem.append(option_html);
    }
    //Modified by Onduso on 28/1/2021
    $("#cheque_number").on('change', function() {

        var office_id = $("#office").val().trim();
        var bank_office_id = $("#bank").val().trim();
        var cheque_number = $(this).val().trim();

        var url = "<?= base_url(); ?>Voucher/get_cheques_for_office/" + office_id + "/" + bank_office_id + "/" + cheque_number;

        // alert(url);

        getRequest(url, function(response) {
            if (response) {
                alert('The cheque number is already used. Choose another one');

                check_cheque_validity();

            }

        });

    });

    function add_options_to_bank_select(response_office_bank, show_cash_accounts = false) {
        if (!show_cash_accounts) $("#cash_account").closest('span').addClass('hidden');

        $("#bank").closest('span').removeClass('hidden');
        $("#bank").removeAttr('disabled');
        $("#cheque_number").val("");
        //$("#cash_account").val("");
        populate_select(response_office_bank, $("#bank"), 'Select a bank account');
    }

    function add_options_to_cash_select(response_office_cash, show_bank_accounts = false) {
        if (!show_bank_accounts) $("#bank").closest('span').addClass('hidden');

        $("#cash_account").closest('span').removeClass('hidden');
        $("#cash_account").removeAttr('disabled');
        $("#cheque_number").val("");
        populate_select(response_office_cash, $("#cash_account"), 'Select a cash account');
    }

    function get_bank_cash_information(OfficeBankSelect) {

        let office_id = $("#office").val();
        let voucher_type_id = $("#voucher_type").val(); //$(voucherTypeSelect).val(); // Can be expense, income, cash_contra or bank_contra
        let url = "<?= base_url(); ?>ajax/voucher/checkVoucherTypeAffectsBank/" + office_id + "/" + voucher_type_id;
        let office_bank_id = $(OfficeBankSelect).val(); //!$("#bank").attr('disabled') ? $("#bank").val() : 0;
        let transaction_date = $('#transaction_date').val();
        let extra_data = {
            'office_bank_id': office_bank_id
        };
        
        if(!office_bank_id){
            return false;
        }

        $.post(url, extra_data, function(response_objects) {
            // var response_objects = JSON.parse(response);
            var response_is_voucher_type_requires_cheque_referencing = response_objects['voucher_type_requires_cheque_referencing'];

            //Get the active cheque of an office;

            if (response_is_voucher_type_requires_cheque_referencing) {
                var url = "<?= base_url(); ?>ajax/voucher/checkActiveChequeBookForOfficeBankExist/" + office_id + "/" + office_bank_id + "/" + transaction_date;
                getRequest(url, function(response_obj) {
                    // alert(response);
                    // var response_obj = JSON.parse(response);
                    //Check if response =false and then redirect to the cheque form
                    if (!response_obj['is_active_cheque_book_existing']) {

                        alert('No active cheque book & you will be directed to add cheque book form');

                        var redirect_to_add_cheque_book_url = "<?= base_url(); ?>cheque_book/singleFormAdd";

                        window.location.replace(redirect_to_add_cheque_book_url);
                        //alert('Yes');
                    } else if (!response_obj['are_all_cheque_books_fully_approved']) {

                        alert('Your active cheque book is either unsubmitted, declined or reinstated and not approved. You will be redirect to the cheque book');

                        var redirect_to_add_cheque_book_url = "<?= base_url(); ?>cheque_book/view/" + response_obj['current_cheque_book_id']; //QnG6NpbmWr

                        window.location.replace(redirect_to_add_cheque_book_url);
                    }
                });
            }

            // if (response_office_cash.length > 0) {
            //     add_options_to_cash_select(response_office_cash);

            //     if (response_office_bank.length > 0) add_options_to_bank_select(response_office_bank, true);

            // } else if (response_office_bank.length > 0) {
            //     add_options_to_bank_select(response_office_bank);

            //     if (response_office_cash.length > 0) add_options_to_cash_select(response_office_cash, true);

            // }

            // if (response_is_transfer_contra) {
            //     $("#cash_recipient_account").closest('span').removeClass('hidden');
            //     $("#cash_recipient_account").removeAttr('disabled');
            //     $("#cash_recipient_account").html('');
            // } else {
            //     $("#cash_recipient_account").closest('span').addClass('hidden');
            // }

            // if (response_is_bank_payment) {
            //     $("#cheque_number").closest('span').removeClass('hidden');

            //     change_voucher_number_field_to_eft_number(response_is_voucher_type_requires_cheque_referencing);

            // } else {
            //     $("#cheque_number").closest('span').addClass('hidden');
            // }

            // if (voucher_type_id) {
            //     //update_request_details_count_on_badge();
            //     $(".btn-insert").show();
            //     $(".btn-retrieve-request").show();
            // } else {
            //     hide_buttons();
            // }

        });


    }

    function change_voucher_number_field_to_eft_number(response_is_voucher_type_requires_cheque_referencing) {

        var cheque_number_div = $("#cheque_number").parent();

        if (response_is_voucher_type_requires_cheque_referencing == 0) {

            cheque_number_div.html('');
            const secondary_input = $('#secondary_input').clone();
            cheque_number_div.append(secondary_input);

            $('#secondary_input').prop('id', 'cheque_number');
            $('#cheque_number').prop('name', 'voucher_cheque_number');
            $('#cheque_number').prop('readonly', 'readonly');
            $('#cheque_number').addClass('account_fields');
            $('#cheque_number').addClass('eft');
            $('#cheque_number').removeClass('hidden');
            $('#cheque_number').removeClass('required');

            $("#cheque_number").parent().prev().html('<?= get_phrase("EFT_serial"); ?>');

        } else {


            cheque_number_div.html('');
            const secondary_select = $('#secondary_select').clone();
            cheque_number_div.append(secondary_select);

            //cheque_number_div.append($('#secondary_select'));
            $('#secondary_select').prop('id', 'cheque_number');
            $('#cheque_number').prop('name', 'voucher_cheque_number');
            $('#cheque_number').prop('readonly', 'readonly');
            $('#cheque_number').addClass('account_fields');
            $('#cheque_number').removeClass('hidden');
            $('#cheque_number').removeClass('required');

            $("#cheque_number").parent().prev().html('<?= get_phrase("cheque_number"); ?>');
        }
    }

    function create_office_cash_dropdown(response_office_cash) {
        var account_select_option = "<option value=''><?= get_phrase('select_cash_account'); ?></option>";

        if (response_office_cash.length > 0) {
            $.each(response_office_cash, function(i, el) {
                account_select_option += "<option value='" + response_office_cash[i].office_cash_id + "'>" + response_office_cash[i].office_cash_name + "</option>";
            });
        }

        $("#cash_account").html(account_select_option);
    }

    // Returns if a value is an object
    function isObject(value) {
        return value && typeof value === 'object' && value.constructor === Object ? true : false;
    }

    function create_accounts_select_options(response_accounts) {
        var account_select_option = "<option value=''><?= get_phrase('select_an_aaccount'); ?></option>";

        if (isObject(response_accounts) && response_accounts.length > 0) {
            $.each(response_accounts, function(i, el) {
                account_select_option += "<option value='" + response_accounts[i].account_id + "'>" + response_accounts[i].account_name + "</option>";
            });
        }

        $(".account").html(account_select_option);

    }

    function create_allocation_select_options(response_allocation) {
        var tbl_body = $("#tbl_voucher_body tbody");
        var allocation_select_option = "<option value=''><?= get_phrase('select_an_allocation_code'); ?></option>";

        if (response_allocation.length > 0) {

            $(".allocation").removeAttr('disabled');

            $.each(response_allocation, function(i, el) {
                allocation_select_option += "<option value='" + response_allocation[i].project_allocation_id + "'>" + response_allocation[i].project_allocation_name + "</option>";
            });
        }

        tbl_body.find(':last-child').find(".allocation").html(allocation_select_option);

    }

    function remove_voucher_detail_rows(min_rows = 0) {
        var tbl_body_rows = $("#tbl_voucher_body tbody tr");

        // Remove extra rows
        var count_body_rows = tbl_body_rows.length;

        //$(".btn-save, .btn-save-new").remove();

        if (count_body_rows > min_rows) {
            $(".btn-save, .btn-save-new").hide();
            $.each(tbl_body_rows, function(i, el) {
                if ($("#tbl_voucher_body tbody tr").length > min_rows) {
                    $(el).remove();
                }
            });
        }
    }

    function reset_account_fields() {
        $('.account_fields').each(function(i, elem) {
            $(elem).val('');
            $(elem).prop('disabled', 'disabled');
            $(elem).closest('span').addClass('hidden');
        });
    }

    function reset_particulars_fields() {
        $("#voucher_vendor").val("");
        $("#voucher_vendor_address").val("");
        $("#voucher_description").val("");
        $("#voucher_total").val("");
        $("#voucher_number").val("")
        $("#voucher_type").val("");
        $("#voucher_type").prop('disabled', 'disabled');
        $("#unapproved_and_approved_vouchers_cash_balance").closest('span').addClass('hidden')
        $("#bank_balance").closest('span').addClass('hidden')
        $("#transaction_date").val("")
        $('.date-field').hide();
    }

    function reset_voucher_identity_fields(clear_office_selector) {
        //console.log($("#office").val());
        if (clear_office_selector) {
            $("#transaction_date").val("");
            $("#voucher_number").val('');
            $("#office").val('');
            $("#voucher_type").val("");
            $("#voucher_type").prop('disabled', 'disabled');
        }

    }

    function resetVoucher(clear_office_selector = true) {
        remove_voucher_detail_rows();
        reset_account_fields();
        reset_particulars_fields();
        reset_voucher_identity_fields(clear_office_selector);
        hide_buttons();

    }

    $(".btn-reset").on('click', function() {
        resetVoucher();
    });

    //Add Commas Added by Livingstone Onduso 
    $(document).on('keydown', '.number-fields', function(event) {

        addCommasToNumber($(this), event);

    });


    //Function to put commas when User is typing Added  by Onduso
    function addCommasToNumber(elem, event) {


        $(elem).on('input', function() {
            let value = $(elem).val().replace(/,/g, '');
            let parts = value.split('.');
            let integerPart = parseInt(parts[0]).toLocaleString();
            let decimalPart = parts.length > 1 ? '.' + parts[1] : '';
            let formattedNumber = integerPart + decimalPart;

            //Add commas and check if after stripping off the commas

            formattedNumber = formattedNumber.replace(/(\d)(?=(\d{3})+$)/g, '$1,');

            if (!isNaN(parseFloat(formattedNumber))) {
                $(elem).val(formattedNumber);
            } //Check if the input is a valid number and not just whitespace
            else if (event.which === 8 && event.which === 46 && !$.isNumeric($(elem).val(formattedNumber))) {
                alert("<?php echo get_phrase('non_number_error', "Error: Invalid input. Please enter a number.") ?>");
                $(elem).val('');

            }

        });
    }

    function copyRow() {

        var tbl_body = $("#tbl_voucher_body tbody");

        var original_row = tbl_body.find('tr').clone()[0];

        tbl_body.append(original_row);

        $.each(tbl_body.find("tr:last").find('input'), function(i, el) {
            let resatable_fields = ['quantity', 'description', 'unitcost'];
            var elem = $(el);

            resatable_fields.forEach(function(fieldClass, index) {
                if (elem.hasClass(fieldClass)) {
                    elem.removeAttr('readonly');
                }

                if (elem.hasClass('number-fields')) {
                    if (elem.hasClass('quantity')) {
                        elem.val(1);
                    } else {
                        elem.val(0);
                    }

                } else {
                    elem.val("");
                }
            });
        });
    }

    function insertRow(response_is_contra = false) {
        var tbl_body = $("#tbl_voucher_body tbody");
        var tbl_head = $("#tbl_voucher_body thead");

        var cell = actionCell();
        cell += quantityCell();
        cell += descriptionCell();
        cell += unitCostCell();
        cell += totalCostCell();

        if (toggle_accounts_by_allocation) {
            cell += allocationCodeCell();
            cell += accountCell();
        } else {
            cell += accountCell();
            cell += allocationCodeCell();
        }

        let office_has_request = '<?= $office_has_request; ?>';

        if (office_has_request) {
            cell += requestIdCell();
        }


        tbl_body.append("<tr>" + cell + "</tr>");
    }

    function voucher_has_request_details_inserted() {
        var request_number = $('.request_number');
        var voucher_has_request_details_inserted = false;


        $.each(request_number, function(i, elem) {
            if ($(elem).val() > 0) {
                voucher_has_request_details_inserted = true;
                return false;
            }
        });

        return voucher_has_request_details_inserted;

    }

    $(".btn-insert").on('click', function() {

        $('.account_fields').each(function(i, elem) {
            //$(elem).attr("style", "pointer-events: none;");
            if ($(elem).val() != '') {
                if ($(elem).is('select')) {
                    $(elem).find('option:not(:selected)').prop('disabled', true);
                } else {
                    $(elem).prop('readonly', 'readonly');
                }
            }
        });

        var tbl_body_rows = $("#tbl_voucher_body tbody tr");

        if (tbl_body_rows.length == 0 || voucher_has_request_details_inserted()) {

            var count_account_fields = 0;

            $(".account_fields").each(function(i, elem) {
                if (!$(elem).closest('span').hasClass('hidden') && $(elem).val() == '') {
                    count_account_fields++;
                    $(elem).css('border', '1px red solid');
                }
            });

            if (count_account_fields) {
                alert('Complete filling the required cash or bank account fields');
                return false;
            }

            updateAccountAndAllocationField();
        } else {
            copyRow();
        }

        $(".btn-save, .btn-save-new").show();
    });


    function updateAccountAndAllocationField() {
        var office_id = $("#office").val();

        var transaction_date = $("#transaction_date").val();

        var voucher_type_id = $("#voucher_type").val(); // Can be expense, income, cash_contra or bank_contra
        var office_bank_id = !$("#bank").attr('disabled') ? $("#bank").val() : 0;
        var extra_data = {
            'office_bank_id': office_bank_id,
            'bank_refund_from': $('#bank_refund').length > 0 && $('#bank_refund').val() > 0 ? $('#bank_refund').val() : 0
        };
        var url = "<?= base_url(); ?>ajax/voucher/getVoucherAccountsAndAllocation/" + office_id + "/" + voucher_type_id + "/" + transaction_date + "/" + office_bank_id;

        $.ajax({
            url: url,
            type: "POST",
            data: extra_data,
            beforeSend: function() {

            },
            success: function(response_objects) {
                console.log(response_objects);
                var account_select_option = "<option value=''>Select an account</option>";
                var allocation_select_option = "<option value=''>Select an allocation code</option>";
                var response_allocation = response_objects.project_allocation;
                var response_is_contra = response_objects.is_contra;

                insertRow(response_is_contra);

                create_allocation_select_options(response_allocation);

            },
            error: function() {
                alert('Error occurred');
            }
        });
    }


    function removeRow(rowCellButton) {
        var row = $(rowCellButton).closest('tr');
        var tbl_body_rows = $("#tbl_voucher_body tbody tr");


        // Remove extra rows
        var count_body_rows = tbl_body_rows.length;

        if (count_body_rows > 1) {
            row.remove();
            updateTotalCost();
        } else {
            alert('You can\'t remove all rows');
        }


    }


    function computeTotalCost(numberField) {

        var activeCell = $(numberField);
        var row = $(numberField).closest('tr');

        var quantity = 0;
        var unitcost = 0;
        var totalcost = 0;

        if (activeCell.hasClass('quantity')) {
            quantity = activeCell.val();

            if (quantity == "" || quantity == null) {
                row.find('.quantity').val(0);
            }

            unitcost = row.find('.unitcost').val();
        } else {
            unitcost = activeCell.val();

            if (unitcost == "" || unitcost == null) {
                row.find('.unitcost').val(0);
            }

            quantity = row.find('.quantity').val();
        }
        //Remove commas added by Onduso
        unitcost = unitcost.replace(/,/g, "");
        quantity = quantity.replace(/,/g, "");

        totalcost = quantity * unitcost;

        //Add commas on totals Added by Onduso
        totalcost = totalcost.toLocaleString();

        row.find('.totalcost').val(totalcost);

        //Add commas
        let sum = sumVoucherDetailTotalCost().toLocaleString();

        $("#voucher_total").val(sum);

    }

    function updateTotalCost() {
        $("#voucher_total").val(sumVoucherDetailTotalCost());
    }

    function sumVoucherDetailTotalCost() {

        var sum = 0;

        $.each($('.totalcost'), function(i, el) {

            //Remove commas from computed totalcost : Add by Onduso
            let total = $(el).val();
            total = total.replace(/,/g, "");

            sum += parseFloat(total);
        });

        return sum;
    }

    function replaceValue(numberField) {
        $(numberField).val("");
    }

    function clearRow(el) {
        $(el).closest('tr').find(".body-input").val(null);
        $(el).closest('tr').find(".number-fields").val(0);
    }

    $(document).on('change', '.account', function() {

        const office_id = $("#office").val();
        const account_id = $(this).val();
        const voucher_type_id = $("#voucher_type").val();
        const transaction_date = $("#transaction_date").val();
        const url = "<?= base_url(); ?>ajax/voucher/getProjectDetailsAccount/";
        const office_bank_id = !$("#bank").attr('disabled') ? $("#bank").val() : 0;

        if (!toggle_accounts_by_allocation) {

            $.ajax({
                url: url,
                data: {
                    'office_id': office_id,
                    'account_id': account_id,
                    'voucher_type_id': voucher_type_id,
                    'transaction_date': transaction_date,
                    'office_bank_id': office_bank_id
                },
                type: "POST",
                success: function(response_allocation) {

                    // var response_allocation = JSON.parse(response);

                    let allocation_select_option = "<option value=''>Select an allocation code</option>";

                    if (response_allocation.length > 0) {
                        $(".allocation").removeAttr('disabled');
                        $.each(response_allocation, function(i, el) {
                            allocation_select_option += "<option value='" + response_allocation[i].project_allocation_id + "'>" + response_allocation[i].project_allocation_name + "</option>";
                        });
                    } else {
                        $(".allocation").prop('disabled', 'disabled');
                    }

                    $(".allocation").html(allocation_select_option);
                }
            });
        }
    });

    $(document).on('change', '.allocation', function() {

        var office_id = $("#office").val();
        var allocation_id = $(this).val();
        var voucher_type_id = $("#voucher_type").val();
        var transaction_date = $("#transaction_date").val();
        var office_bank_id = !$("#bank").attr('disabled') ? $("#bank").val() : 0;
        var row = $(this).closest('tr');

        var url = "<?= base_url(); ?>ajax/voucher/getAccountsForProjectAllocation/";
        var data = {
            'office_id': office_id,
            'allocation_id': allocation_id,
            'voucher_type_id': voucher_type_id,
            'transaction_date': transaction_date,
            'office_bank_id': office_bank_id,
            'bank_refund_from': $('#bank_refund').length > 0 && $('#bank_refund').val() > 0 ? $('#bank_refund').val() : 0
        };
        //console.log(data);

        if (toggle_accounts_by_allocation) {

            $.post(url, data, function(response_accounts) {

                // var response_accounts = JSON.parse(response);
                //console.log(response_accounts);
                var accounts_select_option = "<option value=''>Select account</option>";

                array_size = Object.keys(response_accounts).length;

                if (array_size > 0) {
                    $(".account").removeAttr('disabled');
                    $.each(response_accounts, function(i, el) {
                        accounts_select_option += "<option value='" + i + "'>" + response_accounts[i] + "</option>";
                    });
                } else {
                    $(".account").prop('disabled', 'disabled');
                }

                row.find(".account").html(accounts_select_option);
            });
        }

    });

    function actionCell() {
        return "<td><div class='btn btn-danger action' onclick='removeRow(this);'>Remove Row</div> &nbsp; <span onclick='clearRow(this);' class='fa fa-trash'></span> </td>";
    }

    function quantityCell(value = 1) {
        return "<td><input name='voucher_detail_quantity[]'  type='text' class='form-control required body-input number-fields quantity' onclick='replaceValue(this);' onchange='computeTotalCost(this);' value='" + value + "' /></td>";
    }

    function descriptionCell(value = '') {
        return "<td><input  name='voucher_detail_description[]' type='text' class='form-control required body-input description' value='" + value + "' autocomplete='off' /></td>";
    }

    function unitCostCell(value = '') {
        return "<td><input autocomplete='off' name='voucher_detail_unit_cost[]' type='text' class='form-control required body-input number-fields unitcost' onclick='replaceValue(this);'  onchange='computeTotalCost(this);'  value='" + value + "' /></td>";
    }

    function totalCostCell(value = 0) {
        return "<td><input name='voucher_detail_total_cost[]' type='text' class='form-control required body-input number-fields totalcost' value='" + value + "' readonly='readonly'/></td>";
    }

    function accountCell(value = 0) {

        if (toggle_accounts_by_allocation) {
            return "<td><select disabled='disabled' name='voucher_detail_account[]' class='form-control required body-input account' ></select></td>";
        } else {
            return "<td><select name='voucher_detail_account[]' class='form-control required body-input account' ></select></td>";
        }

    }

    function allocationCodeCell(value = 0) {

        if (toggle_accounts_by_allocation) {
            return "<td><select name='fk_project_allocation_id[]' class='form-control required body-input allocation'></select></td>";
        } else {
            return "<td><select disabled='disabled' name='fk_project_allocation_id[]' class='form-control required body-input allocation'></select></td>";
        }

    }

    function requestIdCell(value = 0) {
        return "<td><input name='fk_request_detail_id[]' type='number' class='form-control body-input number-fields request_number' value='" + value + "' readonly='readonly'/></td>";
    }

    function disable_elements_in_hidden_span() {
        var elem_in_hidden_span = $('span.hidden .account_fields');

        $.each(elem_in_hidden_span, function(i, elem) {
            if (!$(elem).attr('disabled')) {
                $(elem).prop('disabled', 'disabled');
            }
        });
    }

    function saveVoucher(clicked_btn) {

        //Check if the cheque_number_is_selected

        var cheque_number = $('#cheque_number').val();

        if (cheque_number != '') {

            var office_id = $('#office').val();

            let office_bank_id = $("#bank").val();

            let has_eft_class = $('#cheque_number').hasClass('eft') ? 1 : 0;

            var url = "<?= base_url(); ?>ajax/voucher/getDuplicateChequesForAnOffice/" + office_id + '/' + cheque_number + '/' + office_bank_id + '/' + has_eft_class;


            getRequest(url, function(response) {
                // return false;
                // console.log(JSON.parse(response));
                // return false;

                if (!response.check) {
                    post_voucher_transaction(clicked_btn)
                } else {
                    alert('<?= get_phrase('chq_duplicate', 'Selected Cheque number already used'); ?>');
                    //Repulated/referesh the cheque numbers if already used to avoid duplicate
                    check_cheque_validity();
                    $('#cheque_number').css('border', '1px solid red');
                    return false;
                }

            });


        } else {

            post_voucher_transaction(clicked_btn);

        }
    }

    function post_voucher_transaction(clicked_btn) {

        var url = "<?= base_url(); ?>voucher/multiFormAdd";
        var data = $("#frm_voucher").serializeArray();
        var tbl_body_rows = $("#tbl_voucher_body tbody tr");

        let limit_check_url = '<?= base_url(); ?>ajax/voucher/cashLimitExceedCheck'

        let office_id = $('#office').val();
        let office_bank_id = $("#bank").val();
        let office_cash_id = $('#cash_account').val();
        let voucher_type_id = $('#voucher_type').val();
        let amount = $('#voucher_total').val();
        let transaction_date = $('#transaction_date').val();
        let unapproved_and_approved_vouchers = $('#unapproved_and_approved_vouchers_cash_balance').val();
        let bank_balance = $('#bank_balance').val();
        let cheque_number = $('#cheque_number').val();

        let limit_data = {
            office_id,
            office_bank_id,
            office_cash_id,
            voucher_type_id,
            amount,
            transaction_date,
            unapproved_and_approved_vouchers,
            bank_balance,
            cheque_number
        };

        postRequest(limit_check_url, limit_data, function(limit_exceeded) {

            // console.log(limit_exceeded);

            if (limit_exceeded.cash_limit_exceeded) {
                alert('<?= get_phrase('cash_limit_exceeded', "You have exceeded the bank or cash balance"); ?>');
                return false;
            }


            if (tbl_body_rows.length == 0) {
                alert("Please add voucher details before saving the voucher");
                return false;
            }

            // Make all select or inputs in hidden span be disabled
            disable_elements_in_hidden_span();


            if (!check_required_fields()) { //quantity
                alert('Empty required fields exists or "unit cost and cost fields have amount with more than 2 decimal places"');
            } else {
                $.ajax({
                    url: url,
                    type: "POST",
                    data: data,
                    success: function(response) {
                        //alert(response);

                        if (clicked_btn.hasClass('btn-save')) {

                            if (response.flag) {
                                alert("Voucher posted successfully");
                                const referrer = document.referrer;
                                //Delete Duplicate MFR in a month
                                transaction_date = $("#transaction_date").val();
                                office_id = $("#office").val();
                                var ur = '<?= base_url() ?>ajax/voucher/deleteDuplicateMfr/' + transaction_date + '/' + office_id;
                                getRequest(ur, function(re_mf) {
                                    // console.log(re_mf)
                                });

                                //Delete Duplicate CJ in a month
                                var ur = '<?= base_url() ?>ajax/voucher/deleteDuplicateCj/' + transaction_date + '/' + office_id;
                                getRequest(ur, function(re_cj) {
                                    // console.log(re_cj)
                                });

                                if (referrer.indexOf("book") > 0) {
                                    window.location.href = '<?= base_url(); ?>voucher/list'
                                } else {
                                    location.href = document.referrer
                                }
                            } else {
                                alert("Voucher posting failed");
                            }

                        } else {
                            resetVoucher();
                        }
                    }

                });
            }



        })

    }

    $(".btn-save").on('click', function() {
        saveVoucher($(this));
    });

    $(".btn-save-new").on('click', function() {
        saveVoucher($(this));
    });

    function check_required_fields() {
        return_flag = true;

        $(".required").each(function(i, el) {
            if (
                $(el).val() == "" &&
                !$(el).attr('disabled') &&
                !$(el).attr('readonly') &&
                $(el).hasClass('required')
            ) {
                //$(el).addClass('validate_error');
                return_flag = false;
                $(el).css('border', '1px red solid');
            }

            if ($(el).hasClass('quantity') && ($(el).val() == ""|| $(el).val() <= 0 ||isNaN($(el).val().replace(/\,/g,'')))) {
                return_flag = false;
                $(el).css('border', '1px red solid');
            }

            if ($(el).hasClass('unitcost') && ($(el).val() == ""|| $(el).val() == 0 ||$(el).val().replace(/\,/g,'') <=0 ||isNaN($(el).val().replace(/\,/g,''))) || String($(el).val()).split(".")[1]?.length > 2) {
             
                return_flag = false;
                $(el).css('border', '1px red solid');
            }
        });

        return return_flag;
    }


    $(document).on('change', '.required', function() {
        if ($(this).attr('style')) {
            $(this).removeAttr('style');
        }
    });


    $(document).on('change', '.account', function() {
        
        if($('#bank_refund').closest('span').hasClass('hidden')){
            return false;
        }

        const row_index = $(this).closest('td').parent().index();
        const tbody = $('#tbl_tbody').find('tr')
        const raw_quantity_elem = $(this).closest('tr').find('.quantity');
        const raw_unitcost_elem = $(this).closest('tr').find('.unitcost');
        const raw_account_elem = $(this).closest('tr').find('.account');
        const raw_totalcost_elem = $(this).closest('tr').find('.totalcost');
        
        const qty = unformat_number(raw_quantity_elem.val()) > 0 ? parseFloat(unformat_number(raw_quantity_elem.val())) : 0;
        const unitcost = unformat_number(raw_unitcost_elem.val()) ? parseFloat(unformat_number(raw_unitcost_elem.val())) : 0;
        const totalcost = parseFloat(qty * unitcost)
        const url = "<?=base_url();?>ajax/voucher/validateRefundLimit"
        const voucherTypeId = $("#voucher_type").val()

        const data = {
            voucherTypeId,
            totalcost,
            office_id: $('#office').val(),
            account_id: $(this).val(),
            bank_refund_from : $('#bank_refund').length > 0 && $('#bank_refund').val() > 0 ? $('#bank_refund').val() : 0,
            refund_voucher_amount: $("#refund_voucher_amount").length > 0 && unformat_number($("#refund_voucher_amount").val()) > 0 ? unformat_number($("#refund_voucher_amount").val()) : 0
        }

        postRequest(url, data, function (response){
            // console.log(response);
            if(response.is_refundable == 0){
                alert("<?=get_phrase('exceed_refund_amount','Amount being refunded is greater that the detail amount');?>");
                raw_unitcost_elem.val('')
                raw_totalcost_elem.val('')
                raw_account_elem.val($("#target option:first").val());
            }else{

                let account_sum = 0;
                let account_id = response.post['account_id']
                
                tbody.each((index, elem) => {
                    // alert(row_index + ' - ' + index)
                    let account_id_in_row = $(elem).find('.account').val()
                    if(account_id_in_row == account_id && row_index != index){
                        account_sum += parseFloat(unformat_number($(elem).find('.quantity').val())) * parseFloat(unformat_number($(elem).find('.unitcost').val()))
                    }
                });
                
                sum_in_rows = parseFloat(account_sum) + parseFloat(response.post['totalcost'])
                if(parseFloat(response.detail_amount) < sum_in_rows){
                    alert("<?=get_phrase('exceed_refund_amount','Amount being refunded is greater that the detail amount');?>");
                    raw_unitcost_elem.val('')
                    raw_totalcost_elem.val('')
                    raw_account_elem.val($("#target option:first").val());
                }
            }

        })
    })

    $('#bank_refund').on('keypress', function() {
        hide_buttons();
        $("#refund_voucher_amount").val(0);
        $('#bank_refund_error').html('')
    });

    $(document).keypress('#bank_refund',function (e) {
        let key = e.which;
        if(key == 13)  // the enter key code
        {
            search_voucher_amount();
        }
    })
    
    $("#bank_refund_search").on('click', function(){
        search_voucher_amount();
    });

    $("#bank_refund").on('change', function(){
        search_voucher_amount();
    });

    function search_voucher_amount(){
        let voucher_type_id = $("#voucher_type").val()
        let bank_refund_voucher = $('#bank_refund').val()
        let office_id = $('#office').val()

        let data = {
            bank_refund_voucher,
            office_id,
            voucher_type_id
        }
        let url = "<?=base_url();?>ajax/voucher/validateRefundFromVoucher/" + office_id
        
        if(bank_refund_voucher.length == 0){
            alert('<?=get_phrase('bank_refund_voucher_error','You MUST Provide A Valid Voucher');?>');
            return false;
        }

        $('#bank_refund_error').html('')

        postRequest(url, data, function (response){
            hide_buttons();
            $("#refund_voucher_amount").val(0);
            $("#bank_refund").val('');

            if(voucher_type_id){
                if(response.message == 'success'){
                    $(".btn-insert").show();
                    $(".btn-retrieve-request").show();

                    $("#refund_voucher_amount").closest('span').removeClass('hidden');
                    $("#refund_voucher_amount").val(format_number(response.voucher_cost));
                    $("#bank_refund").val(response.voucher_number);
                }else{
                    $('#bank_refund_error').html(response.message)
                }
            }else{
                $('#bank_refund_error').html('<?=get_phrase("choose_valid_voucher_type", "Choose A valid Vocuher Type.");?>')
            }
        })
    }
</script>