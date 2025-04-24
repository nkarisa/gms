<?php 

namespace App\Traits\System;

trait ManipulationTrait {
    function add()
    {
  
      // There are 3 insert scenarios
      // Scenario 1: Master detail insert without a primary relationship and master requires approval
      // Scenario 2: Master detail insert without a primary relationship and master doesn't require approval
      // Scenario 3: Master detail insert with a primary relationship and master requires approval
      // Scenario 4: Master detail insert with a primary relationship and master doesn't require approval
      // Scenario 5: Single record insert that requires approval
      // Scenario 6: Single record insert that doesn't require approval
  
      // Asign the post input to $post_array
      $post_array = $this->request->getPost();
      // Check if there is a before insert method set in the feature model wrapped via grants model
      $post_array = $this->actionBeforeInsert($post_array);
  
      if (!array_key_exists('header', $post_array)) {
        $message = get_phrase('add_operation_failed');
        return $this->response->setJSON(['flag' => false, 'message' => isset($post_array['message']) ? $post_array['message'] : $message]);
      }
      //$detail = [];
      // Extract the post array into header and detail variables
      extract($post_array);
  
      // Determine if the input post has details or not by checking if the detail variable is set
      $post_has_detail = isset($detail) ? true : false;
      $detail = $post_has_detail ? $detail : [];
  
      // Check if the creation of the of the header and detail records requires an approval ticket
      // $approveItemLibrary = new \App\Libraries\Core\ApproveItemLibrary();
      // $header_record_requires_approval = $approveItemLibrary->approveableItem($this->controller);
      //$detail_records_require_approval = $this->approveable_item($this->controller.'_detail');
      // $detail_records_require_approval = $approveItemLibrary->approveableItem($this->dependantTable($this->controller));
  
      // Get the table name of multi select field
      $multi_select_field_name = 'fk_' . $this->multiSelectField() . '_id';
  
      $multi_select_field_values = [];
  
      if ($this->multiSelectField() != '') {
        $multi_select_field_values = $header[$multi_select_field_name];
      }
  
      $message = "";
      if (count($multi_select_field_values) > 0) {
  
        unset($header[$multi_select_field_name]);
  
        $onfly_created_multi_selects = [];
  
        // Find any available on-fly multi select values from a model action_before_insert method
        foreach ($header as $column_name => $form_values) {
          if (is_array($form_values)) {
            $onfly_created_multi_selects[$column_name] = $form_values;
          }
        }
  
        $success = 0;
        $failed = 0;

        foreach ($multi_select_field_values as $multi_select_field_value) {
  
          $header[$multi_select_field_name] = $multi_select_field_value;
  
          if (!empty($onfly_created_multi_selects)) {
            foreach ($onfly_created_multi_selects as $_column_name => $_column_values) {
              // Need to understand this implementation - After which this code will be uncommented
              // $header[$_column_name] = $_column_values[$multi_select_field_value];
            }
          }
  
          $returned_validation_message = $this->addInserts($post_has_detail, $header, $detail);
          if ($returned_validation_message['flag'] == true) {
            $success++;
          } else {
            $failed++;
          }
        }
  
        $message .= $success . ' ' . str_replace('_', ' ', $this->controller) . ' inserted and ' . $failed . ' failed';
  
        $message = ['flag' => true, 'message' => $message];
  
      } else {
  
        $message = $this->addInserts($post_has_detail, $header, $detail);
      }
  
      return $this->response->setJSON($message);
    }
  
