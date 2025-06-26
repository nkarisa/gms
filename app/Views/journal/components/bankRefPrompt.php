<script>
    function getUserInput(message, data) {
        jQuery('#modal_ajax').modal('show', {
            backdrop: 'false'
        });

        const url = "<?=base_url();?>ajax/journal/getBankAndRefViews"
        
       $.post(url, data, function(modalBodyContents) {
         jQuery('#modal_ajax .modal-body #form').html(modalBodyContents.view);
       })
    }
    
</script>

<style>
    /* Custom CSS for Centering Bootstrap 3 Modal */
    .modal {
        text-align: center;
        padding: 0 !important;
        /* Override Bootstrap's default padding */
    }

    /* This pseudo-element creates a full-height invisible inline-block element */
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
        /* Reset text-align for modal content */
        vertical-align: middle;
        /* Optional: You can adjust max-width if needed for smaller modals */
        /* max-width: 500px; */
    }

    /* Responsive adjustment (optional but recommended) */
    @media screen and (max-width: 767px) {
        .modal:before {
            display: none;
            /* Disable vertical centering on small screens for better UX */
        }

        .modal-dialog {
            display: block;
            /* Revert to block display */
            margin: 30px auto;
            /* Bootstrap's default for horizontal centering */
        }
    }

    /* Optional: Some styling for the page to better visualize the modal */
    body {
        min-height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        background-color: #f8f9fa;
    }
</style>

<div class="modal fade" id="modal_ajax" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel"><?=get_phrase('banking_details');?></h5>
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
</div>

<!-- <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="myPromptModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="myPromptModalLabel">Please Enter Your Input</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="promptMessage">Enter your text here:</p>
                <input type="text" class="form-control" id="promptInput">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="promptOkButton">OK</button>
            </div>
        </div>
    </div>
</div> -->