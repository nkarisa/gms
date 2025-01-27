<?php

namespace App\Libraries\System;

use App\Libraries\Core\StatusLibrary;
use App\Traits\System;
use Config\GrantsConfig;
use BadMethodCallException;
use InvalidArgumentException;

class GrantsLibrary
{

  use System\OutputTrait;
  use System\CallbackTrait;
  use System\Extendable;
  use System\SchemaTrait;
  use System\CrudTrait;
  use System\DataTable;
  use System\SetupTrait;
  use System\ApprovalTrait;
  use System\FieldsTrait;
  use System\ManipulationTrait;
  use System\BuilderTrait;
  use System\LibraryInitTrait;


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
  protected $set_field_type = [];
  protected $detail_tables = [];
  public $lookup_tables_with_null_values = [];
  protected $model = null;
  protected $library = null;
  public $dbSchema;
  private $uri;

  public array $lookUpTablesForeignKeyMappings = [];

  function __construct()
  {

    $this->initBuilders();
    // $this->initLibraries();
    
    // Load grants config
    $this->config = config(GrantsConfig::class);
    // Load default helpers
    // helper(['grants','inflector']);
    // Load database
    $this->read_db = \Config\Database::connect('read');
    $this->write_db = \Config\Database::connect('write');

    // Set controller, action and ids
    $this->uri = service('uri');
    // Request 
    $this->request = service('request');
    $segments = $this->uri->getSegments();

    $this->controller = isset($segments[0]) ? $segments[0] : 'dashboard';
    $this->action = isset($segments[1]) && !$this->request->isAJAX() ? $segments[1] : 'list';
    $this->id = isset($segments[2]) && !$this->request->isAJAX() ? $segments[2] : 0;

    // if($this->request->isAJAX()){
    if ($this->controller == "ajax" || $this->controller == "ajaxRequest") {
      $this->controller = isset($segments[1]) ? $segments[1] : 'dashboard';
    }

    if ($this->action == "showList") {
      $this->action = 'list';
    }
    // }

    // Session 
    $this->session = service('session');

    // Response 
    $this->response = service('response'); // Services::response()

  }

  // private function callbackTransactionValidateDuplicatesColumns($table_name)
  // {

  //   $featureLibrary = $this->loadLibrary($table_name);

  //   $columns = array();

  //   if (method_exists($featureLibrary, 'transactionValidateDuplicatesColumns')) {
  //     $columns = $featureLibrary->transactionValidateDuplicatesColumns();
  //   }

  //   return $columns;
  // }

  // private function callbackMultiSelectField($table_name): string
  // {
  //   $library = $this->loadLibrary($table_name);

  //   $multi_select_field = '';

  //   if (
  //     method_exists($library, 'multiSelectField') &&
  //     strlen($library->multiSelectField()) > 0 &&
  //     $this->action !== 'edit'
  //   ) {

  //     $multi_select_field = $library->multiSelectField();
  //   }

  //   return $multi_select_field;
  // }


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


  private function callbackTransactionValidateByComputation(string $table_name, array $insert_array)
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


  final public function loadModel(string $table_name)
  {
    $modules = decode_setting("GrantsConfig", "modules"); // Assuming $config is an instance of Config\App
    $table_model = null;
    $table_model_name = pascalize($table_name) . 'Model';
    // Loop through the modules to find the appropriate library
    foreach ($modules as $module) {
      // Check if the library class exists
      if (class_exists("App\\Models\\" . ucfirst($module) . "\\" . $table_model_name)) {
        // Instantiate the library class
        $table_model = new ("App\\Models\\" . ucfirst($module) . "\\" . $table_model_name)();
      }
    }

    // If the library object is still null, throw an exception
    if ($table_model == null) {
      // To be updated and allow automatic creation of feature library files
      throw new \Exception('Object could not be instantiated');
    }

    // Return the instantiated library object
    return $table_model;
  }

  /**
   * Loads a library for a specific table.
   *
   * @param string $table_name The name of the table for which to load the library.
   * @return mixed The instantiated library object for the specified table.
   * @throws \Exception If the library object could not be instantiated.
   */
  final public function loadLibrary(string $table_name)
  {
    $modules = decode_setting("GrantsConfig", "modules"); // Assuming $config is an instance of Config\App
    $table_library = null;
    $table_library_name = pascalize($table_name) . 'Library';
    // Loop through the modules to find the appropriate library
    foreach ($modules as $module) {
      // Check if the library class exists
      if (class_exists("App\\Libraries\\" . ucfirst($module) . "\\" . $table_library_name)) {
        // Instantiate the library class
        $table_library = new ("App\\Libraries\\" . ucfirst($module) . "\\" . $table_library_name)();
        break;
      }
    }

    // If the library object is still null, throw an exception
    if ($table_library == null) {
      // To be updated and allow automatic creation of feature library files
      throw new \Exception("Object '$table_library_name' could not be instantiated");
    }

    // Return the instantiated library object
    return $table_library;
  }


