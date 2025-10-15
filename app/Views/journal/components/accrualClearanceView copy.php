<?php

use App\Enums\AccrualVoucherTypeEffects;

if (
    in_array(
        $accrualClearingEffect,
        [
            AccrualVoucherTypeEffects::RECEIVABLES_PAYMENTS->value,
            AccrualVoucherTypeEffects::PAYABLE_DISBURSEMENTS->value
        ]
    )
) {
    ?>
    <div class="form-group">
        <label class="col-xs-4 control-label"><?= get_phrase('bank_account'); ?></label>
        <div class="col-xs-8">
            <select id="office_bank_id" class="form-control">
                <option value=""><?= get_phrase('select_bank_account'); ?></option>
                <?php foreach ($activeOfficeBanks as $activeOfficeBank) { ?>
                    <option value="<?= $activeOfficeBank['office_bank_id']; ?>"><?= $activeOfficeBank['office_bank_name']; ?>
                    </option>
                <?php } ?>
            </select>
        </div>
    </div>

    <?php
}

if ($accrualClearingEffect == AccrualVoucherTypeEffects::PAYABLE_DISBURSEMENTS->value) {

    echo form_open("", array('id' => 'frm_clear_accrual', 'class' => 'form-horizontal form-groups-bordered validate', 'enctype' => 'multipart/form-data'));

    ?>
    <div class="form-group">
        <label class="col-xs-4 control-label"><?= get_phrase('bank_reference'); ?></label>
        <div class="col-xs-8">
            <?php
            if ($isBankReferenced) {
                ?>
                <select class="form-control"  id="bankRef" disabled>
                    <option><?= get_phrase('select_bank_reference'); ?></option>
                    <?php
                    foreach ($validChequeNumbers as $validChequeNumber) {
                        ?>
                        <option value="<?= $validChequeNumber; ?>"><?= $validChequeNumber; ?></option>
                        <?php
                    }
                    ?>
                </select>
                <?php
            } else {
                ?>
                <input class="form-control" id="bankRef" placeholder="<?= get_phrase('eft_reference'); ?>"
                    disabled />
                <?php
            }
            ?>
        </div>
    </div>
<?php } ?>

<div class="form-group">
    <label class="col-xs-4 control-label"><?= get_phrase('partial_clearance'); ?></label>
    <div class="col-xs-8">
        <select  id="partial_clearance" class="form-control">
            <option value=""><?= get_phrase('select_clearance_option'); ?></option>
            <option value="0"><?= get_phrase('no'); ?></option>
            <option value="1"><?= get_phrase('yes'); ?></option>
        </select>
    </div>
</div>

<div class="form-group hidden" id="partial_fields">
    <table class="table table-stripped table-bordered">
        <thead>
            <tr>
                <th><?= get_phrase('account'); ?></th>
                <th><?= get_phrase('original_amount'); ?></th>
                <th><?= get_phrase('uncleared_amount'); ?></th>
                <th><?= get_phrase('clear_amount'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php 
                foreach ($voucher['body'] as $voucherDetail) { 
            ?>
                <tr>
                    <td><?= $voucherDetail['account_code']; ?></td>
                    <td class='original_amount'><?= number_format($voucherDetail['totalcost'], 2); ?></td>
                    <td class='uncleared_amount'>
                        <?= number_format(($voucherDetail['totalcost'] - $voucherDetail['cleared_amount']), 2); ?></td>
                    <td><input name="clearing_amount[]" type="text" value="0" class="form-control clearing_amount" /></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>
</form>

<script>
    $(document).on('click','#post_entry', function(){
        const voucherId = $(".clear_accrual").data('voucher_id')
        const formData = $('#frm_clear_accrual').serializeArray()
        const data = {
            voucherId: $("#voucherId").val(),
            accrualClearingEffect: $("#accrualClearingEffect").val(),
            office_bank_id: $("#office_bank_id").val(),
            bankRef: $('#bankRef').val(),
            formData //: $.param(formData, true)
        }
        const url = "<?=base_url();?>ajax/journal/clearAccrualTransaction"

        if(voucherId == '<?= $voucherId; ?>'){
            
            // $.post(url, data, function (){
            //     alert('Posted successful')
            // })

            $.ajax({
                url,
                type: "POST",
                data,
                success: function(){
                    alert('Posted successful')
                }
            })
        }
        
        
        $('#bankDetails').modal('hide');
    })
</script>