<?php
/**
 * Process.php
 *
 * The Process class is meant to simplify the task of processing
 * user submitted forms, redirecting the user to the correct
 * pages if errors are found, or if form is successful, either
 * way. Also handles the logout procedure.
 *
 * Written by: Jpmaster77 a.k.a. The Grandmaster of C++ (GMC)
 * Last Updated: August 19, 2004
 */
include("include/session.php");
include("include/product_functions.php");
include_once("include/constants.php");


/**
 * 
 * @author vivaldi
 *
 * @method procLogin
 * @method procRegister
 * @method procForgotPass
 * @method procEditAccount
 * @method procAddToList
 * @method procRemoveFromList
 * @method procSearch
 * @method procAddNewProduct
 */
class Process
{
	/* Class constructor */
	function Process(){
		global $session;
		
		
		//$_SESSION['debug_info'] = "<p>process.php called</p>\n";
		
		/* User submitted login form */
		if(isset($_POST['sublogin'])){
			$this->procLogin();
		}
		/* User submitted registration form */
		else if(isset($_POST['subjoin'])){
			$this->procRegister();
		}
		/* User submitted forgot password form */
		else if(isset($_POST['subforgot'])){
			$this->procForgotPass();
		}
		/* User submitted edit account form */
		else if(isset($_POST['subedit'])){
			$this->procEditAccount();
		}
		//User clicked the home link
		else if(isset($_POST['subindex']))
		{
			$this->procSetIndex();
		}
		// User sent request to add item to their list
		else if(isSet($_POST['subaddtolist']))
		{
			$this->procAddToList();
		}
		// User sent request to remove item from list
		else if(isSet($_POST['subremovefromlist']))
		{
			$this->procRemoveFromList();
		}
		// User submitted an item search request
		else if(isSet($_POST['subsearch']))
		{
			$this->procSearch();
		}
		else if(isSet($_POST['subbrowse']))
		{
			$this->procBrowseSearch();
		}
		// User submitted an item search request
		else if(isSet($_POST['subcatlist']))
		{
			$this->procCategoryList();
		}
		// User submitted an item search request
		else if(isSet($_GET['subbrandsearch']))
		{
			$this->procBrandSearch();
		}
		// User submitted the add product form
		else if(isSet($_POST['subnewproduct']))
		{
			$this->procAddNewProduct();
		}
		// User submitted the add product form
		else if(isSet($_POST['subprodedit']))
		{
			$this->procProdEdit();
		}
		// User submitted the add product form
		else if(isSet($_POST['subupdateprice']))
		{
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Calling updatePrice method @ process.php</p>\n";}
			$this->procUpdatePrice();
		}
		// User inputted text into the shop search form input
		else if(isSet($_POST['subshopsearch']))
		{
			$this->procShopSearch();
		}
		// User inputted text into the shop search form input
		else if(isSet($_POST['subaddshop']))
		{
			$this->procAddShop();
		}
		// User submitted the sort list function
		else if(isSet($_POST['subsortlist']))
		{
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Calling sortList method @ process.php</p>\n";}
			$this->procSortList();
		}
		// User submitted the sort list function
		else if(isSet($_POST['subdisplaylist']))
		{
			$this->procDisplayList();
		}
		//user submitted the add brand form
		else if(isSet($_POST['subaddbrand']))
		{
			$this->procAddBrand();
		}
		//list item quantity from input
		else if(isSet($_POST['subquantity']))
		{
			$this->procUpdateQuantity();
		}
		else if(isset($_POST['subupdateproduct']))
		{
			$this->procUpdateProduct();
		}
		// Sort location has been changed
		elseif(isset($_POST['subsortlocation']))
		{
			$this->updateSortLocation();
		}
		/**
		 * The only other reason user should be directed here
		 * is if he wants to logout, which means user is
		 * logged in currently.
		 */
		else if($session->logged_in){
			$this->procLogout();
		}
		/**
		 * Should not get here, which means user is viewing this page
		 * by mistake and therefore is redirected.
		 */
		else{
			header("Location: index.php");
		}
		
	
	}

