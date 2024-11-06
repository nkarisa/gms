<?php

namespace App\Libraries\System\Outputs;

class ViewOutput extends OutputTemplate
{

    protected $currentModel = null;
    function __construct($module)
    {
        parent::__construct($module);
    }

    /**
     * feature_model_master_table_visible_columns
     * 
     * Returns an array of selected fields in the master part of the master-detail view action pages from 
     * the feature model is specified
     * @return Array
     */
    function featureModelMasterTableVisibleColumns(): array
    {

        $master_table_visible_columns = [];

        if (
            method_exists($this->currentLibrary, 'masterTableVisibleColumns') &&
            is_array($this->currentLibrary->masterTableVisibleColumns()) &&
            count($this->currentLibrary->masterTableVisibleColumns()) > 0
        ) {

            $master_table_visible_columns = $this->currentLibrary->masterTableVisibleColumns();

            //Add the table id columns if does not exist in $columns
            if (
                is_array($master_table_visible_columns) &&
                !in_array(
                    $this->libs->primaryKeyField($this->controller),
                    $master_table_visible_columns
                )
            ) {
                array_unshift(
                    $master_table_visible_columns,
                    $this->libs->primaryKeyField($this->controller)
                );
            }

        }

        return $master_table_visible_columns;
    }

    /**
     * add_lookup_name_fields_to_visible_columns
     * 
     * This method adds name columns of the look up tables to the selected columns
     * 
     * @param Array $visible_columns - Selected columns
     * @param Array $lookup_table - Look up tables
     * 
     * @return Array - Update visible columns array
     */
    function addLookupNameFieldsToVisibleColumns(array $visible_columns, array $lookup_tables): array
    {
        foreach ($lookup_tables as $lookup_table) {

            $lookup_table_columns = $this->libs->getAllTableFields($lookup_table);

            foreach ($lookup_table_columns as $lookup_table_column) {
                // Only include the name field of the look up table in the select columns
                if (
                    $this->libs->isPrimaryKeyField($lookup_table, $lookup_table_column) ||
                    $this->libs->isNameField($lookup_table, $lookup_table_column)
                ) {
                    array_push($visible_columns, $lookup_table_column);

                }

            }
        }

        return $visible_columns;
    }

    /**
     * insert_history_tracking_fields_to_master_view
     * 
     * This method inserts the created by and last modified by columns if not found in the selected columns
     * 
     * @param array $visible_columns - Selected columns
     * @return array - Update selected columns
     */
    function insertHistoryTrackingFieldsToMasterView(array $visible_columns): array
    {
        if (
            !in_array($this->libs->historyTrackingField($this->controller, 'created_by'), $visible_columns) ||
            !in_array($this->libs->historyTrackingField($this->controller, 'last_modified_by'), $visible_columns)

        ) {
            array_push(
                $visible_columns,
                $this->libs->historyTrackingField($this->controller, 'created_by'),
                $this->libs->historyTrackingField($this->controller, 'last_modified_by')
            );
        }

        return $visible_columns;
    }


    /**
     * insert_status_column_to_master_view
     * 
     * Inserts a status name column if doesn't exist in the selceted/visible columns.
     * This is only done to tables other than approval and the status tbale should be listed
     * as a lookup table in the feature model
     * 
     * @param array $visible_columns - Original selected columns
     * 
     * @return array - Update selected columns array for the master view
     */
    function insertStatusColumnToMasterView(array $visible_columns): array
    {

        $status_name_field = $this->libs->nameField('status');

        if ($this->controller !== "approval") {
            if (
                in_array('status', $this->libs->lookupTables($this->controller)) &&
                !in_array($status_name_field, $visible_columns)
            ) {
                array_push($visible_columns, $status_name_field);
            }
        }

        return $visible_columns;
    }

