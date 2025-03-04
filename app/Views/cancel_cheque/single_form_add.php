<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
//print_r($this->voucher_model->get_default_income_for_account_system($this->session->user_account_system_id));


?>

<div class='row'>
    <div class='col-xs-12'>

        <div class="panel panel-default" data-collapsed="0">
            <div class="panel-heading">
                <div class="panel-title">
                    <i class="entypo-plus-circled"></i>
                    <i style='color: brown;'> <?php echo get_phrase('cancel_cheques'); ?> </i>
                </div>
            </div>



            <div class="panel-body" style="max-width:50; overflow: auto;">
                <!-- Error message -->
                <div id='message_info'></div>
                <?php echo form_open("", array('id' => 'frm_cancel_cheque', 'class' => 'form-horizontal form-groups-bordered validate', 'enctype' => 'multipart/form-data')); ?>


                <div class='form-group'>
                    <div class='col-xs-12 user_message'>

                    </div>
                </div>

                <div>&nbsp;</div>
                <div class='col-xs-12 '>

                    <!-- Cheque Id -->

                    <input class='hidden' id='chequebook_id' type='text' name='chequebook_id' value='0' />

                    <!-- Populate Bank accounts -->


                    <label class='col-xs-2 control-label'><?= get_phrase('bank_account', 'Bank Account') ?></label>
                    <div class='col-xs-2'>


                        <select class="form-control select2 required" id="fk_office_bank_id" name='office_bank_id'>

                            <option value=''> <?= get_phrase('select_bank', 'Select Bank') ?></option>

                            <?php

                            //Get the bank accounts
                            $bank_ids_and_names = $result['office_banks'];

                            foreach ($bank_ids_and_names as $key => $bank_ids_and_name) { ?>

                                <option value=<?= $key ?>> <?= get_phrase('bank_' . $key, $bank_ids_and_name) ?></option>

                            <?php
                            }

                            ?>


                        </select>
                    </div>
                    <!-- Cheque Numbers -->
                    <label class='col-xs-2 control-label'><?= get_phrase('valid_cheques', "Valid Cheque Numbers") ?></label>
                    <div class='col-xs-2'>

                        <select class="form-control select2 required" id="cheque_number_id" name='cheque_number_id[]' multiple>



                        </select>
                    </div>

                    <!-- Reason of cancellation -->


                    <label class='col-xs-2 control-label'><?= get_phrase('reason_of_cancel', 'Reason') ?></label>
                    <div class='col-xs-2'>


                        <select class="form-control select2 required" id="fk_item_reason_id" name='fk_item_reason_id'>

                            <option value='0'> <?= get_phrase('select_reason', 'Select Reason') ?></option>


                            <?php

                            //Get cancel reason
                            $reason_ids_and_names = $result['cheque_cancel_reason'];

                            foreach ($reason_ids_and_names as $key => $reason_ids_and_name) { ?>

                                <option value=<?= $key ?>> <?= get_phrase('item_reason_' . $key, $reason_ids_and_name) ?></option>

                            <?php
                            }

                            ?>


                        </select>
                    </div>

                </div>


                <div>&nbsp;</div>

                <div class='col-xs-12 '>
                    <!-- Reason Field if Others selected -->

                    <label class='col-xs-2 control-label hidden' id='other_reason_label_id'><?= get_phrase('other_reason_field', "Other Reason Details") ?></label>
                    <div class='col-xs-10'>

                        <input class="form-control hidden col-xs-10" id="other_reason" name='other_reason' type='text' />
                    </div>
                </div>


                <div>&nbsp;</div>
                <div class='form-group'>
                    <div class='col-xs-12' style='text-align:center;'>
                        <button class='btn btn-default btn-save'><?= get_phrase("cancel_cheque_no", "Cancel Cheque Number(s)") ?></button>
                        <button class='btn btn-default btn-reset'>Reset</button>
                    </div>
                </div>


                </form>
            </div>
        </div>

        <script>
            //Unhide or Hide Other Reason field and make required
            $(document).on('change', '#fk_item_reason_id', function() {

                let others = $(this).find('option:selected').text();

                if (others.trim() == 'Others') {

                    $('#other_reason_label_id').removeClass('hidden');

                    $('#other_reason').removeClass('hidden');

                    $('#other_reason').addClass('required');
                } else {
                    $('#other_reason_label_id').addClass('hidden');

                    $('#other_reason').addClass('hidden');

                    $('#other_reason').removeClass('required');
                }
            });
            //Populate cheques numbers
            $(document).on('change', '#fk_office_bank_id', function() {


                let office_bank_id = $(this).val();

                //Check if active check exits
                let active_cheque_book_url = '<?= base_url() ?>cancel_cheque/get_active_chequebook/' + office_bank_id;

                $.get(active_cheque_book_url, function(response_active_cheques) {

                    let res = parseInt(response_active_cheques);

                    if (res == 0) {

                        $('#message_info').html('<?= get_phrase('No_active_chqbk', 'No active cheque book exists'); ?>')

                        $('#message_info').css({
                            "color": "red",
                            'font-size': "medium",
                            "margin": "auto",
                            "padding-left": "500px"
                        });

                        return false
                    }

                    //assign the chq book id value
                    $('#message_info').html('');

                    $('#chequebook_id').prop('value', res);

                    let url = '<?= base_url() ?>cancel_cheque/get_valid_cheques/' + office_bank_id;

                    let options = "<option value='0'> <?= get_phrase('select_cheque_number', 'Select Cheque Number') ?></option>";

                    $.get(url, function(response) {

                        let cheque_numbers = JSON.parse(response);

                        //console.log(cheque_numbers);

                        $.each(cheque_numbers, function(i, elem) {

                            options += "<option value='" + elem.cheque_id + "'>" + elem.cheque_number + "</option>";
                        });

                        //Append cheque numbers to cheque_number_id select 2 element
                        $("#cheque_number_id").html(options);
                    });
                })

            });

            // Hide select Select All button
            $(document).ready(function() {
                $('.select2-select-all').hide();
            })

            //Save the cancelled Cheques
            $(document).on('click', '.btn-save', function(event) {

                //Validate the form and return false if an issue with validation other proceed and save data
                if(form_validation()==true){

                    return false;
                }

                //Get the data from the and save form passed validation.

                let information_msg = '<?= get_phrase("information_msg", "Are you sure you want to void this cheque??"); ?>';

                if (confirm(information_msg) == true) {

                    let office_bank_id = $('#fk_office_bank_id').val();

                    let cheque_number_id = $('#cheque_number_id').val();

                    let chequebook_id = $('#chequebook_id').val();

                    let reason_id = $('#fk_item_reason_id').val();

                    //If others selected
                    let others = $('#fk_item_reason_id').find('option:selected').text();

                    let other_reason = '';

                    if (others.trim() == 'Others') {
                        other_reason = $('#other_reason').val();
                    }

                    //get the data  values
                    let data = {
                        'cancel_cheque_number': cheque_number_id,
                        'fk_cheque_book_id': chequebook_id,
                        'office_bank_id': office_bank_id,
                        'fk_item_reason_id': reason_id,
                        'other_reason': other_reason
                    }

                    // console.log(data);

                    let url = '<?= base_url(); ?>cancel_cheque/save_cancelled_cheques';

                    $.post(url, data, function(response) {

                        //console.log(response);

                        if (parseInt(response) == 0) {

                            $('#message_info').html('<?= get_phrase('db_insert_failed', 'Cancelling Cheques Failed'); ?>')

                            $('#message_info').css({
                                "color": "red",
                                'font-size': "medium",
                                "margin": "auto",
                                "padding-left": "500px"
                            });

                            return false;

                        } else {

                            $('#message_info').html('<?= get_phrase('db_insert_success', 'Cheques cancellation Saved'); ?>');

                            $('#message_info').css({
                                "color": "green",
                                'font-size': "medium",
                                "margin": "auto",
                                "padding-left": "500px"
                            });

                            //alert('Test');
                            window.location.replace('<?= base_url(); ?>voucher/list/');
                        }
                    });

                }

                event.preventDefault();

            });

            //Refresh the page
            $(document).on('click', '.btn-reset', function() {

                window.location.href = '<?= base_url() ?>cancel_cheque/single_form_add';

            });

            
            function form_validation(){

                let form_field_missing_info=false;

                /*Get all required fieds and loop to color them with border line of 
                 red if values not supplied otherwise remove red borders if values <> '' or 0 or null*/
                let required=$('.required');

                for(var index=0; index<required.length; index++){

                    let value=$(required[index]).val();

                    if(value==''||value==0 || value==null){

                        /*ignore ids with 's2' e.g. s2id_fk_office_bank_id which are autocreated when it elem=select2 dropdown
                          and highlight the ones without s2 elements like fk_office_bank_id and turn form_field_missing_info=true */
                        $(required[index]).css('border-color', 'red');
                        
                        let element_id_attr=$(required[index]).attr('id');

                        if(element_id_attr.includes("s2id")==false){

                            form_field_missing_info=true;
                        }
                    }else{
                        //Remmove the red border on higlighted elements if all have values
                        if(($(required[index]).hasClass('select2'))){
                           
                            $(required[index]).css('border', '');
                            
                            //reload select2
                            $(required[index]).select2();

                        }else{
                            $(required[index]).css('border', '');
                        }
                    }
                }
                
                return form_field_missing_info;

            }

            
        </script>