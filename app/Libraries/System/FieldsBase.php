<?php

namespace App\Libraries\System;

use App\Libraries\System\GrantsLibrary;
use Config\GrantsConfig;

class FieldsBase
{

  private $column;
  

  private $table;

  private $is_header = false;

  private $default_field_value = null;

  private $is_field_required = true;

  private $is_detail_header = false;

//   private $lib;

  private $config;

  function __construct($column, $table, $is_header = false, $is_detail_header = false)
  {

    $this->column = trim($column);

    $this->table = strtolower($table);

    $this->is_header = $is_header;

    $this->is_detail_header = $is_detail_header;

    // Prevent $this->is_header and  $this->is_detail_header to be true at the same time
    if ($is_header == true && $is_detail_header == true) {
      $this->is_header = true;
      $this->is_detail_header = false;
    }

    $this->set_default_field_value();

    $this->is_field_required();

    // $this->lib = new GrantsLibrary();

    $this->config = config(GrantsConfig::class);
  }

  function index()
  {
  }

  function is_field_required()
  {

    //$is_field_required = 1;
    $library = new GrantsLibrary();
    $all_fields = $library->tableFieldsMetadata($this->table);

    $array_of_columns = array_column($all_fields, 'name');
    $array_of_default = array_column($all_fields, 'default');

    $name_default = array_combine($array_of_columns, $array_of_default);

    foreach ($name_default as $field => $default_value) {
      if ($this->column == $field && $default_value != null && (strlen($default_value) > 0 )) {
        $this->is_field_required = false;
        break;
      }
    }

    return $this->is_field_required;
  }

  function field_type()
  {

    $library = new GrantsLibrary();
    $all_fields = $library->tableFieldsMetadata($this->table);

    $array_of_columns = array_column($all_fields, 'name');
    $array_of_types = array_column($all_fields, 'type');

    $name_types = array_combine($array_of_columns, $array_of_types);

    $column_type = 'int';

    $field_type = 'number';

    if (strpos($this->column, '_name') == true  && $this->column !== $this->table . '_name') {
      $this->column = 'fk_' . substr($this->column, 0, -5) . '_id';
    }

    
    if (array_key_exists($this->column, $name_types)) {
      $column_type  = $name_types[$this->column];
  
      if ($column_type == 'int' || $column_type == 'decimal') {

        $field_type = "number";

        // All fields of format fk_xxx_id are select types
        if (strpos($this->column, '_id') == true && substr($this->column, 0, 3) == 'fk_')  {
          $field_type = "select";
        }
      } elseif ($column_type == 'varchar') {
        $field_type = "text";
      } elseif ($column_type == 'date') {
        $field_type = "date";
      } elseif ($column_type == 'longtext') {
        $field_type = "longtext";
      } elseif (strpos($this->column, 'email') == true) {
        $field_type = "email";
      } else {
        $field_type = "text";
        //$field_type = "select";
      }
    }

    return $field_type;
  }

  private function input_fields($value)
  {

    $id = "";
    $name = 'detail[' . $this->column . '][]';
    $master_class = "detail";

    if ($this->is_header) {
      $id = $this->column;
      $name = 'header[' . $this->column . ']';
      $master_class = 'master';
    } elseif ($this->is_detail_header) {
      $id = $this->column;
      $name = 'detail_header[' . $this->table . '][' . $this->column . ']';
      $master_class = 'master';
    }

    $value = ($value == "" && $this->default_field_value !== 0) ? $this->default_field_value : $value;

    return array('id' => $id, 'name' => $name, 'master_class' => $master_class, 'value' => $value);
  }

  function set_default_field_value()
  {

    $lib = new GrantsLibrary();
    $library = $lib->loadLibrary($this->table);
  
    if (method_exists($library, 'defaultFieldValue') && count($library->defaultFieldValue()) > 0) {
      $default_fields_values = $library->defaultFieldValue();
      if (array_key_exists($this->column, $default_fields_values)) {
        $this->default_field_value = $default_fields_values[$this->column];
      }
    }

    return $this->default_field_value;
  }

