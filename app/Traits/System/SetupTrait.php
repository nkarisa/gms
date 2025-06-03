<?php

namespace App\Traits\System;

use Config\GrantsConfig;

trait SetupTrait {
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

  function create_missing_system_files_from_json_setup()
  {
    $specs_array = create_specs_array(); // file_get_contents(APPPATH . 'version' . DIRECTORY_SEPARATOR . 'spec.json');
    $this->create_missing_system_files($specs_array);
  }

  function create_missing_system_files($table_array)
  {
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
    $controllers_path = APPPATH . 'Controllers' . DIRECTORY_SEPARATOR . 'Web' . DIRECTORY_SEPARATOR . $app_name . DIRECTORY_SEPARATOR;
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


  function tableSetup($table)
  {
    $statusLibrary = new \App\Libraries\Core\StatusLibrary;
    $this->mandatoryFields($table);
    $statusLibrary->insertStatusIfMissing($table);
  }

  function generateItemTrackNumberAndName($approveable_item)
  {
    $header_random = record_prefix($approveable_item) . '-' . rand(1000, 90000);
    $columns[$approveable_item . '_track_number'] = $header_random;
    $columns[$approveable_item . '_name'] = ucfirst($approveable_item) . ' # ' . $header_random;

    return $columns;
  }

  function loadConfigurations()
  {
    $grantsConfig = new GrantsConfig();
    $settings = service('settings');
    $settingsModel = new \App\Models\System\SettingsModel();

    $globalConfigurations = get_object_vars($grantsConfig);
    foreach ($globalConfigurations as $globalConfigurationKey => $globalConfigurationValue) {
      // Check if the setting exists in the database
      if (!$settingsModel->hasSetting("GrantsConfig", $globalConfigurationKey)) {
        // If the setting does not exist, insert it into the database
        $globalConfigurationValue = is_array($globalConfigurationValue) ? json_encode($globalConfigurationValue) : $globalConfigurationValue;
        $settings->set("GrantsConfig.$globalConfigurationKey", $globalConfigurationValue);
      }

    }

    $contextConfig = new \Config\ContextConfig();
    $contextualConfigurations = get_object_vars($contextConfig);

    foreach ($contextualConfigurations as $contextualConfigurationKey => $contextualConfigurationValue) {
      if (!$settingsModel->hasSetting("ContextConfig", $contextualConfigurationKey) && is_array($contextualConfigurationValue)) {
        $settings->set("ContextConfig.$contextualConfigurationKey", json_encode($contextualConfigurationValue));
      }
    }
  }

  function createTableApproversColumns(string $tableName)
  {
      $db_forge = \Config\Database::forge('write'); // Load the default database group
  
      if (!$this->write_db->fieldExists($tableName . '_approvers', $tableName)) {
          // Define the column details
          $fields = [
              $tableName . '_approvers' => [
                  'type' => 'JSON',
                  'null' => true,
              ],
          ];
  
          // Add the column to the specified table
          $db_forge->addColumn($tableName, $fields);
      }
  }

}