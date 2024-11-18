<?php 

use \App\Libraries\System\Widgets\WidgetBase;

$userLibrary = new \App\Libraries\Core\UserLibrary();

extract($result['vouchers']);
$sum_of_accounts = count($accounts['income']) + count($accounts['expense']);
$role_has_voucher_create_permission = $userLibrary->checkRoleHasPermissions(ucfirst('voucher'),'create');   
?>

<style>
    .align-right{
        text-align:right;
    }

</style>
<div id = 'full_journal_view'>
    <div class="row">
    <div class="col-xs-12">
        <?=WidgetBase::load('comment');?>
    </div>
    </div>

    <div class="row">
    <div class="col-xs-12">
        <?php
        if(is_office_in_context_offices($office_id)){
            echo WidgetBase::load('position','position_1');
        }
        
        ?>
    </div>
    </div>

    <div class="row">
    <div class="col-xs-6">
            <a href='<?=base_url();?>voucher/multiFormAdd' class='btn btn-default <?=!$role_has_voucher_create_permission? 'hidden' :'';?>'><?=get_phrase('add_voucher');?></a>
            <div class='btn btn-default voucher_unselected' id = 'select_all_vouchers'><?=get_phrase('select_all_voucher');?></div>
            <div class='btn btn-default hidden' id = 'print_vouchers'><?=get_phrase('print_vouchers');?></div>
        </div>
        
        <?php if($office_has_multiple_bank_accounts){?>
            <div class='col-xs-6'>
                <div class='form-group'>
                    <label class='control-label col-xs-2'>
                        <?=get_phrase('select_office_bank');?>
                    </label>
                    <div class='col-xs-10'>
                        <select class='form-control' id='select_office_bank'>
                            <option value='0'><?=get_phrase('all_bank_accounts');?></option>
                            <?php foreach($office_bank_accounts as $office_bank_account){?>
                                <option value='<?=$office_bank_account['office_bank_id'];?>'><?=$office_bank_account['bank_name'].' - '.$office_bank_account['office_bank_name'].' - '.$office_bank_account['office_bank_account_number'];?></option>
                            <?php }?>
                        </select>
                    </div>
                </div>
            </div>
        <?php }?>
    </div>


    <div class='row'>
        <div class='col-xs-12' style='overflow-x: auto' id='journal_row'>
            <?php include 'ajax_view.php';?>
        </div>
    </div>

</div>

<script>

$(document).ready(function(){
    var income_account_ids = JSON.parse("<?=json_encode(array_keys($accounts['income']));?>");
    var expense_account_ids = JSON.parse("<?=json_encode(array_keys($accounts['expense']));?>");
    
    //alert(income_account_ids.length);
    
    $.each(income_account_ids,function(index,elem){

        var spread_income = $(".spread_income_"+elem);
        var sum = 0;
        $.each(spread_income,function(idx,el){
            sum += parseFloat($(el).html().replace(/,/g,""));
        });
        $(".total_income_"+elem).html(accounting.formatNumber(sum,2));
    });

    $.each(expense_account_ids,function(index,elem){
        var spread_expense = $(".spread_expense_"+elem);
        var sum = 0;
        $.each(spread_expense,function(idx,el){
            sum += parseFloat($(el).html().replace(/,/g,""));
        });
        $(".total_expense_"+elem).html(accounting.formatNumber(sum,2));
    });
    
});


      $(".action").click(function(){

          var cnfrm = confirm('Are you sure you want to perform this action?');

          if(cnfrm){
            alert('Action performed successfully');
          }else{
            alert('Process aborted');
          }
      });

      $("#select_office_bank").on('change',function(){
        var url = "<?=base_url();?>journal/get_office_bank_journal";
        var data = {
            'office_bank_id':$(this).val(),
            'action':'<?=$uri->getSegment(2);?>',
            'journal_id':'<?=$uri->getSegment(3);?>',
            'office_id':'<?=$office_id;?>',
            'transacting_month':'<?=$transacting_month;?>'
            };

        $.post(url,data,function(response){
            //alert(response);
            $('#journal_row').html(response);
        });
      });

      $('#print_vouchers').on('click', function () {
        let url = '<?=base_url();?>ajax/voucher/printableVoucher'
        let checkedCheckboxes = [];
        const journal_id = '<?=$id;?>'
        
        $('.select_voucher:checked').each(function() {
            checkedCheckboxes.push($(this).val());
        });
        
        const data = {
            voucher_ids: checkedCheckboxes,
            journal_id
        }
        
        $.post(url, data, function (response) {
            $('#full_journal_view').html(response)
        })
      })
</script>