    public function insertApprovalRecord($approveableItem)
    {
      // $this->write_db->resetQuery();
      $insertId = 0;
  
      // Prepare approval data
      $approvalRandom = record_prefix('Approval') . '-' . rand(1000, 90000);
      $approval = [
        'approval_track_number' => $approvalRandom,
        'approval_name' => 'Approval Ticket # ' . $approvalRandom,
        'approval_created_by' => session()->get('user_id') ? session()->get('user_id') : 1,
        'approval_created_date' => date('Y-m-d'),
        'approval_last_modified_by' => session()->get('user_id') ? session()->get('user_id') : 1,
        'fk_approve_item_id' => $this->write_db->table('approve_item')
          ->getWhere(['approve_item_name' => strtolower($approveableItem)])
          ->getRow()
          ->approve_item_id,
        'fk_status_id' => $this->initialItemStatus($approveableItem)
      ];
  
      // Insert approval record
      $this->write_db->table('approval')->insert($approval);
  
      // Get the insert ID
      $insertId = $this->write_db->insertID();
  
      return $insertId;
    }
  
  
    public function addInserts($postHasDetail, $header, $detail = []): array
    {
      $initialStatus = $this->initialItemStatus($this->controller);
  
      $this->write_db->transBegin();
  
      // Create the approval ticket if required by the header record
      $approvalId = $this->insertApprovalRecord(strtolower($this->controller));
  
      // $approval = [];
      // $details = [];
  
      if ($this->id) {
        $decodedHashId = hash_id($this->id, 'decode');
  
        $approvalId = $this->write_db->table(strtolower(session()->get('masterTable')))
          ->getWhere([session()->get('masterTable') . '_id' => $decodedHashId])
          ->getRow()
          ->fk_approval_id;
      }
  
      // Prepare the header columns for insertion
      $headerColumns = [];
      $additionalHeaderColumns = [];
      $headerRandom = record_prefix($this->controller) . '-' . rand(1000, 90000);
      $headerColumns[strtolower($this->controller) . '_track_number'] = $headerRandom;
      $headerColumns[strtolower($this->controller) . '_name'] = $this->request->getPost($this->controller . '_name') != ""
        ? $this->request->getPost($this->controller . '_name')
        : ucfirst($this->controller) . ' # ' . $headerRandom;
  
      $allFieldName = array_column($this->tableFieldsMetadata($this->controller),'name');  
      foreach ($header as $key => $value) {
        // Unset columns that are not part of the table columns
        if (!in_array($key, $allFieldName)) {
          $additionalHeaderColumns[$key] = $value;
          continue;
        } 
        $headerColumns[$key] = $value;
      }
  
      if (session()->has('masterTable')) {
        $headerColumns['fk_' . strtolower(session()->get('masterTable')) . '_id'] = hash_id($this->id, 'decode');
      }
  
      $headerColumns['fk_status_id'] = $this->controller != 'status' ? $initialStatus : NULL;
      $headerColumns['fk_approval_id'] = $this->controller != 'status' ? $approvalId : NULL;
      $headerColumns[strtolower($this->controller) . '_created_date'] = date('Y-m-d');
      $headerColumns[strtolower($this->controller) . '_created_by'] = session()->get('user_id');
      $headerColumns[strtolower($this->controller) . '_last_modified_by'] = session()->get('user_id');
  
      // Insert the header record
      $this->write_db->table(strtolower($this->controller))->insert($headerColumns);
  
      // Get the inserted header record ID
      $headerId = $this->write_db->insertID();
      ;
      // Proceed with inserting details if $postHasDetail is true
      if ($postHasDetail) {
        $detailArray = $detail;
        $detailColumns = [];
        $shiftedElement = array_shift($detail);
  
        // Construct an insert batch array for details
        for ($i = 0; $i < sizeof($shiftedElement); $i++) {
          foreach ($detailArray as $column => $values) {
            if (strpos($column, '_name') === true && $column !== $this->dependantTable($this->controller) . '_name') {
              $column = 'fk_' . substr($column, 0, -5) . '_id';
            }
            $detailColumns[$i][$column] = $values[$i];
  
            $detailRandom = record_prefix($this->dependantTable($this->controller)) . '-' . rand(1000, 90000);
            $detailColumns[$i][$this->dependantTable($this->controller) . '_track_number'] = $detailRandom;
            $detailColumns[$i]['fk_' . $this->controller . '_id'] = $headerId;
  
            $detailColumns[$i]['fk_status_id'] = $this->initialItemStatus($this->dependantTable($this->controller));
            $detailColumns[$i]['fk_approval_id'] = $approvalId;
  
            $detailColumns[$i][$this->dependantTable($this->controller) . '_created_date'] = date('Y-m-d');
            $detailColumns[$i][$this->dependantTable($this->controller) . '_created_by'] = session()->get('user_id');
            $detailColumns[$i][$this->dependantTable($this->controller) . '_modified_by'] = session()->get('user_id');
          }
        }
        // $details = $detailColumns;
  
        // Insert the details using insert batch
        $this->write_db->table($this->dependantTable($this->controller))->insertBatch($detailColumns);
      }
  
      $library = $this->loadLibrary($this->controller);
      $transactionValidateDuplicatesColumns = is_array($library->transactionValidateDuplicatesColumns())
        ? $library->transactionValidateDuplicatesColumns()
        : [];
  
      $transactionValidateDuplicates = $this->transactionValidateDuplicates($this->controller, $header, $transactionValidateDuplicatesColumns);
      $transactionValidateByComputation = $this->transactionValidateByComputation($this->controller, $header);
  
      // Merge the $additionalHeaderColumns and $headerColumns
      if(count($additionalHeaderColumns) > 0){
        $headerColumns = array_merge($headerColumns, $additionalHeaderColumns);
      }
      
      return $this->transactionValidate([$transactionValidateDuplicates, $transactionValidateByComputation], $headerColumns, $headerId, $approvalId);
    }
  
