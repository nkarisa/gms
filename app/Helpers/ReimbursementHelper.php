<?php

namespace App\Helpers;

use App\Libraries\Core\StatusLibrary;
use App\Models\Grants\ReimbursementClaimModel;
use Config\Database;
use Config\Services;
use App\Traits\System\ApprovalTrait;

class ReimbursementHelper
{
    use ApprovalTrait;

    private $session;
    private $statusLibrary;

    private $read_db;

    public function __construct()
    {
        $this->session = Services::session();
        $this->statusLibrary = new StatusLibrary();
        $this->read_db = Database::connect('read');
    }

    public function approvalActionButton(
        $tableName,
        $itemStatus,
        $itemId,
        $statusId,
        $itemInitialItemStatusId,
        $itemMaxApprovalStatusIds,
        $disableBtn = false,
        $confirmationRequired = true,
        $customStatusName = '',
        $voidedChq = false,
        $missingVoucherDetailFlag = false,
        $mfrSubmitted = false
    )
    {
        // Initialize disable class for buttons
        $disableClass = $disableBtn ? 'disabled' : '';

        // Default button for exempted status
        $buttons = "<div class='btn btn-info disabled'>" . get_phrase('exempted_status') . "</div>";

        // Check if the user is not a system admin and if the status ID does not exist in itemStatus
        if (!$this->session->system_admin && !isset($itemStatus[$statusId])) {
            $returnItem = $this->statusLibrary->returnToPreviousPositiveStatus($tableName, $itemId, $itemInitialItemStatusId);

            if (!$returnItem) {
                return $buttons;
            } else {
                $statusId = $itemInitialItemStatusId;
            }
        }

        // Retrieve user role IDs from session
        $roleIds = $this->session->role_ids;
        $status = $itemStatus[$statusId];

        // Set button labels based on status properties
        $statusButtonLabel = $status['status_button_label'] != '' ? $status['status_button_label'] : $status['status_name'];
        $statusDeclineButtonLabel = $status['status_decline_button_label'] != "" ? $status['status_decline_button_label'] : get_phrase('return_decline_btn', 'return');
        $statusName = $status['status_name'];
        $statusApprovalDirection = $status['status_approval_direction'];

        $approveNextStatus = 0;
        $declineNextStatus = 0;

        // Determine next status based on approval flow
        $nextStatus = $this->approvalNextStatus($tableName, $itemStatus, $itemId, $statusId, $itemInitialItemStatusId, $itemMaxApprovalStatusIds);

        if (count($nextStatus) > 0) {
            $approveNextStatus = $nextStatus['approve_next_status'];
            $declineNextStatus = $nextStatus['decline_next_status'];
        }


        // Check if the user has permission to act on this status
        $matchRoles = isset($status['status_role']) ? array_intersect($status['status_role'], $roleIds) : [];

        // Determine button color for max approval status
        $infoColor = in_array($statusId, $itemMaxApprovalStatusIds) ? 'primary' : 'info';

        if (sizeof($matchRoles) > 0) {
            // Show action button with button label if the status is not the final approval status
            if (!in_array($statusId, $itemMaxApprovalStatusIds)) {
                $color = ($statusApprovalDirection == -1) ? 'danger' : 'success';

                // If the cheque is voided, change button color to warning
                if ($voidedChq) {
                    $color = 'warning';
                }

                // Check if voucher is missing details and set data attribute accordingly
                $voucherDetailValue = $missingVoucherDetailFlag ? "data-voucher-missing-details='1'" : "data-voucher-missing-details='0'";

                $buttons = "<button id='$itemId' type='button' style='margin-right:5px' 
                        data-table='$tableName' data-item_id='$itemId' $voucherDetailValue  
                        data-confirmation='$confirmationRequired' data-current_status='$statusId' 
                        data-next_status='$approveNextStatus' 
                        class='btn btn-$color item_action $disableClass'>$statusButtonLabel</button>";
            } else {
                // Show a disabled button for the final approval status
                $buttons .= "<button id='$itemId' type='button' style='margin-right:5px' 
                         class='btn btn-$infoColor disabled final_status'>$statusName</button>";
            }

            // Show decline button with decline button label if the status can be declined
            if ($statusId != $itemInitialItemStatusId && $statusApprovalDirection != -1 && !$mfrSubmitted) {
                $buttons .= "<button id='decline_btn_$itemId' type='button' data-table='$tableName' 
                         data-confirmation='$confirmationRequired' data-item_id='$itemId' 
                         data-current_status='$statusId' data-next_status='$declineNextStatus' 
                         class='btn btn-danger $disableClass item_action'>$statusDeclineButtonLabel</button>";
            }
        } else {
            // Show status name/label as a disabled button when the user has no permission
            if ($voidedChq) {
                $infoColor = 'warning';
            }
            $buttons = "<button type='button' style='margin-right:5px' 
                    class='btn btn-$infoColor disabled final_status'>$statusName</button>";
        }

        return $buttons;
    }

