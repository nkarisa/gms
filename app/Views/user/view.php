<?php

$userLibrary = new \App\Libraries\Core\UserLibrary();

$status_workflows = $result['approval_workflow_assignments'];
$office_hierarchy = $result['user_hierarchy_offices'];
$role_permissions = $result['role_permission'];

extract($result);
extract($result['user_info']);
extract($result['status_data']);
?>

<div class='row'>
    <div class='col-xs-12'>
        <div class="panel panel-default" data-collapsed="0">
       	    <div class="panel-heading">
           	    <div class="panel-title" >
           		    <i class="entypo-eye"></i>
					    <?php echo get_phrase('view_user');?>
           	    </div>
            </div>
	    
            <div class="panel-body"  style="max-width:50; overflow: auto;">	

                <?php echo form_open("" , array('id'=>'frm_user','class' => 'form-horizontal form-groups-bordered validate', 'enctype' => 'multipart/form-data'));?>

                    <?php if($userLibrary->checkRoleHasPermissions(ucfirst($controller), 'update')){?>
                     
                        <div class='form-group'>
                        <div class='col-xs-12'  style='text-align:center;'>
                            <?php 
                             $user_id = hash_id($id, 'decode');
                             $edit_user_id = $session->user_id;
                             if($user_id != $edit_user_id){ ?>
                                <a href='<?=base_url();?>user/edit/<?=$uri->getSegment(3,0);?>' id='edit' class='btn btn-default btn-edit'><?=get_phrase('edit');?></a>
                            <?php } ?>
                            
                            <button id='clone' class='btn btn-default btn-clone'><?=get_phrase('clone');?></button>
                            <button id='reset_password' class='btn btn-default btn-reset-password'><?=get_phrase('reset_password');?></button>
                            <div id='reset_consent' class='btn btn-default btn-reset-consent'><?=get_phrase('reset_data_privacy_consent');?></div>
                            <span>
                            <?php 
                                echo approval_action_button($controller, $item_status, $user_id, $user_info['status_id'], $item_initial_item_status_id, $item_max_approval_status_ids);
                            ?>
                            </span>
                        </div>
                    </div>  
                    
                    <?php }
                        if(!empty($valid_user_unique_identifier) && $data_consent == null && $user_context_id == 1){
                    ?>
                            <div class='form-group'>
                                <div class='col-xs-12'  style='text-align:center;'>
                                    <div class = 'well' style="color: red;text-align:center;">
                                        <?=get_phrase('privacy_consent_required_for_approval','User privacy data consent is required to approve this user');?>
                                    </div>
                                </div>
                            </div>
                    <?php
                        }

                    $disabled = empty($data_consent) ? 'disabled' : '';

                    $list_of_uploads = "";
                    if(count($user_identity_documents) > 0){
                        foreach($user_identity_documents as $user_identity_document){
                            if(!isset($user_identity_document['attachment_url'])) continue;
    
                            $objectKey = $user_identity_document['attachment_url'].'/'.$user_identity_document['attachment_name'];
                            $url = service("settings")->get("GrantsConfig.upload_files_to_s3") ? $attachmentLibrary->s3PreassignedUrl($objectKey): $attachmentLibrary->getLocalFilesystemAttachmentUrl($objectKey);
                            
                            $list_of_uploads .= "<a target='__blank' href='". $url. "'>". $user_identity_document['attachment_name']. "</a></br>";
                        }
                    }
                    

                        echo form_group(
                            form_group_content(get_phrase('first_name','First Name'),form_group_content_input($user_firstname,false)),
                            form_group_content(get_phrase('last_name','Last Name'),form_group_content_input($user_lastname,false)),
            
                            form_group_content(get_phrase('preferred_username','Preferred User Name'),form_group_content_input($user_name,false)),
                            form_group_content(get_phrase('email','Email'),form_group_content_input($user_email,false)),
              
                            form_group_content(get_phrase('user_context_definition','User Context Definition'),form_group_content_input(ucfirst($context_definition_name),false)),
                            form_group_content(get_phrase('user_is_context_manager','Is User a Context Manager?'),form_group_content_input($user_is_context_manager == 0?get_phrase('no'):get_phrase('yes'),false)),
                        
                            form_group_content(get_phrase('switchable_user','Switchable user'),form_group_content_input($user_is_switchable == 0?get_phrase('no'):get_phrase('yes'),false)),
                            form_group_content(get_phrase('departments','Departments'),form_group_content_input($department,false)),
                            
                            form_group_content(get_phrase('user_role','User Role'),form_group_content_input($role_name,false)),
                            form_group_content(get_phrase('user_default_language','User Default Language'),form_group_content_input($language_name,false)),

                           !empty($valid_user_unique_identifier) && $context_definition_id == 1 ? form_group_content(get_phrase('employment_date'),form_group_content_input($user_employment_date,false)) : '',
                           !empty($valid_user_unique_identifier) && $context_definition_id == 1 ? form_group_content($valid_user_unique_identifier['unique_identifier_name'],form_group_content_input($user_unique_identifier,false)) : '',

                           !empty($valid_user_unique_identifier) && $context_definition_id == 1 ? form_group_content(get_phrase('user_identity_documents'),form_group_content_input($list_of_uploads,false)) : '',
                           !empty($valid_user_unique_identifier) && $context_definition_id == 1 ? form_group_content(get_phrase('data_privacy_consent'),form_group_content_input('<div class = "btn btn-default '.$disabled.'" id = "show_consent">Show Consent</div>',false)) : ''
                            
                        );

                    ?>

                    <div class='form-group hidden' id = 'consent_content'>
                        <div class='col-xs-12'>
                            <?=$data_consent;?>
                        </div>
                    </div>
                 
                    <hr/>

                    <div class='form-group'>
                        <label style='text-align:center;' class='col-xs-12 control-label'><?=get_phrase('page_field_permissions_label','Page and Field Permissions');?></label>
                        <div class='col-xs-12'>
                            <table class='table datatable'>
                                <thead>
                                    <tr>
                                        <th><?=get_phrase('permission_item');?></th>
                                        <th><?=get_phrase('permission_type');?></th>
                                        <th><?=get_phrase('permission_label');?></th>
                                        <th><?=get_phrase('permission_name');?></th>
                                    </tr>
                                </thead>
                                <tbody>

                                <?php
                                    foreach($role_permissions as $permission_item => $role_permission){
                                        foreach($role_permission as $permission_type => $labelled_permission){
                                            foreach($labelled_permission as $permission_label => $permission){
                                ?>  
                                        <tr>
                                            <td><?=ucwords(str_replace('_',' ',$permission_item));?></td>
                                            <td><?=$permission_type == 1?get_phrase('field_access'):get_phrase('field_access');?></td>
                                            <td><?=ucfirst($permission_label);?></td>
                                            <td><?=ucwords(str_replace('_',' ',$permission));?></td>
                                        </tr>
                                <?php 
                                            }
                                        }
                                    }
                                ?>
                                    
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class='form-group'>
                        <label style='text-align:center;margin-bottom:10px;' class='col-xs-12 control-label'><?=get_phrase('office_hierarchy_label','Office Hierachy');?></label>
                        <div class='col-xs-12'>
                            <div class='text-center'>
                                <?php 
                                if($userLibrary->checkRoleHasPermissions(ucfirst('context_'.$context_definition_name.'_user'), 'create')){
                                    echo add_record_button('context_'.$context_definition_name.'_user','',$id,false,false);
                                }
                                ?>
                            </div>

                            <?php 
                                $hide_element_class = "";
                                if(!$userLibrary->checkRoleHasPermissions(ucfirst('context_'.$context_definition_name), 'update')){
                                    $hide_element_class = "hidden";
                                }
                            ?>

                            <table class='table datatable'>
                            <thead> 
                                <tr>
                                    <th class="<?=$hide_element_class;?>"><?=get_phrase('action');?></th>
                                    <th><?=get_phrase('office_context');?></th>
                                    <th><?=get_phrase('office_name');?></th>
     
                                </tr>
                            </thead>
                            <tbody>
                                
                                <?php 
                                    foreach($result['user_hierarchy_offices'] as $office){
                                        //foreach($offices as $office){
                                ?>
                                        <tr>
                                            <td class="<?=$hide_element_class;?>"><button data-office_id = '<?=$office['office_id'];?>' class='btn btn-danger deactivate_context'><?=get_phrase('deactivate');?></button></td>
                                            <td><?=ucfirst($context_definition_name);?></td>
                                            <td><?=$office['office_name'];?></td>
                                        </tr>
                                    <?php 
                                            //}
                                        }
                                    ?>
                            </tbody>
                            </table>
                        </div>
                    </div>

                  
                    
                    <div class='form-group'>
                        <label style='text-align:center;' class='col-xs-12 control-label'><?=get_phrase('approval_workflow_assignments_label','Approval Workflow Assignments');?></label>
                        <div class='col-xs-12'>
                            <table class='table datatable'>
                                <thead>
                                    <tr>
                                        <th><?=get_phrase('approveable_item');?></th>
                                        <th><?=get_phrase('status_name');?></th>    
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($status_workflows as $status_workflow){?>
                                        <tr>
                                            <td><?=ucwords(str_replace('_',' ',$status_workflow['approve_item_name']));?></td>
                                            <td><?=$status_workflow['status_name'];?></td>
                                        </tr>
                                    <?php }?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </form>
            </div>
        </div>
          
    <div>
