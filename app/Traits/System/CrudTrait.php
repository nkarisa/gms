<?php

namespace App\Traits\System;

use CodeIgniter\Database\Exceptions\DatabaseException;

trait CrudTrait
{
    public function add(string $tableName = '', array $postArray = [])
    {
        if (isEmpty($tableName)) {
            $tableName = strtolower($this->controller);
        }

        if (empty($postArray)) {
            $postArray = $this->request->getPost();
        }

        $postArray = $this->callbackActionBeforeInsert($tableName, $postArray);

        if (!array_key_exists('header', $postArray)) {
            return $this->response->setJSON(['flag' => false, 'message' => $postArray['message']]);
        }

        extract($postArray);

        $postHasDetail = isset($detail);
        $detail = $postHasDetail ? $detail : [];

        $approveItemLibrary = new \App\Libraries\Core\ApproveItemLibrary();
        $headerRecordRequiresApproval = $approveItemLibrary->approveableItem($tableName);
        $detailRecordsRequireApproval = $approveItemLibrary->approveableItem($this->dependantTable($tableName));

        $multiSelectFieldValues = [];

        if (!isEmpty($this->multiSelectField())) {
            $multiSelectFieldName = 'fk_' . $this->multiSelectField() . '_id';
            $multiSelectFieldValues = $header[$multiSelectFieldName];
        }

        $message = "";

        if (count($multiSelectFieldValues) > 0) {

            unset($header[$multiSelectFieldName]);

            $onflyCreatedMultiSelects = [];

            foreach ($header as $columnName => $formValues) {
                if (is_array($formValues)) {
                    $onflyCreatedMultiSelects[$columnName] = $formValues;
                }
            }

            $success = 0;
            $failed = 0;
            foreach ($multiSelectFieldValues as $multiSelectFieldValue) {

                $header[$multiSelectFieldName] = $multiSelectFieldValue;

                if (!empty($onflyCreatedMultiSelects)) {
                    foreach ($onflyCreatedMultiSelects as $_columnName => $_columnValues) {
                        $header[$_columnName] = $_columnValues[$multiSelectFieldValue];
                    }
                }

                $returnedValidationMessage = $this->addInserts($tableName, $headerRecordRequiresApproval, $detailRecordsRequireApproval, $postHasDetail, $header, $detail);
                if (json_decode($returnedValidationMessage, true)['flag'] == true) {
                    $success++;
                } else {
                    $failed++;
                }
            }

            $message .= $success . ' ' . str_replace('_', ' ', $this->controller) . ' inserted and ' . $failed . ' failed';

            $message = json_encode(['flag' => true, 'message' => $message]);

        } else {
            $message = $this->addInserts($tableName, $headerRecordRequiresApproval, $detailRecordsRequireApproval, $postHasDetail, $header, $detail);
        }

        return $this->response->setJSON($message);
    }

    public function addInserts($tableName, $headerRecordRequiresApproval, $detailRecordsRequireApproval, $postHasDetail, $header, $detail = [])
    {
        $statusLibrary = new \App\Libraries\Core\StatusLibrary();
        $initialStatus = $statusLibrary->initialItemStatus($tableName);

        $this->read_db->transStart();

        // Create the approval ticket if required by the header record
        $approvalLibrary = new \App\Libraries\Core\ApprovalLibrary();
        $approvalId = $approvalLibrary->insertApprovalRecord(strtolower($tableName));

        if ($this->id) {
            $decodedHashId = hash_id($this->id, 'decode');
            $query = $this->read_db->table($this->session->get('master_table'))
                ->getWhere([$this->session->get('master_table') . '_id' => $decodedHashId]);
            $row = $query->getRow();
            if ($row) {
                $approvalId = $row->fk_approval_id;
            }
        }

        // Prepare the header columns
        $headerColumns = [];
        $headerRandom = record_prefix($this->controller) . '-' . rand(1000, 90000);
        $headerColumns[strtolower($this->controller) . '_track_number'] = $headerRandom;
        $headerColumns[strtolower($this->controller) . '_name'] = !isEmpty($this->request->getPost($this->controller . '_name')) ? $this->request->getPost($this->controller . '_name') : ucfirst($this->controller) . ' # ' . $headerRandom;

        foreach ($header as $key => $value) {
            $headerColumns[$key] = $value;
        }
        if ($this->session->has('master_table')) {
            $headerColumns['fk_' . strtolower($this->session->get('master_table')) . '_id'] = hash_id($this->id, 'decode');
        }

        $headerColumns['fk_status_id'] = $initialStatus;
        $headerColumns['fk_approval_id'] = $approvalId;
        $headerColumns[strtolower($this->controller) . '_created_date'] = date('Y-m-d');
        $headerColumns[strtolower($this->controller) . '_created_by'] = $this->session->get('user_id');
        $headerColumns[strtolower($this->controller) . '_last_modified_by'] = $this->session->get('user_id');

        // Insert header record - Has to be done with a Model instead of a Query Builder Class
        $this->write_db->table(strtolower($this->controller))->insert($headerColumns);
        $headerId = $this->write_db->insertID();

        // Proceed with inserting details if $postHasDetail
        if ($postHasDetail) {
            $detailArray = $detail;
            $detailColumns = [];
            $shiftedElement = array_shift($detail);

            for ($i = 0; $i < sizeof($shiftedElement); $i++) {
                foreach ($detailArray as $column => $values) {
                    if (strpos($column, '_name') !== false && $column !== $this->dependantTable($this->controller) . '_name') {
                        $column = 'fk_' . substr($column, 0, -5) . '_id';
                    }
                    $detailColumns[$i][$column] = $values[$i];

                    $detailRandom = record_prefix($this->dependantTable($this->controller)) . '-' . rand(1000, 90000);
                    $detailColumns[$i][$this->dependantTable($this->controller) . '_track_number'] = $detailRandom;
                    $detailColumns[$i]['fk_' . $this->controller . '_id'] = $headerId;
                    $detailColumns[$i]['fk_status_id'] = $statusLibrary->initialItemStatus($this->dependantTable($this->controller));
                    $detailColumns[$i]['fk_approval_id'] = $approvalId;
                    $detailColumns[$i][$this->dependantTable($this->controller) . '_created_date'] = date('Y-m-d');
                    $detailColumns[$i][$this->dependantTable($this->controller) . '_created_by'] = $this->session->get('user_id');
                    $detailColumns[$i][$this->dependantTable($this->controller) . '_modified_by'] = $this->session->get('user_id');
                }
            }
            // Has to be done with a Model instead of a query builder
            $this->write_db->table($this->dependantTable($this->controller))->insertBatch($detailColumns);
        }

        $model = $this->controller . '_model';
        $transactionValidateDuplicatesColumns = is_array($this->callbackTransactionValidateDuplicatesColumns($this->controller)) ? $this->callbackTransactionValidateDuplicatesColumns($this->controller) : [];
        $transactionValidateDuplicates = $this->transactionValidateDuplicates($this->controller, $header, $transactionValidateDuplicatesColumns);
        $transactionValidateByComputation = $this->callbackTransactionValidateByComputation($this->controller, $header);

        $result = $this->transactionValidate([$transactionValidateDuplicates, $transactionValidateByComputation], $headerColumns, $headerId, $approvalId);

        $this->write_db->transComplete();

        if ($this->write_db->transStatus() === false) {
            $this->write_db->transRollback();
            // Handle transaction error
            return false;
        }

        return $result;
    }

