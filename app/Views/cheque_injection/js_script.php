<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/*
 *    @author     : Livingstone Onduso
 *    @date        : 24th April, 2022
 *    Finance management system for NGOs
 *    Londuso@ke.ci.org
 */
?>
<script>
    //New Code added by Onduso on 13/06/2023
    //$(document).ready(function() {
    $("#cheque_injection_number, #fk_office_bank_id").on("change", function() {

        var cheque_injection_number_elem = $("#cheque_injection_number");
        var cheque_injection_number = $("#cheque_injection_number").val();
        var office_bank_id = $("#fk_office_bank_id").val();


        var data = {
            'cheque_number': cheque_injection_number,
            "office_bank_id": office_bank_id
        };

        if ($(this).attr('id') == "cheque_injection_number" && (cheque_injection_number == "" || cheque_injection_number <= 0)) {
            
            alert('<?=get_phrase("cheque_number","Cheque number must be NOT zero")?>');

            $(this).val('');

            return false;
        } else {

            let cheque_number_is_valid_url = '<?= base_url(); ?>cheque_injection/cheque_to_be_injected_exists_in_range/' + office_bank_id + "/" + cheque_injection_number;

            // Check if in range and checks have not be used or they have been used as opening outstanding cheques

            $.get(cheque_number_is_valid_url, function(valid_chq_response) {

                // console.log(valid_chq_response);
                //return false;
                let responseObj = JSON.parse(valid_chq_response);

                if (!responseObj.is_injectable) {

                    //Show message, color the field red and then disable the save buttons
                    // alert('The Cheque Number: ' + cheque_injection_number + " is either used or is in opening outstanding");
                    alert(responseObj.message)
                    disable_or_enable_save_btns('disable', cheque_injection_number_elem);

                } else {

                    disable_or_enable_save_btns('enable', cheque_injection_number_elem);
                }

            });

        }

        
    });
    ///});
    //Disable fields
    function disable_or_enable_save_btns(disable_or_enabled, elem) {

        if (disable_or_enabled == 'disable') {

            $(elem).css('border-color', 'red');

            $(elem).val('');

            $('.save').prop('disabled', 'disabled');

            $('.save_new').prop('disabled', 'disabled');

            //return false;
        } else if (disable_or_enabled == 'enable') {
            $(elem).css('border-color', '');

            $('.save').removeAttr('disabled');

            $('.save_new').removeAttr('disabled');
        }

    }


    //End of Addition

    /*
     //OLD Code
     $(document).ready(function() {
         $("#cheque_injection_number, #fk_office_bank_id").on("change", function() {

             var cheque_injection_number_elem = $("#cheque_injection_number");
             var cheque_injection_number = $("#cheque_injection_number").val();
             var office_bank_id = $("#fk_office_bank_id").val();


             if (!office_bank_id && cheque_injection_number) {
                 alert("Kindly choose a bank account");
             } else if (office_bank_id && cheque_injection_number) {
                 //Check
                 var url = "<?= base_url(); ?>cheque_injection/validate_cheque_number_is_cancelled";
                 var data = {
                     'cheque_number': cheque_injection_number,
                     "office_bank_id": office_bank_id
                 };

                 $.post(url, data, function(response) {

                     if (response == 0) {
                         alert('Duplicate injected cheque
                          ' + cheque_injection_number + ' found or leaf is in a used cheque book');
                         cheque_injection_number_elem.val("");
                     }
                 });
             }
         });
     });*/
</script>