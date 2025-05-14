<!-- Include jQuery UI library -->
<!-- <script src="https://code.jquery.com/ui/1.13.0/jquery-ui.min.js"></script> -->
<!-- Include jQuery UI CSS -->
<!-- <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.0/themes/smoothness/jquery-ui.css"> -->
<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.10.0/css/bootstrap-datepicker.min.css"
      integrity="sha512-34s5cpvaNG3BknEWSuOncX28vz97bRI59UnVtEEpFX536A7BtZSJHsDyFoCl8S7Dt2TPzcrCEoHBGeM4SUBDBw=="
      crossorigin="anonymous" referrerpolicy="no-referrer"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.10.0/js/bootstrap-datepicker.min.js"
        integrity="sha512-LsnSViqQyaXpD4mBBdRYeP6sRwJiJveh2ZIbW41EBrNmKxgr/LFZIiWT6yr+nycvhvauz8c2nYMhrP80YhG7Cw=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.sumoselect/3.4.9/jquery.sumoselect.min.js"
        integrity="sha512-+Ea4TZ8vBWO588N7H6YOySCtkjerpyiLnV7bgqwrQF+vqR8+q/InGK9WDZx5d6VtdGRoV6uLd5Dwz2vE7EL3oQ=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery.sumoselect/3.4.9/sumoselect.min.css"
      integrity="sha512-vU7JgiHMfDcQR9wyT/Ye0EAAPJDHchJrouBpS9gfnq3vs4UGGE++HNL3laUYQCoxGLboeFD+EwbZafw7tbsLvg=="
      crossorigin="anonymous" referrerpolicy="no-referrer"/>
<style>
    .hidden_elem {
        display: none;
    }

    /* CSS for selected row */
    tr.selected {
        background-color: #f5f5f5;
    }

    /* CSS for highlighted column */
    td.highlight {
        background-color: #ffc107;
    }

    /* Freeze table headers */
    .dataTables_scrollHead {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        background-color: #fff;
        overflow: hidden;
        z-index: 10;
    }

    /* Adjust table layout */
    table#datatable {
        width: 100%;
    }

    table#datatable thead th {
        white-space: nowrap; /* Prevent text wrapping in header cells */
    }

    .d-none {
        display: none !important;
    }


</style>

<?php
$selected_accounting_system = 0;
extract($result);
?>

<div class='row'>
    <div id="static_range_wrapper" class='form-group col-xs-2'>
        <input class='form-control datepicker' value="<?= $month; ?>" id='date_range' type='text'
               onkeydown="return false;"/>
    </div>

    <div id="date_range_wrapper" class="d-none form-group col-xs-3">
        <div class="input-group">
            <input type="text" class="form-control" id="start_date" value="<?= $month; ?>">
            <div class="input-group-addon">to</div>
            <input type="text" class="form-control" id="end_date" value="<?= $month; ?>">
        </div>

    </div>

    <div class='form-group col-xs-2 <?= !session()->get('system_admin') ? 'hidden_elem' : ''; ?>'>
        <select id="account_system_id" class="form-control">
            <option value=""><?= get_phrase('select_a_national_office', "Select a National Office"); ?></option>
            <?php foreach ($accounting_system as $accounting_system_id => $accounting_system_name) { ?>
                <option value="<?= $accounting_system_id; ?>"><?= $accounting_system_name; ?></option>
            <?php } ?>
        </select>
    </div>

    <div class='form-group col-xs-2'>
        <select class='form-control select2' id='report_category'>
            <option value=""><?= get_phrase('select_report_category', "Select report category"); ?></option>
            <option value="fund_balance"><?= get_phrase('fund_balance_report', "Fund Balance Report"); ?></option>
            <option value="project_balance"><?= get_phrase('project_balance_report', "Project Balance Report"); ?></option>
            <option value="month_cash_balance"><?= get_phrase('month_cash_balance', "Month Cash Balance Report"); ?></option>
            <option value="month_expense"><?= get_phrase('month_expense_report', "Month Expense Report"); ?></option>
            <option value="month_income"><?= get_phrase('month_income_report', "Month Income Report"); ?></option>
        </select>
    </div>

    <div id="ajax-account" class='col-xs-2'>
        <select class='form-control select2' id='accounts'>
            <option value=""><?= get_phrase('select_account', "Select Account"); ?></option>
        </select>
    </div>

    <div id="total-select-wrapper" class="col-xs-2 d-none">
        <select id="total-select" class="form-control">
            <option><?= get_phrase('select_total_columns', "Select Total Columns"); ?></option>
        </select>
    </div>

    <div id="revenue-select-wrapper" class="col-xs-2 d-none">
        <select id="revenue-select" class="form-control">
            <option><?= get_phrase('select_total_columns', "Select Total Columns"); ?></option>
        </select>
    </div>

    <div id="expense-select-wrapper" class="col-xs-2 d-none">
        <select id="expense-select" class="form-control" multiple>
            <option><?= get_phrase('select_expense_columns', "Select Expense Columns"); ?></option>
        </select>
    </div>


