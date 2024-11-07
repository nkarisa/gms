<?php

$grantsLibrary = new \App\Libraries\System\GrantsLibrary();
$accountSystemLibrary = new \App\Libraries\Core\AccountSystemLibrary();
$countryCurrencyLibrary = new \App\Libraries\Grants\CountryCurrencyLibrary();

extract($result);
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
                    <?php echo get_phrase('add_user'); ?>
                </div>
            </div>

            <div class="panel-body" style="max-width:50; overflow: auto;">

                <?php echo form_open("", array('id' => 'frm_user', 'class' => 'form-horizontal form-groups-bordered validate', 'enctype' => 'multipart/form-data')); ?>

                <div class='form-group'>
                    <div class='col-xs-12 user_message'>

                    </div>
                </div>

                <div class='form-group'>
                    <div class='col-xs-12' style='text-align:center;'>
                        <button class='btn btn-default btn-reset'><?= get_phrase('reset', 'Reset'); ?></button>
                        <button class='btn btn-default btn-save'><?= get_phrase('save'); ?></button>
                        <button
                            class='btn btn-default btn-save-new'><?= get_phrase('save_and_new', 'Save and New'); ?></button>
                    </div>
                </div>

                <div class='form-group'>
                    <label class='col-xs-2 control-label'><?= get_phrase('first_name'); ?></label>
                    <div class='col-xs-4'>
                        <?= $grantsLibrary->headerRowField('user_firstname'); ?>
                    </div>

                    <label class='col-xs-2 control-label'><?= get_phrase('last_name'); ?></label>
                    <div class='col-xs-4'>
                        <?= $grantsLibrary->headerRowField('user_lastname'); ?>
                    </div>
                </div>

                <div class='form-group'>


                    <label class='col-xs-2 control-label'><?= get_phrase('email'); ?></label>
                    <div class='col-xs-4'>
                        <?= $grantsLibrary->headerRowField('user_email'); ?>
                    </div>

                    <!-- Context Definition -->

                    <label
                        class='col-xs-2 control-label'><?= get_phrase('user_context_definition', 'User Context Definition'); ?></label>
                    <div class='col-xs-4'>
                        <!-- Populate the context offices -->
                        <div class='input-group'>
                            <span class='input-group-addon'>
                                <i style="color:red" class='fa fa-asterisk'></i>

                            </span>
                            <select id='fk_context_definition_id' name="header[fk_context_definition_id]"
                                class='form-control master required input_user fk_context_definition_id select2 select2-offscreen visible'
                                disabled='disabled'>
                                <option value='0'><?= get_phrase('select_context_office'); ?></option>
                                <?php
                                foreach ($all_context_offices as $key => $all_context_office) {
                                    $redescribe_office_compassion_way = '';
                                    switch ($key) {
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

                                    <option value="<?= $key ?>"><?= $redescribe_office_compassion_way; ?></option>

                                <?php } ?>

                            </select>
                        </div>
                    </div>


                    <label class='col-xs-2 control-label hidden'><?= get_phrase('preferred_username'); ?></label>
                    <div class='col-xs-4 hidden'>
                        <?= $grantsLibrary->headerRowField('user_name'); ?>
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

                            <select id='fk_user_context_office_id' name="header[fk_user_context_office_id]"
                                class='form-control master required input_user fk_user_context_office_id select2 select2-offscreen visible'
                                disabled='disabled'>
                                <option value='0'><?= get_phrase('select_user_office_context'); ?></option>

                            </select>
                        </div>

                    </div>

                    <!-- Department -->
                    <label class='col-xs-2 control-label'><?= get_phrase('user_department'); ?></label>
                    <div class='col-xs-4' id='div_user_department'>
                        <div class='input-group'>
                            <span class='input-group-addon'>
                                <i style="color:red" class='fa fa-asterisk'></i>

                            </span>
                            <select id='department' name="header[department]"
                                class='form-control master required input_user department select2 select2-offscreen visible'
                                disabled='disabled'>
                                <option value='0'><?= get_phrase('select_records'); ?></option>

                            </select>
                        </div>
                    </div>

                </div>

                <?php if ($session->system_admin) { ?>

                    <div class='form-group'>
                        <label class='col-xs-2 control-label'><?= get_phrase('is_user_context_manager'); ?></label>
                        <div class='col-xs-4'>
                            <?= $grantsLibrary->headerRowField('user_is_context_manager', 0); ?>
                        </div>

                        <label class='col-xs-2 control-label'><?= get_phrase('is_user_system_administrator'); ?></label>
                        <div class='col-xs-4'>
                            <?= $grantsLibrary->headerRowField('user_is_system_admin', 0, $session->system_admin ? false : true); ?>
                        </div>

                    </div>

                <?php } ?>

                <!-- <div class='form-group'> -->
                <!-- User Language -->
                <label class='col-xs-2 control-label hidden'><?= get_phrase('user_default_language'); ?></label>
                <div class='col-xs-4 hidden'>
                    <?= $grantsLibrary->headerRowField('language_name', 1); ?>
                </div>


                <!-- <label class='col-xs-2 control-label'>Is User Active</label>
                    <div class='col-xs-4'>
                        <?php //echo $grantsLibrary->headerRowField('user_is_active', 1); ?>
                    </div> -->
                <!-- </div> -->

                <div class='form-group'>
                    <!-- User Role -->
                    <label class='col-xs-2 control-label'><?= get_phrase('user_role'); ?></label>
                    <div class='col-xs-4' id='div_user_role'>
                        <div class='input-group'>
                            <span class='input-group-addon'>
                                <i style="color:red" class='fa fa-asterisk'></i>

                            </span>

                            <select id='fk_role_id' name="header[fk_role_id]" disabled='disabled'
                                class='form-control master required input_user fk_role_id select2 select2-offscreen visible'>
                                <option value='0'><?= get_phrase('select_records'); ?></option>

                            </select>
                        </div>
                    </div>
                    <!-- User Designation -->
                    <label class='col-xs-2 control-label'><?= get_phrase('user_designation'); ?></label>
                    <div class='col-xs-4' id='div_user_designation'>
                        <div class='input-group'>
                            <span class='input-group-addon'>
                                <i style="color:red" class='fa fa-asterisk'></i>

                            </span>
                            <select id='designation' name="header[designation]" disabled='disabled'
                                class='form-control master required input_user select2 select2-offscreen visible'>
                                <option value='0'><?= get_phrase('select_records'); ?></option>

                            </select>
                        </div>

                    </div>


                    <!-- Country Currency -->
                    <?php

                    $hidden = !$session->system_admin ? 'hidden' : '';

                    ?>
                    <label class='col-xs-2 control-label <?= $hidden; ?>'><?= get_phrase('currency'); ?></label>
                    <div class='col-xs-4 <?= $hidden; ?>' id='div_country_currency_id'>
                        <div class='input-group'>
                            <span class='input-group-addon'>
                                <i style="color:red" class='fa fa-asterisk'></i>

                            </span>

                            <?php
                            if ($session->system_admin) { ?>

                                <select id='fk_country_currency_id' name="header[fk_country_currency_id]"
                                    class='form-control master required input_user select2 select2-offscreen visible'>
                                    <option value='0'><?= get_phrase('select_records'); ?></option>

                                </select>

                            <?php } else {

                                $currencies = $countryCurrencyLibrary->getCountryCurrency();

                                foreach ($currencies as $currency_id_key => $currency) { ?>

                                    <input id="currency_id" value="<?= $currency_id_key; ?>" required="required" type="text"
                                        class="form-control" name="header[currency_id]">
                                <?php }
                            } ?>
                        </div>

                    </div>

                </div>

                <!-- Account System -->
                <div class='form-group <?= $hidden; ?>'>
                    <label class='col-xs-2 control-label'><?= get_phrase('user_accounting_system'); ?></label>
                    <div class='col-xs-4' id='div_account_system_id'>
                        <div class='input-group'>
                            <span class='input-group-addon'>
                                <i style="color:red" class='fa fa-asterisk'></i>

                            </span>
                            <?php
                            if ($session->system_admin) { ?>

                                <select id='fk_account_system_id' name="header[fk_account_system_id]"
                                    class='form-control master required input_user select2 select2-offscreen visible '>
                                    <option value='0'><?= get_phrase('select_records'); ?></option>

                                </select>

                            <?php } else {

                                $account_systems = $accountSystemLibrary->getAccountSystems();

                                foreach ($account_systems as $account_system_id_key => $account_system) {
                                    ?>

                                    <!-- <input id="account_system_id" value="<?= $account_system_id_key; ?>" required="required" type="text" class="form-control" name="header[account_system_id]"> -->
                                <?php
                                }
                            } ?>
                        </div>

                    </div>
                </div>

                <div class='form-group fcp_user_only'>
                    <label class='col-xs-2 control-label'><?= get_phrase('employment_date'); ?></label>
                    <div class='col-xs-4'>
                        <?= $grantsLibrary->headerRowField('user_employment_date'); ?>
                    </div>

                    <!-- <label class='col-xs-2 control-label' id = "unique_identifier_label"><?= get_phrase('unique_identifier'); ?></label>
                    <div class='col-xs-2'>
                        <?php //echo $grantsLibrary->headerRowField('user_unique_identifier'); ?>
                    </div>
                    <div class='col-xs-2'>
                        <input class = 'form-control' type = 'file' name = 'unique_identifier_file' id = 'unique_identifier_file' />
                    </div> -->
                </div>

                <div class='form-group'>
                    <label class='col-xs-2 control-label'><?= get_phrase('user_password'); ?></label>
                    <div class='col-xs-4'>
                        <?= $grantsLibrary->passwordField('user_password'); ?>
                    </div>

                    <label class='col-xs-2 control-label'><?= get_phrase('confirm_password'); ?></label>
                    <div class='col-xs-4'>
                        <?= $grantsLibrary->passwordField('confirm_user_password'); ?>
                    </div>
                </div>

                <!-- <div class='form-group'> -->
                <div class='col-xs-12 user_message'>

                </div>
                <!-- </div> -->

                <div class='hidden' id='dynamic_form_fields'>
                    <input type="text" name="unique_identifier_id" id="unique_identifier_id" value='0' />
                </div>

                <div class='form-group'>
                    <div class='col-xs-12' style='text-align:center;'>
                        <button class='btn btn-default btn-reset'><?= get_phrase('reset'); ?></button>
                        <button class='btn btn-default btn-save'><?= get_phrase('save'); ?></button>
                        <button class='btn btn-default btn-save-new'><?= get_phrase('save_and_new'); ?></button>
                    </div>
                </div>

                </form>
            </div>
        </div>
    </div>
</div>

<script>
    
    //Redraw the user context office dropdown
    $(document).on('change', '#fk_context_definition_id', function () {

        let context_office = $(this).val()
        if (context_office == 0) {
            $(this).attr('disabled', 'disabled');
            $("#user_email").val('');
            //Reload the select user context office dropdown and disable it
            let empty_array_for_offices_or_roles_or_departments_designation = [];

            draw_select2_dropdown('fk_user_context_office_id', 'fk_user_context_office_id', 'div_office_context', empty_array_for_offices_or_roles_or_departments_designation);
            draw_select2_dropdown('department', 'department', 'div_user_department', empty_array_for_offices_or_roles_or_departments_designation);
            draw_select2_dropdown('fk_role_id', 'fk_role_id', 'div_user_role', empty_array_for_offices_or_roles_or_departments_designation);
            draw_select2_dropdown('designation', 'designation', 'div_user_designation', empty_array_for_offices_or_roles_or_departments_designation);
            draw_select2_dropdown('fk_country_currency_id', 'fk_country_currency_id', 'div_country_currency_id', empty_array_for_offices_or_roles_or_departments_designation);

            $('#fk_user_context_office_id').attr('disabled', 'disabled');
            $('#department').attr('disabled', 'disabled');
            $('#fk_role_id').attr('disabled', 'disabled');
            $('#designation').attr('disabled', 'disabled');
            $('#fk_country_currency_id').attr('disabled', 'disabled');

            return false;

        } else {

            //Populate Offices Dropdowns

            let url_for_offices = "<?= base_url() ?>ajax/user/getOfficesByAjax/" + context_office;
            get_data_and_build_dropdown(url_for_offices, 'fk_user_context_office_id', 'fk_user_context_office_id', 'div_office_context');

            //Populate Departments Dropdown
            let url_for_departments = '<?= base_url() ?>ajax/user/retrieveDepartmentsByAjax/' + context_office;
            get_data_and_build_dropdown(url_for_departments, 'department', 'department', 'div_user_department');

            //Populate Roles Dropdown
            let url_for_roles = "<?= base_url() ?>ajax/user/retrieveRolesByAjax/" + context_office;
            get_data_and_build_dropdown(url_for_roles, 'fk_role_id', 'fk_role_id', 'div_user_role');

            //Populate Designation Dropdown
            let url_for_designations = "<?= base_url() ?>ajax/user/retrieveDesignationsByAjax/" + context_office;
            get_data_and_build_dropdown(url_for_designations, 'designation', 'designation', 'div_user_designation');

            //Populate Country Currency  and Account Systems
            let system_admin = '<?= $session->system_admin; ?>';

            if (system_admin != 0) {

                //Account Systems
                let url_account_systems = "<?= base_url() ?>ajax/user/getAccountSystemsByAjax"
                get_data_and_build_dropdown(url_account_systems, 'fk_account_system_id', 'fk_account_system_id', 'div_account_system_id');
                //Country Currency
                let url_country_currency = "<?= base_url() ?>ajax/user/getCountryCurrencyByAjax";
                get_data_and_build_dropdown(url_country_currency, 'fk_country_currency_id', 'fk_country_currency_id', 'div_country_currency_id');
            }

        }
    });

    //Method to build the select tag element
    function get_data_and_build_dropdown(url_link, tag_unique_id, tag_unique_name, div_id_to_put_select_tag) {
        //Get data asynchronously 
        $.get(url_link, function (json_data) {
            draw_select2_dropdown(tag_unique_id, tag_unique_name, div_id_to_put_select_tag, json_data);
        });
    }


    //Select2 Tag [Method to build the select tag element]
    function draw_select2_dropdown(select_tag_id, select_tag_name, tag_id_to_attach_selecttag_html, data) {

        var element_str = "<div class=" + "'input-group'>"
        element_str = element_str + "<span class=" + "'input-group-addon'>";
        element_str = element_str + "<i style=" + "'color:red'  class=" + "'fa fa-asterisk'></i></span>";
        element_str = element_str + "<select ";
        element_str = element_str + "id='" + select_tag_id + "'";
        element_str = element_str + " name='header[" + select_tag_name + "]' ";
        element_str = element_str + "class='form-control select2 visible'  >";

        element_str = element_str + "<option value='0'><?= get_phrase('select_records'); ?></option>";

        $.each(data, function (index, value) {

            element_str = element_str + "<option value=" + index + ">" + value + "</option>";
        });

        element_str = element_str + "</select></div>";

        $('#' + tag_id_to_attach_selecttag_html).html(element_str);

        //Get the <select> tag and add the select2() method when dynamically creating an drop element; Code 
        $("select").select2();

    }

    //Prevent form from submitting to itselef
    $(document).ready(function () {
        $('#user_unique_identifier').prop('readonly', 'readonly');
        $(".user_message").html(null);
        $("#confirm_user_password, #user_password, #user_name, #user_email").val(null);

        $(window).keydown(function (event) {
            if (event.keyCode == 13) {
                event.preventDefault();
                return false;
            }
        });
    });

    //Check the validity of email
    $(document).on('change',"#user_email", function() {
        let preferred_user_name = ''
        if ($(this).val() != '') {
            $('#fk_context_definition_id').removeAttr('disabled');
            preferred_user_name = $(this).val();
        }
        let user_email = $(this);
        let user_name = $("#user_name");
        let url = "<?=base_url('ajax/user/checkIfEmailIsUsed'); ?>";
        let data = {
            'user_email': $(this).val(),
            'user_name': $("#user_name").val()
        };

        $.ajax({
            url: url,
            type: "POST",
            data: data,
            success: function(response) {
                if (response.valid_email) {
                    $('#user_name').prop('value', preferred_user_name);
                } else {
                    alert("The email '" + user_email.val() + "' already exists");
                    user_email.val(null);
                    user_name.val(null);
                }
            }
        });

    });


    $("#confirm_user_password, #user_password").on('change', function () {
        var confirm_pass = $("#confirm_user_password").val();
        var pass = $("#user_password").val();

        if (confirm_pass.localeCompare(pass) == 0 && confirm_pass.length > 0 && pass.length > 0) {
            $(".user_message").html(null);
        } else {
            $(".user_message").html('Password mismatch');
            $("#confirm_user_password").val(null);
        }

    });


    $(document).on('change', ".form-control, select2", function () {
        $(this).removeAttr('style');
    });

    $(document).on('click', ".btn-save, .btn-save-new", function (ev) {
        var btn = $(this);
        let url = "<?= base_url(); ?>user/singleFormAdd"
        let data = $("#frm_user").serializeArray();

        // Validate fields missing

        const form_controls = $(".form-control");
        let count_validation_errors = 0;

        $.each(form_controls, function (index, elem) {
            if (!$(elem).hasClass('eliminate')) {

                if ($(elem).hasClass('select2') && !$(elem).hasClass('select2-container') && $(elem).val() == 0) {
                    count_validation_errors += 1;
                    $(elem).parent().css('border', 'red solid 1px');
                    console.log(elem)
                } else if (!$(elem).hasClass('select2') && ($(elem).val() == "" || $(elem).val() == 0)) {
                    count_validation_errors += 1;
                    $(elem).css('border', 'red solid 1px');
                    console.log(elem)
                }
            }
        });

        if (count_validation_errors > 0) {
            alert("Some field are empty");
            return false;
        }

        $.ajax({
            url: url,
            data: data,
            type: "POST",
            success: function (response) {
                alert(response.message);
                if(response.flag){
                    if (btn.hasClass('btn-save')) {
                        location.href = document.referrer
                    } else {
                        reset_form();
                    }
                }
            }
        });

        ev.preventDefault();

    });

    $(".btn-reset").on('click', function (ev) {
        reset_form();
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

    $(document).on('change', '#fk_user_context_office_id', function () {
        const context_office_id = $(this).val()
        const context_definition_id = $('#fk_context_definition_id').val()

        if (!context_definition_id) {
            alert('<?= get_phrase('choose_context_definition', 'Choose context definition before you proceed'); ?>');
            return false;
        }

        const url = '<?= base_url(); ?>ajax/unique_identifier/getOfficeAllowedUniqueIdentifierByAjax'
        const data = {
            context_definition_id,
            context_office_id
        }

        $.post(url, data, function (id_obj) {
            const unique_identifier_name = id_obj.unique_identifier_name
            const unique_identifier_id = id_obj.unique_identifier_id

            if (context_definition_id == 1 && unique_identifier_id > 0) {
                $('.fcp_user_only').removeClass('hidden')
                $('.fcp_user_only').find('input').removeAttr('readonly')
                $('#unique_identifier_id').val(unique_identifier_id)
                $('#unique_identifier_label').html(unique_identifier_name);
            } else {
                $('#user_employment_date, #unique_identifier_id, #user_unique_identifier, #unique_identifier_file').addClass('eliminate')
            }

        });
    })

    $(document).on('change', '#fk_context_definition_id', function () {
        const context_definition_id = $(this).val()

        if (context_definition_id != 1) {
            $('.fcp_user_only').find('input').val('')
            $('.fcp_user_only').addClass('hidden')
        }

    })

    $('#user_unique_identifier').on('change', function () {
        const user_unique_identifier = $(this).val()
        const url = '<?= base_url(); ?>user/verify_user_unique_identifier'
        const unique_identifier_id = $('#unique_identifier_id').val()
        const data = {
            user_unique_identifier,
            unique_identifier_id
        }

        $('.user_message').html('')
        $('#user_unique_identifier').removeAttr('style');

        $.post(url, data, function (response) {
            const obj = JSON.parse(response)
            if (obj.status) {
                $message = '<?= get_phrase('duplicate_identifier', 'Duplicate user identification is not allowed'); ?></br>'

                $.each(obj.records, function (index, elem) {
                    $message += elem.user_firstname + ' ' + elem.user_lastname + ' [' + elem.user_email + ']</br>'
                })

                $('.user_message').html($message)

                $('#user_unique_identifier').val('');
                $('#user_unique_identifier').css('border', '1px red solid');
            }
        })
    })
</script>