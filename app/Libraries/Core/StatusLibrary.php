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

        $this->checkDeclineReinstateStatus();
    }

    function checkDeclineReinstateStatus(){

        $id = hash_id($this->id,'decode');

        $builder = $this->write_db->table('status');

        $builder->select(array(
          'status_id',
          'status_name',
          'fk_approval_flow_id',
          'status_approval_sequence',
          'status_backflow_sequence',
          'status_approval_direction',
          'status_is_requiring_approver_action'
        ));

        $builder->where(array('fk_approval_flow_id'=>$id));
        $approval_flow_status = $builder->get()->getResultArray(); 
    
        $status_grouped_by_level = [];
    
        foreach ($approval_flow_status as $status) {
          if ($status['status_approval_sequence'] == 1) {
            continue;
    
          }
    
          $status_grouped_by_level[$status['status_approval_sequence']][$status['status_approval_direction']] = $status;
        }
    
        $directions = [-1, 0, 1];
    
        $new_status_to_add = [];
    
        $cnt = 0;
    
        foreach($status_grouped_by_level as $status_level => $status){
          foreach($directions as $direction){
            if(!array_key_exists($direction,$status)){
    
              $status_name = get_phrase("fully_approve");
              $status_button_label = '';
    
              if($direction == -1){
                $status_name = get_phrase("declined");
                $status_button_label = get_phrase('reinstate');
              }elseif($direction == 0){
                $status_name = get_phrase("reinstated");
                $status_button_label = get_phrase("approve_after_reinstate");
              }
    
              $new_status_to_add[$cnt]['status_name'] = $status_name;
              $new_status_to_add[$cnt]['status_button_label'] = $status_button_label;
              $new_status_to_add[$cnt]['fk_approval_flow_id'] = $id;
              $new_status_to_add[$cnt]['status_approval_sequence'] = $status_level;
              $new_status_to_add[$cnt]['status_approval_direction'] = $direction;
              $new_status_to_add[$cnt]['status_backflow_sequence'] = $direction == -1 ? 1 : 0;
              $new_status_to_add[$cnt]['status_is_requiring_approver_action'] = 1;
    
              $new_status_to_add[$cnt]['status_track_number'] = $this->generateItemTrackNumberAndName('status')['status_track_number'];
              $new_status_to_add[$cnt]['status_created_date'] = date('Y-m-d');
              $new_status_to_add[$cnt]['status_created_by'] = $this->session->user_id;
              $new_status_to_add[$cnt]['status_last_modified_by'] = $this->session->user_id;
    
    
            }
            $cnt++;
          }
        }
    
        if(!empty($new_status_to_add)){
          $builder->insertBatch($new_status_to_add);
        }
    
        //print_r($new_status_to_add);exit;
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

        $builder = $this->read_db->table('status');
        // Check if the status already exists in the database
        $existing_status = $builder->where([
            'fk_approval_flow_id' => $approval_flow_id,
            'status_approval_sequence' => $status_approval_sequence,
            'status_backflow_sequence' => $status_backflow_sequence,
            'status_approval_direction' => $status_approval_direction,
            'status_is_requiring_approver_action' => $status_is_requiring_approver_action
        ])->get()->getRow();

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

            $this->write_db->table("status")->insert($data);
            $status_id = $this->write_db->insertID();
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
        // $initial_status_id = $this->insertStatus($user_id, get_phrase('ready_to_submit'), $approval_flow_id, 1, 0, 1, 1);

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
            $this->write_db->transBegin();

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
            if ($this->write_db->transStatus() === false) {
                $this->write_db->transRollback();
                throw new \Exception("Error occurred when creating missing status", 500);
            } else {
                $this->write_db->transCommit();
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

    public function initialItemStatus($table_name = "", $account_system_id = 0): int
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
        } elseif (in_array($approveableItem, decode_setting("GrantsConfig","tableThatDontRequireHistoryFields"))) {
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

    function returnToPreviousPositiveStatus($table_name, $item_id, $item_initial_item_status_id)
    {

        $data['fk_status_id'] = $item_initial_item_status_id;
        $builder = $this->write_db->table($table_name);
        $builder->where($table_name . '_id', $item_id);
        $builder->update($data);

        $updates_rows = false;
        if ($this->write_db->affectedRows() > 0) {
            $updates_rows = true;
        }
        // $this->write_db->close();

        return $updates_rows;
    }


    function detailListTableWhere(\CodeIgniter\Database\BaseBuilder $builder): void
    {
        if (!$this->session->system_admin) {
            $builder->groupStart();
            $builder->where(array('status_approval_sequence <> ' => 1));
            $builder->oRwhere(array('status_is_requiring_approver_action <> ' => 0));
            $builder->groupEnd();

            $builder->where(array('status_approval_sequence <> ' => 0));
            $builder->where(array('status_approval_direction ' => 1));
            $builder->orderBy('status_approval_sequence ASC');
        }

    }

    public function singleFormAddVisibleColumns(): array
    {
        return ['status_name', 'status_button_label', 'status_decline_button_label', 'approval_flow_name', 'status_approval_sequence'];
    }


    function action_before_edit($post_array)
    {

        $this->write_db->transStart();

        $status_id = hash_id($this->id, 'decode');

        $builder = $this->read_db->table('status');
        $builder->select('fk_approval_flow_id, status_approval_sequence');
        $builder->where('status_id', $status_id);
        $status_obj = $builder->get()->getRow();

        $current_seq = $status_obj->status_approval_sequence;
        // $updated_seq = $post_array['header']['status_approval_sequence'];

        // Prevent from updating the approval sequencies with an edit.
        $post_array['header']['status_approval_sequence'] = $current_seq;

        // Prevent giving a decline button label for step number 1
        if ($status_obj->status_approval_sequence == 1) {
            $post_array['header']['status_decline_button_label'] = NULL;
        }

        $this->write_db->transComplete();

        if ($this->write_db->transStatus() === FALSE) {
            return [];
        } else {
            return $post_array;
        }
    }



    function actionAfterInsert($post_array, $approval_id, $header_id): bool
    {
        $state = true;
        // Get approve item name of the of the created status
        $builder = $this->write_db->table('status');
        $builder->join('approval_flow', 'approval_flow.approval_flow_id=status.fk_approval_flow_id');
        $builder->join('approve_item', 'approve_item.approve_item_id=approval_flow.fk_approve_item_id');
        $builder->where('status_id', $header_id);
        $approve_item_name = $builder->get()->getRow()->approve_item_name;

        // Get the dependant/ detail table of the approve item name
        $approve_item_detail_name = $this->dependantTable($approve_item_name);

        $builder = $this->write_db->table('approval_flow');
        $builder->join('approve_item', 'approve_item.approve_item_id=approval_flow.fk_approve_item_id');
        $builder->where('approve_item_name', $approve_item_detail_name);
        $dependant_table_approval_flow = $builder->get()->getRow();

        if ($approve_item_detail_name !== "") {
            $this->write_db->transStart();
            $data['status_track_number'] = $this->generateItemTrackNumberAndName('status')['status_track_number'];
            $data['status_name'] = $post_array['status_name'];
            $data['fk_approval_flow_id'] = $dependant_table_approval_flow->approval_flow_id;
            $data['status_approval_sequence'] = $post_array['status_approval_sequence'];
            $data['status_backflow_sequence'] = $post_array['status_backflow_sequence'];
            $data['status_approval_direction'] = $post_array['status_approval_direction'];
            $data['status_is_requiring_approver_action'] = $post_array['status_is_requiring_approver_action'];
            $data['status_created_date'] = $post_array['status_created_date'];
            $data['status_created_by'] = $post_array['status_created_by'];
            $data['status_last_modified_by'] = $post_array['status_last_modified_by'];
            $data['fk_approval_id'] = $post_array['fk_approval_id'];
            $data['fk_status_id'] = $post_array['fk_status_id'];

            $builder = $this->write_db->table('status');
            $builder->insert($data);
            $this->write_db->transComplete();

            if ($this->write_db->transStatus() == false) {
                $state = false;
            } 
        }

        return $state;
    }


    function changeFieldType(): array
    {
  
      $change_field_type = array();
  
      $roles = $this->getAccountSystemRoles($this->session->user_account_system_id);
      
      $builder = $this->read_db->table('role');
      $builder->select(array('role_id', 'role_name'));
  
      if (!$this->session->system_admin) {
        $builder->where('fk_account_system_id', $this->session->user_account_system_id);
      }
  
      $roles = $builder->get()->getResultArray();
  
      $array_of_role_ids = array_column($roles, 'role_id');
      $array_of_role_names = array_column($roles, 'role_name');
      $role_select_options = array_combine($array_of_role_ids, $array_of_role_names);
    
      $change_field_type['role_name']['field_type'] = 'select';
      $change_field_type['role_name']['options'] = $role_select_options;
  
      $change_field_type['status_approval_direction']['field_type'] = 'select';
      $change_field_type['status_approval_direction']['options'] = array(
        '-1' => get_phrase('return_to_sender'),
        '0' => get_phrase('reinstated_to_last_approver'),
        '1' => get_phrase('send_to_next_approver')
      );
  
  
      $change_field_type['status_is_requiring_approver_action']['field_type'] = 'select';
      $change_field_type['status_is_requiring_approver_action']['options'] = array(
        get_phrase('no'),
        get_phrase('yes')
      );
  
      $change_field_type['status_approval_sequence']['field_type'] = 'select';
  
      $default_sequencies = array(
        '1' => get_phrase('first_level'),
        '2' => get_phrase('second_level'),
        '3' => get_phrase('third_level'),
        '4' => get_phrase('fourth_level'),
        '5' => get_phrase('fifth_level'),
        '6' => get_phrase('sixth_level'),
        '7' => get_phrase('seventh_level'),
        '8' => get_phrase('eight_level'),
        '9' => get_phrase('nineth_level'),
        '10' => get_phrase('tenth_level'),
      );
  
      if ($this->action == 'singleFormAdd') {
        // Get an array from the $default_sequencies of approval sequencies that have not been used in reference to the status table
        $unused_approval_sequencies = $this->statusApprovalSequencies($default_sequencies);
        $immediate_unused_approval_sequency_label = current($unused_approval_sequencies);
        $immediate_unused_approval_sequency_key = array_search($immediate_unused_approval_sequency_label, $unused_approval_sequencies);
  
        // Create an array of the immediate unused approval sequency
        $immediate_unused_approval_sequency_array = [$immediate_unused_approval_sequency_key => $immediate_unused_approval_sequency_label];
  
        $default_sequencies = $immediate_unused_approval_sequency_array;
      }elseif($this->action == 'edit'){
  
        $builder = $this->read_db->table('status');
        $builder->select('fk_approval_flow_id');
        $builder->where(array('status_id' => hash_id($this->id, 'decode')));
        $approval_flow_id = $builder->get()->getRow()->fk_approval_flow_id;
  
        $builder = $this->read_db->table('status');
        $builder->select('status_approval_sequence');
        $builder->where(array('fk_approval_flow_id' => $approval_flow_id,'status_approval_sequence > ' => 1));
        $builder->orderBy('status_approval_sequence ASC');
        $status_approval_sequence_obj = $builder->get();
  
        if($status_approval_sequence_obj->getNumRows() > 0){
          $status_approval_sequence = $status_approval_sequence_obj->getResultArray();
  
          $levels = array_column( $status_approval_sequence, 'status_approval_sequence');
          $levels = array_unique($levels);
          $levels = array_flip($levels);
  
          array_pop($levels); // Remove the last sequence
          unset($default_sequencies[1]); // Remove the first level
  
          $default_sequencies = array_intersect_key($default_sequencies, $levels); // Get sequences that are common
          $default_sequencies[0] =  get_phrase('deactivate');
          
        }
      }
  
      $change_field_type['status_approval_sequence']['options'] = $default_sequencies;
  
      $change_field_type['status_backflow_sequence']['field_type'] = 'select';
      $change_field_type['status_backflow_sequence']['options'] = array(
        '0' => get_phrase('none'),
        '1' => get_phrase('first_level'),
        '2' => get_phrase('second_level'),
        '3' => get_phrase('third_level'),
        '4' => get_phrase('fourth_level'),
        '5' => get_phrase('fifth_level'),
        '6' => get_phrase('sixth_level'),
        '7' => get_phrase('seventh_level'),
        '8' => get_phrase('eight_level'),
        '9' => get_phrase('nineth_level'),
        '10' => get_phrase('tenth_level'),
      );
  
  
      return $change_field_type;
    }


    function statusApprovalSequencies($change_field_type_sequencies)
  {
    $lookup_values = [];

    if ($this->id != null) {
      $builder = $this->read_db->table('status');  
      $builder->select(array('status_approval_sequence'));
      $builder->where([
        'approval_flow_id' => hash_id($this->id, 'decode'),
        'status_is_requiring_approver_action' => 1, 'status_approval_direction' => 1
      ]);
      $builder->join('approval_flow', 'approval_flow.approval_flow_id=status.fk_approval_flow_id');
      $status_approval_sequence_obj = $builder->get();

      if ($status_approval_sequence_obj->getNumRows() > 0) {
        $status_approval_sequence = array_flip(array_column($status_approval_sequence_obj->getResultArray(), 'status_approval_sequence'));

        $all_status_approval_sequence = $change_field_type_sequencies; //$this->change_field_type()['status_approval_sequence'];

        foreach ($status_approval_sequence as $status_approval_sequence_id => $status_approval_sequence_label) {
          if (array_key_exists($status_approval_sequence_id, $all_status_approval_sequence)) {
            unset($all_status_approval_sequence[$status_approval_sequence_id]);
          }
        }
      }

      $lookup_values =  $all_status_approval_sequence;
    }

    return $lookup_values;
  }


  function add()
  {
    $post = $this->request->getPost()['header'];

    $jumps = [1, 0, -1]; // 1 = Submitted new Item, 0 = Submitted Reinstated Item, -1 = Declined Item

    $messageAndFlag['flag'] = true;
    $messageAndFlag['message'] = get_phrase('insert_successful');
    
    $insert_array = [];

    $this->write_db->transStart();

    $status_approval_sequence = $post['status_approval_sequence'];
    $approval_flow_id = $post['fk_approval_flow_id'];

    // Insert a fully approved status/ final status
    $this->insertFinalApprovalStatus($approval_flow_id, $status_approval_sequence);

    $cnt = 0;

    foreach ($jumps as $jump) {

      $insert_array[$cnt]['status_approval_sequence'] = $post['status_approval_sequence'];
      $insert_array[$cnt]['fk_approval_flow_id'] = $post['fk_approval_flow_id'];
      $insert_array[$cnt]['status_name'] = $post['status_name'];
      $insert_array[$cnt]['status_button_label'] = $post['status_button_label'];
      $insert_array[$cnt]['status_decline_button_label'] = "";
      $insert_array[$cnt]['status_signatory_label'] = NULL;


      if ($jump == 0) {
        $insert_array[$cnt]['status_name'] = 'Reinstated for ' . $insert_array[$cnt]['status_name'];
        $insert_array[$cnt]['status_button_label'] =  get_phrase("approve");
        $insert_array[$cnt]['status_decline_button_label'] = $post['status_decline_button_label'];
      } elseif ($jump == 1) {
        $insert_array[$cnt]['status_signatory_label'] = NULL;//$post['status_signatory_label'];
        $insert_array[$cnt]['status_decline_button_label'] = $post['status_decline_button_label'];
      } elseif ($jump == -1) {
        $insert_array[$cnt]['status_name'] = 'Declined from ' . $insert_array[$cnt]['status_name'];
        $insert_array[$cnt]['status_button_label'] = get_phrase("reinstate");
      }


      //$status_approval_sequence = $status_approval_sequence_level;
      $insert_array[$cnt]['status_backflow_sequence'] =  $jump == -1 ? 1 : 0;
      $insert_array[$cnt]['status_approval_direction'] = $jump;
      $insert_array[$cnt]['status_is_requiring_approver_action'] = 1; // All custom status require an action from a user

      $insert_array[$cnt]['status_track_number'] = $this->generateItemTrackNumberAndName('status')['status_track_number'];

      $insert_array[$cnt]['status_created_date'] = date('Y-m-d');
      $insert_array[$cnt]['status_created_by'] = $this->session->user_id;
      $insert_array[$cnt]['status_last_modified_by'] = $this->session->user_id;

      $cnt++;
    }

    $builder = $this->write_db->table('status');

    $builder->insertBatch($insert_array);

    $this->write_db->transComplete();

    if (!$this->write_db->transStatus()) {
        $messageAndFlag['flag'] = false;
        $messageAndFlag['message'] = get_phrase('insert_failed');
    }

    return $this->response->setJSON($messageAndFlag);
  }

  function insertFinalApprovalStatus($approval_flow_id, $status_approval_sequence)
  {

    $builder = $this->read_db->table('status');
    // Is final approval status
    $builder->where(array(
      'fk_approval_flow_id' => $approval_flow_id,
      'status_is_requiring_approver_action' => 0,
      'status_approval_direction' => 1,
      'status_backflow_sequence' => 0
    ));
    $final_approval_status = $builder->get();

    $max_sequency_level = $status_approval_sequence + 1;

    if ($final_approval_status->getNumRows() == 0) {
      $this->insertStatus($this->session->user_id, get_phrase('fully_approved'), $approval_flow_id, $max_sequency_level, 0, 1, 0);
      $this->insertStatus($this->session->user_id, get_phrase('reinstate_after_allow_edit'), $approval_flow_id, $max_sequency_level, 1, -1, 1);
      $this->insertStatus($this->session->user_id, get_phrase('reinstated_after_edit'), $approval_flow_id, $max_sequency_level, 0, 0, 1);
    } else {
      // Update to the status_approval_sequence to the last sequence
      $update_data['status_approval_sequence'] = $max_sequency_level;
      $builder = $this->write_db->table('status');
      
      $builder->update($update_data, [
        'status_approval_sequence' => $status_approval_sequence,
        'fk_approval_flow_id' => $approval_flow_id
        ]);
    }
  }

  public function getApprovalAssignments($role_id)
  {

    $builder = $this->read_db->table('status');
    $builder->select(array('status_name', 'approve_item_name'));
    $builder->join('approval_flow', 'approval_flow.approval_flow_id=status.fk_approval_flow_id');
    $builder->join('approve_item', 'approve_item.approve_item_id=approval_flow.fk_approve_item_id');
    $builder->join('status_role', 'status_role.status_role_status_id=status.status_id');
    $builder->where(array('status_role.fk_role_id' => $role_id, 'status_approval_direction' => 1));
    $status = $builder->get()->getResultArray();

    return $status;
  }

  function detailTables(): array {
    return ['status_role'];
  }

  function createChangeHistoryOnStatusChange($new_data, $old_data, $table)
  {
    // Insert Update History
    $builder = $this->read_db->table('approve_item');
    $builder->where(array('approve_item_name' => strtolower($table)));
    $update_data['fk_approve_item_id'] = $builder->get()->getRow()->approve_item_id;

    $update_data['fk_user_id'] = $this->session->user_id;
    $update_data['history_action'] = 1; // 1 = Update, 2 = Delete
    $update_data['history_current_body'] = json_encode($old_data);
    $update_data['history_updated_body'] = json_encode($new_data);
    $update_data['history_created_date'] = date('Y-m-d');
    $update_data['history_created_by'] = $this->session->user_id;
    $update_data['history_last_modified_by'] = $this->session->user_id;

    $this->write_db->table('history')->insert($update_data);
  }

  function getApprovalStepsForAccountSystemApproveItem($account_system_id, $approveable_item_name){
    $builder = $this->read_db->table("status");
    $builder->select(array('status_id','status_name','status_signatory_label','status_approval_sequence','status_approval_direction'));
    $builder->where(array('fk_account_system_id' => $account_system_id, 'approve_item_name' => $approveable_item_name));
    $builder->where(array('status_is_requiring_approver_action' => 1));
    $builder->whereIn('status_approval_direction', [1]);
    $builder->join('approval_flow','approval_flow.approval_flow_id=status.fk_approval_flow_id');
    $builder->join('approve_item','approve_item.approve_item_id=approval_flow.fk_approve_item_id');
    $builder->orderBy('status_approval_sequence','ASC');
    $status_obj = $builder->get();

    $status = [];

    if($status_obj->getNumRows() > 0){
      $status = $status_obj->getResultArray();
    }

    return $status;
  }

  function getDeclineStatusIds($approve_item){

    $status_ids = []; 
    $approve_item = strtolower($approve_item);

    $builder = $this->read_db->table('status');
    $builder->select(array('status_id'));
    $builder->where(array('status_approval_direction' => -1, 'approve_item_name' => $approve_item));
    $builder->join('approval_flow','approval_flow.approval_flow_id=status.fk_approval_flow_id');
    $builder->join('approve_item','approve_item.approve_item_id=approval_flow.fk_approve_item_id');
    $status_ids_obj = $builder->get();

    if($status_ids_obj->getNumRows() > 0){
      $status_ids = array_column($status_ids_obj->getResultArray(), 'status_id');
    }

    return $status_ids;
  }

}