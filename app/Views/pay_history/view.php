<style>
    /* Custom styles for spacing and table readability */
    body {
        padding: 20px;
    }

    .earnings-table-container {
        margin-top: 30px;
        padding: 15px;
        border: 1px solid #eee;
        border-radius: 4px;
    }

    .table>tbody>tr>td {
        vertical-align: middle;
    }

    /* Hide the form by default in the view-mode setup */
    #editMode {
        display: none;
    }

    .view-field-label {
        font-weight: bold;
        color: #555;
    }

    .view-field-value {
        margin-bottom: 15px;
    }

    .action-buttons {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-bottom: 20px;
    }

    .current-status {
        padding: 8px 15px;
        border-radius: 4px;
        font-weight: bold;
    }

    .status-ready {
        background-color: #f0ad4e;
        color: white;
    }

    /* Example warning color */
</style>


<div class="container">

    <div id="viewMode">
        <h1>Pay History Record</h1>
        <hr>

        <div class="action-buttons">
            <button type="button" class="btn btn-warning" onclick="alert('Status Change Logic Triggered!')">
                Status: <span id="viewStatusBtnText">Loading...</span>
            </button>
            <button type="button" class="btn btn-primary" onclick="toggleMode('edit')">
                <span class="glyphicon glyphicon-pencil" aria-hidden="true"></span> **Edit Pay History**
            </button>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Master Details</h3>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-3">
                        <p class="view-field-label">Pay History Name</p>
                        <p class="view-field-value" id="viewHistoryName">Loading...</p>
                    </div>
                    <div class="col-md-3">
                        <p class="view-field-label">Pay History Track Number</p>
                        <p class="view-field-value" id="viewTrackNumber">Loading...</p>
                    </div>
                    <div class="col-md-3">
                        <p class="view-field-label">Staff Name</p>
                        <p class="view-field-value" id="viewStaffName">Loading...</p>
                    </div>
                    <div class="col-md-3">
                        <p class="view-field-label">Total Earning Amount</p>
                        <p class="view-field-value">**<span id="viewTotalEarnings">Loading...</span>**</p>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <p class="view-field-label">Pay Period</p>
                        <p class="view-field-value" id="viewPeriod">Loading...</p>
                    </div>
                    <div class="col-md-6">
                        <p class="view-field-label">Office Name</p>
                        <p class="view-field-value" id="viewOfficeName">Loading...</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="earnings-table-container panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Earning Entries</h3>
            </div>
            <div class="panel-body">
                <table class="table table-striped table-bordered table-condensed">
                    <thead>
                        <tr>
                            <th style="width: 70%;">Earning Category Name</th>
                            <th style="width: 30%;" class="text-right">Earning Amount</th>
                        </tr>
                    </thead>
                    <tbody id="viewEarningsTableBody">
                        <tr>
                            <td colspan="2" class="text-center">Loading earning entries...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>


    </div>
    <div id="editMode">

        <h2>Edit Pay History Details</h2>
        <hr>

        <form id="payHistoryEditForm">

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="editStartDate">Pay History Start Date</label>
                        <input type="date" id="editStartDate" name="startDate" value="2025-07-01" class="form-control"
                            required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="editEndDate">Pay History End Date</label>
                        <input type="date" id="editEndDate" name="endDate" value="2026-06-30" class="form-control"
                            required>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="editStaffName">Staff Name</label>
                        <select id="editStaffName" name="staffName" class="form-control" required>
                            <option value="">-- Select Staff Member --</option>
                            <option value="jephab27@gmail.com" selected>Leonard Gube (jephab27@gmail.com)</option>
                            <option value="staff2@example.com">Alice Johnson</option>
                            <option value="staff3@example.com">Bernard Kibet</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="editTotalEarnings">Pay History Total Earning Amount</label>
                        <input type="text" id="editTotalEarnings" name="totalEarnings" value="0.00" class="form-control"
                            disabled>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="editOfficeName">Office Name</label>
                        <input type="text" id="editOfficeName" name="officeName" value="KE0334-PEFA Church Mwele"
                            class="form-control" required>
                    </div>
                </div>
                <div class="col-md-6">
                </div>
            </div>

            <hr>

            <div class="earnings-table-container panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Earning Entries</h3>
                </div>
                <div class="panel-body">
                    <div id="duplicateAlert" class="alert alert-danger" style="display:none;">
                        <strong>Error:</strong> Cannot add duplicate earning categories. Please remove the existing
                        entry first.
                    </div>

                    <table class="table table-bordered table-condensed" id="editEarningsTable">
                        <thead>
                            <tr>
                                <th style="width: 50%;">Earning Category Name</th>
                                <th style="width: 40%;">Earning Amount</th>
                                <th style="width: 10%;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="editEarningsTableBody">
                            <tr class="earning-row">
                                <td>
                                    <select name="earningCategoryName[]" required class="form-control input-sm"
                                        onchange="checkDuplicate(this); calculateTotal();">
                                        <option value="">Select Category</option>
                                        <option value="Basic Salary">Basic Salary</option>
                                        <option value="Pension contributions">Pension contributions</option>
                                        <option value="Commuter Allowance">Commuter Allowance</option>
                                        <option value="House Allowance">House Allowance</option>
                                    </select>
                                </td>
                                <td><input type="number" name="earningAmount[]" value="0.00" step="0.01" required
                                        class="form-control input-sm" onchange="calculateTotal()"></td>
                                <td><button type="button" class="btn btn-danger btn-sm btn-block"
                                        onclick="removeRow(this)"><span class="glyphicon glyphicon-trash"
                                            aria-hidden="true"></span> Remove</button></td>
                            </tr>
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-success btn-sm" onclick="addNewRow()">
                        <span class="glyphicon glyphicon-plus" aria-hidden="true"></span> Add Earning Row
                    </button>
                </div>
            </div>

            <hr>

            <div class="text-right">
                <button type="submit" class="btn btn-primary">
                    <span class="glyphicon glyphicon-floppy-disk" aria-hidden="true"></span> **Save Changes**
                </button>
                <button type="button" class="btn btn-default" onclick="toggleMode('view')">
                    <span class="glyphicon glyphicon-remove" aria-hidden="true"></span> **Cancel**
                </button>
            </div>
        </form>
    </div>
