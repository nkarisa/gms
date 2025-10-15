<!-- Load Bootstrap 3 CSS -->
<style>
        @import url('https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/css/bootstrap.min.css');
        
        /* Custom styles */
        body {
            background-color: #f7f9fb;
        }
        .container-wrapper {
            margin: 40px auto;
            padding: 20px;
            background: white;
            border-radius: 6px; /* Simple rounded corners */
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); /* Simple shadow */
        }
        .page-title {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 30px;
            font-weight: bold;
            color: #333;
        }
        .table > thead > tr > th {
            border-bottom: 2px solid #ddd;
            background-color: #f5f5f5;
        }
        /* Adjusted .clear_accrual for Bootstrap button styles */
        .btn.clear_accrual {
            /* Overriding to ensure compatibility if custom styles were needed, but using btn-success now */
            transition: background-color 0.2s;
        }
        .form-control[readonly] {
            background-color: #eeeeee;
        }
        /* Style for the dynamic status message */
        #statusMessage {
            margin-top: 15px;
        }
        .clearance-row {
            margin-bottom: 10px;
        }
        .row-header {
            font-weight: bold;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
            margin-bottom: 5px;
            color: #555;
        }

#clearAccrualModal {
    padding-right: 0px;
    background-color: rgb(0 0 0 / 66%);
    place-items: center;
    height: 100vh;
    width: 100vw;
    display: none;
}

.modal-backdrop.fade.in {
    display: none;
}

#clearAccrualModal.show_model{
    display: grid;
}

</style>

<div class="container-wrapper">
        <h1 class="page-title">Accrual Transactions</h1>
        
        <!-- CI4: This table would be populated by looping through data passed from the controller -->
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Voucher Number</th>
                        <th>Voucher Description</th>
                        <th>Vocuher Amount</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <!-- ID added to tbody to allow for dynamic injection -->
                <tbody id="accrualsTableBody">
                    <!-- Dynamic rows will be loaded here by JavaScript -->
                    <tr>
                        <td colspan="4" class="text-center" style="padding: 20px;">
                            <i class="glyphicon glyphicon-refresh spinning"></i> Loading Accruals...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
</div>