    /**
     * toggle_master_view_select_columns
     * 
     * This method creates an array of selected columns to be used in the master_view method in this model.
     * The master_view method of this model is used to implement the grants master_view method which finally feeds
     * to the view_output method.
     * 
     * This methods utilizes a feature model wrapper method master_table_visible_columns from grant library which
     * checks if the feature model has specified columns to be used in the query of the master table of a view action page
     * or If not specified, it uses all fields from the selected table, ensuring that the foreign keys in this case are unset
     * In both cases above, it ensures that the name fields of the lookup tables are added to this array
     * 
     * It finally implements the fields access permission checks and returns the final array
     * 
     * @return array - Select columns
     */
    private function toggleMasterViewSelectColumns()
    {

        // Check if the table has list_table_visible_columns not empty
        $master_table_visible_columns = $this->featureModelMasterTableVisibleColumns();
        $lookup_tables = $this->libs->lookupTables();

        $get_all_table_fields = $this->libs->getAllTableFields();

        foreach ($get_all_table_fields as $get_all_table_field) {
            //Unset foreign keys columns
            if (substr($get_all_table_field, 0, 3) == 'fk_') {
                unset($get_all_table_fields[array_search($get_all_table_field, $get_all_table_fields)]);
            }
        }

        $visible_columns = $get_all_table_fields;
        // $lookup_columns = array();

        if (is_array($master_table_visible_columns) && count($master_table_visible_columns) > 0) {
            $visible_columns = $master_table_visible_columns;

            if (is_array($lookup_tables) && count($lookup_tables) > 0) {
                foreach ($lookup_tables as $lookup_table) {

                    // Add primary_keys for the lookup tables in the visible columns array
                    // table_fields_metadata
                    $lookup_table_fields_data = $this->libs->tableFieldsMetadata($lookup_table);

                    foreach ($lookup_table_fields_data as $field_data) {
                        if ($field_data['primary_key'] == 1) {
                            array_push($visible_columns, $field_data['name']);
                        }
                    }
                }
            }

        } elseif (is_array($lookup_tables) && count($lookup_tables) > 0) {
            $visible_columns = $this->addLookupNameFieldsToVisibleColumns($visible_columns, $lookup_tables);
        }

        // Add created_by and last_modified_by fields if not exists in columns selected insert_history_tracking_fields_to_master_view
        $history_tracking_fields = $this->insertHistoryTrackingFieldsToMasterView($visible_columns);

        //Check if controller is not approval and find if status field is present and it has status in the lookup table
        $status_column = $this->insertStatusColumnToMasterView($history_tracking_fields);

        // Unset deleted at field
        $unset_fields = [$this->libs->historyTrackingField($this->controller, 'deleted_at')];
        $this->libs->defaultUnsetColumns($status_column, $unset_fields);

        //Remove the primary key field from the master table
        unset($status_column[array_search($this->libs->primaryKeyField($this->controller), $status_column)]);

        $accessLibrary = new \App\Libraries\System\AccessBaseLibrary();
        return $accessLibrary->controlColumnVisibility($this->controller, $status_column, 'read');

    }

    /**
     * master_view_query_result
     * 
     * This method returns internal database query results. Its used when the feature model did not
     * run a model specific query.
     * 
     * @return array - Database query result 
     */
    function masterViewQueryResult(): array
    {

        $table = strtolower($this->controller);

        $select_columns = $this->toggleMasterViewSelectColumns();

        if ($this->controller == 'status') {
            array_push($select_columns, $table . '.status_id');
        } else {
            array_push($select_columns, $table . '.fk_status_id as status_id');
        }

        $lookup_tables = $this->libs->lookupTables($table);
        //runMasterViewQuery
        $master_view_query_result = $this->libs->runMasterViewQuery($table, $select_columns, $lookup_tables);

        return $this->libs->updateQueryResultForFieldsChangedToSelectType($this->controller, $master_view_query_result);

    }


    /**
     * toggle_master_view_query_result
     * 
     * This method checks if the feature model has query result for the selected record or if it misses
     * gets it from the internal grants model
     * 
     * @todo the "master_view" method in the feature specific models to be renamed to "master_view_feature_model_query_result"
     * 
     * @return array
     *  
     */

    function toggleMasterViewQueryResult(): array
    {

        $master_view = $this->masterViewQueryResult();

        // Get result from grants model if feature model list returns empty

        if (
            method_exists($this->currentLibrary, 'masterView') &&
            is_array($this->currentLibrary->masterView()) &&
            count($this->currentLibrary->masterView()) > 0
        ) {

            $master_view = $this->currentLibrary->masterView();

        }

        return $master_view;
    }

    //   function currencyConversion(&$query_output,$table_name = ""){

    //     $table_name = $table_name == ""?$this->controller:$table_name;

    //     if(method_exists($this->CI->{$table_name.'_model'},'currency_fields') && 
//         is_array($this->CI->{$table_name.'_model'}->currency_fields()) && 
//             count($this->CI->{$table_name.'_model'}->currency_fields()) > 0){

