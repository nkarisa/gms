<?php foreach ($month_opening_balance as $office_bank_id => $bank_account) { ?>
    <th colspan='3' style='text-align:center;'><?= get_phrase('bank'); ?>
        (<?= $bank_account['account_name']; ?>)</th>
<?php } ?>