    public function transactionValidate($validation_flags_and_failure_messages, $post_array = [], $header_id = 0, $approval_id = 0)
    {
        $message = '';
        $message_and_flag = ['flag' => false];
        $validation_flags = array_column($validation_flags_and_failure_messages, 'flag');

        $this->write_db->transStart();

        try {
            if (in_array(false, $validation_flags)) {
                $this->write_db->transRollback();

                foreach ($validation_flags_and_failure_messages as $validation_check) {
                    if (!$validation_check['flag']) {
                        $message .= $validation_check['error_message'] . '\n';
                        $message_and_flag['flag'] = $validation_check['flag'];
                        $message_and_flag['message'] = $message;
                    }
                }
            } else {
                if ($this->actionAfterInsert($post_array, $approval_id, $header_id)) {
                    $this->write_db->transComplete();

                    if ($this->write_db->transStatus() === false) {
                        throw new DatabaseException('Transaction failed.');
                    }

                    $message = get_phrase('insert_successful');
                    $message_and_flag['flag'] = true;
                    $message_and_flag['message'] = $message;
                    $message_and_flag['header_id'] = hash_id($header_id, 'encode');
                    $message_and_flag['table'] = $this->controller;
                } else {
                    $this->write_db->transRollback();
                    $message = get_phrase('insert_failed');
                    $message_and_flag['message'] = $message;
                }
            }
        } catch (DatabaseException $e) {
            $this->write_db->transRollback();
            return json_encode(['flag' => false, 'message' => get_phrase('insert_failed')]);
        }

        return json_encode($message_and_flag);
    }

    public function transactionValidateDuplicates(string $table_name, array $insert_array, array $validation_fields = [], int $allowable_records = 0)
    {
        $validation_successful = true;
        $failure_message = get_phrase('no_duplicate_records');

        if (!empty($this->callbackTransactionValidateDuplicatesColumns($table_name)) && is_array($validation_fields) && count($validation_fields) > 0) {

            $validate_duplicates_columns = $this->callbackTransactionValidateDuplicatesColumns($table_name);

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

            $builder = $this->read_db->table($table_name);
            $builder->where($insert_array);
            $result = $builder->countAllResults();

            if ($result > $allowable_records) {
                $validation_successful = false; // Validation error flag
                $failure_message = get_phrase('duplicate_entries_not_allowed');
            }
        }

        return ['flag' => $validation_successful, 'error_message' => $failure_message];
    }

    protected function transactionValidateDuplicatesColumns(): array
    {
        return [];
    }

    protected function transactionValidateByComputationFlag(array $arrayToCheck)
    {
        return VALIDATION_SUCCESS; // OR VALIDATION_ERROR
    }

    protected function listTableWhere(\CodeIgniter\Database\BaseBuilder $queryBuilder): void
    {
        
    }

    public function orderListPage(): string
    {
        return ''; // Example - 'status_approval_sequence ASC';
    }

    public function list():array{
        return []; 
    }
}