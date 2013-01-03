<div class="context-bar">
	<?php 
	if(isset($_GET['page']))
	{
		if($_GET['page'] == "product_info")
		{
		?>
		
		<?php	
		}
		else
		{
		?>
		<form action="index.php" method="get" id="product-search">
			<input type="search" name="searchterm" class="product-search-term">
			<input type="hidden" name="page" value="search">
			<button type="submit" class="product-search-submit">search</button>
		</form>	
		<?php	
		}
		
	}
	
	else
	{
	?>
	<form action="index.php" method="get" id="product-search">
		<input type="search" name="searchterm" class="product-search-term">
		<input type="hidden" name="page" value="search">
		<button type="submit" class="product-search-submit">search</button>
	</form>
	<?php
	}
	?>
	
</div>