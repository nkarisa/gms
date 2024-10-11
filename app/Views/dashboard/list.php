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

<div class = 'row'>
    <div class="col-sm-12">
        <div id="ajax_button" class = "btn btn-default">Ajax Click Me</div>
    </div>
</div>

<script>
    $('#ajax_button').click(function() {
        $.ajax({
            url: '<?= site_url('ajax')?>',
            type: 'POST',
            data: {
                controller: 'dashboard',
                method: 'getDashboardData',
                data: {
                    date: '<?= date('Y-m-d')?>'
                }
            },
            success: function(response) {
                console.log(response);
            }
        });
    });
</script>