<?php

namespace App\Libraries\System\Outputs;


class ListOutput extends OutputTemplate
{

function __construct($module){
    parent::__construct($module);
}

function featureModelListTableVisibleColumns()
  {
    $list_table_visible_columns = [];

    if (
      method_exists($this->currentLibrary, 'listTableVisibleColumns') &&
      is_array($this->libs::call($this->controller.'.listTableVisibleColumns'))
    ) {
      $list_table_visible_columns = $this->libs::call($this->controller.'.listTableVisibleColumns');

      if (!$this->libs->loadLibrary('approve_item')->approveableItem(strtolower($this->controller))) {
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
          $this->libs->primaryKeyField($this->controller),
          $list_table_visible_columns
        )
      ) {

        array_unshift(
          $list_table_visible_columns,
          $this->libs->primaryKeyField(strtolower($this->controller))
        );

        // Throw error when a column doesn't exists to avoid Datatable server side loading error

        //Add the lookup table name to the all fields array
        $all_fields = $this->libs->getAllTableFields($this->controller);
        $all_lookup_fields = $this->libs->lookupTablesFields($this->controller);
        $all_fields = array_merge($all_fields, $all_lookup_fields);
        $lookup_tables = $this->libs::call($this->controller.'.lookupTables',[$this->controller]);

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

private function toggleListSelectColumns()
  {
    // Check if the table has list_table_visible_columns not empty
    $list_table_visible_columns = $this->featureModelListTableVisibleColumns();
    $lookup_tables = $this->libs::call($this->controller.'.lookupTables');

    $get_all_table_fields = $this->libs->getAllTableFields();

    foreach ($get_all_table_fields as $get_all_table_field) {

      //Unset foreign keys columns, created_by and last_modified_by columns

      if (
        substr($get_all_table_field, 0, 3) == 'fk_' ||
        $this->libs->isHistoryTrackingField($this->controller, $get_all_table_field, 'created_by') ||
        $this->libs->isHistoryTrackingField($this->controller, $get_all_table_field, 'last_modified_by') ||
        $this->libs->isHistoryTrackingField($this->controller, $get_all_table_field, 'deleted_at')
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

          $lookup_table_columns = $this->libs->getAllTableFields($lookup_table);

          foreach ($lookup_table_columns as $lookup_table_column) {
            // Only include the name field of the look up table in the select columns
            if ($this->libs->isNameField($lookup_table, $lookup_table_column)) {
              array_push($visible_columns, $lookup_table_column);
            }
          }
        }
      }
    }
    return $visible_columns; //$this->CI->access->control_column_visibility($this->controller,$visible_columns,'read');
  }

    private function listInternalQueryResults(Array $lookup_tables):Array {
        $table = $this->controller;
        //echo hash_id($this->CI->id,'decode');exit;
        $filter_where_array = hash_id($this->id,'decode') > 0 && !in_array($table,$this->config->tableThatDontRequireHistoryFields) ? [$table.'.fk_status_id'=>hash_id($this->id,'decode')] : [];
        $toggle_list_select_columns = $this->toggleListSelectColumns();

        if($table == 'status'){
          array_push($toggle_list_select_columns,$table.'.status_id');
        }else{
          array_push($toggle_list_select_columns,$table.'.fk_status_id as status_id');
        }
        
        $selected_results = $this->runListQuery($table,$toggle_list_select_columns,$lookup_tables,'listTableWhere',$filter_where_array);
        $total_records = $this->runListQueryCountAllRecords($table,$toggle_list_select_columns,$lookup_tables,'listTableWhere',$filter_where_array);
        
        return ['selected_results'=>$selected_results, 'total_records' => $total_records];
      }

      private function runDataTableListQuery(
        \CodeIgniter\Database\BaseBuilder $builder,
        string $table,
        array $selected_columns,
        array $lookup_tables,
        string $where_method = "listTableWhere",
        array $filter_where_array = array()
    ) {

        // Get the database connection
        $db = $this->read_db;

        // Select the specified columns
        $builder->select($selected_columns);
    
        // Load the model dynamically
        $featureLibary = $this->libs->loadLibrary($table);
    
        // Apply model-defined where condition if the method exists
        if (method_exists($featureLibary, $where_method)) {
            $featureLibary->$where_method($builder);
        }
    
        // Handle lookup tables
        if (is_array($lookup_tables) && count($lookup_tables) > 0) {
            foreach ($lookup_tables as $lookup_table) {
                // Check if lookup table exists
                if (!$db->tableExists($lookup_table)) {
                    $message = "The table " . $lookup_table . " doesn't exist in the database. Check the lookup_tables function in the " . $table . "_model";
                    throw new \CodeIgniter\Exceptions\PageNotFoundException($message);
                }
                $lookup_table_id = $lookup_table . '_id';
                $builder->join($lookup_table, $lookup_table . '.' . $lookup_table_id . '=' . $table . '.fk_' . $lookup_table_id);
            }
        }
    
        // Apply ordering
        if (method_exists($featureLibary, 'orderListPage')) {
            $builder->orderBy($featureLibary->orderListPage());
        } else {
            $builder->orderBy($table . '_created_date DESC');
        }
    
        // Apply filter conditions
        if (is_array($filter_where_array) && count($filter_where_array) > 0) {
            $builder->where($filter_where_array);
        }
    
        return $builder;
    }
    

      public function runListQuery(
        $table,
        $selected_columns,
        $lookup_tables,
        $where_method = "listTableWhere",
        $filter_where_array = array()
    ) {
        $db = $this->read_db;
        $builder = $db->table($table);
        
        if (!$builder->get()) {
            $message = 'The table ' . $this->controller . ' has no relationship with ' . $table . '. Check the ' . $this->controller . '_model detail_tables method';
            throw new \CodeIgniter\Exceptions\PageNotFoundException($message);
        } else {
            $this->runDataTableListQuery($builder, $table, $selected_columns, $lookup_tables, $where_method, $filter_where_array);
            if ($this->request->getPost('draw')) {
                // Limiting Server Datatable Results
                $start = intval($this->request->getPost('start'));
                $length = intval($this->request->getPost('length'));
    
                $builder->limit($length, $start);
                
                // Ordering Server Datatable Results
                $order = $this->request->getPost('order');
                $col = '';
                $dir = 'desc';
    
                if (!empty($order)) {
                    $col = $order[0]['column'];
                    $dir = $order[0]['dir'];
                }
                
                if ($col == '') {
                    $builder->orderBy($table . '_id', 'DESC');
                } else {
                    $builder->orderBy($selected_columns[$col], $dir);
                }
    
                // Searching Server Datatable Results
                $search = $this->request->getPost('search');
                $value = $search['value'];
    
                array_pop($selected_columns);
    
                if (!empty($value)) {
                    $builder->groupStart();
                    $column_key = 0;
                    foreach ($selected_columns as $column) {
                        if ($column_key == 0) {
                            $builder->like($column, $value, 'both');
                        } else {
                            $builder->orLike($column, $value, 'both');
                        }
                        $column_key++;
                    }
                    $builder->groupEnd();
                }
            }
    
            $result_object = $builder->get();
    
            if (!$result_object) {
                clear_cache_files($table);
                return $builder->get()->getResultArray();
            }
            
            $rst = $result_object->getResultArray();
            // log_message('error', json_encode($rst));
            return $rst;
        }
    }
    

    function runListQueryCountAllRecords(
        $table,
        $selected_columns,
        $lookup_tables,
        $model_where_method = "listTableWhere",
        $filter_where_array = array()
    ) {
        $db = $this->read_db; //\Config\Database::connect('read_db');
        $builder = $db->table($table);
    
        if (!$db->tableExists($table)) {
            $message = 'The table ' . $this->controller . ' has no relationship with ' . $table . '. Check the ' . $this->controller . '_model detail_tables method';
            throw new \CodeIgniter\Exceptions\PageNotFoundException($message);
        } else {
            // Call the _run_list_query method to build the query
            $this->runDataTableListQuery($builder, $table, $selected_columns, $lookup_tables, $model_where_method, $filter_where_array);
    
            return $builder->countAllResults();
        }
    }
    

    private function toggleListQueryResults()
    {
        $featureLibrary = $this->libs->loadLibrary($this->controller);

        // Get the tables foreign key relationship
        $lookup_tables = $this->libs::call($this->controller.'.lookupTables');
        // log_message('error', json_encode($lookup_tables));
        // Get result from grants model if feature model list returns empty
        $query_result = $this->listInternalQueryResults($lookup_tables)['selected_results']; // System generated query result
        // log_message('error', json_encode($query_result));
        if (method_exists($featureLibrary, 'list') && !empty($featureLibrary->list())) {
            $feature_model_list_result = $featureLibrary->list();
            if (is_array($feature_model_list_result)) {
                // Allows empty result set
                $query_result = $feature_model_list_result; // A full user defined query result
            }
        }

        // // Implemeting resetting of options if a field is changed from to a select type
        $query_result['selected_results'] = $this->libs->updateQueryResultForFieldsChangedToSelectType($this->controller, $query_result);
        $query_result['total_records'] = $this->listInternalQueryResults($lookup_tables)['total_records'];
        
        return $query_result;
    }

    public function getOutput()
    {

        // This line prevent the timeout issue of the more menu icon. Reason for timeout are unknown
        if ($this->controller == 'menu') {
            return [];
        }

        // Prevent returning complete view if the feature has no schema
        $field_data = service('grantslib')::call('grants.fieldData', [$this->controller]);

        if (empty($field_data)) {
            return [];
        }

        // Mandatory fields for details tables
        $result = $this->toggleListQueryResults()['selected_results'];

        $keys = $this->toggleListSelectColumns();
        $show_add_button = $this->libs::call($this->controller.'.showAddButton', [$this->controller]);

        $columns = $keys;
        array_shift($columns);

        $return = array(
          'keys'=> $keys,
          'columns' => $columns,
          'fields_meta_data'=>$this->libs->fieldsMetaDataTypeAndName($this->controller),
          'total_records'=>  $this->toggleListQueryResults()['total_records'],
          'table_body'=>$result,
          'table_name'=> $this->controller,
          'is_multi_row'=>$this->libs::call($this->controller.'.checkIfTableIsMultiRow'),
          'has_details_table' => $this->libs->checkIfTableHasDetailTable($this->controller),
          'has_details_listing' => $this->libs->checkIfTableHasDetailListing($this->controller),
          'show_add_button'=>$show_add_button,
          'controller' => $this->controller,
        );

        return $return;
    }
}