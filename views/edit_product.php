<?php 
/**
 * @name Product Edit
 * Template for the list manager tool */

$pageName = "Edit Product";
?>

<header class="dashboard-context-bar">
		<!-- context-sensitive buttons for the dashboard -->
</header>
<div class="dashboard-content">
	<!-- Content for the dasbboard, such as the list etc. -->
	<div class="dashboard-list-container">
		<!-- Container for the list, to apply fixed width to control size -->
		<?php 
		if(isset($_GET['prodid']))
		{
		?>
			<section class="edit-product">
				<h2><?php echo $product_functions->getProductDetails($_GET['prodid'], "name");?></h2>
				<form class="edit-product-form">
					<fieldset>
					<legend>Required</legend>
					<label for="edit-product-name">product name</label>
					<input type="text" class="input edit-product-name" name="edit-product-name" value="<?php echo $product_functions->getProductDetails($_GET['prodid'], "name"); ?>">
					
					<label for="edit-product-brand">brand name</label>
					<input type="text" class="input edit-product-brand" name="edit-product-brand" value="<?php echo $product_functions->getProductDetails($_GET['prodid'], "brand"); ?>">
					<div class="well well-small search-results edit-product-brand-results"></div>
					
					<label for="edit-product-cat">categories</label>
					<select class="edit-product-cat" name="edit-product-cat" placeholder="<?php echo $product_functions->getProductDetails($_GET['prodid'], "category"); ?>">
						<?php 
								//To be filled with ajax (hopefully)
								$product_functions->categoryList(false, $_GET['prodid']);
						?>
					</select>
					
					<label for="edit-product-name">EAN code</label>
					<input type="number" class="input edit-product-barcode" name="edit-product-barcode" value="<?php echo $product_functions->getProductDetails($_GET['prodid'], "barcode"); ?>">
					
					<input type="hidden" class="edit-product-id" value="<?php echo $_GET['prodid']; ?>">
					<button type="submit" class="btn btn-block btn-primary">Update Info</button>
					</fieldset>
					
					<fieldset>
						<legend>Optional</legend>
						
						<span class="input-title">product picture</span>
						<img src="<?php echo DIR_IMAGES; echo $product_functions->getProductDetails($_GET['prodid'], "pic"); ?>" alt="product picture" class="product-pic">
						<input type="file" class="input edit-product-pic">
						
						<label for="edit-product-description">product description</label>
						<textarea class="edit-product-description" name="edit-product-description"><?php echo $product_functions->getProductDetails($_GET['prodid'], "description"); ?></textarea>
					</fieldset>
				</form>
			</section>
		
		<?php
		}
		else 
		{
			echo "<div class=\"alert alert-info\">Something went wrong, I don&rsquo;t have anything to edit</div>";
		}
		?>
	</div>
</div>