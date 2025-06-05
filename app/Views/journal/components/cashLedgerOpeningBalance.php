<?php foreach ($month_opening_balance as $office_cash_id => $cash_account) { ?>
    <th colspan='3'><?= number_format($cash_account['amount'], 2); ?></th>
<?php } ?>