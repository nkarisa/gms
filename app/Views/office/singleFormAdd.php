<?php
extract($result);
?>

<style>
    .has-error {
        border: 1px solid rgb(185, 74, 72) !important;
    }
</style>

<div class='row'>
    <div class='col-xs-12'>

        <div class="panel panel-default" data-collapsed="0">
            <div class="panel-heading">
                <div class="panel-title">
                    <i class="entypo-plus-circled"></i>
                    <?php echo get_phrase('add_office'); ?>
                </div>
            </div>

            <div class="panel-body" style="max-width:50; overflow: auto;">
                <?php echo form_open("", array('id' => 'frm_office', 'class' => 'form-horizontal form-groups-bordered validate', 'enctype' => 'multipart/form-data')); ?>

                <div class='form-group'>
                    <div class='col-xs-12 user_message'>

                    </div>
                </div>

                <div class='form-group'>
                    <div class='col-xs-12' style='text-align:center;'>
                        <button class='btn btn-default btn-reset'><?= get_phrase('reset', 'Reset'); ?></button>
                        <button class='btn btn-default btn-save disabled'><?= get_phrase('save', 'Save'); ?></button>
                        <button
                            class='btn btn-default btn-save-new disabled'><?= get_phrase('save_and_new', 'Save and New'); ?></button>
                    </div>
                </div>

                <div class='form-group'>
                    <label class='col-xs-2 control-label'><?= get_phrase('office_name', 'Office Name'); ?></label>
                    <div class='col-xs-4'>
                        <?= $libs->headerRowField('office_name'); ?>
                    </div>

                    <label
                        class='col-xs-2 control-label'><?= get_phrase('office_description', 'Office Description'); ?></label>
                    <div class='col-xs-4'>
                        <?= $libs->headerRowField('office_description'); ?>
                    </div>
                </div>

                <div class='form-group'>
                    <label class='col-xs-2 control-label'><?= get_phrase('office_code', 'Office Code'); ?></label>
                    <div class='col-xs-4'>
                        <?= $libs->headerRowField('office_code'); ?>
                    </div>

                    <label
                        class='col-xs-2 control-label'><?= get_phrase('office_start_date', 'Office Start Date'); ?></label>
                    <div class='col-xs-4'>
                        <?= $libs->headerRowField('office_start_date'); ?>
                    </div>

                </div>

                <div class='form-group'>
                    <label
                        class='col-xs-2 control-label'><?= get_phrase('context_definition', 'Context Definition'); ?></label>
                    <div class='col-xs-4'>
                        <?= $libs->headerRowField('context_definition_name'); ?>
                    </div>

                    <label
                        class='col-xs-2 control-label'><?= get_phrase('reporting_context', 'Reporting Context'); ?></label>
                    <div class='col-xs-4' id='div_office_context'>
                        <select class='form-control' disabled='disabled'></select>
                    </div>

                </div>


                <div class=' <?= !$session->system_admin ? "hidden" : " "; ?> form-group'>
                    <label
                        class='col-xs-2 control-label hidden'><?= get_phrase('is_office_active', 'Is Office Active?'); ?></label>
                    <div class='col-xs-4 hidden'>
                        <?= $libs->headerRowField('office_is_active', 1); ?>
                    </div>

                    <label
                        class="col-xs-2 control-label <?= !$session->system_admin ? "hidden" : " "; ?>"><?= get_phrase('office_account_system', 'Office Accounting System'); ?></label>
                    <div class='col-xs-4'>
                        <?php
                        //This piece of code was added by Onduso [3/8/2022]
                        if (!$session->system_admin) { ?>
                            <input type="text" id="fk_account_system_id" name="header[fk_account_system_id]"
                                value="<?= $session->user_account_system_id; ?>">
                        <?php } else {
                            echo $libs->headerRowField('account_system_name');
                        }
                        //End of addition
                        ?>

                    </div>
                </div>

                <div class=' <?= !$session->system_admin ? "hidden" : " "; ?> form-group'>
                    <label class='col-xs-2 control-label'>Office Currency</label>
                    <div class='col-xs-4'>
                        <?php
                        //This piece of code was added by Onduso [3/8/2022]
                        if (!$session->system_admin) { ?>
                            <input type="text" id="fk_country_currency_id" name="header[fk_country_currency_id]"
                                value="<?= $country_currency_id; ?>">
                        <?php } else {
                            echo $libs->headerRowField('country_currency_name');
                        }
                        //End of addition
                        ?>
                    </div>
                </div>


                <div class='form-group'>
                    <div class='col-xs-12' style='text-align:center;'>
                        <button class='btn btn-default btn-reset'><?= get_phrase('reset', 'Reset'); ?></button>
                        <button class='btn btn-default btn-save disabled'><?= get_phrase('save', 'Save'); ?></button>
                        <button
                            class='btn btn-default btn-save-new disabled'><?= get_phrase('save_and_new', 'Save and New'); ?></button>
                    </div>
                </div>


                </form>
            </div>
        </div>

        