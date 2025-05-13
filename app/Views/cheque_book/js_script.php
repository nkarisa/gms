<?php 
    $generalLibrary = new App\Libraries\System\GrantsLibrary();
?>

<script>
    $("#fk_office_bank_id").on('change', function() {
        const url = "<?= base_url(); ?>ajax/cheque_book/newChequeBookStartSerial";
        const office_bank_id = $(this).val()
        const data = {
            'office_bank_id': office_bank_id
        };
        
        const url_active_cheques = '<?= base_url() ?>ajax/cheque_book/getActiveChequeBooks/' + office_bank_id;

        if(!office_bank_id){
            return false;
        }

        $.get(url_active_cheques, function(response) {
            let active_chqs = parseInt(response.next_new_cheque_book_start_serial);
            //Cheque if the active cheques exists if so don't create another one otherwise create a new one
            if (active_chqs > 0) {
                alert('<?= get_phrase("active_cheque_book_error",'You Still Have An Active Chequebook For This Bank Account And Can Not Add Another One');?>');
                $('#fk_office_bank_id').val($('#fk_office_bank_id option:eq(0)').val()).trigger('change');
            } else {
                $.post(url, data, function(response) {
                    if (response.next_new_cheque_book_start_serial > 0) {
                        $("#cheque_book_start_serial_number").val(response.next_new_cheque_book_start_serial);
                        $("#cheque_book_start_serial_number").prop('readonly', 'readonly');
                    } else {
                        $("#cheque_book_start_serial_number").val("");
                        $("#cheque_book_start_serial_number").removeAttr('readonly');
                    }
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

        const url = "<?= base_url(); ?>ajax/cheque_book/validateStartSerialNumber";
        const data = {
            'office_bank_id': $("#fk_office_bank_id").val(),
            'start_serial_number': $(this).val()
        };
        const item_has_declined_state = '<?=$generalLibrary->itemHasDeclinedState(hash_id($id, 'decode'), 'cheque_book') ?>';
        const cheque_book_start_serial_number = $(this);

        $.post(url, data, function(response) {
            if (response.validate_start_serial_number > 0 && !item_has_declined_state) {
                alert("Start serial number MUST be equal to " + response.validate_start_serial_number);
                cheque_book_start_serial_number.val("")
            }
        });

    });

    function last_cheque_leaf_label() {
        const start_serial = $('#cheque_book_start_serial_number').val();
        const leave_count = parseInt($("#cheque_book_count_of_leaves").val());
        const office_bank_id = $('#fk_office_bank_id').val();
        const url = '<?= base_url() ?>ajax/cheque_book/getOfficeChequeBooks/' + office_bank_id;

        $.get(url, function(response) {
            let record = parseInt(response.count_cheque_books);

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
    }

    $(document).ready(function() {
        const action = "<?= $action; ?>";

        if (action == 'edit') {
            //$('#cheque_book_start_serial_number').prop('readonly','readonly');
        }

    });
    
    $(document).on('click','.item_action', function () {
        let item_id = $(this).data('item_id');
        let url = "<?=base_url();?>ajax/cheque_book/redirectToVoucherAfterApproval/" + item_id;
        $.get(url, function (response) {
            if(response.redirect){
                let redirect_to_voucher_form = "<?=base_url();?>voucher/multiFormAdd";
                window.location.replace(redirect_to_voucher_form);
            }
        })
    })
</script>