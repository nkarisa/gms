<script>
    function getUserInput(message, data) {
        jQuery('#myCenteredModal').modal({
            backdrop: false
        });

        const url = "<?= base_url(); ?>ajax/journal/getBankAndRefViews"

        $.post(url, data, function (modalBodyContents) {
            jQuery('#myCenteredModal .modal-body #form').html(modalBodyContents.view);
        })
        
        // $.ajax({
        //     url: url,
        //     type: 'POST',
        //     data: data,
        //     async: false,
        //     success: function (modalBodyContents) {
        //         Query('#myCenteredModal .modal-body #form').html(modalBodyContents.view);
        //      }
        // })
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

<div id="myCenteredModal" class="modal fade" role="dialog">
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
                <button type="button" class="btn btn-primary">Action</button>
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
</script>
<!-- 
<div class="modal fade" id="modal_ajax" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel"><?= get_phrase('banking_details'); ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
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
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary">Save changes</button>
            </div>
        </div>
    </div>
</div> -->