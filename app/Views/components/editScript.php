<script>

    let code_field_or_element = '';

    $('.form-control').keydown(function () {
        $(this).removeAttr('style');
        let code_elem = $(this).attr('id');
        let code_str_exist = code_elem.split('_');

        if (code_str_exist.includes('code')) {
            code_field_or_element = code_elem;
        }
    });

    $(".edit, .edit_continue").on('click', function (ev) {
        let elem = $(this);

        //Check if all required fields are filled
        let empty_fields_count = 0;

        $('.form-control').each(function (i, el) {
            if ($(el).hasClass('select2')) {
            } else {
                if ($(el).val().trim() == '' && $(el).attr('required')) {
                    $(el).css('border', '1px solid red');
                    empty_fields_count++;
                }
            }
        });

        if (empty_fields_count > 0) {
            alert('1 or more required fields are empty');
        } else {
            pre_record_post();

            let url = "<?= base_url() . $controller; ?>/<?= $action; ?>/<?= $uri->getSegment(3, 0); ?>";
            let data = $(this).closest('form').serializeArray();

            postRequest(
                url,
                data,
                function (response) {
                    if (response.flag == false) {
                        $('#' + code_field_or_element).css('border', '1px solid red');
                        alert(response.message);
                        return false;

                    } else {
                        on_record_post();
                
                            alert(response.message);
                            //If Edit , use the browser history and go back
                            if(elem.hasClass('back')){
                            location.href = document.referrer      
                        } 
                    }
                }
            );

            post_record_post();
        }

        ev.preventDefault();
    });

    $('.reset').on('click', function(){
        document.getElementById('edit_form').reset();
    })
</script>