  /**
   * This method is used to call a specific method from a library based on the given namespace.
   *
   * @param string $namespace The namespace of the method to be called. It should be in the format 'group.feature.method'.
   * @param array $args An optional array of arguments to be passed to the method.
   *
   * @return mixed The result of the called method.
   *
   * @throws InvalidArgumentException If the first namespace segment is not 'core', 'grants', or 'system'.
   * @throws \Exception If the class corresponding to the given namespace does not exist.
   * @throws BadMethodCallException If the method corresponding to the given namespace does not exist in the class.
   */
  public static function call(string $namespace, array $args = [])
  {

    $modules = config(GrantsConfig::class)->modules;
    // Explode the namespace into segments
    $namespace_items = exploding('.', $namespace, ['group', 'feature', 'method']);
    // If only method is given, assume it's a method in the grants library
    if (sizeof($namespace_items) == 1) {
      $namespace_items['feature'] = 'grants';
    }

    // Extract the namespace segments
    extract($namespace_items);

    // Construct the class name and full class path
    $className = pascalize($feature) . "Library";

    $class_exists = false;
    foreach ($modules as $module) {
      // Check if the library class exists
      $class = "App\\Libraries\\" . ucfirst($module) . "\\" . $className;
      if (class_exists($class)) {
        $class_exists = true;
        // Instantiate the library class
        $newObj = new $class();

        if (method_exists($newObj, $method)) {
          if (in_array($method, ['listOutput', 'viewOutput', 'editOutput', 'singleFormAddOutput', 'multiFormAddOutput'])) {
            return $newObj->$method($module, ...$args);
          }
          return $newObj->$method(...$args);
        } else {
          throw new BadMethodCallException("Method '" . $method . "' not found in class '" . $class . "'");
        }
      }
    }

    if (!$class_exists) {
      throw new \Exception("Class '" . $feature . "' not found in all library namespaces");
      // self::createMissingResources($feature);
    }
  }



  /**
   * default_unset_columns
   * 
   * This method unset selected columns/fields from an array of visible columns 
   * @param array $visible_columns
   * @param array $field_to_unset
   * @return array 
   */
  function defaultUnsetColumns(array &$visible_columns, array $fields_to_unset): array
  {

    foreach ($fields_to_unset as $field) {
      if (in_array($field, $visible_columns)) {
        unset($visible_columns[array_search($field, $visible_columns)]);
      }
    }

    $visible_columns = array_values($visible_columns);

    return $visible_columns;
  }


  public function lookupTablesFields(\App\Interfaces\LibraryInterface $library, array $deriveLookupTables): array
  {
    $lookup_tables_fields = array();
   
    // $deriveLookupTables = $this->deriveLookupTables($tableName);
    $featureLibraryLookUpTables = $library->lookupTables();

    if (
        (is_array($featureLibraryLookUpTables) && count($featureLibraryLookUpTables) > 0) ||
        (is_array($deriveLookupTables) && count($deriveLookupTables) > 0)
      ) {
        
        $lookupTables = array_unique(array_merge($featureLibraryLookUpTables, $deriveLookupTables));

        foreach ($lookupTables as $lookup_table) {
          $lookup_tables_fields = array_merge($lookup_tables_fields, $this->getAllTableFields($lookup_table));
        }
    }

    return $lookup_tables_fields;
  }

  protected function lookupJoins(\CodeIgniter\Database\BaseBuilder $builder, $table = ""){
    
    $lookup_tables = $this->lookupTables();
    $table =  $table == "" ? $this->controller : $table;

    $featureLibrary = $this->loadLibrary($table );
    $foreignKeyMappings = $featureLibrary->lookUpTablesForeignKeyMappings;

    if (is_array($lookup_tables) && count($lookup_tables) > 0) {
      foreach ($lookup_tables as $lookup_table) {
        // Check if lookup table exists
        if (!$this->read_db->tableExists($lookup_table)) {
          $message = "The table " . $lookup_table . " doesn't exist in the database. Check the lookup_tables function in the " . $table . "_model";
          throw new \CodeIgniter\Exceptions\PageNotFoundException($message);
        }

        $lookup_table_id = $lookup_table . '_id';
        $foreignKeyField = 'fk_' . $lookup_table_id;

        if(array_key_exists($lookup_table,$foreignKeyMappings)){
          $foreignKeyField = $foreignKeyMappings[$lookup_table];
        }
        $lookup_tables_with_null_values = [];
        if(!empty(property_exists($featureLibrary, 'lookup_tables_with_null_values'))){
          $lookup_tables_with_null_values = $featureLibrary->lookup_tables_with_null_values;
        }
        $joinType = in_array($lookup_table, $lookup_tables_with_null_values) ? 'LEFT': '';
        $builder->join($lookup_table, $lookup_table . '.' . $lookup_table_id . '=' . $table . '.' . $foreignKeyField, $joinType);
      }
    }
  }

