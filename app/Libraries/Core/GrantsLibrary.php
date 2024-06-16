<?php

namespace App\Libraries\Core;

use Config\GrantsConfig;

class GrantsLibrary
{
  protected $read_db;
  protected $write_db;
  protected $config;
  protected $controller;
  protected $action;
  protected $id;
  protected $dependant_table = null;

  function __construct()
  {
    // Load grants config
    $this->config = config(GrantsConfig::class);
    // Load default helpers
    helper('grants');
    // Load database
    $this->read_db = \Config\Database::connect('read');
    $this->write_db = \Config\Database::connect('write');

    // Set controller, action and ids
    $uri = service('uri');
    $this->controller = $uri->getSegment(1);
    $this->action = $uri->getSegment(2);
    $this->id = $uri->getSegment(3);
  }

  /**
 * Retrieves the schema of the database tables.
 *
 * @param string $package The package name to filter the schema. If empty, all packages will be included.
 * @return array An associative array containing the schema of the tables.
 *
 * @throws \Exception If the schema array format is not defined.
 */
public function getSchema($package = "")
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
public function fieldData($table)
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
public function tableFieldsMetadata($table_name = "")
{
    // If table_name is not provided, use the controller name
    $table = $table_name == "" ? $this->controller : $table_name;

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
    if ($primary_key_field == "") {
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
function listTables()
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
public function tableExists(string $table_name = ""): bool
{
    // If table_name is not provided, use the controller name
    $table = $table_name == "" ? $this->controller : $table_name;

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
function loadLibrary(string $table_name)
{
    $modules = $this->config->modules; // Assuming $config is an instance of Config\App
    $table_library = null;
    $table_library_name = convertToPascalCase($table_name) . 'Library';

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
public function getAllTableFields(string $table_name = ""): array
{
    // If table_name is not provided, use the controller name
    $table = $table_name == "" ? strtolower($this->controller) : strtolower($table_name);

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
function listFields($table)
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

}