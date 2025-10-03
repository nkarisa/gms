<script>
    if('<?= $action; ?>' == 'singleFormAdd'){
        document.getElementById("fk_pay_history_id").innerHTML = ""


        $("#fk_user_id").on('change', function() {
            const userId = $('#fk_user_id').val()
            const payHistoryURL = "<?=base_url();?>ajax/pay_history/getUserLatestPayHistory"

            $.post(payHistoryURL, { userId }, function (response) {
                $("#fk_pay_history_id").empty().append('<option value="">Select Pay History</option>');

                if(response) {
                    $.each(response, function(index, pay_history) {
                        // console.log(pay_history)
                        $("#fk_pay_history_id").append('<option value="' + pay_history.pay_history_id + '">' + pay_history.pay_history_name + '</option>');
                    });
                }
            })
        })
    }
</script>