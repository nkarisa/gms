<!-- Bootstrap 3 Modal Structure -->
    <div class="modal fade" id="clearAccrualModal" tabindex="-1" role="dialog" aria-labelledby="clearAccrualLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="clearAccrualLabel">Clear Accrual: <span id="accrualDescription"></span></h4>
                </div>
                
                <form id="clearanceForm">
                    <input type="hidden" id="accrualId" name="accrual_id">

                    <div class="modal-body">
                        
                        <!-- 1. Bank Account - NOW DYNAMICALLY POPULATED -->
                        <div class="form-group">
                            <label for="bankAccount">Bank Account</label>
                            <select class="form-control" id="bankAccount" name="bank_account" required>
                                <option value="" disabled selected>Loading Accounts...</option>
                            </select>
                        </div>
                        
                        <!-- 2. Partial Clearance Select -->
                        <div class="form-group">
                            <label for="partialClearance">Partial Clearance</label>
                            <select class="form-control" id="partialClearance" name="partial_clearance" required>
                                <option value="No" selected>No</option>
                                <option value="Yes">Yes</option>
                            </select>
                        </div>

                        <!-- Dynamic Partial Clearance Section -->
                        <div id="partialClearanceSection" class="well" style="display: none; background-color: #f9f9f9; border-left: 5px solid #5cb85c;">
                            <h5 class="text-success" style="font-weight: bold; margin-top: 0; margin-bottom: 10px;">Partial Clearance Details</h5>

                            <!-- Account (Readonly, from Fetch) -->
                            <div class="form-group">
                                <label for="incomeAccountCode">Account (Income Account Code)</label>
                                <input type="text" class="form-control" id="incomeAccountCode" name="income_account_code" readonly>
                            </div>

                            <!-- Original Amount (Readonly, from Fetch) -->
                            <div class="form-group">
                                <label for="originalAmount">Original Amount</label>
                                <input type="text" class="form-control" id="originalAmount" name="original_amount" readonly>
                            </div>

                            <!-- Uncleared Amount (Readonly, from Fetch) -->
                            <div class="form-group">
                                <label for="unclearedAmount">Uncleared Amount</label>
                                <input type="text" class="form-control" id="unclearedAmount" name="uncleared_amount" readonly>
                            </div>

                            <!-- Amount to Clear (Input Field) -->
                            <div class="form-group">
                                <label for="amountToClear">Amount to Clear</label>
                                <input type="number" step="0.01" class="form-control" id="amountToClear" name="amount_to_clear" placeholder="Enter amount to clear" required>
                            </div>
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

    <!-- Load jQuery (required for Bootstrap 3 JS) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <!-- Load Bootstrap 3 JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/js/bootstrap.min.js"></script>

    <script>
        $(document).ready(function() {
            
            // Global flag to track the current accrual ID
            let currentAccrualId = null;

            // Simulated data that a CodeIgniter controller (AccrualController::getAccrualDetails) would return
            const simulatedAccrualData = {
                '101': {
                    account: 'INC005 - Office Income',
                    original_amount: 1500.00,
                    uncleared_amount: 1500.00
                },
                '102': {
                    account: 'INC010 - Marketing Services',
                    original_amount: 5200.00,
                    uncleared_amount: 4500.00 // Example of a partially cleared item
                }
            };

            // Simulated data for Bank Accounts
            const simulatedBankAccounts = [
                { value: 'BA001', text: 'Corporate Checking (BA001)' },
                { value: 'BA002', text: 'Treasury Savings (BA002)' },
                { value: 'BA003', text: 'Payroll Account (BA003)' }
            ];
            
            /**
             * Formats a number as currency string.
             * @param {number} amount 
             * @returns {string}
             */
            function formatCurrency(amount) {
                return '$' + new Intl.NumberFormat('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }).format(amount);
            }

            /**
             * Simulated fetch request to get Bank Accounts from the server.
             */
            async function fetchBankAccounts() {
                const select = $('#bankAccount');
                
                // Set initial loading state
                select.empty(); 
                select.append($('<option>', {
                    value: '',
                    text: 'Loading Accounts...',
                    disabled: true,
                    selected: true
                }));
                select.prop('disabled', true); // Disable selection while loading

                try {
                    // Simulate network delay for 700ms
                    await new Promise(resolve => setTimeout(resolve, 700));

                    // Clear loading state and add main placeholder
                    select.empty();
                    select.append($('<option>', {
                        value: '',
                        text: 'Select Bank Account',
                        disabled: true,
                        selected: true
                    }));

                    // Populate accounts from simulated data
                    simulatedBankAccounts.forEach(account => {
                        select.append($('<option>', {
                            value: account.value,
                            text: account.text
                        }));
                    });
                    
                    // Enable the select element
                    select.prop('disabled', false);

                } catch (error) {
                    console.error('Failed to load bank accounts:', error);
                    select.empty().append($('<option>', {
                        value: '',
                        text: 'Error loading accounts',
                        disabled: true,
                        selected: true
                    }));
                    select.prop('disabled', false);
                }
            }


            /**
             * Simulated fetch request to get accrual details from the server.
             * @param {string} accrualId - The ID of the accrual to fetch details for.
             */
            async function fetchAccrualDetails(accrualId) {
                const section = $('#partialClearanceSection');
                const status = $('#statusMessage');
                
                // Clear previous state and hide status message
                status.hide().removeClass('alert-danger alert-success alert-info');
                section.find('input[type="text"]').val('');
                $('#amountToClear').val('');

                try {
                    // Show a loading message (simulated)
                    status.html('<i class="glyphicon glyphicon-time"></i> Loading details...').show().addClass('alert-info');
                    
                    // Simulate network delay for 500ms
                    await new Promise(resolve => setTimeout(resolve, 500));
                    
                    // Simulate CI4 response
                    const data = simulatedAccrualData[accrualId];
                    
                    if (!data) {
                        throw new Error("Accrual details not found.");
                    }
                    
                    // 1. Populate fields
                    $('#incomeAccountCode').val(data.account);
                    $('#originalAmount').val(formatCurrency(data.original_amount));
                    $('#unclearedAmount').val(formatCurrency(data.uncleared_amount));
                    $('#amountToClear').attr('max', data.uncleared_amount).val(data.uncleared_amount); // Set max for validation
                    
                    // 2. Show the section
                    section.slideDown(200);
                    
                    // 3. Clear status
                    status.html('').hide();

                } catch (error) {
                    console.error('Fetch error:', error);
                    section.slideUp(200);
                    status.html('<i class="glyphicon glyphicon-warning-sign"></i> Failed to load details: ' + error.message).show().addClass('alert-danger');
                }
            }

            // -----------------------------------------------------------
            // 1. Handle "Clear Accrual" Button Click (.clear_accrual)
            // This sets the ID/Description and prepares the modal state.
            // -----------------------------------------------------------
            $('.clear_accrual').on('click', function() {
                const id = $(this).data('id');
                const description = $(this).data('description');
                
                currentAccrualId = id;

                // Reset modal form fields
                $('#clearanceForm')[0].reset(); 
                $('#partialClearanceSection').hide();
                $('#statusMessage').html('').hide();
                
                // Set hidden ID and modal title
                $('#accrualId').val(id);
                $('#accrualDescription').text(description + ' (ID: ' + id + ')');

                // The modal is now shown via the data-toggle/data-target attributes.
            });
            
            // -----------------------------------------------------------
            // 2. Handle Modal Show Event
            // This triggers the bank account fetch immediately upon modal opening.
            // -----------------------------------------------------------
            $('#clearAccrualModal').on('show.bs.modal', function(e) {
                // Fetch bank accounts when the modal is about to be shown
                fetchBankAccounts();
            });


            // -----------------------------------------------------------
            // 3. Handle "Partial Clearance" Select Change
            // -----------------------------------------------------------
            $('#partialClearance').on('change', function() {
                const selection = $(this).val();
                
                if (selection === 'Yes') {
                    // If 'Yes' is selected, trigger the fetch request
                    if (currentAccrualId) {
                        fetchAccrualDetails(currentAccrualId);
                    } else {
                         // Should not happen if button click is handled correctly
                        $('#statusMessage').html('<i class="glyphicon glyphicon-info-sign"></i> Error: No accrual ID selected.').show().addClass('alert-danger');
                    }

                } else {
                    // If 'No' is selected, remove the dynamic section
                    $('#partialClearanceSection').slideUp(200);
                    $('#statusMessage').html('').hide();
                    
                    // Ensure the 'Amount to Clear' is not required if full clearance is chosen (No)
                    $('#amountToClear').removeAttr('required');
                }
            });


            // -----------------------------------------------------------
            // 4. Handle Form Submission (Simulation)
            // -----------------------------------------------------------
            $('#clearanceForm').on('submit', function(e) {
                e.preventDefault();
                
                const formData = $(this).serializeArray();
                
                // CI4: This is where you would make the final POST request to your controller
                console.log('--- Form Data to be Submitted to CodeIgniter Controller ---');
                console.log(formData);

                $('#statusMessage').html('<i class="glyphicon glyphicon-ok"></i> Clearance simulated successfully!').show().addClass('alert-success');
                
                // In a real app, you would close the modal after a successful submission
                // $('#clearAccrualModal').modal('hide'); 
            });

        });
    </script>