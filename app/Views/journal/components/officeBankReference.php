<div class = "col-xs-12"></div>
    <?php 
        if($disbursementVoucherTypeIsBankReferenced){
    ?>
        <select class = "form-control" id="bankRef" disabled>
            <option><?=get_phrase('select_bank_reference');?></option>
            <?php 
                foreach($validChequeNumbers as $validChequeNumber){
            ?>
                <option value = "<?=$validChequeNumber;?>"><?=$validChequeNumber;?></option>
            <?php 
                }
            ?>
        </select>
    <?php
        }else{
    ?>
        <input class="form-control" id="bankRef" placeholder="<?=get_phrase('eft_reference');?>" disabled />
    <?php
        }
    ?>
    
</div>