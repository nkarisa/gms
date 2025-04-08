<?php 

namespace App\Libraries\System;

use Config\GrantsConfig;

class SearchBuilderLibrary {

    private $db;
    protected $read_db;
    protected $config;
    protected $request;

    function __construct(){
        
        $this->db = \Config\Database::connect();
        $this->read_db = \Config\Database::connect('read');

        $this->config = config(GrantsConfig::class);
        $this->request = service('request');
    }

    function default_query_group($search_columns, $value, $builder){
        if(!empty($value)){
            $builder->groupStart();
            $column_key = 0;
              foreach($search_columns as $column){
                if($column_key == 0) {
                  $builder->like($column,$value,'both'); 
                }else{
                  $builder->orLike($column,$value,'both');
              }
                $column_key++;				
            }
            $builder->groupEnd();       
          }
    }

    function searchbuilder_query_group($columns, $builder){

        $searchBuilder = $this->request->getPost('searchBuilder');

        if($searchBuilder != null && isset($searchBuilder['criteria'][0]['condition'])){

            $outer_criteria = $searchBuilder['criteria'];
            $outer_logic = $searchBuilder['logic'];
            // log_message('error', json_encode($searchBuilder));
            if(isset($outer_criteria)){
              $builder->groupStart();
              $column_key = 0;
              foreach($outer_criteria as $conditions){
                if(array_key_exists('condition', $conditions)){
                  $this->search_builder_condition($conditions,$outer_logic, $columns, $column_key, $builder);
                }elseif(array_key_exists('criteria', $conditions)){
                  $inner_criteria = $conditions['criteria'];
                  $inner_logic = $conditions['logic'];
                  $inner_column_key = 0;
                  foreach($inner_criteria as $inner_conditions){
                    if(array_key_exists('condition', $inner_conditions)){
                      $this->search_builder_condition($inner_conditions,$inner_logic, $columns, $inner_column_key, $builder);
                    }
                    $inner_column_key++;
                  }
      
                }else{
                  $conditions['condition'] = '=';
                  $this->search_builder_condition($conditions,$outer_logic, $columns, $column_key, $builder);
                }
      
                $column_key++;
              }
              $builder->groupEnd();
            } 
          }
    }

