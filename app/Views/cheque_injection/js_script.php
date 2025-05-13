<script>
    $("#cheque_injection_number, #fk_office_bank_id").on("change", function() {
        let cheque_injection_number_elem = $("#cheque_injection_number");
        let cheque_injection_number = $("#cheque_injection_number").val();
        let office_bank_id = $("#fk_office_bank_id").val();
        let data = {
            'cheque_number': cheque_injection_number,
            "office_bank_id": office_bank_id
        };

        if (cheque_injection_number == "" || cheque_injection_number <= 0) {

            if($(this).attr('id') != 'fk_office_bank_id'){
                alert('<?=get_phrase("cheque_number_greater_zero_alert","Cheque number must be NOT zero")?>');
                $(this).val('');
                return false;
            }
            
        } else {
            // alert('Hello')
            let cheque_number_is_valid_url = '<?= base_url(); ?>ajax/cheque_injection/chequeToBeInjectedExistsInRange';
            // Check if in range and checks have not be used or they have been used as opening outstanding cheques
            $.post(cheque_number_is_valid_url, data, function(responseObj) {
                if (!responseObj.is_injectable) {
                    alert(responseObj.message)
                    disable_or_enable_save_btns('disable', cheque_injection_number_elem);
                } else {
                    disable_or_enable_save_btns('enable', cheque_injection_number_elem);
                }
            });
        }
    });
    
    //Disable fields
    function disable_or_enable_save_btns(disable_or_enabled, elem) {
        if (disable_or_enabled == 'disable') {
            $(elem).css('border-color', 'red');
            $(elem).val('');
            $('.save').prop('disabled', 'disabled');
            $('.save_new').prop('disabled', 'disabled');
        } else if (disable_or_enabled == 'enable') {
            $(elem).css('border-color', '');
            $('.save').removeAttr('disabled');
            $('.save_new').removeAttr('disabled');
        }
    }

</script>