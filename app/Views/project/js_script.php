<script>
$(document).ready(function(){
    $("#project_cost").closest('.form-group').hide();
});

$("#project_end_date").on('change',function(){
    if($(this).val() !== ""){
        $("#project_cost").closest('.form-group').show();
        $("#project_is_default").closest('.form-group').hide();
    }else{
        $("#project_cost").closest('.form-group').hide();
        $("#project_is_default").closest('.form-group').show();
    }
});
</script>