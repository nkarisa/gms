<?php foreach ($month_opening_balance as $accrual_ledger => $ledger_opening_balance) { ?>
    <th colspan='3'><?= number_format($ledger_opening_balance, 2); ?></th>
<?php } ?>