<?php

namespace App\Libraries\System\Outputs;

use \CodeIgniter\Database\BaseBuilder;
class ListOutput extends OutputTemplate
{

  protected $parentTable = null;
  protected $parentId = null;
  function __construct($module)
  {
    parent::__construct($module);
  }

  private function listInternalQueryResults(array $lookup_tables): array
  {
    $table = $this->controller;

    $filter_where_array = hash_id($this->id, 'decode') > 0 && !in_array($table, decode_setting("GrantsConfig","tableThatDontRequireHistoryFields")) ? [$table . '.fk_status_id' => hash_id($this->id, 'decode')] : [];
    $toggle_list_select_columns = $this->libs->toggleListSelectColumns($this->parentTable);

    if ($table == 'status') {
      array_push($toggle_list_select_columns, $table . '.status_id');
    } else {
      array_push($toggle_list_select_columns, $table . '.fk_status_id as status_id');
    }

    $toggle_list_select_columns = array_values($toggle_list_select_columns);

    $listTableWhere = 'listTableWhere';

    if ($this->parentTable != null) {
      $listTableWhere = 'detailListTableWhere';
    }

    $selected_results = $this->drawDatatableResults($table, $toggle_list_select_columns, $lookup_tables, $listTableWhere, $filter_where_array);

    return $selected_results;
  }

