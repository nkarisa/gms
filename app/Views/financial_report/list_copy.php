<style>
    .summary_reports {
        margin-left: 15px;
    }

    .toggle-vis, #fields-select {
        margin-top: 10px;
    }

    .dataTables_filter { display: none; }
    /**.dataTables_filter, .dataTables_info { display: none; } */

    .reset_filter {
        margin-left: 10px;
    }
</style>


<!-- <link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet"/> -->
<link href="https://cdn.datatables.net/datetime/1.4.1/css/dataTables.dateTime.min.css" rel="stylesheet"/>
<link href="https://cdn.datatables.net/searchbuilder/1.4.2/css/searchBuilder.dataTables.min.css" rel="stylesheet"/>
 
<!-- <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script> -->
<script src="https://cdn.datatables.net/datetime/1.4.1/js/dataTables.dateTime.min.js"></script>
<script src="https://cdn.datatables.net/searchbuilder/1.4.2/js/dataTables.searchBuilder.min.js"></script>

<!-- <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script> -->



<div class="row" style="margin-bottom:25px;">
  <div class="col-xs-12" style="text-align:center;">
        <?php
            $userLibrary = new \App\Libraries\Core\UserLibrary(); 
            extract($result);
            if($show_add_button && $userLibrary->checkRoleHasPermissions(ucfirst($this->controller),'create')){
                echo add_record_button($this->controller, $has_details_table,null,$has_details_listing, $is_multi_row);
              }
        ?>
    </div>
</div>

<div class="row" style="margin-bottom:25px;">
  <div class="col-xs-12" style="text-align:center;">
        <?php 
            //if($this->user_model->check_role_has_permissions(ucfirst('transactions_summary_report'),'read')){
        ?>
            <!-- <span class = 'summary_reports'><a href = '<?=base_url()?>transactions_summary_report/list'><?=get_phrase('transactions_summary_report');?></a></span> -->
        <?php
            //  }
      
            // if($this->user_model->check_role_has_permissions(ucfirst('fund_balance_summary_report'),'read')){
            if($userLibrary->checkRoleHasPermissions(ucfirst('fund_balance_summary_report'),'read')){
        ?>
            <!-- <span class = 'summary_reports'></span> -->
            <a class = "btn btn-default" href = '<?=base_url()?>fund_balance_summary_report/list'><?=get_phrase('fund_balance_summary_report');?></a>
        <?php
              }
        ?>

        <div class = "btn btn-default" id = 'btn_manage_columns'>Show/Hide Columns</div>
  </div>
</div>

<?php 
            extract($result);
            if($show_add_button && $this->user_model->check_role_has_permissions(ucfirst($this->controller),'create')){
                echo add_record_button($this->controller, $has_details_table,null,$has_details_listing, $is_multi_row);
              }
        ?>