    //                 $currency_fields = $this->CI->{$table_name.'_model'}->currency_fields();

    //                 foreach($currency_fields as $currency_field){
//                     if(isset($query_output[$currency_field])){

    //                         $id = hash_id($this->CI->id,'decode');

    //                         $rate = currency_conversion($this->CI->grants_model->get_record_office_id($this->CI->controller,$id));

    //                         //$query_output[$currency_field] = $query_output[$currency_field] * $rate;
//                         $query_output[$currency_field] = $query_output[$currency_field] * $rate;
//                     }

    //                 }
//     }

    //     return $query_output;
// }


    /**
     * feature_model_detail_list_table_visible_columns
     * 
     * Returns an array of columns to be selected in a listing table in a master-detail view action page
     * 
     * @param string $table : Selected detail table
     * 
     * @return array
     */
    function featureModelDetailListTableVisibleColumns(string $table)
    {

        $library = $this->libs->loadLibrary($table);

        $detail_list_table_visible_columns = [];

        if (
            method_exists($library, 'detailListTableVisibleColumns') &&
            is_array($library->detailListTableVisibleColumns()) && count($library->detailListTableVisibleColumns()) > 0
        ) {
            $detail_list_table_visible_columns = $library->detailListTableVisibleColumns();

            //Add the table id columns if does not exist in $columns
            if (
                is_array($detail_list_table_visible_columns) &&
                !in_array($this->libs->primaryKeyField($table), $detail_list_table_visible_columns)
            ) {
                array_unshift($detail_list_table_visible_columns, $this->libs->primaryKeyField($table));
            }

            if ($table != 'status') {
                array_push($detail_list_table_visible_columns, $table . '.fk_status_id as status_id');
            } else {
                array_push($detail_list_table_visible_columns, $table . '.status_id');
            }


            //Remove status and approval columns if the approveable item is not approveable
            $approveItemLibary = new \App\Libraries\Core\ApproveItemLibrary();
            if (!$approveItemLibary->approveableItem($table)) {

                $cols = ['status_name', 'approval_name'];

                if ($table == 'status') {
                    $cols = ['approval_name'];
                }

                $this->libs->removeMandatoryLookupTables($detail_list_table_visible_columns, $cols);
            } else {
                $this->libs->addMandatoryLookupTables($detail_list_table_visible_columns, ['status_name', 'approval_name']);
            }

        }

        return $detail_list_table_visible_columns;
    }

    /**
     * toggle_detail_list_select_columns
     * 
     * It checks if the feature model select columns for detail lists have been defined or else
     * uses the fields of the detail table with created_by, last_modified_by and deleted_at fields 
     * unset.
     * 
     * @param string $table - Passed table name
     * @return array - Columns to select
     */
    function toggleDetailListSelectColumns($table): array
    {
        // Check if the table has list_table_visible_columns not empty
        $detail_list_table_visible_columns = $this->featureModelDetailListTableVisibleColumns($table);
        
        //Table lookup tables
        $lookup_tables = $this->libs->callbackLookupTables($table);

        $get_all_table_fields = $this->libs->getAllTableFields($table);

        // Replace the list visible columns if the current controller is approval            
        $library = $this->libs->loadLibrary($table);

        // Removing approval_id field in the select fields
        if ($this->controller == 'approval') {
            if (
                is_array($detail_list_table_visible_columns) &&
                count($detail_list_table_visible_columns) > 0
            ) {
                array_unshift($detail_list_table_visible_columns, $this->libs->primaryKeyField($table));
            }

        }

        // Unset history fields
        foreach ($get_all_table_fields as $get_all_table_field) {

            //Unset foreign keys columns, created_by and last_modified_by columns

            if (
                substr($get_all_table_field, 0, 3) == 'fk_' ||
                $this->libs->isHistoryTrackingField($table, $get_all_table_field, 'created_by') ||
                $this->libs->isHistoryTrackingField($table, $get_all_table_field, 'last_modified_by') ||
                $this->libs->isHistoryTrackingField($table, $get_all_table_field, 'deleted_at')
            ) {
                unset($get_all_table_fields[array_search($get_all_table_field, $get_all_table_fields)]);
            }

        }

        $visible_columns = $get_all_table_fields;

        if ($table == 'status') {
            array_push($visible_columns, $table . '.status_id');
        } else {
            array_push($visible_columns, $table . '.fk_status_id as status_id');
        }

        if (is_array($detail_list_table_visible_columns) && count($detail_list_table_visible_columns) > 0) {
            $visible_columns = $detail_list_table_visible_columns;
            //print_r($visible_columns);exit;
        } else {
            if (is_array($lookup_tables) && count($lookup_tables) > 0) {
                foreach ($lookup_tables as $lookup_table) {


                    $lookup_table_columns = $this->libs->getAllTableFields($lookup_table);

                    foreach ($lookup_table_columns as $lookup_table_column) {
                        // Only include the name field of the look up table in the select columns
                        if ($this->libs->isNameField($lookup_table, $lookup_table_column)) {
                            array_push($visible_columns, $lookup_table . '_id');
                            array_push($visible_columns, $lookup_table_column);
                        }

                    }
                }
            }
        }


        $accessLibrary = new \App\Libraries\System\AccessBaseLibrary();
        return $accessLibrary->controlColumnVisibility($table, $visible_columns, 'read');

    }

