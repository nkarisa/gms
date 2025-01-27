<style>
    #uploadStatus{
        color: red;
        font-weight: bolder;
        text-align: center;
        padding-bottom: 20px;
        padding-top: 20px;
    }
</style>

<div class='row'>
    <div class='col-xs-12 split_screen'>
        <div class="panel panel-default" data-collapsed="0">
            <div class="panel-heading">
                <div class="panel-title">
                    <i class="entypo-plus-circled"></i>
                    <?php echo get_phrase('upload_strategic_objectives'); ?>
                </div>
            </div>

            <div class="panel-body" style="max-width:50; overflow: auto;">
            <div id="uploadStatus"></div>
            <?php echo form_open("", array('id' => 'frm_objectives', 'class' => 'form-horizontal form-groups-bordered validate', 'enctype' => 'multipart/form-data')); ?>
                <div class = 'form-group'>
                        <label class = 'control-label col-xs-4'><?=get_phrase('upload_objectives','Upload Objectives from Connect');?></label>
                        <div class = 'col-xs-8'>
                            <input type="file" name = 'file' id = 'file' class="form-control required" required = "required" />
                        </div>
                    </div>
                    
                    <div class = 'form-group'>
                        <label class = 'control-label col-xs-4'><?=get_phrase('office_name','Office Name');?></label>
                        <div class = 'col-xs-8'>
                            <select class = 'form-control select2' name = 'office_id' id = 'office_id' required = "required">
                                <option value = ""><?=get_phrase('select_an_office');?></option>
                                <?php 
                                    $offices = $this->session->hierarchy_offices;

                                    foreach($offices as $office){
                                ?>
                                        <option value = "<?=$office['office_id'];?>"><?=$office['office_name'];?></option>
                                <?php 
                                    }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class = 'form-group'>
                        <label class = 'control-label col-xs-4'><?=get_phrase('annual_plan_start_date','Annual Plan Start Date');?></label>
                        <div class = 'col-xs-8'>
                            <input class="form-control datepicker required" data-format="yyyy-mm-dd" name = 'annual_plan_start_date' id = 'annual_plan_start_date' onkeydown="return false" required = "required" />
                        </div>
                    </div>

                    <div class = 'form-group'>
                        <label class = 'control-label col-xs-4'><?=get_phrase('annual_plan_end_date','Annual Plan End Date');?></label>
                        <div class = 'col-xs-8'>
                            <input class="form-control datepicker required" data-format="yyyy-mm-dd" name = 'annual_plan_end_date' id = 'annual_plan_end_date' onkeydown="return false" required = "required" />
                        </div>
                    </div>

                    <div class = 'form-group'>
                        <div class = 'col-xs-4'>
                            <div class = 'btn btn-default' id = 'upload' ><?=get_phrase('upload','Upload');?></div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
        $('.required').on('change', function () {
            $(this).removeAttr('style')
        });

        $('#upload').on('click', function () {

            // Check if the date range is a year period
            const annual_plan_start_date = $('#annual_plan_start_date').val()
            const annual_plan_end_date = $('#annual_plan_end_date').val()

            let datesAreOneYearApart = compareDates(annual_plan_start_date, annual_plan_end_date)

            if(!datesAreOneYearApart){
                return false;
            }

            // Check if every field is populated before calling the uploadFile method
            let count_empty_fields = 0
            $.each($('.required'), function (i, elem) {
                if(!$(elem).val()){
                    alert($(elem).attr('id'))
                    $(elem).css('border','1px solid red')
                    count_empty_fields++
                    //s2id_office_id
                }else{
                    $(elem).removeAttr('style')
                }
            })

            if(count_empty_fields > 0){
                alert(count_empty_fields + ' <?=get_phrase('fields are empty');?>');
                return false;
            }

            uploadFile()
        })

        function uploadFile() {
            let formData = new FormData();
            const fileInput = $('#file')[0].files[0];
            const office_id = $('#office_id').val()
            const annual_plan_start_date = $('#annual_plan_start_date').val()
            const annual_plan_end_date = $('#annual_plan_end_date').val()
            const url = '<?=base_url();?>strategic_objectives/upload_strategic_objectives'

            formData.append('file', fileInput);
            formData.append('office_id', office_id);
            formData.append('annual_plan_start_date', annual_plan_start_date);
            formData.append('annual_plan_end_date', annual_plan_end_date);

            $.ajax({
                url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    $('#uploadStatus').html(response);
                },
                error: function () {
                    $('#uploadStatus').html('Error uploading file.');
                }
            });
        }

       
        function compareDates(startDate, endDate) {
            let date1 = $('#date1').val();
            let date2 = $('#date2').val();

            let dateOneYearApart = false;

            if (startDate && endDate) {
                var parsedDate1 = new Date(startDate);
                var parsedDate2 = new Date(endDate);

                // Check if the dates are one year apart
                if (Math.abs(parsedDate1 - parsedDate2) / (365 * 24 * 60 * 60 * 1000) >= 1) {
                    dateOneYearApart = true;
                } else{
                    alert('The dates are less than one year.');
                }
            } else {
                alert('Please enter both dates.');
            }

            return dateOneYearApart;
        }

    </script>