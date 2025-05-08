<?php

extract($result);


//print_r($check_if_medical_app_only);

//echo hash_id($this->id, 'decode');

?>

<div class="row">
    <div class="col-xs-12">
        <?php //echo Widget_base::load('comment'); ?>
    </div>
</div>
<div class='row'>
    <div class="col-xs-12">
        <div class="panel panel-default" data-collapsed="0">
            <div class="panel-heading">
                <div class="panel-title">
                    <i class="entypo-plus-circled"></i>
                    <?php echo get_phrase('edit_medical_claim_record', 'Edit Medical Claim Record'); ?>
                </div>
            </div>

            <!-- Form -->

            <div class="panel-body" style="max-width:50; overflow: auto;">

                <?php echo form_open("", array('id' => 'frm_add_new_claim', 'class' => 'form-horizontal form-groups-bordered validate', 'enctype' => 'multipart/form-data')); ?>

                <div class='col-xs-12 '>
                    <!-- Medical_id field -->
                    <input type='text' name='reimbursement_claim_id' id='reimbursement_claim_id'
                           value="<?= $medical_info['medical_id'][0] ?>" class='hidden'/>

                    <!-- FCP -->
                    <div class='form-group'>
                        <label class='col-xs-2 control-label'><?= get_phrase('fcp_number', 'FCP Number'); ?></label>
                        <div class='col-xs-4'>
                            <select class="form-control select2 required" id="fk_office_id">
                                <?php
                                $fcp_number = '';

                                foreach ($medical_info['fcp_no'] as $office_id => $fcp_no) {

                                    $fcp_number = $fcp_no;

                                    ?>

                                    <option value="<?= $office_id ?>"><?= $fcp_no; ?></option>

                                <?php } ?>

                            </select>
                        </div>
                        <!-- Beneficiary Dropdown -->
                        <label class='col-xs-2 control-label'><?= get_phrase('beneficiary_number', 'Beneficiary Number'); ?></label>
                        <div class='col-xs-4'>

                            <select class="form-control select2 required" id="fk_beneficiary_id">
                                <?php

                                $beneficiary_data = $rcModel->pullFcpBeneficiaries($fcp_number);

                                $ben_number = '';
                                $ben_unique_id = '';
                                $ben = '';

                                //Populate the beneficiary number as select as selected from medical_calim table
                                foreach ($medical_info['beneficiary_info'] as $key => $ben_info) {

                                    $ben_unique_id = $key;

                                    $beneficiary_name = $key . ' - ' . $ben_info;//explode('-', $ben_info)[1];

                                    $ben = $ben_info;

                                    //$ben_number = explode('-', $ben_info)[0];

                                    ?>
                                    <option value="<?= $key ?>"><?= $beneficiary_name; ?></option>

                                <?php } ?>
                                <!-- Add other beenficiaries of that project e.g. TZ0303 to dropdown -->
                                <?php
                                //$ben_unique_id;


                                foreach ($beneficiary_data as $beneficiary) {

                                    if (trim($beneficiary['beneficiary_number']) != trim($ben_unique_id)) { ?>

                                        <option value="<?= $beneficiary['beneficiary_number']; ?>">
                                            <?= $beneficiary['beneficiary_number'] . ' - ' . $beneficiary['beneficiary_name']; ?>
                                        </option>

                                    <?php }
                                }

                                ?>

                            </select>

                            <input class='form-control required hidden' type="text" value="<?= $ben_number ?>"
                                   name='medical_beneficiary_number' id="medical_beneficiary_number"/>

                        </div>

                    </div>

                </div>

                <!-- Beneficiary Name and Diognosis -->
                <div class='col-xs-12 '>


                    <label class='col-xs-2 control-label'><?= get_phrase('beneficiary_name', 'Beneficiary Name'); ?></label>
                    <div class='col-xs-4'>
                        <input class='form-control required' type="text" value="<?= $ben; ?>"
                               name='reimbursement_claim_name' id="reimbursement_claim_name" readonly/>
                    </div>

                    <!-- Diagnosis -->
                    <label class='col-xs-2 control-label'><?= get_phrase('diagnosis', 'Diagnosis'); ?></label>
                    <div class='col-xs-4'>

                        <textarea class='form-control required' name='reimbursement_claim_diagnosis'
                                  id="reimbursement_claim_diagnosis"
                                  placeholder="Describe the condition"><?= $medical_info['diagnosis'][0] ?></textarea>


                    </div>


                </div>

                <!-- Treatment Date and Healthy facility -->


                <div class='col-xs-12 '>

                    <div> &nbsp;</div>
                    <!-- Treatment Date -->
                    <?php
                    //ToDo: revert valid days to use config
                    //Default number of valid days for a claim
                    //$valid_days_for_claiming = $this->config->item('valid_days_for_medical_claims');
                    $valid_days_for_claiming = 60;

                    if (sizeof($country_medical_settings_allowed_claimable_days) > 0) {

                        $valid_days_for_claiming = $country_medical_settings_allowed_claimable_days[0];
                    }

                    $data_start_date = '-' . $valid_days_for_claiming . 'd';


                    ?>

                    <label class='col-xs-2 control-label'><?= get_phrase('treatment_date', 'Treatment Date'); ?></label>
                    <div class='col-xs-4'>

                        <input class='form-control datepicker required' name='medical_claim_treatment_date' type="text"
                               value="<?= $medical_info['treatment_date'][0] ?>" id="medical_claim_treatment_date"
                               required="required" readonly data-format="yyyy-mm-dd" data-end-date="0d"
                               data-start-date="<?= $data_start_date; ?>"/>

                    </div>

                    <!-- Facility Name -->
                    <label class='col-xs-2 control-label'><?= get_phrase('facility_name', 'Facility Name'); ?></label>
                    <div class='col-xs-4'>

                        <input class='form-control required' name='medical_claim_facility' type="text"
                               value="<?= $medical_info['health_facility'][0] ?>" id="medical_claim_facility"
                               placeholder="Health Facility Name e.g. Oxford Hospital"/>


                    </div>


                </div>
                <!-- healthy facillty type -->
                <div class='col-xs-12 '>
                    <div> &nbsp;</div>
                    <label class='col-xs-2 control-label'><?= get_phrase('facility_type', 'Facility Type'); ?></label>
                    <div class='col-xs-4'>

                        <select class='form-control select2 required' name='fk_health_facility_id'
                                id='fk_health_facility_id'>

                            <?php
                            //Populate Facility Types dropdown;

                            $check_health_facility_type_used = $medical_info['health_facility_type'][0];

                            ?>

                            <option value="<?= $check_health_facility_type_used; ?>">
                                <?= $health_facility_types[$check_health_facility_type_used]; ?></option>

                            <?php
                            //Remove the value added in the dropdown above
                            unset($health_facility_types[$check_health_facility_type_used]);

                            foreach ($health_facility_types as $key => $health_facility_type) { ?>

                                <option value="<?= $key; ?>"><?= $health_facility_type; ?></option>

                            <?php } ?>

                        </select>

                        <!-- Hidden iput to store support  docs needed-->

                        <input class='form-control required hidden' name='support_documents_need_flag' type="text"
                               value="<?= $medical_info['support_documents_need_flag'][0] ?>"
                               id="support_documents_need_flag" readonly/>
                    </div>
                    <div> &nbsp;</div>
                    <!-- Connect Incident ID -->
                    <label class='col-xs-2 control-label'><?= get_phrase('connect_incident_id', 'Connect Incident ID'); ?></label>
                    <div class='col-xs-4'>

                        <input class='form-control required' name='reimbursement_claim_incident_id' required="required"
                               value="<?= $medical_info['connect_incident_id'][0]; ?>"
                               id="reimbursement_claim_incident_id"
                               placeholder="Connect Incident no. Numeric Only e..g 123"/>


                    </div>
                </div>

                <hr>

                <div class='col-xs-12'>
                    <!-- Voucher Number -->
                    <label class='col-xs-2 control-label'><?= get_phrase('voucher_number', 'Voucher Number'); ?></label>
                    <div class='col-xs-4'>

                        <select class='form-control select2 required' name='fk_voucher_id' id='fk_voucher_id'>


                            <?php

                            $already_reimbursed_amount = $medical_info['already_reimbursed_amount'];

                            $vouchers_info = $medical_info['vouchers_and_total_costs'];

                            $store_balance_and_voucer_id = [];


                            foreach ($vouchers_info as $key => $vouchers) {

                                $total_rembursable_amount = $vouchers['voucher_detail_total_cost'];

                                $total_balance_reimbursable = 0.00;


                                $balance = 0;

                                if (array_key_exists($vouchers['voucher_id'], $already_reimbursed_amount)) {
                                    $balance = $total_rembursable_amount - $already_reimbursed_amount[$vouchers['voucher_id']];

                                    $store_balance_and_voucer_id[] = $balance;
                                }


                                if ($medical_info['fk_voucher_id'][0] == $vouchers['voucher_id']) {

                                    $total_balance_reimbursable = $balance;
                                    ?>
                                    <option value="<?= $vouchers['voucher_id'] ?>">
                                        <?= $vouchers['voucher_number'] . ' - ' . $medical_info['country_currency_code'] . ' ' . $total_rembursable_amount . ' - ' . $medical_info['country_currency_code'] . ' ' . $balance = $balance >= 0 ? $medical_info['amount_reimbursed'][0] + $balance : $balance; ?>
                                    </option>

                                    <?php
                                    //Remove the value that has came from Medical record from the array and break from the loop for efficiency of code

                                    unset($vouchers_info[$key]);

                                    break;
                                }
                            }
                            ?>

                            <?php
                            //Looop to populate the other vouchers that were not related to the one being edited above

                            foreach ($vouchers_info as $remaining_voucher) {

                                $total_rembursable_amount = $remaining_voucher['voucher_detail_total_cost'];

                                $balance = 0;

                                if (array_key_exists($remaining_voucher['voucher_id'], $already_reimbursed_amount)) {
                                    $balance = $total_rembursable_amount - $already_reimbursed_amount[$remaining_voucher['voucher_id']];
                                }
                                ?>
                                <!-- If balance of medical reimbursable is zero do show the in dropdwon -->
                                <?php
                                if ($balance != 0) { ?>

                                    <option value="<?= $remaining_voucher['voucher_id'] ?>">
                                        <?= $remaining_voucher['voucher_number'] . ' - ' . $medical_info['country_currency_code'] . ' ' . $total_rembursable_amount . ' - ' . $medical_info['country_currency_code'] . ' ' . $balance; ?>
                                    </option>

                                <?php } ?>

                            <?php } ?>
                        </select>

                    </div>

                    <!-- Amount to be claimed -->
                    <div> &nbsp;</div>
                    <label class='col-xs-2 control-label'><?= get_phrase('amount_to_claim', 'Amount to claim'); ?></label>
                    <div class='col-xs-4'>

                        <?php
                        $amount_to_reimburse = $medical_info['amount_reimbursed'][0];
                        if ($medical_info['caregiver_contribution_with_health_cover_card_percentage'] > 0) {
                            $amount_to_reimburse = $amount_to_reimburse + $medical_info['caregiver_contribution'][0];
                            //$add_care_giver_contribution = $medical_info['caregiver_contribution'][0]; 

                        }

                        ?>
                        <input class='form-control required' name='amount_to_claim' type="text"
                               value="<?= $medical_info['country_currency_code'] . ' ' . $amount_to_reimburse; ?>"
                               id="amount_to_claim" placeholder='<?= $medical_info['country_currency_code']; ?> 2000'/>
                    </div>

                </div>
                <div class='col-xs-12'>
                    <!-- 
                            Govenment insurance 
                           
                            this field is hidden if the country has no caregiver relief contribution  
                       -->
                    <?php
                    //$caregiver_relief_percentage_flag = $this->medical_claim_model->get_country_medical_settings(2);

                    $hide_div_if_no_caregiver_relief = 'hidden';

                    $required = '';


                    if (sizeof($medical_info['caregiver_contribution_with_health_cover_card_percentage']) > 0) {

                        $hide_div_if_no_caregiver_relief = '';

                        $required = 'required';
                    }
                    ?>
                    <div class='<?= $hide_div_if_no_caregiver_relief; ?>'>
                        <label class='col-xs-2 control-label'><?= get_phrase('caregiver_insurance_no', 'Caregiver Insurance No.'); ?></label>
                        <div class='col-xs-4'>

                            <input class='form-control <?= $required ?>'
                                   name='reimbursement_claim_govt_insurance_number'
                                   type="text" value="<?= $medical_info['govt_insurance_number'][0]; ?>"
                                   id="reimbursement_claim_govt_insurance_number"/>
                        </div>

                    </div>
                    <div> &nbsp;</div>
                    <!-- Caregiver contribution -->
                    <label class='col-xs-2 control-label'><?= get_phrase('caregiver_contribution', 'Caregiver Contribution'); ?></label>
                    <div class='col-xs-4'>

                        <input class='form-control required' name='reimbursement_claim_caregiver_contribution'
                               type="text"
                               value="<?= $medical_info['country_currency_code'] . ' ' . $medical_info['caregiver_contribution'][0]; ?>"
                               id="reimbursement_claim_caregiver_contribution" readonly/>
                    </div>


                </div>

                <div class='col-xs-12'>
                    <div> &nbsp;</div>
                    <!-- Total Claimable Balance -->
                    <label class='col-xs-2 control-label'><?= get_phrase('total_claimable_balance', 'Total Claimable Balance'); ?></label>
                    <div class='col-xs-4'>

                        <input class='form-control required' name='reimbursement_claim_amount_spent' type="text"
                               value="<?= $medical_info['country_currency_code'] . ' ' . ($medical_info['amount_reimbursed'][0] + $total_balance_reimbursable); //$total_balance_reimbursable;//$store_balance_and_voucer_id[0];
                               ?>"
                               id="reimbursement_claim_amount_spent" readonly/>
                    </div>

                    <!-- Amount to reimburse -->
                    <label class='col-xs-2 control-label'><?= get_phrase('amount_reimbursable', 'Amount Reimbursable'); ?></label>
                    <div class='col-xs-4'>

                        <input class='form-control required' name='reimbursement_claim_amount_reimbursed' type="text"
                               value="<?= $medical_info['country_currency_code'] . ' ' . $medical_info['amount_reimbursed'][0] ?>"
                               id="reimbursement_claim_amount_reimbursed" readonly/>
                    </div>
                </div>

                <div> &nbsp;</div>
                <!-- Save changes button -->
                <div class='col-xs-12'>
                    <div class='form-group'>

                        <div class='col-xs-12' style='text-align:center;'>
                            <button class='btn btn-deafult btn-save-changes'>Save Changes</button>
                            <button class='btn btn-default btn-cancel'>Cancel</button>
                        </div>


                    </div>
                </div>


            </div>

            </form>

        </div>
    </div>
