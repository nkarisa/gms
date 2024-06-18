<?php

namespace App\Libraries\System;

use App\Interfaces\DynamicMethodsInterface;
use Config\GrantsConfig;
use CodeIgniter\Database\Exceptions\DatabaseException;

class GrantsLibrary implements DynamicMethodsInterface
{
  protected $read_db;
  protected $write_db;
  protected $config;
  protected $controller;
  protected $action;
  protected $id;
  protected $dependant_table = null;
  protected $session;
  protected $request;
  protected $response;

  function __construct()
  {
    // Load grants config
    $this->config = config(GrantsConfig::class);
    // Load default helpers
    helper(['grants','inflector']);
    // Load database
    $this->read_db = \Config\Database::connect('read');
    $this->write_db = \Config\Database::connect('write');

    // Set controller, action and ids
    $uri = service('uri');
    // $this->controller = $uri->getSegment(1);
    // $this->action = $uri->getSegment(2);
    // $this->id = $uri->getSegment(3);
    $segments = $uri->getSegments();

    $this->controller = isset($segments[0]) ? $segments[0] : 'dashboard';
    $this->action = isset($segments[1]) ? $segments[1] : 'list';
    $this->id = isset($segments[2]) ? $segments[2] : 0;

    // Session 
    $this->session = service('session');

    // Request 
    $this->request = service('request'); 

    // Response 
    $this->response = service('response'); // Services::response()
  }
  private function callbackActionAfterInsert($table_name, $post_array, $approval_id, $header_id): array
  {

    $featureLibrary = $this->loadLibrary($table_name);

    $success = false;

    if (method_exists($featureLibrary, 'actionAfterInsert')) {
      $success = $featureLibrary->actionAfterInsert($post_array, $approval_id, $header_id);
    } 

    return $success;
  }

  public function actionAfterInsert(array $post_array, int $approval_id, int $header_id): bool{
    return true;
  }

  private function callbackActionBeforeInsert($table_name, $post_array): array
  {

    $featureLibrary = $this->loadLibrary($table_name);

    $updated_post_array = array();

    if (method_exists($featureLibrary, 'actionBeforeInsert')) {
      $updated_post_array = $featureLibrary->actionBeforeInsert($post_array);
    } else {
      $updated_post_array = $post_array;
    }

    return $updated_post_array;
  }

  private function callbackTransactionValidateDuplicatesColumns($table_name){

    $featureLibrary = $this->loadLibrary($table_name);

    $columns = array();

    if (method_exists($featureLibrary, 'transactionValidateDuplicatesColumns')) {
      $columns = $featureLibrary->transactionValidateDuplicatesColumns();
    }

    return $columns;
  }

  public function actionBeforeInsert(array $postArray): array {
    return $postArray;
    }

    private  function callbackMultiSelectField($table_name): string{
        $library = $this->loadLibrary($table_name);

        $multi_select_field =  '';
    
        if (
          method_exists($library, 'multiSelectField') &&
          strlen($library->multiSelectField()) > 0 &&
          $this->action !== 'edit'
        ) {
    
          $multi_select_field = $library->multiSelectField();
        }
    
        return $multi_select_field;
    }

    public function multiSelectField(): string{
        return '';
    }

    public function transactionValidateDuplicatesColumns(): array {
        return [];
    }
    public function transactionValidateByComputationFlag(array $insert_array): string {
        return '';
    }
  /**
 * Retrieves the schema of the database tables.
 *
 * @param string $package The package name to filter the schema. If empty, all packages will be included.
 * @return array An associative array containing the schema of the tables.
 *
 * @throws \Exception If the schema array format is not defined.
 */
private function getSchema($package = "")
{
    // Assuming create_specs_array() is a function that returns the schema array format
    $schema_array_format = create_specs_array();

    if (!isset($schema_array_format)) {
        throw new \Exception('Schema array format is not defined.');
    }

    $tables = [];

    if ($package == '') {
        // If package is not specified, include all packages
        $packages = array_keys($schema_array_format);

        foreach ($packages as $package) {
            foreach ($schema_array_format[$package]['tables'] as $table => $vars) {
                $tables[$table] = $vars;
            }
        }

    } else {
        // If package is specified, include only the tables from that package
        if (isset($schema_array_format[$package]['tables'])) {
            $tables = $schema_array_format[$package]['tables'];
        } else {
            throw new \Exception('Package not found.');
        }
    }

    // Sort the tables alphabetically
    ksort($tables);

    return $tables;
}

