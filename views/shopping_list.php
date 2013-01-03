<?php 
/**
 * @name List
 * Template for the shopping list view
 */
?>

<header class="dashboard-context-bar">
	<!-- context-sensitive buttons for the dashboard -->
	
	<form class="form-horizontal sort-list">
	<button class="btn btn-primary sort-list-submit" type="submit">Sort List!</button>
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
		<?php $product_functions->browseSearch();?>
	</div>
</div>