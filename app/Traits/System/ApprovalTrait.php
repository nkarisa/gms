<?php 

namespace App\Traits\System;

trait ApprovalTrait {
    function actionLabels($table, $primary_key)
  {
    return $this->displayApproverStatusAction(session()->get('role_ids'), $table, $primary_key);
  }

  /**
   * get_status_id
   * 
   * Gives you the status id of a selected item
   * 
   * @param $table String
   * 
   * @param $primary  Int - Item primary key
   * 
   * @return int
   */
  function getStatusId(string $table, int $primaryKey): int
  {
    $fkStatusId = 0;

    // Get database connection
    $builder = $this->read_db->table($table);

    // Run the query to get the record
    $recordObject = $builder->getWhere([$table . '_id' => $primaryKey]);

    // Check if the record exists and if 'fk_status_id' exists in the result
    if ($recordObject->getNumRows() > 0 && array_key_exists('fk_status_id', (array) $recordObject->getRowArray())) {
      $status_id = $recordObject->getRow()->fk_status_id;
      $fkStatusId = $status_id != null ? $status_id : 0;
    }

    return $fkStatusId;
  }

  function currentApprovalActor($itemStatus)
  {

    $builder = $this->read_db->table('status');

    // Fetch the status based on the item status
    $builder->where(['status_id' => $itemStatus]);
    $status = $builder->get()->getRow();

    $roles = [];

    if ($status) {
      // Build the condition for approval direction
      if ($status->status_approval_direction == 1 || $status->status_approval_direction == 0) {
        $sequence = $status->status_approval_sequence;
      } else {
        $sequence = $status->status_backflow_sequence;
      }

      // Prepare query for roles
      $builder = $this->read_db->table('status_role');
      $builder->select('fk_role_id');
      $builder->where([
        'fk_approval_flow_id' => $status->fk_approval_flow_id,
        'status_role_is_active' => 1,
        'status_approval_direction' => 1,
        'status_approval_sequence' => $sequence
      ]);

      $builder->join('status', 'status.status_id = status_role.status_role_status_id');
      $rolesObj = $builder->get();

      // Extract roles if they exist
      if ($rolesObj->getNumRows() > 0) {
        $roles = array_column($rolesObj->getResultArray(), 'fk_role_id');
      }
    }

    // Return the roles array
    return $roles;
  }

  function rangeOfStatusApprovalSequence($approveItemName)
  {

    $builder = $this->read_db->table('status');

    // Select the max status approval sequence
    $builder->select('MAX(status_approval_sequence) as status_approval_sequence');
    $builder->join('approval_flow', 'approval_flow.approval_flow_id = status.fk_approval_flow_id');
    $builder->join('approve_item', 'approve_item.approve_item_id = approval_flow.fk_approve_item_id');

    $builder->where([
      'approve_item_name' => $approveItemName,
      'approval_flow.fk_account_system_id' => session()->get('user_account_system_id')
    ]);

    $maxRange = $builder->get()->getRow()->status_approval_sequence;

    return $maxRange;
  }

  function getApproveableItemIdByStatus($itemStatus)
  {

    $builder = $this->read_db->table('approve_item');

    // Select the approve item id
    $builder->select('approve_item_id');
    $builder->join('approval_flow', 'approval_flow.fk_approve_item_id = approve_item.approve_item_id');
    $builder->join('status', 'status.fk_approval_flow_id = approval_flow.approval_flow_id');

    $builder->where('status_id', $itemStatus);

    $result = $builder->get()->getRow()->approve_item_id;

    return $result;
  }

  function getApproveItemNameByStatus($itemStatus)
  {
    $builder = $this->read_db->table('approve_item');

    // Select the approve item name
    $builder->select('approve_item_name');
    $builder->join('approval_flow', 'approval_flow.fk_approve_item_id = approve_item.approve_item_id');
    $builder->join('status', 'status.fk_approval_flow_id = approval_flow.approval_flow_id');

    $builder->where('status_id', $itemStatus);

    $result = $builder->get()->getRow()->approve_item_name;

    return $result;
  }

