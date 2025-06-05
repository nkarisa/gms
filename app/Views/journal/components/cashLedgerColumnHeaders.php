<?php foreach ($month_opening_balance as $office_cash_id => $cash_account) { ?>
    <th colspan='3' style='text-align:center;'><?= get_phrase('cash'); ?>
        (<?= $cash_account['account_name']; ?>)</th>
<?php } ?>