<div class="row">
    <div class = "col-xs-12 hide" id = "manage_columns" style = "margin-bottom: 20px;">
            <select class="form-control select2" id = "fields-select" multiple>
                <option data-column = "0" value = "0">Action</option>
                <?php for($i = 0; $i < sizeof($columns); $i++){ ?>
                    <option data-column="<?=$i + 1;?>" value = "<?=$i + 1;?>"><?=ucwords(str_replace('_',' ',$columns[$i]));?> [<?=$i + 1;?>]</option>
                <?php } ?>
            </select>
    </div>
    <div class="col-xs-12">
        <table class="table table-striped display nowrap" id="datatable" style="width:100%">
            <thead>
                <tr>
                    <th class = 'notexport'><?=get_phrase('action');?></th>
                    <?php 
                        foreach($columns as $column){
                            $class_name = '';
                            if($column == 'track_number'){
                                $class_name = 'notexport';
                            }
                    ?>
                            <th class = '<?=$class_name;?>'><?=get_phrase($column);?></th>
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

    $('#btn_manage_columns').on('click', function () {
        let manage_cols_div = $("#manage_columns")

        if(manage_cols_div.hasClass('hide')){
            manage_cols_div.removeClass('hide')
        }else{
            manage_cols_div.addClass('hide')
        }
    })

    $(document).ready(function(){

        var url = "<?=base_url();?>ajax/financial_report/showList";
        const datatable = $("#datatable").DataTable({
            // dom: 'lBfrtip',
            dom: 'QlBtip', //'QBfrtip' lrt
            buttons: [
                {
                    extend: 'excel',
                    text: 'Export Records',
                    className: 'btn btn-default',
                    exportOptions: {
                        columns: ':visible:not(.notexport)'
                    }
                },
                {
                    text: 'Reset Filters',
                    action: function ( e, dt, node, config ) {
                        clearLocalStorage()
                    }
                }
            ],
            initComplete: function () {
            var btns = $('.dt-button');
            btns.addClass('btn btn-success reset_filter');
            btns.removeClass('dt-button');

            }
            ,
            pagingType: "full_numbers",
            // bFilter: false,
            stateSave:true,
            pageLength:10,
            order:[],
            serverSide:true,
            processing:true,
            language:{
                processing:'Loading ...',
                searchBuilder: {
                    title: '<?=get_phrase('searchbuilder_title','Create a Search Filter');?>'
                }
            },
            scrollX: "600px",
            ajax:{
                url:url,
                type:"POST",
            },
            "columnDefs": [ 
                {"targets": 0,"orderable": false, 'searchable': false},
                {"targets": 1,"orderable": false, 'searchable': false}, 
                
            ],
            search: {
                return: true,
            },
            searchBuilder: {
                // enterSearch: true,
                depthLimit: 2,
                conditions: {
                    num: {
                        'null': null,
                        '!null': null,
                    },
                    'html-num-fmt': {
                        'null': null,
                        '!null': null,
                    },
                    'num-fmt': {
                        'null': null,
                        '!null': null,
                    },
                    'html-num':{
                        'null': null,
                        '!null': null,
                    },
                    string: {
                        '!null': null,
                        'null': null,
                    },
                    html: {
                        '!null': null,
                        'null': null,
                    },
                    date: {
                        '!=': null,
                        '!between': null,
                        '!null': null,
                        'null': null,
                    }
                }
            },
        });

        function clearLocalStorage(){
            // localStorage.removeItem("DataTables_datatable_/grants/Financial_report/list");
            localStorage.removeItem("DataTables_datatable_/<?=explode('/',FCPATH)[4];?>/<?= $controller; ?>/showList");
            alert('Filters reset successfully');
            window.location.reload()
        }

        $("#datatable_filter").html(search_box());

        // $('a.toggle-vis').each(function (i,el) {
        //     var column = datatable.column($(el).attr('data-column'));

        //     if(column.visible()){
        //         $(el).removeClass("btn-danger");
        //         $(el).addClass('btn-default');
        //     }else{
        //         $(el).removeClass("btn-default");
        //         $(el).addClass('btn-danger');
        //     }
        // })
        
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


        // $('a.toggle-vis').on('click', function (e) {
        //     // Get the column API object
        //     var column = datatable.column($(this).attr('data-column'));

        //     if(column.visible()){
        //         $(this).removeClass("btn-default");
        //         $(this).addClass('btn-danger');
        //     }else{
        //         $(this).removeClass("btn-danger");
        //         $(this).addClass('btn-default');
        //     }

        //     e.preventDefault();
 
 
        //     // Toggle the visibility
        //     column.visible(!column.visible());

        //     $this.toggleClass('btn-danger');

        // });

   });


    function search_box(){
    return '<?=get_phrase('search');?>: <input type="form-control" onchange="search(this)" id="search_box" aria-controls="datatable" />';
    }

    function search(el){
    datatable.search($(el).val()).draw();
    }

    // function getAllLocalStorageItems() {
    //     var items = [];
    //     for (var i = 0; i < localStorage.length; i++) {
    //         var key = localStorage.key(i);
    //         var value = localStorage.getItem(key);
    //         items.push({ key: key, value: value });
    //     }
    //     return items;
    // }

    // // Usage
    // var allItems = getAllLocalStorageItems();
    // console.log(allItems);

    

</script>