  /**
 * Retrieves the field data of a specific table from the schema.
 *
 * @param string $table The name of the table to retrieve the field data for.
 * @return array An array containing the field data of the specified table.
 *
 * @throws \Exception If the table does not exist in the schema or if the field data is not defined.
 */
private function fieldData($table)
{
    $field_data = [];
    $get_schema = $this->getSchema();

    // Check if the table exists in the schema and if the field data is defined
    if (isset($get_schema[$table]) && isset($get_schema[$table]['field_data'])) {
        $field_data = $get_schema[$table]['field_data'];
    } else {
        throw new \Exception('Table not found or field data is not defined.');
    }

    return $field_data;
}

/**
 * Retrieves the field data of a specific table from the schema.
 *
 * @param string $table_name The name of the table to retrieve the field data for. If empty, the controller name will be used.
 * @return array An array containing the field data of the specified table.
 *
 * @throws \Exception If the table does not exist in the schema or if the field data is not defined.
 */
private function tableFieldsMetadata($table_name = "")
{
    // If table_name is not provided, use the controller name
    $table = isEmpty($table_name) ? $this->controller : $table_name;

    // Call the fieldData method to retrieve the field data of the specified table
    $result = $this->fieldData($table);

    // Return the retrieved field data
    return $result;
}

/**
 * Retrieves the primary key field name of a given table.
 *
 * @param string $table_name The name of the table to retrieve the primary key field for.
 * @return string The name of the primary key field.
 *
 * @throws \Exception If the table does not exist or if the primary key field is not found.
 */
public function primaryKeyField(string $table_name): string
{
    // Retrieve the field data of the specified table
    $metadata = $this->tableFieldsMetadata($table_name);

    $primary_key_field = "";

    // Iterate through the field data to find the primary key field
    foreach ($metadata as $data) {
        if ($data['primary_key'] == 1) {
            // Assign the primary key field name to the variable
            $primary_key_field = $data['name'];
            break;
        }
    }

    // If the primary key field is not found, throw an exception
    if (isEmpty($primary_key_field)) {
        throw new \Exception('Primary key field not found.');
    }

    // Return the primary key field name
    return $primary_key_field;
}

  /**
 * Retrieves the context definition of a user based on their user ID.
 *
 * @param int $userId The ID of the user to retrieve the context definition for.
 * @return array An associative array containing the context definition data.
 *
 * @throws \Exception If the user ID is not provided or if the user does not exist.
 */
public function getUserContextDefinition(int $userId): array
{
    // Initialize the database builder for the 'context_definition' table
    $builder = $this->read_db->table('context_definition');

    // Select the required fields from the 'context_definition' table
    $builder->select(['context_definition_id', 'context_definition_name', 'context_definition_level', 'context_definition_is_active']);

    // Join the 'user' table with the 'context_definition' table on the foreign key 'fk_context_definition_id'
    $builder->join('user', 'user.fk_context_definition_id = context_definition.context_definition_id');

    // Apply the where clause to filter the results based on the provided user ID
    $builder->where('user_id', $userId);

    // Execute the query and retrieve the result
    $userContextDefinitionObj = $builder->get();

    // Initialize an empty array to store the user context definition data
    $userContextDefinition = [];

    // Check if any rows were returned by the query
    if ($userContextDefinitionObj->getNumRows() > 0) {
        // If rows were returned, assign the first row data to the $userContextDefinition array
        $userContextDefinition = $userContextDefinitionObj->getRowArray();
    } else {
        // If no rows were returned, throw an exception indicating that the user does not exist
        throw new \Exception('User does not exist.');
    }

    // Return the user context definition data
    return $userContextDefinition;
}

 /**
 * Retrieves an array of all table names from the schema.
 *
 * @return array An array containing the names of all tables.
 */
private function listTables()
{
    // Call the getSchema method to retrieve the schema array
    $get_schema = $this->getSchema();

    // Return the keys of the schema array, which represent the table names
    return array_keys($get_schema);
}

/**
 * Checks if a table exists in the schema.
 *
 * @param string $table_name The name of the table to check. If empty, the controller name will be used.
 * @return bool Returns true if the table exists, false otherwise.
 */
private function tableExists(string $table_name = ""): bool
{
    // If table_name is not provided, use the controller name
    $table = isEmpty($table_name) ? $this->controller : $table_name;

    // Initialize table_exists as false
    $table_exists = false;

    // Get a list of all tables from the schema
    $list_tables = $this->listTables();

    // Check if the table exists in the list of tables
    if (in_array($table, $list_tables)) {
        // If the table exists, set table_exists as true
        $table_exists = true;
    }

    // Return the result
    return $table_exists;
}

