<?php foreach ($active_accrual_ledgers as $accrual_ledger) { ?>
    <th><?= get_phrase($accrual_ledger['accrual_ledger_name']) . ' ' . get_phrase('debit'); ?></th>
    <th><?= get_phrase($accrual_ledger['accrual_ledger_name']) . ' ' . get_phrase('credit'); ?></th>
    <th><?= get_phrase($accrual_ledger['accrual_ledger_name']) . ' ' . get_phrase('balance'); ?></th>
<?php } ?>