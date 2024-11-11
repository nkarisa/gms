<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Safina Grants Management System" />
    <meta name="author" content="" />
    <title><?= get_phrase('login'); ?> | <?php echo $system_title; ?></title>
    <link rel="stylesheet"
        href="<?php echo base_url(); ?>assets/js/jquery-ui/css/no-theme/jquery-ui-1.10.3.custom.min.css">
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/font-icons/entypo/css/entypo.css">
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/bootstrap.css">
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/neon-core.css">
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/neon-theme.css">
    <!-- <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/neon-forms.css"> -->
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/custom.css">
    <script src="<?php echo base_url(); ?>assets/js/jquery-1.11.0.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
    <link href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet"
        type="text/css" />
    <script src="https://code.jquery.com/jquery-1.11.0.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"
        integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
    <!-- <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script> -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link rel="shortcut icon" href="<?php echo base_url(); ?>assets/images/favicon.ico">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css" />
</head>
<body class="page-body login-page login-form-fall" data-url="http://neon.dev">
    </div>
    <!-- This is needed when you send requests via Ajax -->
    <script type="text/javascript">
        var baseurl = '<?php echo base_url(); ?>';
    </script>
    <div class='col-xs-12' id="create_account_div">
        <div class="login-form">
            <form method="post" role="form" id="create_account">
                <div class='col-xs-12' style="text-align:center;">
                    <h2 style="color:#000; font-weight:100; ">
                        <?php echo $system_name; ?>
                        <!-- <a href=""><img src="https://fontmeme.com/permalink/230920/d8521944a6cdcfd4810ee548c53cce80.png" alt="3d-fonts" border="0"></a> -->
                    </h2>
                </div>
                <!-- Overlay -->
                <div class='hidden' id="overlay">
                    <img src='<?php echo base_url() . "images/uploads/preloader4.gif"; ?>' />
                </div>
                <div class='row'>
                    <div class="col-xs-6">
                        <!-- User Country -->
                        <div class="form-group ">
                            <div class='input-group' style="background-color:#D6E1F3; padding-right :5px">
                                <i style="color:red" class='fa fa-asterisk'></i>
                                <select class="form-control required select2" name="user_country" id="user_country">
                                    <option value='0'><?= get_phrase('your_country', 'Your Country'); ?></option>
                                    <?php
                                    //All Safina countries
                                    foreach ($countries as $key => $country) {
                                        if ($key == 1 || $key == 2)
                                            continue; //remove global and africa
                                        ?>
                                        <option value='<?= $key ?>'><?= get_phrase($country . $key, $country); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <!-- User Type e.g. Admin, PF -->
                        <div class="form-group ">
                            <div class='input-group' style="background-color:#D6E1F3; padding-right :5px">
                                <i style="color:red; " class='fa fa-asterisk'></i>
                                <select class="form-control required select2" name="user_type" id="user_type"
                                    disabled='disabled'>
                                    <option value='0'>User Type</option>
                                    <option value='1'>FCP Staff</option>
                                    <option value='2'>PF Staff</option>
                                    <option value='3'>MOP Staff</option>
                                    <option value='4'>Country Admin</option>
                                    <option value='5'>National Office Staff</option>
                                </select>
                            </div>
                        </div>
                        <!-- User Office -->
                        <div class="form-group ">
                            <div class='input-group' style="background-color:#D6E1F3; padding-right :5px">
                                <i style="color:red; " class='fa fa-asterisk'></i>
                                <select class="form-control required select2" name="user_office" id="user_office"
                                    disabled='disabled'>
                                    <option value='0'>FCP/Cluster/Region/Country</option>
                                </select>
                            </div>
                        </div>
                        <!-- User Department -->
                        <div class="form-group ">
                            <div class='input-group' style="background-color:#D6E1F3; padding-right :5px">
                                <i style="color:red; " class='fa fa-asterisk'></i>
                                <select class="form-control required select2 " name="user_department"
                                    id="user_department" disabled='disabled'>
                                    <option value='0'>Department</option>
                                    <!-- Other options are added by ajax call-->
                                </select>
                            </div>
                        </div>
                        <!-- User Role -->
                        <div class="form-group ">
                            <div class='input-group' style="background-color:#D6E1F3; padding-right :5px">
                                <i style="color:red; " class='fa fa-asterisk'></i>
                                <select class="form-control required select2" name="user_role" id="user_role"
                                    disabled='disabled'>
                                    <option value='0'>Role</option>
                                    <!-- Other roles options -->
                                </select>
                            </div>
                        </div>
                        <!-- User Designation -->
                        <div class="form-group ">
                            <div class='input-group' style="background-color:#D6E1F3; padding-right :5px">
                                <i style="color:red; " class='fa fa-asterisk'></i>
                                <select class="form-control required select2" name="user_designation"
                                    id="user_designation" disabled='disabled'>
                                    <option value='0'>Designation</option>
                                    <!-- Other designition options -->
                                </select>
                            </div>
                        </div>
                        <!-- Hidden Fields -->
                        <script>
                            $(document).ready(function () {
                                //Draw Country Currency Hidden Field 
                                draw_secure_hidden_html_input_box('country_currency', 'country currency');
                                //Draw Country Language Hidden Field 
                                draw_secure_hidden_html_input_box('country_language', 'country language');
                                //Draw user_activator_ids e.g. fk_cluster_context_id when FCP office is selected Hidden Field 
                                draw_secure_hidden_html_input_box('user_activator_ids', 'next office on hierachy');
                            })
                        </script>
                    </div>
                    <div class="col-xs-6">
                        <!-- First Name -->
                        <div class="form-group ">
                            <div class='input-group' style="background-color:white;">
                                <i style="color:red; " class='fa fa-asterisk'></i>
                                <input type="text" style="background-color:white; color:black"
                                    class="form-control required" name="first_name" id="first_name"
                                    placeholder="Your First Name" autocomplete="off" />
                            </div>
                        </div>
                        <!-- Surname -->
                        <div class="form-group ">
                            <div class='input-group' style="background-color:white;">
                                <i style="color:red; " class='fa fa-asterisk'></i>
                                <input style="background-color:white; color:black" type="text"
                                    class="form-control required" name="surname" id="surname" placeholder="Your Surname"
                                    autocomplete="off" />
                            </div>
                        </div>
                        <!-- Email -->
                        <div class="form-group">
                            <div class='input-group' style="background-color:white;">
                                <i style="color:red; " class='fa fa-asterisk'></i>
                                <input style="background-color:white; color:black; padding-left :5px" type="text"
                                    class="form-control required" name="email" id="email" placeholder="Your Email"
                                    autocomplete="off" data-mask="email" />
                                <i id='email_txt' class="bi bi-envelope"
                                    style="margin-left: -20px; color:black; font-size:20px ;padding-right :5px"></i>
                            </div>
                            <div id='email_password_div'
                                style="color:red; size:20px; font-family:Georgia, 'Times New Roman', Times, serif;">
                                <!-- Populated by ajax -->
                            </div>
                        </div>
                        <!-- Password -->
                        <div class="form-group ">
                            <div class='input-group' style="background-color:white;">
                                <i style="color:red; " class='fa fa-asterisk'></i>
                                <input style="background-color:white; color:black; padding-left :5px" type="password"
                                    class="form-control required" name="password" id="password" placeholder="Password"
                                    autocomplete="off" />
                                <i class="bi bi-eye-slash" id="togglePassword"
                                    style="margin-left: -20px; cursor: pointer; color:black; font-size:20px; padding-right :5px"></i>
                            </div>
                            <div id='weak_password_div'
                                style="color:red; size:20px; font-family:Georgia, 'Times New Roman', Times, serif;">
                                <!-- Populated by ajax -->
                            </div>
                        </div>
                        <!-- Confirm Password -->
                        <div class="form-group">
                            <div class='input-group' style="background-color:white;">
                                <i style="color:red; " class='fa fa-asterisk'></i>
                                <input style="background-color:white; color:black; padding-left :5px" type="password"
                                    class="form-control required" name="confirm_password" id="confirm_password"
                                    placeholder="Confirm Password" disabled autocomplete="off" />
                                <i class="bi bi-eye-slash" id="toggleConfirmPassword"
                                    style="margin-left: -20px; ;cursor: pointer; color:black; font-size:20px; padding-right :5px"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <center>
                    <!-- <div class="g-recaptcha" data-sitekey="6LehIc4nAAAAAJ19j40qW2hJt0LYooSq1g4RlZmu"></div> -->
                    <div class="form-group col-xs-12">
                        <button id='post_account_created' type="submit" class="btn btn-primary"
                            style="border-radius:10px">
                            <i class=""></i>
                            Create Account
                        </button>
                        <a href="<?= base_url() ?>login" type="submit" class="btn btn-primary"
                            style="border-radius:10px">
                            <i class=""></i>
                            Back To Login
                        </a>
                    </div>
                </center>
        </div>
        </form>
    </div>
    </div>
    <!-- Bottom Scripts -->
    <script src="<?php echo base_url(); ?>assets/js/gsap/main-gsap.js"></script>
    <script src="<?php echo base_url(); ?>assets/js/jquery-ui/js/jquery-ui-1.10.3.minimal.min.js"></script>
    <script src="<?php echo base_url(); ?>assets/js/bootstrap.js"></script>
    <script src="<?php echo base_url(); ?>assets/js/joinable.js"></script>
    <script src="<?php echo base_url(); ?>assets/js/resizeable.js"></script>
    <script src="<?php echo base_url(); ?>assets/js/neon-api.js"></script>
    <script src="<?php echo base_url(); ?>assets/js/jquery.validate.min.js"></script>
    <script src="<?php echo base_url(); ?>assets/js/neon-login.js"></script>
    <script src="<?php echo base_url(); ?>assets/js/neon-custom.js"></script>
    <script src="<?php echo base_url(); ?>assets/js/neon-demo.js"></script>
    <style>
        #create_account_div {
            height: 100vh;
            display: grid;
            place-items: center;
        }
        #create_account_div .login-form {
            min-width: 50vw;
        }
        /* #create_account_div .select2-selection.select2-selection--single{
        width:100%;
    } */
        #create_account_div .input-group {
            display: flex;
            justify-content: space-between;
        }
        #create_account_div .select2.select2-container.select2-container--default.select2-container--disabled.select2-container--focus {
            flex: 1 !important;
        }
        form {
            /* background-color: #EDF0F5; */
            background-color: #D6E1F3;
            padding: 20px;
            border-radius: 15px;
            border-width: 5px;
            border-color: blue;
            border-style: double;
        }
    </style>
