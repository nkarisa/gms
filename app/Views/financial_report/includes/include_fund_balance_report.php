<?php 

extract($result);
?>
<table class="table table-striped" id="fund_balance_table">
        <thead>
            <tr>
                <th class='row_header'><?=get_phrase('fund');?></th>
                <th class='row_header'><?=get_phrase('opening_balance');?></th>
                <th class='row_header'><?=get_phrase('month_income');?></th>
                <th class='row_header'><?=get_phrase('month_expense');?></th>
                <th class='row_header'><?=get_phrase('closing_balance');?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($fund_balance_report as $fund_account_info){?>
                <tr>
                    <td><?=$fund_account_info['account_name'];?></td>
                    <td class="currency fund_month_opening_balance"><?=number_format($fund_account_info['month_opening_balance'],2);?></td>
                    <td class="currency fund_month_income"><?=number_format($fund_account_info['month_income'],2);?></td>
                    <td class="currency fund_month_expense"><?=number_format($fund_account_info['month_expense'],2);?></td>
                    <td class="currency fund_month_closing_balance">0.00</td><!--Value calculate with JS in the view file-->
                </tr>
            <?php }?>
        </tbody>
        <tfoot>
            <tr>
                <td class='row_total'>Total</td>
                <td class='row_total' id="total_fund_month_opening_balance"><?=number_format(array_sum(array_column($fund_balance_report,'month_opening_balance')),2);?></td>
                <td class='row_total' id="total_fund_month_income"><?=number_format(array_sum(array_column($fund_balance_report,'month_income')),2);?></td>
                <td class='row_total' id="total_fund_month_expense"><?=number_format(array_sum(array_column($fund_balance_report,'month_expense')),2);?></td>
                <td class='row_total code_proof_of_cash' id="total_fund_month_closing_balance">0.00</td>
            </tr>
        </tfoot>
        </table>