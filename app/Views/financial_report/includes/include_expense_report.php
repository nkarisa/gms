<style>
.is_clicked{
    color: red;
}
</style>
<div id="expense_reports">

    <?php 
    
    $check_sum = array_column($expense_report,'check_sum');
    $userLibrary = new \App\Libraries\Core\UserLibrary();
    $cnt = 0;
    //log_message('error', json_encode($expense_report));
    
    foreach($expense_report as $income_record){
        $cnt++;
        if($check_sum[$cnt - 1] == 0 && $this->config->skip_empty_expense_reports) continue;  // Skips expense tables that lack records
        $is_budgetable = $income_record['income_account']['income_account_is_budgeted'];
    ?>
    <div class="row">
        <div class="col-xs-12 cols">
            <table class="table table-striped" style="width:100%">
                <thead>
                    <tr>
                        <th colspan='<?=!$multiple_offices_report?11:10;?>' style='text-align:center;'><?=$income_record['income_account']['income_account_name'];?></th>
                    </tr>
                    <tr>
                        <th><?=get_phrase('expense_account');?></th>
                        <th><?=get_phrase('month_expense');?> [A]</th>
                        <th><?=get_phrase('month_budget');?> [B]</th>
                        <th><?=get_phrase('month_budget_variance');?> [C=(B-A)]</th>
                        <th><?=get_phrase('month_percent_variance');?> [D=(C/B)%]</th>
                        <th><?=get_phrase('year_to_date_expense');?> [E]</th>
                        <th><?=get_phrase('budget_to_date');?> [F]</th>
                        <th><?=get_phrase('budget_variance');?> [G=(F-E)]</th>
                        <th><?=get_phrase('year_percent_variance');?> [H=(G/F)%]</th>
                        <?php if(!$multiple_offices_report){?>
                        <th><?=get_phrase('variance_comment');?></th>
                        <?php }?>
                    </tr>
                </thead>
                <tbody>
                <?php 
                    $month_expense = 0;
                    $month_budget = 0;
                    $budget_to_date = 0;
                    $month_variance_amount=0;
                    $year_to_date_expense_amount=0;
                    $year_to_date_budget_amount=0;
                     
                    
                    foreach($income_record['expense_accounts'] as $expense_account){

                        if(
                            $expense_account['month_expense'] == 0
                            && $expense_account['month_expense_to_date'] == 0
                                && $expense_account['budget_to_date'] == 0
                                && !$this->config->show_empty_rows_in_expense_report
                        ) continue;
                ?>
                    <tr>
                        <td><?=$expense_account['expense_account']['expense_account_code'].' - '.$expense_account['expense_account']['expense_account_name'];?></td>
                        <td><?=number_format($expense_account['month_expense'],2);?></td>
                        <td><?=$is_budgetable ? number_format($expense_account['month_budget'],2) : 'XXXXXXXXX';?></td>
                        <?php 
                            $month_budget_variance = $expense_account['month_budget'] - $expense_account['month_expense'];
                            $month_percent_budget_variance = 0; 
                            if($expense_account['month_budget'] == 0 && $expense_account['month_expense'] == 0){
                                $month_percent_budget_variance = 0;
                            }elseif($expense_account['month_budget'] > 0 && $expense_account['month_expense'] != 0){
                                $month_percent_budget_variance = $month_budget_variance/$expense_account['month_budget'];
                            }elseif($expense_account['month_budget'] == 0 && $expense_account['month_expense'] != 0){
                                $month_percent_budget_variance = -1;
                            }
                        ?>
                        <td><?=$is_budgetable ? number_format($month_budget_variance,2): 'XXXXXXXXX';?></td>
                        <td><?=$is_budgetable ? round($month_percent_budget_variance,2) * 100 : 'XXXXXXXXX';?></td>

                       
                        <td><?=number_format($expense_account['month_expense_to_date'],2);?></td>
                        <td><?=$is_budgetable ? number_format($expense_account['budget_to_date'],2) : 'XXXXXXXXX';?></td>
                        <?php 
                            $budget_variance = $expense_account['budget_to_date'] - $expense_account['month_expense_to_date'];
                            $percent_budget_variance = $expense_account['budget_to_date'] > 0 ? ($budget_variance/$expense_account['budget_to_date']) : -1;
                        ?>
                        <td><?=$is_budgetable ? number_format($budget_variance ,2) : 'XXXXXXXXX';?></td>
                        <td><?=$is_budgetable ? round($percent_budget_variance,2) * 100: 'XXXXXXXXX';?></td>
        
                        <?php 
                         //Calculcate the expenses year to date and budget to date
                          $year_to_date_expense_amount+=$expense_account['month_expense_to_date'];

                          $year_to_date_budget_amount+=$expense_account['budget_to_date'];
                        ?>

                        <?php if(!$multiple_offices_report){?>
                        <td>
                            <?php if($is_budgetable ) {?>
                            <i id="variance_comment_<?=$expense_account['expense_account']['expense_account_code'];?>" data-account_id = "<?=$expense_account['expense_account']['expense_account_id'];?>" class="fa fa-comment variance_comment" style="cursor: pointer;"></i> <?=$expense_account['expense_account_comment'] !== ''?'<i class="fa fa-eye"></i>':'';?>
                            <?php }else{
                                echo 'XXXXXXXXX';
                            }?>
                        </td>
                        <?php }?>
                    </tr>
                <?php 
                        $month_expense += $expense_account['month_expense'];
                        $month_budget += $expense_account['month_budget'];
                       // $month_variance_amount += $budget_variance;
                        $budget_to_date += $budget_variance;
                    }
                ?>
                    
                </tbody>
                <tfoot>
                    <tr>
                        <td><?=get_phrase('total');?></td>
                        <td><?=number_format($month_expense,2);?></td>
                        <td><?=number_format($month_budget,2);//B?></td> 
                        <td><?=number_format($budget_to_date,2);//C?></td>
                        <?php
                            //Get Budget month variance percentage;
                            $budget_month_variance_percent = $month_budget != 0?round($budget_to_date / $month_budget,2) * 100:0;

                        ?>
                        <td><?php echo $budget_month_variance_percent?$budget_month_variance_percent:'';?></td>
                        <td><?=number_format($year_to_date_expense_amount,2);?></td>
                        <td><?=number_format( $year_to_date_budget_amount,2);?></td>

                        <?php 
                            //Get budget year variance percentage and budget year variance
						$budget_year_variance_percent=0;

						if($year_to_date_expense_amount>0){
							$budget_year_variance_percent= round($year_to_date_budget_amount / $year_to_date_expense_amount,2)*100;
						}


						$budget_year_variance=$year_to_date_budget_amount-$year_to_date_expense_amount;
                        ?>
                        <td><?php echo number_format($budget_year_variance,2);?></td>
                        <td><?php echo $budget_year_variance_percent?$budget_year_variance_percent:'';?></td>
                       
                       
                        <td>&nbsp;</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <?php }?>

