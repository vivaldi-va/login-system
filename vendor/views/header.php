<?php 
	//include_once 'session.php';
?>
<!doctype html>
<!--[if lt IE 7]> <html class="no-js ie6 oldie" lang="en"> <![endif]-->
<!--[if IE 7]>    <html class="no-js ie7 oldie" lang="en"> <![endif]-->
<!--[if IE 8]>    <html class="no-js ie8 oldie" lang="en"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" lang="en"> <!--<![endif]-->
<head>
  <meta charset="utf-8">
  <meta http-equiv="Content-type" content="text/html; charset=utf-8">
 <!-- <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"> --> 

  <title>Ostos Nero | <?php echo $pageName?></title>
  <meta name="description" content="">
  <meta name="author" content="">

  <meta name="viewport" content="width=device-width,initial-scale=1">

  <!-- CSS concatenated and minified via ant build script-->
  
  <link rel="stylesheet" href="./css/bootstrap.css">
  <link rel="stylesheet" href="./css/bootstrap-responsive.css"> 
  <link rel="stylesheet" href= "./style.css">
  <?php 
  if(!$session->logged_in)
  {
  	// Style overrides to show the login screen
  ?>
  	<link rel="stylesheet" href= "./login-screen.css">
  <?php 
  }		
  ?>
  <link rel="stylesheet" href="chosen/chosen.css" />
  <link href='http://fonts.googleapis.com/css?family=Open+Sans:400,700italic,400italic,700,600,300,600italic' rel='stylesheet' type='text/css'>
  <!-- end CSS-->

  <script src="<?php echo DIR_SITE_HOME;?>js/libs/modernizr-2.0.6.min.js"></script>
</head>

<body>
<?php if($session->logged_in){?>
	<div class="wrapper">
		<?php // Header Bar?>
		<header class="header-bar">
			<h1 class="app-title">ostos nero</h1>
			
			<span>Page Name</span>
			
			<div class="user-info">
				<span class="user-name">username</span>
				<div class="user-dropdown-button"></div>
				<div class="user-dropdown">
					<div class="user-menu">
						
					</div>
				</div><!-- .user-dropdown -->
			</div><!-- .user-info -->
		</header>
	</div><!-- .wrapper -->
	<?php }?>