<?php if($hasVoucherCreatePermission) {?>
    <div data-voucher_id = "<?=$voucherId;?>" class = "btn btn-success clear_accrual">
        <?=get_phrase('clear_accrual');?>
    </div>
<?php } else {?>
    <div class = "btn btn-info disabled">
        <?=get_phrase('clear_accrual');?>
    </div>
<?php }?>

<script>
    $(".clear_accrual").on('click', function(ev){
        const voucherId = $(this).data('voucher_id')
        alert(voucherId)
        ev.preventDefault()
    })
</script>