  function lookupTables(): array
  {
    $lookUpTables = $this->checkLookupTables();
    return $lookUpTables;
  }

  function checkLookupTables($table_name = "")
  {

    $lookup_tables = array();

    if ($table_name != '') {
      $this->library = $this->loadLibrary($table_name);
      if (method_exists($this->library, 'lookupTables') && is_array($this->library->lookupTables())) {
        $lookup_tables = $this->deriveLookupTables($table_name);
        if ($this->action !== 'singleFormAdd') {
          // Check if status and approval lookup tables doesn't exist and add them
          $this->addMandatoryLookupTables($lookup_tables);
          // Hide status and approval columns if the active controller/table is not approveable
          $approveItemLibrary = new \App\Libraries\Core\ApproveItemLibrary();
          if (!$approveItemLibrary->approveableItem($table_name)) {
            $this->removeMandatoryLookupTables($lookup_tables);
          }
        } else {
          $lookup_tables = $this->deriveLookupTables($table_name);
        }
      }
    } else {
      // This part of a code is meant to offer an alternative to lookup_tables 
      $lookup_tables = $this->deriveLookupTables();
    }

    return $lookup_tables;
  }


  public function updateQueryResultForFieldsChangedToSelectType(string $table, array $query_result): array
  {
    // Check if there is a change of field type set and update the results
    $this->setChangeFieldType($table);

    if (count($this->set_field_type) > 0) {

      //Get changed columns 
      $changed_fields = array_keys($this->set_field_type);

      if (!array_key_exists(0, $query_result)) {
        // Used for single record pages e.g Master section 
        foreach ($changed_fields as $changed_field) {
          if (array_key_exists($changed_field, $query_result) && in_array('select', $this->set_field_type[$changed_field])) {
            $query_result[$changed_field] = isset($this->set_field_type[$changed_field]['options'][$query_result[$changed_field]]) ? $this->set_field_type[$changed_field]['options'][$query_result[$changed_field]] : $query_result[$changed_field];
          }
        }
      } else {
        // Used for multi row data e.g. list and details sections
        foreach ($query_result as $index => $row) {

          foreach ($changed_fields as $changed_field) {
            if (array_key_exists($changed_field, $row) && in_array('select', $this->set_field_type[$changed_field])) {
              // The isset check has been used to solve a problem where a field type of select is changed to the same select in order to alter the number of select options. 
              // This workaround is crucial on the detail list of view action pages, Most notably when using the group_country_user lib changeFieldType
              $query_result[$index][$changed_field] = isset($this->set_field_type[$changed_field]['options'][$row[$changed_field]]) ? $this->set_field_type[$changed_field]['options'][$row[$changed_field]] : $row[$changed_field];
            }
          }
        }
      }
    }

    return $query_result;
  }

  function checkDetailTables(string $table_name = ""): array
  {
    $featureLibrary = $this->loadLibrary($table_name);
    $uri = service('uri');

    if ($this->controller == 'approval' && $this->action == 'view') {
      // This is specific to approval view, only to list the detail listing of the selected approveable items
      // This prevents the approval from listing the details tables instead of a specific approveable item
      $id = $uri->getSegment(3, 0);
      // This line needs to be moved to a model
      $builder = $this->read_db->table('approval');
      $builder->join('approve_item', 'approve_item.approve_item_id=approval.fk_approve_item_id');
      $detail_table = $builder->getWhere(['approval_id' => hash_id($id, 'decode')])->getRow()->approve_item_name;
      $this->detail_tables = [$detail_table];
    } elseif ($this->dependantTable($table_name) !== "") {
      // If dependant_table exists, you can't have more than one detail table. This piece nullifies the use
      // of the detail_tables feature model if set
      $this->detail_tables[] = $this->dependantTable($table_name);
    } elseif (
      method_exists($featureLibrary, 'detailTables') &&
      is_array($featureLibrary->detailTables()) && !empty($featureLibrary->detailTables())
    ) {
      $this->detail_tables = $featureLibrary->detailTables();
    }

    return $this->detail_tables;
  }

