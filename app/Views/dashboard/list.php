<?php 
// log_message('error', 'This is a monolog stdout')
?>
<div class = 'row'>
    <div class="col-sm-12">
        <div class="well">
            <h1><?= date('F, d Y') ?></h1>
            <h3><?= get_phrase('dashboard_welcome', 'Welcome to the site'); ?> <strong><?= session()->name; ?></strong></h3>
        </div>
    </div>
</div>

<!-- <div class = 'row'>
    <div class="col-sm-12">
        <div id="post_ajax_button" class = "btn btn-default">Ajax Click Me {Uses Post}</div>
        <div id="get_ajax_button" class = "btn btn-default">Ajax Click Me {Uses Get}</div>
    </div>
</div> -->

<script>
    $('#post_ajax_button').click(function() {
        $.ajax({
            url: '<?= site_url('ajax')?>',
            type: 'POST',
            data: {
                controller: 'dashboard',
                method: 'getDashboardData',
                data: {
                    officeId: 12,
                    date: '<?= date('Y-m-d')?>'
                }
            },
            success: function(response) {
                console.log(response);
            }
        });
    });

    $('#get_ajax_button').click(function() {
        $.ajax({
            url: '<?= site_url('ajax/dashboard/getDashboardData/officeId/12/date/'.date('Y-m-d'))?>',
            type: 'GET',
            success: function(response) {
                console.log(response);
            }
        });
    });
</script>