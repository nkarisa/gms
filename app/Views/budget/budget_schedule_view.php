<style>
    .format_row_for_review {
        background-color: orange;
    }

    .notes,
    .budget_item_revisions {
        cursor: pointer;
    }
</style>
<?php

use \App\Libraries\System\Widgets\WidgetBase;

extract($result);


?>

<style>
    .row {
        margin: 20px;
    }
</style>

<div class="row hidden-print">
    <div class="col-xs-12">
        <?= WidgetBase::load('comment'); ?>
    </div>
</div>

<div class='row'>
    <div class='col-xs-offset-2 col-xs-8 col-xs-offset-2 hidden-print' style='text-align:center;'>

        <?php
        $statusLib = new \App\Libraries\Core\StatusLibrary();
        $initial_status = $statusLib->initialItemStatus($controller);
        $userLib = new \App\Libraries\Core\UserLibrary();
        $permision = $userLib->checkRoleHasPermissions('budget_item', 'create');

        if (($initial_status == $budget_status_id || $is_declined_state) && $permision) { ?>
            <a href="<?= base_url(); ?>budget_item/multiFormAdd/<?= $id; ?>/budget">
                <div class='btn btn-default'><?= get_phrase('add_new_budget_item'); ?></div>
            </a>
        <?php } ?>

        <?php
        echo approval_action_button($controller, $budget_status_data['item_status'], hash_id($id, 'decode'), $budget_status_id, $budget_status_data['item_initial_item_status_id'], $budget_status_data['item_max_approval_status_ids'], $action_button_disabled);
        ?>
        <div data-hide_state='hide' class="btn btn-default" id="filter_marked_items"><?= get_phrase('toggle_marked_for_review_items_list', 'Show/Hide Items to Be Reviewed') ?></div>
    </div>
