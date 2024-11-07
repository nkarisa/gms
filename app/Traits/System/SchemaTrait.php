<?php

namespace App\Traits\System;

trait SchemaTrait
{

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
                throw new \Exception("Package $package not found.");
            }
        }

        // Sort the tables alphabetically
        ksort($tables);

        return $tables;
    }

    private function fieldNames($tableName)
    {
        $fieldData = $this->fieldData($tableName);
        $fieldNames = array_column($fieldData, 'name');
        return $fieldNames;
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
            throw new \Exception("Primary key field for $table_name not found.");
        }

        // Return the primary key field name
        return $primary_key_field;
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
                !method_exists($table_library, 'detachDetailTable') ||
                !$table_library->detachDetailTable())
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

    public function fieldsMetaDataTypeAndName($table)
    {

        $fields_meta_data = [];

        $table_names = $this->lookupTables($table);

        array_push($table_names, $table);

        $feature_library = $this->loadlibrary($table);

        foreach ($table_names as $table_name) {

            if ($table_name !== $table) {
                $feature_library = $this->loadlibrary($table_name);
            }

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
                    method_exists($feature_library, 'changeFieldType') &&
                    array_key_exists($field_name, $feature_library->changeFieldType())
                ) {
                    $fields_meta_data[$field_name] = $$feature_library->changeFieldType()[$field_name]['field_type'];
                }
            }
        }

        return $fields_meta_data;
    }

    public function isNameField(string $table, string $column): bool
    {

        $table_name_field = $this->nameField($table);

        $is_name_field = false;

        if (strtolower($table_name_field) == strtolower($column)) {
            $is_name_field = true;
        }

        return $is_name_field;
    }

    public function isHistoryTrackingField(string $table_name, string $column, string $history_type = "")
    {

        $is_history_tracking_field = false;

        //Helps to prevent the use of invalid history tracking fields
        $template_history_types = array('track_number', 'created_date', 'created_by', 'last_modified_date', 'last_modified_by', 'deleted_at');

        //foreach($history_types as $history_type){

        if ($history_type != "" && in_array($history_type, $template_history_types)) {
            $history_tracking_field = $this->historyTrackingField($table_name, $history_type);

            if ($column == $history_tracking_field) {
                $is_history_tracking_field = true;
            }
        } else {
            // Used when type is not passed. Uses strict column naming
            foreach ($template_history_types as $template_history_type) {
                if ($column == $table_name . '_' . $template_history_type) {
                    $is_history_tracking_field = true;
                }
            }
        }
        return $is_history_tracking_field;
    }

}