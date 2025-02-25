<?php

$funds_transfer_id = 0;
$funds_transfer_type = 0;
$funds_transfer_source_project_allocation_id = null;
$funds_transfer_target_project_allocation_id = null;
$funds_transfer_description = null;
$funds_transfer_amount = null;
$funds_transfer_source_account_id = null;
$funds_transfer_target_account_id = null;
$fk_office_id = 0;
$allocation_codes = [];
$source_accounts = [];
$destination_accounts = [];
$source_fund_balance = 0;
$destination_fund_balance = 0;

if (!empty($result['transfer_request'])) {
    extract($result['transfer_request']);
    $allocation_codes = $result['allocation_codes'];
    $source_accounts = $result['source_accounts'];
    $destination_accounts = $result['destination_accounts'];
    $source_fund_balance = $result['source_fund_balance'];
    $destination_fund_balance = $result['destination_fund_balance'];
}
//echo json_encode($pf);
?>
<div class="row">
    <div class="col-sm-12">

        <div class="panel panel-default" data-collapsed="0">
            <div class="panel-heading">
                <div class="panel-title">
                    <i class="fa fa-pencil"></i>
                    <?php echo get_phrase('edit_funds_transfer'); ?>
                </div>
            </div>
            <div class="panel-body" style="max-width:50; overflow: auto;">
                <a href="<?= base_url(); ?>funds_transfer/list" class="btn btn-primary" id="list_transfer"><?=get_phrase('list_fund_transfer_requests','List fund transfer requests.');?></a>
                <hr />
                <table class="table table-striped">
                    <tbody>
                        <tr>
                            <td colspan='2'><?= get_phrase("office_name", 'Office Name'); ?></td>
                            <td colspan='2'>
                                <select class='form-control required' id='office_id' name='fk_office_id' required>
                                    <option value=""><?= get_phrase('select_office', 'Select Office'); ?></option>
                                    <?php
                                    foreach (session()->hierarchy_offices as $office) {
                                        if (!$office['office_is_active']) continue;
                                    ?>
                                        <option value="<?= $office['office_id'];?>" <?= $fk_office_id == $office['office_id'] ? 'selected' : null; ?>><?= $office['office_name']; ?></option>
                                    <?php } ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td colspan='2'>
                                <?= get_phrase("transfer_type", "Transfer Type"); ?>
                            </td>
                            <td colspan='2'>
                                <select class="form-control" <?= $funds_transfer_type == 0 ? "disabled" : null ?> name="transfer_type" id="transfer_type" required>
                                    <option value="0"><?= get_phrase('select_transfer_type','Select transfer type'); ?></option>
                                    <option value="<?=array_search('income_transfer',transfer_types());?>" <?= $funds_transfer_type == array_search('income_transfer',transfer_types()) ? 'selected' : null; ?>><?= get_phrase(transfer_types()[1]); ?></option>
                                    <option value="<?=array_search('expense_transfer',transfer_types());?>" <?= $funds_transfer_type == array_search('expense_transfer',transfer_types()) ? 'selected' : null; ?>><?= get_phrase(transfer_types()[2]); ?></option>
                                </select>
                            </td>
                        </tr>

                        <tr>
                            <td colspan="4">
                                <textarea class="form-control" required name="transfer_description" id="transfer_description" rows="5" placeholder="<?= get_phrase('enter_transfer_details_here', 'Enter transfer details here'); ?>"><?= $funds_transfer_description != null ? $funds_transfer_description : null; ?></textarea>
                            </td>
                        </tr>

                        <tr>
                            <td style="font-weight:bold;"><?=get_phrase('transfer_order','Transfer Order');?></td>
                            <td style="font-weight:bold;"><?=get_phrase('allocation_code', 'Allocation Code');?></td>
                            <td style="font-weight:bold;"><?=get_phrase('account', 'Account');?></td>
                            <td style="font-weight:bold;"><?=get_phrase('current_fund_balance', 'Current fund balance');?></td>
                        </tr>

                        <tr>
                            <td style="font-weight:bold;"><?=get_phrase('transfer_source', 'Transfer Source');?></td>
                            <td>
                                <select class="form-control allocation_code type_dependant" name="source_allocation" id="source_allocation" <?= $funds_transfer_type == 0 ? "disabled" : null ?> required >
                                    <option value="0"><?=get_phrase('select_source_allocation_code', 'Select source allocation code');?></option>
                                    
                                    <?php 
                                        if($funds_transfer_type > 0){
                                            foreach($allocation_codes as $allocation_id => $allocation_code){
                                    ?>
                                        <option value="<?=$allocation_id;?>" <?=$allocation_id == $funds_transfer_source_project_allocation_id ? "selected": '';?>><?=$allocation_code;?></option>
                                    <?php 
                                            }
                                        }   
                                    ?>
                                
                                </select>
                            </td>
                            <td>
                                <select class="form-control type_dependant type_dependant_select accounts" name="source_account" id="source_account" <?= $funds_transfer_type == 0 ? "disabled" : null ?> required >
                                    <option value=""><?= get_phrase("select_source_account", "Select source account"); ?></option>
                                    <?php
                                    if (!empty($source_accounts)) {
                                        foreach ($source_accounts as $source_account_item => $source_account_code) {
                                    ?>
                                            <option value="<?= $source_account_item; ?>" <?= $source_account_item == $funds_transfer_source_account_id ? get_phrase("selected","Selected") : ''; ?>><?= $source_account_code; ?> </option>
                                    <?php
                                        }
                                    }
                                    ?>
                                </select>
                            </td>
                            <td class='fund_balance'><?=$funds_transfer_type > 0 ? $source_fund_balance : 0;?></td>
                        </tr>


                        <tr>
                            <td style="font-weight:bold;"><?=get_phrase('transfer_destination','Transfer Destination');?></td>
                            <td>
                                <select class="form-control allocation_code type_dependant" name="destination_allocation" id="destination_allocation" <?= $funds_transfer_type == 0 ? "disabled" : null ?> required >
                                    <option value="0"><?=get_phrase('select_destination_allocation_code','Select Destination Allocation Code');?></option>
                                    
                                    <?php 
                                        if($funds_transfer_type > 0){
                                            foreach($allocation_codes as $allocation_id => $allocation_code){
                                    ?>
                                        <option value="<?=$allocation_id;?>" <?=$allocation_id == $funds_transfer_target_project_allocation_id ? get_phrase("selected","Selected") : '';?> ><?=$allocation_code;?></option>
                                    <?php 
                                            }
                                        }   
                                    ?>
                                </select>
                            </td>
                            <td>
                                <select class="form-control type_dependant type_dependant_select accounts" name="destination_account" id="destination_account" <?= $funds_transfer_type == 0 ? "disabled" : null ?> required >
                                    <option value=""><?= get_phrase("select_destination_account", 'Select Destination Account'); ?></option>
                                    <?php
                                    if (!empty($destination_accounts)) {
                                        foreach ($destination_accounts as $destination_account_item => $destination_account_code) {
                                            
                                    ?>
                                            <option value="<?= $destination_account_item; ?>" <?= $destination_account_item == $funds_transfer_target_account_id ? get_phrase("selected","Selected") : ''; ?>><?= $destination_account_code; ?></option>
                                    <?php
                                        }
                                    }
                                    ?>
                                </select>
                            </td>
                            <td class='fund_balance'><?=$funds_transfer_type > 0 ? $destination_fund_balance : 0;?></td>
                        </tr>

                        <tr>
                            <td colspan='2' style="font-weight:bold;"><?= get_phrase("amount_to_be_transferred","Amount to be transfered"); ?></td>
                            <td colspan='2'>
                                <input type="text" required class="form-control type_dependant type_dependant_input" name="transfer_amount" id="transfer_amount" value="<?= $funds_transfer_amount != null ? $funds_transfer_amount : 0; ?>" <?= $funds_transfer_type == 0 ? "disabled" : null ?> />
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4">
                                <button <?= $funds_transfer_type == 0 ? "disabled" : null ?> id="submit" class="btn btn-success"><?= get_phrase("save",'Save'); ?></button>
                                <button id="clear" class="btn btn-success"><?= get_phrase("clear", 'Clear'); ?></button>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    function reset_form() {
        // Disable accounts and remove their options
        $(".type_dependant").each(function(i, elem) {
            if ($(elem).is("select")) {
                $(elem).find('option').not(':first').remove();
            }
            $(elem).attr("disabled", "disabled");
            $("#transfer_amount").val(0);
            $("#transfer_description").val("");
            $("#submit").attr('disabled', 'disabled');

        })

        $("#transfer_type").val(0);
        $(".fund_balance").html("")

    }

    function reset_allocation_code_accounts_and_amount(){
        //remove allocation code options
        $('#source_allocation').find('option').not(':first').remove();
        $('#destination_allocation').find('option').not(':first').remove();


        // Reset the accounts and allocation code selections and disable amount field
        $('.accounts option').filter(function(){
            return parseInt(this.value,10) > 0;
        }).remove();

        $(".allocation_code").prop("selectedIndex", 0);
        $(".accounts").attr('disabled','disabled');
        $("#transfer_amount").val(0);
        $("#transfer_amount").attr('disabled','disabled');
        $(".fund_balance").html("")

    }

    function enable_submit_button(){
        if(
            $("#source_allocation").val() > 0 
            && $("#source_account").val() > 0 
            && $("#destination_account").val() > 0 
            && $("#destination_allocation").val() > 0
        ){
            if($("#transfer_amount").val() > 0 && $("#transfer_description").val() != ""){
                $("#submit").removeAttr('disabled')
            }else{
                $("#submit").attr('disabled','disabled');
            }
            $("#transfer_amount").removeAttr('disabled');
        }else{
            $("#submit").attr('disabled','disabled');
            $("#transfer_amount").val(0);
            $("#transfer_amount").attr('disabled','disabled');
        }
    }

    function set_all_fields_except_office(){
        
        $("#transfer_type").prop("selectedIndex", 0);
        $("#transfer_type").attr('disabled','disabled');
        $("#transfer_description").val("");
        $("#submit").attr('disabled','disabled');

        reset_allocation_code_accounts_and_amount();
    }


    $('#office_id').on('change',function(){
        if($(this).val() > 0){
            $("#transfer_type").removeAttr('disabled');
        }else{
            // Reset the form
            set_all_fields_except_office();
            
        }
        
    })

    $("#transfer_type").on('change',function(){

        reset_allocation_code_accounts_and_amount()
        if($("#transfer_type").val() == 0){
            //prevent the form from submitting
            $("#submit").attr('disabled','disabled');
        }else{

            // Set Variables for Post
            
            const office_id = $("#office_id").val()

            const data = {office_id: office_id}
            const url = '<?= base_url(); ?>funds_transfer/funds_transfer_allocation_codes'

            // AJAX Post call

            $.post(url, data, function(response){

                // Enable allocation code fields
                $(".allocation_code").removeAttr('disabled');
                
                const response_json = JSON.parse(response);

                $.each(response_json, function(allocation_id, project_name) {
                    $(".allocation_code").append("<option value='" + allocation_id + "'>" + project_name + "</option>");
                })
            });

        }
    })



    $(".accounts").on('change',function(){
        let first_account = 0
        let second_account = 0
        
        const fund_balance_td = $(this).parent().next('td')
        const project_td = $(this).parent().prev('td')

        $(".accounts").each(function(i,e){
            const selected_account = $(e).val()

            if(first_account == 0){
                first_account = selected_account
            }else{
                second_account =selected_account
            }

            if(first_account == second_account){
                alert('<?=get_phrase('same_source_and_destination_accounts','Cannot have same source and destination accounts.');?>');
                $(e).prop('selectedIndex', 0)
            }
        })

        // Get the fund balance for the income account
        const funds_transfer_type = $("#transfer_type").val()
        const account_id = $(this).val()
        const office_id = $('#office_id').val()
        const project_allocation_id = project_td.find('.allocation_code').val()

        const data = {account_id: account_id, funds_transfer_type: funds_transfer_type, office_id: office_id, project_allocation_id: project_allocation_id}

        const url = "<?=base_url();?>ajax/funds_transfer/incomeAccountFundBalance"

        $.post(url,data,function(response){
            fund_balance_td.html(response)
        })
    })

    $(".allocation_code").on('change',function(){

        // Clear Fund Balances
        $(this).closest('tr').find('.fund_balance').html("")

        const allocation_direction = $(this).attr('id')
        const allocation_id = $(this).val()
        const funds_transfer_type = $("#transfer_type").val()
        const office_id = $("#office_id").val()

        const url = '<?= base_url(); ?>ajax/funds_transfer/fundsTransferAllocationAccounts'

        const data = {allocation_id: allocation_id, funds_transfer_type: funds_transfer_type, office_id: office_id}

        enable_submit_button()

        $.post(url, data, function(response){
            
            // Enable allocation code fields
            if(allocation_direction == 'source_allocation'){

                $("#source_account").removeAttr('disabled');
            
                // const response_json = JSON.parse(response);

                $('#source_account option').filter(function(){
                    return parseInt(this.value,10) > 0;
                }).remove();

                $.each(response, function(account_id, account_name) {
                    $("#source_account").append("<option value='" + account_id + "'>" + account_name + "</option>");
                })

            }else{

                $("#destination_account").removeAttr('disabled');
            
                const response_json = JSON.parse(response);

                $('#destination_account option').filter(function(){
                    return parseInt(this.value,10) > 0;
                }).remove();

                $.each(response_json, function(account_id, account_name) {
                    $("#destination_account").append("<option value='" + account_id + "'>" + account_name + "</option>");
                })

            }
            
            
        })
    })


    $(".accounts, .allocation_code, #transfer_amount").on('change',function(){
        enable_submit_button()
    })

    $("#transfer_amount").on('keyup',function(){
        enable_submit_button()
    })

    $("#transfer_description").on('keyup',function(){
        enable_submit_button()
    })


    $("#clear").on("click", function() {
        reset_form();
    })

    $("#submit").on("click", function() {
        $(this).attr("disabled", true);
        $("#clear").attr("disabled", true);
        // Prevent posting amount <= 0, no transfer type and source anmd destination accounts selected
        const data = {
            transfer_type: $("#transfer_type").val(),
            transfer_description: $("#transfer_description").val(),
            source_allocation: $("#source_allocation").val(),
            destination_allocation: $("#destination_allocation").val(),
            source_account: $("#source_account").val(),
            destination_account: $("#destination_account").val(),
            transfer_amount: $("#transfer_amount").val(),
            office_id: $("#office_id").val(),
        };

        const url = '<?= base_url(); ?>ajax/funds_transfer/postFundsTransfer/<?= $funds_transfer_id; ?>';

        //alert(JSON.stringify(data));

        $.post(url, data, function(response) {
            alert(response);
            window.location.href = "<?= base_url(); ?>funds_transfer/list";
        });
    })
</script>