    // function detailListInternalQueryResult($table)
    // {
    //     $lookup_tables = $this->libs->callbackLookupTables($table);

    //     $select_columns = $this->toggleDetailListSelectColumns(table: $table);

    //     $filter_where = array($table . '.fk_' . strtolower($this->controller) . '_id' => hash_id($this->uri->getSegment(3, 0), 'decode'));
    //     //print_r($filter_where);exit;
    //     $runListQueryResult = $this->libs->runListQuery($table, $select_columns, $lookup_tables, 'detailListTableWhere', $filter_where);

    //     return $runListQueryResult;
    // }

    /**
     * detail_list_query
     * 
     * This is query result of the detail table. The result of this method will be used in the view_output
     * to create the detail list
     * 
     * @param $table String : The selected table
     * 
     * @return array
     * 
     */
    // function toggleDetailListQuery(string $table): array
    // {
    //     $library = $this->libs->loadLibrary($table);

    //     $detail_list_query = $this->detailListInternalQueryResult($table); // System generated query result

    //     if (
    //         method_exists($library, 'detailListQuery') &&
    //         is_array($library->detailListQuery($table)) &&
    //         count($library->detailListQuery($table)) > 0
    //     ) {
    //         $detail_list_query = $library->detailListQuery($table); // A full user defined query result
    //     }

    //     $detail_list_query = $this->libs->updateQueryResultForFieldsChangedToSelectType($table, $detail_list_query);


    //     return $detail_list_query;
    // }
    /**
     * detail_list_view
     * 
     * This method creates an array to be used in the view_output. It used to construct the table array_result
     * of each detail table
     * 
     * @param $table String : Selected table
     * 
     * @return array
     * 
     */
    function detailListOutput(string $table): array
    {

        // Query result of the detail table
        // $result = $this->toggleDetailListQuery($table);

        // Selected column of the detail table
        $keys = $this->toggleDetailListSelectColumns($table);

        // Check if the detail table has also other detail tables. 
        // It makes its track number a link in the view if true
        $has_details = $this->libs->checkIfTableHasDetailTable($table);

        // It check if the detail table is approveable so as to show the approval links in the status action
        $approveItemLibrary = new \App\Libraries\Core\ApproveItemLibrary();
        $is_approveable_item = $approveItemLibrary->approveableItem($table);

        // Check if the add button is allowed to be shown
        $show_add_button = $this->libs->showAddButton($table);

        // Checks if the detail table has a detail table to it
        $has_details_listing = $this->libs->checkIfTableHasDetailListing($table);

        return array(
            'keys' => $keys,
            // 'table_body' => $result, Uses the listOutPut function
            'table_name' => $table,
            'has_details_table' => $has_details,
            'has_details_listing' => $has_details_listing,
            'is_approveable_item' => $is_approveable_item,
            'show_add_button' => $show_add_button
        );
    }


