<?php

namespace App\Libraries\System;

use Config\GrantsConfig;
use BadMethodCallException;
use InvalidArgumentException;

class GrantsLibrary
{

  use \App\Traits\System\OutputTrait;
  use \App\Traits\System\CallbackTrait;
  use \App\Traits\System\VisibilityTrait;
  use \App\Traits\System\SchemaTrait;

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

  function __construct()
  {
    // Load grants config
    $this->config = config(GrantsConfig::class);
    // Load default helpers
    // helper(['grants','inflector']);
    // Load database
    $this->read_db = \Config\Database::connect('read');
    $this->write_db = \Config\Database::connect('write');

    // Set controller, action and ids
    $uri = service('uri');
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

  /**
 * Loads a library for a specific table.
 *
 * @param string $table_name The name of the table for which to load the library.
 * @return mixed The instantiated library object for the specified table.
 * @throws \Exception If the library object could not be instantiated.
 */
final public function loadLibrary(string $table_name)
{
    $modules = $this->config->modules; // Assuming $config is an instance of Config\App
    $table_library = null;
    $table_library_name = pascalize($table_name) . 'Library';
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
            if (class_exists("App\\Libraries\\" . ucfirst($module) . "\\" . $className)) {
                $class_exists = true;
                $class = "App\\Libraries\\" . ucfirst($module) . "\\" . $className;
                // Instantiate the library class
                $newObj = new $class();

                if(method_exists($newObj, $method)){
                    return $newObj->$method(...$args);
                }else{
                    throw new BadMethodCallException("Method '" . $method . "' not found in class '" . $class . "'");
                }
            }
        }

        if(!$class_exists){
            throw new \Exception("Class '" . $feature . "' not found in all library namespaces");
        }
    }

    function addMandatoryLookupTables(
        &$existing_lookup_tables,
        $mandatory_lookup_tables = ['status']
      ) {
        //$mandatory_lookup_tables = ['status', 'approval']
        foreach ($mandatory_lookup_tables as $mandatory_lookup_table) {
          if (!in_array($mandatory_lookup_table, $existing_lookup_tables)) {
            array_push($existing_lookup_tables, $mandatory_lookup_table);
          }
        }
      }
    
      function removeMandatoryLookupTables(
        &$existing_lookup_tables,
        $mandatory_lookup_tables = ['status']
      ) {
        //$mandatory_lookup_tables = ['status', 'approval']
        foreach ($mandatory_lookup_tables as $mandatory_lookup_table) {
          if (in_array($mandatory_lookup_table, $existing_lookup_tables)) {
            unset($existing_lookup_tables[array_search($mandatory_lookup_table, $existing_lookup_tables)]);
          }
        }
      }

public function lookupTablesFields(String $table): array
  {
    $lookup_tables_fields = array();

    if (is_array($this->loadLibrary($table)->lookupTables()) && count($this->loadLibrary($table)->lookupTables()) > 0) {
      foreach ($this->loadLibrary($table)->lookupTables() as $lookup_table) {
        $lookup_tables_fields = array_merge($lookup_tables_fields, $this->getAllTableFields($lookup_table));
      }
    }

    return $lookup_tables_fields;
  }

    function callbackLookupTables(string $table_name): array
    {
  
      $featureLibrary = $this->loadLibrary($table_name);
      $approveItemLibrary = $this->loadLibrary('approve_item');
  
      $lookup_tables =  array();
  
      if (is_array($featureLibrary->lookupTables($table_name))) 
      {  
        if($this->action !== 'single_form_add'){
          // Check if status and approval lookup tables doesn't exist and add them
          $lookup_tables = $featureLibrary->lookupTables($table_name);
          $this->addMandatoryLookupTables($lookup_tables);
  
          // Hide status and approval columns if the active controller/table is not approveable
          if (!$approveItemLibrary->approveableItem($table_name)) {
            $this->removeMandatoryLookupTables($lookup_tables);
          }
        }else{
          $lookup_tables = $featureLibrary->lookupTables($table_name);
        }
      } 
  
      return $lookup_tables;
    }