	/**
	 * procLogin - Processes the user submitted login form, if errors
	 * are found, the user is redirected to correct the information,
	 * if not, the user is effectively logged in to the system.
	 */
	function procLogin()
	{
		global $session, $form;
		/* Login attempt */
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Login function called @ process.php</p>\n";}
		
		/*
		 * Check form token
		 */
		
		/*if($_POST['token'] != $_SESSION['form-token'])
		{
			echo "You appear to trying to hack the website, shame on you.";
		}*/
		
		$retval = $session->login($_POST['email'], $_POST['pass'], isset($_POST['remember']));
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>" . $_POST['email'] . ", " . $_POST['pass'] . "</p>\n";}
		//$_SESSION['value_array'] = array($_POST['email'], $_POST['pass'], $_POST['remember']);
		$_SESSION['error_array'] = $form->getErrorArray();
		
		/* Login successful */
		if($retval)
		{

			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Login function <strong>success</strong> @ process.php</p>\n";}
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Executing header() redirection to demo.php</p>\n";}
			//header("Location: ".$session->referrer);
			//header("Location: main.php");
		}
		/* Login failed */
		elseif($retval == 2)
		{
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Login function <strong>fail</strong>: no email entered @ process.php</p>\n";}
		}
		elseif($retval == 3)
		{
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Login function <strong>fail</strong>: invalid email @ process.php</p>\n";}
		}
		elseif($retval == 4)
		{
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Login function <strong>fail</strong>: password not entered @ process.php</p>\n";}
		}
		else {
			
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Login function <strong>fail</strong> @ process.php</p>\n";}
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Executing header() redirection to ".$session->referrer."</p>\n";}
			//header("Location: ".$session->referrer);
		}
	}
	 
	/**
	 * procLogout - Simply attempts to log the user out of the system
	 * given that there is no logout form to process.
	 */
	function procLogout(){
		global $session;
		$retval = $session->logout();
		header("Location: ".$session->referrer);
	}
	 
	/**
	 * procRegister - Processes the user submitted registration form,
	 * if errors are found, the user is redirected to correct the
	 * information, if not, the user is effectively registered with
	 * the system and an email is (optionally) sent to the newly
	 * created user.
	 */
	function procRegister(){
		global $session, $form;
		
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Registering new user @ process.php</p>\n";}
		
		/* Convert username to all lowercase (by option) */
		//if(ALL_LOWERCASE){
		$_POST['reg-email'] = strtolower($_POST['reg-email']);
		//}
		/* Registration attempt */
		$retval = $session->register($_POST['reg-email'], $_POST['reg-password1'], $_POST['reg-password2'], $_POST['reg-name']);

		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>" . $_POST['reg-email'] . ", " . $_POST['reg-password1'] . ", " . $_POST['reg-password2'] . ", " . $_POST['reg-name'] . "</p>\n";}
		
		/* Registration Successful */
		if($retval == 0){
			$_SESSION['reguname'] = $_POST['email'];
			$_SESSION['regsuccess'] = true;
			header("Location: index.php");
		}
		/* Error found with form */
		else if($retval == 1){
			$_SESSION['value_array'] = $_POST;
			$_SESSION['error_array'] = $form->getErrorArray();
			header("Location: register.php");
		}
		/* Registration attempt failed */
		else if($retval == 2){
			$_SESSION['reguname'] = $_POST['email'];
			$_SESSION['regsuccess'] = false;
			header("Location: register.php");
		}
	}
	 
	/**
	 * procForgotPass - Validates the given username then if
	 * everything is fine, a new password is generated and
	 * emailed to the address the user gave on sign up.
	 */
	function procForgotPass(){
		global $database, $session, $mailer, $form;
		/* Username error checking */
		$subuser = $_POST['user'];
		$field = "user";  //Use field name for username
		if(!$subuser || strlen($subuser = trim($subuser)) == 0){
			$form->setError($field, "* Username not entered<br>");
		}
		else{
			/* Make sure username is in database */
			$subuser = stripslashes($subuser);
			if(strlen($subuser) < 5 || strlen($subuser) > 30 ||
					!eregi("^([0-9a-z])+$", $subuser) ||
					(!$database->usernameTaken($subuser))){
				$form->setError($field, "* Username does not exist<br>");
			}
		}

		/* Errors exist, have user correct them */
		if($form->num_errors > 0){
			$_SESSION['value_array'] = $_POST;
			$_SESSION['error_array'] = $form->getErrorArray();
		}
		/* Generate new password and email it to user */
		else{
			/* Generate new password */
			$newpass = $session->generateRandStr(8);
			 
			/* Get email of user */
			$usrinf = $database->getUserInfo($subuser);
			$email  = $usrinf['email'];
			 
			/* Attempt to send the email with new password */
			if($mailer->sendNewPass($subuser,$email,$newpass)){
				/* Email sent, update database */
				$database->updateUserField($subuser, "password", md5($newpass));
				$_SESSION['forgotpass'] = true;
			}
			/* Email failure, do not change password */
			else{
				$_SESSION['forgotpass'] = false;
			}
		}

		header("Location: ".$session->referrer);
	}
	 