    function approvalNextStatus($table_name, $item_status, $item_id, $status_id, $item_initial_item_status_id, $item_max_approval_status_ids): array
    {
        // log_message('error', json_encode($item_max_approval_status_ids));
        $status_approval_sequence = 1;
        $status_approval_direction = 1;
        $status = [];

        $status_approval_sequences = array_values(array_unique(array_column($item_status, 'status_approval_sequence')));
        sort($status_approval_sequences);

        $approve_next_status = 0;
        $decline_next_status = 0;
        $sequence_order_number = 0;

        if (isset($item_status[$status_id])) {
            $status = $item_status[$status_id];
            $status_approval_sequence = $item_status[$status_id]['status_approval_sequence'];
            $status_approval_direction = $item_status[$status_id]['status_approval_direction'];
            $sequence_order_number = array_search($status_approval_sequence, $status_approval_sequences);
        }

        // Compute next approval status and decline status
        foreach ($item_status as $id_status => $status_data) {

            $next_order_number = $sequence_order_number < count($status_approval_sequences) - 1 ? $sequence_order_number + 1 : $sequence_order_number;
            $next_sequence_number = $status_approval_sequences[$next_order_number];

            // Forward Jump
            if (
                $status_data['status_approval_sequence'] == $next_sequence_number &&
                !in_array($status_id, $item_max_approval_status_ids) &&
                $status_data['status_approval_direction'] == 1 &&
                ($status_approval_direction == 1 || $status_approval_direction == 0)
            ) {
                $approve_next_status = $id_status;
            }

            // For Reinstating
            if (
                $status_data['status_approval_sequence'] == $status_approval_sequence &&
                $status_id != $item_initial_item_status_id &&
                $status_data['status_approval_direction'] == 0 &&
                $status_approval_direction == -1
            ) {
                $approve_next_status = $id_status;
            }

            // For Approving Reinstatement
            if (
                $status_data['status_approval_sequence'] == $next_sequence_number &&
                $status_id != $item_initial_item_status_id &&
                $status_data['status_approval_direction'] == 1 &&
                $status_approval_direction == 0
            ) {
                $approve_next_status = $id_status;
            }

            // For Declining
            if (
                $status_data['status_approval_sequence'] == $status_approval_sequence &&
                $status_id != $item_initial_item_status_id &&
                $status_data['status_approval_direction'] == -1
            ) {
                $decline_next_status = $id_status;
            }

            // Approving reinstated item that was declined from full approval status

            if (
                $status_data['status_approval_sequence'] == $status_approval_sequence &&
                $status_id != $item_initial_item_status_id &&
                $status_data['status_approval_direction'] == 0 &&
                $status_approval_direction == 0
            ) {
                $approve_next_status = $item_max_approval_status_ids[0];
            }
        }


        $role_ids = $this->session->role_ids;
        $user_id = $this->session->user_id;

        // Only get positive status greater than the next approval status seq.
        // log_message('error', json_encode($item_status));
        $filtered_positive_item_status_above_current_sequence = array_filter($item_status, function ($value) use ($item_status, $approve_next_status) {
            return $value['status_approval_direction'] == 1 && $approve_next_status != 0 && $value['status_approval_sequence'] >= $item_status[$approve_next_status]['status_approval_sequence'];
        });


        $seqs = array_column($filtered_positive_item_status_above_current_sequence, 'status_approval_sequence');
        $status_roles = array_column($filtered_positive_item_status_above_current_sequence, 'status_role');
        $seqs_with_roles = array_combine($seqs, $status_roles);
        ksort($seqs_with_roles);

        $ids = array_keys($filtered_positive_item_status_above_current_sequence);
        $seqs = array_column($filtered_positive_item_status_above_current_sequence, 'status_approval_sequence');
        $seq_with_ids = array_combine($seqs, $ids);


        // Compute the next approval status if the user is an actor in the next approval process
        if ($approve_next_status && sizeof(array_intersect($item_status[$approve_next_status]['status_role'], $role_ids)) > 0) {
            foreach ($seqs_with_roles as $status_approval_sequence => $state_roles) {
                $id = $seq_with_ids[$status_approval_sequence];
                if (sizeof(array_intersect($state_roles, $role_ids)) == 0 || in_array($id, $item_max_approval_status_ids)) {
                    $approve_next_status = $id;
                    break;
                }
            }
        }

        $next_approval_states = compact('approve_next_status', 'decline_next_status');

        return $next_approval_states;
    }

