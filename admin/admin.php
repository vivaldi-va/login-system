<?php
	include("../include/session.php");
	include("../include/product_functions.php");
	include("../header.php");
	?>
	
		<section>
			<header><h1>admin dashboard</h1></header>
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
					include_once("../include/database.php");
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
						
						$userListString .= "<section>\n<header>\n<h1>user list</h1>\n</header>\n";
						
						if(!$result || mysql_num_rows($result) == 0)
						{
							$userListString = "No users found or query failed.";
						}
						else 
						{
							while($dbArray = mysql_fetch_assoc($result))
							{
								$userListString .= "<article>\n
														<header><h2>".$dbArray['id'].": ".$dbArray['email']."</h2></header>
														<dl>\n
															<dt>name\n
																<dd>".$dbArray['name']."\n
															<dt>user-level\n
																<dd>".$dbArray['userLevel']."\n
															<dt>date created\n
																<dd>".$dbArray['dateCreated']."\n
															<dt>last online\n
																<dd>".$dbArray['lastLoginDate']."\n
														</dl>\n
													</article>\n";
							}
							$userListString .= "</section>\n";
							echo $userListString;
						}
					}
					
					
					
				?>
				
					<section>
						<header>
							<h1>Product settings:</h1>
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
						<?php getUserList();?>
					</section>
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
		</section>
	</div>