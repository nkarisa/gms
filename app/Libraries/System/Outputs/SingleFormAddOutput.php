<?php 

namespace App\Libraries\System\Outputs;

class SingleFormAddOutput extends OutputTemplate{
    function __construct($module){
        parent::__construct($module);
    }


    /**
   * add_form_fields
   * 
   * This method builds the add form (single form add or multi form add - master part) fields. 
   * It builds the columns names as keys anf the field html as the value in an associative array
   * 
   * @param $visible_columns_array Array : Columns to be selected
   * 
   * @return array
   */
  private function addFormFields(array $visible_columns_array): array
  {
    $fields = [];
        
    foreach ($visible_columns_array as $table_name => $column) { // Some table names can be 0, 1, 3 for single_form_add_visible_columns or defined names for detail_tables_single_form_add_visible_columns
      $field_value = '';
      $show_only_selected_value = false;

      if (!is_array($column)) {
        // Used to set the default select value in a single_form_add name fields if the form has been opened from a parent record
        if ($this->id != null  && hash_id($this->id, 'decode') > 0 && $column == $this->subAction . '_name') {
          $field_value = hash_id($this->id, 'decode');
          $show_only_selected_value = true;
        }
        
        $fields[$column] = $this->libs->headerRowField($column, $field_value, $show_only_selected_value);
      } else {

        $detail_table = '';

        if (!is_numeric($table_name)) {
          $detail_table = $table_name;
        }

        foreach ($column as $detail_column) {
          $fields[$detail_column] = $this->libs->headerRowField($detail_column, $field_value, $show_only_selected_value, $detail_table);
        }
      }
    }

    return $fields;
  }

  public function getOutput($args): array|\CodeIgniter\HTTP\Response {

      $table = $this->controller;
      // Insert appove item, approval  flow and status record if either in not existing
      $this->libs->tableSetup(strtolower($table));
  
      if ($this->request->getPost()) {
          $library = $this->libs->loadLibrary($table);
          // We use feature library since to allow overriding the grants library add method
          $response = $library->add();
          return $response;
      } else {
        // Adds mandatory fields if not present in the current table
        $visible_columns = $this->libs->checkSingleFormAddVisibleColumns();      
        $visible_columns = array_merge($visible_columns, $this->currentLibrary->detailTablesSingleFormAddVisibleColumns()); // To be tested more of its impact if numbed
        
        $fields = $this->addFormFields($visible_columns); 
        
        return compact('fields');
      }  
    }

}