  private function runQueryBuilder(
    BaseBuilder $builder,
    string $table,
    array $lookup_tables,
    string $where_method,
    array $filter_where_array = []
  ): void {

    // Load the model dynamically
    $featureLibrary = $this->libs->loadLibrary($table);
    $foreignKeyMappings = $featureLibrary->lookUpTablesForeignKeyMappings;

    // Apply model-defined where condition if the method exists
    if ($this->parentTable != null) {
      $foreignKeyField = $this->parentTable . '_id';
      
      if(array_key_exists($this->parentTable,$foreignKeyMappings)){
        $foreignKeyField = $foreignKeyMappings[$this->parentTable];
      }
      $builder->where($foreignKeyField, hash_id($this->parentId, 'decode'));
    }

    if (method_exists($featureLibrary, $where_method)) {
      $featureLibrary->$where_method($builder);
    }

    $this->libs->joinTablesWithOffice($builder, $table);


    // Handle lookup tables and joins
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
        $builder->join($lookup_table, $lookup_table . '.' . $lookup_table_id . '=' . $table . '.' . $foreignKeyField);
      }
    }

    // Apply ordering
    if (method_exists($featureLibrary, 'orderListPage')) {
      $builder->orderBy($featureLibrary->orderListPage());
    } else {
      $builder->orderBy($table . '_created_date DESC');
    }

    // Apply filter conditions
    if (is_array($filter_where_array) && count($filter_where_array) > 0) {
      $builder->where($filter_where_array);
    }
  }

  private function tableQueryBuilder($table): BaseBuilder
  {
    $builder = $this->read_db->table($table);

    if (!$builder->get()) {
      $message = 'The table ' . $this->controller . ' has no relationship with ' . $table . '. Check the ' . $this->controller . '_model detail_tables method';
      throw new \CodeIgniter\Exceptions\PageNotFoundException($message);
    }

    return $builder;
  }

  private function drawDatatableResults(string $table, array $selected_columns, array $lookup_tables, string $listTableWhere, array $filter_where_array): array
  {

    $builder = $this->tableQueryBuilder($table);
    $this->runQueryBuilder(
      $builder,
      $table,
      $lookup_tables,
      $listTableWhere,
      $filter_where_array
    );

    $this->libs->dataTableBuilder($builder, $table, $selected_columns);

    $builder->select($selected_columns);
    // log_message('error', $builder->getCompiledSelect());
    return $builder->get()->getResultArray();
  }


  private function runListQueryCountAllRecords(
    $table,
    $selected_columns,
    $lookup_tables,
    $listTableWhere,
    $filter_where_array
  ): int {
    if (!$this->read_db->tableExists($table)) {
      $message = 'The table ' . $this->controller . ' has no relationship with ' . $table . '. Check the ' . $this->controller . '_model detail_tables method';
      throw new \CodeIgniter\Exceptions\PageNotFoundException($message);
    } else {
      $builder = $this->tableQueryBuilder($table);

      $this->runQueryBuilder(
        $builder,
        $table,
        $lookup_tables,
        $listTableWhere,
        $filter_where_array
      );

      $this->libs->dataTableBuilder($builder, $table, $selected_columns);

      return $builder->countAllResults();
    }
  }


  private function toggleListQueryResults()
  {

    $total_records = 0;
    $query_result = [];

    $featureLibrary = $this->libs->loadLibrary($this->controller);
    $lookup_tables = $this->libs::call($this->controller . '.lookupTables');
    $listSelectColumns = $this->libs->toggleListSelectColumns($this->parentTable);
    $builder = $this->read_db->table($this->controller);
    $filter_where_array = hash_id($this->id, 'decode') > 0 && !in_array($this->controller, decode_setting("GrantsConfig","tableThatDontRequireHistoryFields")) ? [$this->controller . '.fk_status_id' => hash_id($this->id, 'decode')] : [];
    $listTableWhere = 'listTableWhere';

    if ($this->parentTable != null) {
      $listTableWhere = 'detailListTableWhere';
    }

    $total_records = $this->runListQueryCountAllRecords($this->controller, $listSelectColumns, $lookup_tables, $listTableWhere, $filter_where_array);//count($query_result);

    if (
      method_exists($featureLibrary, 'list')
      && is_array($featureLibrary->list($builder, $listSelectColumns, $this->parentId, $this->parentTable))
      && array_key_exists('results', $featureLibrary->list($builder, $listSelectColumns, $this->parentId, $this->parentTable))
      && !empty($featureLibrary->list($builder, $listSelectColumns, $this->parentId, $this->parentTable)['result'])
    ) {
      // log_message('error', 'Here');
      $feature_model_list_result = $featureLibrary->list($builder, $listSelectColumns, $this->parentId, $this->parentTable)['results'];
      // Allows empty result set
      $query_result = $feature_model_list_result; // A full user defined query result
    } else {
      // log_message('error', 'There');
      // Get result from grants model if feature model list returns empty
      $query_result = $this->listInternalQueryResults($lookup_tables);
    }

    // // Implemeting resetting of options if a field is changed from to a select type
    $query_result['selected_results'] = $this->libs->updateQueryResultForFieldsChangedToSelectType($this->controller, $query_result);
    $query_result['total_records'] = $total_records;

    return $query_result;
  }

  function updateListCustomColumnsValues(&$fields_meta_data, &$selectedRecords, array &$selectedColumns){
    
    $library = $this->libs->loadLibrary($this->controller);
    if(
      method_exists($library, 'additionalListColumns') && 
      is_array($columns = $library->additionalListColumns()) &&
      count($columns) > 0
    ){
      
      if(!empty($columns)){
        foreach($columns as $newColumn => $positionAfter){
            if($positionAfter != null){  
              $refIndex = array_search($positionAfter, $selectedColumns);
              array_splice($selectedColumns, $refIndex + 1, 0, $newColumn);
              for($i = 0; $i <= count($selectedRecords); $i++){
                if(isset($selectedRecords[$i])){
                  $keys = array_keys($selectedRecords[$i]);
                  array_splice($keys, $refIndex + 1, 0, $newColumn);
                  
                  $values = array_values($selectedRecords[$i]);
                  array_splice($values, $refIndex + 1, 0, get_phrase('value_not_set'));
        
                  $selectedRecords[$i] = array_combine($keys, $values);
                  }
              }
            }else{
              array_push($selectedColumns, $newColumn );
              for($i = 0; $i<count($selectedRecords); $i++){
                $selectedRecords[$i][$newColumn] = get_phrase('value_not_set');
              }
            }
            $fields_meta_data[$newColumn] = 'varchar';
        }
      }
    }

    $selectedColumns = array_values($selectedColumns);
  }

  public function getOutput($args = [])
  {
    $this->parentId = isset($args[0]) ? $args[0] : null;
    $this->parentTable = isset($args[1]) ? $args[1] : null;
    $library = $this->libs->loadLibrary($this->controller);

    // This line prevent the timeout issue of the more menu icon. Reason for timeout are unknown
    if ($this->controller == 'menu') {
      return [];
    }

    // Prevent returning complete view if the feature has no schema
    $field_data = service('grantslib')::call('grants.fieldData', [$this->controller]);

    if (empty($field_data)) {
      return [];
    }

    $toggleListQueryResults = $this->toggleListQueryResults();
    $keys = $this->libs->toggleListSelectColumns($this->parentTable);
    $table_body = $toggleListQueryResults['selected_results'];
    $fields_meta_data = $this->libs->fieldsMetaDataTypeAndName($this->controller);
    $this->updateListCustomColumnsValues($fields_meta_data, $table_body, $keys);

    $total_records = $toggleListQueryResults['total_records'];
    $controller = $this->controller;
    // $fields_meta_data = $this->libs->fieldsMetaDataTypeAndName($this->controller);
  
    return compact('table_body', 'total_records', 'keys', 'fields_meta_data', 'controller');
  }
}