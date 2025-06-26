<script>
    function getUserInput(message, data) {
        jQuery('#bankDetails').modal({
            backdrop: false
        });

        const url = "<?= base_url(); ?>ajax/journal/getBankAndRefViews"

        $.post(url, data, function (modalBodyContents) {
            const voucherIdInput = "<input class = 'hidden' id = 'voucherId' value = '" + modalBodyContents.voucherId + "'  />"
            const accrualClearingEffect = "<input class = 'hidden' id = 'accrualClearingEffect' value = '" + modalBodyContents.accrualClearingEffect + "'  />" 
            jQuery('#bankDetails .modal-body #form').html(voucherIdInput + accrualClearingEffect + modalBodyContents.view);
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

    $('#post_entry').on('click', function(){
        const data = {
            voucherId: $("#voucherId").val(),
            accrualClearingEffect: $("#accrualClearingEffect").val(),
            office_bank_id: $("#office_bank_id").val(),
            bankRef: $('#bankRef').val()
        }

        console.log(data)

        $('#bankDetails').modal('hide');
    })

    $(document).on('change',"#office_bank_id", function(){
        const post_entry = $("#post_entry")
        const office_bank_id = $("#office_bank_id")
        const bankRef = $("#bankRef")
        const url = "<?=base_url();?>ajax/journal/getOfficeBankRefByOfficeBank"
        const data = {
            office_bank_id
        }

        if(office_bank_id.val() > 0){
            $.post(url, data, function(response){
                post_entry.removeAttr('disabled')
                bankRef.removeAttr('disabled')
                bankRef.children().remove();
                if(response.isBankReferenced){
                    let opts = '<option value = ""><?=get_phrase('select_bank_reference');?></option>';
                    response.options.each(function(i, el){
                        opts += '<option value = "' + el + '">' + el + '</option>'
                    })

                    bankRef.append(opts)
                }
            })
        }else{
            post_entry.prop('disabled', true)
            bankRef.prop('disabled', true)
        }
    })
</script>
