<?php extract($result); ?>
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

                <div class='form-group'>
                    <label class='col-xs-2 control-label'><?= get_phrase('office_bank_name'); ?></label>
                    <div class='col-xs-4'>
                        <select class="form-control select2 required" id="fk_office_bank_id"
                            name='header[fk_office_bank_id]'>
                            <option value="0"><?= get_phrase('select_office_bank'); ?></option>
                            <?php
                            foreach ($office_banks as $key => $office_bank) { ?>

                                <option value="<?= $key; ?>"><?= $office_bank; ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <label class='col-xs-2 control-label'>
                        <?= get_phrase('cheque_book_start_serial_number'); ?>
                    </label>
                    <div class='col-xs-4'>
                        <input id="cheque_book_start_serial_number" type="number" value=""
                            class="form-control master input_cheque_book cheque_book_start_serial_number required"
                            name="header[cheque_book_start_serial_number]"
                            placeholder="Enter Cheque Book Start Serial Number">
                    </div>
                </div>

                <div class='form-group'>
                    <label class='col-xs-2 control-label'><?= get_phrase('cheque_book_count_of_leaves'); ?></label>
                    <div class='col-xs-4'>
                        <input id="cheque_book_count_of_leaves" maxlength="100" required="required" type="number"
                            value="0" class="form-control master input_cheque_book cheque_book_count_of_leaves required"
                            name="header[cheque_book_count_of_leaves]" disabled
                            placeholder="Enter Cheque Book Count Of Leaves">
                    </div>

                    <label class='col-xs-2 control-label'><?= get_phrase('cheque_book_last_serial_number'); ?></label>
                    <div class='col-xs-4'>

                        <input id='cheque_book_last_serial_number_id' type="number" class="form-control required"
                            readonly="" value="" name='header[cheque_book_start_serial_number]'>

                    </div>
                </div>


                <div class='form-group'>
                    <label class='col-xs-2 control-label'><?= get_phrase('cheque_book_use_start_date'); ?></label>
                    <div class='col-xs-4'>
                        <input id="cheque_book_use_start_date" value="" data-format="yyyy-mm-dd" readonly="readonly"
                            type="text"
                            class="form-control master datepicker input_cheque_book cheque_book_use_start_date required"
                            name="header[cheque_book_use_start_date]" placeholder="Enter Cheque Book Use Start Date">
                    </div>
                </div>

                <div class='form-group'>
                    <div class='col-xs-12' style='text-align:center;'>
                        <button class='btn btn-default btn-cancel'><?= get_phrase('cancel'); ?></button>
                        <div class='btn btn-default save-btn'><?= get_phrase('add_new_cheque_book');?></div>
                    </div>
                </div>


                </form>
            </div>
        </div>
        <script>
            function get_cheque_book_size() {
                const office_bank_id = $('#fk_office_bank_id').val();
                const url = '<?= base_url(); ?>ajax/cheque_book/getChequeBookSize/' + office_bank_id;

                $.get(url, function (obj) {
                    const cheque_book_count_of_leaves = obj.cheque_book_size;
                    const is_first_cheque_book = obj.is_first_cheque_book;
                    const cheque_book_start_serial_number = $('#cheque_book_start_serial_number').val();
                    const last_serial = parseInt(cheque_book_start_serial_number) + parseInt(cheque_book_count_of_leaves) - 1;

                    $('#cheque_book_count_of_leaves').val(cheque_book_count_of_leaves);
                    if (is_first_cheque_book) {
                        $('#cheque_book_start_serial_number').removeAttr('disabled');
                        $('#cheque_book_count_of_leaves').removeAttr('disabled');
                    } else {
                        $('#cheque_book_last_serial_number_id').val(last_serial);
                    }
                });
            }

            $('.save-btn').on('click', function (event) {
                if (validate_form()) return false;
                let url = "<?= base_url(); ?>ajax/cheque_book/postChequeBook";
                let data = {
                    'fk_office_bank_id': $('#fk_office_bank_id').val(),
                    'cheque_book_count_of_leaves': $('#cheque_book_count_of_leaves').val(),
                    'cheque_book_start_serial_number': $('#cheque_book_start_serial_number').val(),
                    'cheque_book_use_start_date': $('#cheque_book_use_start_date').val(),
                    'cheque_book_last_serial_number_id': $('#cheque_book_last_serial_number_id').val()
                }

                $.post(url, data, function (response) {
                    console.log(response)
                    if (parseInt(response.insertId) > 0) {
                        //Redirect to a page to submit chq book
                        let office_bank_id = $("#fk_office_bank_id").val();
                        let url = '<?= base_url() ?>ajax/cheque_book/getMaxIdChequeBookForOffice/' + office_bank_id;
                        $.get(url, function (response) {
                            alert('You\'ll be taken to a page to submit the cheque book you have created');
                            window.location.href = '<?= base_url() ?>cheque_book/view/' + response.maxChequeBookId;
                        });

                        return false;
                    } else {
                        alert('<?= get_phrase('chequebook_create_error', 'Error occured when posting new cheque book. Make sure all previous books are approved and all cheque leves used up.'); ?>');
                    }

                });

                event.preventDefault();
            });

            // function on_record_post() {
            //     const office_bank_id = $("#fk_office_bank_id").val();
            //     const url = '<?= base_url() ?>ajax/cheque_book/getMaxIdChequeBookForOffice/' + office_bank_id;
            //     $.get(url, function (response) {
            //         alert('You\'ll be taken to a page to submit the cheque book you have created');
            //         window.location.href = '<?= base_url() ?>cheque_book/view/' + response.maxChequeBookId;
            //     });

            //     return false;
            // }

            //Cancel Adding New cheque book

            $('.btn-cancel').on('click', function (ev) {
                const redirect = '<?= base_url(); ?>cheque_book/list'
                window.location.replace(redirect);
                ev.preventDefault();
            });

            //Validate the inputs before posting
            function validate_form() {
                let any_field_empty = false
                $(".required").each(function () {

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