<?php 

use \App\Libraries\System\Widgets\WidgetBase;
use \App\Libraries\System\GrantsLibrary;
use App\Libraries\Core\UserLibrary;
use App\Libraries\Core\StatusLibrary;


$grantsLibrary = new GrantsLibrary();
$userLibrary = new UserLibrary();
$statusLibrary = new StatusLibrary();

extract($result['master']);
$grantsLibrary->unsetLookupTablesIds($keys);


// Make the master detail table have columns as per the config
$columns = array_chunk($keys,service("settings")->get("GrantsConfig.master_table_columns"),true);

?>
<div class="row">
  <div class="col-xs-12">
      <?=WidgetBase::load('comment');?>
  </div>
</div>

<div class="row">
  <div class="col-xs-12">
      <?=WidgetBase::load('position','position_1');?>
  </div>
</div>

<div class="row">
  <div class="col-xs-12" id='print_pane'>
    <table class="table">
      <thead>
        <tr>
          <th colspan="<?=service("settings")->get("GrantsConfig.master_table_columns");?>" style="text-align:center;"><?=get_phrase($uri->getSegment(1).'_master_record');?>
          </th>
        </tr>

        <tr>
          <th colspan="<?=service("settings")->get("GrantsConfig.master_table_columns");?>" style="text-align:center;">
              <?php 
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
                  $status_data = $grantsLibrary->actionButtonData($controller, $account_system_id);
                  extract($status_data);

                  echo approval_action_button($controller,$item_status, $primary_key, $status_id, $item_initial_item_status_id, $item_max_approval_status_ids);
              }
              
              if(isset($action_labels['show_decline_button']) && $action_labels['show_decline_button']){
                   echo WidgetBase::load('button',get_phrase('decline'),$controller.'/decline/'.$id);
              
               }

                echo WidgetBase::load('button',get_phrase('print'),'#','btn_print','hidden-print');
               ?>     
                  

              <?=WidgetBase::load('position','position_2');?>
          </th>
        </tr>
      </thead>
      <tbody>


        <?php

            foreach ($columns as $row) {
          ?>
            <tr>
          <?php
   
              foreach ($row as $column) {
                $column_value = $table_body[$column];
            
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
                      if(in_array($column,$libs->currencyFields())){
                        echo get_phrase($column).' ('.$session->get('user_currency_code').')';
                      }else{
                        echo get_phrase($column);
                      }
                    ?>:</span> &nbsp;
                  <?php
                    if(strpos($column,'is_')){
                      echo $column_value == 1?get_phrase('yes'):get_phrase('no');

                    }elseif(in_array($column,$lookup_name_fields) ){
                        $primary_table_name = substr($column,0,-5);
                        $lookup_table_id = $table_body[strtolower($primary_table_name).'_id'];
                        echo '<a href="'.base_url().$primary_table_name.'/view/'.hash_id($lookup_table_id).'">'.ucwords(str_replace('_',' ',$column_value)).'</a>';
                    }elseif(in_array($column,$libs->currencyFields())){
                        echo number_format($column_value,2);
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
    <?php if($userLibrary->checkRoleHasPermissions('language_phrase', 'update')){?>
    <div class="row">
      <div class="col-xs-12">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th><?=get_phrase('language_file');?></th>
                    <th><?=get_phrase('upload_language_file');?></th>
                    <?php if($session->get('system_admin')){?>
                        <th><?=get_phrase('account_system_code');?></th>
                    <?php }?>
                </tr>
            </thead>
            <tbody>
                <form action="<?=site_url('language/upload_language_file');?>" method="post" enctype="multipart/form-data">
                <tr>
                    <td><a href="<?=site_url('language/download_language_file');?>/<?=$account_system_code;?>/<?=$language_code;?>"><?=get_phrase('download_language_file','Download Language File');?></a></td>
                    <td><input type = "file" id = "languagefile" name = "csv_file" accept=".csv"/></td>
                    <?php if($session->get('system_admin')){?>
                        <td>
                            <select class="form-control" name = "account_system_code" id = "account_system_code">
                                <option value = ""><?=get_phrase('select_account_system');?></option>
                                <?php foreach($account_systems as $account_system){?>
                                    <option value = "<?=$account_system->account_system_code;?>" <?php if($account_system->account_system_code == $session->get('user_account_system_code')){?> selected <?php }?> ><?=$account_system->account_system_name;?></option>
                                <?php }?>
                            </select>
                        </td>
                    <?php }else{?>
                        <input type="hidden" name="account_system_code" value="<?=$account_system_code;?>" /></td>
                    <?php } ?>
                    
                    <input type="hidden" name="language_code" value="<?=$language_code;?>" />
                    <td><input class="btn btn-default" id="submit" type="submit" value="upload" /></td>
                </tr>
                </form>
            </tbody>
        </table>
      </div>
    </div>
    <?php }?>
  </div>
</div>


<script>

$('#submit').on('click', function(ev){
    if($('#languagefile').val() == ""){
        alert('<?=get_phrase('choose_a_file');?>');
        ev.preventDefault();
    }

    if($("#account_system_code").length && $("#account_system_code").val() == ""){
        alert('<?=get_phrase('choose_an_account_system');?>');
        ev.preventDefault();
    }
});

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