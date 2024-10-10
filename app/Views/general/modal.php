<script>
    function getRequest(url, on_success){
        $.post({
            url: url,
            beforeSend: function() {
                $("#overlay").css("display", "block");
            }
        }).done(function(response) {
            on_success(response);
        }).fail(function(xhr, status, error) {
            alert('Error has occurred');
        }).always(function() {
            $("#overlay").css("display", "none");
        });
    }


     function postRequest(url, data, on_success){
        $.post({
            url: url,
            data: data,
            beforeSend: function() {
                $("#overlay").css("display", "block");
            }
        }).done(function(response) {
            on_success(response);
        }).fail(function(xhr, status, error) {
            alert('Error has occurred');
        }).always(function() {
            $("#overlay").css("display", "none");
        });
    }

    function format_number(amount){
        let put_commas_on_amount = amount.toString().split(".");
        put_commas_on_amount[0] = put_commas_on_amount[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        // $('#bank_balance').val(put_commas_on_amount.join('.'));
        return put_commas_on_amount.join('.');
    }

    function unformat_number(number){
       return number.split(",").join("");
    }
    
    function pre_record_post() {}

    function on_record_post() {}

    function post_record_post() {
    $('select.select2:not([multiple])').each(function() {
        let firstOptionValue = $(this).find('option:first').val();
        $(this).val(firstOptionValue).trigger('change');
    });
    }

    function pre_row_insert() {}

    function on_row_insert() {}

    function post_row_insert() {}

    function pre_row_delete() {}

    function on_row_delete() {}

    function post_row_delete() {}

</script>