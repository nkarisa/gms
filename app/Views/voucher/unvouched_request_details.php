<div id="approved_request_detail" class="">
    <div class="row">
        <div class="col-xs-12" class="no-print" style="text-align:center;margin:10px;font-weight:bold;">
            <?=get_phrase("approved_request_details","Approved Request Details");?>
        </div>
    </div>

    <?php
    //print_r($result);
    if(count($result) > 0){

        $fields = array_keys($result[0]);
        //array_shift($fields);

        ?>

        <table class="table table-striped" id='approved_request_table'>
            <thead>
                <tr>
                    <th colspan='<?=count($fields);?>'>
                        <div class='btn btn-default' id='insert_all_request'><?=get_phrase('insert_all_to_voucher');?></div>
                    </th>
                </tr>
                <tr>    
                    <?php 
                        foreach($fields as $field){
                            if($field == 'request_detail_id'){
                                $field = get_phrase('action', 'action');
                            }
                    ?>
                        <th  nowrap='nowrap'><?=get_phrase($field);?></th>
                    <?php        
                        }
                    ?>
                </tr>
            </thead>
            <tbody>
                <?php 
                    foreach($result as $row){ 
                ?>
                    <tr>
                <?php
                        foreach($fields as $field){
                            $cell_value = $row[$field];

                            if($field == 'request_detail_id'){
                                $cell_value =  '<div class="btn btn-default map_request_to_voucher_row" id="'.$row[$field].'">'.get_phrase('add_to_voucher','Add to voucher').'</div>';   
                            }
                ?>  
                            <td nowrap='nowrap'><?=$cell_value;?></td>
                <?php 
                        }
                ?>
                    </tr>
                <?php        
                    }
                
                ?>
            </tbody>
        </table>
    <?php 
    }else{
    ?>
        <div style="text-align:center;"><?=get_phrase("no_ready_vouching_items","No Items ready for vouching available.");?></div>
    <?php 
    }
    ?>

</div>

<script>

$(".map_request_to_voucher_row").click(function(ev){

    let btn = $(this);

    // Load request details from the server
    get_request_details_for_voucher(btn.attr('id'));

    // Remove the row
    btn.closest('tr').remove();

});

function update_request_details_count_on_badge(){
    var badges = $(".requests_badge");
    var request_rows = $(".map_request_to_voucher_row");

    var current_count = request_rows.length;

    badges.html(current_count);

}

function get_request_details_for_voucher(request_detail_id){

    let data = {'voucher_number':$("#voucher_number").val(),'request_detail_id':request_detail_id}
    let url = '<?=base_url();?>Voucher/get_request_detail';

    $.post(url,data,function(response){
        var obj = JSON.parse(response);
        insertRowFromRequest(obj);
        //updateAccountAndAllocationField();
        update_request_details_count_on_badge();
       // console.log(response);
    });
}

function insertRowFromRequest(obj){
    var tbl_body = $("#tbl_voucher_body tbody");

    var description = obj['voucher_detail_description'];
    var quantity = obj['voucher_detail_quantity'];
    var unitcost = obj['voucher_detail_unit_cost'];
    var totalcost = obj['voucher_detail_total_cost'];
    var expense_account_id = obj['expense_account_id'];
    var project_allocation_id = obj['project_allocation_id'];
    var request_detail_id = obj['request_detail_id'];
    var project_allocation_name = obj['project_allocation_name'];
    var expense_account_name = obj['expense_account_name'];


    var cell = actionCell(); 
    cell += quantityCell(quantity); 
    cell += descriptionCell(description);
    cell += unitCostCell(unitcost); 
    cell += totalCostCell(totalcost); 
    cell += build_allocation_code_select(project_allocation_id,project_allocation_name); 
    cell += build_account_select(expense_account_id,expense_account_name); 
    cell += requestIdCell(request_detail_id); 

    tbl_body.append("<tr>"+cell+"</tr>");

    $('.body-input').prop('readonly','readonly');

    //updateAccountAndAllocationField(expense_account_id, project_allocation_id);
    
    $("#voucher_total").val(sumVoucherDetailTotalCost());
}

function build_allocation_code_select(project_allocation_id,project_allocation_name){
    var option = '<option value="'+project_allocation_id+'">'+project_allocation_name+'</option>';
    return "<td><select name='fk_project_allocation_id[]' class='form-control required body-input allocation' name='' id=''>"+option+"</select></td>";
}

function build_account_select(expense_account_id,expense_account_name){
    var option = '<option value="'+expense_account_id+'">'+expense_account_name+'</option>';
    return "<td><select name='voucher_detail_account[]' class='form-control required body-input account' name='' id=''>"+option+"</select></td>";
}

function run_detail_row(obj){

    var url = "<?=base_url();?><?=ucfirst($this->controller);?>/detail_row";

    var fields = {};

    $(".th_data").each(function(i,el){
        let key  = $(this).attr('id').replace('th_','');
        //alert(obj[key);
        fields[key] = obj[key];
      }); 

    var data = {fields};  

    insert_row(url,data);
}

$("#insert_all_request").on('click',function(){
    var request_rows = $(".map_request_to_voucher_row");
    var request_count = request_rows.length;

    $.each(request_rows,function(i,el){
        get_request_details_for_voucher($(el).attr('id'));
    });

    $("#approved_request_table tbody tr").remove();
});
</script>
