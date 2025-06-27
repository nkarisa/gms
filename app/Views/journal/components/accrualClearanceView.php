<?php

use App\Enums\AccrualVoucherTypeEffects;

if (in_array($accrualClearingEffect,
    [
                AccrualVoucherTypeEffects::RECEIVABLES_PAYMENTS->value,
                AccrualVoucherTypeEffects::PAYABLE_DISBURSEMENTS->value
            ]
        )
    ) {
    ?>
    <div class="form-group">
        <div class="col-xs-12">
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
    ?>
    <div class="col-xs-12"></div>
    <?php
    if ($isBankReferenced) {
        ?>
        <select class="form-control" id="bankRef" disabled>
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
        <input class="form-control" id="bankRef" placeholder="<?= get_phrase('eft_reference'); ?>" disabled />
        <?php
    }
    ?>
    </div>
<?php } ?>