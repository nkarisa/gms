<style>
        body {
            background-color: #f5f5f5;
            padding: 20px;
        }
        .payslip-container {
            max-width: 800px;
            margin: auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            position: relative;
        }
        .payslip-header {
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        .payslip-header h2 {
            margin-top: 0;
            font-weight: bold;
        }
        .section-header {
            background-color: #f2f2f2;
            padding: 10px;
            font-weight: bold;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .table > tbody > tr > td,
        .table > tbody > tr > th {
            padding: 8px;
        }
        .total-row {
            font-weight: bold;
            background-color: #f9f9f9;
        }
        .edit-mode {
            display: none;
        }
        .form-control-edit {
            width: 100%;
        }

        /* --- Navigation Styles --- */
        .payslip-navigation {
            position: fixed;
            top: 50%;
            transform: translateY(-50%);
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .payslip-navigation-left {
            left: 20px;
        }

        .payslip-navigation-right {
            right: 20px;
        }

        .nav-icon {
            background-color: #337ab7;
            color: white;
            padding: 10px;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .nav-icon:hover {
            background-color: #286090;
        }

        /* Hide buttons when printing */
        @media print {
            .no-print {
                display: none;
            }
            .payslip-navigation {
                display: none;
            }

            /* START: New styles to force single-page fit and reduce spacing */
            html, body {
                height: 100%;
                margin: 0;
                padding: 0;
                overflow: hidden; /* Prevent unwanted scrollbars/extra space */
            }
            .payslip-container {
                width: 100%;
                max-width: 100%;
                box-shadow: none; 
                margin: 0;
                padding: 10px; /* Reduced padding for the whole container */
                /* Scale transform is the most reliable way to force single-page fit */
                transform: scale(0.50); /* Adjust as needed, 0.95 reduces size by 5% */
                transform-origin: top center;
            }
            .row {
                margin-bottom: 5px !important; /* Reduce space between sections */
            }
            .payslip-header {
                margin-bottom: 5px; /* Reduce header margin */
            }
            .section-header {
                padding: 5px 10px; /* Compress section headers */
                margin-bottom: 5px;
            }
            .table > tbody > tr > td,
            .table > tbody > tr > th {
                padding: 3px; /* Compress table rows */
            }
            .panel-body {
                padding: 5px; /* Compress net pay panel */
            }
            .panel-heading {
                padding: 5px 15px; /* Compress panel heading */
            }
            /* END: New styles */
        }
    </style>


<div class="payslip-navigation payslip-navigation-left no-print">
    <div class="nav-icon" id="prevPayslip">
        <span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>
    </div>
</div>

<div class="payslip-navigation payslip-navigation-right no-print">
    <div class="nav-icon" id="nextPayslip">
        <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
    </div>
</div>

<div class="container payslip-container">
    <div class="row payslip-header">
        <div class="col-xs-6" id="payslip_organization">
            <h2 id="payslip_organization_name"><!-- Organization name holder --></h2>
            <address id="payslip_organization_address">
                <!-- Address holder -->
            </address>
        </div>
        <div class="col-xs-6 text-right" id="payslip_pay_period">
            <h1 class="text-uppercase">Payslip</h1>
            <p><strong>Pay Period:</strong> <!-- Pay period holder --> </p>
            <p><strong>Pay Date:</strong> <!-- Pay date holder --> </p>
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12">
            <div class="section-header">Employee Details</div>
        </div>
        <div class="col-xs-6">
            <p><strong>Employee Name:</strong> <span id="employeeName"> <!-- Employee Name --> </span></p>
            <p><strong>Job Title:</strong> <span id="jobTitle"> <!-- Employee Title --> </span></p>
        </div>
        <div class="col-xs-6">
            <p><strong>Employee ID:</strong> <span id="employeeId"><!-- Employee ID --></span></p>
            <p><strong>Department:</strong> <span id="department"><!-- Employee Department --></span></p>
        </div>
    </div>

    <hr>

    <div class="row" style="margin-bottom: 25px;">
        <div class="col-xs-12">
            <div class="section-header">Earnings</div>
            <div class="panel panel-default">
                <table class="table table-striped" id="earningsTable">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th class="text-right">Amount (<span
                                    class="currency_symbol"><!-- Currency symbol holder --></span>)</th>
                            <th class="no-print edit-mode">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Earnings rows will be dynamically inserted here -->
                        <tr class="total-row">
                            <td><strong>Gross Earnings</strong></td>
                            <td class="text-right"><strong><span
                                        id="grossEarnings"><!-- Gross earnings holder --></span></strong></td>
                            <td class="no-print edit-mode"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <button class="btn btn-success btn-sm no-print edit-mode" id="addEarningRow">Add Earning</button>
        </div>
    </div>

    <div class="row" style="margin-bottom: 25px;">
        <div class="col-xs-12">
            <div class="section-header">Deductions</div>
            <div class="panel panel-default">
                <table class="table table-striped" id="deductionsTable">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th class="text-right">Amount (<span class="currency_symbol"></span>)</th>
                            <th class="no-print edit-mode">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Deduction rows will be dynamically inserted here -->
                        <tr class="total-row">
                            <td><strong>Total Deductions</strong></td>
                            <td class="text-right"><strong><span id="totalDeductions">1,100.00</span></strong></td>
                            <td class="no-print edit-mode"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <button class="btn btn-success btn-sm no-print edit-mode" id="addDeductionRow">Add Deduction</button>
        </div>
    </div>

    <div class="row" style="margin-bottom: 25px;">
        <div class="col-xs-12">
            <div class="section-header">Accrued Benefits</div>
            <div class="panel panel-default">
                <table class="table table-striped" id="benefitsTable">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th class="text-right">Amount (<span class="currency_symbol"></span>)</th>
                            <th class="no-print edit-mode">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Accrued benefits row holder -->
                        <tr class="total-row">
                            <td><strong>Total Accrued Benefits</strong></td>
                            <td class="text-right"><strong><span id="totalBenefits"><!-- Accrued Benefit Holder --></span></strong></td>
                            <td class="no-print edit-mode"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <button class="btn btn-success btn-sm no-print edit-mode" id="addBenefitRow">Add Accrued Benefit</button>
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12 text-right">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title">Net Pay</h3>
                </div>
                <div class="panel-body">
                    <h1 class="text-success" style="font-size: 3em;">$<span id="netPay"><!-- New Pay holder --></span></h1>
                </div>
            </div>
        </div>
    </div>

    <div class="row no-print text-center" style="margin-top: 20px;">
        <div class="col-xs-12">
            <div id="display-controls">
                <a href="#" class="btn btn-primary" onclick="window.print()">
                    <span class="glyphicon glyphicon-print"></span> Print / Download
                </a>
                <button class="btn btn-warning" id="editButton">
                    <span class="glyphicon glyphicon-edit"></span> Edit Payslip
                </button>
            </div>
            <div id="edit-controls" class="edit-mode">
                <button class="btn btn-success" id="saveButton">
                    <span class="glyphicon glyphicon-floppy-disk"></span> Save
                </button>
                <button class="btn btn-danger" id="cancelButton">
                    <span class="glyphicon glyphicon-remove-circle"></span> Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {

        const editButton = document.getElementById('editButton');
        const saveButton = document.getElementById('saveButton');
        const cancelButton = document.getElementById('cancelButton');
        const addEarningRowBtn = document.getElementById('addEarningRow');
        const addDeductionRowBtn = document.getElementById('addDeductionRow');
        const addBenefitRowBtn = document.getElementById('addBenefitRow');
        const earningsTableBody = document.querySelector('#earningsTable tbody');
        const benefitsTableBody = document.querySelector('#benefitsTable tbody');
        const deductionsTableBody = document.querySelector('#deductionsTable tbody');
        const displayControls = document.getElementById('display-controls');
        const editControls = document.getElementById('edit-controls');
        const prevPayslipBtn = document.getElementById('prevPayslip'); // Added
        const nextPayslipBtn = document.getElementById('nextPayslip'); // Added

        // Explicitly set the UI to display mode on page load to prevent initial rendering issues
        toggleEditMode(false);

        // Function to calculate and update totals
        function updateTotals() {
            let grossEarnings = 0;
            let totalDeductions = 0;
            let totalBenefits = 0;

            // Calculate Gross Earnings
            document.querySelectorAll('#earningsTable tbody tr.earning-row input[type="number"]').forEach(input => {
                grossEarnings += parseFloat(input.value) || 0;
            });
            document.getElementById('grossEarnings').textContent = grossEarnings.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            // Calculate Gross Earnings
            document.querySelectorAll('#benefitsTable tbody tr.benefit-row input[type="number"]').forEach(input => {
                totalBenefits += parseFloat(input.value) || 0;
            });
            document.getElementById('totalBenefits').textContent = totalBenefits.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });


            // Calculate Total Deductions
            document.querySelectorAll('#deductionsTable tbody tr.deduction-row input[type="number"]').forEach(input => {
                totalDeductions += parseFloat(input.value) || 0;
            });
            document.getElementById('totalDeductions').textContent = totalDeductions.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            // Calculate Net Pay
            const netPay = grossEarnings - totalDeductions;
            document.getElementById('netPay').textContent = netPay.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        // Syncs the select dropdowns with the display values
        function syncSelectElements() {
            const allRows = document.querySelectorAll('.earning-row, .deduction-row');
            allRows.forEach(row => {
                const displaySpan = row.querySelector('.display-mode');
                const selectElement = row.querySelector('select.edit-mode');
                if (displaySpan && selectElement) {
                    selectElement.value = displaySpan.dataset.id;
                }
            });
        }

        // Function to toggle between display and edit modes
        function toggleEditMode(isEdit) {
            // Toggle the visibility of the different modes
            const displayElements = document.querySelectorAll('.display-mode');
            const editElements = document.querySelectorAll('.edit-mode');

            displayElements.forEach(el => el.style.display = isEdit ? 'none' : 'inline');
            editElements.forEach(el => el.style.display = isEdit ? 'inline' : 'none');

            // Toggle the button controls
            displayControls.style.display = isEdit ? 'none' : 'block';
            editControls.style.display = isEdit ? 'block' : 'none';

            // Update the total calculations when switching to edit mode
            if (isEdit) {
                updateTotals();
            }
        }

        // Event listener for the "Edit" button
        editButton.addEventListener('click', () => {
            syncSelectElements(); // Synchronize dropdowns before showing edit mode
            toggleEditMode(true);
        });

        // Event listener for the "Cancel" button
        cancelButton.addEventListener('click', () => {
            // Revert changes by reloading or re-rendering data
            location.reload(); // Simple approach to discard all changes
        });

        // Event listener for the "Save" button
        saveButton.addEventListener('click', () => {
            const payload = {
                employeeDetails: {
                    name: document.getElementById('employeeName').textContent,
                    jobTitle: document.getElementById('jobTitle').textContent,
                    employeeId: document.getElementById('employeeId').textContent,
                    department: document.getElementById('department').textContent
                },
                earnings: [],
                deductions: [],
                benefits: []
            };

            // Collect earnings data
            document.querySelectorAll('#earningsTable tbody tr.earning-row').forEach(row => {
                const selectElement = row.querySelector('select');
                const selectedOption = selectElement.options[selectElement.selectedIndex];
                const description = selectedOption.textContent;
                const amount = parseFloat(row.querySelector('input[type="number"]').value) || 0;
                const id = row.dataset.id;
                const record_id = row.dataset.record_id;
                payload.earnings.push({ id, record_id, description, amount });
            });

            // Collect deductions data
            document.querySelectorAll('#deductionsTable tbody tr.deduction-row').forEach(row => {
                const selectElement = row.querySelector('select');
                const selectedOption = selectElement.options[selectElement.selectedIndex];
                const description = selectedOption.textContent;
                const amount = parseFloat(row.querySelector('input[type="number"]').value) || 0;
                const id = row.dataset.id;
                const record_id = row.dataset.record_id;
                payload.deductions.push({ id, record_id, description, amount });
            });

            // Collect benefits data
            document.querySelectorAll('#benefitsTable tbody tr.benefit-row').forEach(row => {
                const selectElement = row.querySelector('select');
                const selectedOption = selectElement.options[selectElement.selectedIndex];
                const description = selectedOption.textContent;
                const amount = parseFloat(row.querySelector('input[type="number"]').value) || 0;
                const id = row.dataset.id;
                const record_id = row.dataset.record_id;
                payload.benefits.push({ id, record_id, description, amount });
            });

            const payslipId = "<?= hash_id($id, 'decode'); ?>";

            payload.payslipId = payslipId

            const updateURL = `${baseURL}ajax/payslip/updatePayslip`

            // Proposed Fetch Request
            // console.log('Proposed payload:', payload); // For demonstration
            fetch(updateURL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(payload),
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Success:', data);
                    // After successful save, switch back to display mode
                    toggleEditMode(false);
                    // Re-render the UI with the saved data
                    // This is a simplified approach, a more robust solution would re-fetch data
                    updateDisplayFromInputs();
                })
                .catch((error) => {
                    console.error('Error:', error);
                    alert('Failed to save changes. Please try again.');
                });
        });

        // Function to create a new editable row
        async function createEditableRow(optionType) {
            const payslipId = "<?= hash_id($id, 'decode'); ?>";
            const optionsURL = `${baseURL}ajax/payslip/getPayslipSectionOptions/${payslipId}`
            const response = await fetch(optionsURL)
            const responseData = await response.json()
            
            const { earnings, deductions} = responseData
            
            const earningOptions = earnings.payable_earning_options.map(option =>
                `<option value="${option.id}">${option.name}</option>`
            ).join('');

            const benefitOptions = earnings.accrued_earning_options.map(option =>
                `<option value="${option.id}">${option.name}</option>`
            ).join('');
            
            const deductionOptions = deductions.map(option =>
                `<option value="${option.id}">${option.name}</option>`
            ).join('');


            const typeClass = `${optionType}-row`
            
            let optionsHtml = '';

            switch(optionType){
                case 'earning':
                    optionsHtml = earningOptions;
                    break;
                case 'benefit':
                    optionsHtml = benefitOptions;
                    break;
                case 'deduction':
                    optionsHtml = deductionOptions;
                    break;
                default:
                    optionsHtml = earningOptions
            }

            const newRow = document.createElement('tr');
            newRow.className = typeClass;
            newRow.dataset.id = `new-${Date.now()}`; // Unique temporary ID
            newRow.innerHTML = `
                <td>
                    <span class="display-mode"></span>
                    <select class="form-control edit-mode"><option value="">Select an Option</option>${optionsHtml}</select>
                </td>
                <td class="text-right">
                    <span class="display-mode"></span>
                    <input type="number" step="0.01" class="form-control text-right edit-mode" value="0.00">
                </td>
                <td class="no-print edit-mode"><button class="btn btn-danger btn-sm remove-row">Remove</button></td>
            `;

            // Add event listener to the new row's input
            newRow.querySelector('input[type="number"]').addEventListener('input', updateTotals);
            
            // Show the remove button of the new inserted row
            newRow.lastElementChild.style.display = 'inline';
                        
            return newRow;
        }

        // Event listener for "Add Earning" button
        addEarningRowBtn.addEventListener('click', async () => {
            const newRow = await createEditableRow('earning');
            const totalRow = earningsTableBody.querySelector('.total-row');
            earningsTableBody.insertBefore(newRow, totalRow);
        });

        // Event listener for "Add Deduction" button
        addBenefitRowBtn.addEventListener('click', async () => {
            const newRow = await createEditableRow('benefit');
            const totalRow = benefitsTableBody.querySelector('.total-row');
            benefitsTableBody.insertBefore(newRow, totalRow);
        });

        // Event listener for "Add Deduction" button
        addDeductionRowBtn.addEventListener('click', async () => {
            const newRow = await createEditableRow('deduction');
            const totalRow = deductionsTableBody.querySelector('.total-row');
            deductionsTableBody.insertBefore(newRow, totalRow);
        });

        // Event delegation for "Remove" buttons
        document.body.addEventListener('click', (event) => {
            if (event.target.classList.contains('remove-row')) {
                const rowToRemove = event.target.closest('tr');
                rowToRemove.remove();
                updateTotals(); // Recalculate totals after removal
            }
        });

        // Event listener for input changes to automatically update totals
        document.querySelectorAll('#earningsTable, #deductionsTable').forEach(table => {
            table.addEventListener('input', (event) => {
                const target = event.target;
                if (target.tagName === 'INPUT' && target.type === 'number') {
                    updateTotals();
                }
            });
        });

        // Function to update the display mode text from the input/select values
        function updateDisplayFromInputs() {
            document.querySelectorAll('.edit-mode').forEach(editElement => {
                const displaySpan = editElement.previousElementSibling;
                const description = editElement.tagName === 'SELECT' ? editElement.options[editElement.selectedIndex].textContent : null;
                if (displaySpan) {
                    if (editElement.tagName === 'SELECT') {
                        displaySpan.textContent = description // editElement.value;
                    } else if (editElement.tagName === 'INPUT' && editElement.type === 'number') {
                        const formattedValue = parseFloat(editElement.value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        displaySpan.textContent = formattedValue;
                    }
                }
            });
            updateTotals();
        }

        /* --- New Navigation Functionality (Client-side Placeholder) --- */
        // These are placeholder functions since actual navigation would require a backend
        // to load different payslip data.
        function navigatePayslip(direction) {
            alert(`Navigating to the ${direction} payslip. (Requires backend implementation to load data.)`);
            // In a real application, you would make an AJAX/fetch request here to load the next/previous payslip data.
            // Example: fetch(`/api/payslip?id=${currentPayslipId + direction}`)
        }

        prevPayslipBtn.addEventListener('click', () => navigatePayslip('previous'));
        nextPayslipBtn.addEventListener('click', () => navigatePayslip('next'));
        /* --- End New Navigation Functionality --- */

        /**
         * Attaches a 'change' event listener to a select element within a table row.
         * When the select value changes, it updates the data-id attribute of the
         * closest table row with the selected option's value.
         *
         * @param {HTMLSelectElement} selectElement The select element to listen to.
         */
        const updateRowDataId = (selectElement) => {
            selectElement.addEventListener('change', (event) => {
                // Use event.target which is the select element that changed
                const selectedOptionValue = event.target.value;
                // The .closest('tr') method is generally more robust than
                // traversing up the DOM manually.
                const row = event.target.closest('tr');

                if (row) {
                    // Update the data-id attribute of the table row
                    row.dataset.id = selectedOptionValue;
                }
            });
        };

        // Select all relevant select elements across all specified tables.
        // The selector targets selects within a tbody, inside a row with one of the specific classes.
        // This single selector replaces the three original, more specific ones.
        const allSelects = document.querySelectorAll(
            '#earningsTable tbody tr.earning-row select, ' +
            '#deductionsTable tbody tr.deduction-row select, ' +
            '#benefitsTable tbody tr.benefit-row select'
        );

        // Apply the reusable function to every selected element.
        allSelects.forEach(updateRowDataId);

        document.querySelector('#deductionsTable tbody').addEventListener('click', (event) => {
            if(event.target.tagName){
                const selectElem = event.target
                event.target.addEventListener('change', () => {
                    selectedOptionValue = selectElem.options[selectElem.selectedIndex].value
                    const id = selectedOptionValue
                    selectElem.closest('tr').dataset.id = id
                })
            }
        })

    });

</script>

<script>
    (async () => {

        editButtonBtn = document.querySelector("#editButton")

        const orgDetailsURL = `${baseURL}ajax/payslip/getOfficePayslipDetails`;
        const payslipId = "<?= hash_id($id, 'decode'); ?>";
        const payslipDataURL = `${baseURL}ajax/payslip/getPayslipData`;

        const payslipheaderTitle = document.getElementById('payslip_organization_name');
        const payslipheaderAddress = document.getElementById('payslip_organization_address');
        const payPeriod = document.getElementById('payslip_pay_period');
        const payTimeFrame = payPeriod.querySelectorAll('p')[0];
        const payDate = payPeriod.querySelectorAll('p')[1];
        const currencySymbols = document.querySelectorAll('.currency_symbol');

        const employeeName = document.getElementById('employeeName');
        const jobTitle = document.getElementById('jobTitle');
        const employeeId = document.getElementById('employeeId');
        const userDepartment = document.getElementById('department');

        const employeeDataSectionGeneration = (payslipData) => {
            const { user_fullname, job_title, employment_number, department } = payslipData.user;
            employeeName.textContent = user_fullname;
            jobTitle.textContent = job_title;
            employeeId.textContent = employment_number;
            userDepartment.textContent = department;
        };

        const payslipHeaderGeneration = ({ orgData, payslipData }) => {
            const { office_postal_address, office_email, office_phone, office_currency_code } = orgData;
            const { payslip_period_start_date, payslip_period_end_date, payslip_pay_date } = payslipData;
            // const { start_date, end_date } = payslip_period;

            const address = `${office_postal_address}<br>${office_email}<br>${office_phone}`;
            const timeFrame = `${payslip_period_start_date} to ${payslip_period_end_date}`;

            payslipheaderAddress.textContent = '';
            payslipheaderTitle.textContent = orgData.office_name;
            payslipheaderAddress.insertAdjacentHTML("afterbegin", address);
            payTimeFrame.insertAdjacentHTML("beforeend", timeFrame);
            payDate.insertAdjacentText("beforeend", payslip_pay_date);

            currencySymbols.forEach(holder => {
                holder.textContent = office_currency_code;
            });
        };


        const earningsBody = earningsTable.querySelector('#earningsTable tbody');
        const deductionsBody = deductionsTable.querySelector('#deductionsTable tbody');
        const benefitsBody = benefitsTable.querySelector('#benefitsTable tbody');

        const deductionsTemplate = (deductionOptions) => {
            if (!Array.isArray(deductionOptions)) return '';
            const options = deductionOptions.map(option =>
                `<option value="${option.id}">${option.name}</option>`
            ).join('');
            return `<select class="form-control edit-mode">${options}</select>`;
        };

        const earningsTemplate = (earningsOptions) => {
            if (!Array.isArray(earningsOptions)) return '';
            const options = earningsOptions.map(option =>
                `<option value="${option.id}">${option.name}</option>`
            ).join('');

            return `<select class="form-control edit-mode">${options}</select>`;
        };

        const deductionRow = ({ deductionId, recordId, title, deductionOptions, value }) => {
            const formatter = new Intl.NumberFormat();
            return `<tr data-id="${deductionId}" data-record_id="${recordId}" class="deduction-row">
                            <td>
                                <span class="display-mode" data-id="${deductionId}">${title}</span>
                                ${deductionsTemplate(deductionOptions)}
                            </td>
                            <td class="text-right">
                                <span class="display-mode">${formatter.format(value)}</span>
                                <input type="number" step="0.01" class="form-control text-right edit-mode"
                                    value="${value}">
                            </td>
                            <td class="no-print edit-mode"><button
                                    class="btn btn-danger btn-sm remove-row">Remove</button></td>
                        </tr>`
        }

        const earningRow = ({ earningId, recordId, title, earningsOptions, value }) => {
            const formatter = new Intl.NumberFormat();
            return `<tr data-id="${earningId}" data-record_id="${recordId}" class="earning-row">
                        <td>
                            <span class="display-mode" data-id="${earningId}">${title}</span>
                            ${earningsTemplate(earningsOptions)}
                        </td>
                        <td class="text-right">
                            <span class="display-mode">${formatter.format(value)}</span>
                            <input type="number" step="0.01" class="form-control text-right edit-mode" value="${value}">
                        </td>
                        <td class="no-print edit-mode">
                            <button class="btn btn-danger btn-sm remove-row">Remove</button>
                        </td>
                    </tr>`;
        };

        const benefitRow = ({ earningId, recordId, title, earningsOptions, value }) => {
            const formatter = new Intl.NumberFormat();
            return `<tr data-id="${earningId}" data-record_id="${recordId}" class="benefit-row">
                        <td>
                            <span class="display-mode" data-id="${earningId}">${title}</span>
                            ${earningsTemplate(earningsOptions)}
                        </td>
                        <td class="text-right">
                            <span class="display-mode">${formatter.format(value)}</span>
                            <input type="number" step="0.01" class="form-control text-right edit-mode" value="${value}">
                        </td>
                        <td class="no-print edit-mode">
                            <button class="btn btn-danger btn-sm remove-row">Remove</button>
                        </td>
                    </tr>`;
        };

        const earningsBodyGeneration = (earnings, earningsOptions) => {
            if (!Array.isArray(earnings)) return '';
            return earnings.map(earning =>
                earningRow({
                    earningId: earning.id,
                    recordId: earning.record_id,
                    title: earning.name,
                    earningsOptions,
                    value: earning.amount
                })
            ).join('');
        };

        const benefitsBodyGeneration = (earnings, earningsOptions) => {
            if (!Array.isArray(earnings)) return '';
            return earnings.map(earning =>
                benefitRow({
                    earningId: earning.id,
                    recordId: earning.record_id,
                    title: earning.name,
                    earningsOptions,
                    value: earning.amount
                })
            ).join('');
        };

        const deductionsBodyGeneration = (deductions, deductionOptions) => {
            if (!Array.isArray(deductions)) return '';
            return deductions.map(deduction =>
                deductionRow({
                    deductionId: deduction.id,
                    recordId: deduction.record_id,
                    title: deduction.name,
                    deductionOptions,
                    value: deduction.amount
                })
            ).join('');
        };

        const params = {
            headers: { "Content-Type": "application/json" },
            method: "POST",
            body: JSON.stringify({ payslipId })
        };

        try {
            const [orgDetailsResponse, payslipDataResponse] = await Promise.all([
                fetch(orgDetailsURL, params),
                fetch(payslipDataURL, params)
            ]);

            if (!orgDetailsResponse.ok) {
                throw new Error(`HTTP error! Status: ${orgDetailsResponse.status}`);
            }
            if (!payslipDataResponse.ok) {
                throw new Error(`HTTP error! Status: ${payslipDataResponse.status}`);
            }

            const orgData = await orgDetailsResponse.json();
            const payslipData = await payslipDataResponse.json();
            const totalEarnings = payslipData.earnings.payable_earnings.reduce((sum, earning) => sum + parseFloat(earning.amount), 0);
            const totalDeductions = payslipData.deductions.reduce((sum, deduction) => sum + parseFloat(deduction.amount), 0);
            const totalAccruedBenefits = payslipData.earnings.accrued_earnings.reduce((sum, earning) => sum + parseFloat(earning.amount), 0);
            const userLocale = payslipData.user.user_locale;

            // console.log(userLocale)
            document.getElementById('grossEarnings').textContent = totalEarnings.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            document.getElementById('totalDeductions').textContent = totalDeductions.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });            
            document.getElementById('totalBenefits').textContent = totalAccruedBenefits.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            document.getElementById('netPay').textContent = (totalEarnings - totalDeductions).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            // Check if the data is structured as expected before accessing properties
            if (orgData && Object.keys(orgData).length && payslipData) {
                payslipHeaderGeneration({ orgData, payslipData });
                
                if (payslipData.user) {
                    employeeDataSectionGeneration(payslipData);
                }

                // Safely access earnings and earnings options
                const payable_earnings = payslipData.earnings.payable_earnings || [];
                const payableEarningsOptions = payslipData.options?.earnings?.payable_earning_options || [];
                earningsBody.insertAdjacentHTML("afterbegin", earningsBodyGeneration(payable_earnings, payableEarningsOptions));

                const accrued_earnings = payslipData.earnings.accrued_earnings || [];
                const accruedEarningsOptions = payslipData.options?.earnings?.accrued_earning_options || [];
                benefitsBody.insertAdjacentHTML("afterbegin", benefitsBodyGeneration(accrued_earnings, accruedEarningsOptions));

                const deductions = payslipData.deductions || [];
                const deductionOptions = payslipData.options?.deductions || [];
                deductionsBody.insertAdjacentHTML("afterbegin", deductionsBodyGeneration(deductions, deductionOptions));

                // Apply permissions to edit button 
                if(!['canUpdate','canDelete'].includes(payslipData?.permission || 'canRead')){
                    editButtonBtn.style.display = 'none'
                }
            }
        } catch (error) {
            console.error("Error occurred:", error);
        }
    })();
</script>