</div>

</div>

<!-- SCRIPTS -->

<script>
    //Populate the Beneficiary name on change of beneficiary number
    $('#fk_beneficiary_id').on('change', function () {

        var beneficiary_no_and_name = this.options[this.selectedIndex].innerText;

        var beneficiary_no_and_name_arr = beneficiary_no_and_name.split('-');

        if (beneficiary_no_and_name_arr.length > 0) {

            $('#medical_claim_name').attr('value', beneficiary_no_and_name_arr[1]);

            $('#medical_beneficiary_number').attr('value', beneficiary_no_and_name_arr[0]);
        }

    });

    //Populate the support_needed_docs flag on hidden field
    $('#fk_health_facility_id').on('change', function () {

        var hidden_input_health_facility_id = $(this).val();

        var url = '<?= base_url(); ?>medical_claim/get_support_needed_docs_flag/' + hidden_input_health_facility_id;

        $.get(url, function (response) {

            $('#support_documents_need_flag').attr('value', response);

        });

    });


    //On select of voucher number
    $('#fk_voucher_id').on('change', function () {

        var amount_and_voucher_no_arr = [];

        if (parseInt($(this).val()) > 0) {
            //Get the selected text and populate the total claimable field

            let selected_voucher_and_amount = this.options[this.selectedIndex].innerText;

            let split_by_country_currency = '<?= $medical_info['country_currency_code']; ?>'

            //Split the currency
            amount_and_voucher_no_arr = selected_voucher_and_amount.split(split_by_country_currency);

            //console.log(amount_and_voucher_no_arr[2]);
            //find the total_claimable_balance field and populate

            let amount_balance = amount_and_voucher_no_arr.length > 2 ? amount_and_voucher_no_arr[2] :
                amount_and_voucher_no_arr[1];

            //fetch the voucher number. Remove the the hyphen character and store the vnumber
            let remove_hyphen_from_voucher_number = amount_and_voucher_no_arr[0].replace(/-/g, "");

            let store_voucher_number = parseInt(remove_hyphen_from_voucher_number);

            // $('#claim_voucher_number').attr('value',store_voucher_number);

            $('#reimbursement_claim_amount_spent').attr('value', split_by_country_currency + " " + amount_balance);

            $('#amount_to_claim').removeAttr('disabled');

            $('#amount_to_claim').val('');

            //$('#reimbursement_claim_caregiver_contribution').val('');
            $('#reimbursement_claim_amount_reimbursed').attr('value', split_by_country_currency + " " + 0.00);


        } else {

            $('#amount_to_claim').val('');

            $('#reimbursement_claim_caregiver_contribution').val('');

            $('#reimbursement_claim_amount_reimbursed').val('');

            $('#reimbursement_claim_amount_spent').val('');

            // $('#claim_voucher_number').val('')

            $('#amount_to_claim').attr('disabled', 'disabled');

        }

    });

    //New way to compute caregiver contribution

    $('#amount_to_claim').change(function (evt) {

        compute_claim_reimbursement($(this));

        evt.preventDefault();

    });

    //DISABLE and Enable the caregiver insurance
    $('#reimbursement_claim_govt_insurance_number').on('change', function (ev) {

        if ($(this).val() != '') {
            $('#amount_to_claim').removeAttr('disabled');
        } else {
            $('#amount_to_claim').attr('disabled', 'disabled');
        }
//On change when the amount_to_claim is NOT=''
//var amount=parseFloat($('#amount_to_claim').val());
        if ($('#amount_to_claim').val() != '') {

            var elem = $('#amount_to_claim');

            compute_claim_reimbursement(elem);

            ev.preventDefault();

        }


    });

    function compute_claim_reimbursement(elem) {

//Values to be passed

        let total_claimable_amount = $('#reimbursement_claim_amount_spent').val();

        let split_by_country_currency = '<?= $medical_info['country_currency_code']; ?>'

//Split the currency
        amount_and_voucher_no_arr = total_claimable_amount.split(split_by_country_currency);

//console.log(amount_and_voucher_no_arr[2]);
//find the total_claimable_balance field and populate  

        let amount_balance = amount_and_voucher_no_arr.length > 2 ? amount_and_voucher_no_arr[2] : amount_and_voucher_no_arr[1];

        let balance_amount_on_voucher = parseFloat(amount_balance.replace(/,/g, '')) // $('#reimbursement_claim_amount_spent').val();

        var amount_to_claim = $(elem).val();

//Split the currency
        let amount_to_claim_arr = amount_to_claim.split(split_by_country_currency);

// alert(amount_to_claim_arr[1]);

//Check if amount_to_claim is greater than 
        if (parseFloat(amount_to_claim_arr[1]) > balance_amount_on_voucher) {
            alert('Amount to claim- ' + split_by_country_currency + amount_to_claim + ' ' + "is more than total claimable balance: " + split_by_country_currency + balance_amount_on_voucher);
            return false;
        }

//Get the medical claims scenerios and  proceed with claim
        let insurance_card = $('#reimbursement_claim_govt_insurance_number').val();

        var url = '<?= base_url(); ?>ajax/reimbursement_claim/medical_claim_scenerios/' + balance_amount_on_voucher + '/' + parseFloat(amount_to_claim_arr[1]) + '/' + insurance_card;

        $.get(url, function (response) {

            let obj_resp = JSON.parse(response);
            console.log(obj_resp);

            //Computation values in the array
            let computation_array = obj_resp.computations;

            //Setting arrays
            let setting_array = obj_resp.settings;

            $('#hold_threhold_amount').attr('value', setting_array.threshold_amount);

            //Compute reimbursed amount when threshold is substracted from amount to claim

            if (setting_array.reimburse_all_when_threshold_met == false) {
                amount_to_claim = amount_to_claim_arr[1] - setting_array.threshold_amount;

                //$('#amount_to_claim').prop('value',$('#amount_to_claim').val()+ '(LESS '+setting_array.threshold_amount+') Threshold Reimbursable amount');
            }

            //Computed values for caregiver contribution, reimbursed amount
            $('#reimbursement_claim_caregiver_contribution').attr('value', split_by_country_currency + ' ' + computation_array.contribution_amount.toFixed(2));

            let amount_reimbursable = amount_to_claim - parseFloat(computation_array.contribution_amount);

            $('#reimbursement_claim_amount_reimbursed').prop('value', split_by_country_currency + ' ' + amount_reimbursable.toFixed(2));

        });
    }

    //Compute the caregiver contribution
    // $('#amount_to_claim').change(function() {

    //     var country_currency_code = '<?= $medical_info['country_currency_code']; ?>' + ' ';

    //     var amount_to_claim = parseFloat($(this).val());

    //     var total_balance = $('#reimbursement_claim_amount_spent').val();


    //     //Split to remove the currency code and then remove commas
    //     var reimbursement_claim_amount_spent = total_balance.split(country_currency_code)[1];

    //     reimbursement_claim_amount_spent = reimbursement_claim_amount_spent.replace(/,/g, '');

    //     //Split to Remove Curency part
    //     var amount_to_claim_value = $(this).val();

    //     var split_to_get_amount = [];

    //     if (isNaN(amount_to_claim_value)) {

    //         split_to_get_amount = amount_to_claim_value.split(' ');

    //     }

    //     if (split_to_get_amount.length) {
    //         amount_to_claim = split_to_get_amount[1];
    //     } else {
    //         amount_to_claim = amount_to_claim_value;
    //     }

    //     //Check if amount to claim is < 0 and is < amount spent on medical expense
    //     if ((amount_to_claim > 0) && amount_to_claim <= parseFloat(reimbursement_claim_amount_spent)) {

    //         var caregiver_contribution_relief_url =
    //             <?= sizeof($medical_info['caregiver_contribution_with_health_cover_card_percentage']) > 0 ? $medical_info['caregiver_contribution_with_health_cover_card_percentage'][0] : 0; ?>

    //         var caregiver_contribution_url =
    //             <?= sizeof($medical_info['percentage_caregiver_contribution']) > 0 ? $medical_info['percentage_caregiver_contribution'][0] : 0; ?>


    //         var amount_reimbursable = amount_to_claim;

    //         var caregiver_contribution_percentage = 0.00;

    //         if (caregiver_contribution_url > 0) {

    //             var percentage_ratio = caregiver_contribution_url / 100;


    //             //caregiver_contribution_percentage = (percentage_ratio * parseInt(amount_to_claim))

    //             var amount_relief_percentage = 0;

    //             //Has relief
    //             if (caregiver_contribution_relief_url > 0) {

    //                 //1=100%
    //                 var percentage_to_relief = 1 - (caregiver_contribution_relief_url / 100);
    //             }
    //             if (percentage_to_relief == 0) {
    //                 caregiver_contribution_percentage = 0; //caregiver reliefed fully;
    //             } else {
    //                 caregiver_contribution_percentage = percentage_ratio * parseFloat(amount_to_claim);

    //                 if (caregiver_contribution_relief_url != 0) {
    //                     caregiver_contribution_percentage = (percentage_ratio * parseFloat(amount_to_claim)) *
    //                         percentage_to_relief;

    //                 }
    //             }
    //             //Compute amount reimbursable [amount to claim - caregiver contribution]
    //             amount_reimbursable = amount_reimbursable - caregiver_contribution_percentage

    //             //Find the caregiver contribution & amount reimbursable fields and populate them

    //             $('#reimbursement_claim_caregiver_contribution').attr('value', country_currency_code + parseFloat(
    //                 caregiver_contribution_percentage).toFixed(2));

    //             $('#reimbursement_claim_amount_reimbursed').attr('value', country_currency_code + parseFloat(
    //                 amount_reimbursable).toFixed(2));
    //         } else {

    //             ////Find the caregiver contribution & amount reimbursable fields and populate them if admin had  logged in
    //             $('#reimbursement_claim_caregiver_contribution').attr('value', country_currency_code + parseFloat(
    //                 caregiver_contribution_percentage).toFixed(2));

    //             $('#reimbursement_claim_amount_reimbursed').attr('value', country_currency_code + parseFloat(
    //                 amount_reimbursable).toFixed(2));
    //         }

    //     } else {
    //         alert('Amount to claim- ' + country_currency_code + amount_to_claim + ' ' +
    //             "is more than total claimable balance: " + country_currency_code + reimbursement_claim_amount_spent);

    //         return false;
    //     }

    // });

    //Save changes Data


    $(".btn-save-changes").on('click', function (ev) {


        // if (validate_form()) return false;

        var url = "<?= base_url(); ?>ajax/reimbursement_claim/edit_medical_claim";
        var btn = $(this);

        var data = {
            fk_office_id: $('#fk_office_id').val(),
            reimbursement_claim_id: $('#reimbursement_claim_id').val(),
            reimbursement_claim_beneficiary_number: $('#reimbursement_claim_beneficiary_number').val(),
            reimbursement_claim_name: $('#reimbursement_claim_name').val(),
            reimbursement_claim_treatment_date: $('#reimbursement_claim_treatment_date').val(),
            reimbursement_claim_facility: $('#reimbursement_claim_facility').val(),
            fk_health_facility_id: $('#fk_health_facility_id').val(),
            support_documents_need_flag: $('#support_documents_need_flag').val(),
            reimbursement_claim_incident_id: $('#reimbursement_claim_incident_id').val(),
            fk_voucher_id: $('#fk_voucher_id').val(),
            reimbursement_claim_diagnosis: $('#reimbursement_claim_diagnosis').val(),
            reimbursement_claim_govt_insurance_number: $('#reimbursement_claim_govt_insurance_number').val(), //can be empty if no govt insurance
            reimbursement_claim_caregiver_contribution: $('#reimbursement_claim_caregiver_contribution').val(),
            reimbursement_claim_amount_reimbursed: $('#reimbursement_claim_amount_reimbursed').val(),
            reimbursement_claim_amount_spent: $('#reimbursement_claim_amount_spent').val()

        };

        $.post(url, data, function (response) {

            // console.log(data);

            //alert(response);

            //Get the edit Id from URL using Javascript
            var str = window.location.href;
            str = str.split("/")

            let medical_hashed_id = str[str.length - 1];

            var redirect = '<?= base_url(); ?>reimbursement_claim/view/' + medical_hashed_id;

            // alert(redirect);

            if (response > 0) {

                alert('Record Updated');


                window.location.replace(redirect);
            } else {
                alert('Record update failed ');

                window.location.replace(redirect);
            }

        });


        ev.preventDefault();
    });


    //Cancel Edit

    $('.btn-cancel').on('click', function (ev) {
        //alert('test');
        //Get the edit Id from URL using Javascript
        let str = window.location.href;
        str = str.split("/")

        let medical_hashed_id = str[str.length - 1];
        var redirect = '<?= base_url(); ?>reimbursement_claim/view/' + medical_hashed_id;
        window.location.replace(redirect);

        ev.preventDefault();

    });
</script>