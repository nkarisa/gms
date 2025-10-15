<?php foreach ($active_accrual_ledgers as $accrual_ledger) { ?>
    <th colspan='3'><?= number_format($month_opening_balance[$accrual_ledger] ?? 0, 2); ?></th>
<?php } ?>