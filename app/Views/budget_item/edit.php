<style>
.center{
    text-align:center;
}
</style>

<?php 

extract($result);

// echo json_encode($interventions);

$budget_item = array_values($budget_item_details)[0];
// echo json_encode($budget_item);
$budget_item_marked_for_review = $budget_item['budget_item_marked_for_review'];
$source_budget_item_id = $budget_item['budget_item_source_id'];
$budget_id = $budget_item['budget_id'];

if($budget_item_marked_for_review){
    $months_to_freeze = array_slice($months_to_freeze, 0, -3, true);
}

$total = array_sum(array_column($budget_item_details,'budget_item_detail_amount'));

?>

<div class="row">
    <div class='col-xs-12'>
        <div class="panel panel-default" data-collapsed="0">
       	    <div class="panel-heading">
           	    <div class="panel-title" >
           		    <i class="entypo-pencil"></i>
					    <?php echo get_phrase('edit_budget_item_for_');?> <?=$office->office_code.' - '.$office->office_name.' : '.get_phrase('FY').$office->budget_year;?>
           	    </div>
            </div>
	    
            <div class="panel-body"  style="max-width:50; overflow: auto;">	
                <?php echo form_open("" , array('id'=>'frm_budget_item','class' => 'form-horizontal form-groups-bordered validate', 'enctype' => 'multipart/form-data'));?>
                    
                   <!-- <input type='text' name='source_budget_item_id' value=''/>
                   <input type='text' name='budget_item_marked_for_review' value='0'/> -->
                    <div class='form-group'>
                        <div class='col-xs-12 center'>
                            <div class='btn btn-icon pull-left' id='btn_back'><i class='fa fa-arrow-left'></i></div>

                            <div class='btn btn-default btn-save'><?=get_phrase('save');?></div>
                            <!-- <div class='btn btn-default btn-save-new'><?=get_phrase('save_and_continue');?></div> -->
                        </div>
                    </div>                    

                    <div class="form-group">
                        <label class='control-label col-xs-2'><?=get_phrase('expense_account');?></label>
                        <div class='col-xs-2'>
                            <select name='fk_expense_account_id' id='fk_expense_account_id'  class='form-control resetable'>
                                <?php foreach($expense_accounts as $expense_account){?>
                                    <option value="<?=$expense_account->expense_account_id;?>" <?php if($current_expense_account_id == $expense_account->expense_account_id) echo "selected";?>><?=$expense_account->expense_account_name;?></option>
                                <?php }?>
                            </select>
                        </div>

                        <label class='control-label col-xs-2'><?=get_phrase('budget_limit_remaining_amount');?></label>
                        <div class='col-xs-2'>
                            <input type="text" class="form-control" id="budget_limit_amount" readonly="readonly" value="<?=$budget_limit_amount;?>"/>
                        </div>

                    </div>

                    <?php 
                    if(isset($this->session->system_settings['use_pca_objectives']) && $this->session->system_settings['use_pca_objectives']){
                ?>
                <div class='form-group'>
                    <div class="col-xs-6">
                        <select class = 'form-control resetable' id = 'pca_objective' name = 'pca_objective'>
                            <option value=""><?=get_phrase('select_an_objective');?></option>
                            <?php 
                                if(!empty($pca_objectives)){
                                foreach($pca_objectives as $pca_objective_id => $pca_objective){ ?>
                                    <option value="<?=$pca_objective_id;?>" <?=$budget_item['objective']->pca_strategy_objective_id == $pca_objective_id ? 'selected' : ''?> ><?=$pca_objective;?></option>
                            <?php 
                                }
                                }else{
                            ?>
                                <option selected value="<?=$budget_item['objective']->pca_strategy_objective_id;?>"><?=$budget_item['objective']->pca_strategy_objective_name;?></option>
                            <?php 
                                }
                            ?>
                        </select>
                    </div>

                    <div class="col-xs-6">
                        <select class = 'form-control resetable' id = 'pca_intervention' name = 'pca_intervention'>
                            <option selected value="<?=$budget_item['objective']->pca_strategy_intervention_id;?>"><?=$budget_item['objective']->pca_strategy_intervention_name;?></option>
                            <?php 
                                if(!empty($interventions)){
                                foreach($interventions as $pca_strategy_intervention_id => $pca_strategy_intervention){ ?>
                                    <option value="<?=$pca_strategy_intervention_id;?>" <?=$budget_item['objective']->pca_strategy_intervention_id == $pca_strategy_intervention_id ? 'selected' : ''?> ><?=$pca_strategy_intervention;?></option>
                            <?php 
                                }
                                }else{
                            ?>
                                <option selected value="<?=$budget_item['objective']->pca_strategy_intervention_id;?>"><?=$budget_item['objective']->pca_strategy_intervention_name;?></option>
                            <?php 
                                }
                            ?>
                        </select>
                    </div>
                </div>

                <?php }?>

                    <div class='form-group'>
                        <div class="col-xs-12">
                            <textarea name='budget_item_description' id='budget_item_description' placeholder="<?=get_phrase('describe_budget_item');?>"  class='form-control resetable'><?=$budget_item['budget_item_description'];?></textarea> 
                        </div>         
                    </div>


                    <div class='form-group'>
                        <label class="control-label col-xs-1"><?=get_phrase('quantity');?></label>
                        <div class="col-xs-2">
                            <input type="number" class="form-control resetable frequency_fields" id = "budget_item_quantity" name="budget_item_quantity"  value="<?=$budget_item['budget_item_quantity'];?>"/>
                        </div>   
                        
                        <label class="control-label col-xs-1"><?=get_phrase('unit_cost');?></label>
                        <div class="col-xs-2">
                            <input type="number" class="form-control resetable frequency_fields" id = "budget_item_unit_cost" name="budget_item_unit_cost"  value="<?=$budget_item['budget_item_unit_cost'];?>"/>
                        </div>    

                        <label class="control-label col-xs-1"><?=get_phrase('often');?></label>
                        <div class="col-xs-2">
                            <input type="number" class="form-control resetable frequency_fields" id = "budget_item_often" name="budget_item_often" value="<?=$budget_item['budget_item_often'];?>" max="12" min="1" />
                        </div>   
                        
                        <label class="control-label col-xs-1"><?=get_phrase('total');?></label>
                        <div class="col-xs-2">
                            <input type="number" class="form-control resetable total_fields" id = "frequency_total" readonly = "readonly" value="<?=$budget_item['budget_item_total_cost'];?>" name=""  />
                        </div> 

                    </div>

                    <div class='form-group'>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th><?=get_phrase('action');?></th>
                                    <?php 
                                        foreach($months as $month){
                                    ?>
                                        <th><?=get_phrase($month['month_name']);?></th>
                                    <?php 
                                        }
                                    ?>
                     
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><div class='btn btn-danger' id='btn-clear'><?=get_phrase('clear');?></div></td>
                                    
                                    <?php foreach($months as $month){ 

                                            $readonly = '';

                                            if(in_array($month['month_id'],$months_to_freeze)){
                                                $readonly = 'readonly';
                                            }
                                            
                                            $cell_amount = isset($budget_item_details[$month['month_number']]['budget_item_detail_amount']) ? $budget_item_details[$month['month_number']]['budget_item_detail_amount'] : 0;
                                        ?>
                                        <td>
                                        <input type='text' <?=$readonly;?> id='' value='<?=$cell_amount;?>' name='fk_month_id[<?=$month['month_id'];?>]' value='0' class='form-control month_spread' />
                                        </td>
                                    <?php }?>
                                
                                </tr>
                            </tbody>      
                        </table>
                    </div>

                    <div class='form-group'>
                       
                        <div class='col-xs-2'>
                            <input type='number' readonly='readonly' name='budget_item_total_cost' id='budget_item_total_cost'  class='form-control resetable total_fields' value='<?=$total;?>' />
                        </div>
                    </div>

                    <div class='form-group'>
                        <div class='col-xs-12 center'>
                            <div class='btn btn-default btn-save'><?=get_phrase('save');?></div>
                            <!-- <div class='btn btn-default btn-save-new'><?=get_phrase('save_and_continue');?></div> -->
                        </div>
                    </div>                
                    <!--Hidden fields-->
                    <input type='hidden' value='<?=hash_id($id,'decode');?>' name='fk_budget_id' id='fk_budget_id' />
                </form>
            </div>    
                  
    </div>
