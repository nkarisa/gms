<script>
    datatable.on('click', '.btn.dt-control', function(e) {
        let tr = e.target.closest('tr');
        let row = datatable.row(tr);
        let dt_controls = $('.btn.dt-control')
        let dt_control_id = $(this).attr('id');
        //let data = row.data();
        let voucher_id = $(this).data('voucher_id');
        let attachments = $(this).data('attachments')
        let can_delete_attachment = $(this).data('can_delete_attachment')


        if (row.child.isShown()) {
            // This row is already open - close it
            row.child.hide();
        } else {

            $.each(dt_controls, function(index, element) {
                let inner_tr = $(element).closest('tr')
                let inner_row = datatable.row(inner_tr)
                datatable.row(inner_row).child.hide();
            })

            // Open this row

            //console.log(data);
            row.child(format(voucher_id,can_delete_attachment)).show();

            // If already has attachments, list them
            //console.log(attachments);
            //build_attachment_table_body(voucher_id, can_delete_attachment, attachments)


            build_attachment_table_body(voucher_id, can_delete_attachment);

            const voucherDropzone = new Dropzone(".drop_receipts", {
                url: "<?php echo base_url() ?>ajax/voucher/uploadReceipts",
                paramName: "file",
                params: {
                    voucher_id: voucher_id
                },
                maxFilesize: 5, // MB
                uploadMultiple: true,
                parallelUploads: 5,
                maxFiles: 5,
                acceptedFiles: 'image/*,application/pdf',
            });

            voucherDropzone.on("complete", function(file) {
                //myDropzone.removeFile(file);
                voucherDropzone.removeAllFiles();
                //alert(myDropzone.getAcceptedFiles());
            });

            voucherDropzone.on('error', function(file, response) {
                // $(file.previewElement).find('.dz-error-message').text(response);
                console.log(response);
            });

            voucherDropzone.on("success", function(file, response) {
                // console.log(response);
                if (response == 0) {
                    alert('<?php echo get_phrase("file_upload_error", "Error in uploading files"); ?>');
                    return false;
                }

                datatable.draw()

            });


        }
    });

    function build_attachment_table_body(voucher_id, can_delete_attachment) {
        let table_tbody = $("#tbl_list_receipts_" + voucher_id + " tbody");
        let hidden = !can_delete_attachment ? 'hidden' : '';

        let attached_dr_url = "<?= base_url() ?>ajax/voucher/getAttachmentDocuments/" + voucher_id;

        $.get(attached_dr_url, function(response) {

            let attachments = response;

            if (attachments.length > 0) {

                $.each(attachments, function(i, elem) {

                    table_tbody.append(
                        `<tr>
                                <td><a href="#0" class="fa fa-trash-o delete_file_attachment ${hidden}" data-record_id = "${voucher_id}" id="delete_${elem.attachment_id}"></a></td>
                                <td><a target="__blank" href="${elem.attachment_url}">${elem.attachment_name}</a></td>
                                <td>${elem.attachment_size}</td><td>${elem.attachment_last_modified_date}</td>
                            </tr>`
                    );
                });
            }

        });

        //Get attachments of the voucher_id


        // if (attachments.length > 0) {

        //     $.each(attachments, function(i, elem) {

        //         table_tbody.append(
        //             `<tr>
        //                     <td><a href="#0" class="fa fa-trash-o delete_file_attachment ${hidden}" data-record_id = "${voucher_id}" id="delete_${elem.attachment_id}"></a></td>
        //                     <td><a target="__blank" href="${elem.attachment_url}">${elem.attachment_name}</a></td>
        //                     <td>${elem.attachment_size}</td><td>${elem.attachment_last_modified_date}</td>
        //                 </tr>`
        //         );
        //     });
        // }
    }

    // function build_attachment_table_body(voucher_id, can_delete_attachment, attachments) {
    //     let table_tbody = $("#tbl_list_receipts_" + voucher_id + " tbody");
    //     let hidden = !can_delete_attachment ? 'hidden' : '';

    //     if (attachments.length > 0) {
    //         $.each(attachments, function(i, elem) {

    //             table_tbody.append(
    //                 `<tr>
    //                         <td><a href="#0" class="fa fa-trash-o delete_file_attachment ${hidden}" data-record_id = "${voucher_id}" id="delete_${elem.attachment_id}"></a></td>
    //                         <td><a target="__blank" href="${elem.attachment_url}">${elem.attachment_name}</a></td>
    //                         <td>${elem.attachment_size}</td><td>${elem.attachment_last_modified_date}</td>
    //                     </tr>`
    //             );
    //         });
    //     }
    // }


    // function format(data) {

    //     console.log(data.length - 1);

    //     const voucher_id = data[data.length - 1]
    //     const can_upload_attachment = data[data.length - 4]
    //     let can_delete_attachment = data[data.length - 3]
    //     const hide_upload = !can_upload_attachment || !can_delete_attachment ? 'hidden' : ''

    //     return (
    //         `<div class="row">
    //         <div class="col-xs-3 ${hide_upload}">
    //             <form class="dropzone drop_receipts" id="drop_receipts_${voucher_id}">
    //                 <div class="fallback">
    //                     <input name="file" type="file" multiple />
    //                 </div>
    //             </form>
    //         </div>
    //         <div class="col-xs-9">
    //             <div class = "table_container">
    //                 <table class="table table-striped table_receipts" id="tbl_list_receipts_${voucher_id}">
    //                     <thead>
    //                         <tr>
    //                             <th><?php echo get_phrase('action', 'Action'); ?></th>
    //                             <th><?php echo get_phrase('file_name', 'File Name'); ?></th>
    //                             <th><?php echo get_phrase('file_size', 'File Size'); ?></th>
    //                             <th><?php echo get_phrase('last_modified_date', 'Last Modified Date'); ?></th>
    //                         </tr>
    //                     </thead>
    //                     <tbody>
    //                     </tbody>
    //                 </table>
    //             </div>
    //         </div>
    //         </div>`
    //     );
    // }