  public function checkIfTableHasDetailListing(string $table_name = ""): bool
  {

    $table = $table_name == "" ? $this->controller : $table_name;
    $all_detail_tables = $this->checkDetailTables($table);
    $dependantTable = $this->dependantTable($table);
    $has_detail_table = false;

    if (
      is_array($all_detail_tables)
      && !empty(is_array($all_detail_tables))
      && $dependantTable != ""
    ) {
      $has_detail_table = true;
    }

    return $has_detail_table;
  }


  function checkIfTableHasDetailTable(string $table_name = ""): bool
  {
    $table = isEmpty($table_name) ? $this->controller : $table_name;

    $all_detail_tables = $this->checkDetailTables($table);

    $has_detail_table = false;

    if (is_array($all_detail_tables) && count($all_detail_tables) > 0) {
      $has_detail_table = true;
    }

    return $has_detail_table;
  }

  public function checkIfTableIsMultiRow(string $table_name = "")
  {

    $table = isEmpty($table_name) ? $this->controller : $table_name;
    $featureLibrary = $this->loadLibrary($table);

    if (property_exists($featureLibrary, 'is_multi_row')) {
      return $featureLibrary->is_multi_row;
    } else {
      return false;
    }
  }

  private function tablesWithAccountSystemRelationship()
  {

    $tables = $this->getAllTables();

    $tables_with_account_system_relationship = [];

    foreach ($tables as $table) {
      $table_fields = $this->getAllTableFields($table);

      foreach ($table_fields as $table_field) {
        if ($table_field == 'fk_account_system_id') {
          $tables_with_account_system_relationship[] = $table;
        }
      }
    }

    return $tables_with_account_system_relationship;
  }

  public function listTableWhere(\CodeIgniter\Database\BaseBuilder $queryBuilder): void
  {
    $tables_with_account_system_relationship = $this->tablesWithAccountSystemRelationship();
    $lookup_tables = $this->lookupTables();
    $account_system_table = '';

    if (!$this->session->system_admin && in_array($this->controller, $tables_with_account_system_relationship)) {
      $queryBuilder->where(array($this->controller . '.fk_account_system_id' => $this->session->user_account_system_id));
    } elseif (!empty($lookup_tables)) {
      foreach ($lookup_tables as $lookup_table) {
        if (in_array($lookup_table, $tables_with_account_system_relationship)) {
          $account_system_table = $lookup_table;
          break;
        }
      }

      if (!$this->session->system_admin && $account_system_table !== '') {
        $queryBuilder->where(array($account_system_table . '.fk_account_system_id' => $this->session->user_account_system_id));
      }
    }
  }


  function checkIfTableHasAccountSystem($table)
  {

    $table_has_account_system = false;

    $table_fields = $this->getAllTableFields($table);

    if (in_array('fk_account_system_id', $table_fields)) {
      $table_has_account_system = true;
    }
    //echo 1;exit;
    return $table_has_account_system;
  }

  function checkIfTableHasOfficeRelationship($table)
  {

    $table_has_office_relationship = false;

    $table_fields = $this->getAllTableFields($table);

    if (in_array('fk_office_id', $table_fields)) {
      $table_has_office_relationship = true;
    }

    return $table_has_office_relationship;
  }

  function joinTablesWithOffice($builder, $table)
  {

    if ($this->checkIfTableHasOfficeRelationship($table)) {
      $builder->whereIn("$table.fk_office_id", array_column($this->session->hierarchy_offices, 'office_id'));
    }
  }

  function joinTablesWithAccountSystem($builder, $table)
  {

    $array_intersect = array_intersect($this->checkLookupTables($table), $this->tablesWithAccountSystemRelationship());

    $array_intersect = array_values($array_intersect);

    if ($this->checkIfTableHasAccountSystem($table)) {
      $builder->join('account_system', 'account_system.account_system_id=' . $table . '.fk_account_system_id');
      $builder->where(array('account_system_code' => $this->session->user_account_system_code));
    } elseif (count($array_intersect) > 0) {

      foreach ($array_intersect as $lookup_table) {
        $lookup_table_id = $lookup_table . '_id';
        $builder->join($lookup_table, $lookup_table . '.' . $lookup_table_id . '=' . $table . '.fk_' . $lookup_table_id);
      }


      $builder->join('account_system', 'account_system.account_system_id=' . $array_intersect[0] . '.fk_account_system_id');
      $builder->where(array('account_system_code' => $this->session->user_account_system_code));
    }
  }


  function unsetLookupTablesIds(&$keys, $table_name = "")
  {

    $table_name = $table_name == "" ? $this->controller : $table_name;
    $library = $this->loadLibrary($table_name);

    $lookup_tables = $library->lookupTables();

    // Unset the lookup id keys
    $unset_fields = [];
    if (is_array($lookup_tables) && count($lookup_tables) > 0) {
      foreach ($lookup_tables as $table) {
        if ($field = $this->primaryKeyField($table)) {
          array_push($unset_fields, $field);
        }
      }
    }

    $this->defaultUnsetColumns($keys, $unset_fields);
  }