</div>

<script>

$(".form-control").on('change',function(){
   if($(this).val() !== ''){
     $(this).removeAttr('style');
   }
});

$("#fk_project_allocation_id").on('change',function(){
    var project_allocation_id = $(this).val();
    var url = "<?=base_url();?>Budget_item/project_budgetable_expense_accounts/"+project_allocation_id;

    let option = '<option value=""><?=get_phrase('select_expense_account');?></option>';

    $('#fk_expense_account_id').html(option);


    if(!$.isNumeric(project_allocation_id)){
        return false;
    }

    $.get(url,function(response){
        var accounts_obj = JSON.parse(response);

        $.each(accounts_obj,function(i,el){
            option += '<option value="'+accounts_obj[i].expense_account_id+'">'+accounts_obj[i].expense_account_name+'</option>';
        });

        $('#fk_expense_account_id').html(option);
    });
    
});

$('.month_spread').focusout(function(){
    if(!$.isNumeric($(this).val())){
        $(this).val(0);
    }
});

$('.month_spread').focusin(function(){
    if($(this).val() == 0 && !$(this).attr('readonly')){
        $(this).val('');
    }
});

$('.month_spread').on('change',function(){
    if($(this).val() < 0){
        alert('<?=get_phrase('negative_values_not_allowed');?>');
        $(this).val(0);
    }

    $('.month_spread').removeAttr('style');
    $('#budget_item_often').removeAttr('style');
});

$('.frequency_fields').on('change', function() {
    if ($(this).val() < 0) {
        alert('<?= get_phrase('negative_values_not_allowed'); ?>');
        $(this).val(0);
    }

    $('.month_spread').removeAttr('style');
    $('#budget_item_often').removeAttr('style');
});

