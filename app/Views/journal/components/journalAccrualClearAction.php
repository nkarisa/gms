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
    //   document.getElementById('showPromptButton').addEventListener('click', async () => {
    //     const result = await bootstrapPrompt('What is your name?', 'Guest');
    //     if (result !== null) {
    //         alert('You entered: ' + result);
    //     } else {
    //         alert('Prompt was cancelled.');
    //     }
    //     });

    $(".clear_accrual").on('click', function(ev){
        const voucherId = $(this).data('voucher_id')
        const url = "<?=base_url();?>ajax/journal/clearAccrualTransaction";
        let data = {
            voucherId,
            bankRef: ''
        }

        if(voucherId == '<?=$voucherId;?>'){

            let cnf = confirm('<?=get_phrase('verify_user_action','Are you sure you want to peform this action?');?>')

            if(!cnf){
                alert('<?=get_phrase('action_aborted');?>')
                return false;
            }

            $.post(url, data, function(response){
                if(response.requireBankRef){
                    let bankRef = getUserInput('<?=get_phrase('bank_reference_required');?>') // A more advanced interface is required here
                    data = {
                        voucherId,
                        bankRef
                    }
                    $.post(url, data, function(response){
                        if(response.message == ""){
                            alert('Please provide all transaction requirements')
                            return false;
                        }
                        if(response.success){
                            alert(response.message)
                            window.location.replace('<?=base_url('voucher/list');?>');
                        }else{
                            alert(response.message)
                        }
                    })
                }else{
                    if(response.success){
                        alert(response.message)
                        window.location.replace('<?=base_url('voucher/list');?>');
                    }else{
                        alert(response.message)
                    }
                }
            })
        }
        ev.preventDefault()
    })
</script>
