<?php 

namespace App\Libraries\System;

class Element{
    
    function row(array $cols){
      $row = "<div class='row'>";
      
      foreach($cols as $col){
        $row .= $col;
      }
  
      $row .= "</div>";
  
      return $row;
    } 
  
    function col(String $col_html, int $size = 12, String $px = 'xs'){
      $col = "<div class='col-".$px."-".$size."'>".$col_html."</div>";
      return $col;
    }
  
    function panel(String $title, String $panel_contents, String $color = 'default', int $collapsed = 0){
      $panel = "<div class='panel panel-".$color."' data-collapsed='".$collapsed."'>";
      $panel .= $this->panel_head($title);
      $panel .= $this->panel_body($panel_contents);
      $panel .= "</div>";
  
      return $panel;
    }
  
    function panel_body(String $panel_body_html){
      $panel_body = "<div class='panel-body'>";
      $panel_body .= $panel_body_html;
      $panel_body .= "</div>";
  
      return $panel_body;
    }

    function widgets(){
      $position = '<div id = "widget_holder" class="col-xs-12" style = "margin-bottom:30px;text-align:center;">';
      $position .= \App\Libraries\System\Widgets\WidgetBase::load('position', 'position_1');
      $position .= '</div>';

      return $position;
    }
  
    function panel_head(String $title, String $icon = 'entypo-plus-circled'){
      $panel_head = "<div class='panel-heading'>";
      $panel_head .= "<div class='panel-title'>";
      $panel_head .= "<i class='".$icon."'></i>";
      $panel_head .= str_replace('_',' ',$title);
      $panel_head .= "</div>";
      $panel_head .= "</div>";
  
      return $panel_head;
    }
  
    function add_form(String $form_name, String $form_contents){
      $widget_col = $this->widgets();
      $row = $this->row([$widget_col]);
      $panel = $this->panel($form_name,$form_contents);
      $column =  $this->col($panel);
      $row .= $this->row([$column]);
  
      return $row;
    }
  
    function form_group($form_group_input, int $input_holder_size = 12, int $label_size = 0, String $label_name = ''){
      if($input_holder_size == 12){
        $label_size = 0;
      }elseif($input_holder_size + $label_size > 12){
        $label_size = 3;
        $input_holder_size = 8;
      }
  
      if($label_size > 0 && $label_name == ''){
        $label_size = 0;
      }

      // $fld = new Fields_base(str_replace(' ','_',$label_name),get_instance()->controller);

      // $mask = $fld->is_field_required() ? "<span class='text-danger'>*</span>" : '';
      
      $form_group = "<div class='form-group'>";
      
      if($label_size > 0){
        $form_group .= "<label class='control-label col-xs-".$label_size."'>".$label_name. "</label>";
      }
      
      $form_group .= "<div class='col-xs-".$input_holder_size."'>";
      if(is_array($form_group_input)){
        $form_group .= implode(' ',$form_group_input);
      }else{
        $form_group .= $form_group_input;
      }
      $form_group .= "</div>";
      $form_group .= "</div>";
      return $form_group;
    }
  
    function create_single_form_add($table, $fields, $form_id = ''){
      
      $add_form = form_open('' , array('id'=>$form_id,'class' => 'form-horizontal form-groups-bordered', 'enctype' => 'multipart/form-data'));
      $lib = new \App\Libraries\System\GrantsLibrary();

      foreach ($fields as $column => $field) {
  
        if( $lib->isPrimaryKeyField($table,$column) == true ||
            $lib->isHistoryTrackingField($table,$column) ||
            $column == 'approval_name' ||
            ($column == 'status_name' && $table != 'status')
        ){
          continue;
        }
  
        $add_form .= $this->form_group($field,9,3,get_phrase($column));
  
      }
  
      $add_form .= $this->form_group([$this->reset_button(),$this->save_button($form_id),$this->save_new_button($form_id)]);
      
      $add_form .= "</form>";
  
      return $add_form;
    }
  
    function reset_button(){
      return "<div class='btn btn-default'>".get_phrase('reset')."</div>";
    }
  
    function save_button($form_id){
        if($form_id == 'add_form'){
            return "<button class='btn btn-default save back'>".get_phrase('save')."</button>";
        }else{
            return "<button class='btn btn-default edit back'>".get_phrase('edit')."</button>";
        }
    }
  
    function save_new_button($form_id){
      if($form_id == 'add_form'){
        return "<button class='btn btn-default save_new'>".get_phrase('save_and_new')."</button>";
      }else{
        return "<button class='btn btn-default edit_continue'>".get_phrase('edit_and_continue')."</button>";
      }
    }
  
  }