<script>
        document.addEventListener('DOMContentLoaded', function() {
            
            // Global state variables
            let currentAccrualId = null;
            let currentUnclearedAmount = 0; // The TOTAL uncleared amount for the parent accrual
            let incomeAccountsCache = []; // Cache for income account dropdown options
            let officeId = null;
            let accrualData = null
            /**
             * Formats a number as currency string.
             * @param {number} amount 
             * @returns {string}
             */
            function formatCurrency(amount) {
                return new Intl.NumberFormat('en-US', {
                    style: 'currency',
                    currency: 'USD',
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }).format(amount).replace('$', ''); 
            }

            // =========================================================
            // CORE FUNCTIONS: TABLE POPULATION
            // =========================================================

            /**
             * Attaches the click event listener to all "Clear Accrual" buttons.
             * This must be called after the table is populated.
             */
            function attachAccrualButtonListeners() {
                document.querySelectorAll('.clear_accrual').forEach(button => {
                    // Remove existing listeners to prevent duplicates (important after table refresh)
                    button.removeEventListener('click', handleAccrualButtonClick); 
                    button.addEventListener('click', handleAccrualButtonClick);
                });
            }

            /**
             * Handles the click on a "Clear Accrual" button.
             */
            function handleAccrualButtonClick() {
                const id = this.dataset.id; 
                const description = this.dataset.description;
                const voucher_number = this.dataset.voucher_number
                const office_id = this.dataset.office_id
                
                currentAccrualId = id;
                currentAccrualVoucherNumber = voucher_number;
                officeId = office_id

                // Reset modal state
                document.getElementById('clearanceForm').reset(); 
                document.getElementById('partialClearanceSection').style.display = 'none';
                document.getElementById('statusMessage').innerHTML = '';
                document.getElementById('statusMessage').style.display = 'none';
                document.getElementById('clearanceRowsContainer').innerHTML = '';
                currentUnclearedAmount = 0;
                
                // Set hidden ID and modal title
                document.getElementById('accrualId').value = id;
                document.getElementById('accrualDescription').textContent = description;

                document.getElementById("clearAccrualModal").classList.toggle('show_model')
            }

            /**
             * Populates the main accruals table with data.
             * @param {Array<Object>} accruals - List of accrual objects.
             */
            function populateAccrualsTable(accruals) {
                const tableBody = document.getElementById('accrualsTableBody');
                if (!tableBody) return;
                
                tableBody.innerHTML = ''; // Clear existing content

                const rowsHtml = accruals.map(accrual => `
                    <tr>
                        <td><a target="__blank" href="${baseURL}voucher/view/${accrual.voucher_hashed_id}">${accrual.voucher_number}</a></td>
                        <td>${accrual.description}</td>
                        <td>$${accrual.amount}</td>
                        <td class="text-center">
                            <button 
                                class="clear_accrual btn btn-sm btn-success" 
                                data-id="${accrual.voucher_id}" 
                                data-voucher_number="${accrual.voucher_number}"
                                data-description="${accrual.voucher_number}" 
                                data-office_id="${accrual.office_id}"
                                data-toggle="modal" 
                                data-target="#clearAccrualModal"
                            >
                                Clear Accrual
                            </button>
                        </td>
                    </tr>
                `).join('');
                
                tableBody.innerHTML = rowsHtml;

                // Attach listeners to the newly created buttons
                attachAccrualButtonListeners();
            }

            /**
             * Simulated fetch request to get the main list of accrual transactions.
             */
            async function fetchAccruals() {
                const tableBody = document.getElementById('accrualsTableBody');
                if (!tableBody) return;
                
                // Display loading message (already in HTML, but can be reset)
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="4" class="text-center" style="padding: 20px;">
                            <i class="glyphicon glyphicon-refresh spinning"></i> Loading Accruals...
                        </td>
                    </tr>
                `;

                try {
                    // await new Promise(resolve => setTimeout(resolve, 1000)); // Simulate API delay
                    const tableRowFetchURL = `${baseURL}/ajax/accrual_clearance/getUnclearedAccrualTransactions`
                    const responseRows = await fetch(tableRowFetchURL)
                    const accrualList = await responseRows.json()

                    // Success: Populate the table
                    populateAccrualsTable(accrualList); // simulatedAccrualList
                    
                } catch (error) {
                    console.error('Failed to fetch accrual list:', error);
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="4" class="text-center text-danger" style="padding: 20px;">
                                <i class="glyphicon glyphicon-warning-sign"></i> Failed to load accrual data.
                            </td>
                        </tr>
                    `;
                }
            }


            // =========================================================
            // CORE FUNCTIONS: MODAL POPULATION (existing functions)
            // =========================================================

            /**
             * Generates an HTML structure for a single amount clearance row.
             * @param {number} totalUnclearedAmount - The total numerical uncleared amount for validation (max).
             * @returns {string} HTML string for the row.
             */
            function generateClearanceRow(accrualData) {
                const rowId = 'row-' + Date.now(); // Using Date.now()
                
                // Generate <option> tags from the cached income accounts
                const selectOptions = accrualData.map(acc => 
                    `<option value="${acc.income_account_id}">${acc.income_account_name}</option>`
                ).join('');

                return `
                    <div class="row clearance-row" id="${rowId}">
                        <div class="col-xs-5">
                            <div class="form-group">
                                <select class="form-control income-account-select" 
                                        name="clear_account_code[${rowId}]" 
                                        data-row-id="${rowId}" 
                                        required>
                                    <option value="" disabled selected>Select Account</option>
                                    ${selectOptions}
                                </select>
                            </div>
                        </div>
                        <div class="col-xs-3">
                            <div class="form-group">
                                <input type="text" class="form-control remaining-amount-field"  
                                       value="0.00" readonly>
                            </div>
                        </div>
                        <div class="col-xs-3">
                            <div class="form-group">
                                <input type="number" step="0.01" class="form-control amount-to-clear" 
                                    name="amount_to_clear[${rowId}]" placeholder="Clear Amount" 
                                    value="" max="0" required>
                            </div>
                        </div>
                        <div class="col-xs-1 text-right">
                            <button type="button" class="btn btn-danger remove-row-btn" data-row-id="${rowId}" style="margin-top: 5px; padding: 5px 8px;">
                                <i class="glyphicon glyphicon-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
            }

            clearanceRowsContainer.addEventListener('change', async function(event) {
                if (!event.target.classList.contains('income-account-select')) return;

                const select = event.target;
                const selectedAccountId = select.value;
                const rowId = select.dataset.rowId;
                const row = document.getElementById(rowId);

                const remainingField = row.querySelector('.remaining-amount-field');
                const clearAmountField = row.querySelector('.amount-to-clear');
                const status = document.getElementById('statusMessage');

                status.style.display = 'none';
                status.classList.remove('alert-danger', 'alert-warning');

                // 1. Validation: Check for duplicates
                let isDuplicate = false;
                document.querySelectorAll('.income-account-select').forEach(selected => {
                    if (selected !== select && selected.value === selectedAccountId) {
                        console.log('selected', selected, 'select', select, 'value', select.value, 'selectedAccountId', selectedAccountId)
                        isDuplicate = true;
                    }
                });

                if (isDuplicate) {
                    select.value = ''; 
                    remainingField.value = '0.00';
                    clearAmountField.value = '';
                    clearAmountField.setAttribute('max', '0');
                    status.innerHTML = '<i class="glyphicon glyphicon-alert"></i> **Error:** This Income Account is already selected in another row.';
                    status.style.display = 'block';
                    status.classList.add('alert-danger');
                    return;
                }

                accrualData.forEach(accrualTransactionRemainingAmount => {
                    if(accrualTransactionRemainingAmount.income_account_id == selectedAccountId){
                        remainingField.value = accrualTransactionRemainingAmount.uncleared_amount
                        clearAmountField.setAttribute('max', accrualTransactionRemainingAmount.uncleared_amount.toFixed(2));
                    }
                })

                currentUnclearedAmount = calculateRemainingAmountTotal()
                
                
            });

            clearanceRowsContainer.addEventListener('click', function(event) {
                const button = event.target.closest('.remove-row-btn');
                if (!button) return;

                const rowToRemove = button.closest('.clearance-row');
                rowToRemove.remove(); 

                if (clearanceRowsContainer.children.length === 0 && accrualData.length > 0) {
                     clearanceRowsContainer.insertAdjacentHTML('beforeend', generateClearanceRow(accrualData));
                }
            });

            /**
             * Simulated fetch request to get Bank Accounts from the server.
             */
            async function fetchBankAccounts() {
                const select = document.getElementById('bankAccount');
                
                select.innerHTML = '';
                select.insertAdjacentHTML('beforeend', `<option value="" disabled selected>Loading Banks...</option>`);
                select.disabled = true;

                // await new Promise(resolve => setTimeout(resolve, 700));
                const bankAccountsURL = `${baseURL}ajax/accrual_clearance/getActionOfficeBanks/${officeId}`
                const response = await fetch(bankAccountsURL)
                const bankAccounts = await response.json();

                select.innerHTML = '';
                select.insertAdjacentHTML('beforeend', `<option value="" disabled selected>Select Bank Account</option>`);

                bankAccounts.forEach(account => {
                    const option = document.createElement('option');
                    option.value = account.office_bank_id;
                    option.textContent = account.office_bank_name;
                    select.appendChild(option);
                });
                
                select.disabled = false;
            }


            /**
             * Simulated fetch request to get total uncleared amount for the accrual.
             */
            async function fetchAccrualDetails(accrualId) {
                const section = document.getElementById('partialClearanceSection');
                const status = document.getElementById('statusMessage');
                const clearanceRowsContainer = document.getElementById('clearanceRowsContainer');
                
                status.style.display = 'none';
                status.classList.remove('alert-danger', 'alert-success', 'alert-info');
                clearanceRowsContainer.innerHTML = ''; 

                try {
                    status.innerHTML = '<i class="glyphicon glyphicon-time"></i> Loading clearance details...';
                    status.style.display = 'block';
                    status.classList.add('alert-info');
                    
                    // await new Promise(resolve => setTimeout(resolve, 500));
                    const unclearedAmountURL = `${baseURL}ajax/accrual_clearance/getUnclearedTransactionDetails/${accrualId}`
                    const unclearedAmountResponse = await fetch(unclearedAmountURL);
                    accrualData = await unclearedAmountResponse.json(); // simulatedAccrualData
                    
                    // console.log(data)

                    if (!accrualData) {
                        throw new Error("Accrual details not found.");
                    }
                    // alert(currentUnclearedAmount)
                    // Store the total uncleared amount globally
                    // currentUnclearedAmount = data.uncleared_amount; 

                    // 1. Initialize with the first clearance row
                    clearanceRowsContainer.insertAdjacentHTML('beforeend', generateClearanceRow(accrualData));
                    
                    // 2. Show the section
                    section.style.display = 'block'; 
                    
                    // 3. Clear status
                    status.innerHTML = '';
                    status.style.display = 'none';

                } catch (error) {
                    console.error('Fetch error:', error);
                    section.style.display = 'none'; 
                    status.innerHTML = '<i class="glyphicon glyphicon-warning-sign"></i> Failed to load details: ' + error.message;
                    status.style.display = 'block';
                    status.classList.add('alert-danger');
                }
            }
            
            // =========================================================
            // EVENT HANDLERS (using Vanilla JS for delegation where possible)
            // =========================================================
            
            // 1. Initial Data Load (Main Table)
            fetchAccruals(); 

            // 2. Handle Modal Show Event - Use jQuery for Bootstrap 3 event
            $('#clearAccrualModal').on('show.bs.modal', async function(e) {
                const status = document.getElementById('statusMessage');
                
                status.innerHTML = '<i class="glyphicon glyphicon-refresh spinning"></i> Fetching configuration data...';
                status.style.display = 'block';
                status.classList.add('alert-info');
                
                try {
                    // Fetch all dependent data concurrently
                    await Promise.all([
                        fetchBankAccounts(),
                    ]);

                    // Now that data is ready, fetch accrual details and build dynamic rows
                    if (currentAccrualId) {
                        await fetchAccrualDetails(currentAccrualId);
                    }
                } catch (error) {
                    status.innerHTML = '<i class="glyphicon glyphicon-warning-sign"></i> Critical Error: Could not load all required data.';
                    status.style.display = 'block';
                    status.classList.add('alert-danger');
                    console.error('Modal initialization failed:', error);
                }
            });


            // 3.b Add Row
            document.getElementById('addRowBtn').addEventListener('click', function() {
                const status = document.getElementById('statusMessage');
                if (accrualData.length > 0) {
                    clearanceRowsContainer.insertAdjacentHTML('beforeend', generateClearanceRow(accrualData));
                } else {
                     status.innerHTML = '<i class="glyphicon glyphicon-warning-sign"></i> Cannot add row: Accrual details or Income Accounts not fully loaded.';
                     status.style.display = 'block';
                     status.classList.add('alert-warning');
                }
            });

            function calculateRemainingAmountTotal() {
                // 1. Find all elements with the specific class
                // document.querySelectorAll returns a NodeList (which supports .forEach)
                const amountInputs = document.querySelectorAll('.remaining-amount-field');

                let totalSum = 0;

                // 2. Use forEach to iterate over the collection
                amountInputs.forEach(inputField => {
                    // Get the value from the input field
                    const valueString = inputField.value;

                    // 3. Convert the string value to a number and add it to the total
                    // parseFloat is used to handle decimal numbers
                    // The || 0 ensures that if the value is empty or not a valid number, 0 is added
                    const numericalValue = parseFloat(valueString) || 0;

                    totalSum += numericalValue;
                });

                // Return the final calculated sum
                return totalSum;
                }


            // 4. Handle Form Submission (Simulation)
            document.getElementById('clearanceForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const form = e.target;
                const formData = [];
                const elements = form.querySelectorAll('input, select');
                
                elements.forEach(element => {
                    if (element.name && element.value && !element.disabled) {
                        formData.push({ name: element.name, value: element.value });
                    }
                });
                
                console.log('--- Form Data to be Submitted to CodeIgniter Controller (Vanilla JS Serialized) ---');
                console.log(formData);

                let totalCleared = 0;
                const status = document.getElementById('statusMessage');
                
                formData.forEach(item => {
                    if (item.name.startsWith('amount_to_clear[')) {
                        totalCleared += parseFloat(item.value) || 0;
                    }
                });
                
                status.classList.remove('alert-danger', 'alert-success');
                
                if (totalCleared > currentUnclearedAmount) {
                    status.innerHTML = `<i class="glyphicon glyphicon-alert"></i> **Error:** Total cleared amount ($${totalCleared.toFixed(2)}) exceeds the *parent accrual's* total uncleared amount ($${currentUnclearedAmount.toFixed(2)}).`;
                    status.style.display = 'block';
                    status.classList.add('alert-danger');
                    return;
                }

                status.innerHTML = '<i class="glyphicon glyphicon-ok"></i> Data posted successfully!';
                status.style.display = 'block';
                status.classList.add('alert-success');

                // Hide the modal 
                
            });

        });
