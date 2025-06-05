<?php if (journal()->getMonthSumExpenseAccounts() > 0) { ?>
    <th colspan='<?= journal()->getMonthSumExpenseAccounts(); ?>'><?= get_phrase('expense'); ?></th><?php } ?>
<?php ?>