  function checkShowAddButton(string $table): bool
  {
    $library = $this->loadLibrary($table);

    $show_add_button = true;

    if (
      method_exists($library, 'showAddButton')
      && $library->showAddButton() != null
    ) {
      $show_add_button = $library->showAddButton();
    }


    return $show_add_button;
  }




  function runMasterViewQuery($table, $selectedColumns, $lookupTables)
  {

    $this->library = $this->loadLibrary($table);
    $builder = $this->read_db->table($table); // Start query builder for the table
    $builder->select($selectedColumns);

    // Check if lookup tables exist and join them
    if (is_array($lookupTables) && count($lookupTables) > 0) {
      foreach ($lookupTables as $lookupTable) {
        // Create table joins
        $lookupTableId = $this->primaryKeyField($lookupTable);
        $builder->join($lookupTable, "$lookupTable.$lookupTableId = $table.fk_$lookupTableId");
      }
    }

    $data = [];

    // Apply where clause if it exists in the library
    if (
      method_exists($this->library, 'masterViewTableWhere') &&
      is_array($this->library->masterViewTableWhere()) &&
      count($this->library->masterViewTableWhere()) > 0
    ) {
      $builder->where($this->library->masterViewTableWhere());
    }

    // Get the data row based on the primary key and hashed ID
    $primaryKeyField = $this->primaryKeyField($table);
    $decodedId = hash_id($this->uri->getSegment(3), 'decode');
    $data = (array) $builder->getWhere([$primaryKeyField => $decodedId])->getRow();

    // Get the name of the record creator
    $createdByField = $this->historyTrackingField($table, 'created_by');
    if (isset($data[$createdByField]) && $data[$createdByField] >= 1) {
      $createdBy = $this->read_db->table('user')
        ->select('CONCAT(user_firstname, " ", user_lastname) as user_name')
        ->where('user_id', $data[$createdByField])
        ->get()
        ->getRow()->user_name;
    } else {
      $createdBy = get_phrase('creator_user_not_set');
    }
    $data['created_by'] = $createdBy;

    // Get the name of the last record modifier
    $lastModifiedByField = $this->historyTrackingField($table, 'last_modified_by');
    if (isset($data[$lastModifiedByField]) && $data[$lastModifiedByField] >= 1) {
      $lastModifiedBy = $this->read_db->table('user')
        ->select('CONCAT(user_firstname, " ", user_lastname) as user_name')
        ->where('user_id', $data[$lastModifiedByField])
        ->get()
        ->getRow()->user_name;
    } else {
      $lastModifiedBy = get_phrase('modifier_user_not_set');
    }
    $data['last_modified_by'] = $lastModifiedBy;

    return $data;
  }

  function checkLookupValues($table)
  {

    $lookup_values = [];
    $this->library = $this->loadLibrary($this->controller);

    if (
      (
        method_exists($this->library, 'lookupValues')
        && is_array($this->library->lookupValues())
        && array_key_exists($table, $this->library->lookupValues())
      )
    ) {
      $result = $this->library->lookupValues()[$table];
      $ids_array = array_column($result, $this->primaryKeyField($table));
      $value_array = array_column($result, $this->nameField($table));
      $lookup_values = array_combine($ids_array, $value_array);
    } else {
      $lookup_values = $this->getLookupValues($table);
    }

    return $lookup_values;
  }

  function getLookupValues($table)
  {
    $table = strtolower($table);
    $builder = $this->read_db->table($table);

    if (
      isset($this->lookupValuesWhere($builder)[$table]) &&
      is_array($this->lookupValuesWhere($builder)[$table]) &&
      count($this->lookupValuesWhere($builder)[$table]) > 0
    ) {
      //$this->create_table_join_statement(strtolower($this->controller),$this->grants->lookup_tables($this->controller));
      $builder->where($this->lookupValuesWhere($builder)[$table]);
    }

    $result = $builder->get()->getResultArray();

    $ids_array = array_column($result, $this->primaryKeyField($table));
    $value_array = array_column($result, $this->nameField($table));

    return array_combine($ids_array, $value_array);
  }



  function checkMultiSelectField($table_name = "")
  {

    $this->library = $this->loadLibrary($this->controller);

    if ($table_name != "") {
      $this->library = $this->loadLibrary($table_name);
    }

    $multi_select_field = '';

    if (
      method_exists($this->library, 'multiSelectField') &&
      strlen($this->library->multiSelectField()) > 0 &&
      $this->action !== 'edit'
    ) {

      $multi_select_field = $this->library->multiSelectField();
    }

    return $multi_select_field;
  }