	/**
	 * procEditAccount - Attempts to edit the user's account
	 * information, including the password, which must be verified
	 * before a change is made.
	 */
	function procEditAccount()
	{
		global $session, $form;
		/* Account edit attempt */
		$retval = $session->editAccount($_POST['curpass'], $_POST['newpass'], $_POST['email']);

		/* Account edit successful */
		if($retval){
			$_SESSION['useredit'] = true;
			header("Location: ".$session->referrer);
		}
		/* Error found with form */
		else{
			$_SESSION['value_array'] = $_POST;
			$_SESSION['error_array'] = $form->getErrorArray();
			header("Location: ".$session->referrer);
		}
	}
	
	
	function procSetIndex()
	{
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Un-setting the dashboard session var to direct back to index @ process.php</p>\n";}
		unset($_SESSION['dashcontent']);
	}
	
	
	/*
	 * ****************************************************************************||
	 * Product form functions
	 * ****************************************************************************||
	 */
	
	/**
	 * Function to retrieve the HTML formatted list
	 * depending on input variables, can be either standard, item-based list
	 * or a sorted list showing prices, split into their respective stores
	 */ 
	function procDisplayList()
	{
		global $session;
		echo $session->theShoppingList();
		
	}
	
	function procSortList()
	{
		global $product_functions;
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Attempting to sort shoppinglist at location: ".$_POST['sortlocation']." @ process.php</p>\n";}
		$sortLocation = $_POST['sortlocation'];
		
		$retval = $product_functions->sortList($_POST['sortlocation']);
		if(!$retval)
		{
			echo "List sort failed";
		}
		
		echo $retval;
	}
	
	
	function procAddToList()
	{
		global $product_functions, $session;
		
	if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Quantity is ".$_POST['addProdQuantity']." of type ".gettype($_POST['addProdQuantity'])." @ process.php</p>\n";}
		
		$retval = $product_functions->addToList($_POST['addProdID']);
		if($retval)
		{
			//echo "Product added.";
			/*
			 * Call the shopping list function and echo it back, to allow for an ajax
			 * dynamic list update.
			 */
			echo $session->theShoppingList();
		}
		else 
		{
			echo "Product failed to add to list...";
		}
		//header("Location: main.php");
	}
	
	function procRemoveFromList()
	{
		global $product_functions, $session;
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Removing item ".$_POST['remProdID']." from list @ process.php</p>\n";}
		// Use regex to extract the list id index from the id
		//$remProdID = preg_match_all("/[0-9]*/", $_POST['remProdID'], $matches);
		$worked = $product_functions->removeFromList($_POST['remProdID']);
		if($worked)
		{
			echo $session->theShoppingList();
		}
		else
		{
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>attempt to remove $remProdID failed @ process.php</p>\n";}
			echo $_SESSION['debug_info'];
		}
	}
	
	function procSearch()
	{
		global $product_functions;
		$escapedTerm = mysql_real_escape_string($_POST['searchTerm']);
		$retval = $product_functions->search($escapedTerm);
		echo $retval;
		//$_SESSION['searchResults'] = $retval;
		//header("Location: main.php");
	}
	
	function procBrowseSearch()
	{
		global $product_functions;
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>attempt to add search term to session vars @ process.php</p>\n";}
		if(!empty($_POST['browseTerm']))
		{
			$escapedTerm = mysql_real_escape_string($_POST['browseTerm']);
			$_SESSION['browseSearchTerm'] = $escapedTerm;
		}
		
		if(isSet($_SESSION['browseSearchTerm']))
		{
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>session vars set with: $escapedTerm @ process.php</p>\n";}
			//$_SESSION['dashcontent'] = "browse";
			if(isset($_SESSION['dashcontent']))
			{
				if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>dashcontent var <strong>set</strong> to ". $_SESSION['dashcontent'] ." @ process.php</p>\n";}
			}
			
			header("Location:./index.php?page=browse");
		}
		else
		{
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>session vars <strong>not</strong> set @ process.php</p>\n";}
		}
		
	}
	
