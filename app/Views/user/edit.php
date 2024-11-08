<?php

$grantsLibrary = new \App\Libraries\System\GrantsLibrary();
$accountSystemLibrary = new \App\Libraries\Core\AccountSystemLibrary();
$countryCurrencyLibrary = new \App\Libraries\Grants\CountryCurrencyLibrary();

$user_id = $id;

extract($result);

extract($result['edit_user_info']);
extract($result['all_offices']);
extract($result['all_departments']);
extract($result['all_context_offices']);
extract($result['all_context_roles']);
extract($result['all_designations']);

$user_context_level = $session->context_definition['context_definition_level'];

$unique_identifier = 0;

if(isset($account_system_identifier['unique_identifier_id']) && $account_system_identifier['unique_identifier_id'] > 0){
    $unique_identifier = $account_system_identifier['unique_identifier_id'];
}
?>

<style>
    .user_message {
        text-align: center;
        color: red;
    }
</style>

<div class='row'>
    <div class='col-xs-12'>

        <div class="panel panel-default" data-collapsed="0">
            <div class="panel-heading">
                <div class="panel-title">
                    <i class="entypo-plus-circled"></i>
                    <?php echo get_phrase('edit_user'); ?>
                </div>
            </div>

            <div class="panel-body" style="max-width:50; overflow: auto;">

                <?php echo form_open("", array('id' => 'frm_edit_user', 'class' => 'form-horizontal form-groups-bordered validate', 'enctype' => 'multipart/form-data')); ?>

                <div class='form-group'>
                    <div class='col-xs-12 user_message'>

                    </div>
                </div>

                <div class='form-group'>
                    <div class='col-xs-12' style='text-align:center;'>
                        <div class='btn btn-default btn-save'><?= get_phrase('save_changes'); ?></div>
                        <div class='btn btn-default btn-cancel'><?= get_phrase('cancel'); ?></div>
                    </div>
                </div>

                <div class='form-group'>
                    <label class='col-xs-2 control-label'><?= get_phrase('first_name'); ?></label>
                    <div class='col-xs-4'>
                        <?= $grantsLibrary->headerRowField('user_firstname', $user_firstname); ?>
                    </div>

                    <label class='col-xs-2 control-label'><?= get_phrase('last_name'); ?></label>
                    <div class='col-xs-4'>
                        <?= $grantsLibrary->headerRowField('user_lastname', $user_lastname); ?>
                    </div>
                </div>


                <div class='form-group'>


                    <label class='col-xs-2 control-label'><?= get_phrase('email') ?></label>
                    <div class='col-xs-4'>
                        <div class="input-group">
                            <span class="input-group-addon"><i style="color:red" class="fa fa-asterisk"></i></span>
                            <input id='user_email' type="text" required="required" value='<?= $user_email; ?>' class="form-control master input_user " name="header[user_email] ">
                        </div>



                    </div>

                    <!-- Context Definition -->

                    <label class='col-xs-2 control-label'><?= get_phrase('user_context_definition'); ?></label>
                    <div class='col-xs-4'>
                        <!-- Populate the context offices -->
                        <div class='input-group'>
                            <span class='input-group-addon'>
                                <i style="color:red" class='fa fa-asterisk'></i>

                            </span>
                            <select data-context_definition_id="<?=$context_definition_id;?>"  id='fk_context_definition_id' name="header[fk_context_definition_id]" class='form-control master required input_user fk_context_definition_id select2 select2-offscreen visible'>


                                <?php

                                $redescribe_office_compassion_way = '';
                                $hold_context_definition_id = $context_definition_id;
                                switch ($context_definition_id) {
                                    case 1:
                                        $redescribe_office_compassion_way = get_phrase('FCP');
                                        break;
                                    case 3:
                                        $redescribe_office_compassion_way = get_phrase('region_/_base_/_province');
                                        break;
                                    case 4:
                                        $redescribe_office_compassion_way = get_phrase('national_office');
                                        break;
                                    case 5:
                                        $redescribe_office_compassion_way = get_phrase('regional_office');
                                        break;
                                    case 6:
                                        $redescribe_office_compassion_way = get_phrase('GMC');
                                        break;
                                    default:
                                        $redescribe_office_compassion_way = get_phrase('cluster');
                                }

                                ?>

                                

                                <option value="<?= $context_definition_id ?>"><?= $redescribe_office_compassion_way; ?></option>
                                <?php
                                //Poulate other context offices
                                    foreach ($all_context_offices as $id => $context_office) {
                                        if ($id != $context_definition_id) { 
                                ?>
                                        <option value="<?= $id ?>"><?= $context_office; ?></option>
                                <?php 
                                        }
                                    }
                                ?>


                            </select>
                           
                        </div>
                         <!-- Hold definition -->
                         <input id='hold_context_definition_id' type="text"  value='<?= $hold_context_definition_id; ?>' class=" hidden form-control master input_user " name="header[hold_context_definition_id]">
                    </div>


                    <label class='col-xs-2 control-label hidden'><?= get_phrase('preferred_username'); ?></label>
                    <div class='col-xs-4 hidden'>

                        <input id="user_name" value='<?= $user_name; ?>' required="required" type="text" class="form-control master input_user" name="header[user_name]">

                    </div>


                </div>

                <div class='form-group'>


                    <label class='col-xs-2 control-label'><?= get_phrase('user_office_context'); ?></label>
                    <div class='col-xs-4' id='div_office_context'>
                        <!-- Populate the user offices -->
                        <div class='input-group'>
                            <span class='input-group-addon'>
                                <i style="color:red" class='fa fa-asterisk'></i>

                            </span>

                            <select id='fk_user_context_office_id' name="header[fk_user_context_office_id][]" class='form-control master required input_user fk_context_definition_id select2 select2-offscreen visible' multiple>
                              
                                <?php
                                //Poulate other user context offices
                                foreach ($all_offices as $office_id => $all_office) {
                                    $selected = '';
                                    if(!empty($user_office)){
                                        foreach($user_office as $office_context){
                                            if($office_context['office_id'] == $office_id){
                                                $selected = 'selected';
                                            }
                                        }
                                    }
                                   ?>

                                        <option value='<?= $office_id; ?>' <?=$selected;?> ><?= $all_office; ?></option>
                                <?php 
                                    
                                }
                                ?>

                            </select>
                        </div>
                        <input type="hidden" name = "header[office_context_changed]" id = 'office_context_changed' />

                    </div>

                    <!-- Department For user-->
                    <label class='col-xs-2 control-label'><?= get_phrase('user_department'); ?></label>
                    <div class='col-xs-4' id='div_user_department'>
                        <div class='input-group'>
                            <span class='input-group-addon'>
                                <i style="color:red" class='fa fa-asterisk'></i>

                            </span>
                            <select id='department' name="header[department]" class='form-control master required input_user fk_context_definition_id select2 select2-offscreen visible'>

                                <option value='0'><?= get_phrase('select_a_department'); ?></option>

                                <?php
                                //Other related departments in the same context
                                foreach ($all_departments as $depart => $all_department) {
                                    //if ($depart != $departments[0]['department_id']) { ?>
                                        <option value='<?= $depart;?>' <?=isset($departments[0]) && $departments[0]['department_id'] ==  $depart? 'selected':''; ?> ><?= $all_department; ?></option>

                                <?php 
                                //}
                                } ?>
                            </select>
                        </div>
                    </div>

                </div>

                <?php if ($session->system_admin) { ?>

                    <div class='form-group'>
                        <label class='col-xs-2 control-label'><?= get_phrase('is_user_a_context_manager') ?></label>
                        <div class='col-xs-4'>
                            <?= $grantsLibrary->headerRowField('user_is_context_manager', 0); ?>
                        </div>

                        <label class='col-xs-2 control-label'><?=get_phrase('is_user_system_administrator');?></label>
                        <div class='col-xs-4'>
                            <?= $grantsLibrary->headerRowField('user_is_system_admin', 0, $session->system_admin ? false : true); ?>
                        </div>

                    </div>

                <?php } ?>


                <div class='form-group'>
                    <!-- User Designation -->
                    <label class='col-xs-2 control-label'><?= get_phrase('user_designation'); ?></label>
                    <div class='col-xs-4' id='div_user_designation'>
                        <div class='input-group'>
                            <span class='input-group-addon'>
                                <i style="color:red" class='fa fa-asterisk'></i>

                            </span>
                            <select id='designation' name="header[designation]" class='form-control master required input_user select2 select2-offscreen visible'>

                                <?php
                                $store_designation_id = '';

                                foreach ($user_designation as $user_designation_id => $designation) {

                                    $store_designation_id = $user_designation_id;

                                ?>
                                    <option value='<?= $user_designation_id; ?>'><?= $designation; ?></option>
                                    <?php }
                                //Other Desination of any
                                if (count($all_designations) > 0) {
                                    foreach ($all_designations as $key => $other_designation) {
                                        if ($user_designation_id != $key) { ?>
                                            <option value='<?= $key; ?>'><?= $other_designation; ?></option>
                                        <?php } ?>

                                <?php }
                                } ?>

                            </select>
                        </div>

                    </div>

                    <label class='col-xs-2 control-label'><?= get_phrase('user_primary_role') ?></label>
                    <div class='col-xs-4' id='div_user_role'>
                        <div class='input-group'>
                            <span class='input-group-addon'>
                                <i style="color:red" class='fa fa-asterisk'></i>

                            </span>

                            <select id='primary_role_id' name="header[primary_role_id]" class='form-control master required input_user fk_context_definition_id select2 select2-offscreen visible'>
                                <?php
                                
                                if (count($all_context_roles) > 0) {
                                    foreach ($all_context_roles as $key => $role_name) {
                                        foreach($user_primary_role as $primary_role_id => $name){
                                 ?>
                                        <option value='<?= $key; ?>' <?= $key == $primary_role_id ? 'selected' : ''?> ><?= $role_name; ?></option>
                                <?php 
                                        }
                                    }
                                } ?>

                            </select>
                        </div>
                    </div>

                </div>

                <!-- Country Currency -->
                <?php $hidden = !$session->system_admin ? 'hidden' : '';?>
                                
                <!-- Account System -->
                <div class='form-group <?= $hidden; ?>'>
                    <label class='col-xs-2 control-label'><?=get_phrase('user_accounting_system');?></label>
                    <div class='col-xs-4' id='div_account_system_id'>
                        <div class='input-group'>
                            <span class='input-group-addon'>
                                <i style="color:red" class='fa fa-asterisk'></i>

                            </span>
                            <?php
                            $account_systems = $accountSystemLibrary->getAccountSystems();
                            if ($session->system_admin) { ?>

                                <select id='fk_account_system_id' name="header[fk_account_system_id]" class='form-control master required input_user select2 select2-offscreen visible '>
                                    <option value='0'><?= get_phrase('select_records'); ?></option>
                                <?php foreach($account_systems as $account_system){?>
                                    <option value='<?=$account_system->account_system_id;?>' <?=$account_system_id == $account_system->account_system_id ? 'selected': '';?> ><?=$account_system->account_system_name;?></option>
                                <?php }?>
                                </select>

                                <?php } else {
                                   // foreach ($account_systems as $account_system_id_key => $account_system) { ?>
                                        <input id="account_system_id" value="<?= $account_system_id; ?>" required="required" type="text" class="form-control" name="header[account_system_id]">
                            <?php  
                                // }
                            } ?>
                        </div>

                    </div>

                    
                    <label class='col-xs-2 control-label <?= $hidden; ?>'><?=get_phrase('currency');?></label>
                    <div class='col-xs-4 <?= $hidden; ?>' id='div_country_currency_id'>
                        <div class='input-group'>
                            <span class='input-group-addon'>
                                <i style="color:red" class='fa fa-asterisk'></i>

                            </span>

                            <?php
                            $currencies = $countryCurrencyLibrary->getCountryCurrency();
                            // print_r($currencies);
                            if ($session->system_admin) { ?>

                                <select id='fk_country_currency_id' name="header[fk_country_currency_id]" class='form-control master required input_user select2 select2-offscreen visible'>
                                    <option value='0'><?= get_phrase('select_records'); ?></option>
                                    <?php foreach($currencies as $currency_id => $currency_code){?>
                                        <option value='<?=$currency_id;?>' <?=$country_currency_id == $currency_id ? 'selected' : '';?> ><?=$currency_code;?></option>
                                    <?php }?>
                                </select>

                                <?php } else {

                                foreach ($currencies as $currency_id_key => $currency) { ?>

                                    <input id="currency_id" value="<?= $currency_id_key; ?>" required="required" type="text" class="form-control" name="header[currency_id]">
                            <?php  }
                            } ?>
                        </div>

                    </div>
                </div>
                
                <?php if(!empty($valid_user_unique_identifier) && $context_definition_id == 1){?>
                <div class='form-group'>
                    <label class='col-xs-2 control-label'><?= get_phrase('employment_date'); ?></label>
                    <div class='col-xs-4'>
                        <?= $grantsLibrary->headerRowField('user_employment_date', $user_employment_date); ?>
                    </div>
                </div>
                <?php }?>

                <div class='form-group'>
                    <label class='col-xs-2 control-label'><?= get_phrase('switchable_user'); ?></label>
                    <div class='col-xs-4'>
                        <?= $grantsLibrary->headerRowField('user_is_switchable', $user_is_switchable); ?>
                    </div>
                </div>

                <div class='form-group'>
                    <div class='col-xs-12' style="text-align:center;">
                        <div id = "add_role" class="btn btn-default"><?=get_phrase('add_secondary_role');?></div>
                    </div>
                </div>

                <!-- <div class='form-group'> -->
                <div class='col-xs-12 user_message'>

                </div>
                <!-- </div> -->
                

                <div class='form-group'>
                    <div class='col-xs-12'>
                            <table id = 'secondary_roles' class = "table table-striped">
                                <thead>
                                    <tr>
                                        <th colspan="3"><?=get_phrase('secondary_roles', 'User Secondary Roles');?></th>
                                    </tr>
                                    <tr>
                                        <th><?=get_phrase('action');?></th>
                                        <th><?=get_phrase('role_name');?></th>
                                        <th><?=get_phrase('role_expiry_date');?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($user_seconday_roles as $secondary_role_id => $user_seconday_role){?>
                                        <tr>
                                            <td>
                                                <div class="btn btn-danger remove_role"><?=get_phrase('remove_role');?></div>
                                            </td>
                                            <td>
                                                <select name="header[secondary_role_ids][]" class='form-control secondary_role_ids required input_user fk_context_definition_id select2 select2-offscreen'>
                                                    <?php
                                                    
                                                    if (count($all_context_roles) > 0) {
                                                        foreach ($all_context_roles as $role_id => $role_name) {
                                                            if(array_key_exists($role_id, $user_primary_role)) continue;
                                                            $selected = '';
                                                            if($secondary_role_id == $role_id){
                                                                $selected = 'selected';
                                                            }
                                                    ?>
                                                            <option value='<?= $role_id; ?>' <?=$selected;?> ><?= $role_name; ?></option>
                                                    <?php 
                                                        }
                                                    } ?>

                                                </select>
                                            </td>
                                            <td>
                                                <input name="header[expiry_dates][]" type="text" onkeydown="return false;" class="form-control expiry_date"  value="<?=$user_seconday_role['expiry_date'];?>" />
                                            </td>
                                        </tr>
                                    <?php }?>
                                </tbody>
                            </table>
                    </div>
                </div>

                <div class='form-group'>
                    <div class='col-xs-12' style='text-align:center;'>

                        <div class='btn btn-default btn-save'> <?= get_phrase('save_changes'); ?></div>
                        <div class='btn btn-default btn-cancel'><?= get_phrase('cancel') ?></div>
                    </div>
                </div>

                </form>
            </div>
        </div>
    </div>