  function column_max_length()
  {
    $library = new GrantsLibrary();
    $all_fields = $library->tableFieldsMetadata($this->table);

    $array_of_columns = array_column($all_fields, 'name');
    $array_of_max_length = array_column($all_fields, 'max_length');

    $name_max_length = array_combine($array_of_columns, $array_of_max_length);

    return $name_max_length[$this->column];
  }

  function number_field($value = 0)
  {

    extract($this->input_fields($value));

    $maxlength = $this->column_max_length();

    $required = $this->is_field_required ? "required='required'" : '';
    $mask_asterisk_color = $this->is_field_required ? "red" : "green";
    $mask = '<span class="input-group-addon"><i style="color:' . $mask_asterisk_color . '" class="fa fa-asterisk"></i></span>';

    return '<div class="input-group">' . $mask . '<input id="' . $id . '" maxlength="' . $maxlength . '" ' . $required . ' type="number" value="' . $value . '" class="form-control ' . $master_class . ' input_' . $this->table . ' ' . $this->column . '" name="' . $name . '" placeholder="' . get_phrase('enter_' . $this->column) . '" /></div>';
  }

  function text_field($value = "")
  {

    extract($this->input_fields($value));

    $maxlength = $this->column_max_length();

    $required = $this->is_field_required ? "required='required'" : '';
    $mask_asterisk_color = $this->is_field_required ? "red" : "green";
    $mask = '<span class="input-group-addon"><i style="color:' . $mask_asterisk_color . '" class="fa fa-asterisk"></i></span>';

    return '<div class="input-group">' . $mask . '<input id="' . $id . '" maxlength="' . $maxlength . '" value="' . $value . '" ' . $required . ' type="text" class="form-control ' . $master_class . ' input_' . $this->table . ' ' . $this->column . '" name="' . $name . '" placeholder="' . get_phrase('enter_' . $this->column) . '" /></div>';
  }

  function email_field($value = "")
  {

    extract($this->input_fields($value));

    $required = $this->is_field_required ? "required='required'" : '';
    $mask_asterisk_color = $this->is_field_required ? "red" : "green";
    $mask = '<span class="input-group-addon"><i style="color:' . $mask_asterisk_color . '" class="fa fa-asterisk"></i></span>';

    return '<div class="input-group">' . $mask . '<input id="' . $id . '" value="' . $value . '" ' . $required . ' type="email" class="form-control ' . $master_class . ' input_' . $this->table . ' ' . $this->column . '" name="' . $name . '" placeholder="' . get_phrase('enter_' . $this->column) . '" /></div>';
  }

  function password_field($value = "")
  {

    extract($this->input_fields($value));

    $required = $this->is_field_required ? "required='required'" : '';
    $mask_asterisk_color = $this->is_field_required ? "red" : "green";
    $mask = '<span class="input-group-addon"><i style="color:' . $mask_asterisk_color . '" class="fa fa-asterisk"></i></span>';

    return '<div class="input-group">' . $mask . '<input id="' . $id . '" value="' . $value . '" ' . $required . '  type="password" class="form-control ' . $master_class . ' input_' . $this->table . ' ' . $this->column . '" name="' . $name . '" placeholder="' . get_phrase('enter_' . $this->column) . '" /></div>';
  }

  function longtext_field($value = "")
  {

    extract($this->input_fields($value));

    $required = $this->is_field_required ? "required='required'" : '';
    $mask_asterisk_color = $this->is_field_required ? "red" : "green";
    $mask = '<span class="input-group-addon"><i style="color:' . $mask_asterisk_color . '" class="fa fa-asterisk"></i></span>';

    return '<div class="input-group">' . $mask . '<textarea id="' . $id . '" ' . $required . ' class="form-control ' . $master_class . ' input_' . $this->table . ' ' . $this->column . ' " name="' . $name . '" placeholder="' . get_phrase('enter_' . $this->column) . '" >' . $value . '</textarea></div>';
  }