function format(voucher_id,can_delete_attachment) {

//const voucher_id = data[data.length - 1]
//const can_upload_attachment = data[data.length - 4]
//let can_delete_attachment = data[data.length - 3]
const hide_upload = !can_delete_attachment ? 'hidden' : ''

return (
    `<div class="row">
    <div class="col-xs-3 ${hide_upload}">
        <form class="dropzone drop_receipts" id="drop_receipts_${voucher_id}">
            <div class="fallback">
                <input name="file" type="file" multiple />
            </div>
        </form>
    </div>
    <div class="col-xs-9">
        <div class = "table_container">
            <table class="table table-striped table_receipts" id="tbl_list_receipts_${voucher_id}">
                <thead>
                    <tr>
                        <th><?php echo get_phrase('action', 'Action'); ?></th>
                        <th><?php echo get_phrase('file_name', 'File Name'); ?></th>
                        <th><?php echo get_phrase('file_size', 'File Size'); ?></th>
                        <th><?php echo get_phrase('last_modified_date', 'Last Modified Date'); ?></th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
    </div>`
);
}



    $(document).on('click', ".delete_file_attachment", function() {
        const elem = $(this)
        const elem_id = $(this).attr('id');
        const attachment_id = elem_id.split('_')[1]
        const voucher_id = $(this).data('record_id');
        const attachment_url = '<?php echo base_url(); ?>ajax/voucher/deleteAttachment/' + attachment_id + '/' + voucher_id;
        const can_delete_attachment = true
        const tbody = $('#tbl_list_receipts_' + voucher_id).find(('tbody'))

        $.get(attachment_url, function(response) {

            elem.closest('tr').remove()
            if (!tbody.children().length) {
                datatable.draw()
            }

            // const attachments =JSON.parse(response)
            // build_attachment_table_body(voucher_id, can_delete_attachment, attachments)
        });
    })
</script>