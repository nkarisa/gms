<style>
    .user_message {
        color:red;
        text-align: center;
        font-weight: bold;
    }
    .hide-message {
        display: none;
    }

    .show-message {
        display: block;
    }
</style>

<?php 
    // echo json_encode($user_unique_identifier_uploads);
    $is_status_id_max = $this->general_model->is_status_id_max('user', $this->session->user_id);
    if(isset($unique_identifier['unique_identifier_name'])) { ?>

<div class = 'row'>
    <div class = 'col-xs-12' style="text-align: center;">
        <b><?=get_phrase('additional_privacy_data_required','Additional Personal Data Required');?></b>
    </div>
</div>

<hr/>

<?php 
if(!$is_status_id_max && $identifier_number != NULL){
    ?>
        <div class = 'row'>
            <div class = 'col-xs-12' style = 'color:red;text-align:center;'>
            
                <div class="well"><?=get_phrase('awaiting_privacy_data_approval_notification','Your user privacy data is awaiting validation by your administrator. If there is a delay please reach the administrator for help. You are not allowed to update this data.');?></div>
            </div>
        </div>
    <?php
    
    } 
?>

<div class = 'row'>
    <div class = 'col-xs-12'>
        <?php //echo form_open("", array('id' => 'frm_consent', 'class' => 'form-horizontal form-groups-bordered validate', 'enctype' => 'multipart/form-data')); ?>
        
        <form id="frm_consent" enctype="multipart/form-data">
            <div class = "form-group">
                <div class = 'col-xs-12'>
                    <?=$content;?>
                    <textarea hidden name = 'consent_content' id = 'consent_content'>
                        <?=$content;?>
                    </textarea>
                </div>
            </div>
        

            <div class = "form-group">

                <label class = 'control-label col-xs-2'><?=get_phrase($unique_identifier['unique_identifier_name']);?></label>
                <div class = 'col-xs-2'>
                    <input class = 'form-control required' value = '<?=isset($identifier_number) ? $identifier_number : '';?>' type = 'text' name = 'user_unique_identifier' id = 'user_unique_identifier'/>
                </div>

                <label class = 'control-label col-xs-2'><?=get_phrase('upload_'.$unique_identifier['unique_identifier_name']);?></label>
                <div class = 'col-xs-2'>
                    <input class = 'form-control required' type = 'file' name = 'file[]' id = 'upload_user_identity'/>
                    <div id = 'fileUpload' style = 'color:green;font-weight:bold'>
                        <ul>
                        <?php 
                            foreach($user_unique_identifier_uploads as $user_unique_identifier_upload){
                            $objectKey = $user_unique_identifier_upload['attachment_url'].'/'.$user_unique_identifier_upload['attachment_name'];
                           // $objectKey = $bank_statements_upload['attachment_url'].'/'.$bank_statements_upload['attachment_name'];
                           $url = $this->config->item('upload_files_to_s3')?$this->grants_s3_lib->s3_preassigned_url($objectKey):$this->attachment_library->get_local_filesystem_attachment_url($objectKey);
                        ?>
                            <li><a target='__blank' href='<?=$url;?>'><?=$user_unique_identifier_upload['attachment_name'];?></li>
                        <?php
                            } 
                        ?>
                        </ul>
                    </div>
                </div>

                <label class = 'control-label col-xs-2'><?=get_phrase('employment_date');?></label>
                <div class = 'col-xs-2'>
                    <input readonly class = 'form-control datepicker required' value = '<?=isset($user_employment_date) ? $user_employment_date : '';?>' onkeydown="return false" type = 'text' name = 'user_employment_date' data-format='yyyy-mm-dd' id = 'user_employment_date'/>
                </div>

                
            </div>

            <div class = 'form-group'>
                <div class = 'col-xs-12'>
                    <div class = 'user_message'></div>
                </div>
            </div>

            <div class = "form-group">
                <div class = 'col-xs-12'>
                    <div class = 'btn btn-success <?=!$is_status_id_max && $identifier_number != NULL ? 'disabled': ''?>' id = 'accept'><?=get_phrase('accept');?></div>
                    <div class = 'btn btn-danger <?=!$is_status_id_max && $identifier_number != NULL ? 'disabled': ''?>' id = 'decline'><?=get_phrase('decline');?></div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php } ?>
<script>

    $(document).on('change','#upload_user_identity', function (){

        const formData  = new FormData($('#frm_consent')[0]) 
        const url = '<?=base_url();?>user/update_user_consent_document'
        
        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            beforeSend: function() {
                $('.user_message').text("<?=get_phrase('wait_identifier_document_to_upload','Wait for the document to upload. Once the upload is complete, the employment date field will become active');?>");
            },
            success: function(response) {
                if(response){
                    $('#user_employment_date').removeAttr('readonly')
                    $('.user_message').text('');
                }
            },
            error: function(response) {
                alert(response);
            },
            cache: false,
            contentType: false,
            processData: false
        });
    })

    $('#accept').on('click', function () {
        // alert('Hello')
        const url = '<?=base_url();?>user/update_user_consent'
        
        // const upload_user_identity = new FormData($('#frm_consent'))
        // let upload_user_identity = new FormData($('#upload_participants_form')[0]);
        // const user_unique_identifier = $('#user_unique_identifier').val()
        // const user_employment_date = $('#user_employment_date').val()

        const formData  = new FormData($('#frm_consent')[0]) // {upload_user_identity, user_unique_identifier, user_employment_date} // $('#frm_consent').serializeArray()

        let count_empty = 0

        $.each($('.required'), function (index, elem){
            if($(elem).val() == ''){
                $(elem).css('border','1px red solid')
                count_empty++
            }
        });

        if(count_empty){
            alert('<?=get_phrase('empty_fields','Some fields are empty');?>');
            return false;
        }

        const cnfrm = confirm('<?=get_phrase('accept_consent_confirmation','Are you sure you want to accept consenting to our data privacy and protecttion policy?');?>');

        if(!cnfrm){
            alert('<?=get_phrase('decline_consent_message','Declining the consent will prevent you from accessing any feature in this system');?>');
            return false;
        }

        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            async: false,
            success: function(response) {
                //if(!is_status_id_max){
                   // window.location.reload()
                //}else if(response){
                window.location.href = '<?= base_url().$this->session->default_launch_page;?>/list'
                //}
            },
            error: function(response) {
                alert(response);
            },
            cache: false,
            contentType: false,
            processData: false
        });
    })

    $('#decline').on('click', function () {
        alert('<?=get_phrase('decline_consent_message','Declining the consent will prevent you from accessing any feature in this system');?>');
    })

    $('#user_unique_identifier').on('change', function  () {
        const user_unique_identifier = $(this).val()
        const url = '<?=base_url();?>user/verify_user_unique_identifier'
        const unique_identifier_id = '<?=isset($unique_identifier['unique_identifier_id']) ? $unique_identifier['unique_identifier_id'] : 0;?>'
        const data = {
            user_unique_identifier,
            unique_identifier_id
        }

        $('.user_message').html('')
        $('#user_unique_identifier').removeAttr('style');

        $.post(url, data, function (response) {
            const obj = JSON.parse(response)
            if(obj.status){
                $message = '<?=get_phrase('duplicate_identifier','Duplicate user identification is not allowed');?></br>'

                $.each(obj.records, function (index, elem) {
                    $message += elem.user_firstname + ' ' + elem.user_lastname + ' [' + elem.user_email + ']</br>'
                })

                $('.user_message').html($message)
                
                $('#user_unique_identifier').val('');
                $('#user_unique_identifier').css('border','1px red solid');
            }
        })
    })
</script>