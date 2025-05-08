<?php
extract($result);

//$medical_claim_id = hash_id($this->id, 'decode');

//$medical_claim_data = $this->medical_claim_model->get_medical_claim_for_an_office($medical_claim_id);

$max_status_id = $statusLibrary->getMaxApprovalStatusId('reimbursement_claim');

$current_status_id_for_medical_item = array_column($reimbursement_claim_data, 'fk_status_id');

$initial_status_id = $statusLibrary->initialItemStatus('reimbursement_claim');

//$facility_type=$this->medical_claim_model->get_health_facility_by_id(hash_id($this->id, 'decode'));

//$facility_type_name=!empty($facility_type)?$facility_type:'Not Applicable';

// print($initial_status_id);
// print_r($current_status_id_for_medical_item[0]);

// echo '</br>';
// echo '</br>';
// print_r($check_if_medical_app_only);

//$logged_role_id = $this->session->role_ids;
$table = 'reimbursement_claim';
//$primary_key = $medical_claim_id;

//Hide medical related inputs if $check_if_medical_app_only=1 otherwise show
// $hide_tags='';

// if($check_if_medical_app_only===1){
//     $hide_tags='hidden';
// }


?>

<div class="row">
    <div class="col-xs-12">
        <?php // echo Widget_base::load('comment'); ?>
    </div>
</div>

