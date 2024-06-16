
	$(".submit").click(function(ev){ 
		var action  = $(this).closest("form").attr("action");
		var data = $(this).closest("form").serializeArray();
		$(".inner-progress").html('');
		$.ajax({
			url:action,
			data:data,
			type:"POST",
			beforeSend:function(){
				$(".inner-progress").html('<div style="text-align:center;margin-top:0px;"><img src="assets/images/preloader.gif" /></div>');
			},
			success:function(response){
				$(".inner-progress").html('');
				jQuery('#modal_ajax').modal('hide');		
				$(".page-content").html(response);
				
			},
			error:function(){
				
			}
		});		
		
		ev.preventDefault();
	});	