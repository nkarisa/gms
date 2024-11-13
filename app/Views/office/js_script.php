<script>
    $(document).ready(function () {
        document.getElementById("defaultOpen").click();
    })

    function openPage(context_definition_id, elmnt, color) {
        let i, tabcontent, tablinks;
        tabcontent = document.getElementsByClassName("tabcontent");
        for (i = 0; i < tabcontent.length; i++) {
            tabcontent[i].style.display = "none";
        }

        tablinks = document.getElementsByClassName("tablink");

        for (i = 0; i < tablinks.length; i++) {
            tablinks[i].style.backgroundColor = "";
        }

        elmnt.style.backgroundColor = color;

        // datatable.ajax.reload();
        load_datatable(context_definition_id)
    }


    function load_datatable(context_definition_id) {
        console.log(context_definition_id)
        // let context_definition_id = pageName // Can be fcp_offices, cluster_offices, base_or_regions
        let url = "<?= base_url("$controller/showList");?>"

        $(".datatable").dataTable().fnDestroy();

        const datatable = $("#datatable").DataTable({
            dom: 'lBfrtip',
            "bDestroy": true,
            buttons: [
                'copyHtml5',
                'excelHtml5',
                'csvHtml5',
                'pdfHtml5',
            ],
            pagingType: "full_numbers",
            stateSave: true,
            pageLength: 10,
            order: [],
            serverSide: true,
            processing: true,
            language: { processing: 'Loading ...' },
            ajax: {
                url: url,
                type: "POST",
                "data": function (d) {
                    d.customfield_fk_context_definition_id = context_definition_id;
                }
            }
        });

    }


$('#cluster').on('change', function(){
    var move_fcps_btn=$('#click_move_fcps');
    if($(this).val()!=0){
        move_fcps_btn.removeAttr('disabled');
    }else{
        move_fcps_btn.attr('disabled','disabled');
    }
});

$('#click_move_fcps').on('click', function(){
  let message='<?=get_phrase('Are_sure_you_want_to_change_the_cluster_of_the_selected_FCPs')?>';
  let office_ids = get_office_ids();

  //Check if checkbox is empty
   if(office_ids.length==0){
    alert('<?=get_phrase('You_have_to_select_atleast_an_FCP_and_cluster')?>');
    return false;
   }

  //Update fcp to clusters
  if(confirm(message) == true){
    let url='<?=base_url();?>ajax/office/massUpdateForFcps';
    data={
      'cluster_office_id':$('#cluster').val(),
      'office_ids':get_office_ids(),
    }

    $.post(url,data,function(res){
      if(res.message){
        load_datatable(1)
      }
    });

  }
  
});

//Check or uncheck 
function check_or_uncheck_checkbox() {
  $(document).on("click", ".checkbox", function(event) {
      if($(this).is(":checked")){
        $(this).attr('checked',true);
      }else{
        $(this).attr('checked',false);
      }
    });
}

function get_office_ids() {
  // Populate the office ids of checked checkboxes
  var office_ids=[];

  $('.checkbox').each(function(){
    if($(this).is(":checked")){
        let office_id=$(this).attr("id");
        office_ids.push(office_id);
    }
  });

  return office_ids
  
}
</script>