  function defaultRoleId()
  {
    $builder = $this->read_db->table('role');

    // Get the role where role_is_new_status_default is true
    $builder->where('role_is_new_status_default', 1);

    $resultObj = $builder->get();

    $defaultRoleId = 0;

    if ($resultObj->getNumRows() > 0) {
      $defaultRoleId = $resultObj->getRow()->role_id;
    }

    return $defaultRoleId;
  }

  function nextApprovalActor($itemStatus)
  {
    $builder = $this->read_db->table('status');

    // Get approval item name using the status_id
    $approveItemName = $this->getApproveItemNameByStatus($itemStatus);

    // Get the range of status approval sequence
    $rangeOfStatusApprovalSequence = $this->rangeOfStatusApprovalSequence($approveItemName);

    // Get approveable item id
    $approveableItemId = $this->getApproveableItemIdByStatus($itemStatus);

    // Get the status record
    $builder->where(['status_id' => $itemStatus]);
    $statusRecord = $builder->get()->getRow();

    // Check if the status record exists
    if (!$statusRecord) {
      return [$this->defaultRoleId()];
    }

    // Get the value of the approval direction
    $statusApprovalDirection = $statusRecord->status_approval_direction;

    // Calculate the next and previous possible sequence numbers
    $nextPossibleSequenceNumber = $statusRecord->status_approval_sequence + 1;
    // $previousPossibleSequenceNumber = $statusRecord->status_approval_sequence - 1;

    $nextApprovalActorRoleIds = [$this->defaultRoleId()];

    // Check if this is not the last status
    if ($nextPossibleSequenceNumber <= $rangeOfStatusApprovalSequence) {

      if ($statusApprovalDirection == 1) {
        // Next approval step
        $builder = $this->read_db->table('status');
        $builder->select('status_role.fk_role_id as fk_role_id');
        $builder->join('approval_flow', 'approval_flow.approval_flow_id = status.fk_approval_flow_id');
        $builder->join('status_role', 'status_role.status_role_status_id = status.status_id');
        $builder->where([
          'status_approval_sequence' => $nextPossibleSequenceNumber,
          'fk_approve_item_id' => $approveableItemId,
          'approval_flow.fk_account_system_id' => session()->get('user_account_system_id')
        ]);

        $nextStatusRecordObj = $builder->get();
        $nextApprovalActorRoleIds = $nextStatusRecordObj->getNumRows() > 0
          ? array_unique(array_column($nextStatusRecordObj->getResultArray(), 'fk_role_id'))
          : [$this->defaultRoleId()];

      } elseif ($statusApprovalDirection == -1) {
        // Backward approval step
        $builder = $this->read_db->table('status');
        $builder->select('status_role.fk_role_id as fk_role_id');
        $builder->join('approval_flow', 'approval_flow.approval_flow_id = status.fk_approval_flow_id');
        $builder->join('status_role', 'status_role.status_role_status_id = status.status_id');
        $builder->where([
          'status_approval_sequence' => $statusRecord->status_approval_sequence,
          'fk_approve_item_id' => $approveableItemId,
          'approval_flow.fk_account_system_id' => session()->get('user_account_system_id'),
          'status_role_status_id' => $itemStatus
        ]);

        $nextStatusRecordObj = $builder->get();
        $nextApprovalActorRoleIds = $nextStatusRecordObj->getNumRows() > 0
          ? array_unique(array_column($nextStatusRecordObj->getResultArray(), 'fk_role_id'))
          : [$this->defaultRoleId()];
      }
    }

    return $nextApprovalActorRoleIds;
  }


  function getStatusName($itemStatus)
  {

    return $this->read_db->table('status')
      ->getWhere(['status_id' => $itemStatus])
      ->getRow()
      ->status_name;
  }

  function userActionLabel($itemStatus)
  {

    $statusObj = $this->read_db->table('status')
      ->where('status_id', $itemStatus)
      ->get();

    $label = '';

    if ($statusObj->getNumRows() > 0) {
      $status = $statusObj->getRow();
      $label = $status->status_name;
      if (!empty($status->status_button_label)) {
        $label = $status->status_button_label;
      }
    }

    return $label;
  }

