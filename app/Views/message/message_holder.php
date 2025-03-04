<style>
    .header {
        font-weight: bold;
    }

    .tile {
        border-top: #ccc 1px solid;
        border-right: #ccc 1px solid;
        border-bottom: #777 1px solid;
        border-left: #777 1px solid;
        padding-top: 10px;
        padding-bottom: 10px;
    }
</style>
<section class="profile-feed">
    <?php
    $session = session();
    if (count($notes) == 0) {

    ?>
        <div class="well"><?= get_phrase('no_budget_item_note', 'There no note for this budget item'); ?></div>
    <?php
    } else {
    ?>
        <div id="notes" style='border: 1px solid black; padding: 30px;border-radius: 10px;'>
            <div class="row">
                <div class="col-xs-12" style="color: red;">
                    <?= get_phrase('note_validity_message', 'Notes can only be edited or deleted by owner and within 24 hours'); ?>
                </div>
            </div>

            <div class="row">
                <div class="col-xs-2 header"><?= get_phrase('action'); ?></div>
                <div class="col-xs-6 header"><?= get_phrase('note'); ?></div>
                <div class="col-xs-2 header"><?= get_phrase('owner'); ?></div>
                <div class="col-xs-1 header"><?= get_phrase('created_date'); ?></div>
                <div class="col-xs-1 header"><?= get_phrase('last_modified_date'); ?></div>
            </div>
            <?php

            foreach ($notes as $note) {
                if ($note['user_id'] != $session->get('user_id')) {
                    mark_note_as_read($session->get('user_id'), $note['message_detail_id']);
                }
            ?>
                <div class="row">
                    <div class="col-xs-2 action_btns" data-note_created_date="<?= $note['created_date']; ?>">
                        <div class="btn-group" role="group" aria-label="Basic example">
                            <div type='button' class='btn btn-danger delete_note' data-message_record_key="<?= $note['message_record_key']; ?>" data-creator_id="<?= $note['user_id']; ?>" data-message_id="<?= $note['message_id']; ?>" data-message_detail_id="<?= $note['message_detail_id']; ?>"> <?= get_phrase('delete'); ?></div>
                            <div type='button' class='btn btn-info edit_note' data-message_record_key="<?= $note['message_record_key']; ?>" data-creator_id="<?= $note['user_id']; ?>" data-message_id="<?= $note['message_id']; ?>" data-message_detail_id="<?= $note['message_detail_id']; ?>"></i> <?= get_phrase('edit'); ?></div>
                            <!-- <div type = 'button' class = "btn btn-success mark_as_read" data-message_record_key = "<?= $note['message_record_key']; ?>" data-creator_id = "<?= $note['user_id']; ?>" data-message_id = "<?= $note['message_id']; ?>" data-message_detail_id = "<?= $note['message_detail_id']; ?>"><?= get_phrase('mark_as_read'); ?></div> -->
                        </div>
                    </div>
                    <div data-message_id="<?= $note['message_id']; ?>" data-message_detail_id="<?= $note['message_detail_id']; ?>" data-message_record_key="<?= $note['message_record_key']; ?>" class="col-xs-6 body"><?= $note['body']; ?></div>
                    <div class="col-xs-2 creator"><?= $note['creator']; ?></div>
                    <div class="col-xs-1 created_date"><?= $note['created_date']; ?></div>
                    <div class="col-xs-1 created_date"><?= $note['last_modified_by'] != null ? $note['last_modified_date'] : ""; ?></div>
                </div>

                <div class="row">
                    <div class='col-xs-12'>
                        <?php
                        $notes_readers = json_decode($note['message_readers'], true);

                        if ($note['message_readers'] != NULL && count($notes_readers) > 0) {

                            echo '<i>' . get_phrase('note_read_by') . '</i>';

                            foreach (array_column($notes_readers, 'fullname') as $reader_fullname) {
                        ?>
                                <span class='label label-success'><?= $reader_fullname; ?></span>
                        <?php
                            }
                        }
                        ?>
                    </div>
                </div>

                <hr />
        <?php
            }
        }
        ?>
        </div>
</section>