    public function transactionValidateDuplicates(string $table_name, array $insert_array, array $validation_fields = [], int $allowable_records = 0): array
    {
  
      $validation_successful = true;
      $failure_message = get_phrase('no_duplicate_records');
  
      // $model = $table_name . "_model";
      $library = $this->loadLibrary($table_name);
  
      if (method_exists($library, 'transactionValidateDuplicatesColumns') && is_array($validation_fields) && count($validation_fields) > 0) {
  
        $validate_duplicates_columns = $library->transactionValidateDuplicatesColumns();
  
        $insert_array_keys = array_unique(array_merge(array_keys($insert_array), $validate_duplicates_columns));
  
        foreach ($insert_array_keys as $insert_column) {
  
          if (!array_key_exists($insert_column, $insert_array)) {
            $missing_field_in_insert_array = [$insert_column => 1];
            $insert_array = array_merge($insert_array, $missing_field_in_insert_array);
          }
  
          if (!in_array($insert_column, $validation_fields)) {
            unset($insert_array[$insert_column]);
          }
        }
  
        $result = $this->write_db->table($table_name)
          ->where($insert_array)->get()->getNumRows();
  
        if ($result > $allowable_records) {
          $validation_successful = false; // Validation error flag
  
          $failure_message = get_phrase('duplicate_entries_not_allowed');
        }
      }
  
      return ['flag' => $validation_successful, 'error_message' => $failure_message];
    }
  
    function transactionValidateByComputation(string $table_name, array $insert_array): array
    {
  
      $validation_successful = true;
      $failure_message = get_phrase('validation_failed');
  
      $library = $this->loadLibrary($table_name);
  
      if (method_exists($library, 'transactionValidateByComputationFlag')) {
        if ($library->transactionValidateByComputationFlag($insert_array) == 'VALIDATION_ERROR') {
          $validation_successful = false;
        }
      }
  
      return ['flag' => $validation_successful, 'error_message' => $failure_message];
  
    }
  
    public function transactionValidate($validationFlagsAndFailureMessages, $postArray = [], $headerId = 0, $approvalId = 0): array
    {
      $message = '';
      $messageAndFlag = [];
      $messageAndFlag['flag'] = false;
      $library = $this->loadLibrary($this->controller);
  
      // Extract flags from validation
      $validationFlags = array_column($validationFlagsAndFailureMessages, 'flag');
  
      // Check if the transaction status is valid
      if ($this->write_db->transStatus() === false) {
        $this->write_db->transRollback();
        $messageAndFlag['message'] = get_phrase('insert_failed');
      } else {
        // If any validation flag is false, rollback
        if (in_array(false, $validationFlags)) {
          $this->write_db->transRollback();
  
          foreach ($validationFlagsAndFailureMessages as $validationCheck) {
            if (!$validationCheck['flag']) {
              $message .= $validationCheck['error_message'] . "\n";
              $messageAndFlag['flag'] = $validationCheck['flag'];
              $messageAndFlag['message'] = $message;
            }
          }
        } else {
          // If the insert action is successful
          if ($library->actionAfterInsert($postArray, $approvalId, $headerId)) {
            $this->write_db->transCommit();
            $message = get_phrase('insert_successful');
            $messageAndFlag['flag'] = true;
            $messageAndFlag['message'] = $message;
            $messageAndFlag['header_id'] = hash_id($headerId, 'encode');
            $messageAndFlag['table'] = $this->controller;
          } else {
            $this->write_db->transRollback();
            $message = get_phrase('insert_failed');
            $messageAndFlag['message'] = $message;
          }
        }
      }
  
      return $messageAndFlag;
    }