</script>

<div class="modal fade" id="clearAccrualModal" tabindex="-1" role="dialog" aria-labelledby="clearAccrualLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="clearAccrualLabel">Clear Accrual Transaction: <span
                        id="accrualDescription"></span></h4>
            </div>

            <form id="clearanceForm">
                <input type="hidden" id="accrualId" name="accrual_id">

                <div class="modal-body">

                    <!-- 1. Bank Account - DYNAMICALLY POPULATED -->
                    <div class="form-group">
                        <label for="bankAccount">Bank Account</label>
                        <select class="form-control" id="bankAccount" name="bank_account" required>
                            <option value="" disabled selected>Loading Accounts...</option>
                        </select>
                    </div>

                    <!-- Dynamic Clearance Section - Populates on modal show -->
                    <div id="partialClearanceSection" class="well"
                        style="display: none; background-color: #f9f9f9; border-left: 5px solid #5cb85c;">
                        <h5 class="text-success" style="font-weight: bold; margin-top: 0; margin-bottom: 15px;">
                            Clearance Details</h5>

                        <!-- Dynamic Clearance Rows Container -->
                        <h5 style="font-size: 14px; font-weight: bold; margin-top: 5px; margin-bottom: 10px;">Individual
                            Clearance Entries</h5>

                        <!-- Row Header for dynamic fields -->
                        <div class="row row-header">
                            <div class="col-xs-5">Account</div>
                            <div class="col-xs-3">Remaining Amt</div>
                            <div class="col-xs-3">Clear Amount</div>
                            <div class="col-xs-1"></div>
                        </div>

                        <div id="clearanceRowsContainer">
                            <!-- Dynamic rows are injected here -->
                        </div>

                        <button type="button" id="addRowBtn" class="btn btn-xs btn-default btn-block"
                            style="margin-top: 10px;">
                            <i class="glyphicon glyphicon-plus"></i> Add Row
                        </button>
                    </div>

                    <!-- Loading/Error Message Area -->
                    <div id="statusMessage" style="padding: 10px; border-radius: 4px; display: none;"></div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success">Save Clearance</button>
                </div>
            </form>
        </div>
    </div>
</div>

