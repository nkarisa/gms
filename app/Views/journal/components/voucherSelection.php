<input type='checkbox' name='selected_voucher[]' class='select_voucher' value='<?= $voucher_id; ?>' /> 
<span 
    title="<?= $voucher_type_name; ?>"
    class="label <?= $cleared ? 'btn-success' : 'btn-warning'; ?>"
>
    <?= service("settings")->get("GrantsConfig.use_voucher_type_abbreviation") ? $voucher_type_abbrev : $voucher_type_name; ?>
<span>