  function showDeclineButton($itemStatus, $loggedRoleId)
  {
    $statusRecord = $this->read_db->table('status')
      ->getWhere(['status_id' => $itemStatus])
      ->getRow();

    $approvalDirection = $statusRecord->status_approval_direction;
    $hasDeclineButton = false;
    $currentActors = $this->currentApprovalActor($itemStatus);

    if (
      ($approvalDirection == 1 || $approvalDirection == 0) &&
      count($currentActors) > 0 &&
      !empty(array_intersect($loggedRoleId, $currentActors)) &&
      $statusRecord->status_approval_sequence != 1
    ) {
      $hasDeclineButton = true;
    }

    return $hasDeclineButton;
  }

  function nextStatus($itemStatus)
  {
    $nextStatusId = 0;

    $approveItemName = $this->getApproveItemNameByStatus($itemStatus);
    $rangeOfStatusApprovalSequence = $this->rangeOfStatusApprovalSequence($approveItemName);
    $approveableItemId = $this->getApproveableItemIdByStatus($itemStatus);

    $statusRecord = $this->read_db->table('status')
      ->getWhere(['status_id' => $itemStatus])
      ->getRow();

    $statusApprovalSequence = $statusRecord->status_approval_sequence;
    $backflowSequence = $statusRecord->status_backflow_sequence;

    if (($statusApprovalSequence < $rangeOfStatusApprovalSequence) && $backflowSequence == 0) {
      $nextApprovalSeq = $statusApprovalSequence + 1;

      $nextStatusIdObj = $this->read_db->table('status')
        ->join('approval_flow', 'approval_flow.approval_flow_id=status.fk_approval_flow_id')
        ->where([
          'status_approval_sequence' => $nextApprovalSeq,
          'fk_approve_item_id' => $approveableItemId,
          'approval_flow.fk_account_system_id' => session()->get('user_account_system_id')
        ])->get();

      if ($nextStatusIdObj->getNumRows() > 0) {
        $nextStatusId = $nextStatusIdObj->getRow()->status_id;
      }
    } elseif (($statusApprovalSequence == $rangeOfStatusApprovalSequence) && $backflowSequence == 0) {
      $nextStatusIdObj = $this->read_db->table('status')
        ->join('approval_flow', 'approval_flow.approval_flow_id=status.fk_approval_flow_id')
        ->where([
          'status_approval_sequence' => $statusApprovalSequence,
          'fk_approve_item_id' => $approveableItemId,
          'approval_flow.fk_account_system_id' => session()->get('user_account_system_id'),
          'status_approval_direction' => 1
        ])->get();

      if ($nextStatusIdObj->getNumRows() > 0) {
        $nextStatusId = $nextStatusIdObj->getRow()->status_id;
      }
    }

    if ($backflowSequence > 0) {
      $rolesIds = $this->read_db->table('status')
        ->join('approval_flow', 'approval_flow.approval_flow_id=status.fk_approval_flow_id')
        ->join('status_role', 'status_role.status_role_status_id=status.status_id')
        ->where([
          'status_approval_sequence' => $backflowSequence,
          'fk_role_id' => session()->get('role_id'),
          'fk_account_system_id' => session()->get('user_account_system_id')
        ])->get();

      if ($rolesIds->getNumRows() > 0) {
        $nextStatusId = $this->read_db->table('status')
          ->join('approval_flow', 'approval_flow.approval_flow_id=status.fk_approval_flow_id')
          ->where([
            'status_approval_sequence' => $statusApprovalSequence,
            'status_approval_direction' => 0,
            'fk_approve_item_id' => $approveableItemId,
            'approval_flow.fk_account_system_id' => session()->get('user_account_system_id')
          ])->get()->getRow()->status_id;
      } else {
        $nextStatusId = $this->read_db->table('status')
          ->join('approval_flow', 'approval_flow.approval_flow_id=status.fk_approval_flow_id')
          ->where([
            'status_approval_sequence' => $statusApprovalSequence,
            'fk_approve_item_id' => $approveableItemId,
            'status_approval_direction' => 1,
            'approval_flow.fk_account_system_id' => session()->get('user_account_system_id')
          ])->get()->getRow()->status_id;
      }
    }

    return $nextStatusId;
  }


