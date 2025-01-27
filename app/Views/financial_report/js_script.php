<?php 
?>

<script>
console.log('test');
// $(document).ready(function(){

    $('#btn_manage_columns').on('click', function () {
        let manage_cols_div = $("#manage_columns")

        if(manage_cols_div.hasClass('hide')){
            manage_cols_div.removeClass('hide')
        }else{
            manage_cols_div.addClass('hide')
        }
    })

    $('#fields-select > option').each(function (i,el) {
            var column = datatable.column($(el).attr('data-column'));

            if(column.visible()){
                $(el).prop("selected", true);
            }else{
                $(el).prop("selected", false);
            }
        })
        

        $('#fields-select').on('change', function() {

            var columns = [];
            $.each($("#fields-select option:selected"), function(){            
                columns.push($(this).val());
            });
            
            $.each(columns, function (i, el) {
                // alert(el)
                var column = datatable.column(el);
                column.visible(true);
            })


            var $sel = $(this),
                val = $(this).val(),
                $opts = $sel.children(),
                prevUnselected = $sel.data('unselected');
            // create array of currently unselected 
            var currUnselected = $opts.not(':selected').map(function() {
                return this.value
            }).get();
            // see if previous data stored
            if (prevUnselected) {
                var unselected = currUnselected.reduce(function(a, curr) {
                if ($.inArray(curr, prevUnselected) == -1) {
                    a.push(curr)
                }
                return a
                }, []);
                // "unselected" is an array if it has length some were removed
                if (unselected.length) {
                    //alert('Unselected is ' + unselected.join(', '));
                    let unselectedColumnIndex = unselected.join(', ')
                    var column = datatable.column(unselectedColumnIndex);
                    column.visible(!column.visible());
                }
            }
            $sel.data('unselected', currUnselected)
        }).change();
        
// })
    
</script>