	function procUpdateQuantity()
	{
		global $product_functions;
		$listItemID = $_POST['updateQuantListID'];
		$quantity = $_POST['updateQuantQuantity'];
		
		$retval = $product_functions->setListItemQuantity($listItemID, $quantity);
	}
	
	function procCategoryList()
	{
		global $product_functions;
		echo $product_functions->categoryList(true);
	}
	
	function procBrandSearch()
	{
		global $product_functions;
		echo stripslashes($product_functions->brandName($_GET['brandString']));
	}
	
	
	function procAddNewProduct()
	{
		global $product_functions;
		global $session;
		
		//$chainId = -1, $shopId = -1, $price = -1, $specialStart = null, $specialEnd = null, $barcode = -1, $notes = ""
		//collect posted values in an array 
		$newProductValues = array(
				"productName" => $_POST['productName'],
				"catID" => $_POST['catID'],
				"brandName" => $_POST['brandName'],
				"volID" => $_POST['volID'],
				"barcode" => $_POST['barcode']);
		
		//send array for validation
		$retval = $product_functions->addNewProduct($newProductValues);
		
		//return message to user
		if(!$retval)
		{
			echo "<div class=\"alert\">product addition failed!\n";
			echo "<a href=".$session->referrer."\">return to last page</a>\n";
		}
		else
		{
			echo "<div class=\"alert alert-success\">".$_POST['productName']." added successfully</div>";
		}
		
	}
	
	/**
	 * Sets a session variable to determine the ID of the product to be edited
	 * then redirect the user to the page where the product update form is located
	 */
	function procProdEdit()
	{
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Editing item ".$_POST['prodEditProductID']." @ process.php</p>\n";}
		$_SESSION['prodEditProductID'] = $_POST['prodEditProductID'];
		//header("Location: priceUpdate.php");
	}
	
	/**
	 * Function that relays post varables from Update Price form to the 
	 * internal functions and database.
	 */
	function procUpdatePrice()
	{
		global $product_functions;
		
		$priceInfoArray = array(
				"productID" => $_POST['productID'],//$_SESSION['prodEditProductID'],
				"price" => $_POST['price'],
				"shopID" => $_POST['shopID']);
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Inserting price for item ".$_POST['productID']." @ process.php</p>\n";}
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>".$_POST['productID'].", ".$_POST['price'].", " . $_POST['shopID']."</p>\n";}
		$retval = $product_functions->updateProductPrice($priceInfoArray);
		if(!$retval)
		{
			echo "<div class=\"alert alert-error\">Price could not be added, errors</div>";
		}
		else 
		{
			echo "<div class=\"alert alert-success\">Price added succesfully</div>";
		}
		
	}
	
	/**
	 * Use post to send a full-text search query and retrieve a list of shops
	 * that match the query string terms.
	 */
	function procShopSearch()
	{
		global $product_functions;
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>shop search received @ process.php</p>\n";}
		echo $product_functions->getShopString(utf8_decode($_POST['shopSearchString']));
		
	}
	
	function procAddShop()
	{
		global $product_functions;
		$retval=$product_functions->newStore($_POST['city'], $_POST['location'], $_POST['chainID']);
		if(!$retval)
		{
			echo "Failed to add store.";
		}
		else
		{
			echo "Success! New store added at ".$_POST['location'].", ".$_POST['city'];
		}
		
	}
	
	function procAddBrand()
	{
		global $product_functions;
		
		$retval = $product_functions->addBrand($_POST['adminBrandName']);
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>method to add brand name called @ process.php</p>\n";}
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>$retval @ process.php</p>\n";}
		echo $retval;
	}
	
	function procUpdateProduct()
	{
		global $product_functions;
		
		$productInfoArray = array("productID" => $_POST['productID'],
									"productName" => $_POST['productName'],
									"brandName" => $_POST['brandName'],
									"categoryID" => $_POST['category'],
									"barcode" => $_POST['barcode'],
									"description" => $_POST['description']);
		
		$retval = $product_functions->updateProductInfo($productInfoArray);
		if(!$retval)
		{
			echo "<div class=\"alert\">Product update failed.</div>\n";
		}
		else
		{
			echo "<div class=\"alert alert-success\">Product updatd successfully!</div>\n";
		}
	}
	
	function updateSortLocation()
	{
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Sort location set to " . $_POST['sortLocation'] . " </p>\n";}
		$_SESSION['sortLocation'] = $_POST['sortLocation'];
	}
	
	
	
};

/* Initialize process */
$process = new Process;

?>