  function deriveLookupTables($table_name = "")
  {

    if ($table_name == ''){
      $table_name = $this->controller;
    }
    
    $fields = $this->getAllTableFields($table_name);
    
    $foreign_tables_array_padded_with_false = array_map(function ($elem) {
      return substr($elem, 0, 3) == 'fk_' ? substr($elem, 3, -3) : false;
    }, $fields);

    // Prevent listing false values and status or approval tables for lookup. 
    // Add status_name and approval_name to the correct visible_columns method in models to see these fields in a page
    $foreign_tables_array = array_filter($foreign_tables_array_padded_with_false, function ($elem) {
      return $elem ? $elem : false;
    });

    // Just remove approval table due to its performance degradation history. This table will be removed in the future
    if (in_array('approval', $foreign_tables_array)) {
      unset($foreign_tables_array[array_search('approval', $foreign_tables_array)]);
    }

    // Add look up tables from the schema
    $tableLookUp = $this->tableLookUp($table_name);
    
    $foreign_tables_array = array_unique(array_merge($foreign_tables_array, $tableLookUp));

    // Hide status and approval columns if the active controller/table is not approveable
    $approveItemLibrary = new \App\Libraries\Core\ApproveItemLibrary();
    if (!$approveItemLibrary->approveableItem($table_name)) {
      if (in_array('status', $foreign_tables_array)) {
        unset($foreign_tables_array[array_search('status', $foreign_tables_array)]);
      }
    }

    return $foreign_tables_array;
  }



  function listTableWhereByAccountSystem($builder, $tableName)
  {
    $fields =
      $tables_with_account_system_relationship = $this->tablesWithAccountSystemRelationship();
    $lookup_tables = $this->lookupTables();

    $account_system_table = '';

    if (!empty($lookup_tables)) {
      foreach ($lookup_tables as $lookup_table) {
        if (in_array($lookup_table, $tables_with_account_system_relationship)) {
          $account_system_table = $lookup_table;
          break;
        }
      }

      if (!$this->session->system_admin && $account_system_table !== '') {
        $builder->where(array($account_system_table . '.fk_account_system_id' => $this->session->user_account_system_id));
      }
    }
  }


  /**
   * single_form_add_visible_columns
   * 
   * Return an array of the selected fields/ columns of the single_form_add action pages
   * It is a model wrapper method that is used in the grants_model single_form_add_visible_columns
   * and not directly in this class
   * @return array
   */
  function checkSingleFormAddVisibleColumns()
  {

    $this->library = $this->loadLibrary($this->controller);
    $single_form_add_visible_columns = [];

    if (
      method_exists($this->library, 'singleFormAddVisibleColumns') &&
      is_array($this->library->singleFormAddVisibleColumns()) &&
      !empty($this->library->singleFormAddVisibleColumns())
    ) {
      $single_form_add_visible_columns = $this->library->singleFormAddVisibleColumns();
    } else {
      // Check if the table has list_table_visible_columns not empty
      $lookup_tables = $this->checkLookupTables();
      $get_all_table_fields = $this->getAllTableFields();

      foreach ($get_all_table_fields as $get_all_table_field) {
        //Unset foreign keys columns, created_by and last_modified_by columns
        if (
          substr($get_all_table_field, 0, 3) == 'fk_' ||
          strpos($get_all_table_field, '_deleted_at') == true
        ) {
          unset($get_all_table_fields[array_search($get_all_table_field, $get_all_table_fields)]);
        }
      }

      $visible_columns = $get_all_table_fields;

      if (is_array($lookup_tables) && count($lookup_tables) > 0) {
        foreach ($lookup_tables as $lookup_table) {

          $lookup_table_columns = $this->getAllTableFields($lookup_table);

          foreach ($lookup_table_columns as $lookup_table_column) {
            // Only include the name field of the look up table in the select columns
            if (strpos($lookup_table_column, '_name') == true) {
              array_push($visible_columns, $lookup_table_column);
            }
          }
        }
      }

      $single_form_add_visible_columns = $visible_columns;
    }
    return $single_form_add_visible_columns;
  }



