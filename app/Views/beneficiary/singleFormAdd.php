<div class='row'>
    <div class='col-xs-12'>

        <div class="panel panel-default" data-collapsed="0">
            <div class="panel-heading">
                <div class="panel-title">
                    <i class="entypo-plus-circled"></i>
                    <?php echo get_phrase('upload_beneficiaries'); ?>
                </div>
            </div>


            <div class="panel-body" style="max-width:50; overflow: auto;">
                <!-- Upload CSV form -->

                <form id='upload_participants_form' , class='form-horizontal form-groups-bordered validate' ,
                      name='upload_participants_form'>

                    <div class='form-group'>

                        <div class='col-xs-8 hidden' id='file_div'></div>

                    </div>
                    <!-- File inputs -->

                    <div class='form-group'>
                        <label class='col-xs-4 control-label'><?= get_phrase('upload') ?></label>
                        <div class='col-xs-8'>
                            <input type="file" name='file' class="form-control" id="file"
                                   aria-describedby="basic-addon3" accept=".csv">
                        </div>

                    </div>
                    <!-- Country Input -->
                    <?php
                    if ($systemAdmin) {

                        $get_national_offices = $nationalOffices;

                        unset($get_national_offices[1]);

                        unset($get_national_offices[2]);

                        // print_r($get_national_offices)

                        ?>
                        <div class='form-group'>
                            <label class='col-xs-4 control-label'><?= get_phrase('Country') ?></label>
                            <div class='col-xs-8'>
                                <select name='countries' class="form-control select2" id="country_id">
                                    <option value='0'><?= get_phrase('select_0', 'Select Country'); ?></option>
                                    <?php
                                    foreach ($get_national_offices as $key => $get_national_office) { ?>
                                        <option value=<?= $key; ?>><?= get_phrase('select' . $key, $get_national_office) ?></option>
                                    <?php } ?>

                                </select>
                            </div>
                        </div>
                    <?php } ?>

                    <!-- Buttons -->
                    <div class='form-group'>
                        <div class='col-xs-12' style='text-align:center;'>
                            <button id='upload' class='btn btn-default btn-reset'>Upload CSV</button>

                        </div>
                    </div>

                </form>
            </div>
        </div>

        <script>
            //Check order of columns in CSV
            $('#file').on('change', function (e) {

                const file = e.target.files[0];

                const reader = new FileReader();

                reader.onload = function () {
                    const lines = reader.result.split('\n');

                    const header = lines[0].trim().split(',');

                    // Define the expected order of columns
                    const expectedColumns = ['beneficiary_name', 'beneficiary_number', 'beneficiary_dob', 'beneficiary_gender'];

                    // Check if the actual columns match the expected order
                    if (JSON.stringify(header) != JSON.stringify(expectedColumns)) {
                        // The columns are in the expected order

                        alert(`CSV columns NOT in the expected order. Order Columns in CSV as: ${expectedColumns.join(', ')}`);

                        $('#file').val('');

                        $('#file').css('border-color', 'red');

                        return false;
                    }

                };

                reader.readAsText(file);
            });


            //Upload to S3 using Multipart Upload
            $('#upload').on('click', function (event) {

                event.preventDefault();

                //If file is not selected return false other instert data
                let file = $('input[type="file"]').val().trim();

                //User logged is system admin
                let logged_user = '<?= $systemAdmin; ?>';

                let country = '';

                if (parseInt(logged_user) === 1) {


                    //Check if country or file is empty
                    if ($('#country_id').val() == 0 && file == '') {


                        alert('Choose CSV file to proceed or country is NOT selected');

                        $('#file').css('border-color', 'red');

                        $('#country_id').css('border-color', 'red').select2();

                        return false;

                    } else if (file == '' && $('#country_id').val() != 0) {

                        $('#file').css('border-color', 'red');

                        alert('Choose CSV file to proceed');

                        return false;

                    } else if (file != '' && $('#country_id').val() == 0) {

                        alert('Choose Country is NOT selected');

                        $('#country_id').css('border-color', 'red').select2();

                        return false;
                    } else {
                        country = parseInt($('#country_id').val());
                    }

                } else {

                    if (file == '') {

                        alert('Choose CSV file to proceed');

                        $('#file').css('border-color', 'red');

                        return false;
                    }

                }

                //Validate the file extension disllow all other file extension except CSV

                // let validate_file_extension = verify_file_extension(file);

                // if (validate_file_extension) {

                //     alert("File is either not CSV file");

                //     let file_element = $("#file");

                //     file_element.val("");

                //     file_element.css('border-color', 'red');

                //     return false;
                // }

                //Get formdata in this case file
                let formData = new FormData($('#upload_participants_form')[0]);

                let url = '<?= base_url() ?>ajax/beneficiary/upload_large_csv_data_to_s3';


                //Disble button and file input and message of uploading
                $('#file_div').removeClass('hidden');
                $('#file_div').html("<center><h3 style='color: green;'><?= get_phrase('upload_msg', "Please wait while the file is being uploaded..."); ?></h3></center>")
                $('#file').attr('disabled', true);
                $('#upload').attr('disabled', true);
                $('#country_id').attr('disabled', true);

                //Wait for 5 seconds before making a call.
                setTimeout(function () {
                    $.ajax({
                        url: url,
                        type: 'POST',
                        data: formData,
                        async: false,
                        success: function (res) {

                            alert(res);

                            window.location = '<?= base_url(); ?>beneficiary/list/';

                        },
                        error: function (response) {
                            alert(response);
                        },
                        cache: false,
                        contentType: false,
                        processData: false
                    });
                }, 3000);

            });

            //check file extension
            function verify_file_extension(file) {

                //Only CSV file is allowed
                var ext = file.split(".");
                //console.log(ext[0]);
                ext = ext[ext.length - 1].toLowerCase();
                var arrayExtensions = ["csv"];

                if (arrayExtensions.lastIndexOf(ext) == -1) {

                    return true;
                } else {
                    return false;
                }
            }
        </script>