<script>
    $(document).ready(function () {
        $('#custom_financial_year_reset_date').prop('readonly','readonly')
        
        $("#fk_office_id").on('change', function () {
            const office_id = $(this).val()
            const url = "<?=base_url();?><?=$controller;?>/get_custom_financial_year_reset_date/" + office_id

            $.get(url, function (custom_financial_year_reset_date) {
                $('#custom_financial_year_reset_date').val(custom_financial_year_reset_date)
                // $(".datepicker").datepicker("setDate", custom_financial_year_reset_date)
            })
        })
    });

    function addYears(num){
        var currYear = new Date().getFullYear();
        var currMonth = new Date().getMonth();
        return new Date(currYear+num, currMonth, 1);
    }

   $(".datepicker").datepicker({
        beforeShowDay: function(d){
            if( d.getDate() === 1 ){
                return true;
            }
            return false;
        },
        startDate: addYears(-10),
        endDate: addYears(3),
        format: 'yyyy-mm-dd',
    });
</script>