</div>

<input type="hidden" id="variance_comment_id" value="0"/>

<div id="note_area_holder" class="col-xs-4 hidden">
    <textarea class="form-control" class="note_area" <?=!$userLibrary->checkRoleHasPermissions('financial_report','create')?'disabled':'';?>
    placeholder="Put your notes here" rows="10"></textarea>
</div>

<script>

$(document).on('change','.active_note_area > textarea',function(){
    var expense_account_id = $(".is_clicked").first().data('account_id');
    var comment = $(this).val();
    var url = "<?=base_url();?>Financial_report/post_expense_account_comment";
    var office_id = $("#office_ids").val();
    var reporting_month = '<?=$reporting_month;?>';
    var report_id = '<?=$report_id;?>';
    var data = {'expense_account_id':expense_account_id,'office_id':office_id,'report_id':report_id,'reporting_month':reporting_month,'variance_comment_text':comment};
    // console.log(data);
    $.post(url,data,function(response){
        alert(response);
    });

});


$(document).on('click',".variance_comment",function(){

    var cols = $(this).closest('.cols');
    var cols_is_full_spaned = cols.hasClass("col-xs-12");
    var row = cols.closest('.row');
    var sibling_rows = row.siblings();
    var id = $(this).attr('id');
    var variance_comment = $(this);

            if($(".is_clicked").length > 0){
                $(".is_clicked").each(function(i,el){
                    $(el).removeClass('is_clicked');
                });
            }

            $(".active_note_area").each(function(i,el){
                $(el).remove();
            });

            sibling_rows.each(function(i,el){
                var sibling_cols = $(el).find('.cols');
        
                var sibling_cols_is_semi_spaned = sibling_cols.hasClass("col-xs-8");

                if(sibling_cols_is_semi_spaned){
                    sibling_cols.removeClass("col-xs-8").addClass("col-xs-12");
                }
            });

            if(cols_is_full_spaned && !variance_comment.hasClass('is_clicked')){

                cols.removeClass("col-xs-12").addClass("col-xs-8");
                $(this).addClass("is_clicked");
                $("#variance_comment_id").val(id);
                update_notes_area(row);
        
            }else if(!cols_is_full_spaned && !variance_comment.hasClass('is_clicked') && id != $("#variance_comment_id").val() ){    
                
                $(this).addClass("is_clicked");
                $("#variance_comment_id").val(id);
                update_notes_area(row);

            }else{

                cols.removeClass("col-xs-8").addClass("col-xs-12");
                $(this).removeClass("is_clicked");
            
            }    

});

function update_notes_area(row){
    
    var expense_account_id = $(".is_clicked").first().data('account_id');
    var office_id = $("#office_ids").val();
    var reporting_month = '<?=$reporting_month;?>';
    var report_id = '<?=$report_id;?>';
    var data = {'expense_account_id':expense_account_id,'office_id':office_id,'report_id':report_id,'reporting_month':reporting_month};
    
    var url = "<?=base_url();?>Financial_report/get_expense_account_comment";

    $.post(url,data,function(comment){
        var pane_count = row.children().length;
        var note_area_holder = $("#note_area_holder");

        if(pane_count == 1){
            var new_note_area_holder = note_area_holder.clone();
            new_note_area_holder.removeAttr('id');
            new_note_area_holder.removeClass('hidden').addClass('active_note_area');
            new_note_area_holder.css('margin-top','100px');
            new_note_area_holder.find('textarea').html(comment);
            row.append(new_note_area_holder);
        }
    });
    
    
}
</script>