<script>
    $(document).ready(function() {
        $('.delete_note, .edit_note, .mark_as_read').each(function() {
            const note_btn = $(this)
            const creator_id = $(this).data('creator_id')
            const note_created_date = $(this).closest('.action_btns').data('note_created_date')

            // Can only delete your own comment and MUST be less than 24 hours since it was created
            if ((!checkActionValidityByDate(note_created_date) || creator_id != '<?= $session->get('user_id'); ?>') && !note_btn.hasClass('mark_as_read')) {
                note_btn.addClass('disabled')
            }

            // Remove mark as read button if the comment is yours
            if (creator_id == '<?= $session->get('user_id'); ?>' && note_btn.hasClass('mark_as_read')) {
                note_btn.remove()
            }
        })
    })

    $('.mark_as_read').on('click', function() {
        // alert('Hello')
    })

    $('.delete_note').on('click', function() {
        const delete_note = $(this)
        const message_detail_id = $(this).data('message_detail_id')
        const message_id = $(this).data('message_id')

        //Create data
        let data = {
            'message_detail_id': message_detail_id,
            'message_id': message_id
        }


        /**
         * const url = '<?= base_url(); ?>ajax/message/deleteNote/' + message_id + '/' + message_detail_id;

         This deleteNote() function in 'message' controller refused to work and giving an error: controller or method is not found: "deleteNote
         //Not sure why
         */


        const url = '<?= base_url(); ?>ajax/budget_item/deleteNote/' + message_id + '/' + message_detail_id;
        // const cnfm = custom_confirm('confirm_note_deletion', 'Are you sure you want to delete this note?', confirmResponse).then(res => alert(res))

        let cnfm = confirm('<?= get_phrase('confirm_note_deletion', 'Are you sure you want to delete this note?'); ?>');

        if (!cnfm) {
            alert('<?= get_phrase('process_aborted'); ?>')
            return false;
        }

        $.post(url, data, function(response) {

            alert(response)
            delete_note.closest('.row').remove()
        });
    })

    $(document).on('click', '.edit_note', function() {

        let btn = $(this)
        let action_btns = btn.closest('.action_btns')
        let body = action_btns.siblings('.body').text()

        let message_record_key = btn.data('message_record_key')
        let message_id = btn.data('message_id')
        let message_detail_id = btn.data('message_detail_id')

        let notearea = $('#notearea_' + message_record_key);

        if (notearea.data('message_record_key') != message_record_key) {
            notearea.data('message_record_key', message_record_key)
        }

        if (notearea.data('message_id') != message_id) {
            notearea.data('message_id', message_id)
        }

        if (notearea.data('message_detail_id') != message_detail_id) {
            notearea.data('message_detail_id', message_detail_id)
        }

        // alert(notearea.data('message_id'))

        notearea.val(body)

    })

    $('.note_area').on('keyup', function() {
        const notes = $('#notes')
        const note_area_id = $(this).attr('id')
        const message_record_key = note_area_id.split('_')[1]
        const notearea_message_id = $(this).data('message_id')

        // let note_holder = $('#notes > div.row > div[data-message_id="' + notearea_message_id + '"][data-message_record_key="' + message_record_key + '"]')

        // if(note_holder){
        // note_holder.html($(this).val())
        // }

    })

    function checkActionValidityByDate(note_date) {
        let updateActionValid = false;
        // Get the current date and time
        var currentDate = new Date();

        // Parse the given date string (yyyy-mm-dd)
        var givenDate = new Date(note_date); // Replace with your given date

        // currentDate.setHours(0, 0, 0, 0);
        // givenDate.setHours(0, 0, 0, 0);
        // alert(givenDate + ' = ' +  currentDate)

        // Calculate the time difference in milliseconds
        var timeDifference = currentDate - givenDate;

        // Calculate the number of milliseconds in 1 day
        var oneDayInMilliseconds = 24 * 60 * 60 * 1000;

        // Compare if the given date is older than 1 day
        if (timeDifference < oneDayInMilliseconds) {
            // console.log("The given date is older than 1 day.");
            updateActionValid = true;
        }
        // else {
        // console.log("The given date is not older than 1 day.");
        // }

        return updateActionValid;
    } 
    </script>

