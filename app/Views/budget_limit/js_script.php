<script>
    
    alert($id);
    console.log($id);

    const sub_action  = '<?=esc($subAction);?>'
        
        if(sub_action == 'budget'){
            const budget_id = '<?=hash_id($id,'decode');?>';
            get_update_budget_limit_list(budget_id)
            list_of_unused_income_accounts()

            // $('.save').on('click', function (){
            //     on_record_post()
            // })
        }
    
    function get_update_budget_limit_list(budget_id){
        
        const url = '<?=base_url();?>budget_limit/update_budget_limit_list/' + budget_id;
        // alert(id)
        $.get(url, function (resp) {
            $('#widget_holder').html(resp)
        })
    }

    function list_of_unused_income_accounts(){
        const id = $('#fk_budget_id').val();
        const url = '<?=base_url();?>budget_limit/list_of_unused_income_accounts/' + id;

        $.get(url, function (resp) {
            const obj = JSON.parse(resp)
            // alert(resp)
            let options = '<option value = "">Select Income Account</option>'
            $.each(obj, function (i,el) {
                options += '<option value = "' + el.income_account_id + '">' + el.income_account_name + '</option>'
            })
            
            $("#fk_income_account_id").html(options)
        })
    }
    
    $(document).ready(function () {

        const sub_action = '<?=$subAction;?>'
        
       if(sub_action  == 'budget'){
           const budget_id = '<?=hash_id($id,'decode');?>'
            get_office_unsubmitted_recent_budget(budget_id)
        }

        function get_office_unsubmitted_recent_budget(budget_id){

            const url = "<?=base_url();?>budget_limit/get_budget_by_id";
            const data = {
                budget_id
            }

            $.post(url, data, function (resp){
                const obj = JSON.parse(resp)
                let options = '<option value = "0">Select a budget</option>'
                options += '<option value = "' + obj.budget_id + '">FY' + obj.budget_year + ' ' + obj.budget_tag_name + '</option>'
                $("#fk_budget_id").html(options)
            })
        }

        const action  = '<?=$action?>';

        if(action == 'edit'){
            $("#fk_office_id").prop('disabled','disabled')
            $("#fk_budget_tag_id").prop('disabled','disabled')
            $("#budget_limit_year").prop('disabled','disabled')
        }
    });

    $('#fk_budget_id').on('change', function () {
        const budget_id = $('#fk_budget_id').val();
        get_update_budget_limit_list(budget_id)
    })


    $('#fk_budget_id').on('change', function () {
        list_of_unused_income_accounts()
    })

    $('.save_new').on('click', function () {
        const budget_id = sub_action == 'budget' ? '<?=hash_id($id,'decode');?>' : $("#fk_budget_id").val();
        get_update_budget_limit_list(budget_id)
    })


</script>