</div>

<div id="warning_holder" class="row <?= !session()->get('system_admin') ? 'hidden_elem' : ''; ?>">
    <div class="well col-xs-12"
         style="text-align:center;font-weight:bold;color:red;"><?= get_phrase('national_office_not_selected', "National Office Not Selected"); ?></div>
</div>

<div id="month_label_holder" class="row <?= session()->get('system_admin') ? 'hidden_elem' : ''; ?>">
    <div id="balance_label" class="col-xs-12" style="text-align: center;margin-top:20px;font-weight:bold;">
        <?= get_phrase('monthly_balances_for_ending_period', "Monthly Balances for the Period Ending") ?> <span
                id="period"><?= $month; ?></span>
    </div>

    <div id="income_label" class="col-xs-12 d-none" style="text-align: center;margin-top:20px;font-weight:bold;">
        <?= get_phrase('monthly_income_report_for_the_period', "Monthly Income Report for the Period") ?> <span
                class="month_period" id="period"></span>
    </div>

    <div id="expense_label" class="col-xs-12 d-none" style="text-align: center;margin-top:20px;font-weight:bold;">
        <?= get_phrase('monthly_expense_report_for_the_period', "Monthly Expense Report for the Period") ?> <span
                class="month_period" id="period"></span>
    </div>

</div>


<div id="table_holder" class="row <?= session()->get('system_admin') ? 'hidden_elem' : ''; ?>">
    <div class="col-xs-12">

        <table class='table' id="datatable" style="white-space: nowrap;">
            <thead>
            <tr>
                <th><?= get_phrase('office_code', "Office Code") ?></th>
                <th class="no-sort"><?= get_phrase('totals', "Totals"); ?></th>
            </tr>
            </thead>
            <tbody>

            </tbody>
        </table>
    </div>
</div>