    public function hasDuplicateRecord($table, $id, $checkDuplicateColumns, $postArray)
    {
      // This query is to extract data to cater for columns that are not sourced from the edit form
      $builder = $this->read_db->table($table);
      $builder->select($checkDuplicateColumns);
      $builder->where([$table . '_id' => hash_id($id, 'decode')]);
      $postedRecord = $builder->get()->getRowArray(); // Equivalent to row_array() in CI3
  
      // Merge the posted array with the database record
      $postArray = array_merge($postArray, $postedRecord);
  
      // Initialize duplicate record flag
      $hasDuplicateRecord = false;
  
      if (count($checkDuplicateColumns) > 0) {
        $cols = [];
  
        // Loop through post data to build the query condition for duplicates
        foreach ($postArray as $field => $value) {
          if (in_array($field, $checkDuplicateColumns) || $field === 'fk_account_system_id') {
            $cols[$field] = $value;
          }
        }
  
        // Exclude the current record from the duplicate check
        $builder = $this->read_db->table($table);
        $builder->where($table . '_id !=', $id);
  
        if (count($cols) > 0) {
          // Apply the duplicate check condition
          $builder->where($cols);
          $numRows = $builder->countAllResults(); // Equivalent to num_rows() in CI3
  
          // If the count of rows is greater than 0, mark as duplicate
          if (($this->action == 'singleFormAdd' && $numRows > 0) || ($this->action == 'edit' && $numRows > 1)) {
            $hasDuplicateRecord = true;
          }
        }
      }
      return $hasDuplicateRecord;
    }
  
    public function createChangeHistory(array $newData, bool $excludeUsingNewDataColumns = false, array $criticalColumnsToUnset = [], string $table = "", $itemId = 0)
    {
      // Determine the table name

      $table = empty($table) ? $this->controller : $table;
  
      // Determine the item ID
      $itemId = $itemId == 0 ? $this->id : $itemId;
  
      // Get table fields
      $fields = $this->read_db->getFieldNames($table); // To be taken from the schema file instead of database table directly
  
      $tableId = strtolower($table) . "_id";
      $decodeId = is_numeric($itemId) ? $itemId : hash_id($itemId, 'decode'); // Assuming hash_id is still in use
      $newData[$tableId] = $decodeId;
  
      // Use fields from new data if not excluding
      if (!$excludeUsingNewDataColumns) {
        $fields = array_keys($newData);
      }
  
      // Determine which fields to include in the query by removing critical columns
      $cols = array_diff($fields, $criticalColumnsToUnset);
  
      // Select old data from the table
      $builder = $this->read_db->table(strtolower($table));
      $builder->select($cols);
      $oldData = $builder->where([$tableId => $decodeId])
        ->get()
        ->getRowArray(); // CI4 equivalent of row_array()
  
      // Prepare the update data for history table
      // $bld=$this->read_db->table('approve_item')->select(['approve_item_id'])->where(['approve_item_name' => strtolower($table)]);
      // $approve_item_id=$bld->get()->getRow()->approve_item_id;

      

      $update_data['fk_approve_item_id'] = $this->read_db->table('approve_item')->where('approve_item_name', strtolower($table))->get()->getRow()->approve_item_id;
      

      $update_data['fk_user_id'] = $this->session->user_id;
      $update_data['history_action'] = 1; // 1 = Update, 2 = Delete
      $update_data['history_current_body'] = json_encode($oldData);
      $update_data['history_updated_body'] = json_encode($newData);
      $update_data['history_created_date'] = date('Y-m-d');
      $update_data['history_created_by'] = $this->session->user_id;
      $update_data['history_last_modified_by'] = $this->session->user_id;
  
      // Insert update history into 'history' table
      $builder_arr = $this->write_db->table('history');
      $builder_arr->insert($update_data);
    }
  