  function declineStatus($itemStatus)
  {

    $nextDeclineStatus = 0;

    $statusRecord = $this->read_db->table('status')
      ->getWhere(['status_id' => $itemStatus])
      ->getRow();

    $approveableItemId = $this->getApproveableItemIdByStatus($itemStatus);
    $approvalSequence = $statusRecord->status_approval_sequence;

    $declineStatusRecord = $this->read_db->table('status')
      ->join('approval_flow', 'approval_flow.approval_flow_id=status.fk_approval_flow_id')
      ->where([
        'status_approval_sequence' => $approvalSequence,
        'status_approval_direction' => -1,
        'fk_approve_item_id' => $approveableItemId,
        'approval_flow.fk_account_system_id' => session()->get('user_account_system_id')
      ])->get();

    if ($declineStatusRecord->getNumRows() > 0) {
      $nextDeclineStatus = $declineStatusRecord->getRow()->status_id;
    }

    return $nextDeclineStatus;
  }

  function declineButtonLabel($itemStatus)
  {

    $statusObj = $this->read_db->table('status')
      ->join('approval_flow', 'approval_flow.approval_flow_id=status.fk_approval_flow_id')
      ->join('approve_item', 'approve_item.approve_item_id=approval_flow.fk_approve_item_id')
      ->where('status_id', $itemStatus)->get();

    $label = '';

    if ($statusObj->getNumRows() > 0) {
      $status = $statusObj->getRow();
      $statusId = $status->status_id;
      $statusDeclineButtonLabel = $status->status_decline_button_label;
      $initialItemStatus = $this->initialItemStatus($status->approve_item_name);
      $label = get_phrase('decline');

      if (!empty($statusDeclineButtonLabel) && $initialItemStatus != $statusId) {
        $label = $statusDeclineButtonLabel;
      }
    }

    return $label;
  }

  function initialItemStatus($tableName = "", $accountSystemId = 0): int
  {
    // $this->read_db->resetQuery();

    if ($accountSystemId == 0) {
      $accountSystemId = session()->get('user_account_system_id');
    }

    $table = $tableName == "" ? $this->controller : $tableName;

    $approveableItem = $this->read_db->table('approve_item')
      ->getWhere(['approve_item_name' => $table]);

    $statusId = 0;

    if ($approveableItem->getNumRows() > 0) {
      $approveableItemId = $approveableItem->getRow()->approve_item_id;
      $approveableItemIsActive = $approveableItem->getRow()->approve_item_is_active;

      // Initial condition array
      $conditionArray = [
        'fk_approve_item_id' => $approveableItemId,
        'status_approval_sequence' => 1,
        'fk_account_system_id' => $accountSystemId
      ];

      if (!$approveableItemIsActive) {
        // Condition for fully approved status if the item is inactive
        $conditionArray = [
          'fk_approve_item_id' => $approveableItemId,
          'status_is_requiring_approver_action' => 0,
          'fk_account_system_id' => $accountSystemId
        ];
      }

      $initialStatus = $this->read_db->table('status')
        ->join('approval_flow', 'approval_flow.approval_flow_id = status.fk_approval_flow_id')
        ->where($conditionArray)->get();

      if ($initialStatus->getNumRows() > 0) {
        $statusId = $initialStatus->getRow()->status_id;
      }
    }

    return $statusId;
  }

  function showLabelAsButton($item_status, $logged_role_id, $table, $primary_key)
  {
    $statusLibrary = new \App\Libraries\Core\StatusLibrary();
    $approveItemLibrary = new \App\Libraries\Core\ApproveItemLibrary();

    $max_approval_status_id = $statusLibrary->getMaxApprovalStatusId($table)[0];
    $current_approval_actors = $this->currentApprovalActor($item_status); // This is an array of current status role actors

    $logged_user_centers = array_column($this->session->hierarchy_offices, 'office_id');

    $is_approveable_item = $approveItemLibrary->approveableItem($table);

    $show_label_as_button = false;

    if (is_array($logged_role_id)) {
      if (
        (
          (is_array($logged_user_centers) &&
            (is_array($current_approval_actors) && !empty(array_intersect($logged_role_id, $current_approval_actors)))) &&
          $is_approveable_item) || $this->session->system_admin
      ) {
        $show_label_as_button = true;
      }
    } else {
      if (
        (
          (is_array($logged_user_centers) &&
            (is_array($current_approval_actors) && in_array($logged_role_id, $current_approval_actors))) &&
          $is_approveable_item) || $this->session->system_admin
      ) {
        $show_label_as_button = true;
      }
    }

    if ($max_approval_status_id == $item_status) {
      $show_label_as_button = false;
    }


    return $show_label_as_button;
  }


