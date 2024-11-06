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
  use \App\Traits\System\CrudTrait;

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
  protected $model = null;
  protected $library = null;
  public $dbSchema;
  private $uri;

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
    $this->uri = service('uri');
    $segments = $this->uri->getSegments();

    $this->controller = isset($segments[0]) ? $segments[0] : 'dashboard';
    $this->action = isset($segments[1]) ? $segments[1] : 'list';
    $this->id = isset($segments[2]) ? $segments[2] : 0;

    // $this->library = $this->loadLibrary($this->controller);

    // Session 
    $this->session = service('session');

    // Request 
    $this->request = service('request');

    // Response 
    $this->response = service('response'); // Services::response()

  }

  // private function callbackActionAfterInsert($table_name, $post_array, $approval_id, $header_id): array
  // {

  //   $featureLibrary = $this->loadLibrary($table_name);

  //   $success = false;

  //   if (method_exists($featureLibrary, 'actionAfterInsert')) {
  //     $success = $featureLibrary->actionAfterInsert($post_array, $approval_id, $header_id);
  //   }

  //   return $success;
  // }


  // private function callbackActionBeforeInsert($table_name, $post_array): array
  // {

  //   $featureLibrary = $this->loadLibrary($table_name);

  //   $updated_post_array = array();

  //   if (method_exists($featureLibrary, 'actionBeforeInsert')) {
  //     $updated_post_array = $featureLibrary->actionBeforeInsert($post_array);
  //   } else {
  //     $updated_post_array = $post_array;
  //   }

  //   return $updated_post_array;
  // }

  private function callbackTransactionValidateDuplicatesColumns($table_name)
  {

    $featureLibrary = $this->loadLibrary($table_name);

    $columns = array();

    if (method_exists($featureLibrary, 'transactionValidateDuplicatesColumns')) {
      $columns = $featureLibrary->transactionValidateDuplicatesColumns();
    }

    return $columns;
  }

  private function callbackMultiSelectField($table_name): string
  {
    $library = $this->loadLibrary($table_name);

    $multi_select_field = '';

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
    $modules = $this->config->modules; // Assuming $config is an instance of Config\App
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
    $modules = $this->config->modules; // Assuming $config is an instance of Config\App
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
          if (in_array($method, ['listOutput', 'viewOutput', 'editOutput', 'singleFormAddOutput'])) {
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

  // static function createMissingResources($feature){
  //   // Create missing controllers, models and libraries
  // }


  function create_missing_system_files_from_json_setup()
  {

    $specs_array = create_specs_array(); // file_get_contents(APPPATH . 'version' . DIRECTORY_SEPARATOR . 'spec.json');
    //print_r($specs_array);
    //$specs_array = yaml_parse($raw_specs, 0);

    $this->create_missing_system_files($specs_array);
  }

  function create_missing_system_files($table_array)
  {
    //print_r($table_array);exit;
    foreach ($table_array as $app_name => $app_tables) {
      foreach ($app_tables['tables'] as $table_name => $setup) {
        $this->create_missing_system_files_methods($table_name, $app_name);
      }
    }
  }

  function create_missing_system_files_methods($table_name, $app_name)
  {

    $table_name = pascalize($table_name);
    $app_name = ucfirst($app_name);

    $assets_temp_path = 'assets' . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR;
    $controllers_path = APPPATH . 'controllers' . DIRECTORY_SEPARATOR . $app_name . DIRECTORY_SEPARATOR;

    if (!file_exists($controllers_path . $table_name . '.php')) {
      $this->create_missing_controller($table_name, $assets_temp_path, $app_name);
      $this->create_missing_model($table_name, $assets_temp_path, $app_name);
      $this->create_missing_library($table_name, $assets_temp_path, $app_name);
    }
  }

  function create_missing_controller($table, $assets_temp_path, $app_name)
  {

    $controllers_path = APPPATH . 'controllers' . DIRECTORY_SEPARATOR . 'web' . DIRECTORY_SEPARATOR . $app_name . DIRECTORY_SEPARATOR;

    // Copy contents of assets/temp_library to the created file after the tag above
    $replaceables = array("%cap_feature%" => $table, '%cap_module%' => $app_name);

    $this->write_file_contents($table, $controllers_path, $assets_temp_path, $replaceables, 'Controller');
  }

  function create_missing_library($table, $assets_temp_path, $app_name)
  {

    $libararies_path = APPPATH . 'Libraries' . DIRECTORY_SEPARATOR . $app_name . DIRECTORY_SEPARATOR;

    // Copy contents of assets/temp_library to the created file after the tag above
    $replaceables = array("%cap_feature%" => $table, '%cap_module%' => $app_name, '%small_feature%' => strtolower($table));

    $this->write_file_contents($table, $libararies_path, $assets_temp_path, $replaceables, 'Library');
  }

  function create_missing_model($table, $assets_temp_path, $app_name)
  {

    $models_path = APPPATH . 'Models' . DIRECTORY_SEPARATOR . $app_name . DIRECTORY_SEPARATOR;

    $replaceables = array(
      "%cap_module%" => $app_name,
      "%cap_feature%" => $table,
      '%small_feature%' => strtolower($table)
    );

    $this->write_file_contents($table, $models_path, $assets_temp_path, $replaceables, 'Model');
  }

  function write_file_contents($table, $sys_file_path, $assets_temp_path, $replaceables, $temp_type = 'Controller')
  {

    // Check if model is available and if not create the file
    if (
      (in_array($temp_type, ['Model', 'Library']) && !file_exists($sys_file_path . $table . $temp_type . '.php')) ||
      ($temp_type == "Controller" && !file_exists($sys_file_path . $table . '.php'))
    ) {

      // Create the file  
      $handle = null;

      if ($temp_type == 'Model' || $temp_type == 'Library') {
        $handle = fopen($sys_file_path . $table . $temp_type . '.php', "w") or die("Unable to open file!");
      } else {
        $handle = fopen($sys_file_path . $table . '.php', "w") or die("Unable to open file!");
      }

      // Add the PHP opening tag to the file 
      $php_tag = '<?php';
      fwrite($handle, $php_tag);

      $replacefrom = array_keys($replaceables);

      $replacedto = array_values($replaceables);

      $file_raw_contents = file_get_contents($assets_temp_path . $temp_type . '.tpl');

      $file_contents = str_replace($replacefrom, $replacedto, $file_raw_contents);

      $file_code = "\n" . $file_contents;

      fwrite($handle, $file_code);
    }
  }

  // public function runListQuery(
  //   string $table,
  //   array $selectedColumns,
  //   array $lookupTables = [],
  //   string $modelWhereMethod = "listTableWhere",
  //   array $filterWhereArray = []
  // ): array {
    
  //   // Get the database connection
  //   $builder = $this->read_db->table($table);

  //   if (!$this->tableExists($table)) {
  //     $message = "The table " . $table . " doesn't exist in the database. Check the lookupTables function in the " . $table . "Model.";
  //     throw new \CodeIgniter\Exceptions\PageNotFoundException($message);
  //   }else{
      
  //     $this->runListActualQuery($builder, $table, $selectedColumns,$lookupTables,$modelWhereMethod, $filterWhereArray);
      
  //     if ($this->request->getPost('draw')) {

  //       // Limiting Server Datatable Results
  //       $start = intval($this->request->getPost('start'));
  //       $length = intval($this->request->getPost('length'));
  //       $builder->limit($length, $start);
    
  //       // Ordering Server Datatable Results
  //       $order = $this->request->getPost('order');
  //       $col = '';
  //       $dir = 'desc';
    
  //       if (!empty($order)) {
  //           $col = $order[0]['column'];
  //           $dir = $order[0]['dir'];
  //       }
    
  //       if ($col == '') {
  //           $builder->orderBy($table . '_id', 'DESC');
  //       } else {
  //           $builder->orderBy($selectedColumns[$col], $dir);
  //       }
    
  //       // Searching Server Datatable Results
  //       $search = $this->request->getPost('search');
  //       $value = $search['value'];
    
  //       // Remove the last column (if necessary)
  //       array_pop($selectedColumns);
    
  //       if (!empty($value)) {
  //           $builder->groupStart();  // Begin grouping
  //           $column_key = 0;
  //           foreach ($selectedColumns as $column) {
  //               if ($column_key == 0) {
  //                   $builder->like($column, $value, 'both');
  //               } else {
  //                   $builder->orLike($column, $value, 'both');
  //               }
  //               $column_key++;
  //           }
  //           $builder->groupEnd();  // End grouping
  //       }
  //     }

  //     $result = $builder->get()->getResultArray();
  //     return $result;
  //   }

  // }

  // private function runListActualQuery(
  //   $builder,
  //   string $table,
  //   array $selectedColumns,
  //   array $lookupTables = [],
  //   string $libraryWhereMethod = "listTableWhere",
  //   array $filterWhereArray = []
  // ) {
  //   // Run column selector
  //   $builder->select($selectedColumns);

  //   // Load the model dynamically
  //   $library = $this->loadLibrary($table);
    
  //   // Apply the model's custom "where" method, if it exists
  //   if (method_exists($library, $libraryWhereMethod)) {
  //     $library->$libraryWhereMethod($builder);
  //   }

  //   // Handle lookup tables and apply joins
  //   if (is_array($lookupTables) && count($lookupTables) > 0) {
  //     foreach ($lookupTables as $lookupTable) {
  //       // Ensure lookup table exists in the database
  //       if (!$this->tableExists($lookupTable)) {
  //         $message = "The table " . $lookupTable . " doesn't exist in the database. Check the lookupTables function in the " . $table . "Model.";
  //         throw new \CodeIgniter\Exceptions\PageNotFoundException($message);
  //       }

  //       // Join lookup tables
  //       $lookupTableId = $lookupTable . '_id';
        
  //       $builder->join($lookupTable, $lookupTable . '.' . $lookupTableId . '=' . $table . '.fk_' . $lookupTableId);
  //     }
  //   }

  //   // Apply ordering method from the model, if exists
  //   if (method_exists($library, 'orderListPage')) {
  //     $builder->orderBy($library->orderListPage());
  //   } else {
  //     $builder->orderBy($table . '_created_date', 'DESC');
  //   }

  //   // Apply additional filter conditions, if provided
  //   if (is_array($filterWhereArray) && count($filterWhereArray) > 0) {
  //     $builder->where($filterWhereArray);
  //   }

  // }


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

    return $visible_columns;
  }

  
  public function lookupTablesFields(string $table): array
  {
    $lookup_tables_fields = array();

    if (is_array($this->loadLibrary($table)->lookupTables()) && count($this->loadLibrary($table)->lookupTables()) > 0) {
      foreach ($this->loadLibrary($table)->lookupTables() as $lookup_table) {
        $lookup_tables_fields = array_merge($lookup_tables_fields, $this->getAllTableFields($lookup_table));
      }
    }

    return $lookup_tables_fields;
  }

  // function callbackLookupTables(string $table_name): array
  // {

  //   $featureLibrary = $this->loadLibrary($table_name);
  //   $approveItemLibrary = $this->loadLibrary('approve_item');

  //   $lookup_tables = array();

  //   if (is_array($featureLibrary->lookupTables($table_name))) {
  //     if ($this->action !== 'single_form_add') {
  //       // Check if status and approval lookup tables doesn't exist and add them
  //       $lookup_tables = $featureLibrary->lookupTables($table_name);
  //       $this->addMandatoryLookupTables($lookup_tables);

  //       // Hide status and approval columns if the active controller/table is not approveable
  //       if (!$approveItemLibrary->approveableItem($table_name)) {
  //         $this->removeMandatoryLookupTables($lookup_tables);
  //       }
  //     } else {
  //       $lookup_tables = $featureLibrary->lookupTables($table_name);
  //     }
  //   }

  //   return $lookup_tables;
  // }

  function lookupTables(): array
  {
    $lookUpTables = $this->callbackLookupTables();
    return $lookUpTables;
  }

  public function callbackLookupTables($table_name = ''): array
    {

        $approveItemLibrary = $this->loadLibrary('approve_item');

        if (isEmpty($table_name))
            $table_name = $this->controller;

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

        // Hide status and approval columns if the active controller/table is not approveable

        if (!$approveItemLibrary->approveableItem($table_name)) {
            if (in_array('status', $foreign_tables_array)) {
                unset($foreign_tables_array[array_search('status', $foreign_tables_array)]);
            }

        }

        return !empty($foreign_tables_array) ? array_values($foreign_tables_array) : [];;

    }

  public function historyTrackingField(string $table_name, string $history_type): string
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

  // function loadDetailLibrary(string $tableName = "", string $module = ""): string
  // {

  //     $tableExists = $this->tableExists($tableName);

  //     if ($tableName !== "" && !is_array($tableName) && $this->read_db->tableExists($tableName) && $tableName != 'migrations') {

  //         // Check if the controller for the table does not exist and the table exists
  //         if (!file_exists(APPPATH . 'Controllers' . DIRECTORY_SEPARATOR . $tableName . '.php') && $tableExists) {
  //             // Handle creating missing models or controllers based on a missing controller
  //             // when the database table exists
  //             $assetsTempPath = FCPATH . 'assets' . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR;

  //             // Additional logic for creating missing files can be added here if required
  //         }

  //         $model = $tableName . 'Model';

  //         try {
  //             // Load the model
  //             $this->load->model($model);
  //         } catch (\Exception $e) {
  //             $message = "Unable to load the specified model " . $model . " in Grants Library as indicated in the detail_tables function of the " . $this->controller . "Model.</br>";
  //             $message .= "Verify if the table " . $tableName . " exists and if the file " . $model . " is present in the third_party Packages Core or Grants models.";
  //             throw new \CodeIgniter\Exceptions\PageNotFoundException($message);
  //         }
  //     }

  //     return $model;
  // }


  public function nameField(string $table_name = ""): string
  {
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

  public function fieldsMetaDataTypeAndName($table)
  {

    $fields_meta_data = [];
    $library = $this->loadLibrary($table);

    $table_names = $this->callbacklookupTables($table);

    array_push($table_names, $table);

    foreach ($table_names as $table_name) {

      if ($table_name !== $table) {
        $library = $this->loadLibrary($table_name);
      }

      // $feature_library = $table_name . '_library';

      $meta_data = $this->tableFieldsMetadata($table_name);
      $names = array_column($meta_data, 'name');
      $types = array_column($meta_data, 'type');
      $fields_meta_data = array_merge($fields_meta_data, array_combine($names, $types));

      foreach ($fields_meta_data as $field_name => $field_type) {
        if (substr($field_name, 0, 3) == 'fk_') {
          $_field_name = substr($field_name, 3, -3) . '_name';
          unset($fields_meta_data[$field_name]);
          $fields_meta_data[$_field_name] = 'varchar';
        }

        if (
          method_exists($library, 'changeFieldType') &&
          array_key_exists($field_name, $library->changeFieldType())
        ) {
          $fields_meta_data[$field_name] = $library->changeFieldType()[$field_name]['field_type'];
        }
      }
    }

    return $fields_meta_data;
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
      // This is specific to approval view, only to list the detail listing of the selected approveable 
      // items
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
      $this->action == 'view' && method_exists($featureLibrary, 'detailTables') &&
      is_array($featureLibrary->detailTables()) && count($featureLibrary->detailTables()) > 0
    ) {
      $this->detail_tables = $featureLibrary->detailTables();
    }

    return $this->detail_tables;
  }

  public function checkIfTableHasDetailListing(string $table_name = ""): bool
  {

    $table = $table_name == "" ? $this->controller : $table_name;

    $all_detail_tables = $this->detailTables($table);

    $has_detail_table = false;

    if (is_array($all_detail_tables) && in_array($this->dependantTable($table), $all_detail_tables)) {
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

    if(!$this->session->system_admin && in_array($this->controller, $tables_with_account_system_relationship)){
      $queryBuilder->where(array($this->controller.'.fk_account_system_id' => $this->session->user_account_system_id));
    }elseif (!empty($lookup_tables)) {
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

    if(in_array( 'fk_account_system_id', $table_fields)) {
      $table_has_account_system = true;
    }
    //echo 1;exit;
    return $table_has_account_system;
  }

  function joinTablesWithAccountSystem($builder, $table)
  {

    $array_intersect = array_intersect($this->callbackLookupTables($table), $this->tablesWithAccountSystemRelationship());
    
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

  public function getPackageSchema($package)
  {
    $packageSchema = $this->getSchema($package);
    return $packageSchema;
  }

  /**
   * is_primary_key_field
   * 
   * Check if a supplied field for a table is a primary key field
   * @param string $table_name - The name of a table to check
   * @param string $field - Field to check from the table
   * 
   * @return bool - True if Primary Key field else false
   */
  function isPrimaryKeyField(string $table_name, string $field): bool
  {

    $is_primary_key_field = false;

    $metadata = $this->tableFieldsMetadata($table_name);

    foreach ($metadata as $data) {
      if ($data['primary_key'] == 1 && $data['name'] == $field) {
        $is_primary_key_field = true;
      }
    }

    return $is_primary_key_field;
  }

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

  /**
   * tables_name_fields
   * 
   * Get name fields of the supplied tables
   * @param array $tables - Tables to get name fields for
   * @return array - Name fields array
   */
  function tablesNameFields(array $tables): array
  {
    $table_name_fields = [];
    if (is_array($tables) && count($tables) > 0) {
      foreach ($tables as $table) {
        array_push($table_name_fields, $this->nameField($table));
      }
    }

    return $table_name_fields;
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

  function tableSetup($table)
  {
    $statusLibrary = new \App\Libraries\Core\StatusLibrary;
    $this->mandatoryFields($table);
    $statusLibrary->insertStatusIfMissing($table);
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

  /**
   * header_row_field
   * 
   * This method populates the single_form_add or master part of the multi_form_add pages.
   * It also checks if their is set_change_field_type of the current column from the feature library
   * 
   * @param $column String : A column from a table
   * @param $field_value Mixed : Value of the field mainly from edit form
   * @param bool $show_only_selected_value
   * @return string
   */

  function headerRowField(string $column, string $field_value = null, bool $show_only_selected_value = false, $detail_table = ''): string
  {

    $f = new \App\Libraries\System\FieldsBase($column, $this->controller, true);

    if ($detail_table != '') {
      $f = new \App\Libraries\System\FieldsBase($column, $detail_table, false, true);
    }

    $this->setChangeFieldType();
    $field_type = $f->field_type();
    $field = $field_type . "_field";

    if (array_key_exists($column, $this->set_field_type)) {

      $field_type = $this->set_field_type[$column]['field_type'];
      $field = $field_type . "_field";

      if ($field_type == 'select' && count($this->set_field_type[$column]['options']) > 0) {
        return $f->select_field($this->set_field_type[$column]['options'], $field_value, false, '', $this->checkMultiSelectField($detail_table));
      } else {
        return $f->$field($field_value);
      }
    } elseif ($field_type == 'select') {
      // $column has a _name suffix if is a foreign key in the table
      // This is converted from fk_xxxx_id where xxxx is the primary table name
      // The column should be in the name format and not id e.g. fk_user_id be user_name
      $lookup_table = strtolower(substr($column, 0, -5));

      return $f->$field($this->checkLookupValues($lookup_table), $field_value, $show_only_selected_value, '', $this->checkMultiSelectField($detail_table));
    } elseif (strrpos($column, '_is_') == true) {

      $field_value = $f->set_default_field_value() !== null ? $f->set_default_field_value() : $field_value;
      return $f->select_field([get_phrase('no'), get_phrase('yes')], $field_value, $show_only_selected_value);
    } else {
      return $f->$field($field_value);
    }
  }

  function checkMultiSelectField($table_name = "")
  {

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

    if ($table_name == '')
      $table_name = $this->controller;

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

    // Hide status and approval columns if the active controller/table is not approveable
    $approveItemLibrary = new \App\Libraries\Core\ApproveItemLibrary();

    if (!$approveItemLibrary->approveableItem($table_name)) {
      if (in_array('status', $foreign_tables_array)) {
        unset($foreign_tables_array[array_search('status', $foreign_tables_array)]);
      }

    }

    return $foreign_tables_array;


  }

  function checkLookupTables($table_name = "")
  {

    if ($table_name != '') {
      $this->library = $this->loadLibrary($table_name);
    }

    $lookup_tables = array();

    if (
      method_exists($this->library, 'lookupTables') &&
      is_array($this->library->lookupTables())
    ) {

      if ($this->action !== 'singleFormAdd') {
        // Check if status and approval lookup tables doesn't exist and add them
        $lookup_tables = $this->deriveLookupTables($table_name);

        $this->addMandatoryLookupTables($lookup_tables);

        // Hide status and approval columns if the active controller/table is not approveable
        $approveItemLibrary = new \App\Libraries\Core\ApproveItemLibrary();
        if (!$approveItemLibrary->approveableItem($table_name)) {
          $this->removeMandatoryLookupTables($lookup_tables);
        }
      } else {
        $lookup_tables = $this->deriveLookupTables($table_name);
      }
    } else {
      // This part of a code is meant to offer an alternative to lookup_tables 
      // methods in models that overrided the MY_Model method
      $lookup_tables = $this->deriveLookupTables();
    }
    //print_r($lookup_tables);exit;
    return $lookup_tables;
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
      return $this->response->setJSON(['flag' => false, 'message' => $post_array['message']]);
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
            $header[$_column_name] = $_column_values[$multi_select_field_value];
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
   
      $message = $this->addInserts( $post_has_detail, $header, $detail);
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
    $headerRandom = record_prefix($this->controller) . '-' . rand(1000, 90000);
    $headerColumns[strtolower($this->controller) . '_track_number'] = $headerRandom;
    $headerColumns[strtolower($this->controller) . '_name'] = $this->request->getPost($this->controller . '_name') != ""
      ? $this->request->getPost($this->controller . '_name')
      : ucfirst($this->controller) . ' # ' . $headerRandom;

    foreach ($header as $key => $value) {
      $headerColumns[$key] = $value;
    }

    if (session()->has('masterTable')) {
      $headerColumns['fk_' . strtolower(session()->get('masterTable')) . '_id'] = hash_id($this->id, 'decode');
    }

    $headerColumns['fk_status_id'] = $this->controller != 'status' ? $initialStatus : NULL;
    $headerColumns['fk_approval_id'] = $this->controller != 'status' ?  $approvalId : NULL;
    $headerColumns[strtolower($this->controller) . '_created_date'] = date('Y-m-d');
    $headerColumns[strtolower($this->controller) . '_created_by'] = session()->get('user_id');
    $headerColumns[strtolower($this->controller) . '_last_modified_by'] = session()->get('user_id');

    // log_message('error', json_encode($headerColumns));

    // Insert the header record
    $this->write_db->table(strtolower($this->controller))->insert($headerColumns);

    // Get the inserted header record ID
    $headerId = $this->write_db->insertID();;

    // log_message('error', json_encode($this->controller));

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

    // log_message('error', json_encode(compact('transactionValidateDuplicates', 'transactionValidateByComputation','headerColumns','headerId','approvalId')));

    return $this->transactionValidate([$transactionValidateDuplicates, $transactionValidateByComputation], $headerColumns, $headerId, $approvalId);
  }

  public function transactionValidateDuplicates(String $table_name, array $insert_array, array $validation_fields = [], int $allowable_records = 0): array
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

  function transactionValidateByComputation(String $table_name, array $insert_array): array{
  
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


  public function checkEditVisibleColumns($table)
  {
      $library = $this->loadLibrary($table);
      $editVisibleColumns = [];
      $visibleColumns = [];

      // Get the list of visible columns and lookup tables
      $editVisibleColumns = $library->editVisibleColumns();
      $lookupTables = $this->callbacklookupTables($table);

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

      if($obj->getNumRows() > 0){
        $result = $obj->getRow();
      }

      return $result;
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
              if ($numRows > 0) {
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
        $updateData = [
            'fk_approve_item_id' => $this->read_db->table('approve_item')
                ->select('approve_item_id')
                ->where(['approve_item_name' => strtolower($table)])
                ->get()
                ->getRow()
                ->approve_item_id,
            'fk_user_id' => $this->session->get('user_id'),
            'history_action' => 1, // 1 = Update, 2 = Delete
            'history_current_body' => json_encode($oldData),
            'history_updated_body' => json_encode($newData),
            'history_created_date' => date('Y-m-d'),
            'history_created_by' => $this->session->get('user_id'),
            'history_last_modified_by' => $this->session->get('user_id'),
        ];

        // Insert update history into 'history' table
        $builder = $this->write_db->table('history');
        $builder->insert($updateData);
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
                    if ($library->actionAfterEdit($data, $approvalId, $id)) {
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
    
    function generateItemTrackNumberAndName($approveable_item)
    {
      $header_random = record_prefix($approveable_item) . '-' . rand(1000, 90000);
      $columns[$approveable_item . '_track_number'] = $header_random;
      $columns[$approveable_item . '_name'] = ucfirst($approveable_item) . ' # ' . $header_random;
  
      return $columns;
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

}