<?php

namespace App\Libraries\Core;

use App\Libraries\Core\GrantsLibrary;
use App\Models\Core\StatusModel;

class StatusLibrary extends GrantsLibrary
{
    protected $table;
    protected $statusModel;

    function __construct()
    {
        parent::__construct();

        $this->statusModel = new StatusModel();

        $this->table = 'status';
    }

    /**
     * Checks if the status ID is the maximum for a given approveable item and item ID.
     *
     * @param string $approveableItem The name of the approveable item.
     * @param int $itemId The ID of the item.
     *
     * @return bool Returns true if the status ID is the maximum, false otherwise.
     *
     * @throws \Exception If there is an error executing the database query.
     */
    public function isStatusIdMax(string $approveableItem, int $itemId): bool
    {
        $isStatusIdMax = false;

        // Initialize the database query builder
        $builder = $this->read_db->table($this->table);

        // Select the status fields
        $builder->select('status.*');

        // Apply the necessary conditions for the query
        $builder->where([
            'approve_item_name' => $approveableItem,
            "{$approveableItem}_id" => $itemId,
            'status_approval_direction' => 1,
            'status_is_requiring_approver_action' => 0
        ]);

        // Join the necessary tables for the query
        $builder->join('approval_flow', 'approval_flow.approval_flow_id = status.fk_approval_flow_id');
        $builder->join('approve_item', 'approve_item.approve_item_id = approval_flow.fk_approve_item_id');
        $builder->join($approveableItem, "{$approveableItem}.fk_status_id = status.status_id");

        // Execute the query
        $query = $builder->get();

        // Check if there are any rows returned
        if ($query->getNumRows() > 0) {
            $isStatusIdMax = true;
        }

        // Return the result
        return $isStatusIdMax;
    }

    /**
 * Inserts a new status record into the database.
 *
 * @param int $user_id The ID of the user creating the status.
 * @param string $status_name The name of the status.
 * @param int $approval_flow_id The ID of the approval flow associated with the status.
 * @param int $status_approval_sequence The approval sequence of the status.
 * @param int $status_backflow_sequence The backflow sequence of the status.
 * @param int $status_approval_direction The approval direction of the status (1 for forward, -1 for backward).
 * @param int $status_is_requiring_approver_action Indicates whether the status requires approver action (0 for no, 1 for yes).
 * @param string $status_button_label The label for the status button.
 * @param string $status_decline_button_label The label for the status decline button.
 *
 * @return int The ID of the newly inserted status record. If the status already exists, the ID of the existing status is returned.
 */
public function insertStatus($user_id, $status_name, $approval_flow_id, $status_approval_sequence, $status_backflow_sequence, $status_approval_direction, $status_is_requiring_approver_action, $status_button_label = "", $status_decline_button_label = "")
{
    $status_id = 0;

    // Check if the status already exists in the database
    $existing_status = $this->statusModel->where([
        'fk_approval_flow_id' => $approval_flow_id,
        'status_approval_sequence' => $status_approval_sequence,
        'status_backflow_sequence' => $status_backflow_sequence,
        'status_approval_direction' => $status_approval_direction,
        'status_is_requiring_approver_action' => $status_is_requiring_approver_action
    ])->first();

    // If the status does not exist, insert a new record
    if (empty($existing_status)) {
        $data = (object) [
            'status_track_number' => generate_item_track_number_and_name('status')['status_track_number'],
            'status_name' => $status_name,
            'status_button_label' => $status_button_label,
            'status_decline_button_label' => $status_decline_button_label,
            'fk_approval_flow_id' => $approval_flow_id,
            'status_approval_sequence' => $status_approval_sequence,
            'status_backflow_sequence' => $status_backflow_sequence,
            'status_approval_direction' => $status_approval_direction,
            'status_is_requiring_approver_action' => $status_is_requiring_approver_action,
            'status_created_date' => date('Y-m-d'),
            'status_created_by' => $user_id,
            'status_last_modified_by' => $user_id
        ];

        $this->statusModel->insert($data);
        $status_id = $this->statusModel->getInsertID();
    } else {
        // If the status already exists, return its ID
        $status_id = $existing_status['status_id'];
    }

    return $status_id;
}


/**
 * Inserts initial and final status records into the database for a given approval flow.
 *
 * @param int $approval_flow_id The ID of the approval flow.
 * @param int $user_id The ID of the user creating the statuses.
 *
 * @return int The ID of the fully approved status record.
 *
 * @throws \Exception If there is an error executing the database query.
 */
function insertInitialAndFinalStatus($approval_flow_id, $user_id)
{
    // Insert initial status record
    $initial_status_id = $this->insertStatus($user_id, get_phrase('ready_to_submit'), $approval_flow_id, 1, 0, 1, 1);

    // Insert fully approved status
    $fully_approved_status_id = $this->insertStatus($user_id, get_phrase('fully_approved'), $approval_flow_id, 2, 0, 1, 0);
    $this->insertStatus($user_id, get_phrase('reinstate_after_allow_edit'), $approval_flow_id, 2, 1, -1, 1); // Reinstate the approval
    $this->insertStatus($user_id, get_phrase('reinstated_after_edit'), $approval_flow_id, 2, 0, 0, 1); // Reinstated approval

    // Return the ID of the fully approved status
    return $fully_approved_status_id;
}

