<script>
    $("#fk_office_bank_id").on('change', function() {

        var url = "<?= base_url(); ?>cheque_book/new_cheque_book_start_serial";
        let office_bank_id = $(this).val()
        var data = {
            'office_bank_id': office_bank_id
        };

        if(!office_bank_id){
            return false;
        }


        var url_active_cheques = '<?= base_url() ?>Cheque_book/get_active_chequebooks/' + office_bank_id;

        $.get(url_active_cheques, function(response) {

            let active_chqs = parseInt(response);

            // alert(active_chqs);
            
            //Cheque if the active cheques exists if so don't create another one otherwise create a new one
            if (active_chqs > 0) {
                alert('<?= get_phrase("active_cheque_book_error",'You Still Have An Active Chequebook For This Bank Account And Can Not Add Another One');?>');
                $('#fk_office_bank_id').val($('#fk_office_bank_id option:eq(0)').val()).trigger('change');
                // window.location.href = '<?= base_url() ?>cheque_book/list'
            } else {

                $.post(url, data, function(next_new_cheque_book_start_serial) {
                    // alert(next_new_cheque_book_start_serial);

                    if (next_new_cheque_book_start_serial > 0) {
                        $("#cheque_book_start_serial_number").val(next_new_cheque_book_start_serial);
                        $("#cheque_book_start_serial_number").prop('readonly', 'readonly');
                    } else {
                        $("#cheque_book_start_serial_number").val("");
                        $("#cheque_book_start_serial_number").removeAttr('readonly');
                    }

                    ///
                    get_cheque_book_size()
                });
            }

        });

    });

    $("#cheque_book_count_of_leaves, #cheque_book_start_serial_number").on('change', function() {
        if ($(this).val() < 1) {
            alert('You must have a count greater than zero');
            $(this).val('');
            $(this).css('border', '1px red solid');
        } else {
            last_cheque_leaf_label();
        }
    });

    $("#cheque_book_start_serial_number").on('change', function() {

        var url = "<?= base_url(); ?>cheque_book/validate_start_serial_number";
        var data = {
            'office_bank_id': $("#fk_office_bank_id").val(),
            'start_serial_number': $(this).val()
        };
        var item_has_declined_state = '<?php $generalLibrary = new App\Libraries\System\GrantsLibrary(); $generalLibrary->itemHasDeclinedState(hash_id($id, 'decode'), 'cheque_book') ?>';
        const cheque_book_start_serial_number = $(this);

        $.post(url, data, function(last_book_max_serial) {
            //alert(item_has_declined_state);
            if (last_book_max_serial > 0 && !item_has_declined_state) {
                alert("Start serial number MUST be equal to " + last_book_max_serial);
                cheque_book_start_serial_number.val("")
            }
        });

    });

    function last_cheque_leaf_label() {


        var start_serial = $('#cheque_book_start_serial_number').val();

        var leave_count = parseInt($("#cheque_book_count_of_leaves").val());

        // alert(start_serial)
        // alert(leave_count)

        // if(leave_count==0){
        //     alert('<?=get_phrase('leave_count', "Kindly select the Count leave");?>');

        //     $('#cheque_book_count_of_leaves').css("border", "2px solid red").select2();

        //     return false;

        // }

        let office_bank_id = $('#fk_office_bank_id').val();

        var url = '<?= base_url() ?>Cheque_book/get_office_chequebooks/' + office_bank_id;

        $.get(url, function(respose) {


            let record = parseInt(respose);

            if (record > 0) {

                if (parseInt(start_serial) > 0 && parseInt(leave_count) > 0) {

                    let last_leaf = parseInt(start_serial) + (parseInt(leave_count) - 1);

                    $('#cheque_book_last_serial_number_id').attr('value', last_leaf);

                } else {
                    alert('<?= get_phrase("error_in_cheque_book_start_serial",'Start Serial And Count Of Leaves Must Be Greater Than Zero'); ?>');

                    return false;
                }

            } else {

                let last_leaf = parseInt(start_serial) + (parseInt(leave_count) - 1);

                $('#cheque_book_last_serial_number_id').prop('value', last_leaf);
            }


        });




        // $('#last_leaf_label').find('input').val(last_leaf);

        // var cheque_book_count_of_leaves_form_group = $("#cheque_book_count_of_leaves").closest('.form-group');

        // if(start_serial > 0 && leave_count > 0){

        //     var last_leaf = parseInt(start_serial) + (parseInt(leave_count) - 1)

        //     if(!$("#last_leaf_form_group").length){
        //         cheque_book_count_of_leaves_form_group.after('<div class="form-group" id="last_leaf_form_group"><label class="col-xs-3 control-label"><?= get_phrase('cheque_book_last_serial_number'); ?></label><div class="col-xs-9" id="last_leaf_label" style="color:red;"><input type="number" class="form-control" readonly value="'+last_leaf+'"/></div></div>');
        //     }else{
        //         $('#last_leaf_label').find('input').val(last_leaf);
        //     }
        // }

    }

    function on_record_post() {

        const office_bank_id = $("#fk_office_bank_id").val();
        
        var url = '<?= base_url() ?>Cheque_book/get_max_id_cheque_book_for_office/' + office_bank_id;

        $.get(url, function(hashedId) {

            alert('You\'ll be taken to a page to submit the cheque book you have created');

            window.location.href = '<?= base_url() ?>cheque_book/view/' + hashedId;

        });

        return false;
    }

    $(document).ready(function() {
        const action = "<?= $action; ?>";

        if (action == 'edit') {
            //$('#cheque_book_start_serial_number').prop('readonly','readonly');
        }

    });
    
    $(document).on('click','.item_action', function () {

        let item_id = $(this).data('item_id');

        let url = "<?=base_url();?>cheque_book/redirect_to_voucher_after_approval/" + item_id;

        $.get(url, function (response) {


            if(response){
                let redirect_to_voucher_form = "<?=base_url();?>voucher/multi_form_add";
                window.location.replace(redirect_to_voucher_form);
            }
        })
    })
</script>