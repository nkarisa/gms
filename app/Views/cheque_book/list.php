<div class="row" style="margin-bottom:25px;">
  <div class="col-xs-12" style="text-align:center;">
        <?php
        $userLibrary = new App\Libraries\Core\UserLibrary();
            extract($result);
            if($show_add_button && $userLibrary->checkRoleHasPermissions(ucfirst($controller),'create')){
                echo add_record_button($controller, $has_details_table,null,$has_details_listing, $is_multi_row);
              }
        ?>
    </div>
</div>

<div class="row">
    <div class="col-xs-12">
        <table class="table table-striped nowrap" id="datatable">
            <thead>
                <tr>
                    <th nowrap><?=get_phrase('action','Action');?></th>
                    <?php 
                        foreach($columns as $column){
                    ?>
                            <th><?=get_phrase($column);?></th>
                    <?php
                        }
                    ?>
                </tr>
            </thead>
            <tbody>

            </tbody>
        </table>
    </div>
</div>

<script>
    //$(document).ready(function(){
        var url = "<?=base_url();?><?=$controller;?>/showList";
        const datatable = $("#datatable").DataTable({
            dom: 'lBfrtip',
            buttons: [
                'copyHtml5',
                'excelHtml5',
                'csvHtml5',
                'pdfHtml5',
            ],
            pagingType: "full_numbers",
            // stateSave:true,
            pageLength:10,
            order:[],
            serverSide:true,
            processing:true,
            language:{processing:'Loading ...'},
            ajax:{
                url:url,
                type:"POST",
            }
        });
   // });

   $("#datatable_filter").html(search_box());

    //});

    function search_box(){
    return '<?=get_phrase('search');?>: <input type="form-control" onchange="search(this)" id="search_box" aria-controls="datatable" />';
    }

    function search(el){
    datatable.search($(el).val()).draw();
    }

</script>