    public function edit(string $id): \CodeIgniter\HTTP\Response
    {
  
      $library = $this->loadLibrary($this->controller);
      $postArray = $this->request->getPost(); // Equivalent to $this->input->post() in CI3
  
      // Call action_before_edit from grants service
      $postArray = $library->actionBeforeEdit($postArray);
  
      $flag = false;
      $flagMessage = "";
  
      if (is_array($postArray) && !empty($postArray) && !array_key_exists('error', $postArray)) {
        extract($postArray);
        // $id = hash_id($id, 'decode');
        $data = $header;
        $approvalId = 0; // To be evaluated later
  
        $transactionValidateDuplicatesColumns = is_array($library->transactionValidateDuplicatesColumns())
          ? $library->transactionValidateDuplicatesColumns()
          : [];
  
        $hasDuplicateRecord = $this->hasDuplicateRecord(
          $this->controller,
          $id,
          $transactionValidateDuplicatesColumns,
          $data
        );
  
        if (!$hasDuplicateRecord) {
          $this->write_db->transBegin(); // Begin transaction
          $this->write_db->table($this->controller)
            ->where([$this->primaryKeyField($this->controller) => hash_id($id, 'decode')])
            ->update($data); // Update the table
  
          $this->createChangeHistory($data); // Create change history
  
          if ($this->write_db->transStatus() === false) {
            $this->write_db->transRollback();
            $flagMessage = get_phrase('update_not_successful');
          } else {
            if ($library->actionAfterEdit($data, $approvalId, hash_id($id, 'decode'))) {
              $this->write_db->transCommit();
              $flag = true;
              $flagMessage = get_phrase('update_completed');
            } else {
              $flag = false;
              $flagMessage = get_phrase('update_not_successful');
              $this->write_db->transRollback();
            }
          }
        } else {
          $flagMessage = get_phrase('duplicates_not_allowed');
        }
      } else {
        $flagMessage = get_phrase('edit_not_allowed');
        if (array_key_exists('error', $postArray)) {
          $flagMessage = $postArray['error'];
        }
      }
  
      // Return JSON response
      return $this->response->setJSON(['flag' => $flag, 'message' => $flagMessage]);
    }


    function mergeWithHistoryFields(string $approve_item_name, array $array_to_merge, bool $add_name_to_array = true, $is_a_new_record = true)
    {
  
      $approvalLibrary = new \App\Libraries\Core\ApprovalLibrary();
      $statusLibary = new \App\Libraries\Core\StatusLibrary();
  
      $data = [];
  
      if ($is_a_new_record) {
  
        $data[$approve_item_name . '_track_number'] = $this->generateItemTrackNumberAndName($approve_item_name)[$approve_item_name . '_track_number'];
        $data[$approve_item_name . '_created_by'] = $this->session->user_id ? $this->session->user_id : 1;
        $data[$approve_item_name . '_created_date'] = date('Y-m-d');
        $data['fk_approval_id'] = $approvalLibrary->insertApprovalRecord($approve_item_name);
        $data['fk_status_id'] = $statusLibary->initialItemStatus($approve_item_name);
  
      } else {
        $data[$approve_item_name . '_last_modified_date'] = date('Y-m-d h:i:s');
        $data[$approve_item_name . '_last_modified_by'] = $this->session->user_id ? $this->session->user_id : 1;
      }
  
      if ($add_name_to_array) {
        $data[$approve_item_name . '_name'] = $this->generateItemTrackNumberAndName($approve_item_name)[$approve_item_name . '_name'];
      }
  
      return array_merge($array_to_merge, $data);
    }
  
}