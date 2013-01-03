<?php
/**
 * Main.php
 *
 * This is an example of the main page of a website. Here
 * users will be able to login. However, like on most sites
 * the login form doesn't just have to be on the main page,
 * but re-appear on subsequent pages, depending on whether
 * the user has logged in or not.
 *
 * Written by: Jpmaster77 a.k.a. The Grandmaster of C++ (GMC)
 * Last Updated: August 26, 2004
 */
include("include/session.php");
include("include/product_functions.php");
include("header.php");
?>



		<?php
		/**
		 * User has already logged in, so display relavent links, including
		 * a link to the admin center if the user is an administrator.
		 */
		if($session->logged_in)
		{
			
			?>
			
			
			
			
			<?php 
			/*if($session->isAdmin())
			{
				echo "<a href=\"admin/admin.php\">Admin Center</a>\n";
			}*/
			//echo "<a href=\"process.php\">Logout</a>\n";
			//echo "</header>\n"
		?>
		<section class="shopping-list">
			<h1>your list:</h1>
			<?php $session->theShoppingList();?>
			<?php
			/*
			 * Sort list form, with text input to add your location to 
			 * enable finding the chains that are near you.
			 */ 
			?>
			<form class="form-horizontal sort-list">
				
				    <div class="input-append">
				    	<input class="span2" id="appendedInputButton" name="location" placeholder="Your location" required type="text">
				    	<button class="btn sort-list" type="button">Sort List!</button>
				    </div>
			</form>
		</section>
		<section class="product-search-container">
			<h1>product search:</h1>
			<form class="form-search" method="POST" action="process.php">
				<div class="input-append">
					<input type="text" name="searchTerm" class="span2 search-query product-search">
					<input type="hidden" name="subsearch" value="1">
					<button type="submit" class="btn">Search</button>
				</div>
			</form>
			<div class="search-results">
			</div>
		</section>
		<section>
			<h1>add item to database:</h1>
			<form method="POST" action="process.php" class="add-product-form">
				<label for="productName">product name:</label>
				<input type="text" name="productName" class="input-large productName" required placeholder="Product Name">
				
				<label for="catID">category:</label>
				<select name="catID" class="input-large cat-dropdown">
					<option value="1">ATERIA-AINEKSET JA KASTIKKEET
				</select>
				
				<label for="brandID">
					brand:
					<input type="text" name="brandName" class="input-large brand-name-input" required placeholder="Brand Name">
				</label>
				<div class="brand-autocomplete-results"></div>
				<input type="hidden" name="brandID" value="-1" class="brand-id">
				
				<label for="volID">volume type:
					<label class="radio">
						<input type="radio" name="volID" class="volID" value="1" checked>kg
					</label>
				<label class="radio">
					<input type="radio" name="volID" class="volID" value="2">liters
				</label>
				</label>
				<input type="hidden" name="subnewproduct" value="1">
				
				<button type="submit" class="span2 btn btn-large">Add It</button>
			</form>
			
		</section>

		<?php
		}
		else
		{
			?>
		<header>
			<h1>Login</h1>
			<?php
			/**
			 * User not logged in, display the login form.
			 * If user has already tried to login, but errors were
			 * found, display the total number of errors.
			 * If errors occurred, they will be displayed.
			 */
			if($form->num_errors > 0)
			{
				echo "<font size=\"2\" color=\"#ff0000\">".$form->num_errors." error(s) found</font>\n";
			}
			?>

			<?php /*
			<form action="process.php" method="POST">
				<label for="email">Email:</label> <input type="email" name="email"
					value="<?php echo $form->value("email"); ?>" required
					placeholder="your-email@example.com">
				<div class="form-error">
					<?php echo $form->error("email"); ?>
				</div>

				<label for="password">Password:</label> <input type="password"
					name="pass" value="<?php echo $form->value("pass"); ?>" required
					placeholder="password"> <a href="forgotpass.php"
					class="forgot-pass">forgot password?</a>
				<div class="form-error">
					<?php echo $form->error("pass"); ?>
				</div>

				<label for="remember">Remember me:</label> 
				<input type="checkbox" name="remember" <?php if($form->value("remember") != ""){ echo "checked"; } ?>> 
				<input type="hidden" name="sublogin" value="1"> 
				<input type="submit" value="Login">
			</form>
			*/?>
			
			    <form class="form-horizontal" action="process.php" method="POST">
				    <div class="control-group">
					   	<label class="control-label" for="inputEmail">Email</label>
					    <div class="controls">
					    	<input type="email" id="inputEmail" class="input-large" name="email" value="<?php echo $form->value("email"); ?>" required placeholder="Email">
					    </div>
					    <div class="alert alert-error">
							<?php echo $form->error("email"); ?>
						</div>
				    </div>
				    <div class="control-group">
				    	<label class="control-label" for="inputPassword">Password</label>
				    	<div class="controls">
				    		<input type="password" id="inputPassword" class="input-large" name="pass" value="<?php echo $form->value("pass"); ?>" required
					placeholder="Password">
				    	</div>
				    	<div class="alert alert-error">
							<?php echo $form->error("pass"); ?>
						</div>
				    </div>
				    <div class="control-group">
					    <div class="controls">
						    <label class="checkbox">
						    	<input type="checkbox" name="remember" <?php if($form->value("remember") != ""){ echo "checked"; } ?>> Remember me
						    </label>
						    <input type="hidden" name="sublogin" value="1"> 
						    <button type="submit" class="btn">Sign in</button>
					    </div>
				    </div>
			    </form>
			
			<a href="register.php" class="btn btn-large btn-primary">create an account</a>
		</header>
		<?php
		}

		/**
		 * Just a little page footer, tells how many registered members
		 * there are, how many users currently logged in and viewing site,
		 * and how many guests viewing site. Active users are displayed,
		 * with link to their user information.
		 */

		/*if(isSet($_SESSION['debug_info']))
		{
			echo "<h2>Debug Info:</h2>\n";
			echo $_SESSION['debug_info'];
		}*/
		if(isSet($_SESSION['value_array']) && isSet($_SESSION['error_array']))
		{
			echo "<p>Form post debug: \n";
			foreach($_SESSION['value_array'] as $v)
			{
				echo $v . " \n";
			}
			echo "<br>\n";
			foreach($_SESSION['error_array'] as $v)
			{
				echo $v . " \n";
			}

			echo "</p>\n";
		}
		else
			echo "<p class=\"clearfix\">No errors (aparently)</p>\n";
		echo "</td></tr><tr><td align=\"center\"><br><br>\n";
		echo "<b>Member Total:</b> ".$database->getNumMembers()."<br>";
		echo "There are $database->num_active_users registered members and ";
		echo "$database->num_active_guests guests viewing the site.<br><br>";

		//include("include/view_active.php");

		?>
	</div><!-- .wrapper -->
	
<?php include("./footer.php");?>
