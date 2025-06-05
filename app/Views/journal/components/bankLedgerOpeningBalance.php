<?php foreach ($month_opening_balance as $office_bank_id => $bank_account) { ?>
    <th colspan='3'><?= number_format($bank_account['amount'], 2); ?></th>
<?php } ?>