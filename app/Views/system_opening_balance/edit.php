<?php 
extract($result);
$columns = array_chunk(array_keys($header),$config->master_table_columns,true);
?>

<style>
  .header_label {
    font-weight: bold;
    text-align: center;
  }

  .row {
    margin-bottom: 25px;
  }
  .well{
    color: red;
    text-align: center;
    font-weight: bold;
    font-size:xx-large;
  }
</style>

<div class="row">
  <div class="col-xs-12" id='print_pane'>
    <table class="table">
      <thead>
        <tr>
          <th colspan="<?=$config->master_table_columns;?>" style="text-align:center;"><?=get_phrase($segments[1].'_master_record');?>
          </th>
        </tr>
      </thead>
      <tbody>
        <?php

            foreach ($columns as $row) {
          ?>
            <tr>
          <?php
              foreach ($row as $column) {
                $column_value = $header[$column];             
          ?>
                <td>
                  <span style="font-weight:bold;">
                    <?php
                        echo get_phrase($column);
                    ?>:</span> &nbsp;
                  <?php
                    echo $column_value;
                  ?>
                </td>
          <?php
              }
          ?>
              </tr>
          <?php
            }
          ?>
          
      </tbody>
    </table>
  </div>
</div>

<hr/>

<?php


if(empty($office_banks)){
?>
    <div class = "well">
        <?=get_phrase("missing_bank_account_for_system_opening_balance",'You are not allowed to edit opening balances without associating this office to a bank first.');?>
    </div>
<?php
}else{
?>

<?php echo form_open("", array('id' => 'frm_system_opening_balance', 'class' => 'form-horizontal form-groups-bordered validate', 'enctype' => 'multipart/form-data')); ?>

    <div class="row">
        <div class="col-xs-12 header_label">
            <div id="reset_cash_balance" class='btn btn-danger'><?= get_phrase('reset_cash_balance'); ?></div>
        </div>
    </div>
    <hr />

    <input type="hidden" value="<?=hash_id($id, 'decode')?>" name="system_opening_balance_id" />

    <div class="row">
        <div class="col-xs-12 header_label">
            <span style = 'color:red;'><?=get_phrase('notice_to_choose_office_bank','Choose an office bank account below to proceed loading the balances');?></span>
        </div>
    </div>
    <hr />

    <div class="row">
    
        <div class="col-xs-6 header_label">
            <?= get_phrase('bank_balance') ?>
        </div>

        <div class="col-xs-6 header_label">
            <?= get_phrase('cash_balance') ?>
        </div>
    </div>

    <hr />

    <div class = 'row'>
    <div class = 'col-xs-6'>
        <div class="form-group">
        <label class="control-label col-xs-6"><?= get_phrase('choose_bank_account'); ?></label>
        <div class="col-xs-6">
            <select class="form-control mandatory" id="office_bank_id" name="office_bank_id">
            <option value=""><?= get_phrase('select_a_bank_account'); ?></option>
            <?php foreach($office_banks as $office_bank){?>
                <option value = '<?=$office_bank['office_bank_id'];?>'><?=$office_bank['office_bank_name'];?></option>
            <?php }?>
           
            </select>
        </div>
        </div>

        <div class="form-group">
            <label class="control-label col-xs-6"><?= get_phrase('book_bank_closing_balance'); ?> <span style='color:brown;font-weight:bold;'>(A)</span></label>
            <div class="col-xs-6">
            <input type="number" class="form-control proof_of_cash bank_reconciliation_fields mandatory" id="book_bank_balance" name="book_bank_balance" value="0" />
            </div>
        </div>

    </div>

    <div class = 'col-xs-6'>
        <?php foreach ($cash_boxes as $cash_box_id => $cash_box) { ?>
            <div class='form-group'>
            <label class='control-label col-xs-6'><?= $cash_box; ?></label>
            <div class="col-xs-6">
                <input type="number" id = "office_cash_<?=$cash_box_id;?>" class="form-control proof_of_cashproof_of_cash proof_of_cash mandatory" value="0" name="cash_balance[<?=$cash_box_id;?>]" />
            </div>
            </div>
        <?php } ?>
    </div>
    </div>

    <hr />

    <div class='row'>
    <div class='col-xs-12'>
        <div class='form-group'>
        <label class='control-label col-xs-6'><?= get_phrase('total_cash'); ?> <span style='color:brown;font-weight:bold;'>(B)</span></label>
        <div class="col-xs-6">
            <input type="number" class="form-control" onkeydown="return false"  name="total_cash" id="total_cash" value="0" />
        </div>
        </div>
    </div>
    </div>

    <hr />

    <div class="row">
    <div class="col-xs-12">
        <table class="table table-stripped balance_table" id="fund_balance_table">
        <thead>
            <tr>
            <th class="header_label" colspan="7"><?= get_phrase('fund_balance'); ?></th>
            </tr>
            <tr>
            <th colspan="7" style="text-align:center;">
                <div id="insert_fund_balance" class="btn btn-success"><?= get_phrase('insert_fund_balance_row'); ?></div>
                <div id="reset_fund_balance" class="btn btn-danger"><?= get_phrase('reset_fund_balance_report'); ?></div>
            </th>
            </tr>
            <tr>
            <th><?= get_phrase('action'); ?></th>
            <th><?= get_phrase('project'); ?></th>
            <th><?= get_phrase('account'); ?></th>
            <th><?= get_phrase('month_opening'); ?></th>
            <th><?= get_phrase('month_income'); ?></th>
            <th><?= get_phrase('month_expense'); ?></th>
            <th><?= get_phrase('closing_balance'); ?></th>
            </tr>
        </thead>
        <tbody>

        </tbody>
        <tfoot>
            <tr>
            <td style="font-weight: bold;" colspan='6'>
                <?= get_phrase('total_fund_balance'); ?> <span style='color:brown;font-weight:bold;'>(C)</span>
            </td>
            <td><input class="form-control" type='number' onkeypress="return false;" name='total_fund_balance' id='total_fund_balance' value="0" /></td>
            </tr>
            <tr>
            <td style="font-weight: bold;" colspan='5'><?= get_phrase('proof_of_cash_check'); ?></td>
            <td>
                <i class='label label-danger' id="proof_of_cash_check"><?= get_phrase('proof_of_cash_error'); ?></i>
                <span style='color:brown;font-weight:bold;'>D = (B = C)</span>
            </td>
            </tr>
        </tfoot>
        </table>
    </div>
    </div>

    <hr />

    <div class='row'>
    <div class="col-xs-6 header_label"><?= get_phrase('outstanding_cheques'); ?></div>
    <div class="col-xs-6 header_label"><?= get_phrase('deposits_in_transit'); ?></div>
    </div>

    <hr />

    <div class='row'>
    <div class="col-xs-6">
        <table class='table table-stripped balance_table' id='outstanding_cheque_table'>
        <thead>
            <tr>
                <th colspan="5" style="text-align:center;">
                <div id="insert_outstanding_cheque" class="btn btn-success"><?= get_phrase('insert_outstanding_cheque_row'); ?></div>
                <div id="reset_outstanding_cheque" class="btn btn-danger"><?= get_phrase('reset_outstanding_cheque_report'); ?></div>
            </th>
            </tr>
            <tr>
            <th><?= get_phrase('action'); ?></th>
            <th><?= get_phrase('transaction_date'); ?></th>
            <th><?= get_phrase('cheque_number'); ?></th>
            <th><?= get_phrase('description'); ?></th>
            <th><?= get_phrase('amount'); ?></th>
            </tr>
        </thead>
        <tbody>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" style = 'font-weight:bold;'><?=get_phrase('total_outstanding_cheques');?> <span style='color:brown;font-weight:bold;'>(E)</span></td>
                <td><input type = 'number' class = "form-control"  onkeypress="return false;" name = 'total_outstanding_cheque'  id = "total_outstanding_cheque" /></td>
            </tr>
        </tfoot>
        </table>
    </div>

    <div class="col-xs-6">
        <table class='table table-stripped balance_table' id='deposit_in_transit_table'>
        <thead>
            <tr>
                <th colspan="4" style="text-align:center;">
                <div id="insert_deposit_in_transit" class="btn btn-success"><?= get_phrase('insert_deposit_in_transit_row'); ?></div>
                <div id="reset_deposit_in_transit" class="btn btn-danger"><?= get_phrase('reset_deposit_in_transit_report'); ?></div>
            </th>
            </tr>
            <tr>
            <th><?= get_phrase('action'); ?></th>
            <th><?= get_phrase('transaction_date'); ?></th>
            <th><?= get_phrase('description'); ?></th>
            <th><?= get_phrase('amount'); ?></th>
            </tr>
        </thead>
        <tbody>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" style = 'font-weight:bold;'><?=get_phrase('total_deposit_in_transit');?> <span style='color:brown;font-weight:bold;'>(F)</span></td>
                <td><input type = 'number' class = "form-control"  onkeypress="return false;" name = 'total_deposit_in_transit'  id = "total_deposit_in_transit" /></td>
            </tr>
        </tfoot>
        </table>
    </div>
    </div>

    <hr/>

    <div class = 'row'>
    <div class = 'col-xs-6 header_label'><?=get_phrase('reconciliation_statement');?></div>

    <div class = 'col-xs-6 header_label'><?=get_phrase('bank_statements');?></div>
    </div>

    <hr/>

    <div class = 'row'>
    <div class = 'col-xs-6'>

            <div class = 'form-group'>
            <label class = 'control-label col-xs-6'><?=get_phrase('statement_date');?></label>
            <div class = "col-xs-6">
                <input type="text" class="form-control datepicker reconciliation_statement mandatory mandatory" id = 'statement_date' name="statement_date" data-format = 'yyyy-mm-dd' onkeydown="return false;"  />
            </div>
            </div>

            <div class = 'form-group'>
            <label class="control-label col-xs-6"><?= get_phrase('statement_balance'); ?> <span style='color:brown;font-weight:bold;'>(G)</span></label>
            <div class="col-xs-6">
                <input type="number" class="form-control reconciliation_statement bank_reconciliation_fields mandatory" id="statement_balance" name="statement_balance" value="0" />
            </div>
            </div>
            
            <div class = 'form-group'>
            <label class="control-label col-xs-6"><?= get_phrase('reconciled_statement_balance'); ?> <span style='color:brown;font-weight:bold;'>H = (G + F - E)</span></label>
            <div class="col-xs-6">
                <input type="number" class="form-control reconciliation_statement mandatory" onkeypress="return false;" id="reconciled_statement_balance" name="reconciled_statement_balance" value="0" />
            </div>
            </div>

            <div class = 'form-group'>
            <label class="control-label col-xs-6"><?= get_phrase('bank_reconciled_difference'); ?> <span style='color:brown;font-weight:bold;'>J = A - H</span></label>
            <div class="col-xs-6">
                <input type="number" class="form-control reconciliation_statement mandatory" onkeypress="return false;" id="bank_reconciled_difference" name="bank_reconciled_difference" value="0" />
            </div>
            </div>

            <div class = 'form-group'>
                <div class = 'col-xs-12' style = 'text-align:center;'>
                    <i class = 'label label-danger' id = 'reconciliation_error'><?=get_phrase('bank_reconciliation_error');?></i> <span style='color:brown;font-weight:bold;'>I = (H = A)</span>
                </div>
            </div>
    </div>

    <div class = 'col-xs-6'>
        <div class = 'form-group'>
            <div class = 'col-xs-12'>
                <input type="file" id="upload_statement" class="form-control" name="file[]" multiple />
            </div>
            <div class = 'col-xs-12'>
                <div id="error" style="color: red;"></div>
            </div>
            <div class = "col-xs-12" id="list_statements">
                    
            </div>
        </div>
    </div>
    </div>

    <hr />

    <div class="row">
    <div class = 'col-xs-12 header_label'>
            <div id = "save" class = 'btn btn-success save save_exit'><?=get_phrase('save');?></div>
            <div id = "save_continue" class = 'btn btn-info save save_continue'><?=get_phrase('save_and_continue','Save and Continue');?></div>
            <div id = "reset" class = 'btn btn-danger'><?=get_phrase('reset');?></div>
    </div>
    </div>
    </form>

<?php 
}
?>

