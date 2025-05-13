<?php 
use \App\Libraries\System\Widgets\WidgetBase;

$voucherLibrary = new \App\Libraries\Grants\VoucherLibrary();
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
$original_voucher_type_before_edit = $result['voucher_header_info']['fk_voucher_type_id'];
$voucher_missing_details = $voucherLibrary->isVoucherMissingVoucherDetails($voucher_header_info['voucher_id']);
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
                    <?php echo get_phrase('edit_voucher', 'Edit Voucher'); ?>

                    <!-- Missing voucher details -->
                    <?php if ($voucher_missing_details) { ?>
                        <i style='color:red; font-size: 18px;'><?= get_phrase('missing_voucher_msg', '[Voucher is missing details, kindly add and repost it.]'); ?></i>

                    <?php } ?>
                </div>
            </div>

            <div class="panel-body" style="max-width:50; overflow: auto;">
                <?php echo form_open("", array('id' => 'frm_voucher', 'class' => 'form-horizontal form-groups-bordered validate', 'enctype' => 'multipart/form-data')); ?>



                <div class='form-group'>
                    <label class='control-label col-xs-1'><?= get_phrase('office', 'Office'); ?></label>
                    <div class='col-xs-2'>
                        <select class='form-control required ' id='office' name='fk_office_id' readonly>
                            <!-- Options are added by jquery code  -->

                        </select>
                    </div>

                    <label class='control-label col-xs-1'><?= get_phrase('transaction_date', 'Transaction Date'); ?></label>
                    <div class='col-xs-2' id='transaction_div_id'>

                        <!-- Input box added on fly by Jquery code -->
                    </div>

                    <label class='control-label col-xs-1'><?= get_phrase('voucher_number', 'Voucher Number'); ?></label>
                    <div class='col-xs-2' id='voucher_number_div_id'>
                        <!-- Voucher number field is added by Jquery -->
                    </div>

                    <input class='hidden' id='hold_voucher_type_effect_for_edit' name='hold_voucher_type_effect_for_edit' value='' />

                    <label class='control-label col-xs-1'><?= get_phrase('voucher_type', 'Voucher Type'); ?></label>
                    <div class='col-xs-2'>
                        <select class='form-control required' name='fk_voucher_type_id' id='voucher_type'>
                            <!-- Voucher Type Dropdown drawn by JQuery -->

                        </select>
                    </div>

                </div>

                <div class='form-group'>

                    <span class='hidden' id='bank_account_span_id'>
                        <label class='control-label col-xs-1'><?= get_phrase('bank_account', 'Bank Account'); ?></label>
                        <div class='col-xs-2'>
                            <select onchange="get_bank_cash_information(this);" class="form-control required account_fields" name='fk_office_bank_id' id='bank'>
                                <!-- Bank account Type Dropdown drawn by JQuery -->

                            </select>
                        </div>
                    </span>
                    <input class='hidden' id='hold_cheque_number_to_append_when_effect_is_same' name='hold_cheque_number_to_append_when_effect_is_same' value='' />

                    <span class='hidden' id='cheque_number_span_id'>
                        <label class='control-label col-xs-1'><?= get_phrase('cheque_number', 'Cheque Number'); ?></label>
                        <div class='col-xs-2'>
                            <input class='hidden' id='hold_cheque_number_for_edit' name='hold_cheque_number_for_edit' value='u' />
                            <select class='form-control required account_fields' name='voucher_cheque_number' id='cheque_number'>
                                <!-- Cheque numbers dropdown drawn by JQuery -->
                            </select>

                        </div>
                    </span>
                    <?php
                    // Display the button if voucher type="Voided Cheque"

                    if (trim($voucher_header_info["voucher_type_name"]) == "Voided Cheque") { ?>

                        <button class='btn btn-primary' id='revert_cheque' name=''><?= get_phrase('revert_cheque', "Revert Cheque"); ?></button>
                        <span id="message_id"></span>

                    <?php } ?>

                    <span class='hidden' id='cash_recipient_account_span_id'>
                        <label class='control-label col-xs-1'><?= get_phrase('receiving_account', 'Receiving Account'); ?></label>
                        <div class='col-xs-2'>
                            <select onchange="get_bank_cash_information(this);" class="form-control required account_fields" name='cash_recipient_account' id='cash_recipient_account'>
                                <!-- Bank account Type Dropdown drawn by JQuery -->

                            </select>
                        </div>
                    </span>
                    <span class='hidden' id='cash_account_span_id'>
                        <label class='control-label col-xs-1'><?= get_phrase('cash_account', 'Cash Account'); ?></label>
                        <div class='col-xs-2'>
                            <select class="form-control required account_fields" name='fk_office_cash_id' id='cash_account'>
                                <!-- Dropdown for Cash account fields drawn by JQuery -->
                            </select>
                        </div>
                    </span>
                </div>

                <div class='form-group'>
                    <span class='hidden' id='total_bank_span_id'>
                        <label class='control-label col-xs-1'><?= get_phrase('bank_balance', 'Bank Balance'); ?></label>
                        <div class='col-xs-2'><input id='bank_balance' class='form-control' value='0' name='bank_balance' onkeydown="return false" /></div>
                    </span>
                    <!--  Total Unapproved and Approved vouchers-->
                    <span class='hidden' id="total_cash_span_id">
                        <label class='control-label col-xs-1'><?= get_phrase('total_cash_balance', 'Total Cash Bal.'); ?></label>
                        <div class='col-xs-2'><input id='unapproved_and_approved_vouchers_cash_balance' name='unapproved_and_approved_vouchers' class='form-control' value='0' onkeydown="return false" /></div>
                    </span>
                </div>

                <div class='form-group'>
                    <label class='col-xs-1'><?= get_phrase('payee/_vendor', 'Payee Vendor'); ?></label>
                    <div class='col-xs-11'>
                        <input type='text' id='voucher_vendor' name='voucher_vendor' class='form-control required' autocomplete="off" />
                    </div>
                </div>

                <div class='form-group'>
                    <label class='col-xs-1'><?= get_phrase('address', 'Address'); ?></label>
                    <div class='col-xs-11'>
                        <input type='text' id='voucher_vendor_address' name='voucher_vendor_address' class='form-control required' autocomplete="off" />
                    </div>
                </div>

                <div class='form-group'>
                    <label class='col-xs-1'><?= get_phrase('description', 'Description'); ?></label>
                    <div class='col-xs-11'>
                        <input type='text' id='voucher_description' name='voucher_description' class='form-control required' autocomplete="off" />
                    </div>
                </div>

                <!-- Voucher Detail Area -->

                <div class='form-group'>
                    <div class='col-xs-12'>

                        <!-- Voucher is missing voucher details -->
                        <?php if ($voucher_missing_details) { ?>
                            <i style='color:red; font-size:18px;'><?= get_phrase('missing_voucher_msg', '[Voucher is missing details, kindly add and repost it.]'); ?></i>
                        <?php } ?>
                        <table class='table table-striped' id='tbl_voucher_body'>
                            <thead>
                                <tr>
                                    <th><?= get_phrase('action', 'Action'); ?></th>
                                    <th><?= get_phrase('quantity', 'Quantity'); ?></th>
                                    <th><?= get_phrase('description', 'Description'); ?></th>
                                    <th><?= get_phrase('unit_cost', 'Unit Cost'); ?></th>
                                    <th><?= get_phrase('total_cost', 'Total Cost'); ?></th>

                                    <?php
                                    $toggle_accounts_by_allocation = $config->toggle_accounts_by_allocation;

                                    if ($toggle_accounts_by_allocation) {
                                    ?>
                                        <th><?= get_phrase('allocation_code', 'Allocation Code'); ?></th>
                                        <th><?= get_phrase('account', 'Account'); ?></th>
                                    <?php } else { ?>
                                        <th><?= get_phrase('account', 'Account'); ?></th>
                                        <th><?= get_phrase('allocation_code', 'Allocation Code'); ?></th>
                                    <?php } ?>


                                </tr>
                            </thead>
                            <tbody id="tbl_tbody">
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan='6'><?= get_phrase('total', 'Total'); ?></td>
                                    <td><input type='text' id='voucher_total' class='form-control' readonly /></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class='form-group'>
                    <div class='col-xs-12 center'>

                        <div class='btn btn-default btn-insert hidden'><?= get_phrase('insert_voucher_detail_row', 'Insert Voucher Details Row'); ?></div>
                        <div class='btn btn-default btn-save hidden'><?= get_phrase('save', 'Save Changes'); ?></div>
                        <div class='btn btn-default btn-cancel'><?= get_phrase('cancel', 'Cancel Changes'); ?></div>

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

