<script>
    // An alternate redirection instead of referrer address
    // const alt_referrer = '<?=base_url();?>budget_limit/single_form_add';

    // Remove the select2 class for budget year and budget tag since they don't populate as expected with options dynamically
    // Also remove the default options
    // $("#budget_year").removeClass("select2").html('');
    // $("#fk_budget_tag_id").removeClass("select2").html('');

    function onchange_fk_office_id(elem){
        const office_id = $(elem).val();
        const url = "<?=base_url();?>budget/list_valid_budget_years_for_office"
        const data = {
            office_id: office_id
        }

        $("#budget_year").html('');
        $("#fk_budget_tag_id").html('');
       
        $.post(url,data,function(fys){

            let options = "<option value=''><?=get_phrase('select_financial_year');?></option>";

            const fys_object = JSON.parse(fys);


            $.each(fys_object, function(i, fy){
                options += "<option value='"+fy+"'>FY"+fy+"</option>";
            });

            $("#budget_year").html(options);
        });
    }


    $("#budget_year").on('change',function(){
        const office_id = $("#fk_office_id").val();
        const budget_year = $(this).val();
        const data = {
            office_id:office_id,
            budget_year:budget_year
        }

        const url = "<?=base_url();?>budget/get_office_budget_tags";

        $.post(url,data,function(tags){
            let options = "<option value=''><?=get_phrase('select_budget_tag');?></option>";

            const tags_object = JSON.parse(tags);

            $.each(tags_object, function(i, tag_name){
                options += "<option value='"+i+"'>"+tag_name+"</option>";
            });

            $("#fk_budget_tag_id").html(options);
        });
    });

</script>