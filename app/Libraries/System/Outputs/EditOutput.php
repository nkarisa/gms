<?php 

namespace App\Libraries\System\Outputs;

class EditOutput extends OutputTemplate{

    function __construct($module){
        parent::__construct($module);
    }

    function editQuery($table)
    {
  
      $keys = $this->libs->checkEditVisibleColumns($table);
      $edit_query = array();
  
      foreach ($keys as $column => $value) {
        // Remove approval and Status fields  
        if ($column == 'fk_approval_id' || $column == 'fk_status_id') continue;

        if (preg_match("/_id$/", $column) && $column !== strtolower($table) . '_id') {
          $edit_query[substr($column, 0, -3) . '_name'] = $value;
        } else {
          $edit_query[$column] = $value;
        }
      }
      
      return $edit_query;
    }

    function editFormFields(array $visible_columns_array): array
    {
        $fields = [];

        foreach ($visible_columns_array as $column => $value) {
            $fields[$column] = $this->libs->headerRowField($column, $value);
        }

        return $fields;
    }

    function getOutput($id): array|\CodeIgniter\HTTP\Response {
        $table = $this->controller;

        if ($this->request->getPost()) {
            return $this->currentLibrary->edit($id);

        } else {    
          $edit_query = $this->editQuery($table);
          $fields = $this->editFormFields($edit_query); 
          return array(
            'fields' => $fields
          );
        }
    }
}