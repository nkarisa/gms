<?php

//Add Form Title

$medical_claim_form_text = 'add_new_claim :MEDICAL';

$disabled = '';

if ($check_if_medical_app_only[0] == 0) {
    $medical_claim_form_text = 'Add new :MEDICAL OR TF';

    $disabled = "disabled='disabled'";
}

?>
<!-- Medical Claim Form or Medical Claim form  and/or with TFI -->
<div class='row'>
    <div class='col-xs-12'>

        <div class="panel panel-default" data-collapsed="0">
            <div class="panel-heading">
                <div class="panel-title">
                    <i class="entypo-plus-circled"></i>
                    <?php echo get_phrase("add_new_claim", $medical_claim_form_text); ?>
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
                            <button class='btn btn-default btn-reset'><?= get_phrase('reset', 'Reset'); ?></button>
                            <button class='btn btn-default btn-save'><?= get_phrase('save', 'Save'); ?></button>
                            <button class='btn btn-default btn-save-new'><?= get_phrase('save_and_new', 'Save and New'); ?></button>
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
                            <label class='col-xs-2 control-label'><?= get_phrase('fcp_number', 'FCP Number'); ?></label>
                            <div class='col-xs-4'>

                                <select class="form-control select2 required" id="fk_office_id" name='fk_office_id'>

                                    <?php
                                    //Populate fcp dropdwon

                                    if (sizeof($user_fcps) > 0) { ?>

                                        <option value="0"><?= get_phrase('select_fcp', 'Select FCP'); ?></option>

                                    <?php }

                                    foreach ($user_fcps as $key => $fcp) {
                                        $store_fcp_no[] = $fcp
                                        ?>
                                        <option value="<?= $key; ?>"><?= $fcp; ?></option>

                                    <?php } ?>

                                </select>
                            </div>
                            <!-- Cluster Name -->
                            <label class='col-xs-2 control-label'><?= get_phrase("cluster_name", "Cluster Name"); ?></label>
                            <div class='col-xs-4'>

                                <input class="form-control required" name="cluster" type="text" value=""
                                       id="cluster_name" placeholder="cluster name" readonly>
                                <input class="form-control required hidden" name="fk_context_cluster_id" type="text"
                                       value="" id="fk_context_cluster_id">
                            </div>

                        </div>

                    <?php } else {
                        $cluster = $this->medical_claim_model->populate_cluster_name($store_fcp_id[0]);
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
                        <label class='col-xs-2 control-label'><?= get_phrase("", "Participant Number"); ?></label>
                        <div class='col-xs-4'>


                            <select class="form-control select2 required" id="fk_beneficiary_id">
                                <option value="0"><?= get_phrase('select_participant'); ?></option>

                                <?php
                                //Populate the Beneficiary numbers on Load if FCP user has logged in
                                if (sizeof($store_fcp_no) == 1) {

                                    $fcp_code = trim($store_fcp_no[0]);

                                    $beneficiary_data = $this->medical_claim_model->pull_fcp_beneficiaries($fcp_code);


                                    if (sizeof($beneficiary_data) != 0) {

                                        foreach ($beneficiary_data as $beneficiary) {

                                            $ben_number = $beneficiary['beneficiary_number'];

                                            $ben_name = $beneficiary['beneficiary_name'];


                                            ?>
                                            <option value="<?= $ben_number; ?>"> <?= $ben_number . '-' . $ben_name; ?></option>

                                        <?php }
                                    } else { ?>

                                        <option value="-1"><?= get_phrase('missing_beneficiaries') ?></option>

                                    <?php }

                                    /*Populate the Beneficiary numbers on when the logged in use is non
                                     FCP user i.e. country admin[This will bring all ]*/
                                } else {

                                    $beneficiary_data = $this->medical_claim_model->pull_fcp_beneficiaries();


                                    foreach ($beneficiary_data as $beneficiary) {

                                        $ben_number = $beneficiary['beneficiary_number'];

                                        $ben_name = $beneficiary['beneficiary_name'];

                                        ?>

                                        <option value="<?= $ben_number; ?>"><?= $ben_number . ' - ' . $ben_name; ?></option>


                                    <?php }
                                } ?>

                            </select>

                            <input class='form-control required hidden' type="text" value=""
                                   name='medical_beneficiary_number' id="medical_beneficiary_number"/>


                        </div>

                        <!--Beneficiary_name  -->

                        <label class='col-xs-2 control-label'><?= get_phrase("participant_name", "Participant Name"); ?></label>
                        <div class='col-xs-4'>

                            <input class='form-control required' type="text" value="" name='medical_claim_name'
                                   id="medical_claim_name" readonly/>


                        </div>
                        <div> &nbsp;</div>
                        <!-- Claim Type -->
                        <?php
                        $hide_fields = '';

                        $for_medical_type = '';

                        if ($check_if_medical_app_only[0] == 0) {
                            //Hide Fields when medical and other reimbursements e.g. diagnosis, Facility Types
                            $hide_fields = 'hidden';
                            $for_medical_type = 'for_medical_type';


                            ?>

                            <label class='col-xs-2 control-label'><?= get_phrase("claim_type", "Claim Type"); ?></label>
                            <div class='col-xs-4'>

                                <select class="form-control select2 required" id="claim_type" name='claim_type'>

                                    <option value='0'><?= get_phrase("select_claim_type", "Select Claim Type"); ?></option>
                                    <option value='medical_claim'><?= get_phrase('medical_claim', 'Medical claim'); ?></option>
                                    <option value='TF'><?= get_phrase("TF", "TF"); ?></option>
                                </select>
                            </div>

                        <?php } ?>

                        <div> &nbsp;</div>


                        <!-- Diagnosis -->

                        <label class='col-xs-2 control-label <?= $hide_fields; ?>  <?= $for_medical_type; ?>'><?= get_phrase("diagnosis", "Diagnosis"); ?></label>
                        <div class='col-xs-4'>

                            <textarea class='form-control required <?= $hide_fields; ?> <?= $for_medical_type; ?> '
                                      name='medical_claim_diagnosis' id="medical_claim_diagnosis"
                                      placeholder="<?= get_phrase("describe_the_condition", "Describe the condition") ?>"></textarea>


                        </div>
                        <div> &nbsp;</div>

                        <!-- Treatment Date -->
                        <?php

                        //Default number of valid days for a claim
                        $valid_days_for_claiming = $this->config->item('valid_days_for_medical_claims');
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

                            <input class='form-control datepicker required' name='medical_claim_treatment_date'
                                   type="text" value="" id="medical_claim_treatment_date" readonly
                                   data-format="yyyy-mm-dd" data-end-date="0d"
                                   data-start-date="<?= $data_start_date; ?>"/>


                        </div>


                        <div> &nbsp;</div>
                        <!-- Facility Name -->
                        <label class='col-xs-2 control-label <?= $hide_fields; ?> <?= $for_medical_type; ?>'><?= get_phrase("facility_name", "Facility Name") ?></label>
                        <div class='col-xs-4'>

                            <input class='form-control required <?= $hide_fields; ?> <?= $for_medical_type; ?>'
                                   name='medical_claim_facility' type="text" value="" id="medical_claim_facility"
                                   placeholder="<?= get_phrase("health_facility_name", "Health Facility Name e.g. Oxford Hospital"); ?>"
                                   autocomplete="off"/>


                        </div>
                        <div> &nbsp;</div>
                        <!-- Facility Type -->
                        <label class='col-xs-2 control-label <?= $hide_fields; ?> <?= $for_medical_type; ?>'><?= get_phrase("facility_type", "Facility Type"); ?></label>
                        <div class='col-xs-4'>

                            <select class='form-control required  <?= $for_medical_type; ?>  <?= $hide_fields; ?>'
                                    name='fk_health_facility_id' id='fk_health_facility_id'>

                                <option value="0"><?= get_phrase('select_health_facility_type', 'Select health_facility_type'); ?></option>

                                <?php
                                //Populate Facility Types dropdwon

                                if (sizeof($health_facility_types) > 0) {
                                    foreach ($health_facility_types as $key => $health_facility_type) {
                                        ?>
                                        <option value="<?= $key; ?>"><?= $health_facility_type; ?></option>
                                    <?php }
                                } else { ?>
                                    <option value=""><?= get_phrase("national_compassion_office", 'Ask national compassion office to add facility types_e.g. public or private'); ?></option>
                                <?php } ?>

                            </select>

                            <!-- Hidden iput to store support  docs needed-->

                            <input class='form-control required hidden' name='support_documents_need_flag' type="text"
                                   value="" id="support_documents_need_flag" readonly/>

                        </div>
                        <div> &nbsp;</div>
                        <!-- Connect Incident ID -->
                        <label class='col-xs-2 control-label <?= $hide_fields; ?> for_medical_type'><?= get_phrase("connect_incident ID", "Connect Incident ID"); ?></label>
                        <div class='col-xs-4'>

                            <input class='form-control required <?= $hide_fields; ?> for_medical_type'
                                   name='medical_claim_incident_id' type="number" required="required" value=""
                                   id="medical_claim_incident_id"
                                   placeholder="<?= get_phrase("connect_id", "Connect Incident no. Numeric Only e..g 123"); ?>"
                                   autocomplete="off"/>


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

                        <label class='col-xs-2 control-label <?= $hide_fields; ?> <?= $for_medical_type; ?>'><?= get_phrase("caregiver_govt", "Caregiver Has Govt. Health Cover"); ?></label>
                        <div class='col-xs-4'>
                            <input type="checkbox" id="govt_insurance_checkbox_id" name="govt_insurance_checkbox"
                                   style='transform:scale(3); margin:15px;'
                                   class=' <?= $hide_fields; ?> <?= $for_medical_type; ?>' value='Yes'>


                        </div>


                        <div class=''>
                            <label id='health_card_id' class='col-xs-2 control-label hidden '
                                   style="color:green;"><?= get_phrase("caregiver_health", "Caregiver Health Cover No."); ?></label>
                            <div class='col-xs-4'>

                                <input class='hidden' name='medical_claim_govt_insurance_number'
                                       style='border:1px; border-style: dotted;  border-color: green' type="text"
                                       value="" id="medical_claim_govt_insurance_number" disabled='disabled'
                                       autocomplete="off"/>
                            </div>

                        </div>


                        <?php } ?>


                        <div class='col-xs-12'>
                            <!-- Voucher Number -->
                            <label class='col-xs-2 control-label'><?= get_phrase("voucher_number", "Voucher Number"); ?></label>
                            <div class='col-xs-4'>

                                <select class='form-control select2 required' name='fk_voucher_id'
                                        id='fk_voucher_id' <?= $disabled; ?>>
                                    <?php

                                    if (count($vouchers_and_total_costs) == 0) { ?>
                                        <option value=" "><?= get_phrase('missing_medical_vouchers', "Missing Medical Vouchers"); ?></option>
                                    <?php } else { ?>
                                        <option value="0"><?= get_phrase('select_voucher', 'Select Voucher'); ?></option>
                                        <?php

                                        foreach ($vouchers_and_total_costs as $vouchers_and_total_cost) {

                                            //Compute the balance of remburasable amount based on amount already reimbursed.
                                            $reimbursable_balance_amount = $vouchers_and_total_cost['voucher_detail_total_cost']; //-50000; //come from medical_claim table

                                            //Minus the mimnimum_rembursable amount
                                            $amount_above_minimum_rembursable = $vouchers_and_total_cost['voucher_detail_total_cost'];


                                            if (array_key_exists($vouchers_and_total_cost['voucher_id'], $already_reimbursed_amount) && array_key_exists($vouchers_and_total_cost['voucher_id'], $fcp_rembursable_amount_from_caregiver)) {

                                                $rembursable_amount_from_national_office = $already_reimbursed_amount[$vouchers_and_total_cost['voucher_id']];

                                                $rembursable_amount_from_caregiver = $fcp_rembursable_amount_from_caregiver[$vouchers_and_total_cost['voucher_id']];

                                                $total_rembursable_amount = $rembursable_amount_from_national_office + $rembursable_amount_from_caregiver;

                                                $reimbursable_balance_amount = $reimbursable_balance_amount - $total_rembursable_amount;

                                                //Only populate the dropdown when amount on the voucher is > 0
                                                if ($reimbursable_balance_amount > 0) {

                                                    ?>

                                                    <option value="<?= $vouchers_and_total_cost['voucher_id']; ?>"><?= $vouchers_and_total_cost['voucher_number'] . ' - ' . $country_currency_code . ' ' . $amount_above_minimum_rembursable . ' - ' . $country_currency_code . ' ' . number_format($reimbursable_balance_amount, 2); ?></option>

                                                <?php }
                                            } else {

                                                // $test=0;
                                                //$amount_before_reimbursemnt=sizeof($minmum_rembursable_amount)>0?$vouchers_and_total_cost['voucher_detail_total_cost']-$minmum_rembursable_amount[0]:$vouchers_and_total_cost['voucher_detail_total_cost'];

                                                $amount_before_reimbursemnt = $vouchers_and_total_cost['voucher_detail_total_cost'];
                                                ?>


                                                <option value="<?= $vouchers_and_total_cost['voucher_id']; ?>"><?= $vouchers_and_total_cost['voucher_number'] . ' - ' . $country_currency_code . ' ' . number_format($amount_before_reimbursemnt, 2); ?></option>

                                                <?php

                                            }
                                        }
                                    } ?>
                                </select>
                                <!-- <input class='form-control required' name='claim_voucher_number' type="text" value="" id="claim_voucher_number" readonly /> -->
                            </div>


                            <!-- Amount to be claimed -->

                            <label class='col-xs-2 control-label'><?= get_phrase("amount_to_claim", "Amount to claim"); ?></label>
                            <div class='col-xs-4'>

                                <input class='form-control required' name='amount_to_claim' type="number" value=""
                                       id="amount_to_claim" disabled='disabled'
                                       placeholder='<?= $country_currency_code ?> 2,000' autocomplete="off"/>
                            </div>

                            <div> &nbsp;</div>
                            <!-- Caregiver contribution -->
                            <label class='col-xs-2 control-label hidden for_medical_type'><?= get_phrase("caregiver_contribution", "Caregiver Contribution") ?></label>
                            <div class='col-xs-4'>

                                <input class='form-control required hidden for_medical_type'
                                       name='medical_claim_caregiver_contribution' type="text" value=""
                                       id="medical_claim_caregiver_contribution" readonly/>
                            </div>
                            <div> &nbsp;</div>
                            <!-- Total Claimable Balance -->
                            <label class='col-xs-2 control-label'><?= get_phrase("total_claimable_balance", "Total Claimable Balance"); ?></label>
                            <div class='col-xs-4'>

                                <input class='form-control required' name='medical_claim_amount_spent' type="text"
                                       value="" id="medical_claim_amount_spent" readonly/>
                            </div>


                            <!-- Amount to reimbursable -->
                            <label class='col-xs-2 control-label'><?= get_phrase('amount_reimbursable', 'Amount Reimbursement'); ?></label>
                            <div class='col-xs-4'>

                                <input class='form-control required' name='medical_claim_amount_reimbursed' type="text"
                                       value="" id="medical_claim_amount_reimbursed" readonly/>
                                <input class='form-control hidden' name='hold_threhold_amount' type="text" value=""
                                       id="hold_threhold_amount"/>
                            </div>
                        </div>
                        <hr>


                    </div>

                    <div class='form-group'>
                        <div class='col-xs-12' style='text-align:center;'>
                            <button class='btn btn-default btn-save'><?= get_phrase('save_btn', 'Save Changes') ?></button>
                            <button class='btn btn-default btn-save-new'><?= get_phrase("save_and_new", "Save and New"); ?></button>
                        </div>
                    </div>


                    </form>
                </div>
            </div>