</div>

<script>

    const format = (value) => value; //.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",")

    const getId = () => {
        const path = window.location.pathname;

        // Split the path by '/', then filter out any empty strings that might result from leading/trailing slashes
        const segments = path.split('/').filter(segment => segment.length > 0);

        // The 3rd segment will be at index 2 (since arrays are 0-indexed)
        return segments[2];
    }

    // Storage for the initially loaded data
    let initialPayHistoryData = null;

    /**
     * Calls the dummy fetch, stores the data, and updates the view and edit fields.
     */
    const loadAndDisplayData = () => {
        
        const payHistoryId = getId()
        const payHistoryURL = `${baseURL}ajax/pay_history/getPayHistory/${payHistoryId}`

        fetch(payHistoryURL)
            .then(response => response.json())
            .then(apiResult => {
                if (apiResult.success) {
                    const data = apiResult.data;
                    initialPayHistoryData = data; // Store data for setting up edit mode
                    // console.log(initialPayHistoryData)
                    // --- Update View Mode Fields ---
                    // const format = data.formatCurrency; // Shorthand

                    document.getElementById('viewHistoryName').textContent = data.pay_history_name;
                    document.getElementById('viewTrackNumber').textContent = data.pay_history_track_number;
                    document.getElementById('viewStaffName').textContent = data.user_fullname;
                    document.getElementById('viewTotalEarnings').textContent = format(data.pay_history_total_earning_amount);
                    document.getElementById('viewPeriod').textContent = data.period;
                    document.getElementById('viewOfficeName').textContent = data.office_name;
                    document.getElementById('viewStatusBtnText').textContent = data.status;

                    // --- Populate View Earnings Table ---
                    const viewTableBody = document.getElementById('viewEarningsTableBody');
                    viewTableBody.innerHTML = '';

                    data.earnings.forEach(item => {
                        const newRow = document.createElement('tr');
                        newRow.innerHTML = `
                                <td>${item.name}</td>
                                <td class="text-right">${format(item.amount)}</td>
                            `;
                        viewTableBody.appendChild(newRow);
                    });

                    // Add the total row
                    const totalRow = document.createElement('tr');
                    totalRow.className = 'info';
                    totalRow.innerHTML = `<td>**Total**</td><td class="text-right">**${format(data.pay_history_total_earning_amount)}**</td>`;
                    viewTableBody.appendChild(totalRow);

                    // --- Set Initial Edit Mode Values (when loading for the first time) ---
                    populateEditMode(data);

                } else {
                    console.error("API error:", apiResult.message);
                    alert("Failed to load data.");
                }
            })
            .catch(error => {
                console.error("Network or parsing error:", error);
                alert("An error occurred while fetching data.");
            });
    }

    /**
     * Populates the edit mode fields and table based on the fetched data.
     * @param {Object} data - The data object from the mock fetch.
     */
    const populateEditMode = (data) => {

        // Set master detail inputs
        document.getElementById('editStartDate').value = data.pay_history_start_date;
        document.getElementById('editEndDate').value = data.pay_history_end_date;
        document.getElementById('editTotalEarnings').value = format(data.pay_history_total_earning_amount);
        document.getElementById('editOfficeName').value = data.officeName;

        // Populate the edit table
        const editTableBody = document.getElementById('editEarningsTableBody');
        editTableBody.innerHTML = ''; // Clear the initial placeholder

        data.earnings.forEach(item => {
            const row = createEarningRow(item.name, item.amount);
            editTableBody.appendChild(row);
        });

        calculateTotal(); // Recalculate total after populating the table
    }


    // ==============================================
    // MODE SWITCHING & TABLE LOGIC
    // ==============================================

    function toggleMode(mode) {
        const viewMode = document.getElementById('viewMode');
        const editMode = document.getElementById('editMode');

        if (mode === 'edit') {
            viewMode.style.display = 'none';
            editMode.style.display = 'block';
            // Recalculate total just in case any value changed via console or other means
            calculateTotal();
        } else {
            editMode.style.display = 'none';
            viewMode.style.display = 'block';
            // In a real app, you might call loadAndDisplayData() here 
            // to reload the fresh data if the save button was pressed.
        }
    }

    const categoryOptions = `
            <option value="">Select Category</option>
            <option value="Basic Salary">Basic Salary</option>
            <option value="Pension contributions">Pension contributions</option>
            <option value="Commuter Allowance">Commuter Allowance</option>
            <option value="House Allowance">House Allowance</option>
        `;

    /**
     * Creates a new earning row element.
     * @param {string} category - The category to pre-select.
     * @param {number} amount - The amount to pre-fill.
     * @returns {HTMLElement} The created table row element.
     */
    function createEarningRow(category = '', amount = 0.00) {
        const newRow = document.createElement('tr');
        newRow.className = 'earning-row';

        // Build the options HTML, marking the current category as selected
        let selectHtml = categoryOptions.replace(`value="${category}"`, `value="${category}" selected`);

        newRow.innerHTML = `
                <td>
                    <select name="earningCategoryName[]" required class="form-control input-sm" onchange="checkDuplicate(this); calculateTotal();">
                        ${selectHtml}
                    </select>
                </td>
                <td><input type="number" name="earningAmount[]" value="${amount.toFixed(2)}" step="0.01" required class="form-control input-sm" onchange="calculateTotal()"></td>
                <td><button type="button" class="btn btn-danger btn-sm btn-block" onclick="removeRow(this)"><span class="glyphicon glyphicon-trash" aria-hidden="true"></span> Remove</button></td>
            `;
        return newRow;
    }

    function addNewRow() {
        const tableBody = document.getElementById('editEarningsTableBody');
        const newRow = createEarningRow();
        tableBody.appendChild(newRow);
        calculateTotal();
    }

    function removeRow(button) {
        const row = button.closest('tr');
        if (row) {
            row.remove();
        }
        calculateTotal();
        checkAllDuplicates();
    }

    function checkDuplicate(selectElement) {
        const selectedValue = selectElement.value;
        if (!selectedValue) return;

        let count = 0;
        const selectElements = document.querySelectorAll('#editEarningsTableBody select[name="earningCategoryName[]"]');

        selectElements.forEach(function (select) {
            if (select.value === selectedValue) {
                count++;
            }
        });

        const alertElement = document.getElementById('duplicateAlert');

        if (count > 1) {
            alertElement.style.display = 'block';
            // Reset the select element to force the user to choose another
            selectElement.value = '';
        } else {
            checkAllDuplicates(); // Check if this change resolved other duplicates
        }
    }

    function checkAllDuplicates() {
        const selectedValues = [];
        const selectElements = document.querySelectorAll('#editEarningsTableBody select[name="earningCategoryName[]"]');

        selectElements.forEach(function (select) {
            const value = select.value;
            if (value) selectedValues.push(value);
        });

        const uniqueValues = new Set(selectedValues);
        const hasDuplicates = selectedValues.length !== uniqueValues.size;
        const alertElement = document.getElementById('duplicateAlert');

        if (hasDuplicates) {
            alertElement.style.display = 'block';
        } else {
            alertElement.style.display = 'none';
        }
    }

    function calculateTotal() {
        let total = 0;
        const amountInputs = document.querySelectorAll('#payHistoryEditForm input[name="earningAmount[]"]');
        const totalEarningsInput = document.getElementById('editTotalEarnings');

        amountInputs.forEach(function (input) {
            const value = parseFloat(input.value) || 0;
            total += value;
        });

        if (totalEarningsInput) {
            totalEarningsInput.value = total.toFixed(2);
        }
    }

    // Initialize functionality when the DOM is fully loaded
    document.addEventListener('DOMContentLoaded', function () {
        // Load data from the dummy fetch and populate the page
        loadAndDisplayData();
    });
</script>