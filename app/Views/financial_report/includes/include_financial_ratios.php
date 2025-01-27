<?php 
// echo json_encode($expense_report);
?>
<table class="table table-striped">
        <thead>
            <tr>
                <th><?=get_phrase('operating_ratio');?> <i style="color:red" class = "fa fa-info-circle" title = "<?=get_phrase("operating_ratio_help","The ratio of the support grants funds used for administration cost against the total support grants expenses in the year");?>"></i></th>
                <th><?=get_phrase('accumulated_fund_ratio');?> <i style="color:red" class = "fa fa-info-circle" title = "<?=get_phrase("accumulated_fund_ratio_help","Is the ratio of the support grants balance against the avarage support grants income to date in a year");?>"></i></th>
                <th><?=get_phrase('budget_variance');?> <i style="color:red" class = "fa fa-info-circle" title = "<?=get_phrase("budget_variance_help","Variance is the diffrence your budget to date from the expenses to date for support funds as a ratio against the budget to date.");?>"></i></th>
                <th><?=get_phrase('survival_ratio');?> <i style="color:red" class = "fa fa-info-circle" title = "<?=get_phrase("survival_ratio_help","This is the ratio of the locally mobilized funds against total financial support from CI excluding gift funds in the year");?>"></i></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?=$financial_ratios['operation_ratio'];?>%</td>
                <td><?=$financial_ratios['accumulation_ratio'];?></td>
                <td><?=$financial_ratios['budget_variance'];?>%</td>
                <td><?=$financial_ratios['survival_ratio'];?>%</td>
            </tr>
        </tbody>
    </table>