<style>
    .control-label {
        text-align: left;
    }

    .center {
        text-align: center;
    }
</style>

<?php 
    extract($result);
    echo hash_id($id, 'decode');
?>

<div class='row'>
    <div class='col-xs-12'>
        <div class="panel panel-default" data-collapsed="0">
            <div class="panel-heading">
                <div class="panel-title">
                    <i class="entypo-plus-circled"></i>
                    <?php echo get_phrase('budget'); ?>
                </div>
            </div>

            <div class="panel-body" style="max-width:50; overflow: auto;">
                <?php echo form_open("", array('id' => 'frm_budget', 'class' => 'form-horizontal form-groups-bordered validate', 'enctype' => 'multipart/form-data')); ?>

                <div class='form-group'>

                    <!-- <span> -->
                        <label class='control-label col-xs-1'><?= get_phrase('office_name'); ?></label>
                        <div class='col-xs-3'>
                            <?php 
                                echo  $fields['office_name'];
                            ?>
                        </div>
                        
                    <!-- </span> -->

                    <!-- <span> -->
                        <label class='control-label col-xs-1'><?= get_phrase('financial_year'); ?></label>
                        <div class='col-xs-3'>
                            <?php 
                                echo  $fields['budget_year'];
                            ?>
                        </div>
                    <!-- </span> -->

                    <!-- <span> -->
                        <label class='control-label col-xs-1'><?= get_phrase('budget_tag_name'); ?></label>
                        <div class='col-xs-3'>
                            <select class="form-control required account_fields" name='header[fk_budget_tag_id]' id='fk_budget_tag_id'>
                                <option value=""><?= get_phrase('select_budget_tag_name'); ?></option>
                            </select>
                        </div>
                    <!-- </span> -->
                </div>

                <div class='form-group'>
                    <div class='col-xs-12 center'>
                        <div class='btn btn-default btn-reset'><?= get_phrase('reset'); ?></div>
                        <div class='btn btn-default btn-insert hidden'><?= get_phrase('add_budget_limit_row'); ?></div>
                        <div class='btn btn-default btn-save save hidden'><?= get_phrase('save'); ?></div>
                    </div>
                </div>

                <div class='form-group'>
                    <div class='col-xs-12'>
                        <table class='table table-striped' id='tbl_budget_limit'>
                            <thead>
                                <tr>
                                    <th><?= get_phrase('action'); ?></th>
                                    <th><?= get_phrase('income_account'); ?></th>
                                    <th><?= get_phrase('budget_limit_amount'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><div class="btn btn-danger remove_row"><?=get_phrase('remove_row','Remove Row');?></div></td>
                                    <td>
                                        <select class="form-control fk_income_account_id" name = "details[fk_income_account_id][]">
                                            <option value=""><?=get_phrase('select_income_account');?></option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type = 'number' class="form-control budget_limit_amount" name = "details[budget_limit_amount][]" value = "0" />
                                    </td>
                                </tr>
                            </tbody>

                        </table>
                    </div>
                </div>

                <div class='form-group'>
                    <div class='col-xs-12 center'>
                        <div class='btn btn-default btn-reset'><?= get_phrase('reset'); ?></div>
                        <div class='btn btn-default btn-save save hidden'><?= get_phrase('save'); ?></div>
                    </div>
                </div>
            </form>
            </div>
        </div>
    </div>
</div>

<script>    
    // Hide first row remove row button
    $('#tbl_budget_limit').find('tbody tr').eq(0).find('.remove_row').hide()

    $('.btn-insert').on("click", function (){
        // alert('Hello')
        const table_body = $('#tbl_budget_limit').find('tbody')
        const first_row = $('#tbl_budget_limit').find('tbody tr').eq(0)
        const clone_first_row = first_row.clone()
        clone_first_row.find('.remove_row').show()
        clone_first_row.find('.budget_limit_amount').val('0')
        table_body.append(clone_first_row)
    })

    $(document).on('click','.remove_row', function (){
        const row = $(this).closest('tr')
        row.remove()
    })

    $('.fk_office_id').on('change', function () {
        // alert('Hello')
        const office_id = $(this).val()
        const url = "<?=base_url();?>budget/list_budgetable_income_account/" + office_id

        $.get(url, function (resp) {
            const obj = JSON.parse(resp)
            // alert(resp)
            let options = '<option value = "">Select Income Account</option>'
            $.each(obj, function (i,el) {
                options += '<option value = "' + el.income_account_id + '">' + el.income_account_name + '</option>'
            })
            
            $(".fk_income_account_id").html(options)

            
        })
    })

    $('#fk_office_id, #budget_year, #fk_budget_tag_id').on('change', function () {
        
        const office_id = $('#fk_office_id').val()
        const budget_year = $('#budget_year').val()
        const fk_budget_tag_id = $('#fk_budget_tag_id').val()

        if(office_id > 0 && budget_year > 0 && fk_budget_tag_id > 0){
            $('.btn-insert, .save').removeClass('hidden')
        }
    })

    $(document).on('change',".fk_income_account_id", function () {
        // alert($(this).val())
       const selected_value = $(this).val()
       $(this).addClass('active_selection')

       for(const income_select_elem of $(".fk_income_account_id")){
         let selected_other_values = $(income_select_elem).val()
        if(selected_other_values == selected_value && !$(income_select_elem).hasClass('active_selection')){
            alert('This account has been already been selected')
            $('.active_selection').prop("selectedIndex", 0);
        }
       }

       $(this).removeClass('active_selection')
    })

    $(document).on('click','.budget_limit_amount', function (){
        const amount = $(this).val()

        if(amount == 0){
            $(this).val('')
        }
    })

    $(document).on('keyup','.budget_limit_amount', function (){
        const amount = $(this).val()

        if(amount < 0){
            alert('<?=get_phrase('negative_amount_restricted','Negative amount not allowed');?>')
            $(this).val('')
        }
    })

    $('.save').on('click', function (){
        const url = "<?=base_url();?>budget/post_budget"
        const data = $('#frm_budget').serializeArray()

        const cnfrm = confirm('<?=get_phrase('confirm_budget_submit', 'Are you sure you want to create this budget record?');?>')

        if(!cnfrm){
            alert('<?=get_phrase('process_aborted');?>');
            return false;
        }

        $.post(url, data, function (resp) {

            const obj = JSON.parse(resp)

            if(Object.keys(obj).includes('budget_id')){
                window.location.replace('<?=base_url();?>budget/view/' + obj.budget_id); 
            }else{
                
                alert(obj.message)
            }
        })
    })

    $('#fk_budget_tag_id').on('change', function () {
        const office_id = $('#fk_office_id').val()
        const budget_year = $('#budget_year').val()
        const budget_tag_id = $(this).val()
        const url = '<?=base_url();?>/budget_limit/get_set_budget_limit'
        const data = {
            office_id,
            budget_year,
            budget_tag_id
        }

        $.post(url, data, function (resp){
            // alert(resp)
            const obj = JSON.parse(resp)
            const first_row = $('#tbl_budget_limit').find('tbody tr').eq(0)
            
            $.each(obj, function(index, row){
                
                if(index > 0){
                    let clone_first_row = first_row.clone()
                    clone_first_row.find('.remove_row').show()
                    clone_first_row.find('.fk_income_account_id').val(row.income_account_id).change();
                    clone_first_row.find('.budget_limit_amount').val(row.budget_limit_amount).change();
                    $('#tbl_budget_limit').find('tbody').append(clone_first_row)
                }else{
                    // alert(index)
                    $('#tbl_budget_limit').find('tbody tr').eq(index).find('.fk_income_account_id').val(row.income_account_id).change()
                    $('#tbl_budget_limit').find('tbody tr').eq(index).find('.budget_limit_amount').val(row.budget_limit_amount).change()
                }
            })

        })
    })
</script>