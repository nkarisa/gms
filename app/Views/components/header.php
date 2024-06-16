
<style>
	#fav_menu_items  ul {
		list-style-type: none;
		margin: 0;
		padding: 0;
		overflow: hidden;
  		background-color: #333;
	}

	#fav_menu_items  ul li {
		display: inline;
		float: left;
	}

	#fav_menu_items ul li a {
		display: block;
		color: white;
		text-align: center;
		padding: 14px 16px;
		text-decoration: none;
	}

	#fav_menu_items ul li a:hover {
		background-color: #111;
	}
</style>

<div class="row">
	<div class="col-md-12 col-xs-12 clearfix hidden-print">
		<div id="fav_menu_items">
			<!-- Your favorite menu will be displayed here -->
		</div>
	</div>
</div>

<div class="row hidden-print">
	<!-- Raw Links -->
	<div class="col-md-6 col-sm-8 clearfix">

        <ul class="list-inline links-list pull-left">
        <!-- Language Selector -->
           <li class="dropdown language-selector">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" data-close-others="true" style='text-decoration:none;'>
                        <img src='<?=base_url();?>images/uploads/user_icons/<?=$user_icon;?>' class='img-circle' style='border:2px gray solid;' width='40px'/> 
							<div class='label label-primary'><?php echo ucfirst($name);?></div>
							
                    </a>

					<?php if($primary_user_data_id && $user_id != $primary_user_data_id){?>
						<div style="cursor:pointer;" id="btn_restore_user" class='label label-danger'><?=get_phrase('restore_to');?>  <?php echo ucfirst($this->session->primary_user_data['user_name']);?></div>
					<?php }?>


				<ul class="dropdown-menu <?php if ($text_align == 'right-to-left') echo 'pull-right'; else echo 'pull-left';?>">
					<li>
						<a href="<?php echo base_url();?>user/view/<?=$user_id;?>">
                        	<i class="fa fa-user"></i>
							<span><?php echo get_phrase('your_profile');?></span>
						</a>
					</li>

					<?php if($user_can_read_switch || $primary_user_data_id){?>
					<li>
						<a href="<?php echo base_url();?>user_switch/list">
                        	<i class="fa fa-toggle-on"></i>
							<span><?php echo get_phrase('switch_user');?></span>
						</a>
					</li>
					<?php }?>


				</ul>


			</li>
        </ul>

	</div>

	<div class="col-md-6 col-sm-4 clearfix hidden-xs">
				<ul class="list-inline links-list pull-right">
					<!-- Language Selector -->
					<li class="dropdown language-selector">
		
						<?=get_phrase('language');?>: &nbsp;
						<a href="#" class="dropdown-toggle" data-toggle="dropdown" data-close-others="true">
							<?php 

								$count_language_found = 0;

								foreach($user_available_languages as $language){
									if($language['language_code'] == $user_locale){
										echo get_phrase(strtolower($language['language_name']));

										$count_language_found++;
									}
								}

								if($count_language_found == 0){
									echo get_phrase(strtolower($default_language['language_name']));
									echo " (";
									echo get_phrase('force_language_change', 'Forced Language Change');
									echo ")";
								}
								
							?>
						</a>
		
						<ul class="dropdown-menu pull-right" >
						
							<?php foreach($user_available_languages as $language){?>
								<li class = "<?=$language['language_code'] == $user_locale ? 'active' : ''?>">
									<a href="#" class = "language_selector" id = "<?=$language['language_code'];?>" >
										<span><?=get_phrase(strtolower($language['language_name']));?></span>
									</a>
								</li>
							<?php }?>
							
						</ul>
		
					</li>
		
				</ul>
		
			</div>

</div>

<hr class="hidden-print" style="margin-top:0px;" />

<script>
	$("#btn_restore_user").on('click',function(){
		const url = "<?=base_url();?>login/switch_user/<?=$primary_user_data_id ? $primary_user_data_id : 0;?>";
		location.href = url;
	});

	$(document).ready(function () {
		
		const url = "<?=base_url();?>menu_user_order/get_favorite_menu_items"

		$.get(url, function (response) {
			const items = JSON.parse(response);
			// console.log(items);
			create_favorite_menu_items(items.item_list);
		});
	
	})

	$(".language_selector").on('click', function () {
		const lang = $(this).attr('id');
		const url = "<?=base_url();?>language/switch_language/" + lang;

		$.get(url, function (resp) {
			alert(resp);

			window.location.reload();
		});
	})
</script>
