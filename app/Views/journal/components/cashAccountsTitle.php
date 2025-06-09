<?php foreach ($month_opening_balance as $office_cash_id => $cash_account) { ?>
    <th><?= $cash_account['account_name'] . ' ' . get_phrase('income'); ?></th>
    <th><?= $cash_account['account_name'] . ' ' . get_phrase('expense'); ?></th>
    <th><?= $cash_account['account_name'] . ' ' . get_phrase('balance'); ?></th>
<?php } ?>