<?php
/**
 * Database.php
 *
 * The Database class is meant to simplify the task of accessing
 * information from the website's database.
 *
 * Written by: Jpmaster77 a.k.a. The Grandmaster of C++ (GMC)
 * Last Updated: August 17, 2004
 */
include_once("constants.php");
/**
 *
 * @author vivaldi
 *
*/
class MySQLDB
{
	var $connection;         //The MySQL database connection
	var $num_active_users;   //Number of active users viewing site
	var $num_active_guests;  //Number of active guests viewing site
	var $num_members;        //Number of signed-up users
	/* Note: call getNumMembers() to access $num_members! */

	/* Class constructor */
	function MySQLDB(){
		/* Make connection to database */
		$this->connection = mysql_connect(DB_SERVER, DB_USER, DB_PASS) or die(mysql_error());
		mysql_select_db(DB_NAME, $this->connection) or die(mysql_error());
		mysql_set_charset('utf8',$this->connection);

		/**
		 * Only query database to find out number of members
		 * when getNumMembers() is called for the first time,
		 * until then, default value set.
		*/
		$this->num_members = -1;

		if(TRACK_VISITORS){
			/* Calculate number of users at site */
			$this->calcNumActiveUsers();

			/* Calculate number of guests at site */
			$this->calcNumActiveGuests();
		}
	}

	/**
	 * confirmUserPass - Checks whether or not the given
	 * username is in the database, if so it checks if the
	 * given password is the same password in the database
	 * for that user. If the user doesn't exist or if the
	 * passwords don't match up, it returns an error code
	 * (1 or 2). On success it returns 0.
	 *
	 * @param string $email
	 * @param string $password
	 * @return number (error code)
	 */
	function confirmUserPass($email, $password)
	{
		/* Add slashes if necessary (for query) */
		if(!get_magic_quotes_gpc()) {
			$email = addslashes($email);
		}

		/* Verify that user is in database */
		$q = "SELECT users.passhash AS passhash, users.salt AS salt FROM ".TBL_USERS." WHERE email = \"$email\"";
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Executing user-pass varification in database.php</p>\n";}
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p><code>".$q."</code></p>\n";}
		$result = mysql_query($q, $this->connection);
		if(!$result || (mysql_numrows($result) < 1)){
			return 1; //Indicates username failure
		}
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>User pass confirmed</p>\n";}
		/* Retrieve password from result, strip slashes */
		$dbarray = mysql_fetch_array($result);
		$dbarray['passhash'] = stripslashes($dbarray['passhash']);
		$dbarray['salt'] = stripslashes($dbarray['salt']);
		$password = stripslashes($password);

		/* Reconstruct the passhash with the user's inputted password and retrieved salt */
		$password = md5( md5( $password ) . md5( $dbarray['salt'] ) );

		/* Validate that password is correct */
		if($password == $dbarray['passhash'])
		{
			return 0; //Success! Username and password confirmed
		}
		else
		{
			return 2; //Indicates password failure
		}
	}

	/**
	 * confirmUserID - Checks whether or not the given
	 * username is in the database, if so it checks if the
	 * given userid is the same userid in the database
	 * for that user. If the user doesn't exist or if the
	 * userids don't match up, it returns an error code
	 * (1 or 2). On success it returns 0.
	 */
	function confirmUserID($email, $userid)
	{
		/* Add slashes if necessary (for query) */
		if(!get_magic_quotes_gpc()) {
			$email = addslashes($email);
		}

		/* Verify that user is in database */
		$q = "SELECT users.id AS userID FROM ".TBL_USERS." WHERE email = \"$email\"";
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Varifying user exists in DB: @ database.php<br><code>".$q."</code></p>\n";}
		$result = mysql_query($q, $this->connection);
		if(!$result || (mysql_numrows($result) < 1))
		{
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>User not found @ database.php</p>\n";}
			return 1; //Indicates username failure
		}
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>User info found, retrieving values @ database.php</p>\n";}
		/* Retrieve userid from result, strip slashes */
		$dbarray = mysql_fetch_array($result);
		$dbarray['userID'] = stripslashes($dbarray['userID']);
		$userid = stripslashes($userid);
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Comparing remembered user (".$userid.") to userid in db (".$dbarray['userID'].") @ database.php</p>\n";}

		/* Validate that userid is correct */
		if($userid == $dbarray['userID'])
		{
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>User confirmed! @ database.php</p>\n";}
			return 0; //Success! Username and userid confirmed
		}
		else
		{
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>User not found. @ database.php</p>\n";}
			return 2; //Indicates userid invalid
		}
	}

	/**
	 * usernameTaken - Returns true if the username has
	 * been taken by another user, false otherwise.
	 */
	function emailTaken($email){
		if(!get_magic_quotes_gpc()){
			$email = addslashes($email);
		}
		$q = "SELECT email FROM ".TBL_USERS." WHERE email = \"$email\"";
		$result = mysql_query($q, $this->connection);
		return (mysql_numrows($result) > 0);
	}

	/**
	 * usernameBanned - Returns true if the username has
	 * been banned by the administrator.
	 */
	function emailBanned($email){
		if(!get_magic_quotes_gpc()){
			$email = addslashes($email);
		}
		$q = "SELECT email FROM ".TBL_BANNED_USERS." WHERE email = \"$email\"";
		$result = mysql_query($q, $this->connection);
		return (mysql_numrows($result) > 0);
	}

	/**
	 * addNewUser - Inserts the given (username, password, email)
	 * info into the database. Appropriate user level is set.
	 * Returns true on success, false otherwise.
	 * @param email = str
	 * @param password = str (unhashed password)
	 * @param firstName = str
	 * @param lastName = str (optional)
	 *
	 * @return mysql_query result
	 */
	function addNewUser($email, $password, $firstName, $lastName = "")
	{
		/**
		 * Generate a random 5 character string, to 'salt' the md5-hashed password which is stored
		 * on the database. The hashed password string is concatenated with the hashed Salt string, and
		 * then this is hashed again before being stored.
		 * This method increases the difficulty in de-coding password hashes by several
		 * orders of magnitude, making it necessary to run 3 rounds of decryption for
		 * any one password.
		 */
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$salt = '';
		for ($i = 0; $i < 5; $i++)
		{
			$salt .= $characters[rand(0, strlen($characters) - 1)];
		}
			
		/* Convert user's IP to INT */
		$ip = ip2long($_SERVER['REMOTE_ADDR']);
		if($ip != null)
		{
// 			echo "<p>ip address turned to int:\n
// 			$ip</p>\n";
		}
		else
		{
			$ip = ip2long("0.0.0.0");
			//echo "<p>ip couldn't be found, so we just added a default value:\n
			//$ip</p>\n";
		}
			
		$saltedPass = md5(md5($password) . md5($salt));
			
		$time = time();
		/* If admin sign up, give admin user level */
		if(strcasecmp($email, ADMIN_NAME) == 0){
			$ulevel = ADMIN_LEVEL;
		}else{
			$ulevel = USER_LEVEL;
		}

		//columns to determine what columns to select when inserting into 'users' table
		$addNewUserColumns = "id, email, passhash, salt, created, firstname, lastname,
				perm_override_remove, perm_override_add, last_login_date,
				reg_ip, last_login_ip, must_validate";
		/*
		 * 'INSERT INTO users('.$addNewUserColumns.') VALUES
		(null, "' . $email . '", "' . $saltedPass . '", "' . $salt.'", CURRENT_TIMESTAMP, "' . $firstName . '", "' . $lastName .
				'", null, null, CURRENT_TIMESTAMP, ' . $ip . ', ' . $ip . ', 0)';
		*/

		/**
		 * @notes
		 * set the 'created' and 'last_login_date' values to the current time and date, since the user
		 * would both create and first access the site a their presnet time
		 *
		 * the perm_overrides are set to null because I'm not using them yet
		 * Must validate is likewise set to a default value of 0 because it's not in use
		 */
		$q = "INSERT INTO ".TBL_USERS." (".$addNewUserColumns.") VALUES (null, \"$email\", \"$saltedPass\", \"$salt\", CURRENT_TIMESTAMP, \"$firstName\", \"$lastName\", null, null, CURRENT_TIMESTAMP, $ip, $ip, 0)";
		return mysql_query($q, $this->connection);
		if(!$q)
		{
			$_SESSION['regerrors'] .= "<p>New user insertion failed. Check query:</p>\n";
			$_SESSION['regerrors'] .= "<p><code>".$q."</code>\n";

		}
	}

	/**
	 * updateUserField - Updates a field, specified by the field
	 * parameter, in the user's row of the database.
	 */
	function updateUserField($email, $field, $value){
		$q = "UPDATE ".TBL_USERS." SET ".$field." = '$value' WHERE email = \"$email\"";
		return mysql_query($q, $this->connection);
	}

	/**
	 * getUserInfo - Returns the result array from a mysql
	 * query asking for all information stored regarding
	 * the given username. If query fails, NULL is returned.
	 *
	 * @param string $email
	 * @return NULL|multitype:
	 */
	function getUserInfo($email)
	{
		$q = "SELECT 
			".TBL_USERS.".id AS id, 
			".TBL_USERS.".email AS email, 
			".TBL_USERS.".firstname AS name,
			".TBL_USERS.".userlevel AS userLevel
			FROM 
			".TBL_USERS." 
			WHERE 
			email = \"$email\"";
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Query to get user info @ database.php:<br><code>$q</code></p>\n";}
		$result = mysql_query($q, $this->connection);
		/* Error occurred, return given name by default */
		if(!$result || (mysql_numrows($result) < 1))
		{
			return NULL;
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p><strong>No user found<strong></p>\n";}
		}

		/* Return result array */
		$dbarray = mysql_fetch_array($result);
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>User info retrieved @ database.php</p>\n";}
		return $dbarray;
	}

	
	function queryUserVendor($userID)
	{
		$vendorInfoArray = array();
		$query = "SELECT";
		return $vendorInfoArray;
	}
	
	
	/**
	 * getNumMembers - Returns the number of signed-up users
	 * of the website, banned members not included. The first
	 * time the function is called on page load, the database
	 * is queried, on subsequent calls, the stored result
	 * is returned. This is to improve efficiency, effectively
	 * not querying the database when no call is made.
	 */
	function getNumMembers()
	{
		if($this->num_members < 0)
		{
			$q = "SELECT * FROM ".TBL_USERS;
			$result = mysql_query($q, $this->connection);
			$this->num_members = mysql_numrows($result);
		}
		return $this->num_members;
	}
	