</div>

<script>


    //Redraw the user context office dropdown
    $(document).on('change', '#fk_context_definition_id', function(e) {

        let user_context_level =  <?=$user_context_level;?>;
        let context_office = $(this).val()
        // alert(user_context_level)
        if(user_context_level < 4){
            $(this).val($(this).data('context_definition_id'));
        }else if (context_office == 0) {

            $(this).attr('disabled', 'disabled');

            $("#user_email").val('');

            //Reload the select user context office dropdown and disable it
            let empty_array_for_offices_or_roles_or_departments_designation = [];

            //build_office_context_element(offices, true);

            draw_select2_dropdown('fk_user_context_office_id', 'fk_user_context_office_id', 'div_office_context', empty_array_for_offices_or_roles_or_departments_designation);

            draw_select2_dropdown('department', 'department', 'div_user_department', empty_array_for_offices_or_roles_or_departments_designation);

            draw_select2_dropdown('fk_role_id', 'primary_role_id', 'div_user_role', empty_array_for_offices_or_roles_or_departments_designation);

            draw_select2_dropdown('designation', 'designation', 'div_user_designation', empty_array_for_offices_or_roles_or_departments_designation);

            draw_select2_dropdown('fk_country_currency_id', 'fk_country_currency_id', 'div_country_currency_id', empty_array_for_offices_or_roles_or_departments_designation);


            $('#fk_user_context_office_id').attr('disabled', 'disabled');

            $('#department').attr('disabled', 'disabled');

            $('#fk_role_id').attr('disabled', 'disabled');

            $('#designation').attr('disabled', 'disabled');

            $('#fk_country_currency_id').attr('disabled', 'disabled');


            return false;

        } else {

            get_common_data_and_build_dropdown(context_office)

        }
    });

    //Method to build the select tag element
    function get_common_data_and_build_dropdown(context_office) {
        let get_user_data = "<?= base_url() ?>user/get_user_data/" + context_office

        //Get data asynchronously 
        $.get(get_user_data, function(response) {

            let json_data = JSON.parse(response);
            let {offices, departments, context_roles, designations, account_systems, country_currency} = json_data

            draw_select2_dropdown('fk_user_context_office_id', 'fk_user_context_office_id', 'div_office_context', offices)
            draw_select2_dropdown('department', 'department', 'div_user_department', departments)
            draw_select2_dropdown('fk_role_id', 'primary_role_id', 'div_user_role', context_roles)
            draw_select2_dropdown('designation', 'designation', 'div_user_designation', designations)

            //Populate Country Currency  and Account Systems
            let system_admin = '<?= $session->system_admin; ?>';

            if (system_admin != 0) {
                draw_select2_dropdown('fk_account_system_id', 'fk_account_system_id', 'div_account_system_id', account_systems);
                draw_select2_dropdown('fk_country_currency_id', 'fk_country_currency_id', 'div_country_currency_id', country_currency);
            }
    });
    }

    //Method to build the select tag element
    function get_data_and_build_dropdown(url_link, tag_unique_id, tag_unique_name, div_id_to_put_select_tag) {

        //Get data asynchronously 
        $.get(url_link, function(response) {

            let json_data = JSON.parse(response);

            draw_select2_dropdown(tag_unique_id, tag_unique_name, div_id_to_put_select_tag, json_data);

        });
    }


    //Select2 Tag [Method to build the select tag element]
    function draw_select2_dropdown(select_tag_id, select_tag_name, tag_id_to_attach_selecttag_html, data) {

        if(select_tag_id == 'fk_user_context_office_id'){
            console.log(data)
        }

        var element_str = "<div class=" + "'input-group'>"
        element_str += "<span class=" + "'input-group-addon'>";
        element_str += "<i style=" + "'color:red'  class=" + "'fa fa-asterisk'></i></span>";
        element_str +=  "<select ";
        element_str +=  "id='" + select_tag_id + "'";
        
        let multiple = "";
        let attr_name_def = " name='header[" + select_tag_name + "]' "
        if($("#" + select_tag_id).attr('multiple')){
            attr_name_def = " name='header[" + select_tag_name + "][]' ";
            multiple = "multiple";
        }

        element_str += attr_name_def
        
        element_str += "class='form-control select2 visible' " + multiple + " >";

        element_str +=  "<option value='0'><?= get_phrase('select_records'); ?></option>";

        $.each(data, function(index, value) {

            element_str = element_str + "<option value=" + index + ">" + value + "</option>";
        });

        element_str +=  "</select></div>";

        $('#' + tag_id_to_attach_selecttag_html).html(element_str);

        //Get the <select> tag and add the select2() method when dynamically creating an drop element; Code 
        $("select").select2();

    }

    //Prevent form from submitting to itselef
    $(document).ready(function() {
        $(window).keydown(function(event) {
            if (event.keyCode == 13) {
                event.preventDefault();
                return false;
            }
        });
    });

    //Check the validity of email
    $(document).on('change', "#user_email", function() {

        // alert($(this).val());
        if ($(this).val() != '') {

            $('#fk_context_definition_id').removeAttr('disabled');

            var preferred_user_name = $(this).val();

        }
        var user_email = $(this);
        var user_name = $("#user_email");
        let url = "<?= base_url(); ?>ajax/user/checkIfEmailIsUsed";
        let data = {
            'user_email': $(this).val(),
            'user_name': preferred_user_name
        };

        $.ajax({
            url: url,
            type: "POST",
            data: data,
            success: function(is_valid_email) {

                if (is_valid_email) {

                    $('#user_name').prop('value', preferred_user_name);
                } else {
                    alert('Invalid email "' + user_email.val() + '" or username "' + user_name.val() + '"');
                    user_email.val(null);
                    user_name.val(null);
                }


            }
        });

    });

    $("#confirm_user_password, #user_password").on('change', function() {
        var confirm_pass = $("#confirm_user_password").val();
        var pass = $("#user_password").val();

        if (confirm_pass.localeCompare(pass) == 0 && confirm_pass.length > 0 && pass.length > 0) {
            $(".user_message").html(null);
        } else {
            $(".user_message").html('Password mismatch');
            $("#confirm_user_password").val(null);
        }

    });

    $(document).ready(function() {
        $(".user_message").html(null);
        // $("#confirm_user_password, #user_password, #user_email").val(null);
        $('.expiry_date').datepicker({
            format:'yyyy-mm-dd',
            startDate: '<?=date('Y-m-d');?>'
        })
    });

    $(document).on('change', ".form-control, select2", function() {
        $(this).removeAttr('style');
    });

    $(".btn-save").on('click', function(ev) {

        let btn = $(this);
        let url = "<?= base_url(); ?>user/edit/<?=hash_id($user_id,'encode');?>";
        let data = $("#frm_edit_user").serializeArray();
        
        // Validate fields missing

        const form_controls = $(".form-control");

        let count_validation_errors = 0;

        $.each(form_controls, function(index, elem) {

            if ($(elem).hasClass('select2') && $(elem).text().trim().length == 0) {
                count_validation_errors += 1;
                $(elem).css('border', 'red solid 1px');
                console.log(elem)
            } else if (!$(elem).hasClass('select2') && ($(elem).val() == "" || $(elem).val() == 0)) {
                count_validation_errors += 1;
                $(elem).css('border', 'red solid 1px');
                console.log(elem)
            } else {
                $(elem).removeAttr('style');
            }

        });

        if (count_validation_errors > 0) {
            alert("Some field are empty");
            return false;
        }

        $.post(url, data, function(response) {
            alert(response.message);
            if(response.flag){
                if (btn.hasClass('btn-save')) {
                    location.href = document.referrer
                } else {
                    reset_form();
                }
            }
        });

        ev.preventDefault();

    });

    $(".btn-cancel").on('click', function(ev) {
        //Redirect back to view page
        let url = window.location.href;
        let record_id=url.split("/").pop();
        let reconstruct_url='<?= base_url();?>/user/view/'+record_id;
        location.href=reconstruct_url;
        ev.preventDefault();
    });

    function reset_form() {
        $('input').val(null);


        // $("#fk_office_id").select2("val","");
        $("#fk_context_definition_id").select2("val", "");
        $("#user_is_context_manager").select2("val", "");
        $("#user_is_context_manager").val(1).prop('selected', true);
        $("#user_is_system_admin").val(1).prop('selected', true);
        $('#fk_account_system_id').select2("val", "");
        $('#designation').val('');
        $('#fk_country_currency_id').select2("val", "");
        $("#fk_language_id").select2("val", "");
        $("#user_is_active").select2("val", "");
        $("#fk_role_id").select2("val", "");
        $("#user_password").val(null).prop('selected', true);
        $("#confirm_user_password").val(null).prop('selected', true);

        $("#office_context").empty().prop('disabled', 'disabled');
        $("#department").empty().prop('disabled', 'disabled');

        $(".user_message").html(null);
    }

    $('#fk_user_context_office_id').on('change', function () {
        $('#office_context_changed').val(1)
    })

    $(document).on('click','#add_role', function () {
        const secondary_roles_table = $('#secondary_roles');
        const row = '<tr><td>' + action_button() + '</td><td>' + role_options() + '</td><td>' + expiry_date() + '</td></tr>';

        secondary_roles_table.find('tbody').append(row)

        $('.expiry_date').datepicker({
            format:'yyyy-mm-dd',
            startDate: '<?=date('Y-m-d');?>'
        })
    })

    function action_button(){
        return '<div class = "btn btn-danger remove_role"><?=get_phrase('remove_role');?></div>';
    }

    function role_options(){
        const all_context_roles = JSON.parse ('<?=json_encode($all_context_roles);?>')
        const primary_role = JSON.parse('<?=json_encode(array_keys($user_primary_role));?>')
        let options = '<option value=""><?=get_phrase('select_role');?></option>';

        $.each(all_context_roles, function (role_id, role_name){
            if(!inArray(role_id, primary_role)){
                options += '<option value = "' + role_id + '">' + role_name + '</option>';
            }
        });

        return '<select name="header[secondary_role_ids][]" class = "form-control">' + options + '</select>';
    }

    function expiry_date(){
        return '<input name="header[expiry_dates][]" type = "text" class = "form-control expiry_date" />';
    }

    $(document).on('click','.remove_role', function () {

        const conf = confirm('<?=get_phrase('confirm_role_user_detach','Are you sure you want to detach this role from this user');?>')
       
        if(!conf){
            alert('<?=get_phrase('process_aborted');?>')
            return false;
        }

        $(this).closest('tr').remove()
    })

    function inArray(needle, haystack) {
        let length = haystack.length;
        for(var i = 0; i < length; i++) {
            if(haystack[i] == needle) return true;
        }
        return false;
    }

    $('#user_unique_identifier').on('change', function  () {
        //alert('Hello')
        const user_unique_identifier = $(this).val()
        const url = '<?=base_url();?>user/verify_user_unique_identifier'
        const unique_identifier_id = '<?=$unique_identifier;?>'
        const data = {
            user_unique_identifier,
            unique_identifier_id
        }

        $('.user_message').html('')
        $('#user_unique_identifier').removeAttr('style')

        $.post(url, data, function (response) {
            const obj = JSON.parse(response)
            if(obj.status){
                $message = '<?=get_phrase('duplicate_identifier','Duplicate user identification is not allowed');?></br>'

                $.each(obj.records, function (index, elem) {
                    $message += elem.user_firstname + ' ' + elem.user_lastname + ' [' + elem.user_email + ']</br>'
                })

                $('.user_message').html($message)
                
                $('#user_unique_identifier').val('');
                $('#user_unique_identifier').css('border','1px red solid');
            }
        })
    })
</script>