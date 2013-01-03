<?php 
	//include("header.php"); 
?>

<div class="wrapper">
	<header class="app-header">
		<h1>ostos nero</h1>
	</header>
	<section class="list-wrapper">
		<header>
		</header>
		<div class="list-body" id="shopping-list">
			<?php /*IF has list items, add them all with a FOR loop, using a template 
					ELSE add a simple text notification and a drop shadow below the header
				*/?>
			<?php
			require("addToList.php");
			/* 


			$user_name = "root";
			$password = "";
			$database = "ostosnero_test";
			$server = "localhost";

			$db_handle = mysql_connect($server, $user_name, $password);
			$hasDb = mysql_select_db($database);
			
			$result = mysql_query
			(
				"SELECT users.firstname, shoppingLists.name AS ShoppingListName, products.name AS ProductName FROM users, shoppingLists, shoppingListProducts, products WHERE users.id = shoppingLists.userID
				AND products.id = shoppingListProducts.productID
				AND users.id = 1"
			)
			or die (mysql_error());
			
			if($result)
			{
				while ($row = mysql_fetch_assoc($result))
				{
					echo('
					<article class="list-item">' .
						'<header>' .
							'<h2>' . strtolower($row['ProductName']) . '</h2>' .
						'</header>' .
						
					'</article>');
				}
				mysql_close($db_handle);
			}
			else
			{
				print("Database not found");
				mysql_close($db_handle);

			} */
			?>
			
		</div>
		<div id="item-search" class="add-item-search">
			<form id="add-to-list-form" class="ui-widget" method="GET">
				<input type="search" id="add-item-dialogue" name="search" placeholder="Add An Item">
			</form>
			<div id="item-search-results" class=""></div>
		</div>
		<footer class="list-footer">
			<div class="button add-item-open">add item</div>
			<div class="button sort-list-button">sort list</div>
		</footer>
	</section>
	<section id="add-item-pane" class="secondary">
		<header>
			<h1>add item</h1>
			<a href="#" class="button add-item-close">close</a>
		</header>
		
		
		<!--<form class="add-item-form" method="GET" >-->
			<div class="add-item-form">
				<div class="input">
					<label for="productName">product name</label>
					<input type="text" name="productName" id="productName" placeholder="Product Name">
				</div>
				<div class="input">
					<label for="categorySelect" required>category</label>
					<select name="categorySelect" id="categorySelect" placeholder="Category" required>
						<option value="1">ATERIA-AINEKSET JA KASTIKKEET</option>
					</select>
				</div>
				<div class="input">
					<label for="brandName">brand name</label>
					<input type="text" name="brandName" id="brandName" placeholder="Brand Name">
				</div>
				<div class="input">
					<label for="volume">volume:</label>
					<input type="number" name="volume" id="volume">
					<label for="volumeType">volume type</label>
					
					<label for="volumeTypeKG">g/kg</label>
					<input type="radio" name="volumeType" class="volume-type-radio" value="1">
					<label for="volumeTypeL">ml/l</label>
					<input type="radio" name="volumeType" class="volume-type-radio" value="2">
					<!--<label for="volumeTypeQuant">quantity</label>
					<input type="radio" name="volumeType" id="volumeTypeQuant" value="quantity">-->
				</div>
				<div class="input">
					<label for="price">price:</label>
					<input type="number" name="price" id="price" placeholder="price (€)">
				</div>
				<div class="input">	
					<label for="chain">chain:</label>
					<select id="chain" name="chain" placeholder="Chain">
						<option value="3">K-Market</option>
						<option value="1">K-Citymarket</option>
						<option value="4">K-Supermarket</option>
						<option value="7">S-Market</option>
						<option value="2">Prisma</option>
						<option value="5">Anttila</option>
						<option value="6">Alepa</option>
					</select>
				</div>
				<div class="input">
					<label for="location">location</label>
					<input type="text" name="location" id="location" placeholder="Store Location">
					<div class="location-auto-complete"></div>
				</div>
				<button class="add-item-submit">Add it</button>
			</div>
			
		<!--</form>-->
	</section>
</div><!-- end .wrapper -->
<?php include("footer.php"); ?>