</div>
<?php foreach ($budget_schedule as $income_group) { ?>
    <div class='row'>
        <div class='col-xs-12' style='text-align:center;font-weight:bold;'>
            <?= ucwords($income_group['income_account']['income_account_name']); ?> <?= get_phrase('budget_schedule_for'); ?> <?= $office; ?> <?= get_phrase('FY'); ?><?= $current_year; ?> <?= $budget_tag; ?> <span class='hidden-print'>(<a href='<?= base_url(); ?>budget/view/<?= $id; ?>/summary/<?= hash_id(1); ?>'><?= get_phrase('show_budget_summary'); ?></a>)</span>
        </div>
    </div>

    <div class='row'>
        <div class='col-xs-12'>
            <?php foreach ($income_group['budget_items'] as $loop_budget_items) { ?>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th colspan='<?= isset($this->session->system_settings['use_pca_objectives']) ? '20' : '19'; ?>' style='text-align:center'>
                                <?= get_phrase('expense_account'); ?>: <?= $loop_budget_items['expense_account']['expense_account_code']; ?> - <?= $loop_budget_items['expense_account']['expense_account_name']; ?>
                            </th>
                        </tr>
                        <tr>
                            <th><?= get_phrase('action'); ?></th>
                            <th class="<?= !isset($this->session->system_settings['use_pca_objectives']) ? 'hidden' : ''; ?>"><?= get_phrase('objective/intervention'); ?></th>
                            <th><?= get_phrase('description'); ?></th>
                            <th><?= get_phrase('quantity'); ?></th>
                            <th><?= get_phrase('unit_cost'); ?></th>
                            <th><?= get_phrase('often'); ?></th>
                            <th><?= get_phrase('total_cost'); ?></th>
                            <th><?= get_phrase('status'); ?></th>
                            <?php foreach ($month_names_with_number_keys as $month_name) { ?>
                                <th><?= $month_name; ?></th>
                            <?php } ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // print_r($loop_budget_items['expense_items']);

                        foreach ($loop_budget_items['expense_items'] as $budget_item_id => $loop_expense_items) {
                            $status_id = $loop_expense_items['status']['status_id'];
                            $budget_item_is_fully_approved = in_array($status_id, $budget_item_status_data['item_max_approval_status_ids']);
                            $format_row_for_review = 'unformat_row_for_review';
                            if ($loop_expense_items['budget_item_marked_for_review'] == 1 || !$budget_item_is_fully_approved) {
                                $format_row_for_review = 'format_row_for_review';
                            }
                        ?>
                            <tr class="<?= $format_row_for_review; ?>">
                                <td class='action_td' nowrap>
                                    <div class="dropdown">
                                        <button class="btn btn-default dropdown-toggle" type="button" id="menu1" data-toggle="dropdown">
                                            <?= get_phrase('action');
                                            $require_originator_action = (isset($budget_item_status_data['item_status'][$status_id]) && ($budget_item_status_data['item_status'][$status_id]['status_approval_sequence'] == 1 || $budget_item_status_data['item_status'][$status_id]['status_backflow_sequence'] == 1)) ? true : false; //$this->general_model->status_require_originator_action($status_id)
                                            ?>
                                            <span class="caret"></span></button>
                                        <ul class="dropdown-menu" role="menu" aria-labelledby="menu1">
                                            <?php
                                            // echo json_encode([$budget_item_status_data['permissions']['update'], $is_current_review, $require_originator_action]);
                                            if ($budget_item_status_data['permissions']['update'] && $is_current_review && $require_originator_action) { ?>
                                                <li><?= list_table_edit_action('budget_item', $budget_item_id); ?></li>
                                                <li class="divider"></li>
                                            <?php } ?>
                                            <?php if ($budget_item_status_data['permissions']['delete'] && $is_current_review && $require_originator_action) { ?>
                                                <li><?= list_table_delete_action('budget_item', $budget_item_id); ?></li>
                                            <?php } ?>

                                            <?php if (
                                                (!$budget_item_status_data['permissions']['update'] &&
                                                    !$budget_item_status_data['permissions']['delete']) || !$is_current_review || !$require_originator_action
                                            ) {
                                                echo "<li><a href='#'>" . get_phrase('no_action') . "</a></li>";
                                            } ?>

                                        </ul>
                                    </div>

                                    <?php

                                    $userLib = new \App\Libraries\Core\UserLibrary();

                                    if ($userLib->checkRoleHasPermissions('budget_item', 'update') && $is_current_review && $budget_item_is_fully_approved) {
                                        $mark_for_review_btn_class_color = 'success';
                                        $mark_for_review_btn_label = get_phrase('do_mark_for_review_label', 'Mark for Review');
                                        if ($loop_expense_items['budget_item_marked_for_review'] == 1) {
                                            $mark_for_review_btn_class_color = 'danger';
                                            $mark_for_review_btn_label = get_phrase('undo_mark_for_review_label', 'Unmark for Review');
                                        }
                                    ?>

                                        <div data-budget_item_id="<?= $budget_item_id; ?>" data-mark="<?= $loop_expense_items['budget_item_marked_for_review'] ?>" class="btn btn-<?= $mark_for_review_btn_class_color; ?> mark_for_review"><?= $mark_for_review_btn_label; ?></div>

                                    <?php
                                    } elseif ($loop_expense_items['budget_item_marked_for_review'] == 1) {
                                    ?>
                                        <i class="fa fa-check"></i>
                                    <?php
                                    }
                                    ?>
                                </td>
                                <td class="<?= !isset($this->session->system_settings['use_pca_objectives']) ? 'hidden' : ''; ?>">
                                    <?= $loop_expense_items['objectives'] != null ? '<b>' . get_phrase('objective') . ': </b>' . $loop_expense_items['objectives']->pca_strategy_objective_name . ' </br> <b>' . get_phrase('intervention') . ':</b> ' . $loop_expense_items['objectives']->pca_strategy_intervention_name . '' : '' ?>
                                </td>
                                <td class='td_description'><?= $loop_expense_items['description']; ?>
                                    <?= count((array)$loop_expense_items['budget_item_revisions']) > 0 ? '<i style="color:blue;" data-budget_item_id = "' . $budget_item_id . '" class = "fa fa-exchange budget_item_revisions"></i>' : ''; ?>
                                    <i style="color: <?= $loop_expense_items['message_id'] > 0 ? 'green' : 'red'; ?>" class="fa fa-comment <?= $loop_expense_items['message_id'] > 0 ? '' : 'hidden'; ?> notes" data-has_notes="<?= $loop_expense_items['message_id'] > 0 ? true : false; ?>" data-budget_item_id="<?= $budget_item_id; ?>" aria-hidden="true"></i>

                                </td>
                                <td><?= $loop_expense_items['quantity']; ?></td>
                                <td><?= number_format($loop_expense_items['unit_cost'], 2); ?></td>
                                <td><?= $loop_expense_items['often']; ?></td>
                                <td><?= number_format($loop_expense_items['total_cost'], 2); ?></td>
                                <td nowrap='nowrap'>
                                    <?php
                                    $status_id = $loop_expense_items['status']['status_id'];
                                    echo approval_action_button('budget_item', $budget_item_status_data['item_status'], $loop_expense_items['budget_item_id'], $status_id, $budget_item_status_data['item_initial_item_status_id'], $budget_item_status_data['item_max_approval_status_ids'], false, false);
                                    ?>
                                </td>
                                <?php foreach ($month_names_with_number_keys as $month_number => $month_name) {
                                    $expense_amount = isset($loop_expense_items['month_spread'][$month_number]['amount']) ? $loop_expense_items['month_spread'][$month_number]['amount'] : 0;
                                ?>
                                    <th><?= number_format($expense_amount, 2); ?></th>
                                <?php } ?>
                            </tr>
                            <tr class="revisions_rows hidden" data-budget_item_id="<?= $budget_item_id; ?>">
                                <td colspan='<?= isset($this->session->system_settings['use_pca_objectives']) ? '20' : '19'; ?>'>
                                    <table class='table table-bordered' style="border: 5px solid green;">
                                        <thead>
                                            <tr>
                                                <th colspan='<?= isset($this->session->system_settings['use_pca_objectives']) ? '22' : '21'; ?>'>
                                                    <?= get_phrase('budget_item_revision_header', 'List of budget item revision'); ?>
                                                </th>
                                            </tr>
                                            <tr>
                                                <th><?= get_phrase('revision_number'); ?></th>
                                                <th><?= get_phrase('revision_date'); ?></th>
                                                <th><?= get_phrase('revision_phase'); ?></th>
                                                <th><?= get_phrase('submitted_revision'); ?></th>
                                                <th><?= get_phrase('description'); ?></th>
                                                <th><?= get_phrase('quantity'); ?></th>
                                                <th><?= get_phrase('unit_cost'); ?></th>
                                                <th><?= get_phrase('often'); ?></th>
                                                <th><?= get_phrase('total_cost'); ?></th>
                                                <?php foreach ($month_names_with_number_keys as $month_name) { ?>
                                                    <th><?= $month_name; ?></th>
                                                <?php } ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $budget_item_revisions = (array)$loop_expense_items['budget_item_revisions'];
                                            $update_revision_number  = 1;
                                            foreach ($budget_item_revisions as $budget_item_revision) {
                                                $cnt = 0;
                                                foreach ($budget_item_revision->data as $phase => $row_data) {

                                            ?>
                                                    <tr>
                                                        <?php
                                                        if ($update_revision_number == $budget_item_revision->revision_number && $cnt == 0) {
                                                        ?>
                                                            <td rowspan='2'><?= $budget_item_revision->revision_number; ?></td>
                                                            <td rowspan='2'><?= $budget_item_revision->revision_date; ?></td>
                                                        <?php
                                                            $cnt++;
                                                        } else {
                                                            $update_revision_number++;
                                                            $cnt = 0;
                                                        }
                                                        ?>
                                                        <td><?= ucfirst($phase); ?></td>
                                                        <td><?= $budget_item_revision->locked ? get_phrase('yes') : get_phrase('no'); ?></td>
                                                        <td><?= $row_data->budget_item_description; ?></td>
                                                        <td><?= $row_data->budget_item_quantity; ?></td>
                                                        <td><?= number_format($row_data->budget_item_unit_cost, 2); ?></td>
                                                        <td><?= $row_data->budget_item_often; ?></td>
                                                        <td><?= number_format($row_data->budget_item_total_cost, 2); ?></td>
                                                        <?php foreach ($month_names_with_number_keys as $month_number => $month_name) {
                                                            $expense_amount = isset($row_data->month_spread->$month_number) ? $row_data->month_spread->$month_number : 0;
                                                        ?>
                                                            <th><?= number_format($expense_amount, 2); ?></th>
                                                        <?php } ?>
                                                    </tr>
                                            <?php
                                                }
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            <?php } ?>

        </div>
    </div>

<?php } ?>

<script>
    $(document).on('click', '.item_action', function() {
        const tr = $(this).closest('tr')
        const next_status = $(this).data('next_status');
        const max_budget_ids = JSON.parse('<?= json_encode($statusLib->getMaxApprovalStatusId('budget_item')); ?>');
        const is_next_status = max_budget_ids.filter(id => id == next_status);

        if (is_next_status > 0) {
            tr.removeClass('format_row_for_review');
        } else {
            tr.addClass('format_row_for_review');
        }

        tr.find('.action_td .dropdown ul').html("<li><a href='#'><?= get_phrase('no_action'); ?></a></li>")
    })

    $(document).on('click', ".mark_for_review", function() {
        const mark = $(this).data('mark');

        const budget_item_id = $(this).data('budget_item_id')
        const url = "<?= base_url(); ?>ajax/budget_item/markForReview/" + mark + '/' + budget_item_id
        const btn = $(this)

        let conf_message = "<?= get_phrase('mark_for_review_alert', 'Are you sure you want to mark this item for review'); ?>";

        if (mark) {
            conf_message = "<?= get_phrase('unmark_for_review_alert', 'Are you sure you want to unmark this item from being reviewed') ?>";
        }

        let cnf = confirm(conf_message)

        if (!cnf) {
            alert('Process aborted');
            return false
        } else {

            $.get(url, function(resp) {

                //console.log(parseInt(resp));

                if (resp == 1) {
                    btn.removeClass('btn-success')
                    btn.addClass('btn-danger')
                    btn.html('<?= get_phrase('undo_mark_for_review_label', 'Unmark for Review'); ?>')
                    btn.data('mark', 1)
                    btn.closest('tr').addClass('format_row_for_review');
                } else {
                    btn.removeClass('btn-danger')
                    btn.addClass('btn-success')
                    btn.html('<?= get_phrase('do_mark_for_review_label', 'Mark for Review'); ?>')
                    btn.data('mark', 0)
                    btn.closest('tr').removeClass('format_row_for_review');
                }
            })

        }



    })

    $(document).on('click', '#filter_marked_items', function() {
        const hide_state = $(this).data('hide_state');
        // alert(hide_state)
        if (hide_state == 'hide') {
            $('.table').find('.unformat_row_for_review').css('display', 'none')
            $(this).data('hide_state', 'show')
            hide_empty_tables()
        } else {
            $('.table').find('.unformat_row_for_review').removeAttr('style')
            $(this).data('hide_state', 'hide')
            show_all_tables()
        }

    })

    function hide_empty_tables() {
        const tables = $('.table')

        $.each(tables, function(i, el) {
            const count_visble_rows = $(el).find('tbody tr:visible').length;
            if (count_visble_rows == 0) {
                $(el).css('display', 'none')
            }
        })
    }

    function show_all_tables() {
        const tables = $('.table')

        tables.not(':visible').removeAttr('style')

    }

    $('tr').on('mouseover', function() {
        if ($(this).find('.notes').hasClass('hidden')) {
            $(this).find('.notes').removeClass('hidden');
        }
    })

    $('tr').on('mouseout', function() {
        if (!$(this).find('.notes').hasClass('hidden') && !$(this).find('.notes').data('has_notes')) {
            $(this).find('.notes').addClass('hidden');
        }
    })

    $('.budget_item_revisions').on('click', function() {

        let budget_item_revisions_icon = $(this)
        const budget_item_id = budget_item_revisions_icon.data('budget_item_id')

        // $('.revisions_rows').addClass('hidden')

        const revisions_row = $('tr').filter(function() {
            return $(this).data('budget_item_id') == budget_item_id;
        });

        if (revisions_row.hasClass('hidden')) {
            $('.revisions_rows').addClass('hidden')
            revisions_row.removeClass('hidden')
        } else {
            revisions_row.addClass('hidden')
        }


    })

    $('.notes').on('click', function() {
        let note_icon = $(this)
        note_area(note_icon)
    })

    function note_area(note_icon) {

        let tr = note_icon.closest('tr')
        let budget_item_id = note_icon.data('budget_item_id')
        let newRow = `
            <tr id = "noterow_${budget_item_id}"  class = "note_row">
                <td colspan="<?= isset($this->session->system_settings['use_pca_objectives']) ? '20' : '19'; ?>" style = "padding-left:25px;padding-right:25px;">
                    <div class="row">
                        <div class="col-xs-12" id = 'noteshistory_${budget_item_id}'>
                            
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-6">
                            <textarea data-message_id = "" data-message_detail_id = "" data-message_record_key = "" rows = "10" id = "notearea_${budget_item_id}" class = "form-control note_area" placeholder = "<?= get_phrase('enter_item_notes_here'); ?>"></textarea>
                        </div>
                    </div>
                    <div class ="row">
                        <div class="col-xs-12">
                            <div id = "savenote_${budget_item_id}" class="btn btn-danger save_note"><?= get_phrase('save'); ?></div>
                        </div>
                    </div>
                </td>
            </tr>`

        $('.note_row').remove()

        if (!note_icon.hasClass('show_notes')) {
            $(newRow).insertAfter(tr);
            note_icon.addClass('show_notes')
            list_notes_history(budget_item_id)
        } else if (note_icon.hasClass('show_notes')) {
            $('#noterow_' + budget_item_id).remove()
            note_icon.removeClass('show_notes')
        }
    }

    $(document).on('click', '.save_note', function() {
        //alert('Hello')
        const save_note_id = $(this).attr('id')
        const budget_item_id = save_note_id.split('_')[1];
        const note = $('#notearea_' + budget_item_id).val()
        const url = '<?= base_url(); ?>ajax/budget_item/postBudgetItemNote'
        const textarea = $('#notearea_' + budget_item_id)

        let message_record_key = textarea.data('message_record_key')
        let message_id = textarea.data('message_id')
        let message_detail_id = textarea.data('message_detail_id')

        // let note_holder = $('#notes > div.row > div[data-message_id="' + message_id + '"][data-message_record_key="' + message_record_key + '"][data-message_detail_id="' + message_detail_id + '"]')
        // note_holder.html(note)

        // return false;

        const data = {
            budget_item_id,
            note,
            update: {
                message_record_key,
                message_id,
                message_detail_id
            }
        }

        if (!$.trim(note)) {
            alert('<?= get_phrase('notes_empty', 'Budget item notes cannot be empty'); ?>')
            return false;
        }

        $.post(url, data, function(response) {

            if (response) {
                list_notes_history(budget_item_id)
                $('#notearea_' + budget_item_id).val('')
            } else {
                alert('<?= get_phrase('error_in_posting_budget_item_note'); ?>')
            }

            // Unset the data properties
            // textarea.data('message_record_key', '')
            textarea.data('message_id', '')
            textarea.data('message_detail_id', '')

        })
    })

    function list_notes_history(budget_item_id) {
        const history_url = '<?= base_url(); ?>ajax/budget_item/getBudgetItemNotesHistory/' + budget_item_id

        $.get(history_url, function(response) {
            
            $('#noteshistory_' + budget_item_id).html(response);
        })
    }
</script>