    function search_builder_condition($conditions,$outer_logic, $columns, $column_key, $builder){
        // log_message('error', json_encode(['conditions' => $conditions,'outer_logic' => $outer_logic, 'column_key' => $column_key]));
        $list_column = str_replace(' ','_',strtolower(trim($conditions['data'])));
        $column = get_query_column_for_list_column($columns, $list_column, '@');
        $value = isset($conditions['value'][0]) ? $conditions['value'][0] : '';
        $type = $conditions['type'];
        $condition = isset($conditions['condition']) ? $conditions['condition'] : '=';
    
        if($value == 'yes' || $value == 'Yes' || $value == 'YES'){
          $value = 1;
        }elseif($value == 'no' || $value == 'No' || $value == 'NO'){
          $value = 0;
        }

        // log_message('error', json_encode($type));
        
        $condition_key_word_prefix = ''; 
    
              if($column_key != 0 && $outer_logic == 'OR'){
                $condition_key_word_prefix = 'or_';
              }
    
              if($type == 'date'){
                switch($condition){
                  case '<':
                    $builder->{$condition_key_word_prefix.'where'}($column.' < "'.$value.'"');
                    break;
                  case '>':
                    $builder->{$condition_key_word_prefix.'where'}($column.' > "'.$value.'"');
                    break;
                  case 'between':
                    $value2 = isset($conditions['value'][1]) ? $conditions['value'][1] : date('Y-m-d');
                    $builder->{$condition_key_word_prefix.'where'}($column.' >= "'.$value.'" AND '. $column . ' <= "' . $value2.'"' );
                    break;
                  case '!=':
                      $value != '' ? $builder->{$condition_key_word_prefix.'where'}(array($column.' <>' => $value)) : $builder->{$condition_key_word_prefix.'where'}(array($column .' IS NOT NULL' => NULL));
                      break;
                  case '=':
                    $value != '' ? $builder->{$condition_key_word_prefix.'where'}(array($column => $value)) : $builder->{$condition_key_word_prefix.'where'}(array($column => NULL));
                    break;
                  default:
                    log_message('error', json_encode(['missing_operator' => $this->request->getPost('searchBuilder')]));
                }
              }elseif($type == 'string' || $type == 'html'){
                switch($condition){
                  case 'starts':
                    $builder->{$condition_key_word_prefix.'like'}($column,$value,'after');
                    break;
                  case '!starts':
                      $builder->{$condition_key_word_prefix.'notLike'}($column,$value,'after');
                      break;
                  case 'ends':
                    $builder->{$condition_key_word_prefix.'like'}($column,$value,'before');
                    break;
                  case 'ends':
                      $builder->{$condition_key_word_prefix.'notLike'}($column,$value,'before');
                      break;
                  case 'contains':
                    $builder->{$condition_key_word_prefix.'like'}($column,$value,'both');
                    break;
                  case '!contains':
                      $builder->{$condition_key_word_prefix.'notLike'}($column,$value,'both');
                      break;
                  case '!=':
                    $array_of_values = explode(',',$value);
                    $value != '' ? $builder->{$condition_key_word_prefix.'whereNotIn'}($column, $array_of_values) : $builder->{$condition_key_word_prefix.'where'}(array($column.' IS NOT NULL' => NULL));
                    break;
                  case '=':
                    $array_of_values = explode(',',$value);
                    $value != '' ? $builder->{$condition_key_word_prefix.'whereIn'}($column, $array_of_values): $builder->{$condition_key_word_prefix.'where'}($column, NULL);
                    break;
                  default:
                    log_message('error', json_encode(['missing_operator' => $this->request->getPost('searchBuilder')]));
                    
                }
              }elseif($type == 'num' || $type == 'html-num' || $type == 'html-num-fmt' || $type == 'num-fmt'){
                switch($condition){
                  case '<':
                    $builder->{$condition_key_word_prefix.'where'}($column.' < '.$value);
                    break;
                  case '>':
                    $builder->{$condition_key_word_prefix.'where'}($column.' > '.$value);
                    break;
                  case '<=':
                    $builder->{$condition_key_word_prefix.'where'}($column.' <= '.$value);
                    break;
                  case '>=':
                    $builder->{$condition_key_word_prefix.'where'}($column.' >= '.$value);
                    break;
                  case 'between':
                    $value2 = isset($conditions['value'][1]) ? $conditions['value'][1] : date('Y-m-d');
                    $builder->{$condition_key_word_prefix.'where'}($column.' >= "'.$value.'" AND '. $column . ' <= "' . $value2.'"' );
                    break;
                  case '!between':
                      $value2 = isset($conditions['value'][1]) ? $conditions['value'][1] : date('Y-m-d');
                      $builder->{$condition_key_word_prefix.'where'}($column.' < "'.$value.'" OR '. $column . ' > "' . $value2.'"' );
                      break;
                  case '=':
                    $array_of_values = explode(',',$value);
                    $value != '' ? $builder->{$condition_key_word_prefix.'whereIn'}($column, $array_of_values) : $builder->{$condition_key_word_prefix.'where'}($column, NULL);
                    break;
                  case '!=':
                      $array_of_values = explode(',',$value);
                      $value != '' ? $builder->{$condition_key_word_prefix.'whereNotIn'}($column, $array_of_values): $builder->{$condition_key_word_prefix.'where'}(array($column .' IS NOT NULL' => NULL));
                      break;
                  default:
                    log_message('error', json_encode(['missing_operator' => $this->request->getPost('searchBuilder')]));
                }
              }
      }
}