function compute_sum_spread(){
    var sum_spread = 0;

    $('.month_spread').each(function(index,elem){
        if($(elem).val() > 0){
            sum_spread = sum_spread + parseFloat($(elem).val());
        }
    });

    $('#budget_item_total_cost').val(sum_spread);
}

$('.month_spread').bind('keyup blur',function(){
    compute_sum_spread();
});

$("#btn-clear").on('click',function(){
    $.each($(".month_spread"),function(i,el){
        if($(el).attr('readonly')){
            
        }else{
            $(el).val(0);
        }
    });

    compute_sum_spread();
});

$(".btn-save-new").on('click',function(){
    var count_of_empty_fields = 0;

    let count_spread_cell_with_amount_gt_zero = $('.month_spread').filter(function() {
                    return parseFloat($(this).val()) > 0;
                }).length;

    $('.form-control').each(function(i,el){
        if($(el).val() == ''){
            count_of_empty_fields++;
            $(el).css('border','1px solid red');
        }
    });

    if(count_of_empty_fields > 0){
        alert('<?=get_phrase("one_or_more_fields_are_empty");?>');
        return false;
    }

    if(count_spread_cell_with_amount_gt_zero != parseFloat($('#budget_item_often').val())){
        $('#budget_item_often, .month_spread').css('border', '1px solid red');
        alert('<?= get_phrase("spread_not_matching_frequency","The month spreading does match the frequency given"); ?>');
        return false;
    }

    save(false);
    resetForm();
});



$(".btn-save").on('click',function(){

    let count_spread_cell_with_amount_gt_zero = $('.month_spread').filter(function() {
                    return parseFloat($(this).val()) > 0;
                }).length;

    var count_of_empty_fields = 0;

    $('.form-control').each(function(i,el){
        if($(el).val() == ''){
            count_of_empty_fields++;
            $(el).css('border','1px solid red');
        }
    });

    if(count_of_empty_fields > 0){
        alert('<?=get_phrase("one_or_more_fields_are_empty");?>');
        return false;
    }

    if(!compute_totals_match()){
        alert('<?=get_phrase("computation_mismatch")?>');
        return false;
    }

    if(count_spread_cell_with_amount_gt_zero != parseFloat($('#budget_item_often').val())){
        $('#budget_item_often, .month_spread').css('border', '1px solid red');
        alert('<?= get_phrase("spread_not_matching_frequency","The month spreading does match the frequency given"); ?>');
        return false;
    }

    save();
});

$("#budget_item_quantity, #budget_item_often, #budget_item_unit_cost").on('keyup',function(){
    var qty = $("#budget_item_quantity").val();
    var unit_cost = $("#budget_item_unit_cost").val();
    var often = $("#budget_item_often").val();
    var frequency_total = 0;

    if(qty!= 0 && unit_cost != 0 && often != 0){
        frequency_total = parseFloat(qty) * parseFloat(unit_cost) * parseFloat(often);
    }
    
    $("#frequency_total").val(frequency_total.toFixed(2));
});

function compute_totals_match(){
    var frequency_compute =  parseFloat($("#frequency_total").val());
    var budget_item_total_cost = parseFloat($("#budget_item_total_cost").val());
    var compute_totals_match = false;

    if(frequency_compute == budget_item_total_cost){
        compute_totals_match = true;
        $(".total_fields").removeAttr('style');
    }else{
        $(".total_fields").css('border','1px red solid');
    }
    //alert(compute_totals_match);
    return compute_totals_match;
}

function save(go_back = true){

    let frm = $("#frm_budget_item");

    let data = frm.serializeArray();    

    const source_budget_item_id = {
        name: 'source_budget_item_id',
        value: '<?=$source_budget_item_id?>'
    }

    const budget_item_marked_for_review = {
        name: 'budget_item_marked_for_review',
        value: '<?=$budget_item_marked_for_review?>'
    }

    const budget_item_id = '<?=hash_id($id,'decode');?>';

     data.push(source_budget_item_id);
     data.push(budget_item_marked_for_review);

    let url = "<?=base_url();?>ajax/budget_item/updateBudgetItem/"+budget_item_id;

    $.ajax({
        url:url,
        data:data,
        type:"POST",
        success:function(response){
            $(".btn-save").addClass('disabled')
            alert(response);
            if(go_back) {
                location.href = document.referrer;
            }
        }
    });
}

$("#btn_back").on('click',function(){
    location.href = document.referrer;
});

$(".btn-reset").on('click',function(){
    resetForm();
});

function resetForm(elem){
    $.each($('.resetable'),function(i,el){
        $($(el).val(null))
    });

    $.each($('.month_spread'),function(i,el){
        $($(el).val(0));
    });
}

$('#pca_objective').on('change', function () {
            const objective = $(this).val();
            const url  = '<?=base_url();?>budget_item/ajax_get_objective_interventions'
            const data = {
                objective
            }

            $.post(url, data, function (response) {

                const interventions = JSON.parse(response);
                let options = '<option value = ""><?=get_phrase('select_an_intervention');?></option>';

                $.each(interventions, function (i, el) {
                    options += '<option value = "' + i + '">' + el + '</option>';
                });

                $('#pca_intervention').html(options)
            })
        })
</script>