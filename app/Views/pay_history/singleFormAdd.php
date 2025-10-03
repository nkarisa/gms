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
</style>


<div class="container">
    <h1>Create New Pay History Record</h1>
    <hr>

    <div id="submissionSuccess" class="alert alert-success" style="display:none;">
        <strong>Success!</strong> Pay History Record created successfully. Total Earnings recorded: <strong
            id="successTotal"></strong>.
    </div>

    <div id="submissionFail" class="alert alert-danger" style="display:none;">
        <strong>Error!</strong> Pay History Record could not be created! <strong
            id="failureMessage"></strong>.
    </div>

    <div id="addMode">

        <form id="payHistoryAddForm">

            <div id="dateValidationError" class="alert alert-danger" style="display:none;">
                <strong>Error:</strong> The **Pay Period End Date** cannot be earlier than or the same as the **Pay
                Period Start Date**. Please select a valid end date.
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="addOfficeName">Office Name <span class="text-danger">*</span></label>
                        <select id="addOfficeName" name="fk_office_id" class="form-control" onchange="updateStaffList()"
                            required>
                            <option value="" selected>-- Select Office --</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label for="addStaffName">Staff Name <span class="text-danger">*</span></label>
                        <select id="addStaffName" name="fk_user_id" class="form-control" required disabled>
                            <option value="" selected>-- Select an Office first --</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="addStartDate">Pay Period Start Date <span class="text-danger">*</span></label>
                        <input type="date" id="addStartDate" name="pay_history_start_date" value="" class="form-control"
                            required onchange="validateDates()">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="addEndDate">Pay Period End Date <span class="text-danger">*</span></label>
                        <input type="date" id="addEndDate" name="pay_history_end_date" value="" class="form-control"
                            required onchange="validateDates()">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="addTotalEarnings">Total Earning Amount</label>
                        <input type="text" id="addTotalEarnings" name="totalEarnings" value="0.00" class="form-control"
                            disabled>
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
                    <div id="addDuplicateAlert" class="alert alert-danger" style="display:none;">
                        <strong>Error:</strong> Cannot add duplicate earning categories. Please remove the existing
                        entry first.
                    </div>

                    <table class="table table-bordered table-condensed" id="addEarningsTable">
                        <thead>
                            <tr>
                                <th style="width: 50%;">Earning Category Name <span class="text-danger">*</span></th>
                                <th style="width: 40%;">Earning Amount <span class="text-danger">*</span></th>
                                <th style="width: 10%;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="addEarningsTableBody">
                        </tbody>
                    </table>
                    <button type="button" id="addNewEarning" class="btn btn-success btn-sm" onclick="addNewRowAdd()">
                        <span class="glyphicon glyphicon-plus" aria-hidden="true"></span> Add Earning Row
                    </button>
                </div>
            </div>

            <hr>

            <div class="text-right">
                <button type="submit" class="btn btn-primary">
                    <span class="glyphicon glyphicon-ok" aria-hidden="true"></span> **Create Record**
                </button>
                <button type="button" class="btn btn-default"
                    onclick="alert('Form Cancelled! This would typically redirect you.')">
                    <span class="glyphicon glyphicon-remove" aria-hidden="true"></span> **Cancel**
                </button>
            </div>
        </form>
    </div>
</div>

