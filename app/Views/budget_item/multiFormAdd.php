<style>
    .center {
        text-align: center;
    }
</style>

<?php

extract($result);

//print_r("test");

// echo json_encode($this->session->system_settings);

?>

<div class="row">
    <div class='col-xs-12'>
        <div class="panel panel-default" data-collapsed="0">
            <div class="panel-heading">
                <div class="panel-title">
                    <i class="entypo-plus-circled"></i>
                    <?php echo get_phrase('add_budget_item_for_'); ?> <?php echo $office->office_code . ' - ' . $office->office_name . ' : ' . get_phrase('FY') . $office->budget_year; ?>
                </div>
            </div>

            <div class="panel-body" style="max-width:50; overflow: auto;">
                <?php echo form_open("", ['id' => 'frm_budget_item', 'class' => 'form-horizontal form-groups-bordered validate', 'enctype' => 'multipart/form-data']); ?>

                <div class='form-group'>
                    <div class='col-xs-12 center'>
                        <div class='btn btn-icon pull-left' id='btn_back'><i class='fa fa-arrow-left'></i></div>

                        <div class='btn btn-default btn-reset'><?php echo get_phrase('reset'); ?></div>
                        <div class='btn btn-default btn-save'><?php echo get_phrase('save'); ?></div>
                        <div class='btn btn-default btn-save-new'><?php echo get_phrase('save_and_new'); ?></div>
                    </div>
                </div>

                <div class="form-group">

                    <label class='control-label col-xs-2'><?php echo get_phrase('project_allocation'); ?></label>
                    <div class='col-xs-2'>
                        <select name='fk_project_allocation_id' id='fk_project_allocation_id' class='form-control resetable'>
                            <option value=''><?php echo get_phrase('select_a_project_allocation'); ?></option>

                            <?php foreach ($project_allocations as $project_allocation) { ?>
                                <option value='<?php echo $project_allocation->project_allocation_id; ?>'><?php echo $project_allocation->project_name; ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <label class='control-label col-xs-2'><?php echo get_phrase('expense_account'); ?></label>
                    <div class='col-xs-2'>
                        <select name='fk_expense_account_id' id='fk_expense_account_id' class='form-control resetable'>

                            <option value=''><?php echo get_phrase('select_an_account'); ?></option>


                        </select>
                    </div>

                    <label class='control-label col-xs-2'><?php echo get_phrase('budget_limit_remaining_amount'); ?></label>
                    <div class='col-xs-2'>
                        <input type="text" class="form-control total_fields" id="budget_limit_amount" readonly="readonly" value="0" />
                    </div>

                </div>

                <?php
                if (isset($this->session->system_settings['use_pca_objectives']) && $this->session->system_settings['use_pca_objectives']) {
                ?>
                    <div class='form-group'>
                        <div class="col-xs-6">
                            <select class='form-control resetable' id='pca_objective' name='pca_objective'>
                                <option value=""><?php echo get_phrase('select_an_objective'); ?></option>
                                <?php foreach ($pca_objectives as $pca_objective_id => $pca_objective) { ?>
                                    <option value="<?php echo $pca_objective_id; ?>"><?php echo $pca_objective; ?></option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="col-xs-6">
                            <select class='form-control resetable' id='pca_intervention' name='pca_intervention'>
                                <option value=""><?php echo get_phrase('select_an_intervention'); ?></option>
                            </select>
                        </div>
                    </div>

                <?php } ?>

                <div class='form-group'>
                    <div class="col-xs-12">
                        <textarea name='budget_item_description' id='budget_item_description' placeholder="<?php echo get_phrase('describe_budget_item'); ?>" class='form-control resetable'></textarea>
                    </div>
                </div>

                <div class='form-group'>
                    <label class="control-label col-xs-1"><?php echo get_phrase('quantity'); ?></label>
                    <div class="col-xs-2">
                        <input type="text" class="form-control resetable frequency_fields" id="budget_item_quantity" name="budget_item_quantity" value="0" />
                    </div>

                    <label class="control-label col-xs-1"><?php echo get_phrase('unit_cost'); ?></label>
                    <div class="col-xs-2">
                        <input type="text" class="form-control resetable frequency_fields" id="budget_item_unit_cost" name="budget_item_unit_cost" value="0" />
                    </div>

                    <label class="control-label col-xs-1"><?php echo get_phrase('often'); ?></label>
                    <div class="col-xs-2">
                        <input type="number" class="form-control resetable frequency_fields" id="budget_item_often" name="budget_item_often" value="1" max="12" min="1" />
                    </div>

                    <label class="control-label col-xs-1"><?php echo get_phrase('total'); ?></label>
                    <div class="col-xs-2">
                        <input type="text" class="form-control resetable total_fields" id="frequency_total" readonly="readonly" value="0" name="" />
                    </div>

                </div>

                <div class='form-group'>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th><?php echo get_phrase('action'); ?></th>
                                <?php
                                // print_r($month_order);
                                // print_r($months);
                                foreach ($months as $month) {
                                ?>
                                    <th><?php echo get_phrase($month['month_name']); ?></th>
                                <?php
                                }
                                ?>

                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div class='btn btn-danger' id='btn-clear'><?php echo get_phrase('clear'); ?></div>
                                </td>

                                <?php
                                // log_message('error', json_encode($months_to_freeze));
                                // print_r($months_to_freeze);
                                ?>

                                <?php foreach ($months as $month) { ?>
                                    <td><input type='text' <?php echo in_array($month['month_id'], $months_to_freeze) ? "readonly" : ''; ?> id='' name='fk_month_id[<?php echo $month['month_id']; ?>]' value='0' class='form-control month_spread' /></td>
                                <?php } ?>

                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class='form-group'>
                    <!-- <label class='control-label col-xs-2'><?php echo get_phrase('total_cost'); ?></label> -->
                    <div class='col-xs-2'>
                        <input type='text' readonly='readonly' name='budget_item_total_cost' id='budget_item_total_cost' class='form-control resetable total_fields' value='0' />
                    </div>
                </div>

                <div class='form-group'>
                    <div class='col-xs-12 center'>
                        <div class='btn btn-default btn-reset'><?php echo get_phrase('reset'); ?></div>
                        <div class='btn btn-default btn-save'><?php echo get_phrase('save'); ?></div>
                        <div class='btn btn-default btn-save-new'><?php echo get_phrase('save_and_new'); ?></div>
                    </div>
                </div>
                <!--Hidden fields-->
                <input type='hidden' value='<?php echo hash_id($id, 'decode'); ?>' name='fk_budget_id' id='fk_budget_id' />
                </form>
            </div>

        </div>
    </div>

    <script>
        $(".form-control").on('change', function() {
            if ($(this).val() !== '') {
                $(this).removeAttr('style');
            }
        });

        $("#fk_expense_account_id").on('change', function() {
            let url = "<?php echo base_url(); ?>ajax/budget_item/budgetLimitRemainingAmount/<?php echo hash_id($id, 'decode'); ?>/" + $(this).val();

            $.get(url, function(response) {
                // console.log(url);
                // console.log(response);
                $("#budget_limit_amount").val(response);
            });
        });



        $("#fk_project_allocation_id").on('change', function() {
            var project_allocation_id = parseInt($(this).val());

            var url = "<?php echo base_url(); ?>ajax/budget_item/projectBudgetableExpenseAccounts/" + project_allocation_id;

            let option = '<option value=""><?php echo get_phrase('select_expense_account'); ?></option>';

            $('#fk_expense_account_id').html(option);


            if (!$.isNumeric(project_allocation_id)) {
                return false;
            }

            $.get(url, function(response) {
                var accounts_obj = JSON.parse(response);

                $.each(accounts_obj, function(i, el) {
                    option += '<option value="' + accounts_obj[i].expense_account_id + '">' + accounts_obj[i].expense_account_name + '</option>';
                });

                $('#fk_expense_account_id').html(option);
            });

        });

        // $('.month_spread').focusout(function() {
        //     if (!$.isNumeric($(this).val())) {
        //         $(this).val(0);
        //     }
        // });

        $('.month_spread').focusin(function() {
            if ($(this).val() == 0 && !$(this).attr('readonly')) {
                $(this).val('');
            }
        });

        $('.month_spread').on('change', function() {
            if ($(this).val() < 0) {
                alert('<?php echo get_phrase('negative_values_not_allowed'); ?>');
                $(this).val(0);
            }

            $('.month_spread').removeAttr('style');
            $('#budget_item_often').removeAttr('style');
        });

        // $('.frequency_fields').focusout(function() {
        //     if (!$.isNumeric($(this).val())) {
        //         $(this).val(0);
        //     }
        // });

        // $('.frequency_fields').focusin(function() {
        //     if ($(this).val() == 0) {
        //         $(this).val('');
        //     }
        // });

        // $('.frequency_fields').on('change', function() {
        //     if ($(this).val() < 0) {
        //         alert('<?php echo get_phrase('negative_values_not_allowed'); ?>');
        //         $(this).val(0);
        //     }

        //     $('.month_spread').removeAttr('style');
        //     $('#budget_item_often').removeAttr('style');
        // });


        $('.frequency_fields').focusin(function() {
            if ($(this).val() == 0) {
                $(this).val('');
            }
        });

        $('.frequency_fields').on('change', function() {
            if ($(this).val() < 0) {
                alert('<?= get_phrase('negative_values_not_allowed', 'Negative Values Not Allowed'); ?>');
                $(this).val(0);
            }

            $('.month_spread').removeAttr('style');
            $('#budget_item_often').removeAttr('style');
        });

        // function compute_sum_spread() {
        //     var sum_spread = 0;

        //     $('.month_spread').each(function(index, elem) {
        //         if ($(elem).val() > 0) {
        //             sum_spread = sum_spread + parseFloat($(elem).val());
        //         }
        //     });

        //     $('#budget_item_total_cost').val(sum_spread.toFixed(2));
        // }

        function compute_sum_spread() {

            let sum_spread = 0;

            $('.month_spread').each(function(index, elem) {

                //NEW CODE: Remove commas //This code was added by Onduso 4/2/2025
                let value = $(elem).val();

                value = value.replace(/,/g, "");

                if (parseFloat(value) > 0) {
                    sum_spread = sum_spread + parseFloat(value);
                }

                let formattedNumber = sum_spread.toLocaleString();

                let valueWithCommas = formattedNumber.replace(/,/g, '');

                let formattedNumberWithParts = formartNumberWithCommas(valueWithCommas);

                $('#budget_item_total_cost').val(formattedNumberWithParts);
                //END

                // OLD CODE
                // if ($(elem).val() > 0) {
                //     sum_spread = sum_spread + parseFloat($(elem).val());
                // }
            });

        }

        $('.month_spread').bind('keyup blur', function() {
            compute_sum_spread();
        });

        $("#btn-clear").on('click', function() {
            $.each($(".month_spread"), function(i, el) {
                $(el).val(0);
            });

            compute_sum_spread();
        });

        $(".btn-save-new").on('click', function() {
            let count_of_empty_fields = 0;

            let count_spread_cell_with_amount_gt_zero = $('.month_spread').filter(function() {
                return parseFloat($(this).val()) > 0;
            }).length;

            $('.form-control').each(function(i, el) {
                if ($(el).val() == '') {
                    count_of_empty_fields++;
                    $(el).css('border', '1px solid red');
                }
            });


            if (count_of_empty_fields > 0) {
                alert('<?php echo get_phrase("one_or_more_fields_are_empty"); ?>');
                return false;
            }

            if ($("#budget_item_total_cost").val() == 0) {
                alert('<?php echo get_phrase("budget_item_must_have_total_greater_than_zero"); ?>');
                return false;
            }

            if (count_spread_cell_with_amount_gt_zero != parseFloat($('#budget_item_often').val())) {
                $('#budget_item_often, .month_spread').css('border', '1px solid red');
                alert('<?php echo get_phrase("spread_not_matching_frequency", "The month spreading does match the frequency given"); ?>');
                return false;
            }

            var url = "<?php echo base_url(); ?>budget_item/get_budget_limit_remaining_amount/<?php echo hash_id($id, 'decode'); ?>/" + $("#fk_expense_account_id").val();

            getRequest(url, function(response) {
                $('#budget_limit_amount').val(response);

                if (!compute_totals_match()) {
                    alert('<?php echo get_phrase("computation_mismatch") ?>');
                    return false;
                }

                save(false);
                resetForm();
            });

        });

        $(".btn-save").on('click', function() {

            var count_of_empty_fields = 0;

            let count_spread_cell_with_amount_gt_zero = $('.month_spread').filter(function() {
                return parseFloat($(this).val()) > 0;
            }).length;

            $('.form-control').each(function(i, el) {
                if ($(el).val() == '') {
                    count_of_empty_fields++;
                    $(el).css('border', '1px solid red');
                }
            });

            if (count_of_empty_fields > 0) {
                alert('<?php echo get_phrase("one_or_more_fields_are_empty"); ?>');
                return false;
            }

            if ($("#budget_item_total_cost").val() == 0) {
                alert('<?php echo get_phrase("budget_item_must_have_total_greater_than_zero"); ?>');
                return false;
            }

            if (count_spread_cell_with_amount_gt_zero != parseFloat($('#budget_item_often').val())) {
                $('#budget_item_often, .month_spread').css('border', '1px solid red');
                alert('<?php echo get_phrase("spread_not_matching_frequency", "The month spreading does match the frequency given"); ?>');
                return false;
            }


            var url = "<?php echo base_url(); ?>ajax/budget_item/getBudgetLimitRemainingAmount/<?php echo hash_id($id, 'decode'); ?>/" + $("#fk_expense_account_id").val();

            getRequest(url, function(response) {
                $('#budget_limit_amount').val(response);

                if (!compute_totals_match()) {
                    alert('<?php echo get_phrase("computation_mismatch") ?>');
                    return false;
                }

                save();
            });


        });

        $(document).on('keyup', '.frequency_fields, .month_spread', function(event) {

            addCommasToNumber($(this), event);

        });

        $("#budget_item_quantity, #budget_item_often, #budget_item_unit_cost").bind('keyup change', function() {

            //Remove the commas to enable computation./This code added by Onduso on 4/02/2025

            let qty = removeCommaSeparatedNumbers($("#budget_item_quantity").val());

            let unit_cost = removeCommaSeparatedNumbers($("#budget_item_unit_cost").val());
            let often = removeCommaSeparatedNumbers($("#budget_item_often").val());
            let frequency_total = 0;

            console.log(unit_cost);
            //End

            if (qty != 0 && unit_cost != 0 && often != 0) {
                frequency_total = parseFloat(qty) * parseFloat(unit_cost).toFixed(2) * parseFloat(often);
            }

            //Put commas total . This code added by Onduso on 4/02/2025

            let totalWithoutCommas = $("#frequency_total").val(frequency_total.toFixed(2));

            let freq_total = $("#frequency_total").val();
            let parts = freq_total.split('.');
            let integerPart = parseInt(parts[0]).toLocaleString();
            let decimalPart = parts.length > 1 ? '.' + parts[1] : '';
            let formattedNumber = integerPart + decimalPart;

            $("#frequency_total").val(formattedNumber);

            //END of addition


        });

        
        function compute_totals_match() {
            let frequency_compute = removeCommaSeparatedNumbers($("#frequency_total").val());

            let budget_item_total_cost = removeCommaSeparatedNumbers($("#budget_item_total_cost").val());
            let budget_limit_amount = removeCommaSeparatedNumbers($("#budget_limit_amount").val());

            //return false;
            let compute_totals_match = false;

            if ((parseFloat(frequency_compute) == parseFloat(budget_item_total_cost)) && (parseFloat(frequency_compute) <= parseFloat(budget_limit_amount))) {
                compute_totals_match = true;
                $(".total_fields").removeAttr('style');
            } else {
                $(".total_fields").css('border', '1px red solid');
            }
            //alert(compute_totals_match);
            return compute_totals_match;
        }

        // function save(go_back = true) {
        //     let frm = $("#frm_budget_item");

        //     let data = frm.serializeArray();

        //     let url = "<?php echo base_url(); ?>ajax/budget_item/insertBudgetItem";

        //     postRequest(url, data, function(response) {
        //         alert(response);

        //         //console.log(data);

        //         //return false;

        //         if (go_back) {
        //             location.href = document.referrer;
        //         }
        //     })
        // }

        function save(go_back = true) {
            let frm = $("#frm_budget_item");

            let data = frm.serializeArray();

            let url = "<?= base_url(); ?>ajax/budget_item/insertBudgetItem";

            //This piece of code was added by Onduso on 5/2/2025
            let modifiedData = [];

            data.forEach(function(item) {

                let sanitize_number = removeCommaSeparatedNumbers(item.value);

                modifiedData.push({
                    name: item.name,
                    value: sanitize_number // Modified value
                });
            });

            //End of addition

            postRequest(url, modifiedData, function(response) {
                alert(response);

                // console.log(data);
                //return false;

                if (go_back) {
                    location.href = document.referrer;
                }
            })
        }


        //Remove commas to sanitize the value to store in DB
        function removeCommaSeparatedNumbers(value) {
            return value.replace(/\b\d{1,3}(?:,\d{3})*(?:\.\d+)?\b/g, match => match.replace(/,/g, ''));


        }



        $("#btn_back").on('click', function() {
            location.href = document.referrer;
        });

        $(".btn-reset").on('click', function() {
            resetForm();
        });

        function resetForm(elem) {
            $.each($('.resetable'), function(i, el) {
                $($(el).val(null))
            });

            $.each($('.month_spread'), function(i, el) {
                $($(el).val(0));
            });

            $("#budget_limit_amount").val(0)
        }

        function spread_not_matching_frequency() {
            return true;
        }

        $('#pca_objective').on('change', function() {
            const objective = $(this).val();
            const url = '<?php echo base_url(); ?>budget_item/ajax_get_objective_interventions'
            const data = {
                objective
            }

            $.post(url, data, function(response) {

                const interventions = JSON.parse(response);
                let options = '<option value = ""><?php echo get_phrase('select_an_intervention'); ?></option>';

                $.each(interventions, function(i, el) {
                    options += '<option value = "' + i + '">' + el + '</option>';
                });

                $('#pca_intervention').html(options)
            })
        })


        //Function to put commas when User is typing Added  by Onduso
        function addCommasToNumber(elem, event) {

            $(elem).on('input', function() {

                let value = $(elem).val().replace(/,/g, '');

                let formattedNumber = formartNumberWithCommas(value);

                if (!isNaN(parseFloat(formattedNumber))) {

                    $(elem).val(formattedNumber);
                } //Check if the input is a valid number and not just whitespace
                else if (event.which === 8 && event.which === 46 && !$.isNumeric($(elem).val(formattedNumber))) {
                    alert("<?= get_phrase('non_number_error', "Error: Invalid input. Please enter a number.") ?>");
                    $(elem).val('');

                }

            });

        }

        function formartNumberWithCommas(valueWithCommas) {
            //let valueWithCommas = formattedNumber.replace(/,/g, '');
            let parts = valueWithCommas.split('.');


            let integerPart = parseInt(parts[0]).toLocaleString();
            let decimalPart = parts.length > 1 ? '.' + parts[1] : '';
            let formattedNumberWithParts = integerPart + decimalPart;
            //Add commas and check if after stripping off the commas
            formattedNumberWithParts = formattedNumberWithParts.replace(/(\d)(?=(\d{3})+$)/g, '$1,');

            return formattedNumberWithParts;
        }
    </script>