    /**
 * Inserts status records into the database for a given approveable item, if they do not exist.
 *
 * @param string $approveItemName The name of the approveable item.
 *
 * @return bool Returns true if the status records were successfully inserted or already existed, false otherwise.
 *
 * @throws \Exception If there is an error executing the database query.
 */
public function insertStatusForApproveableItem(string $approveItemName): bool
{
    // Instantiate necessary libraries
    $approveItemLibrary = new \App\Libraries\Core\ApproveItemLibrary();
    $approvalFlowLibrary = new \App\Libraries\Core\ApprovalFlowLibrary();

    // Ensure the approve item name length is greater than 2
    if (strlen($approveItemName) > 2) {
        // Start a database transaction
        $this->statusModel->transBegin();

        // Retrieve the user ID from session or use 1 as a default
        $userId = session()->get('user_id') ?? 1;

        // Check if the approve item exists in the database, and insert if it doesn't
        $approveItemId = $approveItemLibrary->insertMissingApproveableItem($approveItemName);

        // Retrieve the account systems from the database
        $accountSystems = $this->read_db->table('account_system')->get()->getResultArray();

        // Iterate over each account system
        foreach ($accountSystems as $accountSystem) {
            // Retrieve the approval flow for the current account system and approve item
            $approvalFlow = $this->read_db->table('approval_flow')->where([
                'fk_approve_item_id' => $approveItemId,
                'fk_account_system_id' => $accountSystem['account_system_id']
            ])->get()->getRowArray();

            $approvalFlowId = 0;

            // If the approval flow does not exist, insert a new one
            if (!$approvalFlow) {
                $approvalFlowId = $approvalFlowLibrary->insertApprovalFlow($accountSystem, $approveItemId, $approveItemName, $userId);
            } else {
                $approvalFlowId = $approvalFlow['approval_flow_id'];
            }

            // Retrieve the status for the current approval flow
            $status = $this->read_db->table('status')->where([
                'fk_approval_flow_id' => $approvalFlowId
            ])->get()->getResultArray();

            // If there are less than 2 statuses, insert the initial and final statuses
            if (count($status) < 2) {
                $this->insertInitialAndFinalStatus($approvalFlowId, $userId);
            }
        }

        // Complete the transaction and handle errors
        if ($this->statusModel->transStatus() === false) {
            $this->statusModel->transRollback();
            throw new \Exception("Error occurred when creating missing status", 500);
        } else {
            $this->statusModel->transCommit();
            return true;
        }
    } else {
        return false;
    }
}

  /**
 * Inserts status records into the database for a given approveable item, if they do not exist.
 * Also checks if the approveable item has a dependant table and inserts status records for it if it does.
 *
 * @param string $approve_item_name The name of the approveable item.
 *
 * @return bool Returns true if the status records were successfully inserted or already existed, false otherwise.
 *
 * @throws \Exception If there is an error executing the database query.
 */
function insertStatusIfMissing($approve_item_name)
{
    // Insert status records for the given approveable item
    $res = $this->insertStatusForApproveableItem($approve_item_name);

    // Check if the approveable item has a dependant table
    $has_dependant_table = $this->hasDependantTable($approve_item_name);

    // If the approveable item has a dependant table, insert status records for it
    if ($has_dependant_table) {
        $dependant_table = $this->DependantTable($approve_item_name);
        $this->mandatoryFields($dependant_table);
        $this->insertStatusForApproveableItem($dependant_table);
    }

    // Return the result of inserting status records for the given approveable item
    return $res;
}

}