    public function disableReadytoSubmitBtn($medicalID)
    {
        return "<script> 
        $('.item_action').each(function(){
          var id=$(this).data('item_id');
          if(id==$medicalID){
          $(this).removeClass('disabled');                                                   
        }
       }); 
    </script>";
    }

    function drawDiagnosisHelper()
    {
        $rcModel = new ReimbursementClaimModel();
        //Get sponship types
        $reimbursement_funding_type = $rcModel->reimbursementType();

        //Get diagnosis type
        $reimbursement_diagnosis_type = $rcModel->reimbursementDiagnosisType();


        $html_tag_diagnosis_area = "";

        //Funding Type

        $html_tag_diagnosis_area = $html_tag_diagnosis_area . "<label class='col-xs-2 control-label'>Funding Type</label><div class='col-xs-4'>";

        $html_tag_diagnosis_area = $html_tag_diagnosis_area . "<select class='form-control required' name='fk_reimbursement_funding_type_id' id='fk_reimbursement_funding_type_id'>";
        $html_tag_diagnosis_area = $html_tag_diagnosis_area . "<option value='0'>" . get_phrase('funding_type', 'Select Funding Type') . "</option>";

        foreach ($reimbursement_funding_type as $key => $funding_type) {

            if ($key == 1) {//Remove no funding type in dropdown
                continue;
            }
            $html_tag_diagnosis_area = $html_tag_diagnosis_area . "<option value='" . $key . "'>" . $funding_type . "</option>";
        }
        $html_tag_diagnosis_area = $html_tag_diagnosis_area . '</select></div>';
        //  Diagnosis Category
        $html_tag_diagnosis_area = $html_tag_diagnosis_area . "<label class='col-xs-2 control-label '>Diagnosis Category</label>";
        $html_tag_diagnosis_area = $html_tag_diagnosis_area . "<div class='col-xs-4'>";
        $html_tag_diagnosis_area = $html_tag_diagnosis_area . "<select class=' form-control  required select2' name='medical_claim_diagnosis_category' id='medical_claim_diagnosis_category'>";
        $html_tag_diagnosis_area = $html_tag_diagnosis_area . " <option value='0'>" . get_phrase('diagnosis_category', 'Diagnosis Category') . "</option>";
        $html_tag_diagnosis_area = $html_tag_diagnosis_area . " </select> </div>";

        //Diagnosis Type
        $html_tag_diagnosis_area = $html_tag_diagnosis_area . "<label class='col-xs-2 control-label '>Diagnosis Type</label>";
        $html_tag_diagnosis_area = $html_tag_diagnosis_area . "<div class='col-xs-4'>";
        $html_tag_diagnosis_area = $html_tag_diagnosis_area . "<select class='form-control  required' name='reimbursement_claim_diagnosis_type' id='reimbursement_claim_diagnosis_type'>";
        $html_tag_diagnosis_area = $html_tag_diagnosis_area . "<option value='0'>" . get_phrase('diagnosis_type', 'Diagnosis Type') . "</option>";
        //  $html_tag_diagnosis_area= $html_tag_diagnosis_area." <option value='Illness'>".get_phrase('Illness', 'Illness')."</option>";
        //  $html_tag_diagnosis_area= $html_tag_diagnosis_area." <option value='Injury'>".get_phrase('Injury', 'Injury')."</option> </select> </div>";
        foreach ($reimbursement_diagnosis_type as $id => $diagnosis_type) {
            $html_tag_diagnosis_area = $html_tag_diagnosis_area . "<option value=" . $id . ">" . $diagnosis_type . "</option>";
        }
        $html_tag_diagnosis_area = $html_tag_diagnosis_area . "</select> </div>";

        return $html_tag_diagnosis_area;
    }