  function date_field($value = "")
  {

    extract($this->input_fields($value));

    $value = $value == "0000-00-00" || $value == ''|| $value == NULL ? '' : $value;

    $required = $this->is_field_required ? "required='required'" : '';
    $mask_asterisk_color = $this->is_field_required ? "red" : "green";
    $mask = '<span id = "span-'. $id .'" class="input-group-addon date_check"><i style="color:' . $mask_asterisk_color . '" class="fa fa-asterisk"></i></span>';
    $field = '<div class = "row"><div class="col-xs-11">';
    $field .=  '<div class="input-group">' . $mask . '<input onkeydown="return false" id="' . $id . '" value="'.$value.'" data-format="yyyy-mm-dd" ' . $required . '  type="text" class="form-control ' . $master_class . ' datepicker input_' . $this->table . ' ' . $this->column . '" name="' . $name . '" placeholder="' . get_phrase('enter_' . $this->column) . '" /></div>';
    $field .= '</div>';
    $field .= '<div class ="col-xs-1"><i style="cursor:pointer;" class="fa fa-refresh reset_date_selection"></i></div></div>';
    return $field;
  }

  function select_field($options, $selected_option = 0, $show_only_selected_value = false, $onchange_function_name = '', $multi_select_field = '')
  {
    if ($onchange_function_name == '') {
      $onchange_function_name =  'onchange_' . $this->column;
    }

    $id = "";

    $column_placeholder = $this->column;

    if (substr($this->column, 0, 3) == 'fk_') {
      $column_placeholder = substr($this->column, 3, -3);
    }

    $name = 'detail[' . $this->column . '][]';
    $master_class = "detail";

    $multiple = "";
    $hide_select_label = "";

    if ($this->is_header) {
      $id = $this->column;
      $name = 'header[' . $this->column . ']';
      $master_class = 'master';

      if ($multi_select_field != "" && 'fk_' . $multi_select_field . '_id' == $this->column) {
        $multiple = "multiple='multiple'";
        $hide_select_label = "hidden";
        $name = 'header[' . $this->column . '][]';
      }
    } elseif ($this->is_detail_header) {
      $id = $this->column;
      $name = 'detail_header[' . $this->table . '][' . $this->column . ']';
      $master_class = 'master';

      if ($multi_select_field != "" && 'fk_' . $multi_select_field . '_id' == $this->column) {
        $multiple = "multiple='multiple'";
        $hide_select_label = "hidden";
        $name = 'detail_header[' . $this->table . '][' . $this->column . '][]';
      }
    }

    $this->set_default_field_value();

    $selected_option = ($selected_option == "" && $this->default_field_value !== 0) ? $this->default_field_value : $selected_option;
    $select2 = service("settings")->get("GrantsConfig.use_select2_plugin") ? 'select2 select2-offscreen' : 'no-select';

    $required = $this->is_field_required ? "required='required'" : '';
    $mask_asterisk_color = $this->is_field_required ? "red" : "green";
    $mask = '<span class="input-group-addon"><i style="color:' . $mask_asterisk_color . '" class="fa fa-asterisk"></i></span>';
    //" . get_phrase('select_' . $column_placeholder) . "
    $select =  '<div class="input-group">' . $mask . "<select onchange='" . $onchange_function_name . "(this)' id='" . $id . "' name='" . $name . "' class='form-control " . $master_class . " input_" . $this->table . " " . $this->column . " " . $select2 . "' $required " . $multiple . ">
            <option class='" . $hide_select_label . "' value=''>".get_phrase('select_records')."</option>";
    
    if (is_array($options) && count($options) > 0) {
      foreach ($options as $option_value => $option_html) {
        $selected = "";

        if ($show_only_selected_value && ($option_value != $selected_option)) {
          continue;
        }

        if ($option_value == $selected_option) {
          $selected = "selected='selected'";
        }
        $select .= "<option value='" . $option_value . "' " . $selected . ">" . ucwords(str_replace("_", " ", $option_html)) . "</option>";
      }
    }

    $select .= "</select></div>";

    $select .= "<script>function " . $onchange_function_name . "(elem){}</script>";

    return $select;
  }
}