<?php
// include "view_script.php";
?>

<script>

    $(document).ready(function (){
        $('input[type=text], input[type=number], input[type=file], select').not('#office_bank_id').prop('disabled','disabled')
        $('.btn').addClass('disabled')
    })

    function enableInputs(){
        $('input[type=text], input[type=number], input[type=file], select').not('#office_bank_id').removeAttr('disabled')
        $('.btn').removeClass('disabled')
    }

    function remove_first_rows(){

        $('.balance_table').each(function(i, elem){
            $(elem).find('tbody tr').first().remove()
        })
    }

    // function populate_bank_statements(){
    //     $('#list_statements').html('Hello');
    // }

    $('#office_bank_id').on('change', function (){
        const load_office_bank_balances_url = '<?=base_url();?>ajax/system_opening_balance/loadOfficeBankBalances'
        const office_bank_id = $('#office_bank_id').val()
        const system_opening_balance_id = '<?=hash_id($id,'decode');?>'
        const data = {office_bank_id,system_opening_balance_id}

        $.post(load_office_bank_balances_url, data, function (responseObj) {
            enableInputs()
            reset_balance_form()
            remove_first_rows()
            
            // const responseObj =JSON.parse(response)
            
            $('#list_statements').html(responseObj.bank_statements_uploads)

            $('#book_bank_balance').val(responseObj.opening_bank_balance)

            $.each(responseObj.opening_cash_balance,function(cash_box_id, cash_box_details) {
                $("#office_cash_" + cash_box_id).val(cash_box_details.amount)
            })

            $('#total_cash').val(responseObj.opening_total_cash)

            // Fund Balance 
            let total_fund_balance = 0
            for (const key in responseObj.fund_balance) {
                
                const balance_row = responseObj.fund_balance[key];
                let is_first_row = false 
                // if(key == 0){is_first_row = true}
                const closing = parseFloat(balance_row.opening) +parseFloat(balance_row.income) - parseFloat(balance_row.expense)
                total_fund_balance += closing
                const balances = {
                    opening: balance_row.opening,
                    income: balance_row.income,
                    expense: balance_row.expense,
                    closing
                }

                options = {is_first_row, projects_options: create_projects_options(balance_row.project_id),income_account_options: create_project_income_account_options(balance_row.income_account_id, balance_row.income_account_name), balances}                
                
                $('#fund_balance_table').find('tbody').append(fund_balance_row(options))
            }

            $('#total_fund_balance').val(total_fund_balance.toFixed(2))

            // Outstanding Cheques 
            let total_outstanding_cheques = 0
                // console.log(responseObj.outstanding_cheques)
                for (const key in responseObj.outstanding_cheques) {

                    let is_first_row = false 
                    // if(key == 0){is_first_row = true}
                    const balance_row = responseObj.outstanding_cheques[key];
                    total_outstanding_cheques += parseFloat(balance_row.amount)
                    const initial_outstanding_cheque_data = {
                        transaction_date: balance_row.transaction_date,
                        cheque_number: balance_row.cheque_number,
                        description: balance_row.description,
                        amount: balance_row.amount
                    }

                    options = {is_first_row, data: initial_outstanding_cheque_data}             
                    
                    $('#outstanding_cheque_table').find('tbody').append(outstanding_cheque_row(options))
                }
                // console.log(total_outstanding_cheques)
                $('#total_outstanding_cheque').val(total_outstanding_cheques.toFixed(2))

                // Deposit In Transit 
                let total_deposit_transit = 0
                for (const key in responseObj.deposit_transit) {

                    let is_first_row = false 
                    // if(key == 0){is_first_row = true}
                    const balance_row = responseObj.deposit_transit[key];
                    total_deposit_transit += parseFloat(balance_row.amount)
                    const initial_deposit_transit_data = {
                        transaction_date: balance_row.transaction_date,
                        description: balance_row.description,
                        amount: balance_row.amount
                    }

                    options = {is_first_row, data: initial_deposit_transit_data}             
                    
                    $('#deposit_in_transit_table').find('tbody').append(deposit_transit_row(options))
                }

                $('#total_deposit_in_transit').val(total_deposit_transit.toFixed(2))

            
            // Reconciliation Statement
            const reconciliation_statement = responseObj.reconciliation_statement
            $('#statement_date').val(reconciliation_statement.statement_date)
            $('#statement_balance').val(reconciliation_statement.statement_balance)

            // Valication and other computations
            bank_reconciliation_check()
            proof_of_cash_check()
            bank_reconciliation_check()
            update_reconciled_bank_balance()
        })
    })

    function create_project_income_account_options(income_account_id, income_account_name){
        const option = `<option value = '${income_account_id}'>${income_account_name}</option>`

        return option
    }
    
</script>

