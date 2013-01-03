<?php
	include("./include/session.php");
	include("./include/product_functions.php");
	include("./header.php");
	?>
	
		
			<?php
			/**
			 * Check if user has logged on
			 */
			if($session->logged_in)
			{
				/**
				 * Check if user has an admin user-level
				 */
				if($session->isAdmin())
				{
					include_once("./include/database.php");
					/*
					 * Admin Functions
					 */
					
					
					
					/**
					 * Get all users from the database, and display them in a formatted
					 * list
					 * 
					 */
					function getUserList()
					{
						global $database;
						
						$userListString = "";
						$q = "SELECT
					 			".TBL_USERS.".id AS id,
				 				".TBL_USERS.".email AS email,
					 			".TBL_USERS.".firstname AS name,
								".TBL_USERS.".userlevel AS userLevel,
								".TBL_USERS.".created AS dateCreated,
								".TBL_USERS.".last_login_date AS lastLoginDate
				 				FROM ".TBL_USERS."
					 			ORDER BY ".TBL_USERS.".id";
						$result = $database->query($q);
						
						echo "<section>\n<header>\n<h1>user list</h1>\n</header>\n";
						
						if(!$result || mysql_num_rows($result) == 0)
						{
							$userListString = "No users found or query failed.";
						}
						else 
						{
							echo "<table class=\"table table-striped\">";
							echo "
								<thead>
								<tr>
								<th>email</th>
								<th>name</th>
								<th>user level</th>
								<th>date created</th>
								<th>last online</th>
								</tr>
								</thead>
								<tbody>";
							while($dbArray = mysql_fetch_assoc($result))
							{
								$userListString .= "
										<tr>\n
											<td>".$dbArray['id'].": ".$dbArray['email']."</td>
											<td>".$dbArray['name']."</td>\n
											<td>".$dbArray['userLevel']."</td>\n
											<td>".$dbArray['dateCreated']."</td>\n
											<td>".$dbArray['lastLoginDate']."</td>\n
										</tr>\n";
							}

							echo $userListString;
							echo "</tbody>";
							echo "</table>";
							echo "</section>\n";
						}
					}
					
					
					
				?>
				<div class="wrapper">
					<section class="row-fluid">
						<header>
							<h1>admin console</h1>
							
							<h2>to do:</h2>
							<ul>
								<li>add list of current categories
								<li>add list of brands
								<li>add number of products
								<li>add number of price entries
								<li>add forms to add new brands, categories and other things
								<li>add graphs to show stats on users and products etc.
								<li>add controls to change user settings and choose user-levels
							</ul>
						</header>
						<div class="span6">
							<h2>add new brand</h2>
							<form class="admin-add-brand">
								<div class="control-group">
									<label class="control-label" for="admin-brandname-input">brand name:</label>
									<input class="input admin-brandname-input" name="admin-brandname-input" type="text" placeholder="Brand Name">
								</div>
								<button type="submit" class="btn">Add Brand</button>
							</form>
						</div>
					</section>
					<section class="row-fluid"> 
						<div class="span6">
							<h2>add new category</h2>
							<form>
								<div class="control-group">
									<label class="control-label" for="admin-cat-parent">Parent Category:</label>
									<select class="admin-cat-parent" name="admin-cat-parent">
										<option value="-1">Work in Progress
									</select>
								</div>
								<div class="control-group">
									<label class="control-label" for="admin-cat-name">Category Name</label>
									<input class="input admin-cat-name" name="admin-cat-name" type="text" placeholder="Category Name">
								</div>
								<button type="submit" class="btn">Add Category</button>
							</form>
						</div>
					</section>
					<section class="row-fluid"> 
						<div class="span6">
							<h2>add shop</h2>
							<form>
								<label for="admin-shop-location">Location</label>
								<input type="text" id="admin-shop-location" name="admin-shop-location" class="input">
								
								<label for="admin-shop-chain">Chain</label>
								<select id="admin-shop-chain" name="admin-shop-location">
									<option>Chains go here eventually
								</select>
								
								<label for="admin-shop-address">address</label>
								<input type="text" id="admin-shop-address" class="input">
								
								<label for="admin-shop-city">city</label>
								<input type="text" id="admin-shop-city" class="input">
								
								<label for="admin-shop-country">country</label>
								<input type="text" id="admin-shop-country" class="input" value="finland">
								
								<label for="admin-shop-coords">coords</label>
								<input type="text" id="admin-shop-coords" class="input">
							</form>
						</div>
						
						<div class="span6">
							<h2>add chain</h2>
							<form>
								<label for="admin-chain-name">Name</label>
								<input type="text" id="admin-chain-name" class="input"> 
							</form>
						</div>
					</section>
					<section class="row-fluid"> 
						<div class="span6">
							<article>
								<h2>number of products added:</h2>
								<?php echo $database->getNumProducts();?>
							</article>
							<article>
								<h2>number of prices added:</h2>
								<?php echo $database->getNumPrices();?>
							</article>
						</div>
						<div class="span6">
							<article>
								<h2>number of shops:</h2>
							</article>
						</div>
					</section>
						<?php getUserList();?>
				</div>
				<?php 
				}
				else 
				{
					echo "<span class=\"error\">your user-level is not high enough to view this area</span>\n
				 			<a href=\"$session->referrer\">Go back</a>";
				}
			}
			else
			{
				header("Location: main.php");
			}
			

			?>
			<?php include("footer.php");?>