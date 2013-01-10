<?php 
/**
 * @name List Manager
 * Template for the list manager tool */
?>

<header class="dashboard-context-bar">
		<!-- context-sensitive buttons for the dashboard -->
		
		<form class="form-horizontal sort-list" method="GET" action="index.php">
			<input type="hidden" name="page" value="sort">
			<input type="hidden" value="<?php echo $_SESSION['sortLocation'];?>" name="location" class="sort-location-val">
			<button class="btn btn-primary sort-list-submit" type="submit">go shopping!</button>
		</form>	
		
		<div class="toolbar-product-search toolbar-set">
			<input type="text" class="toolbar-product-search-form" placeholder="Search...">
			<div class="well well-small search-results">
			</div>
		</div>
	</header>
	<div class="dashboard-content">
		<!-- Content for the dasbboard, such as the list etc. -->
		<div class="dashboard-list-container">
			<!-- Container for the list, to apply fixed width to control size -->
			<?php $session->theShoppingList();?>
		</div>
	</div>