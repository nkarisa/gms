<style>
thead tr td {
    font-weight:bold;
}
</style>  

<table class="table table-striped">
    <thead>
        <tr>
            <th><?=get_phrase('accrual_ledger');?></th>
            <th><?=get_phrase('opening_balance');?></th>
            <th><?=get_phrase('debit');?></th>
            <th><?=get_phrase('credit');?></th>
            <th><?=get_phrase('closing_balance');?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($accrued_balance_report as $accrued_ledger_account => $accrued_ledger_balance){?>
            <tr>
                <td><?= get_phrase($accrued_ledger_account); ?></td>
                <td><?= number_format($accrued_ledger_balance['opening'],2); ?></td>
                <td><?= number_format($accrued_ledger_balance['debit'],2); ?></td>
                <td><?= number_format($accrued_ledger_balance['credit'],2); ?></td>
                <td><?= number_format($accrued_ledger_balance['closing'],2); ?></td>
            </tr>
        <?php }?>
    </tbody>
</table>