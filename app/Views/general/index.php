<!DOCTYPE html>
<html lang="en" dir="<?=$text_align == 'left-to-right' ? 'ltr' : 'rtl'; ?>">

<head>

    <title><?= $page_title; ?></title>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="" />
    <meta name="author" content="" />


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
                    <h5 style="" class="pull-left">
                        <!-- <i class="entypo-right-circled"></i> -->
                        <?= $this->renderSection('breadcrumb') ?>
                    </h5>

                    <div class="btn-group pull-right">
                        <button class="btn btn-default" title="<?= get_phrase('back'); ?>"
                            onclick="javascript:go_back();"><i class="fa fa-backward"></i></button>
                        <button class="btn btn-default" title="<?= get_phrase('forward'); ?>"
                            onclick="javascript:go_forward();"><i class="fa fa-forward"></i></button>
                    </div>
                </div>


            </div>

            <hr />
            <div class="page-content">
                <?= view($views_dir."/".$action, $result);?>
            </div>
            <?= view('general/footer') ?>

        </div>

    </div>
    <?php include 'includes_bottom.php'; ?>

</body>

</html>