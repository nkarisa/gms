<?php
	$system_name        =	$this->db->get_where('setting' , array('type'=>'system_name'))->row()->description;
	$system_title       =	$this->db->get_where('setting' , array('type'=>'system_title'))->row()->description;
	$text_align         =	$this->db->get_where('setting' , array('type'=>'text_align'))->row()->description;
	//$account_type       =	$this->session->userdata('login_type');

	$skin_colour        =   $this->db->get_where('setting' , array('type'=>'skin_colour'))->row()->description;
	?>
<!DOCTYPE html>
<html lang="en" dir="<?php if ($text_align == 'right-to-left') echo 'rtl';?>">
<head>

	<title><?php echo $page_title;?> | <?php echo $system_title;?></title>

	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<meta name="description" content="" />
	<meta name="author" content="" />


	<?php include 'includes_top.php';?>

</head>
<body class="page-body <?php if ($skin_colour != '') echo 'skin-' . $skin_colour;?> page-fade-only">
	<div class="page-container horizontal-menu">
		<?php include 'navigation.php';?>
		<div class="main-content">

			<?php include 'header.php';?>

					<div class="row">
						<div class="col-xs-12">
							<h5 style="" class="pull-left">
						       <!-- <i class="entypo-right-circled"></i> -->
									<?php include 'breadcrumb.php'?>
						    </h5>

						  		<div class="btn-group pull-right">
						  			<button class="btn btn-default" title="<?=get_phrase('back');?>" onclick="javascript:go_back();"><i class="fa fa-backward"></i></button>
						  			<button class="btn btn-default" title="<?=get_phrase('forward');?>" onclick="javascript:go_forward();"><i class="fa fa-forward"></i></button>
						  		</div>
						  </div>


					</div>

				<hr />
				<div class="page-content">
		           <!--Showing Progress GIF. Must be available in evert form-->
					<?php 
						if(file_exists(APPPATH."views/".$views_dir.'/'.$page_name.'.php')){
							include APPPATH."views/".$views_dir.'/'.$page_name.'.php';
						}else{
							include APPPATH."views/".$views_dir.'/error.php';
						}
						
					?>
				</div>
			<?php include 'footer.php';?>

		</div>
		<?php //include 'chat.php';?>

	</div>
		
		<?php
			
			include 'includes_bottom.php';
			include 'modal.php';

			if(file_exists(VIEWPATH.$this->controller.DS.$this->session->user_account_system.DS.'js_script.php')){
				include VIEWPATH.$this->controller.DS.$this->session->user_account_system.DS.'js_script.php';
			}elseif(file_exists(VIEWPATH.$this->controller.DS.'js_script.php')){
					include VIEWPATH.$this->controller.DS.'js_script.php';
			}elseif(
				file_exists(VIEWPATH.'templates'.DS.$this->action.'_script.php')
				&& file_exists(VIEWPATH.$this->controller.DS.'js_script.php')
			){
				include VIEWPATH.'templates'.DS.$this->action.'_script.php';
				include VIEWPATH.$this->controller.DS.'js_script.php';
			}
			
			if(file_exists(VIEWPATH.'templates'.DS.$this->action.'_script.php')){
				include VIEWPATH.'templates'.DS.$this->action.'_script.php';
			}
		?>

</body>
</html>
