<?php 

use Config\GrantsConfig;

if ( ! function_exists('form_group')){
    function form_group(...$form_group_contents){

        // $config = config(GrantsConfig::class);

        $master_table_columns = service("settings")->get("GrantsConfig.master_table_columns");

        $chunked_form_group_contents = array_chunk($form_group_contents,$master_table_columns);
        
        $form_group = "";
        $cnt = 0;
        foreach($chunked_form_group_contents as $row){
            if($row[$cnt] == ''){
             continue;
            }
            $form_group .= "<div class='form-group'>";

            foreach($row as $form_group_content){
                $form_group .= $form_group_content;
            }
            
            $form_group .= "</div>";
        }

        return $form_group;
    }
}   

if ( ! function_exists('form_group_context')){
    function form_group_content_input($input, $is_form_control){
      if($is_form_control){
        return $input;
      }else{
        return "<div style='margin-top:8px;'>".$input."</div>";
      }
    }
}        

if ( ! function_exists('form_group_content')){
    function form_group_content(String $label_text, String $form_control,String $form_control_div_id = "", Array $form_control_div_classes = []){
        $label_size = 0;
        $form_control_div_size = 0;
        $cols = [];

        // $config = config(GrantsConfig::class);
        
        $column_size = service("settings")->get("GrantsConfig.master_table_columns");

        switch($column_size){
            case 1:
                $cols = ['label_size'=>2,'form_control_div_size'=>10];
                break;
            
            case 2:
                $cols = ['label_size'=>2,'form_control_div_size'=>4];
                break;

            case 3: 
                $cols = ['label_size'=>2,'form_control_div_size'=>1];
                break;
            
            default:
                $cols = ['label_size'=>2,'form_control_div_size'=>4];
        }

        $label = "<label class='control-label col-xs-".$cols['label_size']."'>".$label_text."</label>";

        $form_control_div = "<div class='col-xs-".$cols['form_control_div_size']." ".implode(' ',$form_control_div_classes)."' id='".$form_control_div_id."'>".$form_control."</div>";

        $form_group_context = $label.$form_control_div;

        return $form_group_context;
    }
          
}


if ( ! function_exists('select_element')){
    function select_element(Array $elements, String $placeholder,Array $classes = array(), String $id = ''){
            $select = "<select id='".$id."' class='form-control ".implode(' ',$classes)."'>
                    <option>".get_phrase('select_'.$placeholder)."</option>";
                    
                    foreach($elements as $element_key=>$element_value){
                        $select .= "<option value='".$element_key."'>".ucfirst($element_value)."</option>";    
                    }
    
            $select .= "</select>";

        return $select;          
    }
          
}

if ( ! function_exists('div_element')){
    function div_element($content = array(), $classes = array(), $id = ''){
        $div = "<div id='".$id."' class='".implode(' ',$classes)."'>";
            foreach($content as $elem){
                $div .= $elem;
            }
        $div .= "</div>";

        return $div;
    }
}

if ( ! function_exists('label_element')){
    function label_element($content, $classes = array(), $id = ''){
        return "<label id='".$id."' class='control-label ".implode(' ',$classes)."'>".$content."</label>";
    }
}

if ( ! function_exists('input_element')){
    function input_element(String $value = '', String $placeholder = '', Array $classes = array(), String $id = '', Array $parameters = array()){

        $_vars = '';

        foreach($parameters as $vars_key=>$vals){
            $_vars .= $vars_key.'='.$vals.' ';
        }

        return "<input type='text' placeholder='".$placeholder."' value='".$value."' ".$_vars." id='".$id."' class='form-control ".implode(' ',$classes)."' />";
    }
}

