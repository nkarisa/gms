<script>
    $('#fk_office_bank_id').on('change', function () {
        const office_bank_id = $('#fk_office_bank_id').val()
        
        const url = '<?=base_url();?>cheque_book_reset/validate_cheque_book_reset_timeframe/' + office_bank_id;

        $.get(url, function (response) {
            if(!response){
                alert('<?=get_phrase('cheque_book_reset_limit','You are not allowed to reset a cheque book twice within a month')?>');
                $('.save, .save_new').addClass('disabled');
            }else{
                if( $('.save, .save_new').hasClass('disabled')){
                    $('.save, .save_new').removeClass('disabled');
                }
            }
        })

    });
</script>