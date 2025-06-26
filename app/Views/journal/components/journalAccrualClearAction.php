<?php
use App\Enums\AccrualVoucherTypeEffects;

if ($hasVoucherCreatePermission) {
    $showBankAccounts = false;
    $showBankReferences = false;

    if ($accrualClearingEffect == AccrualVoucherTypeEffects::RECEIVABLES_PAYMENTS->value) {
        $showBankAccounts = true;
    } elseif ($accrualClearingEffect == AccrualVoucherTypeEffects::PAYABLE_DISBURSEMENTS->value) {
        $showBankAccounts = true;
        $showBankReferences = true;
    }
    ?>
        <div 
            data-voucher_id = "<?= $voucherId; ?>" 
            class = "btn btn-success clear_accrual"
            data-toggle="modal"
        >
            <?= get_phrase('clear_accrual'); ?>
        </div>
<?php } else { ?>
        <div class = "btn btn-info disabled">
            <?= get_phrase('clear_accrual'); ?>
        </div>
<?php } ?>

<script>
    $(".clear_accrual").on('click', function(){
        const voucherId = $(this).data('voucher_id')
        const data = {
            voucherId: '<?= $voucherId ?>',
            accrualClearingEffect: '<?= $accrualClearingEffect; ?>'
        }
        const showBankAccounts = '<?= $showBankAccounts; ?>';
        const showBankReferences = '<?= $showBankReferences; ?>';
        
        if(voucherId == '<?= $voucherId; ?>' && (showBankAccounts || showBankReferences)){
            getUserInput('<?= get_phrase('verify_user_action', 'Are you sure you want to peform this action?'); ?>', data);
        }else if(voucherId == '<?= $voucherId; ?>' && showBankAccounts == "" && showBankReferences == ""){
            $.post(url, data, function(response){
                if(response.message == ""){
                    alert('Please provide all transaction requirements')
                    return false;
                }
                    if(response.success){
                        alert(response.message)
                    }else{
                        alert(response.message)
                    }
            })
        }
    })

    // $(".clear_accrual").on('click', function(ev){
    //     const voucherId = $(this).data('voucher_id')
    //     const url = "<?= base_url(); ?>ajax/journal/clearAccrualTransaction";
    //     let data = {
    //         voucherId,
    //         bankRef: ''
    //     }

    //     if(voucherId == '<?= $voucherId; ?>'){

    //         let cnf = confirm('<?= get_phrase('verify_user_action', 'Are you sure you want to peform this action?'); ?>')

    //         if(!cnf){
    //             alert('<?= get_phrase('action_aborted'); ?>')
    //             return false;
    //         }

    //         $.post(url, data, function(response){
    //             if(response.requireBankRef){
    //                 let bankRef = prompt('<?= get_phrase('bank_reference_required'); ?>') 
    //                 // getUserInput('<?= get_phrase('bank_reference_required'); ?>') // A more advanced interface is required here
                    
    //                 data = {
    //                     voucherId,
    //                     bankRef: bankRef
    //                 }
    //                 $.post(url, data, function(response){
    //                     if(response.message == ""){
    //                         alert('Please provide all transaction requirements')
    //                         return false;
    //                     }
    //                     if(response.success){
    //                         alert(response.message)
    //                         window.location.replace('<?= base_url('voucher/list'); ?>');
    //                     }else{
    //                         alert(response.message)
    //                     }
    //                 })

    //             }else{
    //                 if(response.success){
    //                     alert(response.message)
    //                     window.location.replace('<?= base_url('voucher/list'); ?>');
    //                 }else{
    //                     alert(response.message)
    //                 }
    //             }
    //         })
    //     }
    //     ev.preventDefault()
    // })
</script>
