<?php foreach ($month_opening_balance as $office_bank_id => $bank_account) { ?>
    <th><?= get_phrase('bank_income') . ' (' . $bank_account['account_name'] . ')'; ?></th>
    <th><?= get_phrase('bank_expense') . ' (' . $bank_account['account_name'] . ')'; ?></th>
    <th><?= get_phrase('balance') . ' (' . $bank_account['account_name'] . ')'; ?></th>
<?php } ?>