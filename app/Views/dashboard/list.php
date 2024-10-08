<?php 
// $grantsLibrary = new \App\Libraries\System\GrantsLibrary();
// echo json_encode($grantsLibrary->dbSchema);
?>
<div class = 'row'>
    <div class="col-sm-12">
        <div class="well">
            <h1><?= date('F, d Y') ?></h1>
            <h3><?= get_phrase('dashboard_welcome', 'Welcome to the site'); ?> <strong><?= session()->name; ?></strong></h3>
        </div>
    </div>
</div>