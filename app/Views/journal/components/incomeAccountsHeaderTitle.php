<?php if ($journal->getMonthSumIncomeAccounts() > 0) { ?>
    <th colspan='<?= $journal->getMonthSumIncomeAccounts(); ?>'><?= get_phrase('income'); ?></th>
<?php } ?>