<script>
    var toggle_accounts_by_allocation = '<?= $config->toggle_accounts_by_allocation; ?>';

    var storeVoucherDetailsIdsToDelete = [];
    var storeVoucherIDsToDeleteOnChangeEffect = [];
    var original_voucher_type_id = '<?= $original_voucher_type_before_edit; ?>'

    function load_approved_requests() {
        var office = $("#office").val();
        var url = "<?= base_url(); ?>ajax/voucher/getApproveRequestDetails/" + office;

        $("#request_screen").html("");

        $.ajax({
            url: url,
            beforeSend: function() {

            },
            success: function(responseObj) {
                const response = responseObj.result;

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

    //Add the voucher details and remove the voucher details sections when the voucher effects and same e.g. income!=expense
    function populate_voucher_details_id_for_delete(voucher_type_id_from_db, selectVoucherType) {

        //get the existing and new voucher_effect
        let url_voucher_effect_original = '<?= base_url(); ?>ajax/voucher/getVoucherTypeEffect/' + voucher_type_id_from_db;

        let url_voucher_effect = '<?= base_url(); ?>ajax/voucher/getVoucherTypeEffect/' + selectVoucherType;

        $.get(url_voucher_effect_original, function(res_for_existing_effect_obj) {
            const res_for_existing_effect = res_for_existing_effect_obj.result;
            //Store the original voucher effect
            $('#hold_voucher_type_effect_for_edit').attr('value', res_for_existing_effect);

            //Remove the detail rows
            $.get(url_voucher_effect, function(res_for_new_effect) {

                if (res_for_existing_effect != res_for_new_effect) {

                    //confirm deletion of all voucher details records otherwise refresh
                    let response = "Are sure you to delete voucher details and add new ones?";

                    if (confirm(response) == true) {
                        removeAllRows(res_for_existing_effect, res_for_new_effect);
                    } else {
                        //refresh page
                        location.reload();
                    }


                } else {

                    //Remove and Return populated Rows so that to avoid duplication of rows when saving
                    removeAllRows(res_for_existing_effect, res_for_new_effect);
                    populate_voucher_detail_to_edit();

                }

            });

        });
    }

    $(document).on('change', '#voucher_type', function() {

        //Clear the Cheque and office_cash
        let voucher_type_id_from_db = '<?= $voucher_header_info["fk_voucher_type_id"]; ?>';

        populate_voucher_details_id_for_delete(voucher_type_id_from_db, $(this).val());

        if (parseInt($(this).val()) != parseInt(voucher_type_id_from_db)) {

            $('#cheque_number').html('');

            $("#cash_account").html('');
        }

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



        checkIfDateIsSelected() ? $.get(url, function(response_objects) {

            // var response_objects = JSON.parse(response);
            var response_office_cash = response_objects['office_cash'];
            var response_office_bank = response_objects['office_banks'];
            var response_is_transfer_contra = response_objects['is_transfer_contra'];
            var response_is_bank_payment = response_objects['is_bank_payment'];
            var response_is_voucher_type_requires_cheque_referencing = response_objects['voucher_type_requires_cheque_referencing'];


            $.get(active_request_url, office, function(response) {
                var badges = $(".requests_badge");
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

            if (voucher_type_id) {
                //update_request_details_count_on_badge();
                $(".btn-insert").show();
                $(".btn-retrieve-request").show();
            } else {
                hide_buttons();
            }

        }) : alert('<?= get_phrase('choose_valid_date', "Choose a valid date"); ?>');

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

            url = '<?= base_url() ?>ajax/voucher/unapprovedMonthVouchers/' + office_id + '/' + transction_date + '/' + effect_code + '/' + account_code + '/' + office_cash_id + '/' + 0;
        }

        //CHeck if bank_office_id exists
        if ($('#bank').length) {

            var office_bank_id = $('#bank').val();

            url = '<?= base_url() ?>ajax/voucher/unapprovedMonthVouchers/' + office_id + '/' + transction_date + '/' + effect_code + '/' + account_code + '/' + 0 + '/' + office_bank_id;


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


    $("#bank").on("change", function(ev) {

        const office = $('#office').val();
        const bank = $(this).val();


        // Toogle Disable when a bank account is selected or not
        if ($(this).val() != '') {
            // alert('Hellooo');
            $("#cheque_number").removeAttr('disabled');
            $("#cheque_number").removeAttr('readonly');

            $("#bank_balance").closest('span').removeClass('hidden');

            compute_bank_balance(office, bank);

        } else {
            $("#cheque_number").val("");
            $("#cheque_number").prop('disabled', 'disabled');
            $("#bank_balance").closest('span').addClass('hidden');
        }


        // Populate transfer account list
        // if (!$("#cash_recipient_account").closest('span').hasClass('hidden')) {
        //     populate_cash_transfer_recipient($(this));
        // }

        if ($("#cheque_number").is('input') && $("#cheque_number").val() != "") {
            checkIfEftREfIsValid(office, bank, $("#cheque_number").val());
        } else {

            /*Include the chq number in chec_cheque_validity() dropdown as an option editing 
            records that need cheque refrencing*/

            let chqNoToAppend = $("#hold_cheque_number_to_append_when_effect_is_same").val();
            let orignalVoucherTypeEffefct = $('#hold_voucher_type_effect_for_edit').val();
            let urlCurrentVoucherType = '<?= base_url() ?>ajax/voucher/voucherTypeRequiresChequeReferencing/' + $("#voucher_type").val();

            $.get(urlCurrentVoucherType, function(obj) {
                const response = obj.result;
                if (parseInt(response) == 1) {
                    check_cheque_validity(chqNoToAppend);
                } else {
                    check_cheque_validity();
                }

            });

        }

    });


    function compute_bank_balance(office_id, office_bank_id) {

        //get url and split to pick the last item (voucher ID)
        const currentURL = window.location.href;
        let splitted_url = currentURL.split('/').pop();

        const url = '<?= base_url(); ?>ajax/voucher/computeBankBalance/1';
        const transaction_date = $('#transaction_date').val();
        const data = {
            office_id,
            office_bank_id,
            transaction_date,
            voucher_being_edited_id: splitted_url
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

        //get url and split to pick the last item (voucher ID)
        const currentURL = window.location.href;
        let splitted_url = currentURL.split('/').pop();

        const url = '<?= base_url(); ?>ajax/voucher/computeCashBalance/1';
        const transaction_date = $('#transaction_date').val();
        const data = {
            office_id,
            office_cash_id,
            transaction_date,
            voucher_being_edited_id: splitted_url
        };

        $.post(url, data, function(cash_balance) {
            //$('#cash_balance').val(cash_balance);
            // const cash_balance = JSON.parse(response);

            cash_amount = cash_balance.approved_and_unapproved_vouchers_cash_bal;

            let put_commas_on_cash_amount = cash_amount.toString().split(".");

            put_commas_on_cash_amount[0] = put_commas_on_cash_amount[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");

            $('#unapproved_and_approved_vouchers_cash_balance').val(put_commas_on_cash_amount.join('.'));
            $('#approved_vouchers_cash_balance').val(cash_balance.approved_vouchers_journal_bal);
            //console.log(JSON.parse(response));
        })
    }



    function check_cheque_validity(appendNoIfSameEffect = 0) {

        const office = $("#office").val();
        const bank = $("#bank").val();
        const cheque_number = $("#cheque_number").val();
        const url = "<?= base_url(); ?>ajax/voucher/checkChequeValidity";
        const data = {
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
            const obj = response.leaves
            var options = '<option value=""><?= get_phrase('select_cheque_number', 'Select Cheque Number'); ?></option>';

            if (Object.keys(obj).length === 0) {
                alert('The bank account selected lacks a cheque book');
            } else {
                if (appendNoIfSameEffect != 0) {
                    options += "<option value='" + appendNoIfSameEffect + "'>" + appendNoIfSameEffect + "</option>";
                }
                //console.log(response);
                $.each(obj, function(i, elem) {

                    options += "<option value='" + elem.cheque_id + "'>" + elem.cheque_number + "</option>";
                });
            }

            $("#cheque_number").html(options);
        });
    }

    function checkIfEftREfIsValid(office, bank, cheque_number) {

        var url = "<?= base_url(); ?>ajax/voucher/checkEftValidity";
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

        var url = "<?= base_url(); ?>voucher/computeNextVoucherNumber/";

        $.ajax({
            url: url,
            data: {
                'office_id': office_id
            },
            type: "POST",
            beforeSend: function() {

            },
            success: function(response) {
                //console.log(response);
                $("#voucher_number").val(response.voucherNumber);
            },
            error: function() {
                alert('<?= get_phrase('error_occured', 'Error occurred'); ?>');
            }
        });
    }

    //  Added by Onduso on 2/06/2023

    $(document).ready(function() {

        //Populate the fields
        voucher_header_fields();

        populate_voucher_detail_to_edit();


    });

    function getSavedVoucherTypeAndOtherActiveTypes() {

        var office_id = $("#office").val();
        let transaction_date = $('#transaction_date').val();

        // alert(transaction_date)

        var url = "<?= base_url(); ?>ajax/voucher/getActiveVoucherTypes/" + office_id + "/" + transaction_date;

        $.get(url, function(response_voucher_type) {
            // var response_voucher_type = JSON.parse(response);

            //console.log(response_voucher_type);

            //Saved voucher from server;
            let saved_voucher_type_to_edit = '<?= $voucher_header_info["fk_voucher_type_id"] ?>';

            let saved_voucher_effect_name_to_edit = '<?= $voucher_header_info["voucher_type_effect_name"] ?>';

            //Loop and append the other office active voucher type
            let voucher_type_option = "";

            if (response_voucher_type.length > 0) {
                $.each(response_voucher_type, function(i, el) {

                    let voucher_type_id = response_voucher_type[i].voucher_type_id;

                    if ((voucher_type_id != parseInt(saved_voucher_type_to_edit))) {

                        voucher_type_option += "<option value='" + voucher_type_id + "'>" + response_voucher_type[i].voucher_type_name + "</option>";

                    }

                });
            }
            $("#voucher_type").append(voucher_type_option);
        });
    }

    //This builds the fields of voucher header part
    function voucher_header_fields(test = false) {

        let voucher_id = '<?= hash_id($id, 'decode') ?>';

        let url = '<?= base_url() ?>ajax/voucher/voucherHeaderRecords/' + voucher_id;

        $.get(url, function(voucher_records) {

            // let voucher_records = JSON.parse(response);
            // console.log(voucher_records);

            //Populate Office Field : Office
            let option = "<option value='" + voucher_records.office_id + "'>" + voucher_records.office_code + "<option/>";

            $('#office').append(option);

            //Populate Transaction Date Field and calculate the next voucher transaction date
            let transaction_date = "<input class='form-control  required' name='voucher_date' type='text'" + "value=" + voucher_records.voucher_date + " id='transaction_date' readonly/>";

            $('#transaction_div_id').append(transaction_date);

            computeCurrentTransactingDate(voucher_records.office_id);

            //Populate voucher date
            let voucher_number = "<input type='text' onkeydown=" + "return false" + " class='form-control required' name='voucher_number' id='voucher_number' value='" + voucher_records.voucher_number + "' readonly />";

            $('#voucher_number_div_id').append(voucher_number);

            //Populate the voucher type dropdown and append the other active voucher type
            let voucher_type = "<option value='" + voucher_records.fk_voucher_type_id + "'>" + voucher_records.voucher_type_name + "</option>";

            $('#voucher_type').append(voucher_type);

            getSavedVoucherTypeAndOtherActiveTypes();


            //Populate Cash Account field if the Payment by cash and voucher_type_account_name=cash and voucher_type_effect_name=expense
            let account_type = voucher_records.voucher_type_account_name;

            let voucher_effect = voucher_records.voucher_type_effect_name;

            let voucher_chq_number = voucher_records.voucher_cheque_number;

            if ((account_type == 'Cash' && (voucher_effect == 'Expense' || voucher_effect == 'Cash_contra')) || (account_type == 'Bank' && (voucher_effect == 'Bank_contra' || voucher_effect == 'Expense' || voucher_effect == 'Income' || voucher_effect == 'Bank_to_bank_contra'))) {

                if (voucher_records.fk_office_cash_id != 0) {

                    let cash_account = "<option value='" + voucher_records.fk_office_cash_id + "'>" + voucher_records.office_cash_name + "</option>";

                    $('#cash_account_span_id').removeClass('hidden');

                    $('#cash_account').append(cash_account);

                    //Append the other office cash
                    let active_office_cash_url = '<?= base_url() ?>ajax/voucher/getActiveOfficeCash';

                    populate_dropdowns(active_office_cash_url, '#cash_account', 'cash');

                    //populate cash fields
                    $('#total_cash_span_id').removeClass('hidden');

                    compute_cash_balance(voucher_records.office_id, voucher_records.fk_office_cash_id);

                }


                //Populate total bank fields
                if (voucher_records.voucher_type_account_name == 'Bank' || voucher_effect == 'Cash_contra') {

                    $('#total_bank_span_id').removeClass('hidden');

                    compute_bank_balance(voucher_records.office_id, voucher_records.fk_office_bank_id);
                }
                //Populate Bank fields
                if (voucher_chq_number != 0 || voucher_effect == 'Cash_contra' || voucher_effect == 'Income') {

                    //Display bank account field and add active bank account
                    $('#bank_account_span_id').removeClass('hidden');

                    let bank_account = "<option value='" + voucher_records.fk_office_bank_id + "'>" + voucher_records.office_bank_name + "</option>";

                    $('#bank').append(bank_account);

                    let active_office_bank_url = '<?= base_url() ?>ajax/voucher/getActiveOfficeBank/' + voucher_records.office_id;

                    populate_dropdowns(active_office_bank_url, '#bank', 'bank');

                    //Display and draw cheque number dropdown when cheque >0
                    if (voucher_chq_number > 0 && voucher_records.voucher_type_is_cheque_referenced == 1) {

                        $('#cheque_number_span_id').removeClass('hidden');

                        //Hold chq numbers for chq validation
                        $('#hold_cheque_number_for_edit').val(voucher_records.voucher_cheque_number);

                        $('#hold_cheque_number_to_append_when_effect_is_same').attr('value', voucher_records.voucher_cheque_number);

                        //$('#hold_cheque_number_for_edit').addClass('hidden');

                        let cheque_numbers = "<option value='" + voucher_records.voucher_cheque_number + "'>" + voucher_records.voucher_cheque_number + "</option>";

                        $('#cheque_number').append(cheque_numbers);

                        //add other active chqs;
                        add_other_active_chqs();

                    } //Draw EFT fields
                    else if (voucher_records.voucher_type_is_cheque_referenced == 0 && voucher_records.voucher_cheque_number != 0) {

                        $('#cheque_number_span_id').removeClass('hidden');

                        //Hold chq numbers for chq validation
                        $('#secondary_input').val(voucher_records.voucher_cheque_number);

                        // alert($('#secondary_input').val());

                        change_voucher_number_field_to_eft_number(voucher_records.voucher_type_is_cheque_referenced);

                        // alert($('#secondary_input').val());

                        let el = $('#cheque_number');

                        el.removeAttr('readonly');

                        el.attr('value', voucher_records.voucher_cheque_number);


                    }
                    //Draw reciepient bank if bank_to_bank_contra
                    if (voucher_effect == 'Bank_to_bank_contra') {

                        // let cash_account = "<option value='" + voucher_records.fk_office_cash_id + "'>" + voucher_records.office_cash_name + "</option>";

                        $('#cash_recipient_account_span_id').removeClass('hidden');


                        let active_recipient_bank_url = '<?= base_url() ?>ajax/voucher/getActiveRecipientBank/' + voucher_id;

                        $.get(active_recipient_bank_url, function(result) {


                            // let result = JSON.parse(res);
                            //console.log(result);

                            let recipient_account = "<option value='" + result[0].office_bank_id + "'>" + result[0].office_bank_name + "</option>";

                            $('#cash_recipient_account').append(recipient_account);

                            //    let other_office_bank_url = '<?= base_url() ?>voucher/get_active_office_bank/' + voucher_records.office_id;

                            //    populate_dropdowns(other_office_bank_url, '#cash_recipient_account', 'bank');

                            //populate_dropdowns(active_recipient_bank_url, '#cash_recipient_account', 'bank');
                        })




                    }

                }
            }

            //Particulars Fields
            voucher_particulars_fields(voucher_records.voucher_vendor, voucher_records.voucher_vendor_address, voucher_records.voucher_description);

            $('#secondary_input').val('');
        });
    }
    //Particulars Fields
    function voucher_particulars_fields(payee_edit_data, address_edit_data, description_edit_data) {
        $('#voucher_vendor').attr('value', payee_edit_data);

        $("#voucher_vendor_address").attr('value', address_edit_data);

        $('#voucher_description').attr('value', description_edit_data);
    }

    //Url, element_id, type_of_resource e.g. office_banks

    function populate_dropdowns(url, element_id, type_of_resourse) {

        let saved_record_to_edit;

        $.get(url, function(records) {

            // let records = JSON.parse(response);

            if (type_of_resourse == 'bank') {
                //Saved voucher from server;
                saved_record_to_edit = '<?= $voucher_header_info["fk_office_bank_id"] ?>';

            }

            //Loop and append the other office active voucher type
            let option = "";

            if (records.length > 0) {
                $.each(records, function(i, el) {
                    //Bank Office Dropdown
                    if (type_of_resourse == 'bank') {

                        saved_record_to_edit = '<?= $voucher_header_info["fk_office_bank_id"] ?>';

                        if (records[i].office_bank_id != parseInt(saved_record_to_edit)) {

                            option += "<option value='" + records[i].office_bank_id + "'>" + records[i].office_bank_name + "</option>";

                        }
                    }
                    //Populate voucher_type Dropdown
                    else if (type_of_resourse == 'cash') {
                        saved_record_to_edit = '<?= $voucher_header_info["fk_office_cash_id"] ?>';
                        if (records[i].office_cash_id != parseInt(saved_record_to_edit)) {

                            option += "<option value='" + records[i].office_cash_id + "'>" + records[i].office_cash_name + "</option>";

                        }
                    }


                });
            }
            $(element_id).append(option);
        });

    }


    function add_other_active_chqs() {

        let office = $("#office").val();
        let bank = $("#bank").val();
        let cheque_number = $("#cheque_number").val();

        let option = "";

        let url = "<?= base_url(); ?>ajax/voucher/checkChequeValidity";

        let data = {
            'office_id': office,
            'bank_id': bank,
            'cheque_number': cheque_number
        };

        $.post(url, data, function(chequesObj) {
            const cheques = chequesObj.leaves
            const saved_record_to_edit = '<?= $voucher_header_info["voucher_cheque_number"] ?>';

            $.each(cheques, function(i, e) {

                if (cheques[i].cheque_id != parseInt(saved_record_to_edit)) {

                    option += "<option value='" + cheques[i].cheque_id + "'>" + cheques[i].cheque_id + "</option>";

                }

            });

            $("#cheque_number").append(option);
        });
    }

    function populate_voucher_detail_to_edit() {

        var tbl_body_rows = $("#tbl_voucher_body tbody tr");


        if (tbl_body_rows.length == 0 || voucher_has_request_details_inserted()) {
            updateAccountAndAllocationField_edit();
        } else {
            copyRow();
        }
    }


    function updateAccountAndAllocationField_edit() {

        let office_id = '<?= $voucher_header_info['office_id'] ?>';

        let voucher_type_id = '<?= $voucher_header_info['fk_voucher_type_id'] ?>';

        let transaction_date = '<?= $voucher_header_info['voucher_date'] ?>';

        let office_bank_id = '<?= $voucher_header_info['fk_office_bank_id'] ?>';

        let voucher_id = '<?= $voucher_header_info['voucher_id'] ?>';

        let voucher_type_effect_name = '<?= $voucher_header_info['voucher_type_effect_name'] ?>';


        // alert(voucher_type_effect_name);
        //console.log(voucher_type_effect_name);

        var extra_data = {
            'office_bank_id': office_bank_id,
            'bank_refund_from': $('#bank_refund').length > 0 && $('#bank_refund').val() > 0 ? $('#bank_refund').val() : 0
        };

        let url = "<?= base_url(); ?>ajax/voucher/getVoucherAccountsAndAllocation/" + office_id + "/" + voucher_type_id + "/" + transaction_date + "/" + office_bank_id;

        $.ajax({
            url: url,
            type: "POST",
            data: extra_data,
            beforeSend: function() {

            },
            success: function(response_objects) {
                var account_select_option = "<option value=''>Select an account</option>";
                var allocation_select_option = "<option value=''>Select an allocation code</option>";
                // var response_objects = JSON.parse(response);
                var response_allocation = response_objects['project_allocation'];
                var response_is_contra = response_objects['is_contra'];
                let url_saved_voucher_details = '<?= base_url(); ?>ajax/voucher/getVoucherDetailToEdit/' + voucher_id + '/' + voucher_type_effect_name;

                $.get(url_saved_voucher_details, function(voucher_details) {

                    //Get the last Element value
                    let total_amount = voucher_details.pop();

                    $.each(voucher_details, function(i, elm) {

                        let income_account = parseInt(elm.fk_income_account_id);

                        //console.log(income_account);

                        let expense_account = parseInt(elm.fk_expense_account_id);

                        let contra_account = parseInt(elm.fk_contra_account_id);

                        let allocation_id = parseInt(elm.fk_project_allocation_id);

                        let project_id = parseInt(elm.project_id);

                        //console.log(project_id);

                        //Insert Row
                        if (expense_account > 0) {

                            insertRow_to_edit_voucher(response_is_contra, elm.voucher_detail_id, elm.voucher_detail_quantity, elm.voucher_detail_description, elm.voucher_detail_unit_cost, elm.voucher_detail_total_cost, allocation_id, elm.fk_expense_account_id, elm.expense_account_name, elm.fk_income_account_id);
                            //console.log(income_account);
                            // alert(contra_account)
                        } else if (contra_account > 0) {
                            // alert('Hello 1')
                            insertRow_to_edit_voucher(response_is_contra, elm.voucher_detail_id, elm.voucher_detail_quantity, elm.voucher_detail_description, elm.voucher_detail_unit_cost, elm.voucher_detail_total_cost, allocation_id, contra_account, elm.contra_account_name, elm.fk_income_account_id);
                        } else {
                            //alert(allocation_id);
                            insertRow_to_edit_voucher(response_is_contra, elm.voucher_detail_id, elm.voucher_detail_quantity, elm.voucher_detail_description, elm.voucher_detail_unit_cost, elm.voucher_detail_total_cost, allocation_id, elm.fk_income_account_id, elm.income_account_name, elm.fk_income_account_id);
                        }

                        //Add Allocations
                        create_allocation_select_options_edit(response_allocation, allocation_id, elm.project_name);
                        //Populate other expense accounts

                        populate_other_active_expense_accounts(project_id, expense_account, income_account, contra_account, voucher_type_id);

                    });
                    //Fill the total amount
                    $('#voucher_total').val(total_amount.total_voucher_amount);

                    $('.btn-insert, .btn-save').removeClass('hidden')
                });

            },
            error: function() {
                alert('<?= get_phrase('error_occured', 'Error occurred'); ?>');
            }
        });
    }



    // function populate_other_active_expense_accounts(expense_id, office_id) {

    //     if (expense_id > 0) {

    //         let url = '<?= base_url() ?>voucher/get_expense_active_expense_account/' + expense_id + '/' + office_id;

    //         $.get(url, function(res) {

    //             var option = ''
    //             expeses_account = JSON.parse(res);
    //             //console.log(expeses_account);
    //             $.each(expeses_account, function(i, e) {
    //                 if (expense_id != e.expense_account_id) {
    //                     // console.log(e.expense_account_id);
    //                     option += "<option value='" + e.expense_account_id + "'>" + e.expense_account_name + "</option>";
    //                 };

    //             });
    //             //Append other expenses on the select

    //             $('#id_' + expense_id).append(option);


    //         });

    //     } else {
    //         //do nothing
    //     }

    // }



    //End

    //Compute the transacting month date
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
                // console.log(response);
                // let obj = JSON.parse(response);

                $("#transaction_date").val();

                $('#transaction_date').datepicker({
                    format: 'yyyy-mm-dd',
                    startDate: obj.next_vouching_date,
                    endDate: obj.last_vouching_month_date
                });


            },
            error: function() {
                alert('<?= get_phrase('error_occured', 'Error occurred'); ?>');
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

        const office_id = $("#office").val().trim();
        const bank_office_id = $("#bank").val().trim();
        const cheque_number = $(this).val().trim();
        const url = "<?= base_url(); ?>ajax/voucher/getChequesForOffice/" + office_id + "/" + bank_office_id + "/" + cheque_number;

        $.get(url, function(obj) {
            const response = obj.result;
            if (response) {
                alert('<?= get_phrase('chq_already_exists', 'The cheque number is already used. Choose another one'); ?>');

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

        $.post(url, extra_data, function(response) {

            var response_objects = JSON.parse(response);

            var response_is_voucher_type_requires_cheque_referencing = response_objects['voucher_type_requires_cheque_referencing'];

            //Get the active cheque of an office;
            
            if (response_is_voucher_type_requires_cheque_referencing) {
                var url = "<?= base_url(); ?>ajax/voucher/checkActiveChequeBookForOfficeBankExist/" + office_id + "/" + office_bank_id + "/" + transaction_date;
                $.get(url, function(response_obj) {
                    // alert(response);
                    // var response_obj = JSON.parse(response);
                    //Check if response =false and then redirect to the cheque form
                    if (!response_obj['is_active_cheque_book_existing']) {

                        alert('No active cheque book & you will be directed to add cheque book form');

                        var redirect_to_add_cheque_book_url = "<?= base_url(); ?>cheque_book/single_form_add";

                        window.location.replace(redirect_to_add_cheque_book_url);
                        //alert('Yes');
                    } else if (!response_obj['are_all_cheque_books_fully_approved']) {

                        alert('Your active cheque book is either unsubmitted, declined or reinstated and not approved. You will be redirect to the cheque book');

                        var redirect_to_add_cheque_book_url = "<?= base_url(); ?>cheque_book/view/" + response_obj['current_cheque_book_id']; //QnG6NpbmWr

                        window.location.replace(redirect_to_add_cheque_book_url);
                    }
                });
            }

        });


    }

    function change_voucher_number_field_to_eft_number(response_is_voucher_type_requires_cheque_referencing) {


        var cheque_number_div = $("#cheque_number").parent();


        if (response_is_voucher_type_requires_cheque_referencing == 0) {

            cheque_number_div.html('');
            //$('#cheque_number').val('');
            const secondary_input = $('#secondary_input').clone();
            cheque_number_div.append(secondary_input);

            $('#secondary_input').prop('id', 'cheque_number');
            $('#cheque_number').prop('name', 'voucher_cheque_number');
            $('#cheque_number').prop('readonly', 'readonly');
            $('#cheque_number').addClass('account_fields');
            $('#cheque_number').removeClass('hidden');
            $('#cheque_number').removeClass('required');

            $("#cheque_number").parent().prev().html('<?= get_phrase("EFT_serial", "EFT Serial"); ?>');

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

            $("#cheque_number").parent().prev().html('<?= get_phrase("cheque_number", 'Cheque Number'); ?>');
        }
    }

    function create_office_cash_dropdown(response_office_cash) {
        var account_select_option = "<option value=''><?= get_phrase('select_cash_account', 'Select Cash Account'); ?></option>";

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
        var account_select_option = "<option value=''><?= get_phrase('select_an_account', 'Select An Account'); ?></option>";

        if (isObject(response_accounts) && response_accounts.length > 0) {
            $.each(response_accounts, function(i, el) {
                account_select_option += "<option value='" + response_accounts[i].account_id + "'>" + response_accounts[i].account_name + "</option>";
            });
        }

        $(".account").html(account_select_option);

    }

    function create_allocation_select_options_edit(response_allocation, allocation_id_to_edit, allocation_name_to_edit) {

        let tbl_body = $("#tbl_voucher_body tbody");

        let allocation_select_option = '';

        if (response_allocation.length > 0) {

            $(".allocation").removeAttr('disabled');

            //The Saved record for editing
            allocation_select_option = "<option value='" + allocation_id_to_edit + "'>" + allocation_name_to_edit + "</option>";


            // console.log(allocation_id_to_edit);

            //Store the allocation_id



            $.each(response_allocation, function(i, el) {

                if (allocation_id_to_edit != response_allocation[i].project_allocation_id) {
                    allocation_select_option += "<option value='" + response_allocation[i].project_allocation_id + "'>" + response_allocation[i].project_allocation_name + "</option>";
                }

            });
        }

        tbl_body.find(':last-child').find(".allocation").html(allocation_select_option);

    }

    function create_allocation_select_options(response_allocation) {
        var tbl_body = $("#tbl_voucher_body tbody");
        var allocation_select_option = "<option value=''><?= get_phrase('select_an_allocation_code', 'Select An Allocation Code'); ?></option>";

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
        removeRow();
        reset_account_fields();
        reset_particulars_fields();
        reset_voucher_identity_fields(clear_office_selector);
        hide_buttons();

    }

    $(".btn-reset").on('click', function() {
        //resetVoucher();
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

        //Remove the attr for data-voucher_detail_id so that when you  the voucher_id
        tbl_body.find("tr:last").find('.btn').removeAttr('data-voucher_detail_id');

        //Remove the income account input id attr
        tbl_body.find("tr:last").find('input.income_account').removeAttr('id');

        let resatable_fields = ['hold_voucher_detail_id', 'quantity', 'description', 'unitcost', 'totalcost'];

        $.each(resatable_fields, function(index, fieldClass) {
            let elem = tbl_body.find("tr:last").find('.' + fieldClass)

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

    }

    //Row with Prepopulated Data for Editing

    function insertRow_to_edit_voucher(response_is_contra = false, actionCellVal, quantityCellVal, descriptionCellVal, unitCostCellVal, totalCostCellVal, allocationCodeCellVal, accountCellValID, accountCellValName, incomeId) {
        var tbl_body = $("#tbl_voucher_body tbody");
        var tbl_head = $("#tbl_voucher_body thead");

        var cell = actionCell(actionCellVal, true);
        cell += quantityCell(quantityCellVal, true);
        cell += descriptionCell(descriptionCellVal, true);
        cell += unitCostCell(unitCostCellVal, true);
        cell += totalCostCell(totalCostCellVal, true);

        if (toggle_accounts_by_allocation) {
            cell += allocationCodeCell(allocationCodeCellVal, true, incomeId);
            cell += accountCell(accountCellValID, true, accountCellValName);
        } else {
            cell += accountCell(accountCellValID, true, accountCellValName);
            cell += allocationCodeCell(allocationCodeCellVal, true, incomeId);
        }

        tbl_body.append("<tr>" + cell + "</tr>");
    }
    //Normal insert Without Editing data
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
            //alert($(elem).val());
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
        const office_id = $("#office").val();
        const transaction_date = $("#transaction_date").val();
        const voucher_type_id = $("#voucher_type").val(); // Can be expense, income, cash_contra or bank_contra
        const office_bank_id = !$("#bank").attr('disabled') ? $("#bank").val() : 0;
        const extra_data = {
            'office_bank_id': office_bank_id,
            'bank_refund_from': $('#bank_refund').length > 0 && $('#bank_refund').val() > 0 ? $('#bank_refund').val() : 0
        };
        const url = "<?= base_url(); ?>ajax/voucher/getVoucherAccountsAndAllocation/" + office_id + "/" + voucher_type_id + "/" + transaction_date + "/" + office_bank_id;


        $.ajax({
            url: url,
            type: "POST",
            data: extra_data,
            beforeSend: function() {

            },
            success: function(response_objects) {

                let account_select_option = "<option value=''>Select an account</option>";
                let allocation_select_option = "<option value=''>Select an allocation code</option>";
                let response_allocation = response_objects['project_allocation'];
                let response_is_contra = response_objects['is_contra'];
                //alert(response);
                insertRow(response_is_contra);
                create_allocation_select_options(response_allocation);
            },
            error: function() {
                alert('Error occurred');
            }
        });
    }


    function removeAllRows(existing_voucher_effect, new_voucher_effect) {

        //Populate the voucher details when the voucher effect is different e.g. income!=expense
        $('.action').each(function(i, e) {
            let voucher_detail_id = $(this).data('voucher_detail_id');
            let row = $(this).closest('tr');

            //Build the array with the voucher details
            if (existing_voucher_effect != new_voucher_effect) {
                storeVoucherIDsToDeleteOnChangeEffect.push(voucher_detail_id);
            }

            //Remove the row and update the Total cost.
            row.remove();
            updateTotalCost();

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

            //Delete the record
            let voucher_detail_id = $(rowCellButton).data('voucher_detail_id');

            let data = {
                'voucher_detail_id': voucher_detail_id,
            }

            storeVoucherDetailsIdsToDelete.push(data.voucher_detail_id);


        } else {
            alert('<?= get_phrase('you_cant_remove_all', "You can\'t remove all rows"); ?>');

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

        var office_id = $("#office").val();
        var account_id = $(this).val();
        var voucher_type_id = parseInt($("#voucher_type").val());
        var transaction_date = $("#transaction_date").val();

        var url = "<?= base_url(); ?>voucher/get_project_details_account/";

        var office_bank_id = !$("#bank").attr('disabled') ? $("#bank").val() : 0;

        //var toggle_accounts_by_allocation = '<?= $config->toggle_accounts_by_allocation; ?>';

        if (typeof(voucher_type_id) != "number" || voucher_type_id === 0) {
            return false;
        }

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
                success: function(response) {

                    var response_allocation = JSON.parse(response);

                    var allocation_select_option = "<option value=''>Select an allocation code</option>";

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

    //refresh store_income_account
    $(document).on('change', '.account', function() {

        let account_id_el = $(this).prev('input').attr('id');

        $('#' + account_id_el).val($(this).val());

    });

    $(document).on('change', '.allocation', function() {


        var el = $(this);
        var office_id = $("#office").val();
        var allocation_id = $(el).val();
        var voucher_type_id = parseInt($("#voucher_type").val());
        var transaction_date = $("#transaction_date").val();
        var office_bank_id = !$("#bank").attr('disabled') ? $("#bank").val() : 0;
        var row = $(el).closest('tr');

        if (typeof(voucher_type_id) != "number" || voucher_type_id === 0) {
            return false;
        }

        //Locate the input element with income account value [Enhanced by Onduso]
        let account_id_el = $(this).prev('input').attr('id');

        let account_id = $(this).prev('input.income_account').attr('value');

        let url_income_id = "<?= base_url(); ?>ajax/voucher/getProjectAllocationIncomeAccount/" + allocation_id;

        $.get(url_income_id, function(res) {

            //Find the input elem to update the value
            $('#' + account_id_el).val(parseInt(res));

            //Find the input a newly inserted row and update the value.
            $(el).prev('input.income_account').val(res);
        });

        //End of enhancement

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
                // console.log(response_accounts);
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

    //store_income_account_id
    $(document).on('change', '.account', function() {

        let expense_or_income_id = $(this);


        //let office_bank_account_id=0

        //let office_bank_account_id=parseInt($('#bank').val());

        let voucher_type_id = $('#voucher_type').val();

        let url = "<?= base_url() ?>ajax/voucher/getExpenceAccountIncome/" + expense_or_income_id.val() + '/' + voucher_type_id;


        $.get(url, function(response) {
            //If expenses get changed then store_income_account_id with income account value
            expense_or_income_id.closest('td').siblings().find('input.income_account').attr('value', response);
        });

    });


    function populate_other_active_expense_accounts(project_id, expense_id, income_account_id, contra_id, voucher_type_id) {

        var url = "<?= base_url(); ?>ajax/voucher/getActiveProjectExpensesAccounts/" + project_id + "/" + voucher_type_id;

        if (expense_id > 0) {

            populate_account_dropdown(url, expense_id);

        } else if (contra_id == 0) {
            //populate other incomes e.g R330
            populate_account_dropdown(url, income_account_id);

        } else {
            //Contra code
        }

    }

    //Populate accounts
    function populate_account_dropdown(url, account_id) {

        $.get(url, function(accounts) {

            var option = ''
            // accounts = JSON.parse(res);
            //console.log(res);

            $.each(accounts, function(i, e) {

                //console.log(e);
                if (account_id != i) {
                    option += "<option value='" + i + "'>" + e + "</option>";
                };

            });
            //Append other expenses on the select
            $('#id_' + account_id).append(option);


        });

    }

    function actionCell(value = '', edit_voucher = false) {

        if (edit_voucher) {

            return "<td><div class='btn btn-danger action '  data-voucher_detail_id='" + value + "' onclick='removeRow(this);'>Remove Row</div> <input class='hidden hold_voucher_detail_id' name='hold_voucher_detail_id[]'  type='text' class='form-control required body-input number-fields hold_voucher_detail_id' value='" + value + "' />&nbsp; <span onclick='clearRow(this);' class='fa fa-trash'></span> </td>";
        }
        return "<td><div class='btn btn-danger action' onclick='removeRow(this);'>Remove Row</div> &nbsp; <span onclick='clearRow(this);' class='fa fa-trash'></span> </td>";
    }



    function quantityCell(value = 1, edit_voucher = false) {
        if (edit_voucher) {
            return "<td><input name='voucher_detail_quantity[]'  type='text' class='form-control required body-input number-fields quantity' onclick='replaceValue(this);' onchange='computeTotalCost(this);' value='" + value + "' /></td>";
        }
        return "<td><input name='voucher_detail_quantity[]'  type='text' class='form-control required body-input number-fields quantity' onclick='replaceValue(this);' onchange='computeTotalCost(this);' value='" + value + "' /></td>";
    }

    function descriptionCell(value = '', edit_voucher = false) {

        if (edit_voucher) {
            return "<td><input  name='voucher_detail_description[]' type='text' class='form-control required body-input description' value='" + value + "' autocomplete='off' /></td>";
        }
        return "<td><input  name='voucher_detail_description[]' type='text' class='form-control required body-input description' value='" + value + "' autocomplete='off' /></td>";
    }

    function unitCostCell(value = '', edit_voucher = false) {
        if (edit_voucher) {
            return "<td><input  name='voucher_detail_unit_cost[]' type='text' class='form-control required body-input number-fields unitcost' onclick='replaceValue(this);'  onchange='computeTotalCost(this);'  value='" + parseFloat(value).toFixed(2) + "' /></td>";
        }
        return "<td><input  name='voucher_detail_unit_cost[]' type='text' class='form-control required body-input number-fields unitcost' onclick='replaceValue(this);'  onchange='computeTotalCost(this);'  value='" + parseFloat(value).toFixed(2) + "' /></td>";
    }

    function totalCostCell(value = 0, edit_voucher = false) {
        return "<td><input name='voucher_detail_total_cost[]' type='text' class='form-control required body-input number-fields totalcost' value='" + parseFloat(value).toFixed(2) + "' readonly='readonly'/></td>";
    }

    function accountCell(value = 0, edit_voucher = false, account_name = '') {

        if (edit_voucher) {
            if (toggle_accounts_by_allocation) {

                return "<td><select name='voucher_detail_account[]' class='form-control required body-input account' id='id_" + value + "'><option value='" + value + "'>" + account_name + "</option></select></td>";
            } else {
                return "<td><select name='voucher_detail_account[]' class='form-control required body-input account' id='id_" + value + "'><option value='" + value + "'>" + account_name + "</option></select></td>";
            }
        }



        if (toggle_accounts_by_allocation) {
            return "<td><select disabled='disabled' name='voucher_detail_account[]' class='form-control  required body-input account' ></select></td>";
        } else {
            return "<td><select name='voucher_detail_account[]' class='form-control  required body-input account' ></select></td>";
        }


    }

    function allocationCodeCell(value = 0, edit_voucher = false, incomeId = 0) {

        if (edit_voucher) {

            if (toggle_accounts_by_allocation) {
                return "<td><input name='store_income_account_id[]' class='form-control hidden required body-input income_account ' id='account_id_" + incomeId + "' value='" + incomeId + "'/><select name='fk_project_allocation_id[]' class='form-control required body-input allocation'></select></td>";
            } else {
                return "<td><input name='store_income_account_id[]' class='form-control  hidden required body-input income_account' id='account_id_" + incomeId + "' value='" + incomeId + "'/><select disabled='disabled' name='fk_project_allocation_id[]' class='form-control required body-input allocation'></select></td>";
            }
        }


        if (toggle_accounts_by_allocation) {
            return "<td><input name='store_income_account_id[]' class='form-control hidden required body-input income_account' id='account_id_" + incomeId + "' value='" + incomeId + "'/><select name='fk_project_allocation_id[]' class='form-control required body-input allocation'></select></td>";

        } else {
            return "<td><select disabled='disabled' name='fk_project_allocation_id[]' class='form-control required body-input allocation'></select></td>";
        }


    }


    function requestIdCell(value = 0, edit_voucher = false) {
        if (edit_voucher) {

            return "<td><input name='fk_request_detail_id[]' type='number' class='form-control body-input number-fields request_number' value='" + value + "' readonly='readonly'/></td>";
        }
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

    //Delete voucher details that was removed from vocuher details

    function delete_voucher_detail_record() {

        //if effect is different add items of storeVoucherIDsToDeleteOnChangeEffect to storeVoucherDetailsIdsToDelete

        for (let x in storeVoucherIDsToDeleteOnChangeEffect) {
            storeVoucherDetailsIdsToDelete.push(storeVoucherIDsToDeleteOnChangeEffect[x]);
        }



        if (storeVoucherDetailsIdsToDelete.length > 0) {

            for (let index in storeVoucherDetailsIdsToDelete) {

                let voucherDetailID = storeVoucherDetailsIdsToDelete[index];

                let data = {
                    'voucher_detail_id': voucherDetailID,
                }

                let url = "<?= base_url() ?>voucher/delete_voucher_detail_record";

                $.post(url, data, function(res) {
                    //alert('Date Deleted');
                    //console.log(data);
                });
            }
        }

        //console.log(storeVoucherDetailsIdsToDelete);

    }

    function save_voucher_changes(clicked_btn) {


        //Check if the cheque_number_is_selected
        let cheque_number = $('#cheque_number').val();


        let chq_number_held_for_edit = $('#hold_cheque_number_for_edit').val();

        let eft_number_held_for_edit = $('#cheque_number').val();

        let held_chq_or_eft_number = '';

        if (eft_number_held_for_edit != '') {
            held_chq_or_eft_number = eft_number_held_for_edit;
        } else if (chq_number_held_for_edit != '') {
            held_chq_or_eft_number = chq_number_held_for_edit;
        }

        if (cheque_number != '') {

            var office_id = $('#office').val();

            let office_bank_id = $("#bank").val();

            var url = "<?= base_url(); ?>ajax/voucher/getDuplicateChequesForAnOffice/" + office_id + '/' + cheque_number + '/' + office_bank_id + '/' + held_chq_or_eft_number;

            $.get(url, function(obj) {

                const response = obj.check

                if (response == 0) {
                    edit_voucher_transaction(clicked_btn);
                    //Delete voucher details that was removed from vocuher details
                    //delete_voucher_detail_record();
                } else {
                    alert('<?= get_phrase('chq_alreaady_used', 'Select cheque numbers already used. Select another cheque number.'); ?>');

                    //Repulated/referesh the cheque numbers if already used to avoid duplicate
                    check_cheque_validity();

                    $('#cheque_number').css('border', '1px solid red');

                    return false;
                }

            });


        } else {

            // console.log(storeVoucherDetailsIdsToDelete);

            edit_voucher_transaction(clicked_btn);

        }
    }

    function edit_voucher_transaction(clicked_btn) {

        let voucher_id = '<?= hash_id($id, 'decode') ?>';
        let url = "<?= base_url(); ?>ajax/voucher/editVoucher/" + voucher_id;
        let data = $("#frm_voucher").serializeArray();
        let tbl_body_rows = $("#tbl_voucher_body tbody tr");
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

        $.post(limit_check_url, limit_data, function(obj) {

            const limit_exceeded = obj.cash_limit_exceeded

            if (limit_exceeded == 1) {
                alert('<?= get_phrase('cash_limit_exceeded', "You have exceeded the bank or cash balance"); ?>');
                return false;
            }


            if (tbl_body_rows.length == 0) {
                alert('<?= get_phrase('add_voucher_details', "Please add voucher details before saving the voucher"); ?>');
                return false;
            }

            // Make all select or inputs in hidden span be disabled
            disable_elements_in_hidden_span();

            if (!check_required_fields()) {
                alert('<?= get_phrase('empty_fields', 'Empty required fields exists or "unit cost and cost fields have amount with more than 2 decimal places"'); ?>');
            } else {

                $.ajax({
                    url: url,
                    type: "POST",
                    data: data,
                    success: function(response) {
                        //alert(response);
                        //console.log(response);
                        //console.log(storeVoucherDetailsIdsToDelete);

                        if (parseInt(response) == 1) {


                            //Delete voucher details that was removed from vocuher details
                            delete_voucher_detail_record();

                            const referrer = document.referrer;

                            if (referrer.indexOf("book") > 0) {


                                //window.location.href = '<?= base_url(); ?>voucher/view/' + '<?= hash_id($id, 'decode') ?>';

                            } else {

                                //Redirect to view page
                                let editUrl = window.location.pathname;

                                let urlChunks = editUrl.split('/');

                                let vourcherDetailId = urlChunks.pop();

                                console.log(vourcherDetailId);
                                alert('<?= get_phrase('voucher_updated_successful', "Voucher Updated successfully."); ?>');


                                window.location.href = '<?= base_url(); ?>voucher/view/' + vourcherDetailId;
                            }
                        } else if (parseInt(response) == 0) {

                            alert('<?= get_phrase('voucher_update_failed', "Voucher Update failed"); ?>');
                        }

                    }

                });
            }

        })

    }


    //Save changes
    $(".btn-save").on('click', function() {
        save_voucher_changes($(this));
    });

    //Cancel Changes
    $('.btn-cancel').on('click', function() {
        location.href = document.referrer;
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

            if ($(el).hasClass('quantity') && ($(el).val() == "" || $(el).val() == 0 || $(el).val() < 1 || isNaN($(el).val().replace(/\,/g, '')))) {
                return_flag = false;
                $(el).css('border', '1px red solid');
            }
            //Old Code
            // if ($(el).hasClass('unitcost') && ($(el).val() == "" || $(el).val() == 0 || $(el).val().replace(/\,/g, '') < 0 || isNaN($(el).val().replace(/\,/g, ''))) || String($(el).val()).split(".")[1]?.length > 2) {

            //     return_flag = false;
            //     $(el).css('border', '1px red solid');
            // }

            /*Code added by Onduso on 19th Feb 2025
              Resolving Defect: DE4181: Fix address and vendor in the voucher form to accept commas and decimals
            */

            if ($(el).hasClass('unitcost') && ($(el).val() == "" || $(el).val() == 0 || $(el).val().replace(/\,/g, '') < 0 || isNaN($(el).val().replace(/\,/g, ''))) ) {

                return_flag = false;
                $(el).css('border', '1px red solid');
            }

            //End of addition


        });

        return return_flag;
    }

    $(document).on('change', '.required', function() {
        if ($(this).attr('style')) {
            $(this).removeAttr('style');
        }
    });


    //Gray out the fields if voucher_type_name ='$voucher_header_info["voucher_type_name"]'

    $(document).ready(function() {

        let voucherType = '<?= $voucher_header_info["voucher_type_name"]; ?>';

    
        if (voucherType.trim() == 'Voided Cheque') {
            //alert(voucherType);

            $('#office').prop('disabled', 'disabled');
            $('#transaction_date').prop('disabled', 'disabled');

            $('#voucher_type').prop('disabled', 'disabled');
            $('#bank').prop('disabled', 'disabled');
            $('#cheque_number').prop('disabled', 'disabled');
            $('#voucher_vendor').prop('disabled', 'disabled');
            $('#voucher_vendor_address').prop('disabled', 'disabled');
            $('#voucher_description').prop('disabled', 'disabled');
            $('.btn-insert').hide();
            $('.btn-save').hide();

        }
    });

    //Return the Voided check to the pool.
    $(document).on('click', '#revert_cheque', function(event) {
        let message = "<?= get_phrase('confirm_void_msg_info', 'Are you sure you want to revert cheque??'); ?>";
        if (confirm(message) == true) {
            let voucher_id = '<?= hash_id($id, 'decode') ?>';
            //Delete voucher record of the voided chq in voucher and voucher_details and cancel_cheque tables
            let url = '<?= base_url(); ?>ajax/voucher/revertCancelledChequeAndRelatedVoucher';
            let data = {
                voucher_id
            }

            $.post(url, data, function(res) {
                let status = parseInt(res.save_status);

                message = "<?= get_phrase('revert_chq_msg_info', 'Cheque number reverted'); ?>";

                if (status == 1) {

                    $('#message_id').css('color', 'green');

                    $('#message_id').html(message);

                    window.location.replace('<?= base_url(); ?>voucher/list/');
                } else {

                    message = "<?= get_phrase('revert_chq_msg_error', 'Cheque number NOT reverted'); ?>";

                    $('#message_id').css('color', 'red');

                    $('#message_id').html(message);

                    return false;
                }
            });
        } else {

            window.location.replace('<?= base_url(); ?>voucher/list');
        }

        event.preventDefault();
    });
</script>

<input type="text" class="form-control hidden" name="secondary_input" id="secondary_input" value="" />
<select class='form-control hidden' name='secondary_select' id='secondary_select' disabled='disabled'>
    <option value=''><?= get_phrase('select_cheque_number'); ?></option>
</select>