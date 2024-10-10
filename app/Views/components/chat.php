<style>
	.profile-feed {
		margin-bottom: 45px;
	}

	.post-type {
		margin-top: 10px;
	}

	#message_slider {
		margin: 30px 25px 10px 25px;
	}

	.chat_post {
		background-color: whitesmoke;
		padding: 15px;
		border-radius: 8px;
		margin-bottom: 5px;
	}
</style>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

<section class="profile-feed">

	<?php echo form_open("", array('id' => 'frm_chat', 'class' => 'form-horizontal form-groups-bordered validate', 'enctype' => 'multipart/form-data')); ?>

	<textarea id="chat_message" name="message_detail_content" class="form-control autogrow" placeholder="What's on your mind?"></textarea>

	<div class="form-options">

		<div class="post-type">

			<a href="#" class="tooltip-primary" data-toggle="tooltip" data-placement="bottom" title="" data-original-title="Upload a Picture">
				<i class="entypo-camera"></i>
			</a>

			<a href="#" data-toggle="popover" data-contentwrapper=".mycontent" class="tooltip-primary" id="attachment_clip">
				<i class="entypo-attach"></i>
			</a>

			<a href="" id="show_comments" class="tooltip-primary" data-toggle="tooltip" data-placement="bottom" title="" data-original-title="Show comments">
				<i class="fa fa-bars"></i>
			</a>

			<div type="button" id="post_comment" class="btn btn-primary pull-right">POST</div>
		</div>


	</div>
	</form>

	<!-- Upload Place -->
	<div class='col-xs-8'>

		<div class='col-xs-3 hidden form-group' id='upload_documents'>
			<table>
				<tbody>

					<tr>
						<td>
							<!-- Dropzone  For receipts -->
							<div style="margin-bottom:20px;">
								<form id="drop_receipts" class="dropzone">
									<div class="fallback">
										<input id="receipt_upload_area" name="file" type="file" multiple />
									</div>
								</form>
							</div>
						</td>
					</tr>
				</tbody>

			</table>

		</div>

		<!-- Uploaded files-->
		<div class='col-xs-4 hidden form-group' id='uploaded_files'>
			<table id='uploaded_documents'>


				<tbody>
					<!-- populate the table here -->
				</tbody>

			</table>

		</div>
	</div>



	<div id='message_slider' class="hidden">

	</div>

</section>

