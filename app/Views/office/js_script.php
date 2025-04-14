<?php
$uri = service('uri');
$segments = $uri->getSegments();
$action = isset($segments[1]) ? $segments[1] : 'list';
?>
<script>

  $(document).ready(function () {
    const action = '<?= isset($action) ? $action : 'list'; ?>'
    if (action == 'list') {
      document.getElementById("defaultOpen").click();
    }

    if (action == 'edit') {
      $('#fk_office_id').prop('readonly', 'readonly')

      const urlAccountCondition = '<?= base_url(); ?>ajax/office_bank/validateOfficeBankAccount/<?= hash_id($id, 'decode'); ?>'
      getRequest(urlAccountCondition, function (response) {
        const has_account_balance = response.has_account_balance;
        const has_active_cheque_book = response.has_active_cheque_book;
        if (has_account_balance) {
          $('#office_bank_is_active').closest('.form-group').addClass('hidden');
        }
        if (has_active_cheque_book) {
          $('#office_bank_book_exemption_expiry_date').closest('.form-group').addClass('hidden');
        }
      })
    }

  })

  function openPage(context_definition_id, elmnt, color) {
    let i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tabcontent");
    for (i = 0; i < tabcontent.length; i++) {
      tabcontent[i].style.display = "none";
    }

    tablinks = document.getElementsByClassName("tablink");

    for (i = 0; i < tablinks.length; i++) {
      tablinks[i].style.backgroundColor = "";
    }

    elmnt.style.backgroundColor = color;

    let setContextDefinitionId = localStorage.getItem("context_definition_id");

    if (context_definition_id == 1) {
      $("#select_cluster_to_move").removeClass('hidden');
    } else {
      $("#select_cluster_to_move").addClass('hidden');
    }

    if(setContextDefinitionId != context_definition_id){
      load_datatable({ context_definition_id })
    }

  }

    function initializeListCustomData() {
    return { context_definition_id: 1 }
  }

  $('#cluster').on('change', function () {
    var move_fcps_btn = $('#click_move_fcps');
    if ($(this).val() != 0) {
      move_fcps_btn.removeAttr('disabled');
    } else {
      move_fcps_btn.attr('disabled', 'disabled');
    }
  });

  $('#click_move_fcps').on('click', function () {
    let message = '<?= get_phrase('confirm_change_of_office_cluster') ?>';
    let office_ids = get_office_ids();

    //Check if checkbox is empty
    if (office_ids.length == 0) {
      alert('<?= get_phrase('select_atleast_one_office_alert') ?>');
      return false;
    }

    //Update fcp to clusters
    if (confirm(message) == true) {
      let url = '<?= base_url(); ?>ajax/office/massUpdateForFcps';
      const context_definition_id = 1
      data = {
        'cluster_office_id': $('#cluster').val(),
        'office_ids': get_office_ids(),
      }

      $.post(url, data, function (res) {
        if (res.message) {
          load_datatable({context_definition_id })
        }
      });

    }

  });

  //Check or uncheck 
  function check_or_uncheck_checkbox() {
    $(document).on("click", ".checkbox", function (event) {
      if ($(this).is(":checked")) {
        $(this).attr('checked', true);
      } else {
        $(this).attr('checked', false);
      }
    });
  }

  function get_office_ids() {
    // Populate the office ids of checked checkboxes
    var office_ids = [];

    $('.checkbox').each(function () {
      if ($(this).is(":checked")) {
        let office_id = $(this).attr("id");
        office_ids.push(office_id);
      }
    });

    return office_ids

  }

  $("#fk_context_definition_id").on('change', function () {
    $(".btn-save,.btn-save-new").removeClass('disabled');

    let url = "<?= base_url("ajax/office/responsesForContextDefinition"); ?>";
    let data = {
      'context_definition_id': $(this).val()
    };

    $.ajax({
      url: url,
      data: data,
      type: "POST",
      success: function (obj) {
        $("#div_office_context").html(obj.office_context);
        $("#div_office_context").find('select').removeClass('select2');
        $("select").select2();
      }
    });
  });

  $(".btn-save,.btn-save-new").on('click', function (ev) {
    if (validate_form() == true) {
      alert('Complete the required fields');
      return false;

    }

    var url = "<?= base_url(); ?>office/singleFormAdd";
    var data = $("#frm_office").serializeArray();
    var btn = $(this);

    $.ajax({
      url: url,
      type: "POST",
      data: data,
      success: function (response) {
        alert(response.message);
        if (btn.hasClass('btn-save')) {
          location.href = document.referrer
        } else {
          reset_form();
        }
      }
    });

    ev.preventDefault();
  });

  $(".btn-reset").on('click', function (ev) {
    reset_form();

    ev.preventDefault();
  });

  //Validate the inputs before posting
  function validate_form() {
    any_field_empty = false;
    //$('#office_context').select2('data');
    var data = $(document).find(".select2 option:selected").text();
    $("[required=required]").each(function () {
      if ($(this).val().trim() == '') {
        $(this).css('border-color', 'red');
        any_field_empty = true;
      }

    });

    return any_field_empty;
  }


  function reset_form() {
    $('input').val(null);
    $("#fk_context_definition_id").val(null).attr('selected', true);
    $("#fk_account_system_id").val(0).prop('selected', true);
    $("#office_description").val(null);
    $("#office_context").empty().prop('disabled', 'disabled');
    $('#unit').val('21');
  }


  $(document).on('click', '.suspend', function () {
    const btn = $(this)
    const suspension_status = btn.data('suspension_status');
    const office_id = btn.data('office_id')
    const data = { office_id, suspension_status }
    const url = '<?= base_url(); ?>ajax/office/suspend_office'

    const cnf = confirm('<?= get_phrase('confirm_suspension', 'Are you sure you want to perform this action?'); ?>');

    if (!cnf) {
      alert('<?= get_phrase('process_aborted'); ?>');
      return false;
    }

    $.post(url, data, function (response) {
      // console.log(response);
      if (response.flag) {
        btn.removeClass('btn-success')
        btn.addClass('btn-danger')
        btn.html('Suspend')
        btn.data('suspension_status', 0)
      } else {
        btn.addClass('btn-success')
        btn.removeClass('btn-danger')
        btn.html('Unsuspend')
        btn.data('suspension_status', 1)
      }
    })
  })


  function onchange_fk_context_definition_id(elem) {

  }

  function onchange_office_context(elem) {

  }

  function onchange_fk_account_system_id(elem) {

  }

</script>