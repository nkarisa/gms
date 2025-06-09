<?php foreach ($month_opening_balance as $accrual_ledger => $ledger_opening_balance) { ?>
    <th><?= get_phrase($accrual_ledger) . ' ' . get_phrase('debit'); ?></th>
    <th><?= get_phrase($accrual_ledger) . ' ' . get_phrase('credit'); ?></th>
    <th><?= get_phrase($accrual_ledger) . ' ' . get_phrase('balance'); ?></th>
<?php } ?>