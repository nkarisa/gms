<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

<div class="row">
    <div id="add_btn_wrapper" class="col-12 text-center my-4">
        <a href="<?= base_url(); ?>reimbursement_claim/singleFormAdd" class="btn btn-default">Add Reimbursement
            Claim</a>
    </div>
    <div id='filter_records' class="col-12 my-4">
        <!-- Filter Records Area -->

        <form autocomplete="off" class="form-horizontal form-groups-bordered validate" method="get">

            <div class='row'>

                <div class='col-xs-12'>
                    <div class='form-group'>
                        <!-- Cluster Name -->
                        <label class='col-xs-2 control-label '><?= get_phrase(
                                "filter_by_cluster",
                                "Filter by Cluster"
                            ) ?></label>
                        <div id="fk_cluster_wrapper" class='col-xs-4'>


                        </div>


                    </div>
                </div>
            </div>
            <!-- Status Name -->
            <div class='row'>
                <div class='col-xs-12'>
                    <div class='form-group'>
                        <label class='col-xs-2 control-label '><?= get_phrase(
                                "filter_by_status",
                                "Filter by Status"
                            ) ?></label>
                        <div id="status_wrapper" class='col-xs-4'>

                        </div>


                    </div>
                </div>

            </div>
            <!-- Button -->
            <div> &nbsp;</div>
            <div class='col-xs-12'>
                <div class='form-group'>
                    <div class='col-xs-12' style='text-align:center;'>

                        <input id="filter_id" class='btn btn-primary btn-filter' type="submit" value="Filter">
                    </div>
                </div>
            </div>
        </form>

    </div>
    <div class="col-12">
        <table id="rc-datatable" class="table table-striped">
            <thead>
            <tr>
                <th style="white-space: nowrap;">Claim Id</th>
                <th style="min-width: 150px;"><?= get_phrase('action'); ?></th>
                <th style="white-space: nowrap;" class="absorbing-column"><?= 'Uploads' ?> </th>
                <th style="white-space: nowrap;"><?= get_phrase('reimbursement_trk_no', 'Track number'); ?></th>
                <th style="white-space: nowrap;"><?= get_phrase('voucher', 'voucher_number'); ?></th>
                <th style="white-space: nowrap;"><?= get_phrase('childNo', 'Child Number'); ?></th>
                <th style="white-space: nowrap;"><?= get_phrase('fcpNo', 'FCP Code'); ?></th>
                <th style="white-space: nowrap;"><?= get_phrase('claim_type', 'Claim Type'); ?></th>
                <th style="white-space: nowrap;"><?= get_phrase('funding_type', 'Funding Type'); ?></th>
                <th style="white-space: nowrap;"><?= get_phrase('connect_incident_id', 'Connect Incident ID'); ?></th>

                <th style="white-space: nowrap;"><?= get_phrase('treatment_date', 'Treatment/Transaction Date'); ?></th>
                <th style="white-space: nowrap;"><?= get_phrase('diagnosis', 'Diagnosis/Description'); ?></th>
                <th style="white-space: nowrap;"><?= get_phrase('reimbursement_amount', 'Total Amount'); ?></th>
                <th style="white-space: nowrap;"><?= get_phrase('caregiver_contribution', 'caregiver_contribution'); ?></th>
                <th style="white-space: nowrap;"><?= get_phrase('amount_reimbursed', 'Amount Reimbursed'); ?></th>
                <th style="white-space: nowrap;"><?= get_phrase('fcp_cluster', 'Cluster'); ?></th>
                <th style="white-space: nowrap;"><?= get_phrase('reimbursement_claim_name', 'Child Name'); ?></th>
                <th style="white-space: nowrap;"><?= get_phrase('reimbursement_claim_created_date', 'Created Date'); ?></th>
                <th style="white-space: nowrap;"><?= get_phrase('reimbursement_claim_last_modified_date', 'Modified Date'); ?></th>
                <th style="white-space: nowrap;"><?= get_phrase('last_modified_by', 'Modified By'); ?></th>


            </tr>


            </thead>

        </table>
    </div>
</div>

