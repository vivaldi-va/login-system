<?php
include_once '../include/vendor.php';
include_once '../include/product_functions.php';
include_once '../include/session.php';
include 'views/header.php';

$pageName = "Vendor Application";
?>

<?php 
	if($session->logged_in)
	{
?>

	<div class="wrapper">
		<?php include 'views/context-bar.php';?>
		
	</div>

<?php 
		if(isset($_GET['page']))
		{
			
			switch ($_GET['page'])
			{
				case "search":
					include 'views/search-results.php';
					break;
				case "product-info":
					include 'views/product-info.php';
			}
		}
		else
		{
			include 'views/app-home.php';
		}
	}
	else
	{
	?>
		<div class="wrapper login-screen">
			<div class="login-logo"></div>
			
			<form action="../process.php" method="post" class="login-form">
				<input type="email" name="email" class="login-input login-email" placeholder="email">
				
				<input type="password" name="pass" class="login-input login-pass" placeholder="password">
				<a href="#" class="login-forgot-pass">forgot password?</a>
				
				<label class="checkbox-label">
					remember me?
					<input type="checkbox" name="remember" class="login-remember">
				</label>
				<input type="hidden" name="sublogin" value="1">
				<input type="hidden" name="vendordemo" value="1">
				<button type="submit">log in</button>
			</form>
		</div>
	<?php
	}
	include 'views/footer.php';
?>