</body>
</html>
<script type="text/javascript">
    //Instialize the select2 and Load Countries/Account Systems
    $(document).ready(function () {
        $("select").select2();
    });

    //Password eye Toggling
    const togglePassword = document.querySelector("#togglePassword");
    const password = document.querySelector("#password");
    passwordEyeToggling(togglePassword, password);
    
    //Confirm password eye Toggling
    const toggleConfirmPassword = document.querySelector("#toggleConfirmPassword");
    const confirm_password = document.querySelector("#confirm_password");
    passwordEyeToggling(toggleConfirmPassword, confirm_password);
    
    // prevent form submit
    const form = document.querySelector("form");
    form.addEventListener('submit', function (e) {
        e.preventDefault();
    });
    
    //Make Password to show visible text or hashed
    function passwordEyeToggling(togglePassword, password) {
        togglePassword.addEventListener("click", function () {
            // toggle the type attribute
            const type = password.getAttribute("type") === "password" ? "text" : "password";
            password.setAttribute("type", type);
            // toggle the icon
            this.classList.toggle("bi-eye");
        });
    }
    
    //Javascript hidden Inputs field for data integrity security
    function draw_secure_hidden_html_input_box(elementID, inputValue) {
        let form = document.getElementById("create_account");
        let input = document.createElement("input");
        input.type = "text";
        input.name = elementID;
        input.value = inputValue;
        input.id = elementID;
        input.className = 'required';
        input.hidden = true
        form.appendChild(input);
    }
    
    //Email verification
    $(document).on('change', '#email', function () {
        let data = {
            email: $(this).val(),
        };
        let url = '<?= base_url() ?>ajax/login/verifyValidEmail';
        $('#overlay').removeClass('hidden');
        $.post(url, data, function (res) {
            if (parseInt(res) == 0) {
                //Hide create password button and Show email_password_div
                $('#post_account_created').hide();
                $('#email_password_div').html('Invalid Email');
                $('#overlay').addClass('hidden');
            } else {
                //Show create password button and Hide email_password_div
                $('#post_account_created').show();
                $('#email_password_div').html('');
                //Check if email already exists
                emailExists(data);
            }
        });
    });
    
    //Email exists
    function emailExists(data) {
        let url = '<?= base_url() ?>ajax/login/emailExists';
        $('#overlay').removeClass('hidden');
        $.post(url, data, function (response) {
            if (parseInt(response) == 1) {
                $('#email_password_div').html('Email already exists!');
                $('#post_account_created').hide();
                $('#overlay').addClass('hidden');
            } else {
                $('#email_password_div').html('');
                $('#post_account_created').show();
                $('#overlay').addClass('hidden');
            }
        });
    }
    
    // Verify Password complexity
    $(document).on('change', '#password', function () {
        let data = {
            password: $(this).val(),
        }
        let url = '<?= base_url() ?>login/verifyPasswordComplexity';
        $('#overlay').removeClass('hidden');
        $.post(url, data, function (validPasswordArr) {
            let arrayLength = validPasswordArr.length;
            if (arrayLength > 0) {
                //Hide Create Button
                $('#post_account_created').hide();
                //Unhide Labels
                let buildWrongPasswardDivString = '';
                for (let index = 0; index < arrayLength; index++) {
                    buildWrongPasswardDivString += validPasswordArr[index] + '<br>';
                }
                $('#weak_password_div').html(buildWrongPasswardDivString);
                //Disabled confirm_password
                $('#confirm_password').prop('disabled', true);
                $('#overlay').addClass('hidden');
            } else {
                //Unhide Create Button
                $('#post_account_created').show();
                //Hide Labels
                $('#weak_password_div').html('');
                //Enable confirm_password field
                $('#confirm_password').prop('disabled', false);
                $('#overlay').addClass('hidden');
            }
        });
    });
    
    //Compare Password with Confirm Password
    $(document).on('change', '#confirm_password', function () {
        let password = $('#password').val();
        let passwordConfirm = $(this).val();
        if (passwordConfirm != password) {
            alert('Password and Confirm Password Does Not Match');
            $('#post_account_created').hide();
            return false;
        } else {
            $('#post_account_created').show();
        }
    });
    
    //Validate Form
    function validateForm() {
        let check_any_field_empty;
        let any_field_empty_arr = [];
        $(".required").each(function () {
            //Select2 form validation implementation
            if ($(this).hasClass('select2') && (parseInt($(this).val()) === 0 || parseInt($(this).val()) == -1)) {
                $(this).siblings(".select2-container").css('border', '2px solid red');
                any_field_empty_arr.push(true);
            } else {
                if (($(this).val().trim() == '' && !$(this).hasClass('select2')) || $('option:selected', this).val() == 0) {
                    $(this).css('border', '2px solid red');
                    any_field_empty_arr.push(true);
                } else {
                    $(this).css('border', '');
                    // //Select2 implementation
                    if ($(this).hasClass('select2') && $(this).val() != 0) {
                        $(this).siblings(".select2-container").css('border', '');
                        //any_field_empty_arr.push($(this).prop('id'));
                        any_field_empty_arr.push(false);
                    }
                }
            }
        });
        //console.log(any_field_empty_arr);
        check_any_field_empty = any_field_empty_arr.includes(true);
        return check_any_field_empty;
    }
    
    //Save information WITHOUT reCAPTCHA
    $(document).on('click', "#post_account_created", function () {
        //If Form validation has an error return false or proceed
        if (validateForm()) {
            alert('Error');
            return false;
        }
        $('#overlay').removeClass('hidden');
        //Form submission
        //Verify Email Validity
        let data = {
            email: $('#email').val(),
        };
        let url = '<?= base_url() ?>login/verifyValidEmail';
        $.post(url, data, function (responseValidEmail) {
            //If email is invalid don't proceed
            if (parseInt(responseValidEmail) == 0) {
                $('#email_password_div').html('Invalid Email');
                return false;
            } else {
                //Check if email exists. 
                let url = '<?= base_url() ?>login/emailExists';
                $.post(url, data, function (responseEmailExists) {
                    //If email exists return false otherwise save data to table
                    if (parseInt(responseEmailExists) == 1) {
                        $('#email_password_div').html('Email already exists!');
                        $('#email').css('border', '2px dotted red');
                        return false;
                    } else {
                        // Save new create account
                        $('#email_password_div').html('');
                        $('#email').css('border', '');
                       
                        let data = {
                            first_name: $('#first_name').val(),
                            surname: $('#surname').val(),
                            email: $('#email').val(),
                            password: $('#password').val(),
                            user_type: $('#user_type').val(),
                            user_department: $('#user_department').val(),
                            user_role: $('#user_role').val(),
                            country_language: $('#country_language').val(),
                            user_activator_ids: $('#user_activator_ids').val(),
                            country_currency: $('#country_currency').val(),
                            user_country: $('#user_country').val(),
                            user_designation: $('#user_designation').val(),
                            user_office: $('#user_office').val(),
                        };

                        let url = "<?= base_url() ?>login/saveCreateAccountData"

                        $.post(url, data, function (response) {
                            alert(response.message);
                            $('#overlay').addClass('hidden');
                            //Redirect To Login Page
                            let redirect_url = '<?= base_url(); ?>';
                            window.location.replace(redirect_url);
                        });
                    }
                });
            }
        });
    });
    
    //Get the Context Definitions
    $(document).on('change', '#user_type', function () {
        let userType = parseInt($(this).val());
        //Populate Fcps or clusters or regions or countries
        if (userType != 0) {
            if (userType == 5) {
                //resign it to 4 (country_defination_context)
                userType = 4;
            }
            let compassion_country_id = $('#user_country').val();
            $('#overlay').removeClass('hidden');
            //Offices
            get_user_offices(userType, compassion_country_id);
            //Language for country
            populate_country_language(compassion_country_id);
            //Currency for country
            populate_country_currency(compassion_country_id);
            $('#overlay').addClass('hidden');
        }
    });
    
    //Get Next_level_context_office_id
    $(document).on('change', '#user_office', function () {
        let userType = $('#user_type').val();
        let officeId = $(this).val();
        let countryId = $('#user_country').val();
        $('#overlay').removeClass('hidden');
        populate_user_activator_ids(userType, officeId, countryId);
        //Departments
        get_user_departments_roles_and_designations(userType, 'department', 'user_department', 0);
        //Roles
        get_user_departments_roles_and_designations(userType, 'role', 'user_role', countryId);
        //Designations 
        get_user_departments_roles_and_designations(userType, 'designation', 'user_designation', 0);
    });
    //Populate offices
    function get_user_offices(context_definition_id, account_system_id) {
        let compassion_country_id = $('#user_country').val();
        //Get me offices that belong to country of $(this) context
        let url = '<?= base_url() ?>login/getOffices/' + compassion_country_id + '/' + context_definition_id;
        $.ajax({
            url: url,
            //data: data,
            type: "GET",
            beforeSend: function () {
                $('#overlay').removeClass('hidden');
            },
            success: function (response) {
                $('#overlay').removeClass('hidden');
                selectElement = $('#user_office');
                populate_offices_departments_roles(selectElement, response);
                $('#overlay').addClass('hidden');
            },
            error: function () {
            }
        });
    }
    
    //Populate departments
    function get_user_departments_roles_and_designations(userTypeId, tableName, elementId, countryId) {
        var url = '<?= base_url() ?>login/getUserDepartmentsRolesAndDesignations/' + userTypeId + '/' + tableName + '/' + countryId;
        $.get(url, function (objResponse) {
            // let objResponse = JSON.parse(response);
            // console.log(objResponse);
            selectElement = $('#' + elementId);
            populate_offices_departments_roles(selectElement, objResponse);
        });
    }
    
    //Populate activator ids
    function populate_user_activator_ids(userType, officeID, countryID) {
        let url = '<?= base_url() ?>login/getUserActivatorIds/' + userType + '/' + officeID + '/' + countryID;
        $.get(url, function (activatorUserIds) {
            $('#overlay').removeClass('hidden');
            //Populate the fk_user_ids
            // let activatorUserIds = JSON.parse(response);
            //Check if empty
            if (activatorUserIds.length == 0) {
                alert('User activators e.g. pf, country admin are missing. Contact System administrators for help');
                window.location = '<?= base_url(); ?>';
                return false;
            }
            let activatorUserIdsString = '';
            $.each(activatorUserIds, function (index, el) {
                // console.log(el);
                if (userType == 1) {
                    activatorUserIdsString += el.fk_user_id + ':';
                } else {
                    activatorUserIdsString += el.user_id + ':';
                }
            })
            $('#user_activator_ids').prop('value', activatorUserIdsString);
            $('#overlay').addClass('hidden');
        });
    }
    
    //Populate language
    function populate_country_language(account_system_id) {
        let url = '<?= base_url() ?>login/getCountryLanguage/' + account_system_id;
        $.get(url, function (language) {
            //Check if the value is zero
            // alert(language.language_id)
            if (parseInt(language.language_id) == 0) {
                alert('Ask the system adminitrator to create default language for your country');
                window.location = '<?= base_url(); ?>';
                return false;
            } else {
                $('#country_language').prop('value', parseInt(language.language_id));
            }
        });
    }
    
    //Populate currency
    function populate_country_currency(account_system_id) {
        let url = '<?= base_url() ?>login/getCountryCurrency/' + account_system_id;
        $.get(url, function (country_currency) {
            //Check if the value is zero
            if (parseInt(country_currency.country_currency_id) == 0) {
                alert('Ask the system adminitrator to create local currency record for your country');
                window.location = '<?= base_url(); ?>';
                return false;
            } else {
                $('#country_currency').prop('value', parseInt(country_currency.country_currency_id));
            }
        });
    }
    
    //Populate departments, roles, offices
    function populate_offices_departments_roles(selectElement, results) {
        $('#overlay').removeClass('hidden');
        //Detach the 1st option
        let firstOption = selectElement.find('option:first-child').detach();
        selectElement.empty();
        selectElement.append(firstOption);
        //Populate the the user_office select2 dropdown with other options from database
        // let results = JSON.parse(response);
        let otherOptions = '';
        $.each(results, function (officeID, elTextName) {
            otherOptions += "<option value='" + officeID + "'>" + elTextName + "</option>";
        });
        selectElement.append(otherOptions);
        //sleep(60);
        $('#overlay').addClass('hidden');
    }
    
    //reset userType select2 dropdown
    $(document).on('change', '#user_country', function () {
        let userTypeElem = $('#user_type');
        if (userTypeElem.val() != 0 && $(this).val() != 0) {
            userTypeElem.val(0).trigger('change');
        }
        //Reset to 0 and disabled it or enable when user_country>0
        if ($(this).val() == 0) {
            userTypeElem.val(0).trigger('change');
            userTypeElem.attr('disabled', true);
        } else {
            userTypeElem.removeAttr('disabled');
        }
    });
    
    /*Reset Office Select2; Reset Departments Select2 and Reset Roles Select  */
    function reset_select2_and_disable(element) {
        element.val(0).trigger('change');
        element.attr('disabled', true);
    }
    
    //reset userType select2 dropdown
    $(document).on('change', '#user_type', function () {
        let userOffice = $('#user_office');
        let userDepartment = $('#user_department');
        let userRole = $('#user_role');
        let userDesignation = $('#user_designation');
        if (userOffice.val() != 0 && $(this).val() != 0) {
            userOffice.val(0).trigger('change');
            userDepartment.val(0).trigger('change');
            userRole.val(0).trigger('change');
            userDesignation.val(0).trigger('change');
        }
        //Reset to 0 and disabled it or enable when user_country>0
        if ($(this).val() == 0) {
            reset_select2_and_disable(userOffice);
            reset_select2_and_disable(userDepartment);
            reset_select2_and_disable(userRole);
            reset_select2_and_disable(userDesignation);
        } else {
            userOffice.removeAttr('disabled');
            userDepartment.removeAttr('disabled');
            userRole.removeAttr('disabled');
            userDesignation.removeAttr('disabled');
        }
    })
</script>