<script>
    let hasPermissionForAddClaimButton = false;
    let selectFilterBuilt = false;
    $(document).ready(function () {

        $(document).on('click', '#filter_id', function (e) {
            table.ajax.reload();
            e.preventDefault();
        });

        Dropzone.autoDiscover = false;

        var table = $('#rc-datatable').DataTable({
            dom: 'lBfrtip',
            buttons: [
                'copyHtml5',
                'excelHtml5',
                'csvHtml5',
                'pdfHtml5',
            ],
            //
            // 'stateSave': true,
            order: {
                name: 'reimbursement_claim_id',
                dir: 'desc'
            },
            iDisplayLength: 10,
            scrollY: '55vh',
            scrollX: true,
            processing: true,
            serverSide: true,
            pagingType: "full_numbers",
            ajax: {
                url: '/ajax/reimbursement_claim/claims',
                type: "POST",
                data: function (d) {
                    d.fk_status_ids = $('#status_id').val();
                    d.fk_cluster_ids = $('#fk_cluster_id').val();
                },
                "beforeSend": function () {
                    // Show the loading indicator when the request is sent
                    $('#overlay').css('display', 'block');
                },
                "complete": function (data) {
                    // Hide the loading indicator when the request is complete
                    hasPermissionForAddClaimButton = data.responseJSON.hasPermissionForAddClaimButton

                    if (!selectFilterBuilt) {
                        populateClusterSelect(data.responseJSON.fkClusters);
                        populateStatusSelect(data.responseJSON.status);
                        selectFilterBuilt = true;
                    }

                    if (hasPermissionForAddClaimButton) {
                        $('#add_btn_wrapper').removeClass('hidden');
                        $('#filter_records').addClass('hidden');
                    } else {
                        $('#add_btn_wrapper').addClass('hidden');
                        $('#filter_records').removeClass('hidden');
                    }


                    $('#overlay').css('display', 'none');

                }
            },

            /*
    {
        "reimbursement_claim_id": "134",
        "reimbursement_claim_name": " Treasure Amani Gakii",
        "reimbursement_app_type_name": "MEDICAL-CLAIM",
        "reimbursement_funding_type_name": "Sponsorship",
        "voucher_number": "220811",
        "status_name": "Ready To Submit",
        "reimbursement_claim_track_number": "REIM-72133",
        "reimbursement_claim_facility": "Tenri hospital",
        "reimbursement_claim_incident_id": "I-2696261",
        "reimbursement_claim_beneficiary_number": " KE021401105 ",
        "reimbursement_claim_count": "1",
        "reimbursement_claim_treatment_date": "2023-03-03",
        "reimbursement_claim_created_date": "2023-03-21",
        "reimbursement_claim_diagnosis": "ENT",
        "reimbursement_claim_amount_reimbursed": "264.50",
        "reimbursement_claim_caregiver_contribution": "140.50",
        "fk_context_cluster_id": "171",
        "office_name": "Embu",
        "fk_status_id": "2952",
        "support_documents_need_flag": "1",
        "fk_voucher_detail_id": "965638"
    }
             */
            columns: [
                {
                    data: 'reimbursement_claim_id',
                    searchable: false,
                    visible: false,
                    className: 'nowrap'
                },
                {data: 'action', className: 'nowrap', searchable: false},
                {data: 'uploads', searchable: false},
                {data: 'reimbursement_claim_track_number'},
                {data: 'voucher_number'},
                {data: 'reimbursement_claim_beneficiary_number'},
                {data: 'fcp_number', searchable: false,},
                {data: 'reimbursement_app_type_name'},
                {data: 'reimbursement_funding_type_name'},
                {data: 'reimbursement_claim_incident_id'},
                {data: 'reimbursement_claim_treatment_date'},
                {data: 'reimbursement_claim_diagnosis'},
                {data: 'amount'},
                {data: 'reimbursement_claim_caregiver_contribution'},
                {data: 'reimbursement_claim_amount_reimbursed'},
                {data: 'office_name'},
                {data: 'reimbursement_claim_name'},
                {data: 'reimbursement_claim_created_date'},
                {data: 'reimbursement_claim_last_modified_date'},
                {data: 'last_modified_by'},
            ],
            "createdRow": function (row, data, index) {
                $(row).attr('style', data.tr_style)

            }
        }); //end data table

        $('body').on('click', ".trigger_comment_area", function () {
            //$(".text-hidden").toggleClass("text");

            const id = $(this).data('reimbursement_id_comment_btn');


            $('#claim_decline_reason_div_' + id).removeClass('hidden');
            $('#saved_comments_div_' + id).removeClass('hidden');

            //
            $(this).toggleClass('fa-comment fa-close');

            if ($(this).hasClass('fa-close')) {
                draw_comment_table(id);
            } else {
                $('#claim_decline_reason_div_' + id).addClass('hidden');
                $('#saved_comments_div_' + id).addClass('hidden');
            }


        }); //end trigger_comment_area

        $('body').on('change', ".claim_decline_reason", function (event) {
            const comment = $(this).val().trim();
            const reimbursement_id_txt_area = $(this).data("reimbursement_id_txt_area");

            if (comment == '') {
                alert('<?= get_phrase("empty_reimbursement_comment", "No comment entered"); ?>');

                return false;
            }
            const data = {
                fk_reimbursement_claim_id: reimbursement_id_txt_area,
                reimbursement_comment_detail: comment
            }

            //console.log(data);

            const url = '<?= base_url() ?>ajax/reimbursement_claim/add_reimbursement_comment';

            $.post(url, data, function (response) {

                if (response.insert) {
                    alert('<?= get_phrase("reimbursement_comment_saved", "Comment Saved Successfully"); ?>');

                    //Clear the Textarea
                    $('#claim_decline_reason_' + reimbursement_id_txt_area).val('');

                    $('#saved_comments_div_' + reimbursement_id_txt_area).html('');

                    draw_comment_table(reimbursement_id_txt_area)


                } else {
                    alert('<?= get_phrase("reimbursement_comment_not_saved", "Comment NOT Saved"); ?>');
                }
            });


            event.preventDefault();


        }); //end claim_decline_reason

        $(document).on('click', ".reciepts, .docs", function () {


            //get data from form
            let document_type = $(this).data('document_type');
            let voucher_id = $(this).data('store_voucher_number');
            var reimbursement_claim_id = $(this).data('reimbursement_claim_id');

            // alert(reimbursement_claim_id);

            //Unhide the file upload area html form for RECEIPTS of SUPPORT DOCUMENTS
            if ($('#upload_receipt_' + reimbursement_claim_id).hasClass('hidden') && $(this).hasClass('reciepts')) {

                $('#upload_receipt_' + reimbursement_claim_id).removeClass('hidden');

            } else if ($('#upload_support_docs_' + reimbursement_claim_id).hasClass('hidden') && $(this).hasClass('docs')) {

                $('#upload_support_docs_' + reimbursement_claim_id).removeClass('hidden');

            } else if (!$('#upload_support_docs_' + reimbursement_claim_id).hasClass('hidden') && $(this).hasClass('docs')) {

                $('#upload_support_docs_' + reimbursement_claim_id).addClass('hidden');

            } else if (!$('#upload_receipt_' + reimbursement_claim_id).hasClass('hidden') && $(this).hasClass('reciepts')) {

                $('#upload_receipt_' + reimbursement_claim_id).addClass('hidden');
            }

            //Check if not receipts switch to support documents

            let dropzone_form_id_receipt_or_support_docs = "#drop_receipts_" + reimbursement_claim_id;

            var tbl_tag_id = '#tbl_render_uploaded_receipts_' + reimbursement_claim_id;

            let search_str_attachment_url = 'receipts';

            if ($(this).hasClass('docs')) {

                dropzone_form_id_receipt_or_support_docs = '#drop_support_documents_' + reimbursement_claim_id;
                tbl_tag_id = '#tbl_render_uploaded_docs_' + reimbursement_claim_id;

                search_str_attachment_url = 'support_documents';
            }

            //Populate the docs and receipts from attachment table

            //Ajax to upload to AWS S3
            var myDropzone = new Dropzone(dropzone_form_id_receipt_or_support_docs, {

                url: "<?= base_url() ?>ajax/reimbursement_claim/upload_reimbursement_claims_documents",
                paramName: "file", // The name that will be used to transfer the file
                params: {
                    'document_type': document_type,
                    'reimbursement_claim_id': reimbursement_claim_id,
                    'store_voucher_number': voucher_id,
                },
                maxFilesize: 50, // MB
                uploadMultiple: true,
                parallelUploads: 5,
                maxFiles: 5,
                acceptedFiles: 'image/*,application/pdf',
            });


            myDropzone.on("complete", function (file) {
                myDropzone.removeAllFiles();
            });

            myDropzone.on('error', function (file, response) {
                console.log(response);
            });

            myDropzone.on("success", function (file, response) {

                if (response == 0) {
                    alert('Error in uploading files');
                    return false;
                }

                //Render the uplaod file once uploaded


                var table_tbody = $(tbl_tag_id + " tbody");

                var medical_id = tbl_tag_id.split("_")[4];


                let receipts_and_support_docs_uploaded = false;

                let document_type = tbl_tag_id.split("_")[3]

                if (document_type == 'docs') {
                    document_type = 'support_documents';
                }

                //Get the documents that you have just uploaded and pull medical id


                let obj = JSON.parse(response);

                $.each(obj, function (filename, attachmentsArray) {
                    $.each(attachmentsArray, function (i, elem) {
                        // Once the documents are uploaded, enable the 'Ready To Submit' button
                        let medical_id = elem.attachment_primary_id;

                        let ready_submit_btn = $('#' + medical_id);

                        // Get the support docs flag (from data attribute of corresponding hidden field)
                        let input_support_docs_flag = $('#support_documents_need_flag_' + medical_id).data('suppoort_doc_hidden_field');
                        let support_docs_flag = input_support_docs_flag;

                        // Compose the URL to fetch full attachment details
                        let url = '<?= base_url() ?>ajax/reimbursement_claim/get_medical_claim_attachment_by_Id/' +
                            medical_id + '/' + document_type + '/' + support_docs_flag;

                        // Clear and rebuild table body
                        let rebuild_table = '';

                        $.get(url, function (res) {
                            let attachment_obj = JSON.parse(res);
                            let receipts_and_support_docs_uploaded = false;

                            $.each(attachment_obj, function (index, el) {
                                // Check if either receipt or support doc is uploaded
                                if (el.receipt_or_support_doc_flag === 'true') {
                                    receipts_and_support_docs_uploaded = true;
                                }

                                // Rebuild the table row
                                rebuild_table += `
                    <tr>
                        <td><i id="${el.attachment_id}" class="btn fa fa-trash delete_attachment" aria-hidden="true"></i></td>
                        <td><a target="_blank" href="${el.attachment_url}">${el.attachment_name}</a></td>
                    </tr>`;
                            });

                            // Enable or disable the 'Ready To Submit' button
                            if (receipts_and_support_docs_uploaded) {
                                ready_submit_btn.removeClass('disabled');
                            } else {
                                ready_submit_btn.addClass('disabled');
                            }

                            // Populate the table body
                            table_tbody.html(rebuild_table);
                        });
                    });
                });

            });

        });

        $(document).on('click', '.delete_attachment', function () {

            //Reload the td
            var table_id = $(this).parent().parent().parent().parent().attr('id');

            /*Split the id to get numeral value at index 4 which a table id id="tbl_render_uploaded_receipts_79" e.g. 79 and
              Document type which is either Receipts of Docs  at index 3
            */

            var medical_id = table_id.split("_")[4];

            let document_type = table_id.split("_")[3]

            let ready_submit_btn = $('#' + medical_id);

            //let support_docs_flag = ready_submit_btn.next().data('suppoort_doc_hidden_field');

            let support_docs_flag = ready_submit_btn.closest('td').find('input[data-suppoort_doc_hidden_field]').data('suppoort_doc_hidden_field');

            console.log(support_docs_flag)

            //Ajax call to delete receipts and supporting documenets
            let attachment_id = $(this).attr('id');

            url = '<?= base_url(); ?>ajax/reimbursement_claim/delete_reciept_or_support_docs/' + attachment_id;

            $.post(url, function (response) {

                $message = 'Deletion Failed';

                if (response == true) {

                    $message = 'Attachments Deleted';

                    //Rewrite the upload table
                    var table_tbody = $('#' + table_id + '  tbody');

                    if (document_type == 'docs') {
                        document_type = 'support_documents';
                    }

                    attachment_urls = '<?= base_url(); ?>ajax/reimbursement_claim/get_medical_claim_attachment_by_Id/' + medical_id + '/' + document_type + '/' + support_docs_flag;

                    //After Delete Redraw the table to list the remaining documents= 'Receipts and or Support_ documents'
                    $.get(attachment_urls, function (response2) {


                        table_tbody.html('');

                        let build_tbody_for_receipts_or_support_docs = '';

                        let attachments_after_delete = JSON.parse(response2);

                        $.each(attachments_after_delete, function (index, element) {
                            if (
                                element.attachment_url.includes('support_documents') ||
                                element.attachment_url.includes('receipts')
                            ) {
                                build_tbody_for_receipts_or_support_docs += `
            <tr>
                <td>
                    <i id="${element.attachment_id}" class="btn fa fa-trash delete_attachment" aria-hidden="true"></i>
                </td>
                <td>
                    <a target="_blank" href="${element.attachment_url}">${element.attachment_name}</a>
                </td>
            </tr>`;
                            }
                        });

                        //Re-Draw the tbody for receipts table with id

                        table_tbody.html(build_tbody_for_receipts_or_support_docs);

                        //Check if the all documents/or receipts have been deleted and if so Disable the Ready Submit Button
                        if (build_tbody_for_receipts_or_support_docs == '') {

                            let ready_submit_btn = $('#' + medical_id);
                            ready_submit_btn.addClass('disabled');
                        }

                    });
                }
                alert($message);
            });
        });


    }); //end document.ready

    function draw_comment_table(reimbursement_id_txt_area) {

        //Mark disbled
        $('#decline_btn_' + reimbursement_id_txt_area).addClass('disabled');

        let url_get = "<?= base_url() ?>ajax/reimbursement_claim/get_reimbursement_comments/" + reimbursement_id_txt_area;

        $.get(url_get, function (response) {

            reimbursement_comments = response;

            //console.log(reimbursement_comments);

            //Repopulate the table
            if (reimbursement_comments) {
                let table_html = '';


                $('#decline_btn_' + reimbursement_id_txt_area).removeClass('disabled');


                table_html = table_html + "<table class='table table-striped'><tbody>";


                $.each(reimbursement_comments, function (index, elem) {
                    table_html = table_html + "<tr>";
                    table_html = table_html + "<td >";
                    table_html = table_html + `<i style='cursor:alias; width: 20px; height: 20px;' class='${!hasPermissionForAddClaimButton ? 'fa fa-trash' : ''} delete_comment'
    data-comment_id='${reimbursement_comments[index].reimbursement_comment_id}'
    data-claim_id='${reimbursement_comments[index].fk_reimbursement_claim_id}'></i>`;

                    table_html = table_html + "</td>";
                    table_html = table_html + "<td >";
                    table_html = table_html + reimbursement_comments[index].reimbursement_comment_detail + " [Created By:" + reimbursement_comments[index].user_lastname + "| On:" + reimbursement_comments[index].reimbursement_comment_created_date + "]";
                    table_html = table_html + "</td>";

                    table_html = table_html + "</tr>";


                });

                table_html = table_html + "</tbody></table>";

                //    $('#trigger_comment_area_'+reimbursement_id_txt_area).html('');

                //    let comment_icon="<i  style='cursor:alias; font-size:20pt;' id='trigger_comment_area_'"+reimbursement_id_txt_area+"' data-reimbursement_id_comment_btn='"+reimbursement_id_txt_area+"' class='fa fa-comment trigger_comment_area' ></i>";

                //    $('#trigger_comment_area_'+reimbursement_id_txt_area).html(comment_icon);

                $('#saved_comments_div_' + reimbursement_id_txt_area).html(table_html);


            } else {
                $('#saved_comments_div_' + reimbursement_id_txt_area).html('');
            }


        });


    }

    function populateClusterSelect(data) {
        let wrapper = $("#fk_cluster_wrapper");
        if (!wrapper.length) return;

        // Remove existing select if present
        wrapper.empty();

        // Create the select element
        let select = $('<select>', {
            class: 'form-control js-cluster-select',
            name: 'fk_cluster_id[]',
            id: 'fk_cluster_id', // Set ID within the function
            multiple: 'multiple'
        });

        // Generate options as raw HTML
        let options = Object.entries(data)
            .map(([key, value]) => `<option value="${key}">${value}</option>`)
            .join('');

        select.append(options);
        wrapper.append(select);

        // Initialize Select2
        select.select2({
            placeholder: "Select Clusters",
            allowClear: true
        });
    }

    function populateStatusSelect(data) {
        let wrapper = $("#status_wrapper");
        if (!wrapper.length) return;

        // Remove existing select if present
        wrapper.empty();

        // Create the select element
        let select = $('<select>', {
            class: 'form-control js-status-select',
            name: 'status_id[]',
            id: 'status_id', // Set ID within the function
            multiple: 'multiple'
        });

        // Generate options as raw HTML
        let options = Object.values(data)
            .map(item => `<option value="${item.status_id}">${item.status_name}</option>`)
            .join('');

        select.append(options);
        wrapper.append(select);

        // Initialize Select2
        select.select2({
            placeholder: "Select Statuses",
            allowClear: true
        });
    }


</script>

