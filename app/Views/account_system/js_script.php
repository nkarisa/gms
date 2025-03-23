<script>

    const hiddenFormFields = $("#account_system_settings, #default_project_start_date")
    const hiddenFormFieldsFormGroups = hiddenFormFields.closest('.form-group')

    $(document).ready(function(){
        hiddenFormFieldsFormGroups.css('display','none')
        hiddenFormFields.prop('required',false)
    })
    
    $(document).on('change', function(){
        const account_system_level = $("#account_system_level")
        const template_account_system = $('#template_account_system')
        const account_system_level_value = account_system_level.val();
        const url = "<?=base_url();?>ajax/account_system/getValidReportingAccountSystems"
        const data = {account_system_level: account_system_level_value}

        $.post(url, data, function(response){
            template_account_system.html("");

            $.each(response, function(){
                template_account_system.append('<option value="' + this.account_system_id + '">' + this.account_system_name + '</option>')
            });

        });
    })

    $("#account_system_level").on('change', function(){
        const account_system_level = $(this).val()

        if(account_system_level == 4){
            hiddenFormFieldsFormGroups.css('display','block')
            hiddenFormFields.prop('required',true)
        }else{
            hiddenFormFieldsFormGroups.css('display','none')
            hiddenFormFields.prop('required',false)
        }
    })
</script>