  function isMaxApprovalStatusId(string $approveable_item, int $status_id): bool
  {
    $is_max_status_id = false;

    $statusLibrary = new \App\Libraries\Core\StatusLibrary();
    $max_status_id = $statusLibrary->getMaxApprovalStatusId($approveable_item);

    if ($status_id == $max_status_id) {
      $is_max_status_id = true;
    }

    return $is_max_status_id;
  }


  function displayApproverStatusAction($logged_role_id, $table, $primary_key)
  {
    /**
     * Given the status find the following:
     * 
     * - Who is the next actor? - The next actor is the role id represented by the next approval sequence number.
     *    But if the next status has an approval direction of -1, then the next actor is the role_id directly
     *    Next actor for all declines is derived by the value of backflow sequence status item of an item
     *    the same approval sequence with a direction of -1, get the related role id
     * 
     * - What is the currect status label or both the actor and others viewers? For the actor use 
     *  (Submit to or Decline to [role_name] and for  and for others use Submitted to or Declined to [role_name]
     *  except for the last in the sequence to the label Completed) - Status Action Label field is redundant since labels will be generic
     *  If status has backflow value > 0 then use Reinstate to [role_name] when accessing as s the current user
     * 
     * - Who is the current actor? Use the role id directly. But when value of approval direction is -1 and 
     *  backflow sequence has a value, then used the role id represented by the value in the backflow sequence.
     * 
     * - Show a decline button when? Show when the current approval sequence has 1 0r 0 approval directions
     * 
     * - What is the next status? - Use the status id of the next approval sequence but if the 
     *   approval direction is -1 then use the status represented by the backflow sequence
     * 
     * - The last status is irreversible
     *  
     */

    $approval_button_info = [];

    $item_status = $this->getStatusId($table, $primary_key);
    //echo $primary_key;
    if ($item_status > 0) {

      $approval_button_info['current_actor_role_id'] = $this->currentApprovalActor($item_status);
      $approval_button_info['next_actor_role_id'] = $this->nextApprovalActor($item_status);
      $approval_button_info['status_name'] = $this->getStatusName($item_status);
      $approval_button_info['button_label'] = $this->userActionLabel($item_status);
      $approval_button_info['show_decline_button'] = $this->showDeclineButton($item_status, $logged_role_id);
      $approval_button_info['next_approval_status'] = $this->nextStatus($item_status);
      $approval_button_info['next_decline_status'] = $this->declineStatus($item_status);
      $approval_button_info['show_label_as_button'] = $this->showLabelAsButton($item_status, $logged_role_id, $table, $primary_key);
      $approval_button_info['is_max_approval_status'] = $this->isMaxApprovalStatusId($table, $item_status);
      $approval_button_info['decline_button_label'] = $this->declineButtonLabel($item_status);
    }
    // print_r($approval_button_info);
    // exit;
    return $approval_button_info;
  }

  function actionButtonData($item_type, $account_system_id)
  {
    $userLibrary = new \App\Libraries\Core\UserLibrary();
    $statusLibrary = new \App\Libraries\Core\StatusLibrary();
    $item_max_approval_status_ids = $statusLibrary->getMaxApprovalStatusId($item_type, [], $account_system_id);
    $initial_item_status = $statusLibrary->initialItemStatus($item_type, $account_system_id);

    $data['item_max_approval_status_ids'] = $item_max_approval_status_ids;
    $data['item_status'] = $statusLibrary->itemStatus($item_type, $initial_item_status, $account_system_id);
    $data['item_initial_item_status_id'] = $initial_item_status;
    $data['permissions'] = [
      'update' => $userLibrary->checkRoleHasPermissions($item_type, 'update'),
      'delete' => $userLibrary->checkRoleHasPermissions($item_type, 'delete')
    ];
    $data['active_approval_actor'] = [];

    return $data;
  }
}