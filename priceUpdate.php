<?php
	/**
	 * priceUpdate.php
	 * 
	 * Page that houses the form to update a particular product, selected
	 * from the main shopping list (or elsewhere)
	 */
	
	include ("header.php");
	//$_SESSION['prodEditProductID'] = $_POST['prodEditProductID'];
	$productID = $_SESSION['prodEditProductID'];

	//$productID = $_SESSION['prodEditProductID'];
	
	
	$productName = $product_functions->getProductNameFromID($productID);
	
	
?>


<div class="wrapper">
	<section>
		<header>
			<h1></h1>
		</header>
		<section class="price-change-container">
			<header>
				<hgroup>
					<h2>add or update price:</h2>
					<h3><?php if(!empty($productName)){ echo $productName;}else{echo "can not retrieve product name";}?></h3>
				</hgroup>
			</header>
			<form class="form-horizontal price-change-form">
				<label for="price">price:
					<input type="number" pattern="^[0-9]+([.,][0-9]{2})?$" name="price" class="input-large span2 price" placeholder="Eg. 12,34">
				</label>
				<div class="control-group shop-search-group">
					<label for="shopID">shop:
						<input type="text" name="shopSearch" class="input-large shop-search" placeholder="Shop Location">
					</label>
					<input type="hidden" name="shopID" class="shop-id-value" value="-1">
					<div class="search-results"></div>
					
					<input type="hidden" name="subupdateprice" value="1">
				</div>
				<button type="submit" class="btn span2">Update Price</button>
			</form>
		</section>
		
		<section>
			<header>
				<hgroup>
					<h2>add a new store:</h2>
					<h3>know a store that we havent added? help us expand our database and add it here.</h3>
				</hgroup>
			</header>
			<form method="POST" action="process.php">
				<fieldset>
					<legend>required:</legend>
					<input type="hidden" name="subaddshop" value="1">
					
					<input type="text" name="city" class="input-large" placeholder="City (eg. Helsinki)" required>
					<input type="text" name="location" class="input-large" placeholder="Location (eg. Viikki)" required>
					<label for="chainID">chain:
					<select name="chainID" class="input-large">
						<option value="3">K-Market
						<option value="1">K-Citymarket
						<option value="4">K-Supermarket
						<option value="7">S-Market
						<option value="2">Prisma
						<option value="5">Anttila
						<option value="6">Alepa
					</select>
					</label>
					<button type="submit" class="btn span3">Add New Shop</button>
				</fieldset>
				<fieldset>
					<legend>optional:</legend>
					<input type="text" name="address" placeholder="Address">
					<input type="number" name="coords">
					<input type="submit" value="Add new store">
				</fieldset>
				
			</form>
		</section>
	</section>
</div>
<footer>
	</footer>
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
	<script>window.jQuery || document.write('<script src="js/libs/jquery-1.7.1.min.js"><\/script>')</script>
	<script type="text/javascript" src="./js/vis.js"></script>
	
</body>
</html>