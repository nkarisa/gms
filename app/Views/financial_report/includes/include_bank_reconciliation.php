<table class="table table-striped">
    <thead>
    </thead>
    <tbody>
        <tr>
            <td><?= get_phrase('bank_statement_date'); ?></td>
            <td>
                <?php if ($multiple_offices_report) { ?>
                    <?= $bank_reconciliation['bank_statement_date']; ?>
                <?php } else { ?>
                    <input type="text" class="form-control datepicker" id="bank_statement_date" data-format='yyyy-mm-dd' readonly='readonly' value="<?= $bank_reconciliation['bank_statement_date']; ?>" />
                <?php } ?>

            </td>
        </tr>
        <tr>
            <td><?= get_phrase('bank_statement_closing_balance'); ?></td>
            <td id='td_bank_reconciliation_balance'>
                <?php if (!$allow_mfr_reconciliation || !$this->user_model->check_role_has_permissions(ucfirst($this->controller), 'update')) { ?>
                    <?= number_format($bank_reconciliation['bank_statement_balance'], 2); ?> <i class='fa fa-info-circle' style='color:red;' title='<?= get_phrase('choose_one_bank_account_to_reconcile'); ?>'></i>
                <?php } else { ?>
                    <input type="number" class="form-control" id="bank_statement_balance" value="<?= $bank_reconciliation['bank_statement_balance']; ?>" />
                <?php } ?>
            </td>
        </tr>

        <tr>
            <td><?= get_phrase('add'); ?> : <?= get_phrase('deposit_in_transit'); ?></td>
            <td class='total_dt' id='td_deposit_in_transit'><?= number_format($bank_reconciliation['month_transit_deposit'], 2); ?></td>
        </tr>

        <tr>
            <td><?= get_phrase('less'); ?> : <?= get_phrase('oustanding_cheques'); ?></td>
            <td class='total_oc' id='td_oustanding_cheques'><?= number_format($bank_reconciliation['month_outstanding_cheques'], 2); ?></td>
        </tr>

        <tr>
            <td><?= get_phrase('reconciled_bank_balance'); ?></td>
            <td>
                <span class='bank_reconcile' id='reconciled_bank_balance'><?= number_format($bank_reconciliation['bank_reconciled_balance'], 2); ?></span>
                <span id='reconciliation_flag' class="label label-<?= $bank_reconciliation['is_book_reconciled'] ? 'success' : 'danger'; ?>"><?= get_phrase($bank_reconciliation['is_book_reconciled'] ? 'balanced' : 'not_balanced'); ?></span>
            </td>
        </tr>

        <tr>
            <td><?= get_phrase('book_closing_balance'); ?></td>
            <td class='bank_reconcile' id='td_book_closing_balance'><?= number_format($bank_reconciliation['book_closing_balance'], 2); ?></td>
        </tr>

    </tbody>
</table>

<script>
    $("#bank_statement_balance").on('keyup', function() {
        var bank_statement_balance = $(this).val();
        var td_deposit_in_transit = $("#td_deposit_in_transit").html().split(',').join("");
        var td_oustanding_cheques = $("#td_oustanding_cheques").html().split(',').join("");
        var td_book_closing_balance = $("#td_book_closing_balance").html().split(',').join("");

        var reconciled_bank_balance_amount = parseFloat(bank_statement_balance)+parseFloat(td_deposit_in_transit)-parseFloat(td_oustanding_cheques);

        $("#reconciled_bank_balance").html(accounting.formatNumber(reconciled_bank_balance_amount, 2));
        $("#td_book_closing_balance").html(accounting.formatNumber(td_book_closing_balance, 2))

        if (parseFloat(td_book_closing_balance).toFixed(2) == parseFloat(reconciled_bank_balance_amount).toFixed(2)) {
            if ($("#reconciliation_flag").hasClass('label-danger')) {
                $("#reconciliation_flag").removeClass('label-danger');
                $("#reconciliation_flag").addClass('label-success');
            }

            $("#reconciliation_flag").html('<?= get_phrase('balanced'); ?>');
        } else {

            
            if ($("#reconciliation_flag").hasClass('label-success')) {
                $("#reconciliation_flag").removeClass('label-success');
                $("#reconciliation_flag").addClass('label-danger');
            }

            $("#reconciliation_flag").html('<?= get_phrase('not_balanced'); ?>');
        }

    });

    $("#bank_statement_balance").on('change', function() {
        var url = "<?= base_url(); ?>financial_report/update_bank_reconciliation_balance";
        var office_ids = Array.isArray($('#office_ids').val()) ? $('#office_ids').val() : [$('#office_ids').val()];
        var project_ids = $('#project_ids').val();
        var office_bank_ids = Array.isArray($('#office_bank_ids').val() ) ? $('#office_bank_ids').val()  : [$('#office_bank_ids').val()];
         
        // alert(office_bank_ids);

        var data = {
            'reporting_month': '<?= $reporting_month; ?>',
            'office_ids': office_ids,
            'project_ids': project_ids,
            'office_bank_ids': office_bank_ids,
            'balance': $(this).val()
        };

        console.log(data);

        $.post(url, data, function(response) {
          
            alert(response);

            
        });

    });
</script>