	/**
	 * Set the last_login_date value for the user using their collected ID
	 * Returns true if success, else false.
	 * 
	 * @param int $userID
	 * @return boolean
	 */
	function setLastActive($userID)
	{
		/* Convert user's IP to INT */
		$ip = ip2long($_SERVER['REMOTE_ADDR']);
		if($ip == null)
		{
			$ip = ip2long("0.0.0.0");
			if(DEBUG_MODE){if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>IP could not be found, so i just added a default value</p>\n";}}
		}
		$q = "UPDATE ".TBL_USERS."
				SET last_login_ip = $ip
				WHERE ".TBL_USERS.".id = $userID";
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p><code>$q</code></p>\n";}
		$result = mysql_query($q);
		if(!$result)
		{
			if(DEBUG_MODE){if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Update last login date failed.</p>\n";}}
			return false;
		}
		return true;
	}
	

	/**
	 * calcNumActiveUsers - Finds out how many active users
	 * are viewing site and sets class variable accordingly.
	 */
	function calcNumActiveUsers()
	{
		/* Calculate number of users at site */
		$q = "SELECT * FROM ".TBL_ACTIVE_USERS;
		$result = mysql_query($q, $this->connection);
		$this->num_active_users = mysql_numrows($result);
	}

	/**
	 * calcNumActiveGuests - Finds out how many active guests
	 * are viewing site and sets class variable accordingly.
	 */
	function calcNumActiveGuests()
	{
		/* Calculate number of guests at site */
		$q = "SELECT * FROM ".TBL_ACTIVE_GUESTS;
		$result = mysql_query($q, $this->connection);
		$this->num_active_guests = mysql_numrows($result);
	}

	/**
	 * addActiveUser - Updates username's last active timestamp
	 * in the database, and also adds him to the table of
	 * active users, or updates timestamp if already there.
	 */
	function addActiveUser($email, $time)
	{
		global $form;
		$q = "UPDATE ".TBL_USERS." SET last_login_date = '$time' WHERE email = \"$email\"";
		mysql_query($q, $this->connection);

		if(!TRACK_VISITORS) return;
		$q = "REPLACE INTO ".TBL_ACTIVE_USERS." VALUES (\"$email\", CURRENT_TIMESTAMP)";
		mysql_query($q, $this->connection);
		$this->calcNumActiveUsers();
	}

	/* addActiveGuest - Adds guest to active guests table */
	function addActiveGuest($ip, $time)
	{
		if(!TRACK_VISITORS) return;
		$q = "REPLACE INTO ".TBL_ACTIVE_GUESTS." VALUES ('$ip', '$time')";
		mysql_query($q, $this->connection);
		$this->calcNumActiveGuests();
	}

	/* These functions are self explanatory, no need for comments */
	  
	/* removeActiveUser */
	function removeActiveUser($email)
	{
		if(!TRACK_VISITORS) return;
		$q = "DELETE FROM ".TBL_ACTIVE_USERS." WHERE email = \"$email\"";
		mysql_query($q, $this->connection);
		$this->calcNumActiveUsers();
	}

	/* removeActiveGuest */
	function removeActiveGuest($ip)
	{
		if(!TRACK_VISITORS) return;
		$q = "DELETE FROM ".TBL_ACTIVE_GUESTS." WHERE ip = '$ip'";
		mysql_query($q, $this->connection);
		$this->calcNumActiveGuests();
	}

	/* removeInactiveUsers */
	function removeInactiveUsers()
	{
		if(!TRACK_VISITORS) return;
		$timeout = time()-USER_TIMEOUT*60;
		$q = "DELETE FROM ".TBL_ACTIVE_USERS." WHERE last_login_date < $timeout";
		mysql_query($q, $this->connection);
		$this->calcNumActiveUsers();
	}

	/* removeInactiveGuests */
	function removeInactiveGuests()
	{
		if(!TRACK_VISITORS) return;
		$timeout = time()-GUEST_TIMEOUT*60;
		$q = "DELETE FROM ".TBL_ACTIVE_GUESTS." WHERE last_login_date < $timeout";
		mysql_query($q, $this->connection);
		$this->calcNumActiveGuests();
	}


	/*
	 * PRODUCT FUNCTIONS
	 * ***********************************************************************************
	 */

	/**
	 * Get the name of a chain by querying the database using the chain ID
	 * 
	 * @param int $chainID
	 * @return string|unknown
	 */
	function getChainName($chainID)
	{
		$chainName = "";
		$q = "SELECT ".TBL_CHAINS.".name AS name, ".TBL_SHOPS.".chainID AS shopChainID FROM ".TBL_CHAINS.", ".TBL_SHOPS."
				WHERE ".TBL_SHOPS.".chainID = $chainID OR
						(".TBL_SHOPS.".id = $chainID AND ".TBL_CHAINS.".id = ".TBL_SHOPS.".chainID)";
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p><code>$q</code></p>\n";}
		$result = mysql_query($q);
		if(!$result)
		{
			return "chain name not found";
		}
		$dbArray = mysql_fetch_assoc($result);
		$chainName = $dbArray['name'];
		return $chainName;
	}
	
	/**
	 * Get a shop's name value from an inputted shop ID value and return it as a string
	 * 
	 * @param int $id
	 * @return string
	 */
	function getShopLocation($id)
	{		
		$q = "SELECT ".TBL_SHOPS.".name AS name FROM ".TBL_SHOPS." WHERE ".TBL_SHOPS.".id = $id";
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p><code>$q</code></p>\n";}
		$result = mysql_query($q);
		if(!$result){
			return "Chain name not found";
		}
		$dbArray = mysql_fetch_assoc($result);
		return $dbArray['name'];
		
	}
	
	/**
	 * return a product name as a string, using the product ID value
	 * 
	 * @param int $id
	 * @return string
	 */
	function getProductName($id)
	{
		$q = "SELECT ".TBL_PRODUCTS.".name AS name FROM ".TBL_PRODUCTS." WHERE ".TBL_PRODUCTS.".id = $id";
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p><code>$q</code></p>\n";}
		$result = mysql_query($q);
		if(!$result)
		{
			return "product not found";
		}
		$dbArray = mysql_fetch_assoc($result);
		return $dbArray['name'];
	}
	
	function getProductBrand($productID)
	{
		$q = "SELECT ".TBL_BRANDS.".name AS brandName FROM ".TBL_PRODUCTS.", ".TBL_BRANDS." WHERE ".TBL_PRODUCTS.".id = $productID AND ".TBL_BRANDS.".id = ".TBL_PRODUCTS.".brandID";
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p><code>$q</code></p>\n";}
		$result = mysql_query($q);
		if(!$result)
		{
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>brand name query failed</p>\n";}
			return false;
		}
		$dbArray = mysql_fetch_assoc($result);
		return $dbArray['brandName'];
	}
	
	function getProductCategory($productID)
	{
		$q = "SELECT ".TBL_CATEGORIES.".name AS categoryName FROM ".TBL_PRODUCTS.", ".TBL_CATEGORIES." WHERE ".TBL_PRODUCTS.".id = $productID AND ".TBL_CATEGORIES.".id = ".TBL_PRODUCTS.".categoryID";
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p><code>$q</code></p>\n";}
		$result = mysql_query($q);
		if(!$result)
		{
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>category name query failed</p>\n";}
			return false;
		}
		$dbArray = mysql_fetch_assoc($result);
		return $dbArray['categoryName'];
	}
	
	function getProductBarcode($productID)
	{
		$q = "SELECT ".TBL_PRODUCTS.".barcode AS barcode FROM ".TBL_PRODUCTS." WHERE ".TBL_PRODUCTS.".id = $productID";
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p><code>$q</code></p>\n";}
		$result = mysql_query($q);
		if(!$result)
		{
			return "product barcode not found";
		}
		$dbArray = mysql_fetch_assoc($result);
		return $dbArray['barcode'];
	}
	
	/**
	 * Return the product thumbnail filename stored on the database from the supplied product id
	 * 
	 * @param int $productID
	 * @return string|boolean
	 */
	function getProductThumb($productID)
	{
		$query = "SELECT ".TBL_PRODUCTS.".picUrlThumb AS picThumb FROM ".TBL_PRODUCTS." WHERE ".TBL_PRODUCTS.".id = $productID";
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p><code>$query</code></p>\n";}
		if($result = mysql_query($query))
		{
			$dbArray = mysql_fetch_assoc($result);
			$pictureURL = $dbArray['picThumb'];
			return $pictureURL;
		}
		else
		{
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Pic thumbnail filename not found</p>\n";}
			return false;
		}
	}
	
	/**
	 * Return the product picture filename stored on the database from the supplied product id
	 * 
	 * @param int $productID
	 * @return string|boolean
	 */
	function getProductPicture($productID)
	{
		$query = "SELECT ".TBL_PRODUCTS.".picUrl AS pic FROM ".TBL_PRODUCTS." WHERE ".TBL_PRODUCTS.".id = $productID";
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p><code>$query</code></p>\n";}
		if($result = mysql_query($query))
		{
			$dbArray = mysql_fetch_assoc($result);
			$pictureURL = $dbArray['pic'];
			return $pictureURL;
		}
		else
		{
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Pic filename not found</p>\n";}
			return false;
		}
	}
	
	/**
	 * Return all product info in an associative array for a given product ID
	 * 
	 * @param int $productID
	 * @return boolean|multitype:
	 */
	function getProductInfo($productID)
	{
		$query = "SELECT
				".TBL_PRODUCTS.".name AS productName,
				".TBL_PRODUCTS.".barcode AS barcode,
				".TBL_PRODUCTS.".picUrl AS picUrl,
				".TBL_PRODUCTS.".description AS description,
				".TBL_BRANDS.".name AS brandName,
				".TBL_CATEGORIES.".name AS categoryName
				FROM ".TBL_PRODUCTS.", ".TBL_BRANDS.", categories
				WHERE
				".TBL_PRODUCTS.".id = $productID AND
				".TBL_PRODUCTS.".brandID = ".TBL_BRANDS.".id AND
				".TBL_PRODUCTS.".categoryID = ".TBL_CATEGORIES.".id";
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p><code>$query</code></p>\n";}
		$result = mysql_query($query);
		if(!$result || mysql_num_rows($result) == 0)
		{
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Product info query failed @ database.php</p>\n";}
			return false;
		}
		$dbArray = mysql_fetch_assoc($result);
		if(DEBUG_MODE){
			$_SESSION['debug_info'] .= "<p>\n";
			foreach($dbArray AS $value)
			{
				$_SESSION['debug_info'] .= $value . "\n";
			}
			$_SESSION['debug_info'] .= "</p>\n";
		}
		return $dbArray;
	}
	
	
	/**
	 * Fetch the entries in the category table and return then as an associative array
	 * 
	 * @return boolean|multitype:
	 */
	function getCategoryList($selected = null)
	{
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Attempting to retrieve category list @ database.php</p>\n";}
		//Get the parent categories (where parentID = 0)
		$q_getGroups = "SELECT * FROM ".TBL_CATEGORIES." WHERE ".TBL_CATEGORIES.".parentID = 0 ORDER BY ".TBL_CATEGORIES.".id";
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Retrieve parent categories:</p>\n";}
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p><code>$q_getGroups</code></p>\n";}
		$result_groups = mysql_query($q_getGroups);
		if(!$result_groups)
		{
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Failed to receive parent categories</p>\n";}
			return false;
		}
		$catGroupArray = array();
		/*
		 * Add all the parent categories into an array, with the category ID as the key
		 * and the name as the value
		 */
		while($groupsDbArray = mysql_fetch_assoc($result_groups))
		{
			$catGroupArray[$groupsDbArray['id']] = $groupsDbArray['name'];
		}
		
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Retrieve sub-categories: </p>\n";}
		$q_categories = "SELECT * FROM ".TBL_CATEGORIES." WHERE ".TBL_CATEGORIES.".parentID > 0 ORDER BY ".TBL_CATEGORIES.".id";
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p><code>$q_categories</code></p>\n";}
		$result = mysql_query($q_categories);
		
		/*
		 * Return false if no category rows returned, it would indicate an error in the query (or the database has changed)
		 */
		if(!$result || mysql_num_rows($result) == 0)
		{
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Can not retrieve category list</p>\n";}
			return false;
		}
		$subCatArray = array();
		/*
		 * Add each sub-category to an array, where the key is the category id and the value is
		 * a sub-array, with the parentID as the key, and the name as the value
		 * 
		 * id =>
		 * 		parentID => name
		 */
		while($categoriesDbArray = mysql_fetch_assoc($result))
		{
			$subCatParentIDArray[$categoriesDbArray['id']] = $categoriesDbArray['parentID'];
			$subCatNameArray[$categoriesDbArray['id']] = $categoriesDbArray['name'];
		}
		
		
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Add the sub-categories to their respective groups</p>\n";}
		$categoryString = "";
		foreach($catGroupArray AS $groupID => $groupName)
		{
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Add to $groupID: $groupName:</p>\n";}
			$categoryString .= "<optgroup label=\"$groupName\">";
			foreach($subCatParentIDArray AS $subCatID => $parentID)
			{
				//if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Fetch sub-cats</p>\n";}
				if($parentID == $groupID)
				{
					//if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Add ".$subCatNameArray[$subCatID]." to  $groupName</p>\n";}
					if($selected != null && $selected == $subCatID)
					{
						$selectedAttr = "selected";
					}
					else
					{
						$selectedAttr = "";
					}
					$categoryString .= "<option value=\"$subCatID\" $selectedAttr>";
					$categoryString .= strtolower($subCatNameArray[$subCatID]);
					$categoryString .= "</option>";
				}
			}
			$categoryString .= "</optgroup>";
			
		}
		//if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>$categoryString</p>\n";}
		return $categoryString;
	}
	

	/**
	 * createList - create a new list, with name and id for defined user
	 * @param int $userID
	 * @param string $listName
	 * @return boolean
	 */
	function createList($userID, $listName = "A Shopping List")
	{
		$q = "INSERT INTO
				".TBL_SHOPPING_LISTS." (id, userID, name)
				VALUES
				(null, $userID, \"$listName\")";
		$result = mysql_query($q);

		//Check if INSERT INTO query worked
		if(!$result)
		{
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Fault in inserting new list @ database.php:<br><code>$q</code></p>\n";}
			return false;
		}
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>New list created for user #$userID</p>\n";}
		return true;
	}


	/**
	 *
	 * @param string $email
	 * @return boolean|Ambigous <>
	 */
	function getListID($email)
	{
		//Select all lists where the user id matches the userID column
		$q = "SELECT ".TBL_USERS.".id AS userID, ".TBL_USERS.".email AS userEmail, ".TBL_SHOPPING_LISTS.".id AS listID, ".TBL_SHOPPING_LISTS.".userID AS listUserID FROM ".TBL_USERS.", ".TBL_SHOPPING_LISTS." WHERE ".TBL_USERS.".id = ".TBL_SHOPPING_LISTS.".userID AND ".TBL_USERS.".email = \"$email\"";
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Sending query to return shopping list ID:<br><code>$q</code></p>\n";}
		$result = mysql_query($q);
		if(!$result || mysql_num_rows($result) == 0)
		{
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Returned rows are 0, or query failed. Shopping list not found for $email @ database.php</p>\n";}
			return false;
		}
		$dbArray = mysql_fetch_assoc($result);
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Found list #".$dbArray['listID']." for user #".$dbArray['userID']." @ database.php</p>\n";}
		return $dbArray['listID'];
	}

	/**
	 * Retrieve all the list items for the defined shopping list and concatenate into a single
	 * string for eventual echoing in the rendered html document
	 *
	 * @param int $userID
	 * @param int $listID
	 * @return boolean|string
	 */
	function getListItems($userID, $listID)
	{
		$listString = "";
		$q = 'SELECT
			'.TBL_USERS.'.firstname,
			'.TBL_SHOPPING_LISTS.'.id AS listID,
			'.TBL_SHOPPING_LISTS.'.name AS ShoppingListName,
			'.TBL_SHOPPING_LIST_PRODUCTS.'.id AS listItemID,
			'.TBL_SHOPPING_LIST_PRODUCTS.'.shoppingListID,
			'.TBL_SHOPPING_LIST_PRODUCTS.'.ProductID AS listProductID,
			'.TBL_SHOPPING_LIST_PRODUCTS.'.quantity AS quantity,
			'.TBL_PRODUCTS.'.id AS productID,
			'.TBL_PRODUCTS.'.name AS ProductName,
			'.TBL_PRODUCTS.'.barcode AS barcode,
			'.TBL_PRODUCTS.'.picUrl AS pic,
			'.TBL_PRODUCTS.'.picUrlThumb AS picThumb,
			'.TBL_BRANDS.'.name AS brandName
			FROM
			'.TBL_USERS.',
			'.TBL_SHOPPING_LISTS.',
			'.TBL_SHOPPING_LIST_PRODUCTS.',
			'.TBL_PRODUCTS.',
			'.TBL_BRANDS.'
			WHERE
			'.$userID.' = '.TBL_SHOPPING_LISTS.'.userID AND
			'.$userID.' = '.TBL_USERS.'.id AND
			'.$listID.' = '.TBL_SHOPPING_LIST_PRODUCTS.'.shoppinglistID AND
			'.TBL_PRODUCTS.'.id = '.TBL_SHOPPING_LIST_PRODUCTS.'.productID AND
			'.TBL_BRANDS.'.id = '.TBL_PRODUCTS.'.brandID '.
			"ORDER BY " . TBL_SHOPPING_LIST_PRODUCTS . ".id";
		
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Querying database to retrieve list items @ database.php<br><code>$q</code></p>\n";}
		$result = mysql_query($q);
		if(!$result || mysql_num_rows($result) == 0)
		{
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p> Shopping list not found for list #$listID. Returned rows are 0, or query failed. @ database.php</p>\n";}
			return "No list items found.";
		}
		
		while($dbArray = mysql_fetch_assoc($result))
		{
			$productNameLower = strtolower($dbArray['ProductName']);
			if($dbArray['barcode'] == -1)
			{
				$barcode = "No EAN Code";
			}
			else 
			{
				$barcode = $dbArray['barcode'];
			}
			
			/*
			 * Generate the product picture
			 */
			$productThumb = DIR_IMAGES.$this->getProductThumb($dbArray['productID']);
			$productPic = DIR_IMAGES.$this->getProductPicture($dbArray['productID']);
			if(!$productPic)
			{
				$productPic = DIR_IMAGES."product-placeholder.png";
			}
			
			
			
			$listString .= '<article class="list-item">' .
								'<header>' .
									'<img src="'.$productThumb.'" alt="Thumbnail of product" class="product-pic-thumb">'.
									'<div class="product-text">'.
										'<span class="list-brand">'.$dbArray['brandName'].' </span>' . $productNameLower . '
									</div>
									<div class="quantity"><input type="text" class="quantity-input" value="'.$dbArray['quantity'].'" id="'.$dbArray['listItemID'].'"><span class="quantity-label">Kpl.</span></div>
									<a href="#" class="list-button list-button-small list-dropdown">expand info</a>											
									<a href="#" id="'.$dbArray['listItemID'].'" class="list-button list-remove" onClick="removeClicked(this)">remove from list</a>
									<a href="./index.php?page=edit&prodid='.$dbArray['productID'].'" class="list-button list-edit-info">edit item</a>' .
								'</header>' .
								"<footer>
										<img src=\"$productPic\" class=\"product-pic\" alt=\"Picture of product\">
										<div class=\"product-info\">
											<h3>product information</h3>
											<span class=\"ean-code\">$barcode</span>
											<p>Duis malesuada molestie mi, ac cursus lectus faucibus at. 
												Aenean at vestibulum diam. Class aptent taciti sociosqu ad litora torquent 
												per conubia nostra, per inceptos himenaeos. Mauris vitae pretium felis.</p>
										</div>
								</footer>".
							'</article>';
		}
		
		$listString .= "<div class=\"dashboard-loading\"></div>";

		return $listString;
	}
	
	/**
	 * Return a formatted list of products found from a search term
	 * @param string $searchTerm
	 * @return string|boolean
	 */
	function returnSearchList($searchTerm)
	{
		$returnString = "";
		$numItemsFound = 0;
		$q = "SELECT ".TBL_PRODUCTS.".id AS productID,
			".TBL_PRODUCTS.".name AS productName,
			".TBL_PRODUCTS.".brandID AS productBrandID,
			".TBL_PRODUCTS.".picUrl AS pic,
			".TBL_PRODUCTS.".picUrlThumb AS picThumb,
			".TBL_PRODUCTS.".barcode AS barcode,
			".TBL_BRANDS.".id AS brandID,
			".TBL_BRANDS.".name AS brandName
		FROM ".TBL_PRODUCTS.", ".TBL_BRANDS."
		WHERE
		(".TBL_PRODUCTS.".name LIKE \"%$searchTerm%\" OR
				".TBL_BRANDS.".name LIKE \"%$searchTerm%\") AND
				".TBL_BRANDS.".id = ".TBL_PRODUCTS.".brandID";
		//"SELECT products.id, products.name AS name FROM products WHERE products.name LIKE \"%$searchTerm%\" LIMIT 0,10";
		
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Searching products @ database.php</p>\n";}
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p><code>$q</code></p>\n";}
		$result = mysql_query($q);
		if(mysql_num_rows($result) == 0)
		{
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>No results found. Returned rows are 0, or query failed. @ database.php</p>\n";}
			return false;
		}
		while($dbArray = mysql_fetch_assoc($result))
		{
			$productNameLower = strtolower($dbArray['productName']);
			$numItemsFound++;
			$returnString .= 
			"<article class=\"list-item\">
				<header>
					<img src=\"".DIR_IMAGES.$dbArray['picThumb']."\" alt=\"Thumbnail of product\" class=\"product-pic-thumb\">
					<div class=\"product-text\">
						<span class=\"list-brand\">".$dbArray['brandName']." </span>$productNameLower
					</div>
					<a href class=\"list-button list-button-small list-dropdown\">expand info</a>	
					<a href id=\"".$dbArray['productID']."\" class=\"list-button list-add\">Add to List</a>
					<a href=\"./index.php?page=edit&prodid=" . $dbArray['productID'] . "\" class=\"list-button list-edit-info\">edit item</a>
				</header>
				<footer>
					<img src=\"".DIR_IMAGES.$dbArray['pic']."\" class=\"product-pic\" alt=\"Picture of product\">
					<div class=\"product-info\">
						<h3>product information</h3>
						<span class=\"ean-code\">".$dbArray['barcode']."</span>
						<p>Duis malesuada molestie mi, ac cursus lectus faucibus at.
						Aenean at vestibulum diam. Class aptent taciti sociosqu ad litora torquent
						per conubia nostra, per inceptos himenaeos. Mauris vitae pretium felis.</p>
					</div>
				</footer>
			</article>";
			
			
		}
		return $returnString;
	}
	
	
	
	/**
	 * Update a shopping list product row with a new quantity
	 * 
	 * @param int $listItemID
	 * @param int $quantity
	 * @return boolean
	 */
	function listItemQuantity($listItemID, $quantity)
	{
		$query = "UPDATE ".TBL_SHOPPING_LIST_PRODUCTS." SET quantity = $quantity WHERE ".TBL_SHOPPING_LIST_PRODUCTS.".id = $listItemID";
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Quantity update query: <code>$query</code></p>\n";}
		$result = mysql_query($query);
		if(!$result)
		{
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Quantity could not be changed. Returned rows are 0, or query failed. @ database.php</p>\n";}
			return false;
		}
		return true;
	}
	
	
	/**
	 * 
	 * @param unknown_type $userID
	 * @param unknown_type $listID
	 * @return boolean|string
	 */
	function returnFakeSort($userID, $listID)
	{
		$definedStoreIDsDemo01 = array(3,70);
		
		
		$selloPrismaProductsArray = array();
		$selloKchainProductsArray = array();
		
		$prismaTotal = 0;
		$kchainTotal = 0;
		
		
		
		$getUserListQueryString = "SELECT 
		".TBL_PRICES.".id AS priceID, 
		".TBL_PRODUCTS.".id productID, 
		".TBL_PRODUCTS.".name AS productName, 
		".TBL_PRICES.".shopID AS shopID, 
		".TBL_CHAINS.".name AS chainName, 
		".TBL_SHOPS.".name AS shopLocation,
		min(".TBL_PRICES.".price) AS productPrice
		
		FROM ".TBL_PRODUCTS.", ".TBL_PRICES.", ".TBL_SHOPS.", ".TBL_CHAINS.", ".TBL_SHOPPING_LISTS.", ".TBL_SHOPPING_LIST_PRODUCTS."
		
		WHERE 
		".TBL_SHOPPING_LISTS.".userID = $userID AND
		".TBL_SHOPPING_LIST_PRODUCTS.".shoppingListID = $listID AND
		".TBL_SHOPPING_LIST_PRODUCTS.".ProductID = ".TBL_PRODUCTS.".id AND
		".TBL_PRICES.".productID = ".TBL_PRODUCTS.".id AND
		(".TBL_PRICES.".shopID = 3 OR ".TBL_PRICES.".shopID = 70) AND
		".TBL_SHOPS.".id = ".TBL_PRICES.".shopID AND
		".TBL_CHAINS.".id = ".TBL_SHOPS.".chainID
		GROUP BY ".TBL_PRODUCTS.".id
		ORDER BY ".TBL_PRICES.".id";
		
		
		/*"SELECT
		shoppinglists.id,
		shoppinglists.userID,
		shoppinglistproducts.id,
		shoppinglistproducts.shoppingListID,
		shoppinglistproducts.ProductID,
		products.id,
		products.name AS productName,
		prices.price AS productPrice,
		prices.productID,
		prices.shopID,
		shops.id,
		shops.name AS shopLocation,
		shops.chainID,
		chains.id,
		chains.name AS chainName
		FROM
		shoppinglists, shoppinglistproducts, products, prices, shops, chains
		WHERE
		shoppinglists.userID = $userID AND
		shoppinglistproducts.shoppingListID = $listID AND
		shoppinglistproducts.ProductID = products.id AND
		prices.productID = products.id AND
		shops.id = prices.shopID AND
		chains.id = shops.chainID
		GROUP BY products.id
		ORDER BY shops.id";*/
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p><code>$getUserListQueryString</code></p>\n";}
		$getUserListQuery = mysql_query($getUserListQueryString);
		while($getUserList = mysql_fetch_assoc($getUserListQuery))
		{
			/*
				*	Add the products to arrays, depending on which store they're at.
			*/
			if($getUserList['shopID'] == 3)
			{
				array_push($selloPrismaProductsArray, '<div class="sorted-product '.strtolower($getUserList['shopLocation'].'-'.$getUserList['chainName']).'">
				<div class="price-info">'.strtolower($getUserList['productName']).' &ndash; <strong>&euro;'.$getUserList['productPrice'].'</strong></div>
			</div>');
					
				$prismaTotal += $getUserList['productPrice'];
			}
			elseif($getUserList['shopID'] == 70)
			{
				array_push($selloKchainProductsArray, '<div class="sorted-product '.strtolower($getUserList['shopLocation'].'-'.$getUserList['chainName']).'">
				<div class="price-info">'.strtolower($getUserList['productName']).' &ndash; <strong>&euro;'.$getUserList['productPrice'].'</strong></div>
			</div>');
					
				$kchainTotal += $getUserList['productPrice'];
			}
			else
			{
				return false;
			}
		
		
		}
		$returnString = "";
		//print each store's list in it's own table
		$returnString .= '<article class="store-list">';
		$returnString .= '<h2>Sello &ndash; Prisma</h2>';
		foreach($selloPrismaProductsArray as $sortedProduct)
		{
			$returnString .= $sortedProduct;
		}
		$returnString .= '<footer class="store-price-total">total: '.$prismaTotal.'&euro;</footer>';
		$returnString .= '</article>';
		
		$returnString .= '<article class="store-list">';
		$returnString .= '<h2>Sello &ndash; K-Citymarket</h2>';
		foreach($selloKchainProductsArray as $sortedProduct)
		{
			$returnString .= $sortedProduct;
		}
		$returnString .= '<footer class="store-price-total">total: '.$kchainTotal.'&euro;</footer>';
		$returnString .= '</article>';
		$sumTotal = $kchainTotal + $prismaTotal;
		$returnString .= '<footer class="list-price-total">sum total: '.$sumTotal.'&euro;</footer>';
		
		return $returnString;
	}


	/**
	 * Bring back a sorted version of the shopping list, based on the inputted location.
	 * Then format them based on which shop they are to be found
	 * 
	 * 1. Get list of stores based in location name input
	 * 2. Generate a total list price for each store separately
	 * 3. Find the cheapest price between stores
	 * 
	 * @param int $userID
	 * @param int $listID
	 * @param string $location
	 * @return boolean|string
	 */
	function returnSortedList($userID, $listID, $location)
	{
		global $constants;
		$definedStoreIdArray = array();
		
		/*
		 * Query the database to find a list of shops matching the location string
		 */
		$q_location = "SELECT
						".TBL_SHOPS.".id AS shopID,
						".TBL_SHOPS.".name AS shopLocation,
						".TBL_SHOPS.".city AS shopCity,
						".TBL_CHAINS.".id AS chainID,
						".TBL_CHAINS.".name AS chainName
						FROM ".TBL_SHOPS.", ".TBL_CHAINS."
						WHERE 
						(".TBL_SHOPS.".name LIKE \"$location\" OR
						".TBL_SHOPS.".city LIKE \"$location\") AND
						".TBL_CHAINS.".id = ".TBL_SHOPS.".chainID";
		$result = mysql_query($q_location);
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p><code>$q_location</code></p>\n";}
		if(!$result || mysql_num_rows($result) == 0)
		{
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>No location found at $location</p>\n";}
			//Something
			return false;
		}
		else 
		{	
		
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Found location, fetching an array of the locations @ database.php</p>\n";}
			
			/*
			 * Add each of the found shops under an array, with the key
			 * being their shopID, and the value being the shop name
			 * 
			 * Shop ID => chain name
			 */
			while($locationDbArray = mysql_fetch_assoc($result))
			{
				$definedStoreIdArray[$locationDbArray['shopID']] = $locationDbArray['chainName'];
				if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Added shop ".$locationDbArray['shopID']."</p>\n";}
				
				$shopIDArray[] = $locationDbArray['shopID'];
			}
			
			//Create an sql snippet of the stores found at the location
			$q_chains_part = "(".TBL_SHOPS.".id = ". implode(" OR ".TBL_SHOPS.".id = ", $shopIDArray) . ")";
			
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p><code>$q_chains_part</code></p>\n";}
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Collecting the products in the database, and their cheapest prices from the found shops @ database.php</p>\n";}
			
			
			
			/*************************************************************
			 * FIND TOTALS FOR EACH STORE
			*************************************************************/
			
			$shopTotals = array();
			
			//Loop through each store, which were found in the previous algorithm
			foreach($definedStoreIdArray AS $shopID => $chainName)
			{
				// set variable for the store
				$shopTotals[$shopID] = 0;
				
				//Query to get all the products from the list, with the latest prices for each product.
				$q_shopLatestPrice = 'SELECT
				'.TBL_SHOPPING_LISTS.'.id,
				'.TBL_SHOPPING_LISTS.'.userID,
				'.TBL_SHOPPING_LIST_PRODUCTS.'.id AS listItemID,
				'.TBL_SHOPPING_LIST_PRODUCTS.'.shoppingListID,
				'.TBL_SHOPPING_LIST_PRODUCTS.'.ProductID,
				'.TBL_SHOPPING_LIST_PRODUCTS.'.quantity AS quantity,
				'.TBL_PRODUCTS.'.id AS productID,
				'.TBL_PRODUCTS.'.name AS productName,
				'.TBL_PRICES.'.price AS price,
				min('.TBL_PRICES.'.created)
				FROM
				'.TBL_USERS.',
				'.TBL_SHOPPING_LISTS.',
				'.TBL_SHOPPING_LIST_PRODUCTS.',
				'.TBL_PRODUCTS.',
				'.TBL_PRICES.',
				'.TBL_SHOPS.'
				WHERE
				'.$userID.' = '.TBL_SHOPPING_LISTS.'.userID AND
				'.$userID.' = '.TBL_USERS.'.id AND
				'.$listID.' = '.TBL_SHOPPING_LIST_PRODUCTS.'.shoppinglistID AND
				'.TBL_PRODUCTS.'.id = '.TBL_SHOPPING_LIST_PRODUCTS.'.productID AND ' .
				TBL_PRICES.'.productID = '.TBL_PRODUCTS.'.id AND ' .
				TBL_SHOPS.'.id = ' . $shopID . ' AND ' .
				TBL_PRICES.'.shopID = '.TBL_SHOPS.'.id' .
				' GROUP BY '.TBL_PRICES.'.id';
				
				if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p><code>$q_shopLatestPrice</code></p>\n";}
				
				$result = mysql_query($q_shopLatestPrice);
				if(!$result)
				{
					if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Query failed: <code>".mysql_error()."</code></p>\n";}
					return false;
				}
				elseif(mysql_num_rows($result) == 0)
				{
					if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>No prices for this store</p>\n";}
				}
				
				// Loop through all the products found at this shop, and add each price to the total
				while($dbArray = mysql_fetch_assoc($result))
				{
					$productTotalPrice = $dbArray["price"] * $dbArray["quantity"];
					$shopTotals[$shopID] += $productTotalPrice;
					if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Added " . $dbArray["price"] . " to total.</p>\n";}
				}
				
			}//FOREACH
			
			
			/*
			 * ESTIMATED SAVINGS CALCULATION
			 * 
			 *  - Select all individual products in list
			 *  - for each of these, get a price in all shops from defined list and add to array
			 *  - find the sum of the values in the array, then devide by the count to get the average
			 *  - add this average to a variable
			 */
			
			// Get a list of individual products in list
			$q_estimatedSaved = "SELECT
								" . TBL_PRODUCTS . ".id AS productID,
								" . TBL_PRODUCTS . ".name,
								" . TBL_SHOPPING_LIST_PRODUCTS . ".quantity
								FROM
								" . TBL_PRODUCTS . ", " . TBL_SHOPPING_LISTS . ", " . TBL_SHOPPING_LIST_PRODUCTS . ", " . TBL_USERS . "
								WHERE
								" . TBL_USERS . ".id = $userID AND
								" . TBL_SHOPPING_LISTS . ".id = $listID AND
								" . TBL_SHOPPING_LISTS . ".id = " . TBL_SHOPPING_LIST_PRODUCTS . ".shoppinglistID AND
								" . TBL_SHOPPING_LIST_PRODUCTS . ".productID = " . TBL_PRODUCTS . ".id";
			
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p><code>$q_estimatedSaved</code></p>\n";}
			$result_estimatedSaved = mysql_query($q_estimatedSaved);
			if (!$result_estimatedSaved)
			{
				if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Estimated saved query failed: <code>".mysql_error()."</code></p>\n";}
				return false;
			}
			elseif(mysql_num_rows($result_estimatedSaved) == 0)
			{
				if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>No products found</p>\n";}
			}
			
			//Define list average total var
			$totalAverage = 0;
			$totalAverageArray = array();
			
			/*
			 * Array to store store prices for av calc
			 * Note: key are redundant as the goal is the final total figure.
			 * 
			 * totalAverageArray =
			 * 		productID => productPriceArray = 
			 * 							key => price
			 * 
			 * FOREACH product in totalAverageArray
			 * 		productAverageVar = sum(productPriceArray) / count(productPriceArray)
			 * 		totalAverage += productAverageVar
			 * END FOREACH
			 */
			
			// Loop through each product on list
			while($dbArray_estimatedSaved = mysql_fetch_assoc($result_estimatedSaved))
			{
				// set a value in the total array, with key being product ID and 
				// value as an array of the prices in each shop
				$totalAverageArray[$dbArray_estimatedSaved['productID']] = array();
				// For each shop counted towards sort, find the latest price for the product
				foreach($definedStoreIdArray AS $shopID => $value)
				{
					$q_pricesEachShop = "SELECT
									" . TBL_PRICES . ".price,
									" . TBL_PRICES . ".shopID,
									min(" . TBL_PRICES . ".created), 
									" . TBL_SHOPPING_LIST_PRODUCTS . ".quantity
									FROM " . TBL_PRICES . ", " . TBL_SHOPPING_LISTS . ", " . TBL_SHOPPING_LIST_PRODUCTS . ", " . TBL_USERS . "
									WHERE
									" . TBL_USERS . ".id = $userID AND
									" . TBL_PRICES . ".shopID = " . $shopID . " AND
									" . TBL_SHOPPING_LISTS . ".id = $listID AND
									" . TBL_SHOPPING_LIST_PRODUCTS . ".shoppingListID = " . TBL_SHOPPING_LISTS . ".id AND
									" . TBL_SHOPPING_LIST_PRODUCTS . ".productID = " . $dbArray_estimatedSaved['productID'] . " AND
									" . TBL_SHOPPING_LIST_PRODUCTS . ".productID = " . TBL_PRICES . ".productID 
									GROUP BY " . TBL_PRICES . ".id";
					if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p><code>$q_pricesEachShop</code></p>\n";}
					$result = mysql_query($q_pricesEachShop);
					if(!$result)
					{
						if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>query failed: <code>" . mysql_error() . "</code></p>\n";}
						return false;
					}
					// return the results of the query
					$dbArray = mysql_fetch_assoc($result);
					
					// Push the price for each shop into the sub-array in totalAverageArray
					array_push($totalAverageArray[$dbArray_estimatedSaved['productID']], $dbArray['price'] * $dbArray['quantity']);
					
					if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>added ". $dbArray['price'] * $dbArray['quantity'] . " to array
							<br>ProductID = " . $dbArray_estimatedSaved['productID'] . "</p>\n";}
				}
				
				
			}
			
			// Debug function to dump the contents of the totalAvarageArray var
			if(DEBUG_MODE){
				ob_start();
				var_dump($totalAverageArray);
				$totalArrayDump = ob_get_clean();
				$_SESSION['debug_info'] .= "<p>Info in total array: <br>$totalArrayDump</p>\n";
			}
			
			/*
			 * loop through all the collected prices and find the average for each product,
			 * and add this to the total average
			 */
			foreach($totalAverageArray AS $key => $priceArray)
			{
				$sum = 0;
				$count = 0;
				// Itterate through the price array and add to the count if the 
				// value is not zero
				// i.e. only count towards average if there is a price
				foreach($priceArray AS $value)
				{
					if($value > 0)
					{
						$count++;
					}
				}
				// check to make sure it doesnt devide by zero
				if($count > 0)
				{
					$average = array_sum($priceArray) / $count;
				}
				else
				{
					$average = 0;
				}
				$totalAverage += $average;
				if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>added $average to total average ($totalAverage)</p>\n";}
			}
			
			
			/*
			 * GET MINIMUM PRICES FOR EACH PRODUCT AND OUTPUT LIST
			 */
			
			/*
			 * New query to return sorted list with lowest prices
			 */
			
			$q_priceSort = 	"SELECT 
							".TBL_PRODUCTS.".id AS productID, 
							".TBL_PRODUCTS.".name AS productName, 
							min(".TBL_PRICES.".price) AS price, 
							".TBL_PRICES.".id AS priceID, 
							".TBL_SHOPS.".id AS shopID, 
							".TBL_SHOPPING_LIST_PRODUCTS.".quantity AS quantity,
							".TBL_BRANDS.".name AS brandName
							FROM ".TBL_PRODUCTS.", ".TBL_PRICES.", ".TBL_SHOPPING_LISTS.", ".TBL_SHOPPING_LIST_PRODUCTS.", ".TBL_USERS.", ".TBL_SHOPS.", ".TBL_BRANDS."
							WHERE
							".TBL_USERS.".id = $userID AND
							".TBL_SHOPPING_LIST_PRODUCTS.".shoppinglistID = $listID AND
							".TBL_SHOPPING_LISTS.".id = ".TBL_SHOPPING_LIST_PRODUCTS.".shoppinglistID AND
							".TBL_SHOPPING_LIST_PRODUCTS.".productID = ".TBL_PRODUCTS.".id AND
							".TBL_PRICES.".productID = ".TBL_PRODUCTS.".id AND
							".TBL_PRICES.".shopID = ".TBL_SHOPS.".id AND
							$q_chains_part AND
							".TBL_BRANDS.".id = ".TBL_PRODUCTS.".brandID
							GROUP BY ".TBL_PRODUCTS.".id";
			
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Query to retrieve minimum prices for list items, and their relevant shop information</p>\n";}
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p><code>$q_priceSort</code></p>\n";}
			$result_priceSort = mysql_query($q_priceSort);
			if(!$result_priceSort)
			{
				if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Could not return minimum prices</p>\n";}
				return false;
			}
			
			/*
			 * Define variables in array for each shop
			 */
			foreach($definedStoreIdArray AS $shopID => $storeName)
			{
				if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Defining variables for shop #$shopID</p>\n";}
				//Total for each shop
				$sortedListShopTotal[$shopID] = 0;
				//Define string for each shop
				$sortedListArray[$shopID] = "<article class=\"sorted-list\"><h2>".$this->getChainName($shopID)."</h2>";

				if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>$sortedListArray[$shopID]</p>\n";}
			}
			
			/*
			 * add the content of each list
			 */
			while($dbArray_priceSort = mysql_fetch_assoc($result_priceSort))
			{
				if(!isSet($sortedListShopTotal[$dbArray_priceSort['shopID']]))
				{
					$sortedListShopTotal[$dbArray_priceSort['shopID']] = 0;
				}
				/*
				 * formatted list item
				 */
				$finalPrice = $dbArray_priceSort['price'] * $dbArray_priceSort['quantity'];
				
				if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Adding item to shop #" .$dbArray_priceSort['shopID']. "'s sorted list</p>\n";}
				$priceDecimalFix = str_replace('.', ',', strval($finalPrice));
				
				$sortedListArray[$dbArray_priceSort['shopID']] .= "<div class=\"sorted-list-item\">\n
																	".$dbArray_priceSort['quantity']."&times; <span class=\"list-brand\">".$dbArray_priceSort['brandName']." "."</span>".$dbArray_priceSort['productName']." &mdash; ".$priceDecimalFix."&euro;\n
																	</div>";
				/*
				 * increment the total for the store's list
				 */
				$sortedListShopTotal[$dbArray_priceSort['shopID']] += $dbArray_priceSort['price'] * $dbArray_priceSort['quantity'];
			}
			
			/*
			 * Close off each list and find total list price
			 */
			$sortedListTotalPrice = 0;
			foreach($definedStoreIdArray AS $shopID => $storeName)
			{
				$shopTotalStringFormat =  str_replace('.', ',', strval(round($sortedListShopTotal[$shopID], 2)));
				$sortedListArray[$shopID] .= "<footer><strong>Total for store: ".$shopTotalStringFormat."&euro;</strong></footer></article>";
				$sortedListTotalPrice += $sortedListShopTotal[$shopID];
			}
			
			//var_dump($sortedListArray);
			foreach($sortedListArray AS $key => $value)
			{
				if(DEBUG_MODE){$_SESSION['debug_info'] .= "<h3>Shop #$key</h3><p>$value</p>\n";}
			}
			
			/*
			 * Calculate the total amount saved with this list sort
			 */
			
			/*
			 * Round float value to 2 decimals, convert it to string then replace the decimal to a comma.
			 */
			// $savedAmount =  str_replace('.', ',', strval(round($this->calcSaved($shopTotals, $sortedListTotalPrice), 2)));
			$savedCalc = $totalAverage - $sortedListTotalPrice;
			$savedAmount = str_replace('.', ',', strval(round($savedCalc, 2)));
			
			$listBackLink = "<button class=\"btn return-to-list\">return to list</button>";
			$sortedListString = $listBackLink . implode("\n", $sortedListArray) . "<div class=\"total-saved\">total saved = $savedAmount&euro;</div>";
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>$sortedListString</p>\n";}
			
			return $sortedListString;
		}// END IF
	}// END FUNCTION
	
	/**
	 * Find list of shops based on the input query
	 * i.e. an address, post code, city etc.
	 * 
	 * @param string $location
	 * @return boolean|multitype:Ambigous Array of shops i.e. {Shop ID => Chain Name}
	 */
	function getSortLocations($location, $returnSQL = false)
	{
		$query = "SELECT
						".TBL_SHOPS.".id AS shopID,
						".TBL_SHOPS.".name AS shopLocation,
						".TBL_SHOPS.".city AS shopCity,
						".TBL_CHAINS.".id AS chainID,
						".TBL_CHAINS.".name AS chainName
						FROM ".TBL_SHOPS.", ".TBL_CHAINS."
						WHERE
						(".TBL_SHOPS.".name LIKE \"$location\" OR
								".TBL_SHOPS.".city LIKE \"$location\") AND
								".TBL_CHAINS.".id = ".TBL_SHOPS.".chainID";
		

		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p><code>$query</code></p>\n";}
		
		$result = mysql_query($query);
		
		if(!$result)
		{
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Query failed: <code>".mysql_error()."</code></p>\n";}
			return false;
		}
		
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Found location, fetching an array of the locations @ database.php</p>\n";}
			
		/*
		 * Add each of the found shops under an array, with the key
		* being their shopID, and the value being the shop name
		*
		* Shop ID => chain name
		*/
		$definedStoreIdArray = array();
		while($locationDbArray = mysql_fetch_assoc($result))
		{
			$definedStoreIdArray[$locationDbArray['shopID']] = $locationDbArray['chainName'];
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Added shop ".$locationDbArray['shopID']."</p>\n";}
		}
			
	
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Collecting the products in the database";}
		return $definedStoreIdArray;
	}
	
	/**
	 * Get a list of the items in the user's shopping list.
	 * 
	 * @param int $userID
	 * @param int $listID
	 * @return boolean|multitype: array of items in nested arrays
	 */
	function getUserListInfo($userID, $listID, $locationArray)
	{
		// Get a list of individual products in list
		$query = "SELECT
				" . TBL_PRODUCTS . ".id AS productID,
				" . TBL_PRODUCTS . ".name,
				" . TBL_PRODUCTS . ".picUrl,
				" . TBL_PRODUCTS . ".barcode,
				" . TBL_PRODUCTS . ".categoryID,
				" . TBL_SHOPPING_LIST_PRODUCTS . ".id AS listItemID,
				" . TBL_SHOPPING_LIST_PRODUCTS . ".quantity,
				" . TBL_BRANDS . ".name AS brandName
				FROM
				" . TBL_PRODUCTS . ", " . TBL_SHOPPING_LISTS . ", " . TBL_SHOPPING_LIST_PRODUCTS . ", " . TBL_USERS . ", " . TBL_BRANDS . "
				WHERE
				" . TBL_USERS . ".id = $userID AND
				" . TBL_SHOPPING_LISTS . ".id = $listID AND
				" . TBL_SHOPPING_LISTS . ".id = " . TBL_SHOPPING_LIST_PRODUCTS . ".shoppinglistID AND
				" . TBL_SHOPPING_LIST_PRODUCTS . ".productID = " . TBL_PRODUCTS . ".id AND
				" . TBL_BRANDS . ".id = " . TBL_PRODUCTS . ".brandID";
			
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p><code>$query</code></p>\n";}
		
		$result = mysql_query($query);
		
		if (!$result)
		{
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Estimated saved query failed: <code>".mysql_error()."</code></p>\n";}
			return false;
		}
		elseif(mysql_num_rows($result) == 0)
		{
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>No products found</p>\n";}
		}
		
		$productArray = array();
		$shopIDArray = array();
		foreach ($locationArray AS $shopID => $value)
		{
			$shopIDArray[] = $shopID;
		}
		
		// Create an sql snippet of the stores found at the location
		$locationSQL = "(".TBL_SHOPS.".id = ". implode(" OR ".TBL_SHOPS.".id = ", $shopIDArray) . ")";
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p><code>$locationSQL</code></p>\n";}
		
		
		/*
		 * For each product found, find the price at each of the shops found for the location.
		 */
		while($dbArray = mysql_fetch_assoc($result))
		{
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Starting to find prices for product #" . $dbArray['productID'] . "</p>\n";}
			// Basic product information
			$productArray[$dbArray['productID']] = array("quantity" => $dbArray['quantity'], 
														"listItemID" => $dbArray['listItemID'], 
														"name" => $dbArray['name'],
														"brand" => $dbArray['brandName'],
														"barcode" => $dbArray['barcode'],
														"category" => $dbArray['categoryID'],
														"pic" => $dbArray['picUrl']
														);
			
			
			/*
			 * For each of the shops,
			* get product price info
			*/
			$query_prices =
			"SELECT
			" .TBL_PRODUCTS. ".id AS productID,
			" .TBL_PRICES. ".price AS price,
			max(" .TBL_PRICES. ".created),
			" .TBL_SHOPS. ".id AS shopID
			FROM
			" .TBL_USERS. ", " .TBL_SHOPPING_LISTS. ", " .TBL_SHOPPING_LIST_PRODUCTS. ", " .TBL_PRODUCTS. ", " .TBL_PRICES. ", " .TBL_SHOPS. "
			WHERE
			$userID = " .TBL_USERS. ".id AND
			$listID = " .TBL_SHOPPING_LISTS. ".id AND
			" . $dbArray['productID'] . " = " .TBL_PRODUCTS. ".id AND
			" .TBL_PRICES. ".productID = " .TBL_PRODUCTS. ".id AND
			" .TBL_PRICES. ".shopID = " .TBL_SHOPS. ".id AND 
			$locationSQL 
			GROUP BY " .TBL_SHOPS. ".id";
				
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p><strong>Query to get product prices:</strong><br><code>$query_prices</code></p>\n";}
			
			$result_prices = mysql_query($query_prices);
			if(!$result_prices)
			{
				if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Query failed: <code>".mysql_error()."</code></p>\n";}
				return false;
			}
			
			// Define the array to hold the product's prices
			//array_push($pricesArray[$dbArray['productID']], array());
			$productArray[$dbArray['productID']]['prices'] = array();
			
			// Loop through all the products found at this shop, and add each product's price to an array
			while($dbArray_prices = mysql_fetch_assoc($result_prices))
			{
				$productArray[$dbArray['productID']]['prices'][$dbArray_prices['shopID']] = $dbArray_prices['price'];
				
				if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Added " . $dbArray_prices["price"] . " to total.</p>\n";}
			}
		}
		
		/*
		 * dump the array to debug
		 */
		if(DEBUG_MODE)
		{
			$productArrayDumpString = $this->dumpArray($productArray);
			$_SESSION['debug_info'] .= "<p>product info array: <br> $productArrayDumpString</p>\n";
		}
		
		
		return $productArray;
		
		
		
		
		
		
		// Declare the array that will hold the prices for each shop
		$pricesArray = array();
		
		foreach($productArray AS $productID => $infoArray)
		{
			/*
			 * For each of the shops,
			 * get product price info
			*/
			$query = 
			"SELECT
			" .TBL_PRODUCTS. ".id,
			" .TBL_PRICES. ".price,
			min(" .TBL_PRICES. ".created),
			" .TBL_SHOPS. ".id
			FROM
			" .TBL_USERS. ", " .TBL_SHOPPING_LISTS. ", " .TBL_SHOPPING_LIST_PRODUCTS. ", " .TBL_PRODUCTS. ", " .TBL_PRICES. ", " .TBL_SHOPS. "
			WHERE
			$userID = " .TBL_USERS. ".id AND
			$listID = " .TBL_SHOPPING_LISTS. ".id AND
			$productID = " .TBL_PRODUCTS. ".id AND
			" .TBL_PRICES. ".productID = " .TBL_PRODUCTS. ".id AND
			" .TBL_PRICES. ".shopID = " .TBL_SHOPS. ".id
			GROUP BY prices.id";
					
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p><code>$query</code></p>\n";}
		
			$result = mysql_query($query);
			if(!$result)
			{
				if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Query failed: <code>".mysql_error()."</code></p>\n";}
				return false;
			}
			
			
			// Loop through all the products found at this shop, and add each product's price to an array
			while($dbArray = mysql_fetch_assoc($result))
			{
				$productTotalPrice = $dbArray["price"] * $dbArray["quantity"];
				$shopTotals[$shopID] += $productTotalPrice;
				
				
				array_push($pricesArray[$shopID], $dbArray['price']); 
				
				if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Added " . $dbArray["price"] . " to total.</p>\n";}
			}
				
		}
		return $productArray;
		
		
		
	}
	
	
	function getListPrices($list)
	{
		/*************************************************************
		 * FIND TOTALS FOR EACH STORE
		*************************************************************/
			
		$shopTotals = array();
			
		//Loop through each store, which were found in the previous algorithm
		foreach($definedStoreIdArray AS $shopID => $chainName)
		{
			// set variable for the store
			$shopTotals[$shopID] = 0;
		
			//Query to get all the products from the list, with the latest prices for each product.
			$query = 'SELECT
				'.TBL_SHOPPING_LISTS.'.id,
				'.TBL_SHOPPING_LISTS.'.userID,
				'.TBL_SHOPPING_LIST_PRODUCTS.'.id AS listItemID,
				'.TBL_SHOPPING_LIST_PRODUCTS.'.shoppingListID,
				'.TBL_SHOPPING_LIST_PRODUCTS.'.ProductID,
				'.TBL_SHOPPING_LIST_PRODUCTS.'.quantity AS quantity,
				'.TBL_PRODUCTS.'.id AS productID,
				'.TBL_PRODUCTS.'.name AS productName,
				'.TBL_PRICES.'.price AS price,
				min('.TBL_PRICES.'.created)
				FROM
				'.TBL_USERS.',
				'.TBL_SHOPPING_LISTS.',
				'.TBL_SHOPPING_LIST_PRODUCTS.',
				'.TBL_PRODUCTS.',
				'.TBL_PRICES.',
				'.TBL_SHOPS.'
				WHERE
				'.$userID.' = '.TBL_SHOPPING_LISTS.'.userID AND
				'.$userID.' = '.TBL_USERS.'.id AND
				'.$listID.' = '.TBL_SHOPPING_LIST_PRODUCTS.'.shoppinglistID AND
				'.TBL_PRODUCTS.'.id = '.TBL_SHOPPING_LIST_PRODUCTS.'.productID AND ' .
				TBL_PRICES.'.productID = '.TBL_PRODUCTS.'.id AND ' .
				TBL_SHOPS.'.id = ' . $shopID . ' AND ' .
				TBL_PRICES.'.shopID = '.TBL_SHOPS.'.id' .
				' GROUP BY '.TBL_PRICES.'.id';

				if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p><code>$query</code></p>\n";}

				$result = mysql_query($query);
				if(!$result)
				{
					if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Query failed: <code>".mysql_error()."</code></p>\n";}
					return false;
				}
				elseif(mysql_num_rows($result) == 0)
				{
					if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>No prices for this store</p>\n";}
				}

				// Loop through all the products found at this shop, and add each price to the total
				while($dbArray = mysql_fetch_assoc($result))
				{
					$productTotalPrice = $dbArray["price"] * $dbArray["quantity"];
					$shopTotals[$shopID] += $productTotalPrice;
					if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Added " . $dbArray["price"] . " to total.</p>\n";}
				}
		
		}//FOREACH
	}
	
	
	/**
	 * Calculate the approximate amount saved from the shop price totals given in the array
	 * Determine the average of the shop totals, and subtract the sorted list total from this value.
	 * 
	 * @param array $shopPriceTotals
	 * @param int $sortedListTotal
	 * @return number|boolean
	 */
	function calcSaved($shopPriceTotals, $sortedListTotal)
	{
		if(empty($shopPriceTotals))
		{
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>no shop totals in shop total array</p>\n";}
			return false;
		}
		elseif(empty($sortedListTotal))
		{
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>sorted list total is empty</p>\n";}
			return false;
		}
		$savedAmount = 0;
		$sumShopTotals = 0;
		foreach($shopPriceTotals AS $shopID => $value)
		{
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>shop total for $shopID: &euro;$value</p>\n";}
			$sumShopTotals += $value;
		}
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>sum of shop totals: &euro;$sumShopTotals</p>\n";}
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>sorted list total: &euro;$sortedListTotal</p>\n";}
		
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>number of stores involved: ".count($shopPriceTotals)."</p>\n";}
		
		$numStores = count($shopPriceTotals);
		$averageShopTotal = $sumShopTotals / $numStores;
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>average shop totals: &euro;$averageShopTotal</p>\n";}
		$savedAmount = $averageShopTotal - $sortedListTotal;
		
		return abs($savedAmount);
	}
	
	/**
	 * Get a particular product price, at a specific shop. 
	 * 
	 * @param int $productID
	 * @param int $shopID
	 * @return boolean|float
	 */
	function getProductPrice($productID, $shopID, $returnArray = false)
	{
		
		$query = "SELECT prices.id, prices.price, max(prices.created) FROM prices, shops, products
		WHERE
		prices.productID = $productID AND
		shops.id = $shopID AND
		prices.shopID = shops.ID
		GROUP BY prices.id";
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p><code>$query</code></p>\n";}
	
		
		$result = mysql_query($query);
		if(!$result)
		{
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Price select query failed:<br>
														<code>".mysql_error()."</code></p>\n";}
			return false;
		}
		$dbArray = mysql_fetch_assoc($result);
		
		if($returnArray)
		{
			return $dbArray;
		}
		
		return $dbArray['price'];
	}
	
	/**
	 * Check whether the product at the specified shop has an active discount. 
	 * If the current time is within or equal to the start and end date, it will return true.
	 * 
	 * @param int $productID
	 * @param int $shopID
	 * @return boolean
	 */
	function productHasDiscount($productID, $shopID)
	{
		$currentDate = date("Y-m-d");
		//$priceID = $this->getProductPrice($productID, $shopID, true);
		
		/*$query = "SELECT 
				prices.id, prices.price, max(prices.created), prices.startDate, prices.endDate
				FROM
				prices, products, shops
				WHERE
				prices.shopID = shops.id AND
				shops.id = $shopID AND
				prices.productID = products.id AND
				products.id = $productID
				GROUP BY prices.id";*/
		$query = "SELECT 
				TIMESTAMPDIFF(DAY, 'prices.startDate', 'prices.endDate') AS hasDiscount, max(prices.created)
				FROM prices, products, shops
				WHERE
				prices.shopID = shops.id AND
				shops.id = $shopID AND
				prices.productID = products.id AND
				products.id = $productID
				GROUP BY prices.id";
		
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p><code>$query</code></p>\n";}
		$result = mysql_query($query);
		if(!$result)
		{
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Price select query failed:<br>
														<code>".mysql_error()."</code></p>\n";}
			return false;
		}
		$dbArray = mysql_fetch_assoc($result);
		
		if($dbArray['hasDiscount'] > 0)
		{
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Product #$productID has a discount</p>\n";}
			return true;
			
		}
		else
		{
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Product #$productID <strong>does not</strong> have a discount</p>\n";}
			return false;
		}
		
		/*
		$offerStart = strtotime($dbArray['startDate']);
		$offerEnd = strtotime($dbArray['endDate']);
		
		if($offerStart <= $currentDate && $offerEnd >= $currentDate)
		{
			return true;
		}
		else
		{
			return false;
		}
		*/
		
	}
	
	
	function getProductDiscount($priceID)
	{
		$currentTime = date("Y-m-d");
		
		$query = "SELECT 
				discounts.price, max(discounts.created), discounts.dateStart, discounts.dateEnd
				FROM discounts, prices
				WHERE
				discounts.priceID = prices.id AND
				prices.id = $priceID
				GROUP BY discounts.id";
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p><code>$query</code></p>\n";}
		$result = mysql_query($query);
		if(!$result)
		{
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Price select query failed:<br>
														<code>".mysql_error()."</code></p>\n";}
			return false;
		}
		$dbArray = mysql_fetch_assoc($result);
		
		if($currentTime >= $dbArray['dateStart'] && $dbArray['dateEnd'] <= $currentTime)
		{
			return true;
		}
		
		return false;
	}
	

	/**
	 * Return an array of prices for a product using it's ID
	 * 
	 * Returns an array with the key set as the price ID and the value as the price float
	 *  
	 * @param int $productID
	 * @return boolean|multitype:Ambigous <>
	 */
	function getAllProductPrices($productID)
	{
		$q = "SELECT prices.id AS priceID, prices.price AS priceValue, shops.id FROM prices, shops, products WHERE prices.productID = $productID AND products.id = prices.productID
GROUP BY prices.id";
		$result = mysql_query($q);
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>get price query: <code>$q</code></p>\n";}
		if(!$result)
		{
			return false;
		}
		
		$returnArray = array();
		while($dbArray = mysql_fetch_assoc($result))
		{
			/*
			 * Loop to add all the prices to an array
			 */
			$returnArray[$dbArray['priceID']] = $dbArray['priceValue'];
		}
		
		/*
		 * return this array
		 */
		
		return $returnArray;
	}
	
	/**
	 * Return the shop ID value from a price ID as an int
	 * 
	 * @param int $priceID
	 * @return boolean|int
	 */
	function getPriceShop($priceID)
	{
		$q = "SELECT shops.id AS shopID FROM shops, prices WHERE prices.id = $priceID AND prices.shopID = shops.id";
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>get shop ID query: <code>$q</code></p>\n";}
		$result = mysql_query($q);
		if(!$result)
		{
			return false;
		}
		$dbArray = mysql_fetch_assoc($result);
		return $dbArray['shopID'];
	}
	
	
	
	/**
	 * Retrieve all the database rows from the products table that are 'like' the search term
	 * return a html-formatted string of the retrieved products
	 *
	 * @param string $searchTerm
	 * @return string
	 */
	function productSearch($searchTerm)
	{
		$returnString = "";
		$numItemsFound = 0;
		$q = "SELECT ".TBL_PRODUCTS.".id AS productID, 
			".TBL_PRODUCTS.".name, 
			".TBL_PRODUCTS.".brandID AS productBrandID, 
			".TBL_BRANDS.".id AS brandID, 
			".TBL_BRANDS.".name AS brandName 
		FROM ".TBL_PRODUCTS.", ".TBL_BRANDS."
		WHERE 
		(".TBL_PRODUCTS.".name LIKE \"%$searchTerm%\" OR
		".TBL_BRANDS.".name LIKE \"%$searchTerm%\") AND
		".TBL_BRANDS.".id = ".TBL_PRODUCTS.".brandID";
		//"SELECT products.id, products.name AS name FROM products WHERE products.name LIKE \"%$searchTerm%\" LIMIT 0,10";
		
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Searching products @ database.php</p>\n";}
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p><code>$q</code></p>\n";}
		$result = mysql_query($q);
		if(mysql_num_rows($result) == 0)
		{
			return "No results found.";
		}
		while($dbArray = mysql_fetch_assoc($result))
		{
			$numItemsFound++;
			$returnString .= "<div class=\"search-result\">\n
					<div class=\"search-item-name\"><span class=\"brand\">".strtolower($dbArray['brandName'])." </span>".strtolower($dbArray['name'])."</div>
					<a class=\"icon-plus add-item\" id=\"".$dbArray['productID']."\" title=\"".strtolower($dbArray['name'])."\">Add Item</a>
					</div>";
		}
		return $returnString;
	}
	
	/**
	 * Returns search results in the form of an array, as to be able to use templates
	 * @param string $searchTerm
	 * @return boolean|multitype:
	 */
	function productSearchArr($searchTerm)
	{
		$returnString = "";
		$numItemsFound = 0;
		$q = "SELECT 
			".TBL_PRODUCTS.".id AS productID,
			".TBL_PRODUCTS.".name,
			".TBL_PRODUCTS.".brandID AS productBrandID,
			".TBL_PRODUCTS.".barcode,
			".TBL_BRANDS.".id AS brandID,
			".TBL_BRANDS.".name AS brandName
		FROM ".TBL_PRODUCTS.", ".TBL_BRANDS."
		WHERE
		(".TBL_PRODUCTS.".name LIKE \"%$searchTerm%\" OR
			".TBL_BRANDS.".name LIKE \"%$searchTerm%\") AND
			".TBL_BRANDS.".id = ".TBL_PRODUCTS.".brandID";
		//"SELECT products.id, products.name AS name FROM products WHERE products.name LIKE \"%$searchTerm%\" LIMIT 0,10";
	
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Searching products @ database.php</p>\n";}
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p><code>$q</code></p>\n";}
		$result = mysql_query($q);
		if(!$result){
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Search quuery failed: <code>".mysql_error()."</code></p>\n";}
			return false;
		}
		
		$retArray = array();
		while($dbArray = mysql_fetch_assoc($result))
		{
			$retArray[$dbArray['productID']] = array(
													"id" => $dbArray['productID'],
													"name" => $dbArray['name'],
													"brand" => $dbArray['brandName'],
													"barcode" => $dbArray['barcode']);
		}
		return $retArray;
	}
	
	
	
	function categoryBrowse($catID)
	{
		$q = "SELECT products.id AS productID, products.name AS productName, brands.name AS brandName FROM categories, products, brands
			  WHERE categories.id = products.categoryID AND 
				products.categoryID = $catID AND
				brands.id = products.brandID";
				
		$result = mysql_query($q);

		$catItemList = "";
		while($dbArray = mysql_fetch_assoc($result))
		{
			$catItemList .= "<div class=\"search-result\"><span class=\"product-name\">".$dbArray['brandName']." ".$dbArray['productName']."</span></div>";
		}
		return $catItemList;
	}
	
	/**
	 * Function to query the database and retrieve a selection of brandnames similar to the query.
	 * Acts to provide something to fill the brand-name input with, which will later be queried separately to get the ID
	 * which can be put in the database.
	 * 
	 * @param string $query
	 * @return string
	 */
	function getBrandName($query)
	{
		$returnString="";
		$q = "SELECT brands.id, brands.name AS brandName FROM brands WHERE brands.name LIKE \"%$query%\"";
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Getting brand name @ database.php</p>\n";}
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p><code>$q</code></p>\n";}
		$result = mysql_query($q);
		if(!$result || mysql_num_rows($result) == 0)
		{
			return "brand not found";
		}
		$returnString .= "<div class=\"brand-autocomplete\">\n";
		while($dbArray = mysql_fetch_assoc($result))
		{
			$returnString .= "<div class=\"search-result\">
							<div class=\"search-item-name\">
								".strtolower($dbArray['brandName'])."\n</div>
							<a href=\"#\" class=\"icon-plus add-item\" title=\"".strtolower($dbArray['brandName'])."\">\n
							</a>\n
							</div>";
							
		}
		$returnString .= "</div>\n";
		return $returnString;
	}
	
	/**
	 * Query the database to find the id index that matches the inputted brand name
	 * and return the ID as an int
	 * 
	 * @param string $name
	 * @return boolean|int
	 */
	function getBrandID($name)
	{
		$id = -1;
		$q = "SELECT brands.id AS brandID FROM brands WHERE brands.name LIKE \"$name\"";
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Getting brand ID @ database.php</p>\n";}
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p><code>$q</code></p>\n";}
		$result = mysql_query($q);
		if(!$result || mysql_num_rows($result) == 0)
		{
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Brand ID not found for $name @ database.php</p>\n";}
			return false;
			
		}
		
		$dbArray = mysql_fetch_assoc($result);
		$id = $dbArray['brandID'];
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Found $id @ database.php</p>\n";}
		return $id;
	}


	
	/**
	 * ***************************************************************************************||
	 * Add/Remove list-item functions
	 * ***************************************************************************************||
	 */

	/**
	 * Add a product to the shoppinglistproducts table using retrieved product id value
	 * @param int $productId
	 * @param int $listId
	 * @return boolean
	 */
	function addProductToList($productId, $listId)
	{
		$quantity = 1;
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>checking list for duplicate item @ database.php</p>\n";}
		$q_checkForExistingItem = "SELECT shoppinglistproducts.id AS listItemID, 
								shoppinglistproducts.productID AS listProductID,
								shoppinglistproducts.quantity AS quantity
								FROM shoppinglistproducts, shoppinglists
								WHERE shoppinglistproducts.productID = $productId AND 
								shoppinglistproducts.shoppingListID = $listId AND
								shoppinglists.id = shoppinglistproducts.shoppingListID";
		
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p><code>$q_checkForExistingItem</code></p>\n";}
		$result_checkForExistingItem = mysql_query($q_checkForExistingItem);
		
		/*
		 * IF item is already on the list
		 * 	Update the quantity by 1 
		 */
		if($result_checkForExistingItem && mysql_num_rows($result_checkForExistingItem) > 0)
		{
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Found duplicate item in list @ database.php</p>\n";}
			while($dbArray_checkForExistingItem = mysql_fetch_assoc($result_checkForExistingItem))
			{
				$newQuantity = $dbArray_checkForExistingItem['quantity'] + $quantity;
				$q_updateQuantity = "UPDATE shoppinglistproducts SET quantity = $newQuantity WHERE shoppinglistproducts.id = ".$dbArray_checkForExistingItem['listItemID'];
				if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p><code>$q_updateQuantity</code></p>\n";}
				$result = mysql_query($q_updateQuantity);
				if(!$result)
				{
					if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>failed to update quantity for a list item @ database.php</p>\n";}
					return false;
				}
			}
		}
		else 
		{
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>No list entry found, adding new one</p>\n";}
			$q = "INSERT INTO ".TBL_SHOPPING_LIST_PRODUCTS."(id, shoppinglistID, productID, quantity) VALUES (null, $listId, $productId, $quantity)";
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p><code>$q</code></p>\n";}
			$result = mysql_query($q);
			if(!$result)
			{
				return false;
			}
		}
		return true;
	}

	/**
	 * Remove a specified item from the shoppinglistproducts table
	 * @param int $listItemId
	 * @param int $listId
	 * @return boolean
	 */
	function removeProductFromList($listItemId)
	{
		$q = "DELETE FROM shoppinglistproducts WHERE shoppinglistproducts.id = $listItemId";
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p><code>$q</code></p>\n";}
		$result = mysql_query($q);
		if(!$result)
		{
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Remove query failed @ dabase.php</p>\n";}
			return false;
		}
		return true;
	}



	/**
	 * Add and update products and prices functions
	 */

	/**
	 * Insert a new product to the database from a form,
	 * optionally this includes adding price info for a particular store
	 * as well as adding notes or comments about the product
	 *
	 * @param int $userId
	 * @param string $productName
	 * @param int $categoryId
	 * @param int $brandId
	 * @param int $volumeId
	 * @return boolean
	 */
	function insertProduct($userId, $productName, $categoryId, $brandId, $volumeId, $barcode)
	{
		$q_productInfo = "INSERT INTO products
		(id, created, name, barcode, volumeID, categoryID, brandID, userID)
		VALUES (NULL, CURRENT_TIMESTAMP, \"$productName\", $barcode, $volumeId, $categoryId, $brandId, $userId)";
		
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p><code>$q_productInfo</code></p>\n";}
		
		$result_productInfo = mysql_query($q_productInfo);
		
		//Return false if INSERT INTO query fails to add new product
		if(!$result_productInfo)
		{
			
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Product insert query failed:<br><code>".mysql_error()."</code></p>\n";}
			return false;
		}
		
		return true;
	}
	
	/**
	 * Insert or update a price for a product at a particular shop.
	 * Returns true if query was success, else false.
	 * 
	 * @param int $productID
	 * @param float $price
	 * @param int $shopId
	 * @param time $specialStart
	 * @param time $specialEnd
	 * 
	 * @return string
	 */
	
	function insertProductPrice($produdctId, $price, $shopId, $userId, $specialStart = "NULL", $specialEnd = "NULL")
	{
		
		//INSERT QUERY 
		$q_priceInfo = "INSERT INTO prices
			(id, created, productID, shopID, userID, price, startDate, endDate)
			VALUES (null, CURRENT_TIMESTAMP, $produdctId, $shopId, $userId, $price, $specialStart, $specialEnd)";
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p><code>$q_priceInfo</code></p>\n";}
		$result_priceInfo = mysql_query($q_priceInfo);
		//Return false if INSERT INTO query fails
		if(!$result_priceInfo)
		{
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Price info <strong>not</strong> inserted @ database.php</p>\n";}
			return false;
		}
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Price info inserted into database at @ database.php</p>\n";}
	
		
		return true;
	}
	
	
	function updateProductInfo($productInfo)
	{
		$query_products = "UPDATE products 
					SET products.name = \"" . $productInfo['productName'] . "\", 
						products.barcode = \"" . $productInfo['barcode'] . "\",
						products.brandID = " . $productInfo['brandID'] . ",
						products.categoryID = " . $productInfo['categoryID'] . ",
						products.description = \"" . $productInfo['description'] . "\"
						WHERE products.id = " . $productInfo['productID'];
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p><code>$query_products</code></p>\n";}
		$result_products = mysql_query($query_products);
		if(!$result_products)
		{
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Profuct table failed to update @ database.php<br>
															<code>".mysql_error()."</code></p>\n";}
			return false;
		}
		return true;
	}
	
	
	
	/**
	 * Get a string of a shop from a keyword search and return the formatted string
	 * @param string $query
	 * @return string
	 */
	function generateShopString($query)
	{
			
		$shopString = "";
		
		$q = "
				SELECT
					".TBL_SHOPS.".id AS shopID,
					".TBL_SHOPS.".name AS shopLocation,
					".TBL_SHOPS.".city,
					".TBL_SHOPS.".address,
					".TBL_CHAINS.".name AS chain
				FROM
					".TBL_SHOPS.", ".TBL_CHAINS."
				WHERE
					(".TBL_SHOPS.".name LIKE \"%$query%\" OR
					".TBL_SHOPS.".address LIKE \"%$query%\" OR
					".TBL_SHOPS.".city LIKE \"%$query%\" OR
					".TBL_CHAINS.".name LIKE \"%$query%\") AND
					".TBL_CHAINS.".id = ".TBL_SHOPS.".chainID";
		
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p><code>$q</code></p>\n";}
		
		$result = mysql_query($q);
		//return $q;
		if(!$result || mysql_num_rows($result) == 0)
		{
			return "no results found.";
		}
		
		while($dbArray = mysql_fetch_assoc($result))
		{
			$shopString .= "<div class=\"search-result\">
								<div class=\"search-item-name\">".$dbArray['chain'].", ".$dbArray['shopLocation']."</div>
								<a href=\"#\" id=\"".$dbArray['shopID']."\" class=\"icon-plus add-item\" title=\"".$dbArray['chain'].", ".$dbArray['shopLocation']."\">".
									$dbArray['chain'].", ".$dbArray['shopLocation'].
								"</a>
							</div>";
			
		}
		
		return $shopString;
	}
	
	/**
	 * Function to insert a new store into the database
	 * 
	 * @param int $userID
	 * @param string $city
	 * @param string $location
	 * @param int $chainID
	 * @param string $address
	 * @param string $country
	 * @param float $lat
	 * @param float $long
	 * @return boolean
	 */
	function addNewShop($userID, $city, $location, $chainID, $address = "", $country = "finland", $lat = 0.00, $long = 0.00)
	{
		$q = "INSERT INTO 
				shops 
				(id, created, name, chainID, latitude, longitude, userID, address, city, country)
				VALUES
				(NULL, CURRENT_TIMESTAMP, \"$location\", $chainID, $lat, $long, $userID, \"$address\", \"$city\", \"$country\")";
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p><h3>insert new shop query</h3><code>$q</code></p>\n";}
		$result = mysql_query($q);
		if(!$result)
		{
			return false;
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>insert query failed @ database.php</p>\n";}
		}
		
		return true;
	}
	
	
	/**
	 * return a name of a product as a string from an inputted ID integer value
	 * 
	 * @param int $prodID
	 * @return string|Ambigous <>
	 */
	function getProdNameFromID($prodID)
	{
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Getting product name from ID $prodID @ database.php</p>\n";}
		$q = "SELECT products.name AS productName FROM products WHERE products.id = $prodID";
		$result = mysql_query($q);
		
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p><code>$q</code></p>\n";}
		if(!$result)
		{
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Could not find product name</p>\n";}
			return "product not found";
		}
		$dbArray = mysql_fetch_assoc($result);
		if(empty($dbArray['productName']))
		{
			return "product #$prodID has no name";
		}
		
		return $dbArray['productName'];
		
		 
	}
	
	/**
	 * Insert a new brand row into the brands table in the database
	 * 
	 * Return 1: Brand already exists
	 * Return true: insert success
	 * Return false: insert failed
	 * 
	 * @param string $brandName
	 * @return boolean|int
	 */
	function insertBrand($brandName)
	{
		
		 $q_checkForExistingBrand = "SELECT * FROM ".TBL_BRANDS." WHERE brands.name LIKE \"".mysql_real_escape_string($brandName)."\"";
		 if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p><code>$q_checkForExistingBrand</code></p>\n";}
		 $brandAleadyExists = mysql_query($q_checkForExistingBrand);
		 if(mysql_num_rows($brandAleadyExists) >= 1)
		 {
		 	if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Brand already exists</p>\n";}
		 	return -1;
		 }
		 
		 $q = "INSERT INTO brands (id, name) VALUES (null, \"".mysql_real_escape_string($brandName)."\")";
		 if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p><code>$q</code></p>\n";}
		 $result = mysql_query($q);
		 if(!$result)
		 {
		 	if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Brand insert failed @ database.php</p>\n";}
		 	return false;
		 }
		 if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>brand insert success @ database.php</p>\n";}
		 return true;
	}

	/**
	 * Returns the number of products stored in the database
	 * 
	 * @return boolean|number
	 */
	function getNumProducts()
	{
		$q = "SELECT " . TBL_PRODUCTS . ".id FROM " . TBL_PRODUCTS;
		$result = mysql_query($q);
		if(!$result)
		{
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Query failed: <code>" . mysql_error() . "</code></p>\n";}
			return false;
		} 
		return mysql_num_rows($result);
	}
	
	/**
	 * Returns the number of prices stored in the database
	 *
	 * @return boolean|number
	 */
	function getNumPrices()
	{
		$q = "SELECT " . TBL_PRICES . ".id FROM " . TBL_PRICES;
		$result = mysql_query($q);
		if(!$result)
		{
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Query failed: <code>" . mysql_error() . "</code></p>\n";}
			return false;
		}
		return mysql_num_rows($result);
	}
	
	
	function dumpArray($array)
	{
		ob_start();
		var_dump($array);
		$arrayDump = ob_get_clean();
		return $arrayDump;
	}
	
	
	

	/**
	 * query - Performs the given query on the database and
	 * returns the result, which may be false, true or a
	 * resource identifier.
	 */
	function query($query)
	{
		return mysql_query($query, $this->connection);
	}
};

/* Create database connection */
$database = new MySQLDB;

?>