</div>

<script>

    $(document).ready(function () {
        const valid_user_unique_identifier = '<?=count($valid_user_unique_identifier);?>'
        const data_consent = '<?=$data_consent;?>'
        const user_context_id = '<?=$user_context_id;?>'
        
        if(valid_user_unique_identifier > 0 && !data_consent && user_context_id == 1){
            $('.item_action').addClass('disabled');

        }

    })

    $('#reset_consent').on('click', function () {
        const user_id = '<?=hash_id($id,'decode');?>'
        const url = '<?=base_url();?>user/reset_data_privacy_consent/' + user_id

        const cfm =confirm('Are you sure you want to reset this user\'s data privacy consent?');

        if(!cfm){
            alert('Process aborted');
            return false;
        }

        $.get(url, function(response) {
            alert(response)
        })
    })

    $('#show_consent').on('click', function () {
        const consent_content_is_hidden = $('#consent_content').hasClass('hidden')

        if(consent_content_is_hidden){
            $('#consent_content').removeClass('hidden')
        }else{
            $('#consent_content').addClass('hidden')
        }
    })
    
    $('.deactivate_context').on('click',function(e){
        const url = "<?=base_url();?>user/remove_context_user";
        const row = $(this).closest('tr');
        const deactivate_context_count = $('.deactivate_context').length;
        //alert(deactivate_context_count);
        if(deactivate_context_count == 1){
            alert('You must have atleast one context for a user');
        }else{
            const data = {
                'context_definition_id': '<?=$context_definition_id;?>',
                'context_definition_name': '<?=$context_definition_name;?>',
                'office_id': $(this).data('office_id'),
                'user_id': '<?=hash_id($id,'decode')?>'
            }

            $.post(url,data,function(response){
                alert(response);
                row.remove();
            })
        }

        e.preventDefault();
        
    });

    $("#reset_password").on('click',function(e){
        const url = "<?=base_url();?>user/password_reset/<?=$id;?>";

        var conf = confirm("Are you sure you want to reset the password?");

        if(!conf){
            alert("Process aborted");
            return false;
        }

        $.get(url,function(response){
            alert(response);
        });

        e.preventDefault();
    });

</script>