  /**
 * Loads a library for a specific table.
 *
 * @param string $table_name The name of the table for which to load the library.
 * @return mixed The instantiated library object for the specified table.
 * @throws \Exception If the library object could not be instantiated.
 */
private function loadLibrary(string $table_name)
{
    $modules = $this->config->modules; // Assuming $config is an instance of Config\App
    $table_library = null;
    $table_library_name = pascalize($table_name) . 'Library';
    // log_message('eeror', json_encode($table_library_name));
    // Loop through the modules to find the appropriate library
    foreach ($modules as $module) {
        // Check if the library class exists
        if (class_exists("App\\Libraries\\" . ucfirst($module) . "\\" . $table_library_name)) {
            // Instantiate the library class
            $table_library = new ("App\\Libraries\\" . ucfirst($module) . "\\" . $table_library_name)();
        }
    }

    // If the library object is still null, throw an exception
    if ($table_library == null) {
        // To be updated and allow automatic creation of feature library files
        throw new \Exception('Object could not be instantiated');
    }

    // Return the instantiated library object
    return $table_library;
}

  /**
 * Checks if a table has a dependant table.
 *
 * @param string $table_name The name of the table to check. If empty, the controller name will be used.
 * @return bool Returns true if the table has a dependant table, false otherwise.
 */
public function hasDependantTable($table_name = "")
{
    $has_dependant_table = false;
    $table_library = $this->loadLibrary($table_name);
    $table_exists = $this->tableExists($table_name . "_detail");

    // Check if the table library has a dependant_table property and it's not null,
    // or if the table with "_detail" suffix exists in the schema.
    if (
        (
            property_exists($table_library, 'dependant_table') &&
            $table_library->dependant_table != null
        ) ||
        $table_exists
    ) {
        $has_dependant_table = true;
    }

    return $has_dependant_table;
}

 /**
 * Retrieves the name of the dependant table for a given table.
 *
 * @param string $table_name The name of the table to check. If empty, the controller name will be used.
 * @return string The name of the dependant table. If no dependant table is found, an empty string is returned.
 */
public function dependantTable(string $table_name = "")
{
    // Instantiate the library for the given table
    $table_library = $this->loadLibrary($table_name);

    // Initialize the dependant_table variable
    $dependant_table = '';

    // Check if the table library has a dependant_table property and it's not null
    if (
        property_exists($table_library, 'dependant_table') &&
        $table_library->dependant_table != null
    ) {
        // If the dependant_table property is defined, assign its value to the dependant_table variable
        $dependant_table = $table_library->dependant_table;
    } elseif (
        // Check if a table with "_detail" suffix exists and if the detach_detail_table method is not defined or returns false
        $this->tableExists($table_name . "_detail") &&
        (
            !method_exists($table_library, 'detach_detail_table') ||
            !$table_library->detach_detail_table())
    ) {
        // If the legacy way of implementing dependancy table is used, assign the table suffixed with "_detail" to the dependant_table variable
        $dependant_table = $table_name . "_detail";
    }

    // Return the dependant_table variable
    return $dependant_table;
}

