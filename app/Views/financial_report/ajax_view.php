<?php
$userLibrary = new \App\Libraries\Core\UserLibrary();
?>
<div id="voucher_print">
    <div class="row">
        <div class='col-xs-12 header'><?= get_phrase('fund_balance_report'); ?></div>
        <div class="col-xs-12" id="fund_balance_report">
            <?php include "includes/include_fund_balance_report.php"; ?>
        </div>
    </div>

    <div class='row'>
        <div class='col-xs-12 header'><?= get_phrase('projects_balance_report'); ?></div>
        <div class='col-xs-12'>
            <?php include "includes/include_project_balance_report.php"; ?>
        </div>
    </div>

    <div class='row'>
        <div class='col-xs-12 header'><?= get_phrase('support_grants_financial_ratios'); ?></div>
        <div class='col-xs-12'>
            <?php include "includes/include_financial_ratios.php"; ?>
        </div>
    </div>


    <?php if (!empty($funds_transfers)) { ?>
        <div class='row'>
            <div class="col-xs-12">
                <div class="col-xs-12 header"><?= get_phrase('funds_transfers'); ?></div>
                <?php include "includes/include_funds_transfers.php"; ?>
            </div>
        </div>
    <?php } ?>

    <?php if (!empty($accrualIsActivated)) { ?>
        <div class='row'>
            <div class="col-xs-12">
                <div class="col-xs-12 header"><?= get_phrase('accrued_balances'); ?></div>
                <?php include "includes/include_accrued_balances.php"; ?>
            </div>
        </div>
    <?php } ?>

    <div class="row">
        <div class="col-xs-12 header"><?= get_phrase('proof_of_cash'); ?></div>
        <div class="col-xs-6" id="proof_of_cash">
            <?php include "includes/include_proof_of_cash.php"; ?>
        </div>


    </div>

    <div class="row">
        <div class="col-xs-6">
            <div class="col-xs-12 header"><?= get_phrase('bank_reconciliation'); ?></div>
            <?php include "includes/include_bank_reconciliation.php"; ?>
        </div>

        <div class="col-xs-6">
            <div class="col-xs-12 header"><?= get_phrase('bank_statements'); ?></div>
            <?php include "includes/include_bank_statements.php"; ?>
        </div>
    </div>

    <div class="row">
        <div class="col-xs-6">
            <div class="col-xs-12 header"><?= get_phrase('uncleared_cheques_/or_efts'); ?></div>
            <?php include "includes/include_outstanding_cheques.php"; ?>
        </div>

        <div class="col-xs-6">
            <div class="col-xs-12 header"><?= get_phrase('cleared_cheques_/or_efts'); ?></div>
            <?php include "includes/include_cleared_outstanding_cheques.php"; ?>
        </div>

    </div>


    <div class="row">
        <div class="col-xs-6">
            <div class="col-xs-12 header"><?= get_phrase('deposit_in_transit'); ?></div>
            <?php include "includes/include_deposit_in_transit.php"; ?>
        </div>

        <div class="col-xs-6">
            <div class="col-xs-12 header"><?= get_phrase('deposit_in_transit_cleared_effects'); ?></div>
            <?php include "includes/include_cleared_deposit_in_transit.php"; ?>
        </div>

    </div>

    <div class="row">
        <div class="col-xs-12">
            <div class="col-xs-12 header"><?= get_phrase('expense_report'); ?></div>
            <?php include "includes/include_expense_report.php"; ?>
        </div>
    </div>

</div>

<hr />
<?php //if(!$multiple_offices_report && $multiple_projects_report && !$financial_report_submitted) 
if (!$financial_report_submitted && $userLibrary->checkRoleHasPermissions('financial_report', 'update')) {
?>
    <div class="row">
        <div class="col-xs-12" style="text-align:center;">
            <div class='btn btn-default' id="submit_report"><?= get_phrase('submit'); ?></div>
        </div>
    </div>
<?php } ?>