<script>
	//Delete Documents

	$(document).on('click', '.delete_attachment', function() {

		let id = $(this).attr('id');

		var url = '<?= base_url() . $controller ?>/delete_uploaded_document/' + id;

		$.post(url, function(response) {

			if (response == 1) {

				alert('Document Deleted');

				var attachment_url = '<?= base_url() . $controller ?>/get_uploaded_S3_documents/';

				$.get(attachment_url, function(re) {

					//Draw html table and populate it with uploaded docs from S3
					let uploads = JSON.parse(re);

					var tes='<?=$controller?>';
					//draw_and_populate_table(uploads);
					draw_and_populate_table(uploads, tes);

				});

			} else {
				alert('Deletion Failed');
			}
		});

	});



	//...............................................................

	//Dropzone
	//Ajax to upload to AWS S3
	var myDropzone = new Dropzone('#drop_receipts', {


		url: '<?= base_url() . $controller ?>/upload_documents_for_any_feature',
		paramName: "file",
		params: {

		},
		maxFilesize: 50, // MB
		uploadMultiple: true,
		parallelUploads: 5,
		maxFiles: 5,
		acceptedFiles: 'image/*,application/pdf',
	});
	myDropzone.on("complete", function(file) {
		myDropzone.removeAllFiles();
	});

	myDropzone.on('error', function(file, response) {
		console.log(response);
	});
	myDropzone.on("success", function(file, response) {

		console.log(JSON.parse(response));

		let response_obj = JSON.parse(response);

		if (response == 0) {
			alert('Error in uploading files');
			return false;
		} else {

			//Get the attachment_id
			let attachment_primary_id = '';

			

			$.each(response_obj, function(index, elem) {

				attachment_primary_id = elem.attachment_primary_id;

			});

			//console.log(attachment_primary_id);

			if (attachment_primary_id != 0) {

				var attachment_url = '<?= base_url() . $controller ?>/get_uploaded_S3_documents/' + attachment_primary_id;

				$.get(attachment_url, function(res) {

					//Draw html table and populate it with uploaded docs from S3

					let uploaded_documents = JSON.parse(res);

					let controler='<?= $controller; ?>';

					console.log(uploaded_documents);

					//draw_and_populate_table(uploaded_documents);
					draw_and_populate_table(uploaded_documents,controler);
				});


			} else {
				alert('Some errors occured when uploading your documents');
			}

		}

	});

	$(document).ready(function() {
		Dropzone.autoDiscover = false;
	});

	//Upload Area
	$('#attachment_clip').on('click', function() {


		//get uploaded documents
		var attachment_url = '<?= base_url() . $controller ?>/get_uploaded_S3_documents/';

		$.get(attachment_url, function(re) {

			//Draw html table and populate it with uploaded docs from S3

			let uploded_docs = JSON.parse(re);

			//console.log(uploded_docs);

			// draw_and_populate_table(uploded_docs);

			let controler='<?= $controller; ?>';

			draw_and_populate_table(uploded_docs, controler);


		})

		//Show uploaded docs and dropzone area
		let upload_documents_div = $('#upload_documents');
		if (upload_documents_div.hasClass('hidden')) {
			upload_documents_div.removeClass('hidden');
		} else {
			upload_documents_div.addClass('hidden')
		}

		let uploaded_files_div = $('#uploaded_files');

		if (uploaded_files_div.hasClass('hidden')) {
			uploaded_files_div.removeClass('hidden');
		} else {
			uploaded_files_div.addClass('hidden')
		}

		//Modify the attacheme to hide
		if ($(this).children('i').hasClass('entypo-attach')) {
			$(this).children('i').removeClass('entypo-attach');
			$(this).children('i').addClass('entypo-eye');
		} else {
			$(this).children('i').removeClass('entypo-eye');
			$(this).children('i').addClass('entypo-attach');
		}
	})

	$(document).ready(function() {
		let previous_chats = $("#previous_chats").clone();
		//previous_chats.removeClass('hidden');
		$("#message_slider").append(previous_chats.html());
	});

	$("#frm_chat a").on('click', function(ev) {
		ev.preventDefault();
	});

	$("#show_comments").on('click', function() {
		$("#message_slider").toggleClass('hidden');
	});

	$("#post_comment").on('click', function() {

		let chat_message = $('#chat_message').val();
		let url = "<?= base_url() . $controller; ?>/post_chat";
		let data = {
			'message_detail_content': chat_message,
			'item_id': '<?= $item_id; ?>'
		};

		$.ajax({
			url: url,
			type: "POST",
			data: data,
			success: function(response) {

				let obj = JSON.parse(response);
				let message = obj.message;
				let message_date = obj.message_date;
				let creator = obj.creator;

				$("#message_slider").prepend(chat_post(message, message_date, creator));
				$('#chat_message').val(null);
			}
		});
	});

	function draw_and_populate_table(uploded_docs) {

		var rebuild_table_original_before_uploads = '';

		let table_id_for_uploads = $('#uploaded_documents tbody');

		table_id_for_uploads.html('');



		var url = '<?= base_url() . $controller ?>/get_current_status_of_item';
		$.get(url, function(response) {

			var disable = ''
			if (response == -1) {
				disable = 'disabled';
			}

			//Build the table
			if (uploded_docs.length == 0) {

				rebuild_table_original_before_uploads = rebuild_table_original_before_uploads + '<div style="color:green;"> <h3> No Uploads to view.</h3></div>';

			} else {

				rebuild_table_original_before_uploads = '<tr><td nowrap width="100%"> <h4><u>Delete File</u></h4> </td> <td nowrap width="100%"><h4><u>File Name</u></h4> </td></tr>';

				$.each(uploded_docs, function(i, e) {
					//Rebuiding table with new uplaoded documents

					rebuild_table_original_before_uploads = rebuild_table_original_before_uploads + '<tr><td ><i id=' + e.attachment_id +  ' class="btn  fa fa-trash delete_attachment aria-hidden="true" '+  disable+'></i></td><td ><a target= "__blank" href=' + e.attachment_url + '>' + e.attachment_name + '</a></td></tr>'

				});
			}

			return table_id_for_uploads.html(rebuild_table_original_before_uploads);

		});




	}

	function chat_post(message, message_date, creator) {
		let new_post = $("#chat_post").clone();
		new_post.removeAttr('id');
		new_post.removeClass('hidden');
		new_post.find('.message_holder').html(message);
		new_post.find('.timestamp').find('div').append(message_date);
		new_post.find('.user').find('div').append(creator);
		new_post.addClass('chat_post');

		return new_post;
	}
</script>

<div id='chat_post' class="row hidden">

	<div class='col-xs-7 message_holder'>

	</div>

	<div class="col-xs-2 user">
		<div class='pull-right'><i class='fa fa-user '> &nbsp; </i></div>
	</div>

	<div class="col-xs-2 timestamp">
		<div class='pull-right'><i class='fa fa-clock-o'></i> </div>
	</div>

	<div class="col-xs-1 icon_holder">
		<i class='fa fa-pencil pull-right'></i>

		<i class='fa fa-trash pull-right'></i>
	</div>
</div>


<div id='previous_chats' class='hidden'>

	<?php
	if (isset($chat_messages)) {
		foreach ($chat_messages as $chat_message) {
	?>
			<div class='row chat_post'>
				<div class='col-xs-7'>
					<?= $chat_message['message']; ?>
				</div>

				<div class="col-xs-2 user">
					<div class='pull-right'><i class='fa fa-user '></i> &nbsp; <a target='__blank' href='<?= base_url(); ?>user/view/<?= hash_id($chat_message['author'], 'encode'); ?>'><?= $this->CI->user_model->get_user_full_name($chat_message['author']); ?></a></div>
				</div>

				<div class="col-xs-2 timestamp">
					<div class='pull-right'><i class='fa fa-clock-o '></i> <?= date('jS M Y h:i:s', strtotime($chat_message['message_date'])); ?></div>
				</div>

				<div class="col-xs-1 icon_holder">
					<i class='fa fa-pencil pull-right'></i>

					<i class='fa fa-trash pull-right'></i>
				</div>
			</div>
	<?php
		}
	}
	?>
</div>