<!DOCTYPE html>
<html lang="en" dir="<?=$text_align == 'left-to-right' ? 'ltr' : 'rtl'; ?>">

<head>

    <title><?= $page_title; ?></title>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="" />
    <meta name="author" content="" />
<meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">

    <?php include 'includes_top.php'; ?>

</head>

<body class="page-body <?php if ($skin_colour != '')
    echo 'skin-' . $skin_colour; ?> page-fade-only">
    <div class="page-container horizontal-menu">

        <?= view('general/navigation', ['navigation' => $navigation]); ?>

        <div class="main-content">

            <?= view('general/header', [...$user]); ?>

            <div class="row">
                <div class="col-xs-12">
                    <div class = "col-xs-4">
                        <h5 class="pull-left">
                            <!-- <i class="entypo-right-circled"></i> -->
                            <?=create_breadcrumb();?>
                        </h5>
                    </div>

                    <div class = "col-xs-6 pull-left">
                        <?php 
                            if(service('settings')->get("GrantsConfig.maintenance_mode")){
                        ?>
                                <div class = "warning"><?=get_phrase('maintenance_mode_message','The system is under maintenance schedule. You will be contacted by your
						Country Administrators once the system is back')?></div>
                        <?php
                            }
                        ?>
                    </div>

                    <div class = "col-xs-2">
                        <div class="btn-group pull-right">
                            <button class="btn btn-default" title="<?= get_phrase('back'); ?>"
                                onclick="javascript:go_back();"><i class="fa fa-backward"></i></button>
                            <button class="btn btn-default" title="<?= get_phrase('forward'); ?>"
                                onclick="javascript:go_forward();"><i class="fa fa-forward"></i></button>
                        </div>
                    </div>
                </div>


            </div>

            <hr />
            <div class="page-content">
                <?= view($views_dir."/".$page_name, $result);?>
            </div>
            <?= view('general/footer') ?>

        </div>

    </div>
    <?php 
        include 'includes_bottom.php'; 

        // include 'modal.php';
			if(file_exists(VIEWPATH.$controller.DS.session()->get('user_account_system').DS.'js_script.php')){
				include VIEWPATH.$controller.DS.session()->get('user_account_system').DS.'js_script.php';
			}elseif(file_exists(VIEWPATH.$controller.DS.'js_script.php')){
					include VIEWPATH.$controller.DS.'js_script.php';
			}elseif(file_exists(VIEWPATH.'components'.DS.'js_script.php')){
				include VIEWPATH.'components'.DS.'js_script.php';
			}
	
			if(file_exists(VIEWPATH.'components'.DS.$action.'Script.php')){
				include VIEWPATH.'components'.DS.$action.'Script.php';
			}
        include 'modal.php';
    ?>

</body>

</html>