<div class='row'>
    <div class="col-xs-12">

        <div class="panel panel-default" data-collapsed="0">
            <div class="panel-heading">
                <div class="panel-title">
                    <i class="entypo-plus-circled"></i>
                    <?php echo get_phrase('medical_claim_record', 'Medical Claim Record'); ?>
                </div>
            </div>
        </div>


        <div class="panel-body" style="max-width:50; overflow: auto;padding-left: 60px;padding-right: 30px;">

            <div class="row form_rows">
                <div class='col-xs-12'>
                    <div onclick="PrintElem('#reimbursement_record_print')"
                         class="btn btn-default"><?= get_phrase('print', 'Print'); ?></div>

                    <?php

                    $staus_id = $current_status_id_for_medical_item[0];

                    $reinstate_btn = $status_data['item_status'][$staus_id]['status_button_label'];
                    //Add Edit Button
                    if ($userLibrary->checkRoleHasPermissions('reimbursement_claim', 'update') && ($max_status_id[0] != $current_status_id_for_medical_item[0]) && $initial_status_id == $current_status_id_for_medical_item[0] || $reinstate_btn == 'Reinstate') {
                        echo '<a href="' . base_url("reimbursement_claim/edit/$claimID") . '" class="btn btn-default " id="" onclick="">Edit</a>';
                    }

                    //This helper has been replaced with approval_action_button helper
                    echo $rcHelper->approval_action_buttons($logged_role_id, $table, hash_id($claimID, 'decode'), $claimID);


                    ?>

                    <button class='btn btn-default btn-gotomedical-claim-list'><?= get_phrase("go_back", "Go Back") ?></button>

                </div>

            </div>

            <hr>

            <div id="medical_record_print">

                <div class='row form_rows visible-print'>
                    <div class='col-xs-12' style='text-align:center;'>
                        <? //= show_logo($header['office_id']); ?>
                    </div>
                    <div class='col-xs-12' style='text-align:center;margin-top:60px;'>
                        <?= get_phrase('claim', 'Claim'); ?>
                    </div>
                </div>

                <div class="row form_rows">
                    <div class="col-xs-4"><span
                                class='span_label'><?= get_phrase('beneficiary_name', 'Beneficiary Name.'); ?>:</span> <?= $reimbursement_claim_data[0]['reimbursement_claim_name'] ?>
                    </div>

                    <div class="col-xs-4"><span
                                class='span_label'><?= get_phrase('beneficiary_no', 'Beneficiary No.'); ?>:</span> <?= $reimbursement_claim_data[0]['reimbursement_claim_beneficiary_number'] ?>
                    </div>

                    <div class="col-xs-4"><span
                                class='span_label'><?= get_phrase('diagnosis', 'Diagnosis'); ?>:</span> <?= $reimbursement_claim_data[0]['reimbursement_claim_diagnosis'] ?>
                    </div>


                </div>
                <hr>
                <div class="row form_rows">
                    <div class="col-xs-4"><span
                                class='span_label'><?= get_phrase('amount', 'Amount'); ?>:</span> <?= number_format($reimbursement_claim_data[0]['reimbursement_claim_amount_spent'], 2); ?>
                    </div>

                    <div class="col-xs-4"><span
                                class='span_label'><?= get_phrase('caregiver_contribution', 'Caregiver Contribution'); ?>:</span> <?= number_format($reimbursement_claim_data[0]['reimbursement_claim_caregiver_contribution'], 2); ?>
                    </div>

                    <div class="col-xs-4"><span
                                class='span_label'><?= get_phrase('amount_reimbursed', 'Amount Reimbursed'); ?>:</span> <?= number_format($reimbursement_claim_data[0]['reimbursement_claim_amount_reimbursed'], 2); ?>
                    </div>


                </div>

                <hr>
                <div class="row form_rows">
                    <div class="col-xs-4"><span
                                class='span_label'><?= get_phrase('health_facility', 'Health Facility'); ?>:</span> <?= $reimbursement_claim_data[0]['reimbursement_claim_facility']; ?>
                    </div>

                    <div class="col-xs-4"><span
                                class='span_label'><?= get_phrase('health_facility_type', 'Health Facility Type'); ?>:</span> <?= $rcModel->getHealthFacilityByID(hash_id($claimID, 'decode'))//$facility_type_name;//$medical_claim_data[0]['health_facility_name'];                       ?>
                    </div>

                    <div class="col-xs-4"><span
                                class='span_label'><?= get_phrase('voucher_number', 'Voucher Number'); ?>:</span> <?= $reimbursement_claim_data[0]['voucher_number']; ?>
                    </div>


                </div>
                <hr>
                <div class="row form_rows">


                    <div class="col-xs-4"><span
                                class='span_label'><?= get_phrase('treatment_date', 'Treatment Date'); ?>:</span> <?= $reimbursement_claim_data[0]['reimbursement_claim_treatment_date']; ?>
                    </div>

                    <div class="col-xs-4"><span
                                class='span_label'><?= get_phrase('approval_status', 'Approval Status'); ?>:</span> <?= $reimbursement_claim_data[0]['status_name']; ?>
                    </div>

                    <div class="col-xs-4"><span
                                class='span_label'><?= get_phrase('raised_on', 'Raised On'); ?>:</span> <?= $reimbursement_claim_data[0]['reimbursement_claim_created_date']; ?>
                    </div>

                </div>

                <hr>
                <div class="row form_rows">


                    <div class="col-xs-4"><span
                                class='span_label'><?= get_phrase('compassion_connect_incident_id', 'Compassion Connect Incident Id'); ?>:</span> <?= $reimbursement_claim_data[0]['reimbursement_claim_incident_id']; ?>
                    </div>

                    <div class="col-xs-4"><span
                                class='span_label'><?= get_phrase('reimbursement_claim_count', 'Reimbursement'); ?>:</span> <?= $reimbursement_claim_data[0]['reimbursement_claim_count']; ?>
                    </div>


                </div>

                <hr>

                <div class="row form_rows">


                    <div class="<?= $reimbursement_claim_data[0]['support_documents_need_flag'] == 0 ? 'col-xs-12' : 'col-xs-6' ?>">
                        <!-- Receipts Table -->
                        <table id='reciepts_uploads'>
                            <thead>
                            <tr>
                                <th>
                                    <h3><?= get_phrase('receipts', 'Receipts'); ?></h3>
                                </th>

                            </tr>

                            </thead>

                            <?php

                            //Get only attachemnt for the medical Id selected
                            $array_of_value_this_medical_id = [];

                            foreach ($medical_claim_attachments as $medical_claim_attachment) {

                                if ($medical_claim_attachment['attachment_primary_id'] == hash_id($claimID, 'decode')) {

                                    $array_of_value_this_medical_id[] = $medical_claim_attachment;
                                }
                            }

                            $count = 1;

                            foreach ($array_of_value_this_medical_id as $medical_id_detail) { ?>

                                <tr>
                                    <?php
                                    $attachment_url = $medical_id_detail['attachment_url'];

                                    $pattern = "/receipts/";

                                    if (preg_match($pattern, $attachment_url) == 1) {
                                        $objectKey = $attachment_url . '/' . $medical_id_detail['attachment_name'];
                                        $url = $config->upload_files_to_s3 ? $awsAttachmentLibrary->s3PreassignedUrl($objectKey) : $attachmentLibrary->getLocalFilesystemAttachmentUrl($objectKey);
                                        ?>

                                        <td>
                                            <p> <?= $count . ")  "; ?><a target='__blank'
                                                                         href='<?= $url; ?>'><?= $medical_id_detail['attachment_name']; ?></a>
                                            </p>
                                        </td>


                                    <?php } ?>


                                </tr>


                                <?php $count++;
                            } ?>


                        </table>
                    </div>
                    <div class="col-xs-4"></div>
                    <?php
                    if ($reimbursement_claim_data[0]['support_documents_need_flag'] == 1) { ?>

                        <div class="col-xs-6">
                            <table id='support_doc_uploads'>
                                <thead>
                                <tr>
                                    <th>
                                        <h3><?= get_phrase('supporting_doc', 'Supporting Doc.'); ?></h3>
                                    </th>
                                </tr>
                                </thead>

                                <?php
                                $count = 1;
                                foreach ($array_of_value_this_medical_id as $medical_id_detail) { ?>

                                    <tr>
                                        <?php
                                        $attachment_url = $medical_id_detail['attachment_url'];

                                        $pattern = "/support_documents/";

                                        if (preg_match($pattern, $attachment_url) == 1) {

                                            $objectKey = $attachment_url . '/' . $medical_id_detail['attachment_name'];
                                            $url = $config->upload_files_to_s3 ? $awsAttachmentLibrary->s3PreassignedUrl($objectKey) : $attachmentLibrary->getLocalFilesystemAttachmentUrl($objectKey);
                                            ?>
                                            <td>
                                                <p> <?= $count . ")  "; ?> <a target='__blank'
                                                                              href='<?= $url; ?>'><?= $medical_id_detail['attachment_name']; ?></a>
                                                </p>
                                            </td>


                                            <?php $count++;
                                        } ?>

                                    </tr>

                                <?php } ?>

                            </table>
                        </div>


                    <?php } ?>

                </div>

            </div>
        </div>

    </div>


    <script>
        //Print
        function PrintElem(elem) {
            $(elem).printThis({
                debug: false,
                importCSS: true,
                importStyle: true,
                printContainer: false,
                loadCSS: "",
                pageTitle: "<?php echo get_phrase('medical_record', 'Medical Record'); ?>",
                removeInline: false,
                printDelay: 333,
                header: null,
                formValues: true
            });
        }

        //Once the document have loaded and check if uploads are present or not
        $(document).ready(function () {

            //Check if support document is needed
            let medical_id_flag = <?= $reimbursement_claim_data[0]['support_documents_need_flag']; ?>

            if (parseInt(medical_id_flag) == 1) {

                if ($('#reciepts_uploads tbody tr td').length == 0 || $('#support_doc_uploads tbody tr td').length == 0) {

                    $('#approve_button').addClass('disabled');
                }
            } else {
                if ($('#reciepts_uploads tbody tr td').length == 0) {

                    $('#approve_button').addClass('disabled');
                }
            }

        });


        //Go back to List of Medical Claims

        $('.btn-gotomedical-claim-list').on('click', function (ev) {
            //alert('test');
            var redirect = '<?= base_url(); ?>reimbursement_claim/list/';
            window.location.replace(redirect);

            ev.preventDefault();

        });
    </script>