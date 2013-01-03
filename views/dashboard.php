<?php 
/**
 * @name Dashboard
 * Template for the dashboard, and conditional statements for determining what content should be 
 * loaded within it
 */
?>
<div class="wrapper">
	<section class="dashboard">
		<?php 
		if(isset($_SESSION['dashcontent']))
		{
			if($_SESSION['dashcontent'] == "shoppinglist")
			{
				include 'shopping_list.php';
			}
			else if($_SESSION['dashcontent'] == "sort")
			{
				include 'sort.php';
			}
			else if($_SESSION['dashcontent'] == "browse")
			{
				include 'browse.php';
			}
			else if($_SESSION['dashcontent'] == "edit")
			{
				include 'edit_product.php';
			}
			else
			{
				/*
				 * The list view should be set to be the default view
				*/
				include 'list.php';
			}
		}		
		else
		{
			/*
			 * The list view should be set to be the default view
			*/
			include 'list.php';
		}
		?>
	</section>
	<?php include 'toolbar.php';?>
</div>