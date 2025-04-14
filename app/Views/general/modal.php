<style>
    .menu_tab.active {
        border: 5px white solid;
        border-radius: 2px;
    }
</style>
<script>

    $(document).ready(function () {
        $(".navbar-nav").find(".<?= $uri->getSegments()[0]; ?>").addClass("active");
    });

    function getRequest(url, on_success) {
        $.post({
            url: url,
            beforeSend: function () {
                $("#overlay").css("display", "block");
            }
        }).done(function (response) {
            on_success(response);
        }).fail(function (xhr, status, error) {
            alert('Error has occurred');
        }).always(function () {
            $("#overlay").css("display", "none");
        });
    }


    function postRequest(url, data, on_success) {
        $.post({
            url: url,
            data: data,
            beforeSend: function () {
                $("#overlay").css("display", "block");
            }
        }).done(function (response) {
            on_success(response);
        }).fail(function (xhr, status, error) {
            alert('Error has occurred');
        }).always(function () {
            $("#overlay").css("display", "none");
        });
    }

    function format_number(amount) {
        let put_commas_on_amount = amount.toString().split(".");
        put_commas_on_amount[0] = put_commas_on_amount[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        // $('#bank_balance').val(put_commas_on_amount.join('.'));
        return put_commas_on_amount.join('.');
    }

    function unformat_number(number) {
        return number.split(",").join("");
    }

    function pre_record_post() { }

    function on_record_post() { }

    function post_record_post() {
        $('select.select2:not([multiple])').each(function () {
            let firstOptionValue = $(this).find('option:first').val();
            $(this).val(firstOptionValue).trigger('change');
        });
    }

    function pre_row_insert() { }

    function on_row_insert() { }

    function post_row_insert() { }

    function pre_row_delete() { }

    function on_row_delete() { }

    function post_row_delete() { }


    $('.datatable').DataTable({
        dom: 'lBfrtip',
        "bDestroy": true,
        buttons: [
            'copyHtml5',
            'excelHtml5',
            'csvHtml5',
            'pdfHtml5',
        ],
        "pagingType": "full_numbers",
        'stateSave': true
    });


    // Use in the list page approval button e.g. in budget schedule
    $(document).on('click', '.item_action', function () {

        let item_id = $(this).data('item_id');
        let next_status = $(this).data('next_status');
        let current_status = $(this).data('current_status');
        let table = $(this).data('table');
        let voucher_missing_details = $(this).data('voucher-missing-details');
        let url = "<?= base_url(); ?>" + table + "/update_item_status/" + table;
        let data = {
            'item_id': item_id,
            'next_status': next_status,
            'current_status': current_status,
        };

        if (voucher_missing_details == 1) {
            alert("<?= get_phrase('voucher_missing_detail_msg', 'Voucher is missing Details and will be directed to edit screen!'); ?>");
            return false;
        } else {
            let cnf = confirm('Are you sure you want to perfom this action?')
            if (!cnf) {
                alert('<?= get_phrase('action_aborted', 'Action aborted'); ?>')
                return false;
            }
        }

        sendApprovalButtonRequest($(this), data, url);
    });

    function sendApprovalButtonRequest(button, data, url) {
        let td = button.parent();
        $.post(url, data, function (response) {
            button.parent().html(response);
            button.addClass('disabled');
            button.toggleClass('btn-info', 'btn-success');
            button.siblings().remove();
            button.closest('tr').find('.action_td .dropdown ul').html("<li><a href='#'><?= get_phrase('no_action'); ?></a></li>");

            try {
                datatable.draw();
            } catch (e) {
                if (e instanceof ReferenceError) {
                    console.log('DataTable API not set')
                }
            }
            //get uploaded documents
            let attachment_url = '<?= base_url() .'ajax/'. $controller ?>/getUploadedS3Documents/';
            $.get(attachment_url, function (re) {
                //Draw html table and populate it with uploaded docs from S3
                let uploded_docs = JSON.parse(re);
                let controler = '<?= $controller; ?>';
                draw_and_populate_table(uploded_docs, controler);
            });
        });
    }


    function create_favorite_menu_items(items) {
        let elements = '<ul>';
        $.each(items, function (i, elem) {
            elements += "<li><a href='<?= base_url(); ?>" + i + "/list'>" + elem + "</a></li>"
        })
        elements += '</ul>';
        $("#fav_menu_items").html(elements);
    }


    function load_datatable(customFields) {
    let datatable;
    const url = "<?= base_url(); ?><?= $controller; ?>/showList";
    if (typeof customFields === 'object') {
      $(".datatable").dataTable().fnDestroy();
      datatable = $("#datatable").DataTable({
        dom: 'lBfrtip',
        "bDestroy": true,
        buttons: [
          'copyHtml5',
          'excelHtml5',
          'csvHtml5',
          'pdfHtml5',
        ],
        pagingType: "full_numbers",
        stateSave: true,
        pageLength: 10,
        order: [],
        serverSide: true,
        processing: true,
        language: { processing: 'Loading ...' },
        ajax: {
          url: url,
          type: "POST",
          data: function (d) {
            d.customData = customFields
          },
          complete: function (resp) {
            if (resp.readyState == 4) {
              const customData = resp.responseJSON.customData
              if (typeof customData == 'object') {
                const len = Object.keys(customData).length
                if (len) {
                  for (const [key, value] of Object.entries(customData)) {
                    localStorage.setItem(key, value);
                  }
                }
              }
            }
          }
        }
      });
    } else {
      console.log('Argument supplied must be an object');
    }

    return datatable
  }

  let customData = typeof initializeListCustomData === "function" ? initializeListCustomData() : {}
  let url = "<?= base_url(); ?><?= $controller; ?>/showList";
  let datatable = load_datatable(customData)

  $("#datatable_filter").html(search_box());


  function search_box() {
    return '<?= get_phrase('search'); ?>: <input type="form-control" onchange="search(this)" id="search_box" aria-controls="datatable" />';
  }

  function search(el) {
    datatable.search($(el).val()).draw();
  }
</script>