    private function historyTrackingField(String $table_name, String $history_type): String
    {
  
      $featureLibrary = $this->loadLibrary($table_name);
  
      $history_type_field = "";
  
      if (property_exists($featureLibrary, $history_type . '_field')) {
        $property = $history_type . '_field';
        $history_type_field = $featureLibrary->$property;
      } else {
        $fields = $this->getAllTableFields($table_name);
  
        if (in_array($table_name . '_' . $history_type, $fields)) {
          $history_type_field = $table_name . '_' . $history_type;
        }
      }
  
      return $history_type_field;
    }

    private function nameField(String $table_name = ""): String
    {
      // log_message('error', json_encode($table_name));
      $featureLibrary = $this->loadLibrary($table_name);
  
      $name_field = "";
  
      if (property_exists($featureLibrary, 'name_field')) {
        $name_field = $featureLibrary->name_field;
      } else {
        $fields = $this->getAllTableFields($table_name);
  
        if (in_array($table_name . '_name', $fields)) {
          $name_field = $table_name . '_name';
        }
      }
  
      return $name_field;
    }

  function setChangeFieldType($detail_table = "")
  {

    // Aray format for the changeFieldType method in feature library: 
    //array('[field_name]'=>array('field_type'=>$new_type,'options'=>$options));

    $featureLibary = $this->loadLibrary($this->controller);

    if ($detail_table !== "") {
      $featureLibary = $this->loadLibrary($detail_table);
    }

    if (
      method_exists($featureLibary, 'changeFieldType') &&
      is_array($featureLibary->changeFieldType())
    ) {

      $this->set_field_type = $featureLibary->changeFieldType();
    }

    return $this->set_field_type;
  }

  public function updateQueryResultForFieldsChangedToSelectType(String $table, array $query_result): array
  {
    // Check if there is a change of field type set and update the results
    $changed_field_type = $this->setChangeFieldType($table);

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

  function detailTables(string $table_name = ""): array
{
    $featureLibrary = $this->loadLibrary($table_name);

    $uri = service('uri');
    $db = \Config\Database::connect('read');

    if ($this->controller == 'approval' && $this->action == 'view') {
        // This is specific to approval view, only to list the detail listing of the selected approveable 
        // items
        // This prevents the approval from listing the details tables instead of a specific approveable item
        $id = $uri->getSegment(3, 0);

        // This line needs to be moved to a model
        $builder = $db->table('approval');
        $builder->join('approve_item', 'approve_item.approve_item_id=approval.fk_approve_item_id');
        $detail_table = $builder->getWhere(['approval_id' => hash_id($id, 'decode')])->getRow()->approve_item_name;

        $this->detail_tables = [$detail_table];
    } elseif ($this->dependantTable($table_name) !== "") {
        // If dependant_table exists, you can't have more than one detail table. This piece nullifies the use
        // of the detail_tables feature model if set
        $this->detail_tables[] = $this->dependantTable($table_name);
    } elseif (
        $this->action == 'view' && method_exists($featureLibrary, 'detail_tables') &&
        is_array($featureLibrary->detail_tables())
    ) {
        $this->detail_tables = $featureLibrary->detail_tables();
    }

    return $this->detail_tables;
}

public function checkIfTableHasDetailListing(String $table_name = ""): Bool
  {

    $table = $table_name == "" ? $this->controller : $table_name;

    $all_detail_tables = $this->detailTables($table);

    $has_detail_table = false;

    if (is_array($all_detail_tables) && in_array($this->dependantTable($table), $all_detail_tables)) {
      $has_detail_table = true;
    }

    return $has_detail_table;
  }


  function checkIfTableHasDetailTable(String $table_name = ""): Bool
  {

    $table = isEmpty($table_name) ? $this->controller : $table_name;

    $all_detail_tables = $this->detailTables($table);

    $has_detail_table = false;

    if (is_array($all_detail_tables) && count($all_detail_tables) > 0) {
      $has_detail_table = true;
    }

    return $has_detail_table;
  }

  protected function checkIfTableIsMultiRow(String $table_name = "")
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

  public function listTableWhere(\CodeIgniter\Database\BaseBuilder $queryBuilder):void {
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
          $queryBuilder->where(array($account_system_table . '.fk_account_system_id' => $this->session->user_account_system_id));
      }
    }
  }
}