    function currencyConversion(&$query_output,$table_name = ""){
    
        $table_name = $table_name == "" ? $this->controller : $table_name;
    
        $library = $this->libs->loadLibrary($table_name );
        $officeLibrary = new \App\Libraries\Core\OfficeLibrary();

        if(method_exists($library,'currencyFields') && 
            is_array($library->currencyFields()) && 
                count($library->currencyFields()) > 0){
                
                    $currency_fields = $library->currencyFields();
    
                    foreach($currency_fields as $currency_field){
                        if(isset($query_output[$currency_field])){
                            
                            $id = hash_id($this->id,'decode');
                            
                            $rate = currency_conversion($officeLibrary->getRecordOfficeId($this->controller,$id));
    
                            //$query_output[$currency_field] = $query_output[$currency_field] * $rate;
                            $query_output[$currency_field] = $query_output[$currency_field] * $rate;
                        }
                        
                    }
        }
    
        return $query_output;
    } 

//     private function detailtoggleListSelectColumns($table_name): array
//   {
//     // Check if the table has list_table_visible_columns not empty
//     $list_table_visible_columns = $this->featureModelDetailListTableVisibleColumns($table_name);
//     $lookup_tables = $this->libs::call($this->controller.'.lookupTables');

//     $get_all_table_fields = $this->libs->getAllTableFields();

//     foreach ($get_all_table_fields as $get_all_table_field) {

//       //Unset foreign keys columns, created_by and last_modified_by columns

//       if (
//         substr($get_all_table_field, 0, 3) == 'fk_' ||
//         $this->libs->isHistoryTrackingField($this->controller, $get_all_table_field, 'created_by') ||
//         $this->libs->isHistoryTrackingField($this->controller, $get_all_table_field, 'last_modified_by') ||
//         $this->libs->isHistoryTrackingField($this->controller, $get_all_table_field, 'deleted_at')
//       ) {

//         unset($get_all_table_fields[array_search($get_all_table_field, $get_all_table_fields)]);
//       }
//     }

//     $visible_columns = $get_all_table_fields;

//     if (is_array($list_table_visible_columns) && count($list_table_visible_columns) > 1) {
//       $visible_columns = $list_table_visible_columns;
//     } else {
//       if (is_array($lookup_tables) && count($lookup_tables) > 0) {
//         foreach ($lookup_tables as $lookup_table) {

//           $lookup_table_columns = $this->libs->getAllTableFields($lookup_table);

//           foreach ($lookup_table_columns as $lookup_table_column) {
//             // Only include the name field of the look up table in the select columns
//             if ($this->libs->isNameField($lookup_table, $lookup_table_column)) {
//               array_push($visible_columns, $lookup_table_column);
//             }
//           }
//         }
//       }
//     }
//     return $visible_columns; //$this->CI->access->control_column_visibility($this->controller,$visible_columns,'read');
//   }

    function getOutput($id): array
    {

        // Use the $id instead of the getSegment(3) in the future code to allow calling this method without routed request

        $table = $this->controller;
        $master_additional_fields = [];

        if (method_exists($this->currentLibrary, 'masterTableAdditionalFields')) {
            $master_additional_fields = $this->currentLibrary->masterTableAdditionalFields();
        }

        $query_output = $this->toggleMasterViewQueryResult();
        $keys = $this->toggleMasterViewSelectColumns();

        if (is_array($master_additional_fields) && count($master_additional_fields) > 0) {
            $query_output = array_merge($query_output, $master_additional_fields);
            array_push($keys, implode(',', array_keys($master_additional_fields)));
        }

        // Apply currency conversion
        $approveItemLibrary = new \App\Libraries\Core\ApproveItemLibrary();
        $has_details = $this->libs->checkIfTableHasDetailTable($table);
        $is_approveable_item = $approveItemLibrary->approveableItem($table);

        $look_tables_name_fields = $this->libs->tablesNameFields(
            $this->libs->lookupTables()
        );

        $result['master'] = array(
            'keys' => $keys,
            'table_body' => $query_output,
            'table_name' => $table,
            'has_details_table' => $has_details,
            'is_approveable_item' => $is_approveable_item,
            'lookup_name_fields' => $look_tables_name_fields,
            'action_labels' => $this->libs->actionLabels($table, hash_id($this->uri->getSegment(3, 0), 'decode'))
        );

        $detail_tables = $this->libs->checkDetailTables($table);

        $result['detail'] = [];

        if ($has_details) {
            $detail = array();
            foreach ($detail_tables as $detail_table) {
                // $detail[$detail_table]['keys'] = $this->toggleDetailListSelectColumns($detail_table);
                $detail[$detail_table] = $this->detailListOutput($detail_table);
                $detail[$detail_table]['fields_meta_data'] = $this->libs->fieldsMetaDataTypeAndName($detail_table);
                $detail[$detail_table]['is_multi_row'] = $this->libs->checkIfTableIsMultiRow($detail_table);
            }

            $result['detail'] = $detail;
        }

        return $result;
    }
}