  /**
 * Adds mandatory fields to a given table if they are not already present.
 *
 * @param string $table The name of the table to add mandatory fields to.
 *
 * @return void
 */
public function mandatoryFields(string $table): void
{
    if ($table !== '') {
        $table = strtolower($table);

        $mandatory_fields = [];

        // Define mandatory fields based on the table name
        if ($table !== 'approval' && $table !== 'approval_flow') {
            $mandatory_fields = [
                $table . '_created_date',
                $table . '_created_by',
                $table . '_last_modified_by',
                $table . '_last_modified_date',
                'fk_approval_id',
                'fk_status_id'
            ];
        } elseif ($table === 'approval') {
            $mandatory_fields = [
                $table . '_created_date',
                $table . '_created_by',
                $table . '_last_modified_by',
                $table . '_last_modified_date',
                'fk_status_id'
            ];
        } elseif ($table === 'approval_flow') {
            $mandatory_fields = [
                $table . '_created_date',
                $table . '_created_by',
                $table . '_last_modified_by',
                $table . '_last_modified_date'
            ];
        }

        $fields_to_add = [];

        // Get all fields of the given table
        $table_fields = $this->getAllTableFields($table);

        // Check if mandatory fields are not already present in the table
        foreach ($mandatory_fields as $mandatory_field) {
            if (!in_array($mandatory_field, $table_fields)) {
                // Define the data type and constraint for each mandatory field
                if (substr($mandatory_field, 0, 3) === 'fk_' || substr($mandatory_field, -3) === '_by') {
                    $fields_to_add[$mandatory_field] = [
                        'type' => 'INT',
                        'constraint' => 100
                    ];
                } elseif (strpos($mandatory_field, '_date') !== false) {
                    $fields_to_add[$mandatory_field] = [
                        'type' => 'DATE'
                    ];
                } else {
                    $fields_to_add[$mandatory_field] = [
                        'type' => 'VARCHAR',
                        'constraint' => 100
                    ];
                }
            }
        }

        // Add mandatory fields to the table if they are not already present
        if (count($fields_to_add) > 0) {
            $forge = \Config\Database::forge('write');
            $forge->addColumn($table, $fields_to_add);
        }
    }
}

/**
 * Retrieves all fields of a given table from the schema.
 *
 * @param string $table_name The name of the table to retrieve fields for. If empty, the controller name will be used.
 * @return array An array containing the names of all fields in the specified table.
 */
private function getAllTableFields(string $table_name = ""): array
{
    // If table_name is not provided, use the controller name
    $table = isEmpty($table_name) ? strtolower($this->controller) : strtolower($table_name);

    // Call the listFields method to retrieve the fields of the specified table
    $fields = $this->listFields($table);

    // Return the retrieved fields
    return $fields;
}

/**
 * Retrieves the names of all fields in a given table from the schema.
 *
 * @param string $table The name of the table to retrieve fields for.
 * @return array An array containing the names of all fields in the specified table.
 */
private function listFields($table)
{
    $fields = [];
    $get_schema = $this->getSchema();

    // Check if the table exists in the schema and if the field data is defined
    if (isset($get_schema[$table]) && isset($get_schema[$table]['field_data'])) {
        // Extract the names of all fields in the specified table
        $fields = array_column($get_schema[$table]['field_data'], 'name');
    }

    // Return the retrieved fields
    return $fields;
}

  /**
 * Creates the necessary directory structure for resource uploads.
 *
 * The function retrieves the names of all approveable items from the database,
 * and creates a directory for each item under the 'uploads/attachments/' directory.
 * If the 'uploads/' or 'uploads/attachments/' directories do not exist, they are created.
 *
 * @return void
 */
function createResourceUploadDirectoryStructure()
{
    $builder = $this->read_db->table('approve_item');

    $builder->select(array('approve_item_name'));
    $approveable_items = $builder->get()->getResultArray();

    // Check if the 'uploads/' directory exists, and create it if not
    if (!file_exists('uploads/')) {
        mkdir('uploads/');
    }

    // Check if the 'uploads/attachments/' directory exists, and create it if not
    if (!file_exists('uploads/attachments/')) {
        mkdir('uploads/attachments/');
    }

    // Iterate through the approveable items and create a directory for each item
    foreach ($approveable_items as $approveable_item) {
        if (!file_exists('uploads/attachments/' . $approveable_item['approve_item_name'])) {
            mkdir('uploads/attachments/' . $approveable_item['approve_item_name']);
        }
    }
}

/**
 * Retrieves all table names from the schema.
 *
 * @return array An array containing the names of all tables.
 */
public function getAllTables()
{
    // Call the listTables method to retrieve the names of all tables
    $tables = $this->listTables();

    // Return the retrieved table names
    return $tables;
}

public function add(string $tableName = '', array $postArray = [])
{
    if(isEmpty($tableName)){
        $tableName = strtolower($this->controller);
    }

    if(empty($postArray)){
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
    $headerColumns[strtolower($this->controller) . '_name'] =  !isEmpty($this->request->getPost($this->controller . '_name')) ? $this->request->getPost($this->controller . '_name') : ucfirst($this->controller) . ' # ' . $headerRandom;

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


private function callbackTransactionValidateByComputation(String $table_name, array $insert_array)
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


}