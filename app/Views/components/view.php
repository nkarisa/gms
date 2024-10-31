<?php 

use \App\Libraries\System\Widgets\WidgetBase;

// echo json_encode($result['detail']);

extract($result);
extract($result['master']);

$config = config(Config\GrantsConfig::class);
$userLibrary = new \App\Libraries\Core\UserLibrary();
$statusLibrary = new \App\Libraries\Core\StatusLibrary();
$uri = service('uri');
$lib = service('grantslib');
$lib->unsetLookupTablesIds($keys);

// Make the master detail table have columns as per the config
$columns = array_chunk($keys,$config->master_table_columns,true);

?>
<div class="row">
  <div class="col-xs-12">
      <?php 
        echo WidgetBase::load('comment');
      ?>
  </div>
</div>

<div class="row">
  <div class="col-xs-12">
    <?php 
        echo WidgetBase::load('position','position_1');
    ?>
  </div>
</div>

<div class="row">
  <div class="col-xs-12" id='print_pane'>
    <table class="table">
      <thead>
        <tr>
          <th colspan="<?=$config->master_table_columns;?>" style="text-align:center;"><?=get_phrase($uri->getSegment(1).'_master_record');?>
          </th>
        </tr>

        <tr>
          <th colspan="<?=$config->master_table_columns;?>" style="text-align:center;">
              <?php 
              //echo $table_body['status_id'];
              //echo $this->status_model->is_status_actionable_by_user($table_body['status_id'],$this->controller);
              
              if( $userLibrary->isStatusActionableByUser($table_body['status_id'], $controller) ){
                if($userLibrary->checkRoleHasPermissions(ucfirst($controller),'update'))
                {
                     echo WidgetBase::load('button',get_phrase('edit'),$controller.'/edit/'.$id);
                }
  
                if($userLibrary->checkRoleHasPermissions(ucfirst($controller),'delete'))
                {
                    echo WidgetBase::load('button',get_phrase('delete'),$controller.'/delete/'.$id);
                }
  
              }
              
              
              if(isset($action_labels['show_label_as_button']) && $action_labels['show_label_as_button']){ 
              
                  $primary_key = hash_id($id, 'decode');
                  $status_id = $table_body['status_id'];
                  $account_system_id = $statusLibrary->getStatusAccountSystem($status_id);
                  $status_data = $lib->actionButtonData($controller, $account_system_id);
                  extract($status_data);

                  echo approval_action_button($controller,$item_status, $primary_key, $status_id, $item_initial_item_status_id, $item_max_approval_status_ids);
              }
              
              if(isset($action_labels['show_decline_button']) && $action_labels['show_decline_button']){
                   echo WidgetBase::load('button',get_phrase('decline'),$controller.'/decline/'.$id);
              
               }

                echo WidgetBase::load('button',get_phrase('print'),'#','btn_print','hidden-print');
               ?>     
                  

            <?php 
                // echo Widget_base::load('position','position_2');
            ?>
          </th>
        </tr>
      </thead>
      <tbody>


        <?php

            foreach ($columns as $row) {
          ?>
            <tr>
          <?php
              //$primary_table_name = "";
              foreach ($row as $column) {
                $column_value = $table_body[$column];
                
                // Implement these skips in the before Output
                //if( strpos($column,'_deleted_at') == true) continue;
              

                if(strpos($column,'_created_by') == true){
                    $column_value = $table_body['created_by'];
                }

                if(strpos($column,'_last_modified_by') == true ){
                    $column_value = $table_body['last_modified_by'];
                }


          ?>
                <td>
                  <span style="font-weight:bold;">
                    <?php 
                    //   if(in_array($column,$this->{$this->controller.'_model'}->currency_fields())){
                    //     echo get_phrase($column).' ('.$this->session->user_currency_code.')';
                    //   }else{
                        echo get_phrase($column);
                    //   }
                    ?>:</span> &nbsp;
                  <?php
                    if(strpos($column,'is_')){
                      echo $column_value == 1?get_phrase('yes'):get_phrase('no');

                    }elseif(in_array($column,$lookup_name_fields) ){
                        $primary_table_name = substr($column,0,-5);
                        $lookup_table_id = $table_body[strtolower($primary_table_name).'_id'];
                        echo '<a href="'.base_url().$primary_table_name.'/view/'.hash_id($lookup_table_id).'">'.ucwords(str_replace('_',' ',$column_value)).'</a>';
                    //}elseif(in_array($column,$this->{$this->controller.'_model'}->currency_fields())){
                      //  echo number_format($column_value,2);
                        //echo $column_value;
                    }else{
                        echo ucwords(str_replace('_',' ',$column_value));
                    }
                  ?>
                </td>
          <?php
              }
          ?>
              </tr>
          <?php
            }
          ?>
          
      </tbody>
    </table>
    <div class="row">
      <div class="col-xs-12">
        <?php 
            // echo Widget_base::load('position','position_3');
        ?>
      </div>
    </div>
    <?php

    if( isset($result['detail']) && count($result['detail']) > 0){
      //print_r($result['detail']);
      foreach ($result['detail'] as $detail_table_name => $details) {
        //print_r(array_keys($details));
        extract($details);
        //echo $detail_table_name;
        $primary_key_column = array_shift($keys);
        ?>

        <hr/>

        <div class="row" style="margin-bottom:25px;">
          <div class="col-xs-12" style="text-align:center;">
            
            <?php
              if($show_add_button){
                echo add_record_button($detail_table_name, $controller,$has_details_table,$uri->getSegment(3),$has_details_listing, $is_multi_row);// $details['is_multi_row']
              }
            ?>
          </div>
        </div>
          <table class="table table-striped datatable_details">
            <thead>
              <!--Add one to count of keys because of the action column that has been added in this view s-->
              <tr><th colspan="<?=count($keys) + 1;?>"><?=ucwords(str_replace("_"," ",$detail_table_name));?></th></tr>
              <?=render_list_table_header($detail_table_name,$keys);?>
            </thead>
            <tbody>
              <?php foreach ($table_body as $row) {
                //print_r($row);
                ?>
                <tr>
                  <td class='hidden-print'>
                      <?php
                        //echo $this->grants->action_list($detail_table_name,$row[$detail_table_name.'_id'],$is_approveable_item);
                      ?>
                      <div class="dropdown">
                      <button class="btn btn-default dropdown-toggle" type="button" id="menu1" data-toggle="dropdown">
                        <?=get_phrase('action');?>
                      <span class="caret"></span></button>
                      <ul class="dropdown-menu" role="menu" aria-labelledby="menu1">

                      <?php if(
                      $userLibrary->isStatusActionableByUser($row['status_id'],$detail_table_name) &&
                      (
                        $userLibrary->checkRoleHasPermissions((ucfirst($detail_table_name)),'update') || 
                        $userLibrary->checkRoleHasPermissions((ucfirst($detail_table_name)),'delete')
                      )
                    ){?>

                        <?php if($userLibrary->checkRoleHasPermissions(ucfirst($detail_table_name),'update')){ ?>
                        <li><?=list_table_edit_action($detail_table_name,$row[$detail_table_name.'_id']);?></li>
                        <li class="divider"></li>
                        <?php }?>
                        <?php if($userLibrary->checkRoleHasPermissions(ucfirst($detail_table_name),'delete')){ ?>
                        <li><?=list_table_delete_action($detail_table_name,$row[$detail_table_name.'_id']);?></li>
                        <?php }?>

                        <?php }else{
                            echo "<li><a href='#'>".get_phrase('no_action')."</a></li>";
                        }?>

                      </ul>
                    </div>
                  </td>
                  <?php
                      
                      $primary_key = 0;
                      
                      $column_key = 0;

                      $lookup_table = "";
                      
                      foreach ($keys as $column){
                        $primary_key = $row[$primary_key_column];
                        
                        if(strpos($column,'_id') == true && 
                            !$lib->isPrimaryKeyField($detail_table_name,$column) 
                          ){

                          //$column_key = $row['fk_'.$column];
                          // Remove the id suffix

                          $lookup_table = substr($column,0,-3);
                          continue;
                        }

                  ?>
                        <td>

                          <?php
                          if(isset($row[$column]) && array_key_exists($column,$row) ){
                            if(strpos($column,'track_number') == true && $has_details_table == 1 ){
                              echo '<a href="'.base_url().$detail_table_name.'/view/'.hash_id($primary_key).'">'.$row[$column].'</a>';
                            }elseif(strpos($column,'_is_') == true){
                                echo $row[$column] == 1 || $row[$column] == "Yes" || $row[$column] == "yes" ?"Yes":"No";
                            }elseif($fields_meta_data[$column] == 'int' || $fields_meta_data[$column] == 'decimal'){
                               echo number_format($row[$column],2);
                            }elseif($column_key > 0){ 
                               echo '<a href="'.base_url().$lookup_table.'/view/'.hash_id($column_key).'">'.ucwords(str_replace('_',' ',$row[$column])).'</a>';
                            }else{
                                echo ucfirst($row[$column]);
                            }
                          }
                          else{
                            echo get_phrase('value_not_set');
                          }
                          ?>
                        </td>
                  <?php
                      }
                  ?>
                </tr>
              <?php
              }
              ?>
            </tbody>
          </table>
        <?php
      }
    }
    ?>
  </div>
</div>


<script>

$(document).ready(function(){
  $('.btn_export, .dataTables_filter,.dataTables_info').addClass('hidden-print');
});

$('#btn_print').on('click',function(ev){

  PrintElem('#print_pane');

  ev.preventDefault();
});

function PrintElem(elem)
    {
        $(elem).printThis({ 
		    debug: false,              
		    importCSS: true,             
		    importStyle: true,         
		    printContainer: false,       
		    loadCSS: "", 
		    pageTitle: "<?php echo get_phrase('grants_system');?>",             
		    removeInline: false,        
		    printDelay: 333,            
		    header: null,             
		    formValues: true          
		});
    }
</script>