<script>

    const system_admin = '<?=session()->get('system_admin');?>';

    let datatable = $("#datatable").DataTable();

    var today = new Date();

    $("#datatable_filter").html(search_box());

    function search_box() {
        return '<?=get_phrase('search', "Search");?>: <input type="form-control" onchange="search(this)" id="search_box" aria-controls="datatable" />';
    }

    function search(el) {
        datatable.search($(el).val()).draw();
    }


    $("#date_range").datepicker({
        format: 'yyyy-mm-dd',
        minViewMode: 1,
        autoclose: true,
        maxDate: new Date(today.getFullYear(), today.getMonth(), 1),
    }).on('changeDate', function (e) {
        var startDate = e.date;
        // Update the end datepicker's min and max values
        $('#start_date').datepicker('setDate', startDate);
        $('#end_date').datepicker('setDate', startDate);


    });


    function hideZeroAmountColumns() {
        if (datatable.data().any()) { // Check whether datatable has any data
            // Hide columns with '0.00' values
            let columnsCount = datatable.columns().header().length;
            for (var colIndex = 0; colIndex < columnsCount; colIndex++) {
                if (isColumnAllZeros(colIndex)) {
                    datatable.column(colIndex).visible(false);
                } else {
                    datatable.column(colIndex).visible(true);
                }
            }
        }
    }

    // Function to check if all cells in a column have value '0.00'
    function isColumnAllZeros(columnIndex) {
        let allZeros = true;

        datatable.column(columnIndex).nodes().each(function (cell) {

            if ($(cell).text() !== '0.00') {
                allZeros = false;
                return false; // Exit the loop early if any cell is non-zero
            }
        });
        return allZeros;
    }

    function hideBlankColumns() {
        if (datatable.data().any()) { // Check whether datatable has any data
            // Hide columns with '0.00' values
            let columnsCount = datatable.columns().header().length;
            for (var colIndex = 0; colIndex < columnsCount; colIndex++) {
                if (isColumnAllBlanks(colIndex)) {
                    datatable.column(colIndex).visible(false);
                } else {
                    datatable.column(colIndex).visible(true);
                }
            }
        }
    }

    // Function to check if all cells in a column have value '0.00'
    function isColumnAllBlanks(columnIndex) {
        let allZeros = true;

        datatable.column(columnIndex).nodes().each(function (cell) {

            if ($(cell).text() !== '') {
                allZeros = false;
                return false; // Exit the loop early if any cell is not empty/blank
            }
        });
        return allZeros;
    }

    function populate_account_select(columns) {
        let options = '<option value = "">Select a filter account</option>';

        $.each(columns, function (index, column) {
            if (column.hasOwnProperty('id')) {
                options += "<option value = '" + column.id + "'>" + column.title + "</option>"
            }
        })

        $("#accounts").html(options)
        $('#accounts option:first').prop('selected', true).trigger('change.select2');
    }

    $('#report_category, #date_range, #accounts, #account_system_id').on('change', function () {

        if ($(this).attr('id') == 'account_system_id' && $(this).val() == "") {
            return false;
        }

        if ($(this).attr('id') == 'date_range') {
            if ($('#report_category').val() == '') {
                $('#report_category').val('fund_balance');
            }
            $('#report_category').trigger('change')
        } else {
            triggerInit($(this), $('#report_category').val())
        }

    })

    function triggerInit(elem, report_category) {
        // let report_category = $('#report_category').val()
        let columns_url = "<?=base_url();?>ajax/<?=$controller;?>/fundColumns/" + report_category;
        let data_url = "<?=base_url();?>ajax/<?=$controller;?>/fundShowList/" + report_category;
        let elem_id = $(elem).attr('id')
        const data = {
            account_system_id: $('#account_system_id').val(),
            accounts: elem_id != 'accounts' ? '' : $("#accounts").val()
        }

        $('#period').html($("#date_range").val())

        if (report_category == 'project_balance') {
            columns_url = "<?=base_url();?>ajax/<?=$controller;?>/civColumns";
            data_url = "<?=base_url();?>ajax/<?=$controller;?>/civShowList";
        }

        $.post(columns_url, data, function (response) {

            const accounts_columns = JSON.parse(response)
            const accounts = accounts_columns.accounts
            const columns = accounts_columns.columns

            if (datatable) {
                datatable.destroy();
                $('#datatable').empty();
            }

            if (elem_id != 'accounts') {
                populate_account_select(accounts)
            }

            datatable = datatableInitilization(data_url, columns, report_category)
        });

        return datatable
    }

    let visibleColumnsState = null;

    function datatableInitilization(data_url, columns, report_category) {
        return $('#datatable').DataTable({
            dom: 'lBfrtip',
            buttons: [
                {
                    extend: 'excel',
                    exportOptions: {
                        columns: ':visible' // Only export visible columns
                    }
                }
            ],
            pagingType: "full_numbers",
            // stateSave:true,
            pageLength: 10,
            order: [],
            serverSide: true,
            processing: true,
            language: {processing: 'Loading ...'},
            ordering: true,
            "scrollY": "500px", // Adjust the height according to your needs
            "scrollX": true,
            "scrollCollapse": true,
            "lengthMenu": [[10, 25, 50, 100, 500, 1000], [10, 25, 50, 100, 500, 1000]],
            columnDefs: [{
                orderable: false,
                targets: "no-sort"
            }],
            ajax: {
                url: data_url,
                type: "POST",
                data: function (d) {
                    // Pass the selected date to the server

                    // if(refresh_account_selector){
                    //     $('#accounts option:first').prop('selected', true).trigger('change.select2');
                    // }

                    d.date_range = $("#date_range").val()
                    d.start_date = $("#start_date").val()
                    d.end_date = $("#end_date").val()
                    d.accounts = $("#accounts").val()

                    if (system_admin) {
                        d.account_system_id = $("#account_system_id").val();
                        // alert(d.account_system_id)
                    }

                },
                "beforeSend": function () {
                    // Show the loading indicator when the request is sent
                    $('#overlay').css('display', 'block');
                },
                "complete": function () {
                    // Hide the loading indicator when the request is complete
                    $('#overlay').css('display', 'none');

                }
            },
            "columns": columns,
            drawCallback: function (settings) {

                const response = settings.json;
                const account_system_selected = response.account_system_selected

                if (account_system_selected && system_admin) {
                    $('#warning_holder').addClass('hidden_elem')
                    $('#month_label_holder, #table_holder').removeClass('hidden_elem')
                }


                handleReportCategoryDisplay(columns);


                // Header Update date label
                $('#period').html($("#date_range").val())
            }
        });
    }


    $('#account_system_id').on('change', function () {
        // alert('Hello')
        const account_system_id = $('#account_system_id').val()

        if (account_system_id > 0) {
            $('#table_holder, #month_label_holder').removeClass('hidden_elem')
            $('#warning_holder').addClass('hidden_elem')
        } else {
            $('#table_holder, #month_label_holder').addClass('hidden_elem')
            $('#warning_holder').removeClass('hidden_elem')
        }

    });

    $(document).ready(function () {
        //this code suppress errors of datatable from the browser because. This is needed because at times, columns are being generated dynamically and causing an alert on the browser however the table still works.

        $.fn.dataTableExt.sErrMode = "console";

        $.fn.dataTableExt.oApi._fnLog = function (oSettings, iLevel, sMesg, tn) {
            var sAlert = (oSettings === null)
                ? "DataTables warning: " + sMesg
                : "DataTables warning (table id = '" + oSettings.sTableId + "'): " + sMesg
            ;

            if (tn) {
                sAlert += '<?=get_phrase('for_more_information_about_error', ". For more information about this error, please see ");?>' +
                    "http://datatables.net/tn/" + tn
                ;
            }

            if (iLevel === 0) {
                if ($.fn.dataTableExt.sErrMode == "alert") {
                    alert(sAlert);
                } else if ($.fn.dataTableExt.sErrMode == "thow") {
                    throw sAlert;
                } else if ($.fn.dataTableExt.sErrMode == "console") {
                    console.log(sAlert);
                } else if ($.fn.dataTableExt.sErrMode == "mute") {
                }

                return;
            } else if (console !== undefined && console.log) {
                console.log(sAlert);
            }
        }

        $(document).ready(function () {

            // Function to check if the date range is valid
            function isRangeValid() {
                var startDate = $('#start_date').datepicker('getDate');
                var endDate = $('#end_date').datepicker('getDate');

                if (!startDate || !endDate) {
                    return false; // Return false if either date is not selected
                }

                // Ensure end date is not earlier than the start date
                if (endDate < startDate) {
                    return false;
                }

                // Ensure the range does not exceed 1 year
                var oneYearFromStart = new Date(startDate);
                oneYearFromStart.setFullYear(oneYearFromStart.getFullYear() + 1);

                if (endDate > oneYearFromStart) {
                    return false;
                }

                return true; // Valid range
            }

            // Function to trigger Init if the range is valid
            function checkAndTriggerInit() {
                if (isRangeValid()) {
                    triggerInit(null, $('#report_category').val());
                }
            }

            // Start datepicker
            $('#start_date').datepicker({
                format: 'yyyy-mm-dd',
                minViewMode: 1,
                autoclose: true,
                endDate: today // up to today's date
            }).on('changeDate', function (e) {
                var startDate = e.date;

                var maxEndDate = new Date(startDate);
                maxEndDate.setFullYear(maxEndDate.getFullYear() + 1);

                // Update the end datepicker's min and max values
                $('#end_date').datepicker('setStartDate', startDate);
                $('#end_date').datepicker('setEndDate', maxEndDate);

                $('#end_date').datepicker('setDate', maxEndDate);

                // Check if the range is valid and trigger the function
                checkAndTriggerInit();
            });

            // End datepicker
            $('#end_date').datepicker({
                format: 'yyyy-mm-dd',
                autoclose: true,
                minViewMode: 1,
                startDate: today, // Disallow past dates
            }).on('changeDate', function (e) {
                var endDate = e.date;
                var startDate = $('#start_date').datepicker('getDate');

                if (!startDate || !endDate) return;

                // Ensure end date is not before start date
                if (endDate < startDate) {
                    $('#end_date').datepicker('setDate', startDate);
                }

                // Validate & trigger action
                checkAndTriggerInit();
            });
        });


    })

    $(document).on('click', '#datatable tbody tr', function () {
        datatable.$('tr.selected').removeClass('selected');
        $('td.highlight').removeClass('highlight');

        // Highlight the clicked row
        $(this).addClass('selected');

        // Get the index of the clicked row
        var rowIndex = datatable.row(this).index();

        // Highlight the corresponding column cells
        // datatable.column(rowIndex).nodes().to$().addClass('highlight');


    });

    let lastReportCategory = null; // To store the last selected report category

    function handleReportCategoryDisplay(columns) {
        let reportCategory = $('#report_category').val(); // Get the selected report

        // Check if the reportCategory has changed
        if (reportCategory === lastReportCategory) {
            applyColumnVisibility(columns);
            return; // Skip reconfiguring if the category is the same
        }

        // Update the stored report category
        lastReportCategory = reportCategory;

        destroyColumns();
        let duration = $('#start_date').val() + ' - ' + $('#end_date').val();
        $('.month_period').html(duration);

        if (reportCategory === 'month_expense') {
            // Case 1: When reportCategory is 'month_expense'
            buildExpenseColumnSelect(columns);
            hideColumns(columns);
            $('#ajax-account').addClass('d-none');
            $('#date_range_wrapper').removeClass('d-none');
            $('#static_range_wrapper').addClass('d-none');
            $('#balance_label').addClass('d-none');
            $('#income_label').addClass('d-none');
            $('#expense_label').removeClass('d-none');
        } else if (reportCategory === 'month_income') {
            // Case 2: When reportCategory is 'month_income'
            buildIncomeColumnSelect(columns);
            hideColumns(columns);
            hideBlankColumns();
            hideZeroAmountColumns();
            $('#ajax-account').addClass('d-none');
            $('#date_range_wrapper').removeClass('d-none');
            $('#static_range_wrapper').addClass('d-none');
            $('#balance_label').addClass('d-none');
            $('#income_label').removeClass('d-none');
            $('#expense_label').addClass('d-none');
        } else {
            // Case 3: When reportCategory is anything else
            $('#ajax-account').removeClass('d-none');
            $('#static_range_wrapper').removeClass('d-none');
            $('#date_range_wrapper').addClass('d-none');
            $('#balance_label').removeClass('d-none');
            $('#income_label').addClass('d-none');
            $('#expense_label').addClass('d-none');

            hideBlankColumns();
            hideZeroAmountColumns();

            // Add any additional logic for other categories
        }

    }


    function destroyColumns() {

        let expenseColumnSelect = $('#expense-select-wrapper');
        let totalColumnSelect = $('#total-select-wrapper');
        let revenueColumnSelect = $('#revenue-select-wrapper');

        expenseColumnSelect.addClass('d-none');
        totalColumnSelect.addClass('d-none');
        revenueColumnSelect.addClass('d-none');
    }

    function toggleValues(element, hide = false) {
        // Get the target table ID from the data-toggle-table attribute
        var toggleCode = $(element).data('toggle-code');

        // Toggle the visibility of the table
        $("#tbl-" + toggleCode).toggleClass('d-none');
        $("#btn-toggle-" + toggleCode).toggleClass('d-none');

        // Optionally hide the toggle button
        if (hide == true) {
            $("#btn-toggle" + toggleCode).addClass('d-none');
        } else {
            datatable.columns.adjust();
            $("#btn-toggle" + toggleCode).removeClass('d-none');
        }

    }

    function hideColumns(columns) {

        let table = $('#datatable').DataTable();
        columns.forEach(function (column, index) {
            if (column.visible === false) {
                // Programmatically hide the column
                table.column(index).visible(false);
            }
        });
    }

    function buildExpenseColumnSelect(columns) {
        let expenseColumnSelect = $('#expense-select');
        let totalColumnSelect = $('#revenue-select');
        $('#revenue-select-wrapper').removeClass('d-none');

        if (totalColumnSelect[0].sumo) {
            // SumoSelect is initialized, now unload it
            totalColumnSelect[0].sumo.unload();
        }

        // Create optgroup for parents and add children under respective parent
        let totalColumns = '';
        totalColumns += `<option value="0">'<?=get_phrase('show_all', "Show All");?>'</option>`
        columns.forEach(col => {
            if (col.is_parent) {

                totalColumns += `<option value="${col.id}">${col.name}</option>`

            }
        })

        // Initialize SumoSelect


        totalColumnSelect.html(totalColumns).SumoSelect({
            placeholder: '<?=get_phrase('filter_by_column', "Filter By Column");?>',
            searchText: '<?=get_phrase('type_column_name', "Type Column Name");?>',
            search: true,
            clearAll: true,
        });

        totalColumnSelect.on('change', function () {

            $('#expense-select-wrapper').removeClass('d-none');
            if (expenseColumnSelect[0].sumo) {
                // SumoSelect is initialized, now unload it
                expenseColumnSelect[0].sumo.unload();
            }

            let expenseColumns = '';
            let selectedColumn = $(this).val();
            if (selectedColumn == 0) {
                showById(null, null, columns);
            } else {

                const children = columns
                    .filter(col => selectedColumn.includes(col.parent));
                children.forEach(child => {
                    expenseColumns += `<option value="${child.id}">${child.name}</option>`
                })


            }

            expenseColumnSelect.html(expenseColumns);

            expenseColumnSelect.SumoSelect({
                placeholder: '<?=get_phrase('select_expense_columns', "Select Expense Columns");?>',
                searchText: '<?=get_phrase('type_column_name', "Type Column Name");?>',
                selectAll: true,
                search: true,
                okCancelInMulti: true,
                clearAll: true,
                triggerChangeCombined: true // Ensures combined changes trigger events
            });


        });

        expenseColumnSelect.on('change', function () {
            let selectedColumns = $(this).val();
            showById($(this), selectedColumns, columns)
        });
    }

    function buildIncomeColumnSelect(columns) {

        let totalColumnSelect = $('#total-select');
        totalColumnSelect.attr('multiple', 'multiple');
        $('#total-select-wrapper').removeClass('d-none');


        if (totalColumnSelect[0].sumo) {
            // SumoSelect is initialized, now unload it
            totalColumnSelect[0].sumo.unload();
        }

        // Create optgroup for parents and add children under respective parent
        let totalColumns = '';
        totalColumns += `<option disabled value="0">Filter by Column</option>`
        columns.forEach(col => {
            if (col.is_parent) {

                totalColumns += `<option value="${col.id}">${col.name}</option>`

            }
        })

        // Initialize SumoSelect


        totalColumnSelect.html(totalColumns).SumoSelect({
            placeholder: '<?=get_phrase('select_income_columns', "Select Income Columns");?>',
            searchText: '<?=get_phrase('type_column_name', "Type Column Name");?>',
            selectAll: true,
            search: true,
            okCancelInMulti: true,
            clearAll: true,
            triggerChangeCombined: true // Ensures combined changes trigger events
        });


        totalColumnSelect.on('change', function () {
            let selectedColumns = $(this).val();
            showById($(this), selectedColumns, columns)
        });
    }

    function showById(element, selectedColumnIds, columns) {
        let table = $('#datatable').DataTable();

        // When nothing is selected, show all the parent columns
        if (selectedColumnIds == null) {
            if (element !== null) {
                element[0].sumo.selectItem(0);
            }

            columns.forEach((col, index) => {
                let column = table.column(index);
                if (col.visible || col.is_parent) {
                    column.visible(true);
                } else {
                    if (index !== 0) {
                        column.visible(false);
                    }
                }
            });
        } else {
            // Update column visibility based on selectedColumnIds
            columns.forEach(function (col, index) {
                let column = table.column(index);
                if (col.id) {
                    column.visible(selectedColumnIds.includes(col.id.toString()));
                }
            });
        }

        // Capture the current visibility state from DataTable
        visibleColumnsState = table.columns().visible().toArray().map((isVisible, index) => ({
            id: columns[index]?.id || null,
            visible: isVisible,
            is_parent: columns[index]?.is_parent || false, // Include additional properties if needed
        }));
    }


    function applyColumnVisibility() {
        if (visibleColumnsState === null) {
            return; // No state to apply yet
        }

        let table = $('#datatable').DataTable();

        visibleColumnsState.forEach((colState, index) => {
            let column = table.column(index);
            column.visible(colState.visible);
        });
    }


</script>