    function drawDiagnosisArea()
    {

        $rcModel = new ReimbursementClaimModel();
        //Get sponship types
        $reimbursement_funding_type = $rcModel->reimbursementType();

        //Get diagnosis type
        $reimbursement_diagnosis_type = $rcModel->reimbursementDiagnosisType();

        $html_tag_diagnosis_area = "";

        //Funding Type

        $html_tag_diagnosis_area = $html_tag_diagnosis_area . "<label class='col-xs-2 control-label'>Funding Type</label><div class='col-xs-4'>";

        $html_tag_diagnosis_area = $html_tag_diagnosis_area . "<select class='form-control required' name='fk_reimbursement_funding_type_id' id='fk_reimbursement_funding_type_id'>";
        $html_tag_diagnosis_area = $html_tag_diagnosis_area . "<option value='0'>" . get_phrase('funding_type', 'Select Funding Type') . "</option>";

        foreach ($reimbursement_funding_type as $key => $funding_type) {

            if ($key == 1) {//Remove no funding type in dropdown
                continue;
            }
            $html_tag_diagnosis_area = $html_tag_diagnosis_area . "<option value='" . $key . "'>" . $funding_type . "</option>";
        }
        $html_tag_diagnosis_area = $html_tag_diagnosis_area . '</select></div>';
        //  Diagnosis Category
        $html_tag_diagnosis_area = $html_tag_diagnosis_area . "<label class='col-xs-2 control-label '>Diagnosis Category</label>";
        $html_tag_diagnosis_area = $html_tag_diagnosis_area . "<div class='col-xs-4'>";
        $html_tag_diagnosis_area = $html_tag_diagnosis_area . "<select class=' form-control  required select2' name='medical_claim_diagnosis_category' id='medical_claim_diagnosis_category'>";
        $html_tag_diagnosis_area = $html_tag_diagnosis_area . " <option value='0'>" . get_phrase('diagnosis_category', 'Diagnosis Category') . "</option>";
        $html_tag_diagnosis_area = $html_tag_diagnosis_area . " </select> </div>";

        //Diagnosis Type
        $html_tag_diagnosis_area = $html_tag_diagnosis_area . "<label class='col-xs-2 control-label '>Diagnosis Type</label>";
        $html_tag_diagnosis_area = $html_tag_diagnosis_area . "<div class='col-xs-4'>";
        $html_tag_diagnosis_area = $html_tag_diagnosis_area . "<select class='form-control  required' name='reimbursement_claim_diagnosis_type' id='reimbursement_claim_diagnosis_type'>";
        $html_tag_diagnosis_area = $html_tag_diagnosis_area . "<option value='0'>" . get_phrase('diagnosis_type', 'Diagnosis Type') . "</option>";
        //  $html_tag_diagnosis_area= $html_tag_diagnosis_area." <option value='Illness'>".get_phrase('Illness', 'Illness')."</option>";
        //  $html_tag_diagnosis_area= $html_tag_diagnosis_area." <option value='Injury'>".get_phrase('Injury', 'Injury')."</option> </select> </div>";
        foreach ($reimbursement_diagnosis_type as $id => $diagnosis_type) {
            $html_tag_diagnosis_area = $html_tag_diagnosis_area . "<option value=" . $id . ">" . $diagnosis_type . "</option>";
        }
        $html_tag_diagnosis_area = $html_tag_diagnosis_area . "</select> </div>";

        return $html_tag_diagnosis_area;
    }

    function approval_action_buttons($logged_role_id, $table, $primary_key, $id, $show_as_button = true)
    {
        ?>
        <style>
            .btn {
                margin: 5px;
            }
        </style>
        <?php


        $approver_status = $this->displayApproverStatusAction($logged_role_id, $table, $primary_key);
        //print_r($approver_status);
        // exit;
        $current_user_roles = $this->session->role_ids;
        $buttons = "";

        // log_message('error', json_encode(['current_user_role' => $current_user_role , 'current_actor' => $approver_status['current_actor_role_id']]));

        // log_message('error',json_encode(array_intersect($current_user_roles, $approver_status['current_actor_role_id'])));

        if ($show_as_button) {
            if (
                // in_array($CI->session->role_id, $approver_status['current_actor_role_id'])
                is_array(array_intersect($current_user_roles, $approver_status['current_actor_role_id']))
                &&
                $approver_status['show_label_as_button'] == true
            ) {
                $buttons = "<a id='approve_button' title='" . $approver_status['status_name'] . "' href='" . base_url() . 'reimbursment_claim' . "/approve/" . $id . "' class='btn btn-default'>" . $approver_status['button_label'] . "</a>";

                //if ($approver_status['show_decline_button'] == true) {
                //$buttons .= "<a href='" . base_url() . $CI->controller . "/decline/" . $CI->id . "' class='btn btn-default' id='decline_button'>Decline</a>";
                //}
            }
        } else {
            $buttons = $approver_status['button_label'];
        }

        if ($approver_status['show_decline_button'] == true) {
            $buttons .= "<a href='" . base_url() . 'reimbursement_claim' . "/decline/" . $id . "' class='btn btn-default' id='decline_button'>" . $approver_status['decline_button_label'] . "</a>";
        }


        return $buttons;
        //return json_encode($approver_status); //$current_user_role + " " + $approver_status['current_actor_role_id'];
    }

}