<?php 
    extract($data);

    $amount_with_objectives = $tallies['with_objectives']['amount'];
    $amount_without_objectives = $tallies['without_objectives']['amount'];

    $sum_budget = $amount_with_objectives + $amount_without_objectives;

    $percentage_with_objectives = number_format($sum_budget > 0 ? ($amount_with_objectives/ $sum_budget ) * 100 : 0,2);
?>
<div class = 'row'>
    <div class = 'col-xs-3'>
    <div class="tile-stats tile-cyan">
                <div class="icon"><i class="entypo-users"></i></div>
                <div id="budget_amount_without_objectives" class="num metrics" data-start="0" data-end="0" data-postfix="" data-duration="1500" data-delay="0"><?=$percentage_with_objectives;?></div>

                <h3><?=get_phrase('budget_percent_with_objectives');?></h3>
                <p></p>
            </div>
    </div>
</div>

<?php 
foreach($tabulation as $summary_type => $summary){
?>

<div class = "row">
    <div class = "col-xs-12">
        <table class = 'table table-striped'>
            <thead>
                <tr>
                    <th colspan = '2' style="text-align: center;font-weight: bold;"><?=get_phrase($summary_type);?></th>
                </tr>
                <tr>
                    <th><?=get_phrase('item');?></th>
                    <th><?=get_phrase('budgeted_amount');?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($summary as $id => $summary_item){?>
                    <tr>
                        <td><?=$summary_item[0]['name'];?></td>
                        <td><?=number_format(array_sum(array_column($summary_item,'amount')),2);?></td>
                    </tr>
                <?php }?>
            </tbody>
        </table>
    </div>
</div>
<?php 
}
?>

