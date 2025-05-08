<?php


//Draws Medical Form or Medical form with other reimbursables e.g MED-TFI
include "add_form_html.php";


$reimbursement_claim_app_id = count($reimbursement_app_types) == 1 ? array_keys($reimbursement_app_types)[0] : '';

?>

<script>
    var re_load = '<?= base_url(); ?>reimbursement_claim/single_form_add';

    //Checkbox
    $(document).ready(function () {
        //set initial state.

        $('#govt_insurance_checkbox_id').change(function () {
            if (this.checked) {
                $('#reimbursement_claim_govt_insurance_number').removeClass('hidden');

                $('#health_card_id').removeClass('hidden');

                $('#reimbursement_claim_govt_insurance_number').removeAttr('disabled');

                $('#reimbursement_claim_govt_insurance_number').addClass('required');
            } else {
                $('#reimbursement_claim_govt_insurance_number').val('');

                $('#reimbursement_claim_govt_insurance_number').addClass('hidden');

                $('#health_card_id').addClass('hidden')

                //$('#reimbursement_claim_govt_insurance_number').attr('disabled', 'disabled');

                $('#reimbursement_claim_govt_insurance_number').removeClass('required');

                if ($('#fk_voucher_detail_id').val() == 0) {
                    $('#total_receipt_amount').attr('disabled', 'disabled');

                }
                //clear fields to be re-calculated
                $('#reimbursement_claim_caregiver_contribution').val('');

                $('#amount_to_claim').val('');

                $('#total_receipt_amount').val('');

                $('#total_receipt_amount').attr('disabled', 'disabled');

                $('#fk_voucher_detail_id').val(0).select2(); //refresh select2

                $('#reimbursement_claim_amount_spent').val('');

            }
        });
    });
    //Switch Claim Type
    $('#fk_reimbursement_app_type_id').on('change', function (event) {

        $('#reimbursement_claim_caregiver_contribution').val('');

        $('#total_receipt_amount').val('');

        //$('#total_receipt_amount').val('');

        let medical_claim_type = $('#fk_reimbursement_app_type_id option:selected').text();

        //alert(medical_claim_type);
        //If value<>0
        if (parseInt(medical_claim_type) != 0) {

            $('#fk_voucher_detail_id').removeAttr('disabled', 'disabled');

        } else {
            let element = $('#fk_voucher_detail_id');

            element.attr('disabled', 'disabled');

            element.val() = '';
        }
        //Expose hidden elements for if medical claim OR HVC CPR or MED-TFI
        hide_fields_based_claim_type(medical_claim_type);

        event.preventDefault();
    });


    function draw_med_tfi_fields() {
        let html_tag = "<label class='col-xs-2 control-label '>Diagnosis</label>";
        html_tag = html_tag + "<div class='col-xs-4'>";

        html_tag = html_tag + " <textarea class='form-control required ' name='reimbursement_claim_diagnosis' id='reimbursement_claim_diagnosis' placeholder='Describe the condition'></textarea> </div>";

        return html_tag

    }

    function draw_hvc_cpr_fields() {


        //Select Tag for Vulnerability
        var html_tag = "<label class='col-xs-2 control-label'>Vulnerability</label> <div class='col-xs-4'>";

        html_tag = html_tag + "<select class='form-control required' id='vulnerability_id' name='vulnerability'>";

        html_tag = html_tag + "<option value='0'>Select vulnerability</option>";
        html_tag = html_tag + " <option value='Abuse'>Abuse</option>";
        html_tag = html_tag + "<option value='Neglect '>Neglect</option>";
        html_tag = html_tag + "<option value='Disaster'>Disaster</option>";
        html_tag = html_tag + "<option value='Abandonment'>Abandonment</option>";
        html_tag = html_tag + "<option value='Caregiver incapacitation'>Caregiver incapacitation</option>";
        html_tag = html_tag + "<option value='Death of Caregiver'>Death of Caregiver</option>";
        html_tag = html_tag + "<option value='Education needs'>Education needs</option>";

        html_tag = html_tag + "</select></div>";

        //Select Tag for Intervention
        var html_tag1 = "<label class='col-xs-2 control-label'>Intervention</label> <div class='col-xs-4'>";

        html_tag1 = html_tag1 + "<select class='form-control required' id='intervention_id' name='intervention'>";

        html_tag1 = html_tag1 + "<option value='0'>Select Intervention</option>";
        html_tag1 = html_tag1 + " <option value='School items'>School items</option>";
        html_tag1 = html_tag1 + "<option value='Counselling'>Counselling</option>";
        html_tag1 = html_tag1 + "<option value='Legal costs'>Legal costs</option>";
        html_tag1 = html_tag1 + "<option value='Food items'>Food items</option>";
        html_tag1 = html_tag1 + "<option value='Household items'>Household items</option>";
        html_tag1 = html_tag1 + "<option value='Transport'>Transport</option>";
        html_tag1 = html_tag1 + "<option value='Other'>Other</option>";

        html_tag1 = html_tag1 + "</select></div>";

        return html_tag + html_tag1;

    }

    // $(document).on('change', '#vulnerability_id',function(){
    //     alert($(this).val());

    // });

    function hide_fields_based_claim_type(medical_type) {

        //Make this fiels NOT required if MED-TFI claim
        //$('#reimbursement_claim_diagnosis').removeClass('required');

        if (medical_type == 'HVC-CPR') {

            hide_medical_claim_related_fields();


            $('#transaction_date').html("<label class='col-xs-2 control-label'>Intervention Date</label>");

            $('#diagnosis_area_id').html('');

            let html_tag = draw_hvc_cpr_fields();

            $('#diagnosis_area_id').html(html_tag);

        } else if (medical_type == 'MED-TFI' || medical_type == 'CIV-MEDICAL') {

            let date = medical_type == 'MED-TFI' ? 'Transaction Date' : 'Treatment Date';

            $('#transaction_date').html("<label class='col-xs-2 control-label'>" + date + "</label>");

            $('#diagnosis_area_id').html('');

            hide_medical_claim_related_fields();

            let html_tag = draw_med_tfi_fields();

            $('#diagnosis_area_id').html(html_tag);

        } else if (medical_type == 'MEDICAL-CLAIM') {

            //remove_HVC_CPR_fields_from_dom();
            $('#diagnosis_area_id').html('');


            $('.for_medical_type').each(function (index, elem) {
                $(elem).removeClass('hidden');
            });

            //Rewrite the date to treatment date

            $('#transaction_date').html("<label class='col-xs-2 control-label'>Treatment Date</label>");

            //Make this fiels required if MED-TFI claim
            //$('#reimbursement_claim_diagnosis').addClass('required');
            var diagnosis_area_tags = "<?= $rcHelper->drawDiagnosisArea(); ?>";

            $('#diagnosis_area_id').html(diagnosis_area_tags);

            $('#medical_claim_diagnosis_category').removeClass('select2'); //[Sometimes the Select2 class makes the dropdown not to draw therefore add this line]

            $('#reimbursement_claim_facility').addClass('required');


            //$('#reimbursement_claim_govt_insurance_number').addClass('required');

            $('#support_documents_need_flag').addClass('required');

            $('#reimbursement_claim_incident_id').addClass('required');

            $('#fk_health_facility_id').addClass('required');
        }


    }

    function hide_medical_claim_related_fields() {
        $('#reimbursement_claim_facility').removeClass('required');

        //$('#reimbursement_claim_govt_insurance_number').removeClass('required');

        $('#support_documents_need_flag').removeClass('required');

        $('#reimbursement_claim_incident_id').removeClass('required');

        $('#fk_health_facility_id').removeClass('required');

        $('#reimbursement_claim_govt_insurance_number').removeClass('required');
        $('#reimbursement_claim_govt_insurance_number').addClass('hidden');
        $('#health_card_id').addClass('hidden');
        $('#govt_insurance_checkbox_id').prop("checked", false);

        //Hide medical reimbursement fields
        $('.for_medical_type').each(function (index, elem) {
            $(elem).addClass('hidden');
        });
    }


    //Populate the support_needed_docs flag on hidden field
    $('#fk_health_facility_id').on('change', function () {

        var hidden_input_health_facility_id = $(this).val();

        var url = '<?= base_url(); ?>ajax/reimbursement_claim/get_support_needed_docs_flag/' + hidden_input_health_facility_id;

        $.get(url, function (response) {

            $('#support_documents_need_flag').attr('value', response);

        });

    });
    //Populate cluster based on the fcp selections
    $('#fk_office_id').on('change', function () {
        var fcp_id = $(this).val();

        var url = '<?= base_url(); ?>ajax/reimbursement_claim/populate_cluster_name/' + fcp_id;

        $.get(url, function (response) {

            let response_obj = JSON.parse(response);

            console.log(response_obj);

            $.each(response_obj, function (key, value) {
                //let split_name = value.split('Context for office')[1];

                $('#cluster_name').prop('value', value);

                $('#fk_context_cluster_id').prop('value', key);
            })

        });

    })
    //Populate the Beneficiary name on change of beneficiary number
    $('#fk_beneficiary_id').on('change', function () {


        var beneficiary_no_and_name = this.options[this.selectedIndex].innerText;

        var beneficiary_no_and_name_arr = beneficiary_no_and_name.split('-');

        if (beneficiary_no_and_name_arr.length > 0) {

            $('#reimbursement_claim_name').attr('value', beneficiary_no_and_name_arr[1]);

            $('#reimbursement_claim_beneficiary_number').attr('value', beneficiary_no_and_name_arr[0]);
        }

    });
    //Modify the connect incident number
    $('#reimbursement_claim_incident_id').change(function (ev) {

        $(this).prop('type', 'text');
        var str = "I-";
        var input = $(this).val();

        $(this).val(str + input);

        //Check if Connect Incident ID exists
        let url = '<?= base_url() ?>ajax/reimbursement_claim/check_if_connect_id_exists/' + input;

        $.get(url, function (res) {

            if (parseInt(res) === 1) {

                alert('The Connect incident ID: ' + str + input + ' Exists');

                $('#reimbursement_claim_incident_id').css('border-color', 'red');

                $('#reimbursement_claim_incident_id').val('');

                return false;
            }

        });

        ev.preventDefault();


    });
    //Clear the connect incident number on focus
    $('#reimbursement_claim_incident_id').focus(function () {
        $(this).prop('type', 'number');
        $(this).val("");
    });

    //New way to compute caregiver contribution

    $('#total_receipt_amount').change(function (evt) {

        compute_claim_reimbursement($(this));

        evt.preventDefault();

    });

    //DISABLE and Enable the caregiver insurance
    $('#reimbursement_claim_govt_insurance_number').on('change', function (ev) {

        if (($(this).val() != '' && $(this).val() != 0) && parseInt($('#fk_voucher_detail_id').val()) != 0) {
            $('#total_receipt_amount').removeAttr('disabled');
        } else {
            $('#total_receipt_amount').attr('disabled', 'disabled');
        }
        //On change when the total_receipt_amount is NOT=''
        //var amount=parseFloat($('#total_receipt_amount').val());
        if ($('#total_receipt_amount').val() != '') {

            var elem = $('#total_receipt_amount');

            compute_claim_reimbursement(elem);

            ev.preventDefault();

        }


    });

    function compute_claim_reimbursement(elem) {
        //Values to be passed

        let total_claimable_amount = $('#reimbursement_claim_amount_spent').val();

        let split_by_country_currency = '<?= $country_currency_code; ?>'

        //Split the currency
        amount_and_voucher_no_arr = total_claimable_amount.split(split_by_country_currency);

        //console.log(amount_and_voucher_no_arr[2]);
        //find the total_claimable_balance field and populate

        let amount_balance = amount_and_voucher_no_arr.length > 2 ? amount_and_voucher_no_arr[2] : amount_and_voucher_no_arr[1];

        let balance_amount_on_voucher = parseFloat(amount_balance.replace(/,/g, '')) // $('#reimbursement_claim_amount_spent').val();

        var total_receipt_amount = parseFloat($(elem).val());


        // //Check if total_receipt_amount is greater than
        // if (total_receipt_amount > balance_amount_on_voucher) {
        //     alert('Amount to claim- ' + split_by_country_currency + total_receipt_amount + ' ' + "is more than total claimable balance: " + split_by_country_currency + balance_amount_on_voucher);
        //     return false;
        // }

        //Get the medical claims scenerios and  proceed with claim
        let insurance_card = $('#reimbursement_claim_govt_insurance_number').val();

        let medical_claim_app = $('#fk_reimbursement_app_type_id option:selected').text();

        var url = '<?= base_url(); ?>ajax/reimbursement_claim/medical_claim_scenerios/' + balance_amount_on_voucher + '/' + total_receipt_amount + '/' + insurance_card;

        $.get(url, function (response) {

            let obj_resp = JSON.parse(response);
            console.log(obj_resp);


            //Computation values in the array
            let computation_array = obj_resp.computations;

            //Setting arrays
            let setting_array = obj_resp.settings;

            $('#hold_threhold_amount').attr('value', setting_array.threshold_amount);
            $('#hold_reimburse_all_when_threshold_met_flag').attr('value', setting_array.reimburse_all_when_threshold_met);

            //Compute reimbursed amount when threshold is substracted from amount to claim
            //Check if reimburse_all so that you calculate the reimbursement amount
            // if (setting_array.reimburse_all_when_threshold_met == false) {
            //     total_receipt_amount = total_receipt_amount - setting_array.threshold_amount;
            // }


            // if ($('#fk_reimbursement_app_type_id').length > 0) {

            //     if (medical_claim_app === 'MEDICAL-CLAIM') {

            //        total_receipt_amount = total_receipt_amount-setting_array.threshold_amount;
            //     } else {
            //         total_receipt_amount = total_receipt_amount;
            //     }

            // } else {
            //     total_receipt_amount = total_receipt_amount - setting_array.threshold_amount;

            // }


            // if (setting_array.reimburse_all_when_threshold_met == false  && $('#fk_reimbursement_app_type_id').length>0 && $('#fk_reimbursement_app_type_id option:selected').text() === 'MEDICAL-CLAIM') {
            //     total_receipt_amount = total_receipt_amount - setting_array.threshold_amount;

            //     //$('#total_receipt_amount').prop('value',$('#total_receipt_amount').val()+ '(LESS '+setting_array.threshold_amount+') Threshold Reimbursable amount');
            // }
            // else if(setting_array.reimburse_all_when_threshold_met == false){
            //    total_receipt_amount = total_receipt_amount - setting_array.threshold_amount;
            // }


            //check amount
            voucher_amount_plus_caregiver_contribution = balance_amount_on_voucher + parseFloat(computation_array.contribution_amount);

            if ((total_receipt_amount != balance_amount_on_voucher) && medical_claim_app != 'MEDICAL-CLAIM' && medical_claim_app != '') {
                alert('Total Receipt Amount Not equal the voucher amount to be claimed ');

                return false;
            }


            //Check if Total Receipt = voucher amount and setting_array.allow_use_insurance_card=true
            if (((total_receipt_amount == balance_amount_on_voucher) && (setting_array.allow_use_insurance_card && insurance_card == '')) && medical_claim_app === 'MEDICAL-CLAIM') {
                alert('Total Receipt Amount equals the voucher amount but you did not provide Insurance Card Number. Provide to proceed ');

                clear_caregiver_amount_to_claim_fields();

                return false;

            } else if ((Math.trunc(total_receipt_amount) != Math.trunc(voucher_amount_plus_caregiver_contribution)) && medical_claim_app === 'MEDICAL-CLAIM') {

                alert('Total Receipt Amount of:' + total_receipt_amount + ' is NOT equal to total receipt voucher amount of :' + voucher_amount_plus_caregiver_contribution);

                clear_caregiver_amount_to_claim_fields();

                return false;

            } else if ((Math.trunc(total_receipt_amount) != Math.trunc(voucher_amount_plus_caregiver_contribution)) && medical_claim_app === '') {
                //This Malawi case or country with medical app alone
                alert('Total Receipt Amount of:' + total_receipt_amount + ' is NOT equal to total receipt voucher amount of :' + voucher_amount_plus_caregiver_contribution);

                clear_caregiver_amount_to_claim_fields();

                return false;
            } else {

                let amount_reimbursable = Math.trunc(total_receipt_amount) - Math.trunc(parseFloat(computation_array.contribution_amount));

                let caregiver_contribution = Math.trunc(parseFloat(computation_array.contribution_amount));

                //With TF
                if (medical_claim_app != 'MEDICAL-CLAIM') {

                    amount_reimbursable = total_receipt_amount;

                    caregiver_contribution = 0;
                }
                //Computed values for caregiver contribution, reimbursed amount
                $('#reimbursement_claim_caregiver_contribution').prop('value', split_by_country_currency + ' ' + caregiver_contribution.toFixed(2));

                $('#amount_to_claim').prop('value', split_by_country_currency + ' ' + amount_reimbursable.toFixed(2));
            }
        });
    }

    //Clear fields
    function clear_caregiver_amount_to_claim_fields() {

        $('#reimbursement_claim_caregiver_contribution').val('');
        $('#amount_to_claim').val('');
        $('#total_receipt_amount').val('');
    }

    //On select of voucher number

    $(document).on('change', '#fk_voucher_detail_id', function () {
        //Clear computed fields
        //$('#reimbursement_claim_caregiver_contribution').val('');

        clear_caregiver_amount_to_claim_fields();

        var amount_and_voucher_no_arr = [];

        var national_health_cover_card = <?= sizeof($national_health_cover_flag) > 0 ? $national_health_cover_flag[0] : 0; ?>

        if (parseInt($(this).val()) > 0) {
            //Get the selected text and populate the total claimable field

            let selected_voucher_and_amount = this.options[this.selectedIndex].innerText;

            let split_by_country_currency = '<?= $country_currency_code; ?>'

            //Split the currency
            amount_and_voucher_no_arr = selected_voucher_and_amount.split(split_by_country_currency);

            //alert(amount_and_voucher_no_arr[2]);
            //find the total_claimable_balance field and populate

            let amount_balance = amount_and_voucher_no_arr.length > 2 ? amount_and_voucher_no_arr[2] : amount_and_voucher_no_arr[1];


            //fetch the voucher number. Remove the the hyphen character and store the vnumber
            let remove_hyphen_from_voucher_number = amount_and_voucher_no_arr[0].replace(/-/g, "");

            let store_voucher_number = parseInt(remove_hyphen_from_voucher_number);

            // $('#claim_voucher_number').attr('value',store_voucher_number);

            $('#reimbursement_claim_amount_spent').prop('value', split_by_country_currency + " " + amount_balance);

            let checkbox_checked = $('#govt_insurance_checkbox_id').is(":checked");


            if (national_health_cover_card == 0 || national_health_cover_card == '') {
                $('#total_receipt_amount').removeAttr('disabled');
            } else {


                //With TF
                if ($("#fk_reimbursement_app_type_id").val() == 'MED-TFI') {

                    $('#total_receipt_amount').removeAttr('disabled');
                } else {


                    let health_cover_number_field = $('#reimbursement_claim_govt_insurance_number').val();


                    if (((health_cover_number_field == 0 || health_cover_number_field == '') && !checkbox_checked && parseInt(national_health_cover_card) === 1) || (checkbox_checked && (health_cover_number_field != 0 && health_cover_number_field != ''))) {

                        $('#total_receipt_amount').removeAttr('disabled');
                        //alert('Yesy');
                    } else {


                        $('#total_receipt_amount').attr('disabled', 'disabled');

                    }

                }
            }


        } else {


            //$('#reimbursement_claim_govt_insurance_number').attr('disabled', 'disabled');

            $('#reimbursement_claim_govt_insurance_number').val('');

            $('#total_receipt_amount').val('');

            $('#reimbursement_claim_caregiver_contribution').val('');

            $('#amount_to_claim').val('');

            $('#reimbursement_claim_amount_spent').val('');

            // $('#claim_voucher_number').val('')

            //if (national_health_cover_card == 0 || national_health_cover_card == '') {
            $('#total_receipt_amount').attr('disabled', 'disabled');
            //}

        }


    });

    $(document).on('change', '#fk_voucher_detail_id', function () {

        //Clear computed fields
        clear_caregiver_amount_to_claim_fields();

        var amount_and_voucher_no_arr = [];

        var national_health_cover_card = <?= sizeof($national_health_cover_flag) > 0 ? $national_health_cover_flag[0] : 0; ?>

        if (parseInt($(this).val()) > 0) {
            //Get the selected text and populate the total claimable field

            let selected_voucher_and_amount = this.options[this.selectedIndex].innerText;

            let split_by_country_currency = '<?= $country_currency_code; ?>'

            //Split the currency
            amount_and_voucher_no_arr = selected_voucher_and_amount.split(split_by_country_currency);

            //alert(amount_and_voucher_no_arr[2]);
            //find the total_claimable_balance field and populate

            let amount_balance = amount_and_voucher_no_arr.length > 2 ? amount_and_voucher_no_arr[2] : amount_and_voucher_no_arr[1];


            //fetch the voucher number. Remove the the hyphen character and store the vnumber
            let remove_hyphen_from_voucher_number = amount_and_voucher_no_arr[0].replace(/-/g, "");

            let store_voucher_number = parseInt(remove_hyphen_from_voucher_number);

            // $('#claim_voucher_number').attr('value',store_voucher_number);

            $('#reimbursement_claim_amount_spent').prop('value', split_by_country_currency + " " + amount_balance);

            let checkbox_checked = $('#govt_insurance_checkbox_id').is(":checked");


            if (national_health_cover_card == 0 || national_health_cover_card == '') {
                $('#total_receipt_amount').removeAttr('disabled');
            } else {


                //With TF
                if ($("#fk_reimbursement_app_type_id").val() == 'MED-TFI') {

                    $('#total_receipt_amount').removeAttr('disabled');
                } else {


                    let health_cover_number_field = $('#reimbursement_claim_govt_insurance_number').val();


                    if (((health_cover_number_field == 0 || health_cover_number_field == '') && !checkbox_checked && parseInt(national_health_cover_card) === 1) || (checkbox_checked && (health_cover_number_field != 0 && health_cover_number_field != ''))) {

                        $('#total_receipt_amount').removeAttr('disabled');
                        //alert('Yesy');
                    } else {


                        $('#total_receipt_amount').attr('disabled', 'disabled');

                    }

                }
            }


        } else {


            //$('#reimbursement_claim_govt_insurance_number').attr('disabled', 'disabled');

            $('#reimbursement_claim_govt_insurance_number').val('');

            $('#total_receipt_amount').val('');

            $('#reimbursement_claim_caregiver_contribution').val('');

            $('#amount_to_claim').val('');

            $('#reimbursement_claim_amount_spent').val('');

            // $('#claim_voucher_number').val('')

            //if (national_health_cover_card == 0 || national_health_cover_card == '') {
            $('#total_receipt_amount').attr('disabled', 'disabled');
            //}

        }


    });


    //Save Data

    $(".btn-save,.btn-save-new").on('click', function (ev) {

        // alert($('#reimbursement_claim_diagnosis').val());


        //alert($('#vulnerability_id').val());
        //Check if amount is reimbursable. Must be greater than the set threshold
        let reimbursable_amt = $('#amount_to_claim').val();

        let country_currency_code = '<?= $country_currency_code; ?>';

        let amount_to_reimburse = parseInt(reimbursable_amt.split(country_currency_code)[1]);

        let threshold = parseInt($('#hold_threhold_amount').val());

        let reimburse_all_when_threshold_met_flag = $('#hold_reimburse_all_when_threshold_met_flag').val();


        let amount_is_met = threshold > amount_to_reimburse ? true : false;

        let claim_type = $('#fk_reimbursement_app_type_id').val();

        if (amount_is_met && (claim_type != 'MED-TFI' || claim_type != 'CI-MEDICAL' || claim_type != 'HVC-CPR') && reimburse_all_when_threshold_met_flag == true) {
            //alert(claim_type);
            alert('Reimbursable amount of ' + country_currency_code + ' ' + amount_to_reimburse + ' is below your country threshold of ' + country_currency_code + ' ' + threshold); // less than the set threshold/minimum reimbursable amount');
            return false;
        } else if (amount_to_reimburse <= 0) {

            alert('<?= get_phrase('reimburse_amount_error', 'Reimbursable Can not Zero or less'); ?>');

            return false;

        }


        //[POST THE CLAIM]

        //Check if to redirect to list or remain on add form
        var go_to_list_of_medical_claims = true

        if ($(this).hasClass('btn-save-new')) {

            go_to_list_of_medical_claims = false;

        }

        //Validate the form before saving
        //console.log(validate_form());

        // if(validate_form()==true){
        //     console.log('Yes');
        // }

        // return false;


        if (validate_form()) {

            alert('You have some fields missing. Please complete them higligeted in red');
            return false;

        } else {

            var url = "<?= base_url(); ?>ajax/reimbursement_claim/add_reimbursement_claim";
            var btn = $(this);

            //Get Diagnosis record from the form field[diagnosis category or vulnerability/Interevention]
            let diagnosis = '';
            if ($('#reimbursement_claim_diagnosis').length) {
                diagnosis = $('#reimbursement_claim_diagnosis').val();
            } else if ($('#medical_claim_diagnosis_category').length) {
                diagnosis = $('#medical_claim_diagnosis_category option:selected').text();
            } else if ($('#vulnerability_id').length) {

                let vulnerability = $('#vulnerability_id').val();

                let intervention = $('#intervention_id').val();

                diagnosis = vulnerability + "[" + intervention + "]";
            }


            //reimbursement_claim_funding_type
            let reimbursement_app_text = $('#fk_reimbursement_app_type_id option:selected').text();

            let reimbursement_funding_type_length = $('#fk_reimbursement_funding_type_id').length;

            let fk_reimbursement_funding_type_id = reimbursement_app_text == 'MEDICAL-CLAIM' || reimbursement_funding_type_length ? $('#fk_reimbursement_funding_type_id').val() : 'NULL';

            //Pass reimbursement type id for the app if size of array is 1

            let reimbursement_app_id = '<?= $reimbursement_claim_app_id; ?>';

            //Check if app type id is empty or with value then reassign fk_reimbursement_app_type_id with the value
            let reimbursement_type = reimbursement_app_id == '' ? $('#fk_reimbursement_app_type_id').val() : reimbursement_app_id;

            // alert(reimbursement_type);
            // return false;

            var data = {
                fk_office_id: $('#fk_office_id').val(),
                fk_reimbursement_app_type_id: reimbursement_type,
                fk_reimbursement_funding_type_id: fk_reimbursement_funding_type_id,
                reimbursement_claim_beneficiary_number: $('#reimbursement_claim_beneficiary_number').val(),
                reimbursement_claim_name: $('#reimbursement_claim_name').val(),
                reimbursement_claim_treatment_date: $('#reimbursement_claim_treatment_date').val(),
                reimbursement_claim_facility: $('#reimbursement_claim_facility').val(),
                fk_health_facility_id: $('#fk_health_facility_id').val(),
                fk_context_cluster_id: $('#fk_context_cluster_id').val(),
                support_documents_need_flag: $('#support_documents_need_flag').val(),
                reimbursement_claim_incident_id: $('#reimbursement_claim_incident_id').val(),
                fk_voucher_detail_id: $('#fk_voucher_detail_id').val(),
                reimbursement_claim_diagnosis: diagnosis,
                reimbursement_claim_govt_insurance_number: $('#reimbursement_claim_govt_insurance_number').val(), //can be empty if no govt insurance
                reimbursement_claim_caregiver_contribution: $('#reimbursement_claim_caregiver_contribution').val(),
                reimbursement_claim_amount_reimbursed: $('#amount_to_claim').val(),
                reimbursement_claim_amount_spent: $('#reimbursement_claim_amount_spent').val()

            };

            $.post(url, data, function (response) {

                console.log(response);
                //     return false;

                var redirect = '<?= base_url(); ?>reimbursement_claim/list';

                if (parseInt(response) > 0) {

                    alert('Your New Medical Claim has been saved');

                    if (go_to_list_of_medical_claims) {

                        window.location.replace(redirect);
                    } else {
                        //Clear the add form
                        //reset_form();

                        window.location.replace(re_load);

                    }

                } else {
                    alert('Record not saved. Either you have empty field or something wrong happened');

                    //window.location.replace(redirect);
                    return false;
                }

            });

            ev.preventDefault();

        }

    });

    //Reset form
    $(".btn-reset").on('click', function (ev) {
        // reset_form();
        window.location.replace(re_load);

        ev.preventDefault();
    });


    function validate_form() {

        let check_any_field_empty;

        let any_field_empty_arr = [];


        $(".required").each(function () {

            //Select2 form validation implementation
            if ($(this).hasClass('select2') && (parseInt($(this).val()) === 0 || parseInt($(this).val()) == -1)) {

                $(this).siblings(".select2-container").css('border-color', 'red');

                any_field_empty_arr.push(true);
                //console.log($(this).val());
                //alert($(this).val());
                any_field_empty_arr.push($(this).prop('id'));


            } else {
                if (($(this).val().trim() == '' && !$(this).hasClass('select2')) || $('option:selected', this).val() == 0) {

                    $(this).css('border-color', 'red');
                    any_field_empty_arr.push(true);
                    any_field_empty_arr.push($(this).prop('id'));


                } else {
                    $(this).css('border-color', '');

                    // //Select2 implementation
                    if ($(this).hasClass('select2') && $(this).val() != 0) {
                        $(this).siblings(".select2-container").css('border', '');

                        any_field_empty_arr.push($(this).prop('id'));

                        any_field_empty_arr.push(false);
                    }

                }

            }

        });

        check_any_field_empty = any_field_empty_arr.includes(true);

        return check_any_field_empty;

        //return any_field_empty_arr;
    }

    //Validate the inputs before posting
    // function validate_form() {

    //     var any_field_empty = false

    //     $(".required").each(function() {

    //         if ($(this).val().trim() == '') {

    //             $(this).css('border-color', 'red');
    //             any_field_empty = true;

    //         } else {
    //             $(this).css('border-color', '');

    //             //Select2 implementation
    //             if ($(this).hasClass('select2') && $(this).val() != 0) {
    //                 $(this).siblings(".select2-container").css('border', '');

    //                 any_field_empty = false;
    //             }

    //         }

    //     });

    //     return any_field_empty;
    // }

    function reset_form() {
        $('input').val(null);
        $('textarea').val(null);
        $("#fk_office_id").select2("val", "");
        $("#fk_beneficiary_id").select2("val", "");
        $("#fk_health_facility_id").select2("val", "");
        $("#fk_voucher_detail_id").select2("val", "");

    }
</script>