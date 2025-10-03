<script>
    if('<?= $action; ?>' == 'singleFormAdd'){
        document.getElementById("fk_user_id").innerHTML = ""
    }

    $("#fk_office_id").on('change', function() {
        const officeId = $("#fk_office_id").val()
        const payHistoryUserURL = "<?=base_url();?>ajax/pay_history/getPayHistoryUsers"

        $.post(payHistoryUserURL, { officeId }, function(response) {
            // Populate select2 options for fk_user_id select 

            $("#fk_user_id").empty().append('<option value="">Select User</option>');

            if(response && response.length > 0) {
                $.each(response, function(index, user) {
                    $("#fk_user_id").append('<option value="' + user.user_id + '">' + user.user_firstname + ' ' + user.user_lastname + '</option>');
                });
            }
        })
    })
    
</script>