<script>

    // ==============================================
    // Data and Dependent Select Logic
    // ==============================================

    const addNewEarningBtn = document.querySelector("#addNewEarning")

    const validOffices = async () => {
        const officeURL = `${baseURL}ajax/pay_history/getLoggedUserOffices`
        const response = await fetch(officeURL)
        const offices = await response.json()
        const addOfficeName = document.querySelector('#addOfficeName')

        const options = offices.map(office => `<option value="${office.office_id}">${office.office_name}</option>`).join('')

        addOfficeName.insertAdjacentHTML("beforeend", options)
    }

    const updateStaffList = async () => {
        const officeSelect = document.getElementById('addOfficeName');
        const staffSelect = document.getElementById('addStaffName');
        const selectedOffice = officeSelect.value;
        const userURL = `${baseURL}ajax/pay_history/getOfficeUsers/${selectedOffice}`

        // Clear existing options
        staffSelect.innerHTML = '';

        const response = await fetch(userURL)
        const users = await response.json()

        // Add the default "Select Staff Member" option
        const defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.textContent = '-- Select Staff Member --';
        staffSelect.appendChild(defaultOption);

        if (users.length) {
            users.forEach(staff => {
                const option = document.createElement('option');
                option.value = staff.user_id;
                option.textContent = staff.name;
                staffSelect.appendChild(option);
            });

            staffSelect.disabled = false;
            toggleAddNewEarningRowButtonVisibility(true)
            addNewRowAdd()

        } else {
            staffSelect.disabled = true;
            defaultOption.textContent = '-- Select an Office first --';
            toggleAddNewEarningRowButtonVisibility(false)
        }


    }


    // ==============================================
    // Earning Entries Table Logic
    // ==============================================

    const createNewRow = (earningCategoriesData) => {

        const earningCategoryOptions = earningCategoriesData.map(earningCategory => `<option value="${earningCategory.id}">${earningCategory.name}</option>`).join('')

        const initialNewRowHtml = `
            <tr class="earning-row">
                <td>
                    <select name="fk_earning_category_id[]" required class="form-control input-sm" onchange="checkDuplicateAdd(this)">
                        <option value="">Select Category</option>
                        ${earningCategoryOptions}
                    </select>
                </td>
                <td><input type="number" name="earning_amount[]" value="0.00" step="0.01" required class="form-control input-sm" oninput="calculateTotalAdd()"></td>
                <td><button type="button" class="btn btn-danger btn-sm btn-block" onclick="removeRowAdd(this)"><span class="glyphicon glyphicon-trash" aria-hidden="true"></span> Remove</button></td>
            </tr>
        `;

        return initialNewRowHtml;
    }

    const addNewRowAdd = async () => {
        const tableBody = document.getElementById('addEarningsTableBody');
        const officeId = document.querySelector("#addOfficeName").value
        const earningCategoriesURL = `${baseURL}ajax/pay_history/getOfficeEarningCategories/${officeId}`

        const response = await fetch(earningCategoriesURL)
        const earningCategoriesData = await response.json()

        // Use insertAdjacentHTML for slightly better performance than innerHTML +=
        tableBody.insertAdjacentHTML('beforeend', createNewRow(earningCategoriesData));
        calculateTotalAdd();
    }

    function removeRowAdd(button) {
        const row = button.closest('tr');
        const tableBody = document.getElementById('addEarningsTableBody');

        if (tableBody.children.length > 1) {
            row.remove();
            calculateTotalAdd();
            checkAllDuplicatesAdd();
        } else {
            alert("You must have at least one Earning Entry.");
        }
    }

    function calculateTotalAdd() {
        let total = 0;
        const amountInputs = document.querySelectorAll('#payHistoryAddForm input[name="earning_amount[]"]');

        amountInputs.forEach(input => {
            // Use the input's value as a string and parse it
            const value = parseFloat(input.value) || 0;
            total += value;
        });

        document.getElementById('addTotalEarnings').value = total.toFixed(2);
    }

    function checkDuplicateAdd(selectElement) {
        const selectedValue = selectElement.value;
        if (!selectedValue) return;

        let count = 0;
        const categorySelects = document.querySelectorAll('#addEarningsTableBody select[name="earningCategoryName[]"]');

        categorySelects.forEach(select => {
            if (select.value === selectedValue) {
                count++;
            }
        });

        const alertElement = document.getElementById('addDuplicateAlert');

        if (count > 1) {
            alertElement.style.display = 'block';
            // Clear the value of the select that caused the duplicate
            selectElement.value = '';
        } else {
            if (alertElement.style.display === 'block') {
                // Only check all if the alert is currently visible
                checkAllDuplicatesAdd();
            }
        }
    }

    function checkAllDuplicatesAdd() {
        const selectedValues = [];
        const categorySelects = document.querySelectorAll('#addEarningsTableBody select[name="earningCategoryName[]"]');

        categorySelects.forEach(select => {
            const value = select.value;
            if (value) selectedValues.push(value);
        });

        // Check for duplicates in the array
        const hasDuplicates = selectedValues.some((val, i) => selectedValues.indexOf(val) !== i);
        const alertElement = document.getElementById('addDuplicateAlert');

        if (hasDuplicates) {
            alertElement.style.display = 'block';
        } else {
            alertElement.style.display = 'none';
        }
    }

    // ==============================================
    // Date Validation Logic (Ensures Start < End)
    // ==============================================

    function validateDates() {
        const startDateInput = document.getElementById('addStartDate');
        const endDateInput = document.getElementById('addEndDate');
        const dateAlert = document.getElementById('dateValidationError');

        const startDate = startDateInput.value;
        const endDate = endDateInput.value;

        // Only validate if both dates are filled
        if (startDate && endDate) {
            if (new Date(startDate) >= new Date(endDate)) {
                dateAlert.style.display = 'block';
                // Clear the invalid End Date field
                endDateInput.value = '';
                return false;
            } else {
                dateAlert.style.display = 'none';
                return true;
            }
        }
        dateAlert.style.display = 'none';
        return true;
    }

    const toggleAddNewEarningRowButtonVisibility = (visibilityState) => {
        addNewEarningBtn.style.display = visibilityState ? 'inline' : 'none'
    }

    // ==============================================
        // Form Reset Logic
        // ==============================================

        /**
         * Resets the entire form to its initial state (or a state ready for new input).
         * Calls the native form.reset() and then handles dynamic elements.
         */
        function resetForm() {
            const form = document.getElementById('payHistoryAddForm');
            // 1. Use the native reset method to clear most standard inputs
            form.reset();

            // 2. Clear dynamic table rows and re-add the mandatory one
            const tableBody = document.getElementById('addEarningsTableBody');
            tableBody.innerHTML = ''; // Clears all rows
            addNewRowAdd(); // Re-adds the single mandatory row

            // 3. Reset dependent dropdowns and alerts
            updateStaffList(); // Resets the Staff Name dropdown
            document.getElementById('dateValidationError').style.display = 'none';
            document.getElementById('addDuplicateAlert').style.display = 'none';
            document.getElementById('addTotalEarnings').value = '0.00'; // Ensure this is explicitly set if form.reset() doesn't cover disabled inputs

            // Optional: If you want to reset the dropdowns:
            document.getElementById('addOfficeName').value = '';

            // NOTE: The lines to hide the form and show/hide the success message are
            // now handled by the form submission logic for more granular control.
        }


    // ==============================================
    // Initialization and Event Listeners
    // ==============================================

    const initializeForm = async () => {

        // Disable Add Button
        toggleAddNewEarningRowButtonVisibility(false)

        // Initialize valid office
        await validOffices();

        // Initialize the form with one empty earning row
        // addNewRowAdd();

        const form = document.getElementById('payHistoryAddForm');

        // Primary Form Submission Logic
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            // 1. Validate Dates
            if (!validateDates()) {
                // Stop submission if dates are invalid
                document.getElementById('addEndDate').focus(); // Focus on the now-empty end date field
                return;
            }

            // 2. Validate Earnings Duplicates
            checkAllDuplicatesAdd();
            const duplicateAlert = document.getElementById('addDuplicateAlert');
            if (duplicateAlert.style.display === 'block') {
                alert('Please resolve duplicate earning categories before submitting.');
                return;
            }


            // If all validations pass (simulating successful submission)
            const totalEarnings = document.getElementById('addTotalEarnings').value;
            const saveEarningURL = `${baseURL}ajax/pay_history/savePayHistory`
            const payHistoryAddForm = new FormData(document.querySelector("#payHistoryAddForm"))
            const successMessage = document.getElementById('submissionSuccess');
            const failureMessage = document.getElementById('submissionFail');

            const response = await fetch(saveEarningURL, {
                method: 'POST',
                body: payHistoryAddForm
            })

            const savedData = await response.json()
            
            if(savedData.flag){
                
                document.getElementById('successTotal').textContent = totalEarnings;

                // 1. Reset the form's fields to prepare for the next submission
                resetForm();

                // 2. Show the success message (the form stays visible)
                failureMessage.style.display = 'none';
                successMessage.style.display = 'block';

                // 3. Hide the success message after 3 seconds (3000 milliseconds)
                // setTimeout(function () {
                //     successMessage.style.display = 'none';
                // }, 3000);
            }else{
                document.getElementById('failureMessage').textContent = savedData.message;
                failureMessage.style.display = 'block';
                successMessage.style.display = 'none';

                // setTimeout(function () {
                //     failureMessage.style.display = 'none';
                // }, 3000);
            }

        });

        // The event listeners for dynamically added rows (amount and category change) 
        // are handled by the 'oninput' and 'onchange' attributes directly in the initialNewRowHtml string.
    }

    // Run initialization function once the DOM is fully loaded
    document.addEventListener('DOMContentLoaded', initializeForm);

</script>