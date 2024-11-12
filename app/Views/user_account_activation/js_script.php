<script>
    let userAccountActivationArr = [];

    $(document).on('change', '#select_chkbox', function (event) {
        userAccountActivationArr = [];
        //Check all checkboxes
        let chkBoxes = $('.form-check-input');
        if ($(this).is(":checked")) {
            //Show Bulk activate/reject button CheckBoxes
            $('#activate_all').removeClass('hidden');
            $('#reject_all').removeClass('hidden');

            $.each(chkBoxes, function (i, el) {
                $(el).prop('checked', true);
                //Populate array
                let userToActivateId = $(el).prop('id');
                let id = userToActivateId.split('_')[1];
                userAccountActivationArr.push(id);
            });
        } else {
            //Hide button and unmark CheckBoxes
            $('#activate_all').addClass('hidden');
            $('#reject_all').addClass('hidden');

            $.each(chkBoxes, function (i, el) {
                $(el).prop('checked', false);
                userAccountActivationArr = [];
            });
        }
        // console.log(userAccountActivationArr);
    });

    function bulkUserActivationOrRejection(bulkUrl, message) {
        //Check uncheccked values and remove from array before deleting
        let chkBoxes = $('.form-check-input');
        $.each(chkBoxes, function (i, elem) {

            if (!$(elem).is(':checked')) {
                let userToActivateId = $(elem).prop('id');
                let id = userToActivateId.split('_')[1];

                //Remove values that are diselected by unchecked chkboxes
                userAccountActivationArr = $.grep(userAccountActivationArr, function (value) {
                    return value != id;
                });

            } else {
                $(elem).closest('tr').remove();
            }

        });

        //Now Loop userAccountActivationArr as u you update user, context and department user
        if (userAccountActivationArr.length > 0) {
            let data = {};
            $.each(userAccountActivationArr, function (index, el) {
                //Pass data to either  reject or activate users
                if (bulkUrl == 'activate_new_user_account') {
                    data = {
                        userIdToActivate: el,
                    }
                } else {
                    data = {
                        rejectedUserId: el,
                    }
                }

                let url = "<?= base_url() ?>ajax/user_account_activation/" + bulkUrl;

                $.post(url, data, function (updateDataResponse) {

                });
                //Check if last index to show message
                var isLastElement = index == userAccountActivationArr.length - 1;

                if (isLastElement) {
                    alert(userAccountActivationArr.length + message);

                }

            });
        }
    }

    //Bulk activation of users
    $(document).on('click', '#activate_all', function () {
        let confirmUserActivation = confirm("Are you sure you want to activate " + userAccountActivationArr.length + " new users");
        if (confirmUserActivation) {
            bulkUserActivationOrRejection('activateNewUserAccount', ' New users have been activated & can access Safina');
        } 

    });

    //Bulk rejection of users
    $(document).on('click', '#reject_all', function () {
        let confirmUserRejection = confirm("Are you sure you want to reject activating " + userAccountActivationArr.length + " new users");
        if (confirmUserRejection) {
            bulkUserActivationOrRejection('rejectActivatingNewUserAccount', ' New users have been rejected & can not access Safina');
        } 
    });

    //Show Bulk action button if atleast individual chkbox is selected other hide

    $(document).on('click', '.form-check-input', function () {
        if ($(this).is(':checked')) {
            let userToActivateId = $(this).prop('id');
            let id = userToActivateId.split('_')[1];
            userAccountActivationArr.push(id);

        } else {

            //Get the selected ID and remove it from array
            let userToActivateId = $(this).prop('id');

            let id = userToActivateId.split('_')[1];

            const index_to_remove = userAccountActivationArr.indexOf(id);

            userAccountActivationArr.splice(index_to_remove, 1);
        }
        //Check if array is empty
        if (userAccountActivationArr.length != 0) {
            $('#activate_all').removeClass('hidden');
            $('#reject_all').removeClass('hidden');

        } else {
            $('#activate_all').addClass('hidden');
            $('#reject_all').addClass('hidden');

            //Uncheck the checkbox of selecting all other checkboxes
            $('#select_chkbox').prop('checked', false);
        }
        console.log(userAccountActivationArr);

    });

    //Activate User

    $(document).on('click', '.btn-success', function () {


        let confirmUserActivation = confirm("Are you sure you want to activate new user");

        if (confirmUserActivation) {
            //get the button Id property

            let idPropertValue = $(this).prop('id');

            //Get the record of idPropertValue in db which will help pull the fk_user_id from the user_activation_account table
            let recordId = idPropertValue.split('_')[1];

            let data = {
                userIdToActivate: recordId,
            }

            let url = "<?= base_url() ?>ajax/user_account_activation/activateNewUserAccount/";

            $.post(url, data, function (updateDataResponse) {

                if (parseInt(updateDataResponse) == 1) {

                    alert('User Activated and Ready to use Safina');

                } else {
                    alert('User Not activated contact system administrator or developer');
                }

            });
            //Remove the row
            $(this).closest('tr').remove();

        } else {
            //do nothing
        }


    });


    //Reject activating User

    $(document).on('click', '.btn-danger', function () {

        //get the button Id property and split to get the actual id in table e.g 15

        let idPropertValue = $(this).prop('id');

        let recordId = idPropertValue.split('_')[1];

        //check if the refect resean is provided

        const rejectReasonId = '#rejectreason_' + recordId;

        let rejectReason = $(rejectReasonId).val();

        let userRejectionReason = '';

        //Unhide the select dropdown for reject reason
        $(rejectReasonId).removeClass('hidden');
        $(rejectReasonId).css('border', '2px dotted red');

        if (rejectReason != 0) {

            userRejectionReason = $(rejectReasonId + " option:selected").text();

        } else {
            alert('Provide the rejection reason');

            return false;
        }


        let userRejectActivation = confirm("Are you sure you want to reject activating new user");

        if (userRejectActivation) {


            let data = {
                rejectedUserId: recordId,
                rejectReason: userRejectionReason
            }

            let url = "<?= base_url() ?>ajax/user_account_activation/rejectActivatingNewUserAccount/";

            $.post(url, data, function (deleteDataResponse) {

                console.log(JSON.parse(deleteDataResponse));

                if (parseInt(deleteDataResponse) == 1) {

                    alert('User Has been removed and Will Never Access Safina');

                } else {
                    alert('An Error Occurred Contact System Administrator or Developer');
                }

            });
            //Remove the row
            $(this).closest('tr').remove();

        } else {
            //Do nothing
        }

    });

    //Remove dotted lines on change of the rejectReason dropdown
    $(document).on('change', '.form-control', function () {

        $(this).css('border', '');

    });
</script>