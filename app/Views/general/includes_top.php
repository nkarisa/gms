<?php 
	$settingsModel = new App\Models\Core\SettingModel();
	$settings = $settingsModel->all();
	$skin_colour = $settings['skin_colour'];
	$text_align = $settings['text_align'];
?>
<!--Datatables CSS CDNs-->
<link rel="stylesheet" href="https://cdn.datatables.net/1.10.15/css/jquery.dataTables.min.css" />
<!--<link rel="stylesheet" href="https://cdn.datatables.net/1.10.12/css/dataTables.bootstrap.min.css" /> -->
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/1.3.1/css/buttons.dataTables.min.css" />

<!--Jquery CDN Minified -->
<script src="https://code.jquery.com/jquery-2.2.4.min.js"
	integrity="sha256-BbhdlvQf/xTY9gja0Dq3HiwQF8LaCRTXxZKRutelT44=" crossorigin="anonymous"></script>

<!--Datatables JS CDNs-->
<script src="https://cdn.datatables.net/1.10.15/js/jquery.dataTables.min.js"></script>

<!--Bootstrap JS CDNs-->
<script src="https://stackpath.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"
	integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa"
	crossorigin="anonymous"></script>

<!--Datatables Buttons JS CDNs-->
<script src="https://cdn.datatables.net/buttons/1.3.1/js/dataTables.buttons.min.js"></script>
<script src="//cdn.datatables.net/buttons/1.3.1/js/buttons.flash.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="//cdn.datatables.net/buttons/1.3.1/js/buttons.html5.min.js"></script>
<script src="//cdn.datatables.net/buttons/1.3.1/js/buttons.print.min.js"></script>

<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/font-icons/font-awesome/css/font-awesome.min.css">

<link rel="stylesheet" href="<?php echo base_url(); ?>assets/js/jquery-ui/css/no-theme/jquery-ui-1.10.3.custom.min.css">
<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/font-icons/entypo/css/entypo.css">
<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/neon-core.css">
<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/neon-theme.css">
<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/neon-forms.css">
<script type="text/javascript" src="//cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/1.3.1/css/buttons.dataTables.min.css">

<?php
if ($skin_colour != ''): ?>
	<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/skins/<?php echo $skin_colour; ?>.css">
<?php endif; ?>

<?php if ($text_align == 'right-to-left'): ?>
	<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/neon-rtl.css">
<?php endif; ?>

<!--[if lt IE 9]><script src="assets/js/ie8-responsive-file-warning.js"></script><![endif]-->

<!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
<!--[if lt IE 9]>
		<script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
		<script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
<![endif]-->
<link rel="shortcut icon" href="<?php echo base_url(); ?>assets/images/favicon.png">
<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/font-icons/font-awesome/css/font-awesome.min.css">

<link rel="stylesheet" href="<?php echo base_url(); ?>assets/js/vertical-timeline/css/component.css">
<link rel="stylesheet" href="<?php echo base_url(); ?>assets/js/datatables/responsive/css/datatables.responsive.css">

<!--Dropzone-->
<link rel="stylesheet" href="<?php echo base_url(); ?>assets/js/dropzone/dist/min/dropzone.min.css">

<script src="<?php echo base_url(); ?>assets/js/dropzone/dist/min/dropzone.min.js" type="text/javascript"></script>

