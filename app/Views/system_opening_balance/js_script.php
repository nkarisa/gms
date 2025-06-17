<?php
use App\Enums\AccrualLedgerAccounts;

extract($result);
?>
<script>

  const selected_option_value = 0
  const balances = {
    opening: 0,
    income: 0,
    expense: 0,
    closing: 0
  }

  const initial_outstanding_cheque_data = {
    transaction_date: '',
    cheque_number: 0,
    description: '',
    amount: 0
  }

  const initial_deposit_transit_data = {
    transaction_date: '',
    description: '',
    amount: 0
  }

  const initial_accrual_account_data = {
    accrual_account: '',
    accrual_effect: 0,
    amount: 0
  }

  const ACCRUAL_LEDGER_ACCOUNTS = ['receivables', 'payables', 'prepayments', 'depreciation', 'payroll_liability']


  $(document).ready(function () {
    bank_reconciliation_check()
    proof_of_cash_check()
  })

  $(document).on('change', ".project_ids", function () {
    const project_id = $(this).val()
    const url = '<?= base_url(); ?>ajax/system_opening_balance/getProjectIncomeAccounts/' + project_id
    let tr = $(this).closest('tr')
    let income_account_select = tr.find('.income_account_ids')
    let options = '<option value = ""><?= get_phrase('select_income_account'); ?></option>'

    $.get(url, function (income_accounts) {

      for (const [income_account_id, income_account_name] of Object.entries(income_accounts)) {
        options += `<option value = '${income_account_id}' ${selected_option_value == income_account_id ? 'selected' : ''} >${income_account_name}</option>`
      }
      income_account_select.html(options)
    })
  })

  function create_income_account_options(selected_option_value = 0) {
    let options = '<option value = ""><?= get_phrase('select_income_account'); ?></option>'
    const income_accounts = JSON.parse('<?= json_encode(isset($income_accounts) ? $income_accounts : []); ?>')

    for (const [income_account_id, income_account_name] of Object.entries(income_accounts)) {
      options += `<option value = '${income_account_id}' ${selected_option_value == income_account_id ? 'selected' : ''} >${income_account_name}</option>`
    }

    return options
  }

  function create_projects_options(selected_option_value = 0) {
    let options = '<option value = ""><?= get_phrase('select_a_project'); ?></option>'
    const office_projects = JSON.parse('<?= json_encode(isset($office_projects) ? $office_projects : []); ?>')

    for (const [project_id, project_name] of Object.entries(office_projects)) {
      options += `<option value = '${project_id}' ${selected_option_value == project_id ? 'selected' : ''} >${project_name}</option>`
    }

    return options
  }

  $('#fund_balance_table').find('tbody').append(fund_balance_row({ is_first_row: true, projects_options: create_projects_options(selected_option_value), income_account_options: create_income_account_options(selected_option_value), balances }));
  $('#outstanding_cheque_table').find('tbody').append(outstanding_cheque_row({ is_first_row: true, data: initial_outstanding_cheque_data }));
  $('#deposit_in_transit_table').find('tbody').append(deposit_transit_row({ is_first_row: true, data: initial_deposit_transit_data }));

  $('#insert_fund_balance').on('click', function () {
    const row = fund_balance_row({ is_first_row: false, projects_options: create_projects_options(), income_account_options: create_income_account_options(), balances })
    $('#fund_balance_table').find('tbody').append(row);
  })

  $('#insert_accrual_balance').on('click', function () {
    const accrual_balance_table = $("#accrual_balance_table").find('tbody')
    if (accrual_balance_table.children().length == '<?= count(AccrualLedgerAccounts::cases()) - 1; ?>') {
      $("#insert_accrual_balance").addClass('disabled')
    }
    const row = accrual_balance_row({ is_first_row: false, accrual_account_options: create_accrual_accounts_options(), accrual_ledger_options: create_accrual_ledger_options(), data: initial_accrual_account_data })
    $('#accrual_balance_table').find('tbody').append(row);
  })

  $(document).on('click', ".accrual_account_remove", function () {
    const insert_accrual_balance = $("#insert_accrual_balance")
    if (insert_accrual_balance.hasClass('disabled')) {
      insert_accrual_balance.removeClass('disabled')
    }
  })

  $(document).on('change', '.accrual_account_codes', function () {
    // const code = $(this)
    const accrual_account_codes = $(".accrual_account_codes")

    const selectedAccountsInRows = new Set();

    accrual_account_codes.each(function () {
      const selectedValue = $(this).val();
      if (selectedValue && selectedValue !== '') {
        selectedAccountsInRows.add(selectedValue);
      }
    });

    accrual_account_codes.each(function () {
      const $currentSelect = $(this);
      // Store the currently selected value before clearing options
      const previouslySelected = $currentSelect.val();

      $currentSelect.empty(); // Clear existing options
      $currentSelect.append('<option value="">-- <?= get_phrase('select_ledger_account'); ?> --</option>'); // Add default option

      // Add options based on ALL_STUDENTS, filtering out those already selected elsewhere
      ACCRUAL_LEDGER_ACCOUNTS.forEach(account => {
        // A account is available if:
        // - They are not selected in any other row OR
        // - They are the account currently selected in *this* particular dropdown
        if (!selectedAccountsInRows.has(account) || account === previouslySelected) {
          $currentSelect.append(`<option value="${account}">${account}</option>`);
        }
      });

      // Re-select the account that was previously chosen in this dropdown, if it's still an option.
      // This is crucial to maintain the user's selection after refresh.
      if (previouslySelected) {
        $currentSelect.val(previouslySelected);
      }
    });
  })

  $('#insert_outstanding_cheque').on('click', function () {
    const row = outstanding_cheque_row({ is_first_row: false, data: initial_outstanding_cheque_data })
    $('#outstanding_cheque_table').find('tbody').append(row);
  })

  $('#insert_deposit_in_transit').on('click', function () {
    const row = deposit_transit_row({ is_first_row: false, data: initial_deposit_transit_data })
    $('#deposit_in_transit_table').find('tbody').append(row);
  })

  function fund_balance_row(options) {

    const {
      is_first_row,
      projects_options,
      income_account_options,
      balances
    } = options

    const action_button = `<div class = 'btn btn-danger remove_row fund_balance_remove'>${'<?= get_phrase('remove_row'); ?>'}</div>`
    const project_select = `<select name = "project_ids[]" class = "form-control project_ids mandatory">${projects_options}</select>`
    const account_select = `<select name = "income_account_ids[]" class = "form-control income_account_ids mandatory">${income_account_options}</select>`
    const opening_input = `<input name = "opening_amounts[]" type = "number" class = "form-control opening fund_balance_amount mandatory" value = "${balances.opening}"  />`
    const income_input = `<input name = "income_amounts[]" type = "number" class = "form-control income fund_balance_amount mandatory" value = "${balances.income}"  />`
    const expense_input = `<input name = "expense_amounts[]" type = "number" class = "form-control expense fund_balance_amount mandatory" value = "${balances.expense}"  />`
    const closing_input = `<input onkeydown='return false;' name = "balance_amounts[]" type = "number" class = "form-control closing mandatory" value = "${balances.closing}"  />`

    const row = `
                  <tr><td>${is_first_row ? '' : action_button}</td>
                  <td>${project_select}</td>
                  <td>${account_select}</td>
                  <td>${opening_input}</td>
                  <td>${income_input}</td>
                  <td>${expense_input}</td>
                  <td>${closing_input}</td></tr>
                `

    return row
  }

  function accrual_balance_row(options) { //is_first_row = false

    const {
      is_first_row,
      accrual_account_options,
      accrual_ledger_options,
      data
    } = options

    const action_button = `<div class = 'btn btn-danger remove_row accrual_account_remove'>${'<?= get_phrase('remove_row'); ?>'}</div>`
    const accrual_account_code_select = `<select name = "accrual_account_codes[]" class = "form-control accrual_account_codes mandatory">${accrual_account_options}</select>`
    const accrual_ledger_effect_select = `<select name = "accrual_ledger_effect[]" class = "form-control accrual_ledger_effect mandatory">${accrual_ledger_options}</select>`
    const accrual_account_amount_input = `<input  value = "${data.amount}" name = "cheque_amount[]" type = "number" class = "form-control cheque_amount bank_reconciliation_fields outstanding_cheque mandatory" />`

    const row = `
                  <tr><td>${is_first_row ? '' : action_button}</td>
                  <td>${accrual_account_code_select}</td>
                  <td>${accrual_ledger_effect_select}</td>
                  <td>${accrual_account_amount_input}</td>
                `
    return row
  }

  function outstanding_cheque_row(options) { //is_first_row = false

    const {
      is_first_row,
      data
    } = options

    const action_button = `<div class = 'btn btn-danger remove_row outstanding_cheque_remove'>${'<?= get_phrase('remove_row'); ?>'}</div>`
    const transaction_date_input = `<input onkeydown='return false;' value = "${data.transaction_date}" name = "cheque_transaction_date[]" type = "text" data-format = 'yyyy-mm-dd' class = "form-control datepicker mandatory transaction_date outstanding_cheque"  />`
    const cheque_number_input = `<input  value = "${data.cheque_number}" name = "cheque_number[]" type = "number" class = "form-control cheque_number outstanding_cheque mandatory"  />`
    const cheque_description_input = `<input  value = "${data.description}" name = "cheque_description[]" type = "text" class = "form-control cheque_description outstanding_cheque mandatory"  />`
    const cheque_amount_input = `<input  value = "${data.amount}" name = "cheque_amount[]" type = "number" class = "form-control cheque_amount bank_reconciliation_fields outstanding_cheque mandatory" />`

    const row = `
                  <tr><td>${is_first_row ? '' : action_button}</td>
                  <td>${transaction_date_input}</td>
                  <td>${cheque_number_input}</td>
                  <td>${cheque_description_input}</td>
                  <td>${cheque_amount_input}</td>
                `

    return row
  }

  function deposit_transit_row(options) {

    const {
      is_first_row,
      data
    } = options

    const action_button = `<div class = 'btn btn-danger remove_row deposit_in_transit_remove'>${'<?= get_phrase('remove_row'); ?>'}</div>`
    const transaction_date_input = `<input onkeydown='return false;' value = "${data.transaction_date}" name = "deposit_transaction_date[]" type = "text" data-format = 'yyyy-mm-dd' class = "form-control datepicker transaction_date deposit_transit mandatory" onkeyup = 'return false;'  />`
    const transaction_description_input = `<input value = "${data.description}" name = "transaction_description[]" type = "text" class = "form-control transaction_description deposit_transit mandatory"  />`
    const transaction_amount_input = `<input value = "${data.amount}" name = "transaction_amount[]" type = "number" class = "form-control transaction_amount bank_reconciliation_fields deposit_transit mandatory"  />`

    const row = `
                <tr><td>${is_first_row ? '' : action_button}</td>
                <td>${transaction_date_input}</td>
                <td>${transaction_description_input}</td>
                <td>${transaction_amount_input}</td>
              `

    return row
  }

  function compute_total_fund_balance() {
    let total_fund_balance = 0

    $('.closing').each(function (index, elem) {
      if ($(elem).val() != "") {
        total_fund_balance += parseFloat($(elem).val())
      }
    })

    return total_fund_balance
  }

  function compute_total_outstanding_cheques() {
    let total_outstanding_cheques = 0

    $('.cheque_amount').each(function (index, elem) {
      if ($(elem).val() != "") {
        total_outstanding_cheques += parseFloat($(elem).val())
      }
    })

    return total_outstanding_cheques
  }

  function compute_total_deposit_in_transit() {
    let total_deposit_in_transit = 0

    $('.transaction_amount').each(function (index, elem) {
      if ($(elem).val() != "") {
        total_deposit_in_transit += parseFloat($(elem).val())
      }
    })

    return total_deposit_in_transit
  }

  $(document).on('keyup', '.fund_balance_amount', function () {
    const row = $(this).closest('tr')
    const opening = parseFloat(row.find('.opening').val())
    const income = parseFloat(row.find('.income').val())
    const expense = parseFloat(row.find('.expense').val())
    const compute_closing = (opening + income - expense).toFixed(2)
    const closing = row.find('.closing').val(compute_closing)
    const total_fund_balance = compute_total_fund_balance()

    $('#total_fund_balance').val(total_fund_balance.toFixed(2))
    proof_of_cash_check()
  })

  $(document).on('keyup', '.cheque_amount', function () {

    let total_cheque_amount = 0

    $('.cheque_amount').each(function (index, elem) {
      if ($(elem).val() != "") {
        total_cheque_amount += parseFloat($(elem).val())
      }
    })

    $('#total_outstanding_cheque').val(total_cheque_amount.toFixed(2))
    bank_reconciliation_check()
  })

  $(document).on('keyup', '.transaction_amount', function () {
    let total_transaction_amount = 0
    $('.transaction_amount').each(function (index, elem) {
      if ($(elem).val() != "") {
        total_transaction_amount += parseFloat($(elem).val())
      }
    })
    $('#total_deposit_in_transit').val(total_transaction_amount.toFixed(2))
    bank_reconciliation_check()
  })

  $('.proof_of_cash').on('keyup', function () {
    const proof_of_cash_elemnts = $('.proof_of_cash')
    let sum_cash = 0;

    proof_of_cash_elemnts.each(function (index, elem) {
      if ($(elem).val() != "") {
        sum_cash += parseFloat($(elem).val())
      }
    })

    $('#total_cash').val(sum_cash.toFixed(2))
    proof_of_cash_check()
  })

  function reset_office_bank_selection() {
    $("#office_bank_id").prop('selectedIndex', 0).val()
  }

  function reset_proof_of_cash() {
    $('.proof_of_cash, #total_cash').val(0);
    proof_of_cash_check()
  }

  $('#reset_cash_balance').on('click', function () {
    const cnf = confirm('<?= get_phrase('confirm_clearing_cash_balances', 'Are you sure you want to clear the cash and bank balance?'); ?>')

    if (!cnf) {
      alert('<?= get_phrase('proccess_aborted'); ?>')
      return false;
    }

    reset_proof_of_cash()
  })

  function reset_fund_balance() {
    let tbody = $('#fund_balance_table tbody');
    let rowsToRemove = tbody.find('tr:not(:first)');

    $('.fund_balance_amount, #total_fund_balance, .closing').val(0)
    rowsToRemove.remove();
    proof_of_cash_check()
  }

  $(document).on('click', '#reset_fund_balance', function () {

    const cnf = confirm('<?= get_phrase('confirm_row_delete', 'Are you sure you want to delete the rows in this section?'); ?>')

    if (!cnf) {
      alert('<?= get_phrase('proccess_aborted'); ?>')
      return false;
    }
    reset_fund_balance()
  })

  function reset_outstanding_cheque() {
    let tbody = $('#outstanding_cheque_table tbody');
    let rowsToRemove = tbody.find('tr:not(:first)');

    $('#total_outstanding_cheque, #less_outstanding_cheques, .cheque_amount').val(0)
    rowsToRemove.remove();
    bank_reconciliation_check()
  }

  $(document).on('click', '#reset_outstanding_cheque', function () {

    const cnf = confirm('<?= get_phrase('confirm_row_delete', 'Are you sure you want to delete the rows in this section?'); ?>')

    if (!cnf) {
      alert('<?= get_phrase('proccess_aborted'); ?>')
      return false;
    }

    reset_outstanding_cheque()
  })

  function reset_deposit_in_transit() {

    let tbody = $('#deposit_in_transit_table tbody');
    let rowsToRemove = tbody.find('tr:not(:first)');

    $('#total_deposit_in_transit, #add_deposit_in_transit, .transaction_amount').val(0)
    rowsToRemove.remove();
    bank_reconciliation_check()
  }

  $(document).on('click', '#reset_deposit_in_transit', function () {

    const cnf = confirm('<?= get_phrase('confirm_row_delete', 'Are you sure you want to delete the rows in this section?'); ?>')

    if (!cnf) {
      alert('<?= get_phrase('proccess_aborted'); ?>')
      return false;
    }

    reset_deposit_in_transit()
  })

  function removeErrorStyles() {
    $('#total_cash, #total_fund_balance').removeAttr('style')
    $('#book_bank_balance, #reconciled_statement_balance').removeAttr('style')
  }

  function compute_proof_of_cash_check() {
    const total_cash = $('#total_cash').val()
    const total_fund_balance = $('#total_fund_balance').val()

    return parseFloat(total_cash) == parseFloat(total_fund_balance) ? true : false;
  }

  function proof_of_cash_check() {

    const proof_of_cash_check = $('#proof_of_cash_check')

    if (compute_proof_of_cash_check()) {
      if (proof_of_cash_check.hasClass('label-danger')) {
        proof_of_cash_check.removeClass('label-danger')
        proof_of_cash_check.addClass('label-success')
        proof_of_cash_check.html('<?= get_phrase('proof_of_cash_correct'); ?>')
        $('#total_cash, #total_fund_balance').removeAttr('style')
      }
    } else {
      if (!proof_of_cash_check.hasClass('label-danger')) {
        proof_of_cash_check.removeClass('label-success')
        proof_of_cash_check.addClass('label-danger')
        proof_of_cash_check.html('<?= get_phrase('proof_of_cash_error'); ?>')
        $('#total_cash, #total_fund_balance').css('border', 'solid red 2px')
      }
    }
  }

  function compute_bank_reconciliation_check() {
    update_reconciled_bank_balance()
    const book_bank_balance = $('#book_bank_balance').val()
    const reconciled_statement_balance = $('#reconciled_statement_balance').val()
    const reconciliation_difference = parseFloat(book_bank_balance) == parseFloat(reconciled_statement_balance) ? true : false;
    // console.log(reconciliation_difference, book_bank_balance, reconciled_statement_balance)
    return reconciliation_difference;
  }

  function bank_reconciled_difference() {
    const book_bank_balance = $('#book_bank_balance').val()
    const reconciled_statement_balance = $('#reconciled_statement_balance').val()

    return parseFloat(book_bank_balance) - parseFloat(reconciled_statement_balance)
  }

  function bank_reconciliation_check() {

    const reconciliation_error_check = $('#reconciliation_error')

    $('#bank_reconciled_difference').val(bank_reconciled_difference())

    if (compute_bank_reconciliation_check()) {
      if (reconciliation_error_check.hasClass('label-danger')) {
        reconciliation_error_check.removeClass('label-danger')
        reconciliation_error_check.addClass('label-success')
        reconciliation_error_check.html('<?= get_phrase('bank_reconciliation_correct'); ?>')
        $('#book_bank_balance, #reconciled_statement_balance').removeAttr('style')
      }
    } else {
      if (!reconciliation_error_check.hasClass('label-danger')) {
        reconciliation_error_check.removeClass('label-success')
        reconciliation_error_check.addClass('label-danger')
        reconciliation_error_check.html('<?= get_phrase('proof_of_cash_error'); ?>')
        $('#book_bank_balance, #reconciled_statement_balance').css('border', 'solid red 2px')
      }
    }
  }

  //fund_balance_amount, .proof_of_cash, #statement_balance, .cheque_amount,.cheque_number
  $(document).on('click', 'input', function () {
    if ($(this).val() == 0) {
      $(this).val('')
    }
  })

  $(document).on('click', '.remove_row', function () {
    const cnf = confirm('<?= get_phrase('confirm_opening_balance_row_remove', 'Are you sure you want to remove this row?'); ?>')

    if (!cnf) {
      alert('<?= get_phrase('proccess_aborted'); ?>')
      return false;
    }

    $(this).closest('tr').remove()

    if ($(this).hasClass('fund_balance_remove')) {
      $('#total_fund_balance').val(compute_total_fund_balance().toFixed(2))
    }

    if ($(this).hasClass('outstanding_cheque_remove')) {
      $("#total_outstanding_cheque").val(compute_total_outstanding_cheques().toFixed(2))
    }

    if ($(this).hasClass('deposit_in_transit_remove')) {
      $("#total_deposit_in_transit").val(compute_total_deposit_in_transit())
    }

    update_reconciled_bank_balance()
    bank_reconciliation_check()
    // compute_bank_reconciliation_check()
  })

  function compute_reconciled_bank_balance() {
    const book_bank_balance = $('#book_bank_balance').val() != "" ? $('#book_bank_balance').val() : 0
    const total_outstanding_cheque = $('#total_outstanding_cheque').val() != "" ? $('#total_outstanding_cheque').val() : 0
    const total_deposit_in_transit = $('#total_deposit_in_transit').val() != "" ? $('#total_deposit_in_transit').val() : 0
    const statement_balance = $('#statement_balance').val() != "" ? $('#statement_balance').val() : 0

    const reconciled_statement_balance = parseFloat(statement_balance) + parseFloat(total_deposit_in_transit) - parseFloat(total_outstanding_cheque);

    return reconciled_statement_balance
  }

  function update_reconciled_bank_balance() {
    const reconciled_statement_balance = compute_reconciled_bank_balance()

    $('#reconciled_statement_balance').val(reconciled_statement_balance.toFixed(2));
  }


  $(document).on('keyup', '.bank_reconciliation_fields', function () {
    update_reconciled_bank_balance()
    bank_reconciliation_check()
  })

  function reset_reconciliation_statement() {
    $('.reconciliation_statement').val('')
  }

  function clearTextInputs() {
    $('input[type=text]').val('')
    $('input[type=number]').val(0)
  }

  function reset_balance_form() {
    reset_proof_of_cash()
    reset_fund_balance()
    reset_outstanding_cheque()
    reset_deposit_in_transit()
    reset_reconciliation_statement()
    removeErrorStyles()
    clearTextInputs()
  }

  $(document).on('click', '#reset', function () {
    reset_office_bank_selection()
    reset_balance_form()
  })


  $(document).on('click', '.save', function () {

    const total_cash = $('#total_cash').val()
    const proof_of_cash_check = compute_proof_of_cash_check()
    const bank_reconciliation_check = compute_bank_reconciliation_check()
    // const post_data = $('#frm_system_opening_balance').serializeArray();
    const url = '<?= base_url(); ?>ajax/system_opening_balance/saveOpeningBalances/<?= hash_id($id, 'decode') ?>'
    const btn = $(this)

    let mandatory_fields = $('.mandatory')
    let count_empty_fields = 0;
    $.each(mandatory_fields, function (index, elem) {
      if ($(elem).val() == "") {
        ++count_empty_fields
        $(elem).css('border', '1px solid red');
      }
    });

    if (count_empty_fields > 0) {
      alert('<?= get_phrase('empty_fields', "You have empty fields that are required") ?>');
      return false;
    }

    if (total_cash != 0 && proof_of_cash_check && bank_reconciliation_check) {
      let form_data = new FormData($('form')[0]);
      $.ajax({
        type: 'POST',
        url: url,
        data: form_data,
        processData: false,
        contentType: false,
        beforeSend: function () {
          $("#overlay").css("display", "block");
        },
        success: function (responseObj) {
          alert('Opening Financial Report Saved successfully');
          if (btn.hasClass('save_exit')) {
            location.href = document.referrer;
          } else {
            $('#list_statements').html(responseObj.bank_statements_uploads)
          }

          $("#overlay").css("display", "none");
        }
      });
      // $.post(url, post_data, function (response) {
      //   if(response){
      //     alert('Opening Financial Report Saved successfully');
      //     if(btn.hasClass('save_exit')){
      //       // alert('Opening Financial Report Saved successfully');
      //       location.href = document.referrer;
      //     }
      //   }else{
      //     alert('Error in saving')
      //   }
      // })
    } else {
      $message = 'Opening Balanced are not reconciling. Please check on on the following areas: \n'

      if (total_cash == 0) {
        $message += " => The total cash balance MUST be a value not equal to zero. See validation (B) \n"
      }

      if (!proof_of_cash_check) {
        $message += " => The total cash (B) and and total fund balance (C) MUST be equal. See validation (D) \n"
      }

      if (!bank_reconciliation_check) {
        $message += " => The book bank balance (A) and the reconciled statement balance (G) MUST be equal. See validation (J) \n"
      }

      alert($message)

      // alert($('#book_bank_balance').val())

      if ($(this).hasClass('save_continue')) {
        $.post(url, post_data, function (response) {

        })
      }

    }
  })

  $(document).on('click', '.datepicker', function () {
    $(this).datepicker(
      {
        'format': 'yyyy-mm-dd'
      }
    )
  })

  // $('.delete_statement').on('click', function (){
  //   alert('Hello')
  // })

  function delete_statement(attachment_id) {
    const url = '<?= base_url(); ?>system_opening_balance/delete_statement/'
    const system_opening_balance_id = "<?= hash_id($id, 'decode'); ?>"
    const data = {
      attachment_id,
      system_opening_balance_id
    }

    $.ajax({
      type: 'POST',
      url: url,
      data: data,
      success: function (responseObj) {
        $('#list_statements').html(responseObj.bank_statements_uploads)
      }
    })
  }

  $('#upload_statement').on('change', function () {
    var file = this.files[0];
    var maxSize = 2 * 1024 * 1024; // 2 MB
    var allowedType = 'application/pdf'; // MIME type for PDF files

    if (file) {
      let message = '';
      if (file.size > maxSize) {
        message += 'File size exceeds 2 MB. Please choose a smaller file. \n';
        // $(this).val(''); // Clear the input field
      }

      if (file.type !== allowedType) {
        message += 'Invalid file type. Please upload a PDF file.\n'
        // $(this).val(''); // Clear the input field
      }

      if (message != "") {
        $('#error').text(message);
      } else {
        $('#error').text(''); // Clear the error message
      }

    }
    // var file = this.files[0];
    // var maxSize = 2 * 1024 * 1024; // 2 MB

    // if (file.size > maxSize) {
    //     $('#error').text('File size exceeds 2 MB. Please choose a smaller file.');
    //     $(this).val(''); // Clear the input field
    // } 
    // else {
    //     $('#error').text(''); // Clear the error message
    // }
  });

  function create_accrual_accounts_options($selected_options = 0) {
    let options = '<option value = "">--- <?= get_phrase('select_ledger_account'); ?> ---</option>'
    const accrual_accounts = ['receivables', 'payables', 'prepayments', 'depreciation', 'payroll_liability'];
    const accrual_account_codes = $('.accrual_account_codes')


    for (let i = 0; i < accrual_accounts.length; i++) {
      let breakEqual = false
      for (let j = 0; j < accrual_account_codes.length; j++) {
        if (accrual_accounts[i] == $(accrual_account_codes[j]).val()) {
          breakEqual = true
          break
        }
      }

      if (breakEqual) continue

      options += `<option value = '${accrual_accounts[i]}' ${selected_option_value == accrual_accounts[i] ? 'selected' : ''} >${accrual_accounts[i]}</option>`
    }

    return options
  }

  function create_accrual_ledger_options(selected_option_value = 0) {
    let options = '<option value = ""><?= get_phrase('select_ledger_effect'); ?></option>'
    const accrual_ledgers = ['debit', 'credit'];

    for (let i = 0; i < accrual_ledgers.length; i++) {
      options += `<option value = '${accrual_ledgers[i]}' ${selected_option_value == accrual_ledgers[i] ? 'selected' : ''} >${accrual_ledgers[i]}</option>`
    }

    return options
  }

  $(document).on('change', '.accrual_account_codes', function () {
    const accrual_account_codes = $(this)
    const closest_accrual_ledger_effect = accrual_account_codes.closest('tr').find('.accrual_ledger_effect');
    let default_accrual_effect = 'credit';
    const accrual_account_codes_name = accrual_account_codes.val();

    if (accrual_account_codes_name == 'receivables' || accrual_account_codes_name == 'prepayments') {
      default_accrual_effect = 'debit';
    }

    closest_accrual_ledger_effect.val(default_accrual_effect)
  })

  $(document).on('change', '.accrual_ledger_effect', function (ev) {
    const cnf = confirm('<?= get_phrase('alert_change_accrual_effect', 'Are you sure you want to change the default effect'); ?>');
    const accrual_account_codes_name = $(this).closest('tr').find('.accrual_account_codes').val()
    let default_accrual_effect = 'credit'
    const selected_option = $(this).val()

    if (accrual_account_codes_name == 'receivables' || accrual_account_codes_name == 'prepayments') {
      default_accrual_effect = 'debit';
    }

    if (!cnf && selected_option != default_accrual_effect) {
      alert('<?= get_phrase('process_aborted'); ?>')
      $(this).val(default_accrual_effect)
    }

    if (!cnf && selected_option == default_accrual_effect) {
      alert('<?= get_phrase('process_aborted'); ?>')
      $(this).val(selected_option == 'credit' ? 'debit' : 'credit')
    }

  });
</script>