  public function checkEditVisibleColumns($table)
  {
    $library = $this->loadLibrary($table);
    $editVisibleColumns = [];
    $visibleColumns = [];

    // Get the list of visible columns and lookup tables
    $editVisibleColumns = $library->editVisibleColumns();
    $lookupTables = $this->checklookupTables($table);

    $getAllTableFields = $this->getAllTableFields();

    // Filter out foreign key and certain columns
    foreach ($getAllTableFields as $key => $getAllTableField) {
      if (
        substr($getAllTableField, 0, 3) === 'fk_' ||
        strpos($getAllTableField, '_deleted_at') !== false
      ) {
        unset($getAllTableFields[$key]);
      }
    }

    $visibleColumns = $getAllTableFields;

    if (is_array($editVisibleColumns) && count($editVisibleColumns) > 0) {
      $columns = [];
      foreach ($editVisibleColumns as $column) {
        if (strpos($column, '_name') !== false && $column !== strtolower($table) . '_name') {
          $columns[] = substr($column, 0, -5) . '_id';
        } else {
          $columns[] = $column;
        }
      }
      $visibleColumns = $columns;
    } else {
      if (is_array($lookupTables) && count($lookupTables) > 0) {
        foreach ($lookupTables as $lookupTable) {
          $lookupTableColumns = $this->getAllTableFields($lookupTable);

          foreach ($lookupTableColumns as $lookupTableColumn) {
            if (strpos($lookupTableColumn, '_name') !== false) {
              $visibleColumns[] = substr($lookupTableColumn, 0, -5) . '_id';
            }
          }
        }
      }
    }

    // Add joins for lookup tables
    $builder = $this->read_db->table($table);
    if (is_array($lookupTables) && count($lookupTables) > 0) {
      foreach ($lookupTables as $lookupTable) {
        $lookupTableId = $lookupTable . '_id';
        $builder->join(
          $lookupTable,
          "$lookupTable.$lookupTableId = $table.fk_$lookupTableId"
        );
      }
    }

    // Select the visible columns and return the row
    $builder->select($visibleColumns);
    $builder->where([$table . '_id' => hash_id($this->id, 'decode')]);
    $obj = $builder->get();

    $result = [];

    if ($obj->getNumRows() > 0) {
      $result = $obj->getRow();
    }

    return $result;
  }

  function getAccountSystemRoles($user_account_system_id)
  {
    $builder = $this->read_db->table('role');
    $builder->select('role_id, role_name');

    if (!$this->session->system_admin) {
      $builder->where('fk_account_system_id', $user_account_system_id);
    }

    $roles = $builder->get()->getResultArray();

    return $roles;
  }


  function featureListTableVisibleColumns($parentTable = null)
  {
    $list_table_visible_columns = [];
    $list_table_visible_columns_method = 'listTableVisibleColumns';

    if ($parentTable != null) {
      $list_table_visible_columns_method = 'detailListTableVisibleColumns';
    }

    $currentLibrary = $this->loadLibrary($this->controller);
    $approveItemLibrary = $this->loadLibrary('approve_item');
    
    if (
      method_exists($currentLibrary, $list_table_visible_columns_method) &&
      is_array($currentLibrary->$list_table_visible_columns_method())
    ) {
      $list_table_visible_columns = $currentLibrary->$list_table_visible_columns_method();

      if (!$approveItemLibrary->approveableItem(strtolower($this->controller))) {
        $columns = ['status_name', 'approval_name'];

        foreach ($columns as $column) {
          if (in_array($column, $list_table_visible_columns)) {
            $column_name_key = array_search($column, $list_table_visible_columns);
            unset($list_table_visible_columns[$column_name_key]);
          }
        }
      }

      //Add the table id columns if does not exist in $columns
      if (
        is_array($list_table_visible_columns) &&
        !in_array(
          $this->primaryKeyField($this->controller),
          $list_table_visible_columns
        )
      ) {

        array_unshift(
          $list_table_visible_columns,
          $this->primaryKeyField(strtolower($this->controller))
        );

        // Throw error when a column doesn't exists to avoid Datatable server side loading error

        //Add the lookup table name to the all fields array
        $all_fields = $this->getAllTableFields($this->controller);

        $deriveLookupTables = $this->deriveLookupTables($this->controller);
        $all_lookup_fields = $this->lookupTablesFields($currentLibrary, $deriveLookupTables);
        $all_fields = array_merge($all_fields, $all_lookup_fields);
        $lookup_tables = $this->checkLookupTables($this->controller);

        foreach ($list_table_visible_columns as $_column) {
          if (!in_array($_column, $all_fields) && $_column !== "") {
            $message = "The column " . $_column . " does not exist in the table " . $this->controller . " or its lookup tables " . implode(',', $lookup_tables) . "</br>";
            $message .= "Check the 'list_table_visible_columns' or 'lookup_tables' functions of the " . $this->controller . "_model for the source";
            // show_error($message, 500, 'An Error As Encountered');
            throw new \Exception($message);
          }
        }
      }
    }
    return $list_table_visible_columns;
  }

