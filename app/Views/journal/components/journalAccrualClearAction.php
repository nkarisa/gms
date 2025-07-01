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
            accrualClearingEffect: '<?= $accrualClearingEffect; ?>',
            officeId: '<?=$officeId;?>', 
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
</script>


<script>
    function getUserInput(message, data) {
        jQuery('#bankDetails').modal({
            backdrop: false
        });

        const url = "<?= base_url(); ?>ajax/journal/getBankAndRefViews"

        $.post(url, data, function (modalBodyContents) {
            const voucherIdInput = "<input class = 'hidden' id = 'voucherId' value = '" + modalBodyContents.voucherId + "'  />"
            const accrualClearingEffect = "<input class = 'hidden' id = 'accrualClearingEffect' value = '" + modalBodyContents.accrualClearingEffect + "'  />" 
            const officeId = "<input class = 'hidden' id = 'officeId' value = '" + modalBodyContents.officeId + "'  />" 
            jQuery('#bankDetails .modal-body #form').html(voucherIdInput + accrualClearingEffect + officeId + modalBodyContents.view);
        })
    }

</script>

<style>
    /* Custom CSS for centering the modal */
    .modal {
        text-align: center;
        /* Horizontally center inline-block elements */
        padding: 0 !important;
        /* Remove default padding that might affect centering */
    }

    .modal:before {
        content: '';
        display: inline-block;
        height: 100%;
        vertical-align: middle;
        margin-right: -4px;
        /* Adjust for spacing issues with inline-block */
    }

    .modal-dialog {
        display: inline-block;
        text-align: left;
        /* Reset text alignment for modal content */
        vertical-align: middle;
    }

    /* Optional: Add some content to make the page scrollable for testing */
    body {
        min-height: 150vh;
        background-color: #f0f0f0;
    }
</style>

<div id="bankDetails" class="modal fade" role="dialog">
    <div class="modal-dialog">

        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><?= get_phrase('banking_details'); ?></h4>
            </div>
            <div class="modal-body">
                <?php
                echo form_open("", array(
                    'id' => 'form',
                    'class' => 'form-horizontal form-groups-bordered validate',
                    'enctype' => 'multipart/form-data'
                ));
                ?>
                Loading ...
                </form>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button id = "post_entry" type="button" class="btn btn-primary" disabled><?=get_phrase('post_entry');?></button>
            </div>
        </div>

    </div>
</div>


<script>
    $(document).ready(function () {
        // Function to center the modal
        function centerModal() {
            $(this).find('.modal-dialog').css({
                'margin-top': function () {
                    var modalHeight = $(this).outerHeight();
                    var windowHeight = $(window).height();
                    // Use Math.max to ensure margin-top is not negative
                    return Math.max(0, (windowHeight - modalHeight) / 2);
                },
                'margin-left': function () {
                    var modalWidth = $(this).outerWidth();
                    var windowWidth = $(window).width();
                    // Use Math.max to ensure margin-left is not negative
                    return Math.max(0, (windowWidth - modalWidth) / 2);
                }
            });
        }

        // Apply the centering function when the modal is shown
        $('.modal').on('show.bs.modal', centerModal);

        // Re-center on window resize if modal is already open
        $(window).on('resize', function () {
            $('.modal:visible').each(centerModal);
        });
    });

    $(document).on('change',"#office_bank_id", function(){
        const post_entry = $("#post_entry")
        const office_bank_id_input = $("#office_bank_id")
        const office_bank_id = office_bank_id_input.val()
        const bankRef = $("#bankRef")
        const url = "<?=base_url();?>ajax/journal/getOfficeBankRefByOfficeBank"
        const data = {
            office_bank_id
        }

        if(office_bank_id > 0){
            $.post(url, data, function(response){
                post_entry.removeAttr('disabled')
                bankRef.removeAttr('disabled')
                bankRef.children().remove();
                if(response.isBankReferenced){
                    console.log(response)
                    let opts = '<option value = ""><?=get_phrase('select_bank_reference');?></option>';
                     $.each(response.options, function(i, el){
                         opts += '<option value = "' + el.cheque_id + '">' + el.cheque_number + '</option>'
                     })

                    bankRef.append(opts)
                }
            })
        }else{
            post_entry.prop('disabled', true)
            bankRef.prop('disabled', true)
        }
    })

    $(document).on('change','#partial_clearance', function(){
        const voucherId = $("#voucherId").val()
        const partial_fields = $("#partial_fields")
        const partial_clearance_option = $(this).val()
        
        if(voucherId == '<?= $voucherId; ?>'){
            partial_fields.addClass('hidden')
            if(partial_clearance_option > 0){
                partial_fields.removeClass('hidden')
            }
        }
    })

    $(document).on('keyup',".clearing_amount", function(){
        const voucherId = $(".clear_accrual").data('voucher_id')
        const amountToClear = $(this).val()
        const unclearedAmount = removeCurrencySeparator($(this).closest('td').siblings('.uncleared_amount').html())

        if(voucherId == '<?= $voucherId; ?>'){
            const balanceAfterClear = parseFloat(unclearedAmount) - parseFloat(amountToClear);
            if(balanceAfterClear < 0){
                alert('<?=get_phrase('acrual_clearing_limit_error','You have exceeded the amount that is allowed to be cleared');?>');
                $(this).val(0)
            }
        }
        
    })

    function removeCurrencySeparator(numberString) {
        return numberString.replace(/,/g, '');
    }

   $('#post_entry').on('click', function(){
        const voucherId = $(".clear_accrual").data('voucher_id')
        const data = {
            voucherId: $("#voucherId").val(),
            accrualClearingEffect: $("#accrualClearingEffect").val(),
            office_bank_id: $("#office_bank_id").val(),
            bankRef: $('#bankRef').val()
        }
        const url = "<?=base_url();?>ajax/journal/clearAccrualTransaction"

        if(voucherId == '<?= $voucherId; ?>'){
            $.post(url, data, function (){
                alert('Posted successful')
            })
        }
        
        
        $('#bankDetails').modal('hide');
    })
</script>