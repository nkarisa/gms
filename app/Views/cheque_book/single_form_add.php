<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

extract($result);

?>
<div class='row'>
    <div class='col-xs-12'>

        <div class="panel panel-default" data-collapsed="0">
            <div class="panel-heading">
                <div class="panel-title">
                    <i class="entypo-plus-circled"></i>
                    <?php echo get_phrase('add_check_book'); ?>
                </div>
            </div>

            <div class="panel-body" style="max-width:50; overflow: auto;">
                <?php

                echo form_open("", array(
                    'id' => 'frm_add_check_book',
                    'class' => 'form-horizontal form-groups-bordered validate',
                    'enctype' => 'multipart/form-data'
                ));
                ?>

                <div class='col-xs-12'>
                    <div class='form-group'>
                        <label class='col-xs-2 control-label'><?= get_phrase('office_bank_name'); ?></label>
                        <div class='col-xs-4'>

                            <select class="form-control select2 required" id="fk_office_bank_id" name='header[fk_office_bank_id]'>
                                <option value="0"><?= get_phrase('select_office_bank'); ?></option>
                                <?php
                                foreach ($office_banks as $key => $office_bank) { ?>

                                    <option value="<?= $key; ?>"><?= $office_bank; ?></option>
                                <?php } ?>

                            </select>
                        </div>
                        <!-- Start Cheque Serial Number -->
                        <div class='form-group'>
                            <label class='col-xs-2 control-label'>
                                <?= get_phrase('cheque_book_start_serial_number'); ?>
                            </label>
                            <div class='col-xs-4'>

                                <input id="cheque_book_start_serial_number" type="number" value="" class="form-control master input_cheque_book cheque_book_start_serial_number required" name="header[cheque_book_start_serial_number]" placeholder="Enter Cheque Book Start Serial Number">

                            </div>
                        </div>

                        <div class='col-xs-12'>
                            <!-- Cheque Leaves -->
                            <div class='form-group'>
                                <label class='col-xs-2 control-label'><?= get_phrase('cheque_book_count_of_leaves'); ?></label>
                                <div class='col-xs-4'>

                                    <input id="cheque_book_count_of_leaves" maxlength="100" required="required" type="number" value="0" class="form-control master input_cheque_book cheque_book_count_of_leaves required" name="header[cheque_book_count_of_leaves]" disabled placeholder="Enter Cheque Book Count Of Leaves">
                                
                                    <!-- <select class="form-control select2 required" id="cheque_book_count_of_leaves" name='header[cheque_book_count_of_leaves]' disabled>

                                       <option value='0'><?=get_phrase('leave_count', 'Select Count of leave')?></option>
                                       <option value='50'>50</option>
                                       <option value='100'>100</option>
                                       <option value='150'>150</option>
                                       <option value='200'>200</option>
                                       <option value='250'>250</option>
                                       <option value='300'>300</option>
                                       <option value='350'>350</option>

   
                                    </select> -->
                                </div>
                                <!-- Start Cheque Serial Number -->
                                <div class='form-group'>
                                    <label class='col-xs-2 control-label'><?= get_phrase('cheque_book_last_serial_number'); ?></label>
                                    <div class='col-xs-4'>

                                        <input id='cheque_book_last_serial_number_id' type="number" class="form-control required" readonly="" value="" name='header[cheque_book_start_serial_number]'>

                                    </div>
                                </div>

                                <div class='col-xs-12'>
                                    <!-- Cheque Book Use Start Date -->
                                    <div class='form-group'>
                                        <label class='col-xs-2 control-label'><?= get_phrase('cheque_book_use_start_date'); ?></label>
                                        <div class='col-xs-4'>

                                            <input id="cheque_book_use_start_date" value="" data-format="yyyy-mm-dd" readonly="readonly" type="text" class="form-control master datepicker input_cheque_book cheque_book_use_start_date required" name="header[cheque_book_use_start_date]" placeholder="Enter Cheque Book Use Start Date">
                                        </div>

                                    </div>
                                    <div class='form-group'>
                                        <div class='col-xs-12' style='text-align:center;'>
                                            <button class='btn btn-default btn-cancel'><?= get_phrase('cancel'); ?></button>
                                            <button class='btn btn-default btn-save'><?= get_phrase('add_new_cheque_book'); ?></button>
                                        </div>
                                    </div>


                                    </form>
                                </div>
                            </div>
                            <script>
                                // $('#fk_office_bank_id').on('change', function (){


                                // });

                                function get_cheque_book_size() {
                                    const office_bank_id = $('#fk_office_bank_id').val();
                                    const url = '<?= base_url(); ?>cheque_book/get_cheque_book_size/' + office_bank_id;

                                    $.get(url, function(response) {
                                        // alert(response);
                                        const obj = JSON.parse(response);

                                        const cheque_book_count_of_leaves = obj.cheque_book_size;
                                        const is_first_cheque_book = obj.is_first_cheque_book;
                                        console.log(obj);
                                        const cheque_book_start_serial_number = $('#cheque_book_start_serial_number').val();
                                        const last_serial = parseInt(cheque_book_start_serial_number) + parseInt(cheque_book_count_of_leaves) - 1;

                                         //alert(cheque_book_start_serial_number);

                                        $('#cheque_book_count_of_leaves').val(cheque_book_count_of_leaves);
                                        //$('#cheque_book_last_serial_number_id').val(last_serial);
                                        //$('#cheque_book_last_serial_number_id').val(last_serial);

                                        if (is_first_cheque_book) {
                                            $('#cheque_book_start_serial_number').removeAttr('disabled');
                                            $('#cheque_book_count_of_leaves').removeAttr('disabled');

                                        }else{
                                            $('#cheque_book_last_serial_number_id').val(last_serial);
                                        }
                                    });
                                }

                            

                                $('.btn-save, .btn-save-new').on('click', function(event) {

                                    if (validate_form()) return false;

                                    var url = "<?= base_url(); ?>cheque_book/post_cheque_book";

                                    var data = {
                                        'fk_office_bank_id': $('#fk_office_bank_id').val(),
                                        'cheque_book_count_of_leaves': $('#cheque_book_count_of_leaves').val(),
                                        'cheque_book_start_serial_number': $('#cheque_book_start_serial_number').val(),
                                        'cheque_book_use_start_date': $('#cheque_book_use_start_date').val(),
                                        'cheque_book_last_serial_number_id': $('#cheque_book_last_serial_number_id').val()

                                    }
                                    $.post(url, data, function(res) {


                                        if (parseInt(res) > 0) {
                                            //Redirect to a page to submit chq book
                                            on_record_post();

                                        } else {
                                            alert('<?= get_phrase('chequebook_create_error', 'Error occured when posting new cheque book. Make sure all previous books are approved.'); ?>');
                                        }

                                    });

                                    event.preventDefault();
                                });

                                //Cancel Adding New cheque book

                                $('.btn-cancel').on('click', function(ev) {

                                    var redirect = '<?= base_url(); ?>cheque_book/list'

                                    window.location.replace(redirect);

                                    ev.preventDefault();

                                });
                                //Validate the inputs before posting
                                function validate_form() {

                                    var any_field_empty = false

                                    $(".required").each(function() {

                                        if ($(this).val().trim() == '') {

                                            $(this).css('border-color', 'red');
                                            any_field_empty = true;

                                        } else {
                                            $(this).css('border-color', '');

                                            //Select2 implementation
                                            if ($(this).hasClass('select2') && $(this).val() != 0) {
                                                $(this).siblings(".select2-container").css('border', '');

                                                any_field_empty = false;
                                            }

                                        }

                                    });

                                    return any_field_empty;
                                }
                            </script>