  public function getListColumns(string $parentTable = null)
  {
    $selectedColumns = $this->toggleListSelectColumns($parentTable);
    $library = $this->loadLibrary($this->controller);

    if (
      method_exists($library, 'additionalListColumns') &&
      is_array($columns = $library->additionalListColumns()) &&
      count($columns) > 0
    ) {

      if (!empty($columns)) {
        foreach ($columns as $newColumn => $positionAfter) {
          if ($positionAfter != null) {
            $refIndex = array_search($positionAfter, $selectedColumns);
            array_splice($selectedColumns, $refIndex + 1, 0, $newColumn);
          } else {
            array_push($selectedColumns, $newColumn);
          }
        }
      }
    }

    return $selectedColumns;
  }

  public function toggleListSelectColumns($parentTable = null): array
  {
    // Check if the table has list_table_visible_columns not empty
    $list_table_visible_columns = $this->featureListTableVisibleColumns($parentTable);
    $lookup_tables = $this->lookupTables();
    $get_all_table_fields = $this->getAllTableFields();

    foreach ($get_all_table_fields as $get_all_table_field) {
      //Unset foreign keys columns, created_by and last_modified_by columns
      if (
        substr($get_all_table_field, 0, 3) == 'fk_' ||
        $this->isHistoryTrackingField($this->controller, $get_all_table_field, 'created_by') ||
        $this->isHistoryTrackingField($this->controller, $get_all_table_field, 'last_modified_by') ||
        $this->isHistoryTrackingField($this->controller, $get_all_table_field, 'deleted_at')
      ) {

        unset($get_all_table_fields[array_search($get_all_table_field, $get_all_table_fields)]);
      }
    }

    $visible_columns = $get_all_table_fields;

    if (is_array($list_table_visible_columns) && count($list_table_visible_columns) > 1) {
      $visible_columns = $list_table_visible_columns;
    } else {
      if (is_array($lookup_tables) && count($lookup_tables) > 0) {
        foreach ($lookup_tables as $lookup_table) {
          $lookup_table_columns = $this->getAllTableFields($lookup_table);
          foreach ($lookup_table_columns as $lookup_table_column) {
            // Only include the name field of the look up table in the select columns
            if ($this->isNameField($lookup_table, $lookup_table_column)) {
              array_push($visible_columns, $lookup_table_column);
            }
          }
        }
      }
    }

    return $visible_columns;
  }


  function checkDataTableCondition(\CodeIgniter\Database\BaseBuilder $builder, $customFields)
  {
    $library = $this->loadLibrary($this->controller);
    if (method_exists($library, 'dataTableCondition')) {
      $library->dataTableCondition($builder, $customFields);
    }
  }


  public function notExistsSubQuery($builder, string $lookupTable, string $associationTable, string $stringCondition = '')
  {

    $subQuery = 'SELECT * FROM ' . $associationTable .
      ' WHERE ' . $associationTable . '.fk_' . $lookupTable . '_id = ' .
      $lookupTable . '.' . $lookupTable . '_id ' . $stringCondition;

    $builder->where('NOT EXISTS (' . $subQuery . ')', null, false);
  }

  function accountSystemDeclineStates($approve_item, $account_system_id)
  {

    // Query Builder
    $builder = $this->read_db->table('status');
    $builder->join('approval_flow', 'approval_flow.approval_flow_id = status.fk_approval_flow_id');
    $builder->join('approve_item', 'approve_item.approve_item_id = approval_flow.fk_approve_item_id');
    $builder->where('approve_item.approve_item_name', $approve_item);
    $builder->where('approval_flow.fk_account_system_id', $account_system_id);
    $builder->where('status_approval_direction', -1);

    // Execute the query
    $status_obj = $builder->get();

    $decline_states = [];
    if ($status_obj->getNumRows() > 0) {
      foreach ($status_obj->getResult() as $row) {
        $decline_states[] = $row->status_id;
      }
    }

    return $decline_states;
  }

  function itemHasDeclinedState($item_id, $table){

    $item_has_declined_state = false;

    if($item_id != null){

        // $this->read_db->where(array($table.'_id'=>$item_id));
        // $this->read_db->join($table,$table.'.fk_status_id=status.status_id');
        // $status_approval_direction = $this->read_db->get('status')->row()->status_approval_direction;
        // $item_has_declined_state = $status_approval_direction == -1 ? true : false;

        $query = $this->read_db->table('status')
            ->where(array($table.'_id'=>$item_id))
            ->join($table,$table.'.fk_status_id=status.status_id');

        $status_approval_direction = $query->get()->getRow()->status_approval_direction;
        $item_has_declined_state = $status_approval_direction == -1 ? true : false;
    }

    return $item_has_declined_state;
}

}
