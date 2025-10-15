<?php
    // $db = \Config\Database::connect();

    // $fields = $db->getFieldData('accrual_ledger');
    // echo json_encode($fields);
?>
<div class = 'row'>
    <div class="col-sm-12">
        <div class="well">
            <h1><?= date('F, d Y') ?></h1>
            <h3><?= get_phrase('dashboard_welcome', 'Welcome to the site'); ?> <strong><?= session()->name; ?></strong></h3>
        </div>
    </div>
</div>

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