<script>
    $(document).on('click', "#submit_report", function(ev) {

        var url = "<?= base_url(); ?>ajax/financial_report/submitFinancialReport";

        var data = {
            'office_id': <?= $office_ids[0]; ?>,
            'reporting_month': '<?= $reporting_month; ?>',
            'financial_report_id': '<?= $report_id; ?>'
        };

        postRequest(url, data, function(response) {
            if (response) {
                if (response != 1) {
                    alert(response);
                } else {
                    alert('<?= get_phrase('financial_report_submission_success', 'MFR Submitted Successful'); ?>');
                    location.href = document.referrer;
                }
            } else {
                alert(response);
            }
        });

        // $.post({
        //     url: url,
        //     data: data,
        //     beforeSend: function() {
        //         $("#overlay").css("display", "block");
        //     }
        // }).done(function(response) {
        //     if (response) {
        //         if (response != 1) {
        //             alert(response);
        //         } else {
        //             alert('<?= get_phrase('financial_report_submission_success', 'MFR Submitted Successful'); ?>');
        //             location.href = document.referrer;
        //         }
        //     } else {
        //         alert(response);
        //     }
        // }).fail(function(xhr, status, error) {
        //     alert('Error has occurred');
        // }).always(function() {
        //     $("#overlay").css("display", "none");
        // });

        // $.post(url, data, function(response) {
        //     if (response) {

        //         if (response != 1) {
        //             alert(response);
        //         } else {
        //             alert('<?= get_phrase('financial_report_submission_success', 'MFR Submitted Successful'); ?>');
        //             location.href = document.referrer;

        //         }

        //     } else {
        //         alert(response);
        //     }
        // });

        ev.preventDefault();
    });

    $(document).ready(function() {

        // $(".total_oc").append(" <span class='label label-success'>2</span>");
        // $(".total_dt").append(" <span class='label label-danger'>3</span>");
        // $(".code_proof_of_cash").append(" <span class='label label-info'>1</span>");


        if ('<?= $financial_report_submitted ?>' == 1) {
            $("#bank_statement_balance").prop('disabled', 'disabled');
            $(".clear_btn").addClass('disabled');
            $(".delete_statement").removeClass('delete_statement');
        }

        compute_fund_balance_totals()

    });

    function compute_fund_balance_totals() {
        $('#fund_balance_table tbody tr').each(function(i, el) {
            let opening_balance = parseFloat($(el).find('.fund_month_opening_balance').html().split(',').join(""));
            let month_income = parseFloat($(el).find('.fund_month_income').html().split(',').join(""));
            let month_expense = parseFloat($(el).find('.fund_month_expense').html().split(',').join(""));
            let closing_opening_balance = (opening_balance + month_income) - month_expense;

            $(this).find('.fund_month_closing_balance').html(accounting.formatNumber(closing_opening_balance, 2));
        });

        let sum_opening_balance = parseFloat($('#total_fund_month_opening_balance').html().split(',').join(""));
        let sum_month_income = parseFloat($('#total_fund_month_income').html().split(',').join(""));
        let sum_month_expense = parseFloat($('#total_fund_month_expense').html().split(',').join(""));

        let total_fund = accounting.formatNumber((sum_opening_balance + sum_month_income - sum_month_expense), 2);

        $("#total_fund_month_closing_balance").html(total_fund);

        // alert(total_fund)

        $(".row_total, .row_header").css('font-weight', 'bold');
    }

    function compute_reconciliation(clear_btn) {
        var td_effects_total = clear_btn.closest('table').find('td.td_effects_total');
        var td_row_amount = clear_btn.closest('tr').find('td.td_row_amount');
        var table_id = clear_btn.closest('table').attr('id');

        var drop_table_id = '';
        var effect_to_balance = 'negative';

        if (table_id == 'tbl_transit_deposit') {

            drop_table_id = 'tbl_cleared_transit_deposit';

        } else if (table_id == 'tbl_cleared_transit_deposit') {

            drop_table_id = 'tbl_transit_deposit';
            effect_to_balance = 'positive';

        } else if (table_id == 'tbl_outstanding_cheque') {

            drop_table_id = 'tbl_cleared_outstanding_cheque';

        } else if (table_id == 'tbl_cleared_outstanding_cheque') {

            drop_table_id = 'tbl_outstanding_cheque';
            effect_to_balance = 'positive';

        }



        var effects_total = td_effects_total.html().split(',').join("");

        var row_amount = td_row_amount.html().split(',').join("");

        var td_drop_table_total = $("#" + drop_table_id).find('td.td_effects_total');
        var drop_table_total = td_drop_table_total.html().split(',').join("");

        //Check if destination table is a number then compute the amount
        if (Number(drop_table_total) || parseFloat(drop_table_total) == 0.00) {
            drop_table_total = drop_table_total + parseFloat(row_amount);
        } else {
            drop_table_total = 0.00;
        }

        var origin_table_balance = parseFloat(effects_total) - parseFloat(row_amount);
        var drop_table_balance = parseFloat(drop_table_total) + parseFloat(row_amount);

        td_effects_total.html(accounting.formatNumber(origin_table_balance, 2));
        td_drop_table_total.html(accounting.formatNumber(drop_table_balance, 2));

        var td_deposit_in_transit = $("#td_deposit_in_transit");
        var td_oustanding_cheques = $("#td_oustanding_cheques");
        var reconciled_bank_balance = $("#reconciled_bank_balance");

        if (table_id == 'tbl_transit_deposit') {
            td_deposit_in_transit.html(accounting.formatNumber(origin_table_balance, 2));
        } else if (table_id == 'tbl_cleared_transit_deposit') {
            td_deposit_in_transit.html(accounting.formatNumber(drop_table_balance, 2));
        } else if (table_id == 'tbl_outstanding_cheque') {
            td_oustanding_cheques.html(accounting.formatNumber(origin_table_balance, 2));
        } else if (table_id == 'tbl_cleared_outstanding_cheque') {
            td_oustanding_cheques.html(accounting.formatNumber(drop_table_balance, 2));
        }

        var td_bank_reconciliation_balance = $("#td_bank_reconciliation_balance");
        var bank_reconciliation_balance = 0;

        if (td_bank_reconciliation_balance.find('input').length > 0) {
            bank_reconciliation_balance = td_bank_reconciliation_balance.find('input').val().split(',').join("");
        } else {
            bank_reconciliation_balance = td_bank_reconciliation_balance.html().split(',').join("");
        }

        var reconciled_bank_balance = parseFloat(bank_reconciliation_balance) + parseFloat(td_deposit_in_transit.html().split(',').join("")) - parseFloat(td_oustanding_cheques.html().split(',').join(""));

        $("#reconciled_bank_balance").html(accounting.formatNumber(reconciled_bank_balance, 2));

        var td_book_closing_balance = $("#td_book_closing_balance");

        var book_closing_balance = td_book_closing_balance.html().split(',').join("");

        //If cancelling increase the closing balance otherwise subtract it
        if (clear_btn.hasClass('cancel_btn') && clear_btn.hasClass('active_effect')) {
            book_closing_balance = parseFloat(book_closing_balance) + parseFloat(row_amount);
        } else if (clear_btn.hasClass('cancel_btn') && clear_btn.hasClass('cleared_effect')) {
            book_closing_balance = parseFloat(book_closing_balance) - parseFloat(row_amount);
        }

        $("#td_book_closing_balance").html(accounting.formatNumber(book_closing_balance, 2));

        if (parseFloat(book_closing_balance).toFixed(2) === parseFloat(reconciled_bank_balance).toFixed(2)) {
            if ($("#reconciliation_flag").hasClass('label-danger')) {
                $("#reconciliation_flag").removeClass('label-danger');
                $("#reconciliation_flag").addClass('label-success');
                $("#reconciliation_flag").html('<?= get_phrase('balanced'); ?>');
            }
        } else {
            if ($("#reconciliation_flag").hasClass('label-success')) {
                $("#reconciliation_flag").removeClass('label-success');
                $("#reconciliation_flag").addClass('label-danger');
                $("#reconciliation_flag").html('<?= get_phrase('not_balanced'); ?>');
            }
        }
    }

    function cancel_bounced_openning_cheque(btn) {

        var id = btn.attr('id');
        var rebuild_data_attribute = '';
        var opening_outstanding_bouched_chq_id = btn.data('opening_outstanding_cheque_id');
        var from_class = "active_effect";
        var to_class = "cleared_effect";
        var current_table = btn.closest('table');
        var connector_table = current_table.attr('id') + "_connector";
        var from_color = 'danger';
        var to_color = 'success';
        btn.attr('data-opening_outstanding_cheque_id', rebuild_data_attribute);
        var to_label = "<?= get_phrase('undo'); ?>";

        if (btn.hasClass('cleared_effect') && btn.hasClass('cancel_btn')) {
            from_class = 'cleared_effect';
            to_class = "active_effect";
            from_color = 'success';
            to_color = 'danger';

            //Append _bounce when number or when not a number, split to get the number then append
            if (Number(opening_outstanding_bouched_chq_id)) {

                rebuild_data_attribute = opening_outstanding_bouched_chq_id + '_bounce';
            } else {

                let get_chq_id_number = opening_outstanding_bouched_chq_id.split('_')[0];

                rebuild_data_attribute = get_chq_id_number + '_bounce';
            }

            btn.attr('data-opening_outstanding_cheque_id', rebuild_data_attribute);

            to_label = "<?= get_phrase('cancel'); ?>";
        }

        var reporting_month = '<?= $reporting_month; ?>';
        var office_bank_id = $('#office_bank_ids').val()

        //Get the chq ID and the bounce marker and split them to get the chq ID and the mark
        var opening_outstanding_chq_id_and_bounce_maker = btn.data('opening_outstanding_cheque_id');

        if (Number(opening_outstanding_chq_id_and_bounce_maker)) {
            opening_outstanding_chq_id_and_bounce_maker = opening_outstanding_chq_id_and_bounce_maker + '_unbounce';
        }
        var splitted_marker_and_chq_id = opening_outstanding_chq_id_and_bounce_maker.split('_');
        var chq_id = splitted_marker_and_chq_id[0];
        var bounce_or_unbounce_marker = splitted_marker_and_chq_id[1];

        var message = 'You are about to cancel an oustanding openning cheque. Click "OK" to proceed';

        //Modify the URL with correct boalen based on the marker. If bounce marker=bounce turn the flag true othersise false
        var url = "<?= base_url(); ?>financial_report/update_bank_support_funds_and_oustanding_cheque_opening_balances/" + office_bank_id + "/" + chq_id + "/" + reporting_month + '/' + 1;

        if (bounce_or_unbounce_marker != 'bounce') {
            message = 'You are about to undo the cancelled outstanding cheque';
            url = "<?= base_url(); ?>financial_report/update_bank_support_funds_and_oustanding_cheque_opening_balances/" + office_bank_id + "/" + chq_id + "/" + reporting_month + '/' + 0;
        }

        if (confirm(message) == true) {

            $.get(url, function(response) {

                if (response == 1) {

                    var cloned_tr = btn.closest('tr').clone();
                    var action_div = cloned_tr.find(':first-child').find('div');

                    //Remove the cancel button when canncellling bounced chq and return the clear button when undoing the chq
                    if (to_class == 'cleared_effect') {
                        action_div.remove('div.cancel_btn');

                    } else if (to_class == 'active_effect') {

                        let return_clear_div_with_clear_btn = '<div data-data-opening_deposit_transit_id="0" data-opening_outstanding_cheque_id="' + opening_outstanding_bouched_chq_id + '" id="0" class="btn btn-danger  clear_btn to_clear outstanding_cheque active_effect state_0" >Clear </div>'

                        cloned_tr.find(':first-child').prepend(return_clear_div_with_clear_btn);
                    }

                    btn.closest('tr').remove();

                    action_div.removeClass(from_class).removeClass('btn-' + from_color).addClass(to_class).addClass('btn-' + to_color).addClass('cancel_btn').html(to_label);

                    if (action_div.hasClass('state_0')) {
                        action_div.removeClass('state_0').addClass('state_1');
                    } else {
                        action_div.removeClass('state_1').addClass('state_0');
                    }

                    $("." + connector_table + " tbody").append(cloned_tr);

                }
                reload_fund_balance_report()
                reload_proof_of_cash()
            });
            //Recompute the reconciliation
            compute_reconciliation(btn);
        } else {

            return false;
        }
    }

    function reload_fund_balance_report() {
        let url = "<?= base_url(); ?>financial_report/fund_balance_report";
        let data = {
            office_id: $('#office_ids').val(),
            reporting_month: '<?= $reporting_month; ?>',
            project_ids: $("#project_ids").val() ? $("#project_ids").val() : '',
            office_bank_ids: $("#office_bank_ids").val() ? $("#office_bank_ids").val() : ''
        }

        $.post(url, data, function(response) {
            //alert(response)
            $("#fund_balance_report").html(response)

            compute_fund_balance_totals()
        })
    }

    function reload_proof_of_cash() {
        let url = "<?= base_url(); ?>financial_report/proof_of_cash";
        let data = {
            office_id: $('#office_ids').val(),
            reporting_month: '<?= $reporting_month; ?>',
            project_ids: $("#project_ids").val() ? $("#project_ids").val() : '',
            office_bank_ids: $("#office_bank_ids").val() ? $("#office_bank_ids").val() : ''
        }

        $.post(url, data, function(response) {
            //alert(response)
            // compute_fund_balance_totals()
            $("#proof_of_cash").html(response)
            compute_proof_of_cash()

        })
    }

    function clear_effect(btn) {
        // var btn = $(this);
        var id = btn.attr('id');
        var url = "<?= base_url(); ?>ajax/financial_report/clearTransactions";
        var voucher_state = btn.hasClass('state_0') ? 0 : 1; //$(this).attr('data-state');
        var opening_outstanding_cheque_id = btn.data('opening_outstanding_cheque_id');
        var opening_deposit_transit_id = btn.data('opening_deposit_transit_id');
        var data = {
            'voucher_id': id,
            'is_outstanding_cheque': btn.hasClass('outstanding_cheque'),
            'voucher_state': voucher_state,
            'reporting_month': '<?= $reporting_month; ?>',
            'opening_outstanding_cheque_id': opening_outstanding_cheque_id,
            'opening_deposit_transit_id': opening_deposit_transit_id
        };
        var from_class = "active_effect";
        var to_class = "cleared_effect";
        var current_table = btn.closest('table');
        var connector_table = current_table.attr('id') + "_connector";
        var from_color = 'danger';
        var to_color = 'success';
        var to_label = "<?= get_phrase('unclear'); ?>";

        if (btn.hasClass('cleared_effect')) {
            from_class = 'cleared_effect';
            to_class = "active_effect";
            from_color = 'success';
            to_color = 'danger';
            to_label = "<?= get_phrase('clear'); ?>";
        }

        $.ajax({
            url: url,
            data: data,
            type: "POST",
            success: function(response) {
                //console.log(response);
                if (response) {

                    //Clone the tr then remove the tr
                    var cloned_tr = btn.closest('tr').clone();

                    var action_div = cloned_tr.find(':first-child').find('div');


                    if (to_class == 'cleared_effect' && id == 0) {
                        action_div.remove('div.cancel_btn');

                    } else if (to_class == 'active_effect' && id == 0) {

                        let return_clear_div_with_clear_btn = '<div data-data-opening_deposit_transit_id="0" data-opening_outstanding_cheque_id="' + opening_outstanding_cheque_id + '_bounce" id="0" class="btn btn-danger  cancel_btn to_clear outstanding_cheque active_effect state_0">Cancel</div>'

                        cloned_tr.find(':first-child').append(return_clear_div_with_clear_btn);
                    }


                    btn.closest('tr').remove();

                    //Reconstruct the tr by adding the cloned with label you want
                    action_div.removeClass(from_class).removeClass('btn-' + from_color).addClass(to_class).addClass('btn-' + to_color).html(to_label);

                    if (action_div.hasClass('state_0')) {
                        action_div.removeClass('state_0').addClass('state_1');
                    } else {
                        action_div.removeClass('state_1').addClass('state_0');

                    }

                    $("." + connector_table + " tbody").append(cloned_tr);


                } else {
                    alert('<?= get_phrase('update_failed'); ?>');
                }

            }
        });
    }

    $(document).on('click', '.cancel_btn', function(e) {
        //compute_reconciliation($(this));
        cancel_bounced_openning_cheque($(this));
        e.stopImmediatePropagation();
    });

    $(document).on('click', '.clear_btn', function(e) {
        //console.log('Firing!!!');
        compute_reconciliation($(this));
        clear_effect($(this));
        e.stopImmediatePropagation();
        //e.preventDefault();
    });

    $(document).ready(function() {
        compute_proof_of_cash()
    });

    function compute_proof_of_cash() {
        let total_cash = $("#total_cash").html().replace(/,/g, ''); // You can use replaceAll(',','') instead but The replaceAll method is not supported in Internet Explorer versions 6-11 
        let total_fund_month_closing_balance = $("#total_fund_month_closing_balance").html().replace(/,/g, '');

        // alert(total_cash);
        // alert(total_fund_month_closing_balance);

        let proof_check_message = '<span class="label label-danger">Incorrect Proof Of Cash</span>';

        if ((parseFloat(total_cash) - parseFloat(total_fund_month_closing_balance)) == 0) {
            proof_check_message = '<span class="label label-success">Correct Proof Of Cash</span>';
        }

        $("#proof_of_cash_check").html(proof_check_message);
    }


    $(document).ready(function() {
        Dropzone.autoDiscover = false;
    });

    var myDropzone = new Dropzone("#drop_statements", {
        url: "<?= base_url() ?>ajax/financial_report/uploadStatements",
        paramName: "file", // The name that will be used to transfer the file
        params: {
            'office_id': <?= $office_ids[0]; ?>,
            'reporting_month': '<?= $reporting_month; ?>',
            'project_id': $("#project_ids").val() ? $("#project_ids").val() : '',
            'office_bank_ids': $("#office_bank_ids").val() ? $("#office_bank_ids").val() : ''
        },
        maxFilesize: 50, // MB
        uploadMultiple: true,
        parallelUploads: 5,
        maxFiles: 5,
        acceptedFiles: 'image/*,application/pdf',
    });

    // myDropzone.on("sending", function(file, xhr, formData) { 
    // // Will sendthe filesize along with the file as POST data.
    // formData.append("filesize", file.size);  

    // });

    myDropzone.on("complete", function(file) {
        //myDropzone.removeFile(file);
        myDropzone.removeAllFiles();
        //alert(myDropzone.getAcceptedFiles());
    });

    myDropzone.on('error', function(file, response) {
        // $(file.previewElement).find('.dz-error-message').text(response);
        console.log(response);
    });

    myDropzone.on("success", function(file, response) {
        // console.log(response);
        if (response == 0) {
            alert('Error in uploading files');
            return false;
        }
        var table_tbody = $("#tbl_list_statements tbody");
        var obj = JSON.parse(response);
        // console.log(obj);

        //clear the table
        table_tbody.empty();

        $.each(obj, function(i, elem) {
            // console.log(elem);
            for (let index = 0; index < elem.length; index++) {
                table_tbody.append('<tr><td><a href="#" class="fa fa-trash-o delete_statement" id="' + elem[index].attachment_id + '" data-file_name="' + elem[index].attachment_name + '" data-file_path="' + elem[index].s3_preassigned_url + '"></a></td><td><a target="__blank" href="' + elem[index].s3_preassigned_url + '">' + elem[index].attachment_name + '</a></td><td>' + elem[index].attachment_size + '</td><td>' + elem[index].attachment_last_modified_date + '</td></tr>');

            }

        });


    });

    // $(document).on('click', '.delete_statement', function(event) {

    //     event.preventDefault();

    //     //Prepare Data 
    //     let attachment_primary_id = $(this).attr('id');

    //     let file_path = $(this).data('file_path');

    //     let file_name = $(this).data('file_name');

    //     let url = "<?= base_url(); ?>financial_report/delete_statement";
    //     let data = {
    //         'id': attachment_primary_id,
    //         'file_name': file_name,
    //         'file_path': file_path,

    //     };

    //     //Remove the row 

    //     $(this).closest('tr').remove();

    //     //Execute the ajax
    //     $.ajax({
    //         url: url,
    //         data: data,
    //         type: "POST",
    //         success: function(response) {

    //             $("#deletion_info_id").removeClass('hidden');

    //             $("#deletion_info_id").html(response);

    //         }

    //     });

    // });


    $(document).on('click', '.delete_statement', function() {
        event.preventDefault();
        //Prepare Data
        let attachment_primary_id = $(this).attr('id');

        let file_path = $(this).data('file_path');

        let file_name = $(this).data('file_name');

        let url = "<?= base_url(); ?>ajax/financial_report/deleteStatement";
        let data = {
            'id':attachment_primary_id,
            'file_name':file_name,
            'file_path': file_path,

        };

        //Remove the row

        $(this).closest('tr').remove();

        //Execute the ajax
        $.ajax({
            url: url,
            data: data,
            type: "POST",
            success: function(response) {
                //console.log(response);
                //alert(response);
                // $("#deletion_info_id").removeClass('hidden');

                // $("#deletion_info_id").html(response);

                $("#deletion_info_id").removeClass('hidden');

                $("#deletion_info_id").html(response);

            }

         });


    });
</script>