<div class = "form-group">
    <div class="col-xs-12">
        <select id="office_bank_id" class = "form-control">
            <option value=""><?=get_phrase('select_bank_account');?></option>
            <?php foreach($activeOfficeBanks as $activeOfficeBank){?>
                <option value="<?=$activeOfficeBank['office_bank_id'];?>"><?=$activeOfficeBank['office_bank_name'];?></option>
            <?php } ?>
        </select>
    </div>
</div>