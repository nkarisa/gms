<script>
  $('.form-control').keydown(function() {
    $(this).removeAttr('style');
  });

  $(".save, .save_new").on('click', function(ev) {

    let elem = $(this);
    let empty_fields_count = 0;
    let code_field_or_element = '';

    $('.form-control').each(function(i, el) {
      let code_elem = $(el).attr('id');
      let code_str_exist = code_elem.split('_');

      if(code_str_exist.includes('code')){
        code_field_or_element = code_elem;
      }

      if ($(el).hasClass('select2') && $(el).is('select')) {
        if (!$(el).val() && $(el).attr('required')) {
          $(el).closest('div').css('border', '1px solid red');
          empty_fields_count++;
        } else {
          $(el).closest('div').removeAttr('style');
        }

      } else {
        if ($(el).val().trim() == '' && $(el).attr('required')) {
          $(el).css('border', '1px solid red');
          empty_fields_count++;
        }
      }

    })

    if (empty_fields_count > 0) {
      alert('1 or more required fields are empty');
      return false;
    } else {
      pre_record_post();

      let url = "<?= base_url(implode("/", $uri->getSegments()));?>"
      let data = $(this).closest('form').serializeArray();
      
      postRequest(
        url,
        data,
        function(response) {
          if (response.flag == false) {
            $('#'+code_field_or_element).css('border', '1px solid red')
            alert(response.message)
            return false;
          } 
          else {

            on_record_post()
            alert(response.message);
            //If Save , use the browser history and go back
            if (elem.hasClass('back')) {
              if (typeof alt_referrer === 'undefined') {
                  location.href = document.referrer
              }else{
                  window.location.replace(alt_referrer + '/' + response.header_id + '/' + response.table);
              }
            } else {
              document.getElementById('add_form').reset();
            }
            
          }
          
        }
      );

      post_record_post();
    }

    ev.preventDefault();
  });


</script>