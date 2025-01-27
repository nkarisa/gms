<?php 
$financialReportLibrary =  new \App\Libraries\Grants\FinancialReportLibrary();
$columns = $financialReportLibrary->listTableVisibleColumns();
?>

<!-- <link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet"/> -->
<link href="https://cdn.datatables.net/datetime/1.4.1/css/dataTables.dateTime.min.css" rel="stylesheet"/>
<link href="https://cdn.datatables.net/searchbuilder/1.4.2/css/searchBuilder.dataTables.min.css" rel="stylesheet"/>
 
<!-- <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script> -->
<script src="https://cdn.datatables.net/datetime/1.4.1/js/dataTables.dateTime.min.js"></script>
<script src="https://cdn.datatables.net/searchbuilder/1.4.2/js/dataTables.searchBuilder.min.js"></script>

<!-- <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script> -->

<div style="text-align:center;">
    <div class = "btn btn-default center" id = 'btn_manage_columns' >Show/Hide Columns</div>
</div>

<div class="row">
    <div class = "col-xs-12 hide" id = "manage_columns" style = "margin-bottom: 20px;">
            <select class="form-control select2" id = "fields-select" multiple>
                <?php for($i = 0; $i < sizeof($columns); $i++){ ?>
                    <option data-column="<?=$i;?>" value = "<?=$i ;?>"><?=ucwords(str_replace('_',' ',$columns[$i]));?> [<?=$i + 1;?>]</option>
                <?php } ?>
            </select>
    </div>
 
</div>

