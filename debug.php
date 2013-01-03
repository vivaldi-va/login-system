<?php
	include_once('header.php');

?>

<div class="wrapper">
	<section class="debug">
		<?php 
		if(isSet($_SESSION['debug_info']))
		{
			echo "<h1>Debug Info:</h1>\n";
			echo $_SESSION['debug_info'];
		}
		?>
	</section>

</div>