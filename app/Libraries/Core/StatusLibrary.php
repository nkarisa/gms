<?php

namespace App\Libraries\Core;

use App\Libraries\System\GrantsLibrary;
use App\Models\Core\StatusModel;
use CodeIgniter\HTTP\RedirectResponse;

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

    // public function multiSelectField(): string
    // {
    //     return '';
    // }

    // public function actionBeforeIinsert(array $postArray): array{
    //     return $postArray;
    // }

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

    public function initialItemStatus($table_name = "", $account_system_id = 0)
    {

        if ($account_system_id == 0) {
            $account_system_id = session()->get('user_account_system_id');
        }

        $table = isEmpty($table_name) ? $this->controller : $table_name;

        $approveableItem = $this->read_db->table('approve_item')
            ->getWhere(['approve_item_name' => $table])
            ->getRow();

        $status_id = 0;

        if ($approveableItem) {
            $approveable_item_id = $approveableItem->approve_item_id;
            $approveable_item_is_active = $approveableItem->approve_item_is_active;

            $condition_array = [
                'fk_approve_item_id' => $approveable_item_id,
                'status_approval_sequence' => 1,
                'fk_account_system_id' => $account_system_id
            ];

            if (!$approveable_item_is_active) {
                $condition_array = [
                    'fk_approve_item_id' => $approveable_item_id,
                    'status_is_requiring_approver_action' => 0,
                    'fk_account_system_id' => $account_system_id
                ];
            }

            $initial_status = $this->read_db->table($this->table)
                ->join('approval_flow', 'approval_flow.approval_flow_id = status.fk_approval_flow_id')
                ->getWhere($condition_array)
                ->getRow();

            if ($initial_status) {
                $status_id = $initial_status->status_id;
            }
        }

        return $status_id;
    }

    public function getMaxApprovalStatusId(string $approveableItem, array $officeIds = [], int $accountSystemId = 0): array|RedirectResponse
    {
        $maxStatusIds = [];
        $builder = $this->read_db->table($this->table);

        if (empty($officeIds)) {
            if (empty($this->session->get('hierarchy_offices'))) {
                $message = "Your account is improperly set. Your user context assignment misses a related record in the correct context user table. Contact the administrator";
                $message .= "<br/><a href='" . base_url() . "login/logout'>" . get_phrase('log_out') . "</a>";
                // throw new \Exception($message);
                return redirect()->to('login/index');
            }
        }

        $builder->join('approval_flow', 'approval_flow.approval_flow_id = status.fk_approval_flow_id')
            ->join('approve_item', 'approve_item.approve_item_id = approval_flow.fk_approve_item_id')
            ->join('account_system', 'account_system.account_system_id = approval_flow.fk_account_system_id')
            ->join('office', 'office.fk_account_system_id = account_system.account_system_id');

        if ($accountSystemId > 0) {
            $builder->where('account_system.account_system_id', $accountSystemId);
        } else {
            $hierarchyOffices = !empty($officeIds) ? $officeIds : array_column(session()->get('hierarchy_offices'), 'office_id');
            $builder->whereIn('office.office_id', $hierarchyOffices);
        }

        $builder->select(['status_id', 'status_approval_sequence'])
            ->where([
                'approve_item_name' => $approveableItem,
                'status_backflow_sequence' => 0,
                'status_approval_direction' => 1,
                'status_is_requiring_approver_action' => 0
            ]);

        $maxStatusApprovalSequenceObj = $builder->get();

        if ($maxStatusApprovalSequenceObj->getNumRows() > 0 && $maxStatusApprovalSequenceObj->getRow()->status_approval_sequence > 0) {
            $maxStatusIdsWithSeq = $maxStatusApprovalSequenceObj->getResultArray();
            $maxStatusIds = array_unique(array_column($maxStatusIdsWithSeq, 'status_id'));
        } elseif (in_array($approveableItem, $this->config->tableThatDontRequireHistoryFields)) {
            // Nothing to do
        } else {
            $message = "You have no initial status set for the feature " . $approveableItem . ". Please check if all approval workflow related tables are correctly set</br>";
            if (!$this->insertStatusIfMissing($approveableItem)) {
                throw new \Exception($message);
            }
        }

        return $maxStatusIds;
    }

    public function isMaxApprovalStatusId(string $approveableItem, int $statusId): bool
    {
        $isMaxStatusId = false;
        $maxStatusId = $this->getMaxApprovalStatusId($approveableItem);

        if (in_array($statusId, $maxStatusId)) {
            $isMaxStatusId = true;
        }

        return $isMaxStatusId;
    }

    public function getStatusAccountSystem($statusId)
    {
        // Initialize the default account system ID
        $accountSystemId = 0;

        // Get the database connection
        $builder = $this->read_db->table('status');

        // Apply the necessary joins and conditions
        $builder->where('status.status_id', $statusId);
        $builder->join('approval_flow', 'approval_flow.approval_flow_id = status.fk_approval_flow_id');

        // Get the result
        $statusObj = $builder->get();

        // Check if the result exists and assign the account system ID
        if ($statusObj->getNumRows() > 0) {
            $accountSystemId = $statusObj->getRow()->fk_account_system_id;
        }

        return $accountSystemId;
    }

    function getItemStatusRoles($table)
    {

        $status_roles_obj = $this->read_db->table('status_role')
            ->select(array('status_id', 'status_role.fk_role_id as role_id', 'status_approval_sequence'))
            ->where(array('approve_item_name' => $table, 'status_role_is_active' => 1, 'approval_flow.fk_account_system_id' => $this->session->user_account_system_id))
            ->join('status', 'status.status_id=status_role.status_role_status_id')
            ->join('approval_flow', 'approval_flow.approval_flow_id=status.fk_approval_flow_id')
            ->join('approve_item', 'approve_item.approve_item_id=approval_flow.fk_approve_item_id')
            ->get();

        $roles = [];
        $level_roles = [];

        if ($status_roles_obj->getNumRows() > 0) {
            $status_roles = $status_roles_obj->getResultArray();
            foreach ($status_roles as $status_role) {
                $roles[$status_role['status_id']][] = $status_role['role_id'];
                $level_roles[$status_role['status_approval_sequence']][] = $status_role['role_id'];
            }
        }

        return ['status_roles' => $roles, 'level_roles' => $level_roles];
    }

    public function itemStatus($table, $itemInitialItemStatusId, $accountSystemId = 0)
    {
        // Retrieve the item status roles
        $itemStatusRoles = $this->getItemStatusRoles($table);
        $statusRoles = $itemStatusRoles['status_roles'];
        $levelRoles = $itemStatusRoles['level_roles'];

        // If account_system_id is not provided, use the session's account system ID
        if ($accountSystemId == 0) {
            $accountSystemId = session()->get('user_account_system_id');
        }

        // First query: Fetch status details
        $db = \Config\Database::connect();
        $builder = $db->table('status');

        $builder->select([
            'status.status_id',
            'status.status_name',
            'status.status_button_label',
            'status.status_decline_button_label',
            'status.status_approval_sequence',
            'status.status_approval_direction',
            'status.status_backflow_sequence'
        ]);
        $builder->join('approval_flow', 'approval_flow.approval_flow_id = status.fk_approval_flow_id');
        $builder->join('approve_item', 'approve_item.approve_item_id = approval_flow.fk_approve_item_id');
        $builder->where([
            'approve_item_name' => $table,
            'approval_flow.fk_account_system_id' => $accountSystemId
        ]);
        $status = $builder->get()->getResultArray();

        // Second query: Fetch approval exemptions
        $builder = $db->table('approval_exemption');
        $builder->select([
            'status.status_id',
            'approval_exemption.fk_office_id as office_id',
            'status.status_approval_sequence'
        ]);
        $builder->join('status', 'status.status_id = approval_exemption.approval_exemption_status_id');
        $builder->join('approval_flow', 'approval_flow.approval_flow_id = status.fk_approval_flow_id');
        $builder->join('approve_item', 'approve_item.approve_item_id = approval_flow.fk_approve_item_id');
        $builder->where([
            'approve_item_name' => $table,
            'approval_exemption_is_active' => 1,
            'approval_flow.fk_account_system_id' => $accountSystemId
        ]);
        $builder->whereIn('fk_office_id', array_column(session()->get('hierarchy_offices'), 'office_id'));
        $exemptionsObj = $builder->get();

        // Collect the exempted sequences
        $exemptedSequences = [];
        if ($exemptionsObj->getNumRows() > 0) {
            $exemptionsRaw = $exemptionsObj->getResultArray();
            foreach ($exemptionsRaw as $exemptRow) {
                $exemptedSequences[$exemptRow['status_id']] = $exemptRow['status_approval_sequence'];
            }
        }

        // Organize status records with status_id as the key
        $statusRecordsWithStatusIdKey = [];
        foreach ($status as $statusRecord) {
            if (!in_array($statusRecord['status_approval_sequence'], $exemptedSequences)) {
                $statusId = $statusRecord['status_id'];
                $statusRecordsWithStatusIdKey[$statusId] = $statusRecord;
            }
        }

        // Assign roles based on approval direction
        foreach ($statusRecordsWithStatusIdKey as $statusId => &$statusRecord) {
            if ($statusRecord['status_approval_direction'] == -1) {
                $statusRecord['status_role'] = isset($statusRoles[$itemInitialItemStatusId]) ? $statusRoles[$itemInitialItemStatusId] : [];
            } elseif ($statusRecord['status_approval_direction'] == 0) {
                $statusRecord['status_role'] = isset($levelRoles[$statusRecord['status_approval_sequence']]) ? $levelRoles[$statusRecord['status_approval_sequence']] : [];
            } else {
                $statusRecord['status_role'] = isset($statusRoles[$statusId]) ? $statusRoles[$statusId] : [];
            }
        }

        return $statusRecordsWithStatusIdKey;
    }

    function returnToPreviousPositiveStatus($table_name, $item_id, $item_initial_item_status_id){

        $data['fk_status_id'] = $item_initial_item_status_id;
        $builder = $this->write_db->table($table_name);
        $builder->where($table_name.'_id', $item_id);
        $builder->update($data);
    
        $updates_rows =  false;
        // log_message('error', json_encode($this->write_db->affected_rows()));
        if($this->write_db->affectedRows() > 0){
          $updates_rows = true;
        }
        // $this->write_db->close();
    
        return $updates_rows;
    }

}