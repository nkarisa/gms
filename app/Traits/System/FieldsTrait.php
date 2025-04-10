<?php 
namespace App\Traits\System;

use App\Libraries\System\FieldsBase;

trait FieldsTrait {
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

  function getTypeNameById($type, $type_id = '', $field = '')
  {
    $field = $field == '' ? $type . '_name' : $field;
    $builder = $this->read_db->table($type);
    $queryResult = $builder->getWhere( array($type . '_id' => $type_id));
    if ($queryResult->getNumRows() > 0) {
      return $queryResult->getRow()->$field;
    } else {
      return "";
    }
  }

 

  function selectField($column, $options)
  {
    $field = new FieldsBase($column, $this->controller, true);
    return $field->select_field($options);
  }

  function emailField($field_name)
  {
    $field = new FieldsBase($field_name, $this->controller, true);
    return $field->email_field();
  }

  function textField($field_name)
  {
    $field = new FieldsBase($field_name, $this->controller, true);
    return $field->text_field();
  }

  function passwordField($field_name)
  {
    $field = new FieldsBase($field_name, $this->controller, true);
    return $field->password_field();
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

   function headerRowField(string $column, ?string $field_value = null, bool $show_only_selected_value = false, $detail_table = ''): string
   {
 
     $f = new FieldsBase($column, $this->controller, true);
 
     if ($detail_table != '') {
       $f = new FieldsBase($column, $detail_table, false, true);
     }
 
     $this->setChangeFieldType();
     $field_type = $f->field_type();
     $field = $field_type . "_field";
 
     if (array_key_exists($column, $this->set_field_type)) {
       $field_type = $this->set_field_type[$column]['field_type'];
       $select2 = isset($this->set_field_type[$column]['select2']) && $this->set_field_type[$column]['select2'] ? $this->set_field_type[$column]['select2'] : false;
       $field = $field_type . "_field";
 
       if ($field_type == 'select' && count($this->set_field_type[$column]['options']) > 0) {
          if($select2){
            return $f->select_field($this->set_field_type[$column]['options'], $field_value, false, '', $column);
          }
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

}