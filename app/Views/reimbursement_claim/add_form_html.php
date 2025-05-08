<?php


$deduct_or_load_all_voucher_amount = 0;
// if($medical_reimburse_all_flag==1){
//     $deduct_or_load_all_voucher_amount
// }
//print_r($medical_threshold_amount);
//echo '<br>';
//print_r($medical_reimburse_all_flag);

//Add Form Title

$medical_claim_form_text = 'add_Medical-Reimbursement_Claim';

$disabled = '';

/*
if (count($reimbursement_app_types) > 1) {
    $medical_claim_form_text = 'add MEDICAL or CIV-MEDICAL or MED-TFI or HVC Claim';

    $disabled = "disabled='disabled'";
}
*/

?>
<!-- Medical Claim Form or Medical Claim form  and/or with MED-TFI -->
<div class='row'>
    <div class='col-xs-12'>

        <div class="panel panel-default" data-collapsed="0">
            <div class="panel-heading">
                <div class="panel-title">
                    <i class="entypo-plus-circled"></i>
                    <i style='color: brown;'> <?php echo get_phrase($medical_claim_form_text); ?> </i>
                </div>
            </div>

            <div class="panel-body" style="max-width:50; overflow: auto;">
                <?php echo form_open("", array('id' => 'frm_add_new_claim', 'class' => 'form-horizontal form-groups-bordered validate', 'enctype' => 'multipart/form-data')); ?>

                <div class='form-group'>
                    <div class='col-xs-12 user_message'>

                    </div>
                </div>


                <div class='col-xs-12'>
                    <div class='form-group'>
                        <div class='col-xs-12' style='text-align:center;'>
                            <button class='btn btn-default btn-reset'>Reset</button>
                            <button class='btn btn-default btn-save'>Save</button>
                            <button class='btn btn-default btn-save-new'>Save and New</button>
                        </div>
                    </div>
                </div>
                <div class='col-xs-12 '>

                    <!-- FCP Name -->
                    <?php

                    //Check if array for FCP more than 1 item 
                    $fcp_counts = sizeof($user_fcps);

                    $store_fcp_no[] = $fcp_counts == 1 ? array_values($user_fcps)[0] : [];

                    $store_fcp_id[] = $fcp_counts == 1 ? array_keys($user_fcps)[0] : [];

                    //

                    if (sizeof($user_fcps) > 1) { ?>

                        <div class='form-group'>
                            <label class='col-xs-2 control-label'>FCP Number</label>
                            <div class='col-xs-4'>

                                <select class="form-control select2 required" id="fk_office_id" name='fk_office_id'>

                                    <?php
                                    //Populate fcp dropdwon

                                    if (sizeof($user_fcps) > 0) { ?>

                                        <option value="0"><?= get_phrase('select_fcp'); ?></option>

                                    <?php }

                                    foreach ($user_fcps as $key => $fcp) {
                                        $store_fcp_no[] = $fcp
                                        ?>
                                        <option value="<?= $key; ?>"><?= $fcp; ?></option>

                                    <?php } ?>

                                </select>
                            </div>
                            <!-- Cluster Name -->
                            <label class='col-xs-2 control-label'>Cluster Name</label>
                            <div class='col-xs-4'>

                                <input class="form-control required" name="cluster" type="text" value=""
                                       id="cluster_name" placeholder="cluster name" readonly>
                                <input class="form-control required hidden" name="fk_context_cluster_id" type="text"
                                       value="" id="fk_context_cluster_id">
                            </div>

                        </div>

                    <?php } else {
                        $cluster = $rcModel->populateClusterName($store_fcp_id[0]);
                        //print_r(array_keys($cluster)[0]);

                        //print_r(array_keys($user_fcps)[0]);

                        ?>

                        <input class="form-control required hidden" name="fk_office_id" type="text"
                               value="<?= array_keys($user_fcps)[0]; ?>" id="fk_office_id">
                        <input class="form-control required hidden" name="fk_context_cluster_id" type="text"
                               value="<?= array_keys($cluster)[0]; ?>" id="fk_context_cluster_id">
                    <?php } ?>

                    <!-- Beneficiary Number -->

                    <div class='form-group'>
                        <label class='col-xs-2 control-label'>Participant Number</label>
                        <div class='col-xs-4'>


                            <select class="form-control select2 required" id="fk_beneficiary_id">
                                <option value="0"><?= get_phrase('select_participant'); ?></option>

                                <?php
                                //Populate the Beneficiary numbers on Load if FCP user has logged in
                                if (sizeof($store_fcp_no) == 1) {


                                    $fcp_no = trim($store_fcp_no[0]);

                                    $beneficiary_data = $rcModel->pullFcpBeneficiaries($fcp_no);

                                    if (sizeof($beneficiary_data) != 0) {

                                        foreach ($beneficiary_data as $beneficiary) {
                                            $ben_num = $beneficiary['beneficiary_number'];
                                            $ben_name = $beneficiary['beneficiary_name'];
                                            ?>


                                            <option value="<?= $ben_num ?>"> <?= $ben_num . ' - ' . $ben_name ?></option>

                                        <?php }
                                    } else { ?>

                                        <option value="-1"><?= get_phrase('missing_beneficiaries') ?></option>

                                    <?php }

                                    /*Populate the Beneficiary numbers on when the logged in use is non
                                     FCP user i.e. country admin[This will bring all ]*/
                                } else {

                                    $beneficiary_data = $this->reimbursement_claim_model->pull_fcp_beneficiaries();


                                    foreach ($beneficiary_data as $beneficiary) {
                                        $ben_num2 = $beneficiary['beneficiary_number'];
                                        $ben_name2 = $beneficiary['beneficiary_name'];

                                        ?>

                                        <option value="<?= $ben_num2; ?>"><?= $ben_num2 . ' - ' . $ben_name2; ?></option>


                                    <?php }
                                } ?>

                            </select>

                            <input class='form-control required hidden'
                                   type="text" value="" name='reimbursement_claim_beneficiary_number'
                                   id="reimbursement_claim_beneficiary_number"
                            />


                        </div>
                        <!-- Beneficiary Name -->

                        <label class='col-xs-2 control-label'>Participant Name</label>
                        <div class='col-xs-4'>

                            <input class='form-control required' type="text" value="" name='reimbursement_claim_name'
                                   id="reimbursement_claim_name" readonly/>


                        </div>

                        <div> &nbsp;</div>
                        <!-- Claim Type -->
                        <?php
                        $hide_fields = '';
                        $for_medical_type = '';


                        if (count($reimbursement_app_types) > 1) {
                            //Hide Fields when medical and other reimbursements e.g. diagnosis, Facility Types
                            $hide_fields = 'hidden';
                            $for_medical_type = 'for_medical_type'; ?>

                            <!-- Claim Type -->

                            <label class='col-xs-2 control-label'><?= get_phrase('reimbursement_type', 'Reimbursement Type') ?></label>
                            <div class='col-xs-4'>

                                <select class="form-control select2 required" id="fk_reimbursement_app_type_id"
                                        name='fk_reimbursement_app_type_id'>

                                    <option value='0'><?= get_phrase('select_reimbursement_type', 'Select Reimbursement Type'); ?></option>

                                    <?php
                                    foreach ($reimbursement_app_types as $reimbursement_app_id => $reimbursement_app_type) { ?>

                                        <option value='<?= $reimbursement_app_id; ?>'><?= $reimbursement_app_type; ?></option>

                                    <?php } ?>


                                </select>
                            </div>

                        <?php } ?>


                        <!-- Diagnosis -->

                        <div id="diagnosis_area_id">

                            <?= $rcHelper->drawDiagnosisHelper(); ?>

                        </div>

                        <!-- Treatment Date -->
                        <?php

                        //Default number of valid days for a claim
                        //ToDo:  // ToDo: Not able to find this in current config library
                        $valid_days_for_claiming = 60;
                        //$valid_days_for_claiming = $this->config->item('valid_days_for_medical_claims');
                        if (sizeof($country_medical_settings_allowed_claimable_days) > 0) {

                            $valid_days_for_claiming = $country_medical_settings_allowed_claimable_days[0];
                        }

                        //print_r($country_medical_settings_allowed_claimable_days);

                        $data_start_date = '-' . $valid_days_for_claiming . 'd';


                        ?>
                        <div id='transaction_date'>
                            <label class='col-xs-2 control-label '>Date</label>

                        </div>
                        <div class='col-xs-4'>

                            <input class='form-control datepicker required' name='reimbursement_claim_treatment_date'
                                   type="text" value="" id="reimbursement_claim_treatment_date" readonly
                                   data-format="yyyy-mm-dd" data-end-date="0d"
                                   data-start-date="<?= $data_start_date; ?>"/>


                        </div>


                        <div> &nbsp;</div>
                        <!-- Facility Name -->
                        <label class='col-xs-2 control-label <?= $hide_fields; ?> <?= $for_medical_type; ?>'>Facility
                            Name</label>
                        <div class='col-xs-4'>

                            <input class='form-control required <?= $hide_fields; ?> <?= $for_medical_type; ?>'
                                   name='reimbursement_claim_facility' type="text" value=""
                                   id="reimbursement_claim_facility"
                                   placeholder="Health Facility Name e.g. Oxford Hospital" autocomplete="off"/>


                        </div>
                        <div> &nbsp;</div>
                        <!-- Facility Type -->
                        <label class='col-xs-2 control-label <?= $hide_fields; ?> <?= $for_medical_type; ?>'>Facility
                            Type</label>
                        <div class='col-xs-4'>

                            <select class='form-control required  <?= $for_medical_type; ?>  <?= $hide_fields; ?>'
                                    name='fk_health_facility_id' id='fk_health_facility_id'>

                                <option value="0"><?= get_phrase('select_health_facility_type'); ?></option>

                                <?php
                                //Populate Facility Types dropdwon

                                if (sizeof($health_facility_types) > 0) {
                                    foreach ($health_facility_types as $key => $health_facility_type) {
                                        ?>
                                        <option value="<?= $key; ?>"><?= $health_facility_type; ?></option>
                                    <?php }
                                } else { ?>
                                    <option value=""><?= get_phrase('ask_national_compassion_office_to_add_facility_types_e.g._public_or_private'); ?></option>
                                <?php } ?>

                            </select>

                            <!-- Hidden iput to store support  docs needed-->

                            <input class='form-control required hidden' name='support_documents_need_flag' type="text"
                                   value="" id="support_documents_need_flag" readonly/>

                        </div>
                        <div> &nbsp;</div>
                        <!-- Connect Incident ID -->
                        <label class='col-xs-2 control-label <?= $hide_fields; ?> for_medical_type'>Connect Incident
                            ID</label>
                        <div class='col-xs-4'>

                            <input class='form-control required <?= $hide_fields; ?> for_medical_type'
                                   name='reimbursement_claim_incident_id' type="number" required="required" value=""
                                   id="reimbursement_claim_incident_id"
                                   placeholder="Connect Incident no. Numeric Only e..g 123" autocomplete="off"/>


                        </div>
                    </div>

                    <hr>

                    <div> &nbsp;</div>
                    <!-- 
                            Govenment insurance 
                           
                            this field is hidden if the country has no caregiver relief contribution  
                       -->
                    <?php
                    //print_r($national_health_cover_flag);
                    if (sizeof($national_health_cover_flag) > 0 && $national_health_cover_flag[0] != 0) { ?>

                    <!-- Caregiver Insurance Card Checkbox -->


                    <div class=''>

                        <label class='col-xs-2 control-label <?= $hide_fields; ?> <?= $for_medical_type; ?>'><?= get_phrase('claim_contribution_exemption_label', 'Caregiver Has Govt. Health Cover or Exemption'); ?></label>
                        <div class='col-xs-4'>
                            <input type="checkbox" id="govt_insurance_checkbox_id" name="govt_insurance_checkbox"
                                   style='transform:scale(3); margin:15px;'
                                   class=' <?= $hide_fields; ?> <?= $for_medical_type; ?>' value='Yes'>


                        </div>


                        <div class=''>
                            <label id='health_card_id' class='col-xs-2 control-label hidden '
                                   style="color:green;"><?= get_phrase('claim_contribution_exemption_reference', 'Caregiver Health Cover or Exemption No.'); ?></label>
                            <div class='col-xs-4'>

                                <input class='hidden' name='reimbursement_claim_govt_insurance_number'
                                       style='border:1px; border-style: dotted;  border-color: green' type="text"
                                       value="" id="reimbursement_claim_govt_insurance_number" disabled='disabled'
                                       autocomplete="off"/>
                            </div>

                        </div>


                        <?php } ?>


                        <div class='col-xs-12'>
                            <!-- Voucher Number -->
                            <label class='col-xs-2 control-label'>Voucher Number</label>
                            <div class='col-xs-4'>

                                <select class='form-control select2 required' name='fk_voucher_detail_id'
                                        id='fk_voucher_detail_id' <?= $disabled; ?>>
                                    <?php

                                    if (count($vouchers_and_total_costs) == 0) { ?>
                                        <option value=" "><?= get_phrase('missing_medical_vouchers'); ?></option>
                                    <?php } else { ?>
                                        <option value="0"><?= get_phrase('select_voucher'); ?></option>
                                        <?php

                                        foreach ($vouchers_and_total_costs as $vouchers_and_total_cost) {

                                            //Compute the balance of remburasable amount based on amount already reimbursed.
                                            $reimbursable_balance_amount = $vouchers_and_total_cost['voucher_detail_total_cost']; //-50000; //come from medical_claim table

                                            //Minus the mimnimum_rembursable amount
                                            $amount_above_minimum_rembursable = $vouchers_and_total_cost['voucher_detail_total_cost'];


                                            if (array_key_exists($vouchers_and_total_cost['voucher_detail_id'], $already_reimbursed_amount) && array_key_exists($vouchers_and_total_cost['voucher_detail_id'], $fcp_rembursable_amount_from_caregiver)) {

                                                $rembursable_amount_from_national_office = $already_reimbursed_amount[$vouchers_and_total_cost['voucher_detail_id']];

                                                $rembursable_amount_from_caregiver = $fcp_rembursable_amount_from_caregiver[$vouchers_and_total_cost['voucher_detail_id']];

                                                $total_rembursable_amount = $rembursable_amount_from_national_office + $rembursable_amount_from_caregiver;

                                                $reimbursable_balance_amount = $reimbursable_balance_amount - $total_rembursable_amount;

                                                //Only populate the dropdown when amount on the voucher is > 0
                                                if ($reimbursable_balance_amount > 0) {

                                                    ?>

                                                    <option value="<?= $vouchers_and_total_cost['voucher_detail_id']; ?>"><?= $vouchers_and_total_cost['voucher_number'] . ' [' . $vouchers_and_total_cost['expense_account_code'] . ']' . ' - ' . $country_currency_code . ' ' . $amount_above_minimum_rembursable . ' - ' . $country_currency_code . ' ' . number_format($reimbursable_balance_amount, 2); ?></option>

                                                <?php }
                                            } else {

                                                // $test=0;
                                                //$amount_before_reimbursemnt=sizeof($minmum_rembursable_amount)>0?$vouchers_and_total_cost['voucher_detail_total_cost']-$minmum_rembursable_amount[0]:$vouchers_and_total_cost['voucher_detail_total_cost'];

                                                $amount_before_reimbursemnt = $vouchers_and_total_cost['voucher_detail_total_cost'];
                                                ?>


                                                <option value="<?= $vouchers_and_total_cost['voucher_detail_id']; ?>"><?= $vouchers_and_total_cost['voucher_number'] . ' [' . $vouchers_and_total_cost['expense_account_code'] . ']' . ' - ' . $country_currency_code . ' ' . number_format($amount_before_reimbursemnt, 2); ?></option>

                                                <?php

                                            }
                                        }
                                    } ?>
                                </select>
                                <!-- <input class='form-control required' name='claim_voucher_number' type="text" value="" id="claim_voucher_number" readonly /> -->
                            </div>


                            <!-- Amount to be claimed -->

                            <label class='col-xs-2 control-label'><?= get_phrase('total_receipt_amount_reimbursement_form', 'Total Receipt Amount') ?></label>
                            <div class='col-xs-4'>

                                <input class='form-control required' name='total_receipt_amount' type="number" value=""
                                       id="total_receipt_amount" disabled='disabled'
                                       placeholder='<?= $country_currency_code ?> 2,000' autocomplete="off"/>
                            </div>

                            <div> &nbsp;</div>
                            <!-- Caregiver contribution -->
                            <label class='col-xs-2 control-label hidden for_medical_type'>Caregiver Contribution</label>
                            <div class='col-xs-4'>

                                <input class='form-control required hidden for_medical_type'
                                       name='reimbursement_claim_caregiver_contribution' type="text" value=""
                                       id="reimbursement_claim_caregiver_contribution" readonly/>
                            </div>
                            <div> &nbsp;</div>
                            <!-- Total Claimable Balance -->
                            <label class='col-xs-2 control-label'>Total Claimable Balance</label>
                            <div class='col-xs-4'>

                                <input class='form-control required' name='reimbursement_claim_amount_spent' type="text"
                                       value="" id="reimbursement_claim_amount_spent" readonly/>
                            </div>


                            <!-- Amount to reimbursable -->
                            <label class='col-xs-2 control-label'><?= get_phrase('amount_to_claim', 'Amount to claim'); ?></label>
                            <div class='col-xs-4'>

                                <input class='form-control required' name='amount_to_claim' type="text" value=""
                                       id="amount_to_claim" readonly/>
                                <input class='form-control hidden' name='hold_threhold_amount' type="text" value=""
                                       id="hold_threhold_amount"/>
                                <input class='form-control hidden' name='hold_reimburse_all_when_threshold_met_flag'
                                       type="text" value="" id="hold_reimburse_all_when_threshold_met_flag"/>
                            </div>
                        </div>
                        <hr>


                    </div>

                    <div class='form-group'>
                        <div class='col-xs-12' style='text-align:center;'>
                            <button class='btn btn-default btn-reset'>Reset</button>
                            <button class='btn btn-default btn-save'>Save</button>
                            <button class='btn btn-default btn-save-new'>Save and New</button>
                        </div>
                    </div>


                    </form>
                </div>
            </div>

            <script>
                //Re-write the tag for Diagnosis Category based on what is selected in the Diagnosis Type

                $(document).on('change', '#reimbursement_claim_diagnosis_type', function () {

                    //Get Illness or Injuries
                    let url = '<?= base_url(); ?>ajax/reimbursement_claim/reimbursement_illiness_category/' + parseInt($(this).val());

                    $.get(url, function (response) {

                        options = '<option value="0">Diagnosis Category</option>';


                        $("#medical_claim_diagnosis_category").val('0').select2(); //Select default in select 2

                        $.each(JSON.parse(response), function (id, val) {
                            options = options + " <option value='" + id + "'>" + val + "</option>";

                        });
                        $('#medical_claim_diagnosis_category').html('');
                        $('#medical_claim_diagnosis_category').html(options).select2(); //referesh the Select2 by adding select2() at the end of html()
                    });
                    //console.log(options)

                })
            </script>