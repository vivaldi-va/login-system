<?php 
	include_once ("include/session.php");
	include_once ("include/product_functions.php");
	include_once ("include/form.php");
	include_once 'include/constants.php';
	
	$pageName = "Genius Shopper";
?>

<!doctype html>
<!--[if lt IE 7]> <html class="no-js ie6 oldie" lang="en"> <![endif]-->
<!--[if IE 7]>    <html class="no-js ie7 oldie" lang="en"> <![endif]-->
<!--[if IE 8]>    <html class="no-js ie8 oldie" lang="en"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" lang="en"> <!--<![endif]-->
<head>
  <meta charset="utf-8">
  <meta http-equiv="Content-type" content="text/html; charset=utf-8"><!-- ISO-8859-1 -->
 <!-- <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"> --> 

  <title>Ostos Nero | <?php echo $pageName?></title>
  <meta name="description" content="">
  <meta name="author" content="">

  <meta name="viewport" content="width=device-width,initial-scale=1">

  <!-- CSS concatenated and minified via ant build script-->
  
  <link rel="stylesheet" href="./css/bootstrap.css">
  <link rel="stylesheet" href="./css/bootstrap-responsive.css"> 
  <link rel="stylesheet" href= "./style.css">
  <link rel="stylesheet" href="chosen/chosen.css" />
  <link href='http://fonts.googleapis.com/css?family=Open+Sans:400,700italic,400italic,700,600,300,600italic' rel='stylesheet' type='text/css'>
  <!-- end CSS-->

  <script src="./js/libs/modernizr-2.0.6.min.js"></script>
</head>

<body>

	<div class="wrapper">
		<div class="navbar">
			<div class="navbar-inner">
				<div class="container">
				 
					 
					<!-- Be sure to leave the brand out there if you want it shown -->
					<a class="brand" id="home-link" href="index.php">Ostos Nero</a>
					
					   <?php if(!$session->logged_in){?>
					    <ul class="nav">
							<li class="active">
								<a href="#">Home</a>
							</li>
							<li><a href="#">Link</a></li>
							<li><a href="#">Link</a></li>
							
						</ul>
						
						<a href="register.php" class="btn btn-primary pull-right">Join Now</a>
						<?php }?>
						<!-- Login Form -->
						<?php 
						if($session->logged_in)
						{?>
						<div class="btn-group pull-right login-form">
							<!-- <button class="btn">User Name</button> -->
							<button class="btn dropdown-toggle" data-toggle="dropdown">
								<?php echo $session->userName;?>
								<span class="caret"></span>
							</button>
							<ul class="dropdown-menu mobile-show-dropdown">
								<li><?php echo "<a href=\"userinfo.php?user=$session->email\">My Account</a>\n" ?>
								<li><?php echo "<a href=\"useredit.php\">Edit Account</a>\n"; ?>
								<?php if($session->isAdmin()){?>
								<li><?php echo "<a href=\"admin.php\">Admin Centre</a>\n"; ?>
								<?php }?>
								<li><?php echo "<a href=\"process.php\">Logout</a>\n"; ?>
							</ul><!-- .dropdown-menu -->
						</div><!-- .btn-group -->
						<div class="location-form">
							<input type="text" id="primary-location" class="input pull-right" value="<?php if(isset($_SESSION['sortLocation'])){echo $_SESSION['sortLocation'];}?>" placeholder="Your Location">
							<div class="well well-small input-dropdown">
								<ul class="input-dropdown-group">
									<li>sello
								</ul>
							</div>
						</div>
						<?php }else{?>
						<div class="btn-group pull-right login-form">
							<!-- <button class="btn">User Name</button> -->
							<button class="btn dropdown-toggle" data-toggle="dropdown">
								Login
								<span class="caret"></span>
							</button>
							<div class="login-dropdown-form mobile-show-dropdown">
								<form class="navbar-form form-horizontal pull-right login-form">
									<div class="control-group">
										<label class="control-label" for="login-email">email</label>
										<div class="controls">
											<input class="input login-email" name="login-email" type="email" value="<?php echo $form->value("email"); ?>" placeholder="example@ostosnero.com">
										</div>
									</div>
									<div class="control-group">
										<label class="control-label" for="login-pass">password</label>
										<div class="controls">
											<input class="input login-pass" name="login-pass" type="password" value="<?php echo $form->value("pass"); ?>" placeholder="password">
										</div>
									</div>
									<div class="control-group">
										<div class="controls">
											<label class="checkbox">
												<input class="login-remember" type="checkbox" <?php if($form->value("remember") != ""){ echo "checked"; } ?>>remember me
											</label>
										</div>
									</div>
									<div class="control-group">
										<div class="controls">
											<button type="submit" class="btn btn-info">Sign In</button>
										</div>
									</div>
									<input type="hidden" class="form-token" value="<?php echo $_SESSION['form-token'];?>">
								</form>
							</div><!-- .dropdown-menu -->
						</div><!-- .btn-group -->
						<?php }?>
						
				</div><!-- .container -->
			</div><!-- .navbar-inner -->
		</div><!-- .navbar -->
	</div>	<!-- .wrapper -->
	
	<!-- /header -->