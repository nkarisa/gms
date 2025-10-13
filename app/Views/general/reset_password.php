<!DOCTYPE html>
<html lang="en">
    <head>
        <?php
        $system_name = "Safina"; // $this->db->get_where('setting', array('type' => 'system_name'))->row()->description;
        $system_title = "Safina"; // $this->db->get_where('setting', array('type' => 'system_title'))->row()->description;
        ?>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">

        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta name="description" content="Safina Grants Management System" />
        <meta name="author" content="" />

        <title><?php echo get_phrase('reset_password'); ?> | <?php echo $system_title; ?></title>


        <link rel="stylesheet" href="<?php echo base_url();?>assets/js/jquery-ui/css/no-theme/jquery-ui-1.10.3.custom.min.css">
        <link rel="stylesheet" href="<?php echo base_url();?>assets/css/font-icons/entypo/css/entypo.css">
        <link rel="stylesheet" href="http://fonts.googleapis.com/css?family=Noto+Sans:400,700,400italic">
        <link rel="stylesheet" href="<?php echo base_url();?>assets/css/bootstrap.css">
        <link rel="stylesheet" href="<?php echo base_url();?>assets/css/neon-core.css">
        <link rel="stylesheet" href="<?php echo base_url();?>assets/css/neon-theme.css">
        <link rel="stylesheet" href="<?php echo base_url();?>assets/css/neon-forms.css">
        <link rel="stylesheet" href="<?php echo base_url();?>assets/css/custom.css">

        <script src="<?php echo base_url();?>assets/js/jquery-1.11.0.min.js"></script>

        <!--[if lt IE 9]><script src="<?php echo base_url();?>assets/js/ie8-responsive-file-warning.js"></script><![endif]-->

        <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
        <!--[if lt IE 9]>
                <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
                <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
        <![endif]-->
        <link rel="shortcut icon" href="<?php echo base_url();?>assets/images/favicon.ico">

    </head>
    <body class="page-body login-page login-form-fall" data-url="http://neon.dev">


        <!-- This is needed when you send requests via Ajax -->
        <script type="text/javascript">
            var baseurl = '<?php echo base_url(); ?>';
        </script>

        <div class="login-container">

            <div class="login-header login-caret">

                <div class="login-content" style="width:100%;">

                    <a href="<?php echo base_url(); ?>" class="logo">
                        <img src="uploads/logo.png" height="60" alt="" />
                    </a>

                    <p class="description">
                        <h2 style="color:#cacaca; font-weight:100;">
                            <?php echo $system_name; ?>
                        </h2>
                    </p>
                    <p class="description"><?=get_phrase('enter_preferred_password','Enter your preferred password.');?></p>

                    <!-- progress bar indicator -->
                    <div class="login-progressbar-indicator">
                        <h3>43%</h3>
                        <span>resetting password...</span>
                    </div>
                </div>

            </div>

            <div class="login-progressbar">
                <div></div>
            </div>

            <div class="login-form">

                <div class="login-content">

                    <div class="form-login-error">
                        <h3><?=get_phrase('invalid_details','Invalid Details');?></h3>
                        <p><?=get_phrase('enter_your_details','Please enter details!');?></p>
                    </div>
                    <form method="post" role="form" id="form_reset_password">

                        <div class="form-forgotpassword-success">
                            <i class="entypo-check"></i>
                            <p><?=get_phrase('password_reset_success','Your password has been reset successfully. Please go to the login page to continue logging in!')?></p>
                        </div>

                        <div class="form-steps">

                            <div class="step current" id="step-1">

                                <div class="form-group">
                                    <div class="input-group">
                                        <div class="input-group-addon">
                                        <i class="entypo-eye"></i>
                                        </div>

                                        <input type="password" class="form-control input-field" name="first_attempt_password" id="first_attempt_password" placeholder="Password" autocomplete="off" />
                                        <i id="toggle_pwd" class="fa fa-fw fa-eye field_icon"></i>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="input-group">
                                        <div class="input-group-addon">
                                        <i class="entypo-lock"></i>
                                        </div>

                                        <input type="password" class="form-control input-field" name="password" id="password" placeholder="Repeat Password" autocomplete="off" />
                                        <i id="toggle_pwd" class="fa fa-fw fa-eye field_icon"></i>
                                    </div>

					            </div>

                                <div class="form-group">
                                    <button type="submit" class="btn btn-info btn-block btn-login">
                                        <?php echo get_phrase('reset_password'); ?>
                                        <i class="entypo-right-open-mini"></i>
                                    </button>
                                </div>

                            </div>

                        </div>

                    </form>



                    <div class="login-bottom-links">

                        <a href="<?php echo base_url(); ?>" class="link">
                            <i class="entypo-lock"></i>
                            <?php echo get_phrase('return_to_login_page'); ?>
                        </a>


                    </div>

                </div>

            </div>

        </div>


        <!-- Bottom Scripts -->
        <script src="<?php echo base_url();?>assets/js/gsap/main-gsap.js"></script>
        <script src="<?php echo base_url();?>assets/js/jquery-ui/js/jquery-ui-1.10.3.minimal.min.js"></script>
        <script src="<?php echo base_url();?>assets/js/bootstrap.js"></script>
        <script src="<?php echo base_url();?>assets/js/joinable.js"></script>
        <script src="<?php echo base_url();?>assets/js/resizeable.js"></script>
        <script src="<?php echo base_url();?>assets/js/neon-api.js"></script>
        <script src="<?php echo base_url();?>assets/js/jquery.validate.min.js"></script>
        <script src="<?php echo base_url();?>assets/js/neon-resetpassword.js"></script>
        <script src="<?php echo base_url();?>assets/js/jquery.inputmask.bundle.min.js"></script>
        <script src="<?php echo base_url();?>assets/js/neon-custom.js"></script>
        <script src="<?php echo base_url();?>assets/js/neon-demo.js"></script>

    </body>
</html>