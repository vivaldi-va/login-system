<?php 
require ('./include/constants.php');
include("header.php");?>

<?php if($session->logged_in){?>
<!-- Body Content -->
<?php 


if(isset($_GET['page']))
{
	$_SESSION['dashcontent'] = $_GET['page'];
	if(DEBUG_MODE){
		$_SESSION['debug_info'] .= "<p>get var set @ index.php</p>\n";
	}
}
else
{
	if(DEBUG_MODE){
		$_SESSION['debug_info'] .= "<p>get not set, unsetting the session var @ index.php</p>\n";
	}
	unset($_SESSION['dashcontent']);
}

include 'views/dashboard.php';
?>


<?php }else{?>
<?php 
?>

<div class="wrapper login-screen">
	<div class="login-logo"></div>
	
	<form action="process.php" method="post" class="demo-login-form">
		<input type="email" class="login-input login-email" placeholder="email">
		
		<input type="password" class="login-input login-pass" placeholder="password">
		<a href="#" class="login-forgot-pass">forgot password?</a>
		
		<label class="checkbox-label">
			remember me?
			<input type="checkbox" name="remember" class="login-remember">
		</label>
		<input type="hidden" value="marko.lauhiala@saunalahti.fi" name="email">
		<input type="hidden" value="zxcvbnm" name="pass">
		
		<input type="hidden" value="1" name="sublogin">
		<button type="submit">log in</button>
	</form>
</div>
<?php }?>

<?php include("footer.php");?>
