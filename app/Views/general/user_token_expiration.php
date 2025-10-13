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
    
                </div>

            </div>


                <div class="login-content">
                    <div class = 'row'>
                        <div class = 'col-xs-12'>
                            <p style='font-size: 18pt;'><?=get_phrase('expired_user_token','The user token has expired. Please use the forgot password link below to reset your password');?></p>
                        </div>
                    </div>

                    <div class="login-bottom-links">
                        <div>
                            <a href="<?php echo base_url(); ?>login/forgot_password" class="link">
                                <?php echo get_phrase('forgot_your_password'); ?> ?
                            </a>
                        </div>
				    </div>
                </div>

        </div>

    </body>
</html>