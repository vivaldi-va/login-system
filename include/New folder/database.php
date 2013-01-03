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
		mysql_set_charset('latin1',$this->connection);

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
		$_SESSION['debug_info'] .= "<p>Executing user-pass varification in database.php</p>\n";
		$_SESSION['debug_info'] .= "<p><code>".$q."</code></p>\n";
		$result = mysql_query($q, $this->connection);
		if(!$result || (mysql_numrows($result) < 1)){
			return 1; //Indicates username failure
		}
		$_SESSION['debug_info'] .= "<p>User pass confirmed</p>\n";
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
		$_SESSION['debug_info'] .= "<p>Varifying user exists in DB: @ database.php<br><code>".$q."</code></p>\n";
		$result = mysql_query($q, $this->connection);
		if(!$result || (mysql_numrows($result) < 1))
		{
			$_SESSION['debug_info'] .= "<p>User not found @ database.php</p>\n";
			return 1; //Indicates username failure
		}
		$_SESSION['debug_info'] .= "<p>User info found, retrieving values @ database.php</p>\n";
		/* Retrieve userid from result, strip slashes */
		$dbarray = mysql_fetch_array($result);
		$dbarray['userID'] = stripslashes($dbarray['userID']);
		$userid = stripslashes($userid);
		$_SESSION['debug_info'] .= "<p>Comparing remembered user (".$userid.") to userid in db (".$dbarray['userID'].") @ database.php</p>\n";

		/* Validate that userid is correct */
		if($userid == $dbarray['userID'])
		{
			$_SESSION['debug_info'] .= "<p>User confirmed! @ database.php</p>\n";
			return 0; //Success! Username and userid confirmed
		}
		else
		{
			$_SESSION['debug_info'] .= "<p>User not found. @ database.php</p>\n";
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
		$_SESSION['debug_info'] .= "<p>Query to get user info @ database.php:<br><code>$q</code></p>\n";
		$result = mysql_query($q, $this->connection);
		/* Error occurred, return given name by default */
		if(!$result || (mysql_numrows($result) < 1))
		{
			return NULL;
			$_SESSION['debug_info'] .= "<p><strong>No user found<strong></p>\n";
		}

		/* Return result array */
		$dbarray = mysql_fetch_array($result);
		$_SESSION['debug_info'] .= "<p>User info retrieved @ database.php</p>\n";
		return $dbarray;
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
	function setLastActiveTime($userID)
	{
		$q = "UPDATE ".TBL_USERS."
				SET ".TBL_USERS.".last_login_date = CURRENT_TIMESTAMP
				WHERE ".TBL_USERS.".id = $userID";
		$_SESSION['debug_info'] .= "<p><code>$q</code></p>\n";
		$result = mysql_query($q);
		if(!$result)
		{
			$_SESSION['debug_info'] .= "<p>Update last login date failed.</p>\n";
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
		$_SESSION['debug_info'] .= "<p><code>$q</code></p>\n";
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
		$_SESSION['debug_info'] .= "<p><code>$q</code></p>\n";
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
		$_SESSION['debug_info'] .= "<p><code>$q</code></p>\n";
		$result = mysql_query($q);
		if(!$result)
		{
			return "product not found";
		}
		$dbArray = mysql_fetch_assoc($result);
		return $dbArray['name'];
	}
	
	/**
	 * Fetch the entries in the category table and return then as an associative array
	 * 
	 * @return boolean|multitype:
	 */
	function getCategoryList()
	{
		$_SESSION['debug_info'] .= "<p>Attempting to retrieve category list @ database.php</p>\n";
		//Get the parent categories (where parentID = 0)
		$q_getGroups = "SELECT * FROM ".TBL_CATEGORIES." WHERE ".TBL_CATEGORIES.".parentID = 0 ORDER BY ".TBL_CATEGORIES.".id";
		$_SESSION['debug_info'] .= "<p>Retrieve parent categories:</p>\n";
		$_SESSION['debug_info'] .= "<p><code>$q_getGroups</code></p>\n";
		$result_groups = mysql_query($q_getGroups);
		if(!$result_groups)
		{
			$_SESSION['debug_info'] .= "<p>Failed to receive parent categories</p>\n";
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
		
		$_SESSION['debug_info'] .= "<p>Retrieve sub-categories: </p>\n";
		$q_categories = "SELECT * FROM ".TBL_CATEGORIES." WHERE ".TBL_CATEGORIES.".parentID > 0 ORDER BY ".TBL_CATEGORIES.".id";
		$_SESSION['debug_info'] .= "<p><code>$q_categories</code></p>\n";
		$result = mysql_query($q_categories);
		
		/*
		 * Return false if no category rows returned, it would indicate an error in the query (or the database has changed)
		 */
		if(!$result || mysql_num_rows($result) == 0)
		{
			$_SESSION['debug_info'] .= "<p>Can not retrieve category list</p>\n";
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
		
		
		$_SESSION['debug_info'] .= "<p>Add the sub-categories to their respective groups</p>\n";
		$categoryString = "";
		foreach($catGroupArray AS $groupID => $groupName)
		{
			$_SESSION['debug_info'] .= "<p>Add to $groupID: $groupName:</p>\n";
			$categoryString .= "<optgroup label=\"$groupName\">";
			foreach($subCatParentIDArray AS $subCatID => $parentID)
			{
				//$_SESSION['debug_info'] .= "<p>Fetch sub-cats</p>\n";
				if($parentID == $groupID)
				{
					$_SESSION['debug_info'] .= "<p>Add ".$subCatNameArray[$subCatID]." to  $groupName</p>\n";
					$categoryString .= "<option value=\"$subCatID\">";
					$categoryString .= strtolower($subCatNameArray[$subCatID]);
					$categoryString .= "</option>";
				}
			}
			$categoryString .= "</optgroup>";
			
		}
		$_SESSION['debug_info'] .= "<p>$categoryString</p>\n";
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
			$_SESSION['debug_info'] .= "<p>Fault in inserting new list @ database.php:<br><code>$q</code></p>\n";
			return false;
		}
		$_SESSION['debug_info'] .= "<p>New list created for user #$userID</p>\n";
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
		$_SESSION['debug_info'] .= "<p>Sending query to return shopping list ID:<br><code>$q</code></p>\n";
		$result = mysql_query($q);
		if(!$result || mysql_num_rows($result) == 0)
		{
			$_SESSION['debug_info'] .= "<p>Returned rows are 0, or query failed. Shopping list not found for $email @ database.php</p>\n";
			return false;
		}
		$dbArray = mysql_fetch_assoc($result);
		$_SESSION['debug_info'] .= "<p>Found list #".$dbArray['listID']." for user #".$dbArray['userID']." @ database.php</p>\n";
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
			'.TBL_PRODUCTS.'.id AS productID,
			'.TBL_PRODUCTS.'.name AS ProductName
			FROM
			'.TBL_USERS.',
			'.TBL_SHOPPING_LISTS.',
			'.TBL_SHOPPING_LIST_PRODUCTS.',
			'.TBL_PRODUCTS.'
			WHERE
			'.$userID.' = '.TBL_SHOPPING_LISTS.'.userID AND
			'.$userID.' = '.TBL_USERS.'.id AND
			'.$listID.' = '.TBL_SHOPPING_LIST_PRODUCTS.'.shoppinglistID AND
			'.TBL_PRODUCTS.'.id = '.TBL_SHOPPING_LIST_PRODUCTS.'.productID';
		
		$_SESSION['debug_info'] .= "<p>Querying database to retrieve list items @ database.php<br><code>$q</code></p>\n";
		$result = mysql_query($q);
		if(!$result || mysql_num_rows($result) == 0)
		{
			$_SESSION['debug_info'] .= "<p> Shopping list not found for list #$listID. Returned rows are 0, or query failed. @ database.php</p>\n";
			return "No list items found.";
		}
		
		while($dbArray = mysql_fetch_assoc($result))
		{
			$productNameLower = strtolower($dbArray['ProductName']);
			$listString .= '<article class="list-item">' .
								'<header>' .
									'<h2>' . $productNameLower . '</h2>
									<div class="btn-group">
									<a href id="'.$dbArray['listItemID'].'" class="btn btn-small btn-danger list-remove" onClick="removeClicked(this)">remove from list</a>' .
									'<a './*href="priceUpdate.php?prodEditID='.$dbArray['productID'].'" */'id="'.$dbArray['productID'].'" class="btn btn-small btn-info product-edit" onClick="editClicked(this)">edit info</a>
									</div>' .
								'</header>' .
							'</article>';
		}

		return $listString;
	}


	/**
	 * Bring back a sorted version of the shopping list, based on the inputted location.
	 * Then format them based on which shop they are to be found
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
		$_SESSION['debug_info'] .= "<p><code>$q_location</code></p>\n";
		if(!$result || mysql_num_rows($result) == 0)
		{
			$_SESSION['debug_info'] .= "<p>No location found at $location</p>\n";
			//Something
		}
		
		$_SESSION['debug_info'] .= "<p>Found location, fetching an array of the locations @ database.php</p>\n";
		
		/*
		 * Add each of the found shops under an array, with the key
		 * being their shopID, and the value being the shop name
		 * 
		 * Shop ID => chain name
		 */
		while($locationDbArray = mysql_fetch_assoc($result))
		{
			$definedStoreIdArray[$locationDbArray['shopID']] = $locationDbArray['chainName'];
			$_SESSION['debug_info'] .= "<p>Added shop ".$locationDbArray['shopID']."</p>\n";
			
			$shopIDArray[] = $locationDbArray['shopID'];
		}
		
		$q_chains_part = "(".TBL_SHOPS.".id = ". implode(" OR ".TBL_SHOPS.".id = ", $shopIDArray) . ")";
		
		$_SESSION['debug_info'] .= "<p><code>$q_chains_part</code></p>\n";
		$_SESSION['debug_info'] .= "<p>Collecting the products in the database, and their cheapest prices from the found shops @ database.php</p>\n";
		
		
		$q_list = 'SELECT
				'.TBL_SHOPPING_LISTS.'.id,
				'.TBL_SHOPPING_LISTS.'.userID,
				'.TBL_SHOPPING_LIST_PRODUCTS.'.id,
				'.TBL_SHOPPING_LIST_PRODUCTS.'.shoppingListID,
				'.TBL_SHOPPING_LIST_PRODUCTS.'.ProductID,
				'.TBL_PRODUCTS.'.id,
				'.TBL_PRODUCTS.'.name AS productName,
				'.TBL_PRICES.'.price AS productPrice,
				'.TBL_PRICES.'.productID,
				'.TBL_PRICES.'.shopID,
				'.TBL_SHOPS.'.id,
				'.TBL_SHOPS.'.name AS shopLocation,
				'.TBL_SHOPS.'.chainID,
				'.TBL_CHAINS.'.id,
				'.TBL_CHAINS.'.name AS chainName
				FROM
				'.TBL_SHOPPING_LISTS.', '.TBL_SHOPPING_LIST_PRODUCTS.', '.TBL_PRODUCTS.', '.TBL_PRICES.', '.TBL_SHOPS.', '.TBL_CHAINS.'
				WHERE
				'.TBL_SHOPPING_LISTS.'.userID = '.$userID.' AND
				'.TBL_SHOPPING_LIST_PRODUCTS.'.shoppingListID = '.$listID.' AND
				'.$q_chains_part.' AND
				'.TBL_SHOPPING_LIST_PRODUCTS.'.ProductID = '.TBL_PRODUCTS.'.id AND
				'.TBL_PRICES.'.productID = '.TBL_PRODUCTS.'.id AND
				'.TBL_SHOPS.'.id = '.TBL_PRICES.'.shopID AND
				'.TBL_CHAINS.'.id = shops.chainID
				GROUP BY '.TBL_PRODUCTS.'.id
				ORDER BY '.TBL_SHOPS.'.id';
		$_SESSION['debug_info'] .= "<p><code>$userID, $listID</code></p>\n";
		$_SESSION['debug_info'] .= "<p>Running query to return sorted shopping lists products @ database.php</p>\n";
		$_SESSION['debug_info'] .= "<p><code>$q_list</code></p>\n";
		$result = mysql_query($q_list);
		if(!$result || mysql_num_rows($result) == 0)
		{
			$_SESSION['debug_info'] .= "<p>No products found for list #$listID. Returned rows are 0, or query failed.<br>
			There are either no prices in the database for the list items, or errors in sql.</p>\n";
			return false;
		}
		//$dbArray = mysql_fetch_assoc($result);
		$_SESSION['debug_info'] .= "<p>Products found, continuing with sort</p>\n";


		/*
		 * Loop through the found products and add them to respective arrays
		*
		*/
		$sortedListArray = array();
		while($pricesDbArray = mysql_fetch_assoc($result))
		{
			/*
			 * For each shop in the shop array, check if the product with the lowest price is in
			* the chain
			*
			* If so, add it to an array of sorted products, in a sub array for it's respective chain
			*
			* SORTEDLIST i =>
			* 				ARRAY key(shopID) => list string
			*/
			$_SESSION['debug_info'] .= "<p>Sorted products loop</p>\n";
			foreach($definedStoreIdArray AS $key => $value)
			{
				/*
					* IF the key from the store array matches the shopID from the sorted SQL array
				* 		add it to a new array inside the list array
				*/
				$_SESSION['debug_info'] .= "<p>defined store foreach</p>\n";
				$_SESSION['debug_info'] .= "<p>key = $key</p>\n";
				$_SESSION['debug_info'] .= "<p>shopID = ".$pricesDbArray['shopID']."</p>\n";

				if(!isset($listItemString))
				{
					$listItemString = "";
				}
				if($key == $pricesDbArray['shopID'])
				{
					$listItemString .= '<div class="sorted-product '.strtolower($pricesDbArray['shopLocation'].'-'.$pricesDbArray['chainName']).'">
							<div class="price-info">'.strtolower($pricesDbArray['productName']).' &ndash; <strong>&euro;'.$pricesDbArray['productPrice'].'</strong></div>
									</div>';
					/*
					 * Array stores list for EACH shop per key, the list is made up of a concatinated string of HTML
					*/
					$sortedListArray[$key] = $listItemString;
					$_SESSION['debug_info'] .= "<p>$listItemString</p>\n";
				}
			}
		}
		
		
		
		$_SESSION['debug_info'] .= "<p>Product Info Array Contents:</p>\n";
		
		
		
			
		
		/*
		 * declare an empty string for each shop id, to enable 
		 * the formatted string to be concatinated into it.
		 */
		
		
		
		/*
		 * loop through the product array and calculate the lowest price for each product.
		 */
		
		
		
		//var_dump($sortedListArray);
		foreach($sortedListArray AS $key => $value)
		{
			$_SESSION['debug_info'] .= "<h3>Shop #$key</h3><p>$value</p>\n";
		}
		return $sortedListArray;
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
		$q = "SELECT
		".TBL_PRODUCTS.".id,
		".TBL_PRODUCTS.".name,
		".TBL_PRODUCTS.".brandID
		FROM
		".TBL_PRODUCTS.", ".TBL_BRANDS."
		WHERE
		".TBL_PRODUCTS.".name LIKE \"%$searchTerm%\" OR
		(".TBL_BRANDS.".name LIKE \"%$searchTerm%\" AND
		".TBL_BRANDS.".id = ".TBL_PRODUCTS.".id)
		GROUP BY
		".TBL_PRODUCTS.".id";
		//"SELECT products.id, products.name AS name FROM products WHERE products.name LIKE \"%$searchTerm%\" LIMIT 0,10";
		
		$_SESSION['debug_info'] .= "<p>Searching products @ database.php</p>\n";
		$_SESSION['debug_info'] .= "<p><code>$q</code></p>\n";
		$result = mysql_query($q);
		if(mysql_num_rows($result) == 0)
		{
			return "No results found.";
		}
		while($dbArray = mysql_fetch_assoc($result))
		{
			$numItemsFound++;
			$returnString .= "<div class=\"search-result\">\n
					<div class=\"search-item-name\">".strtolower($dbArray['name'])."</div>
					<a class=\"icon-plus add-item\" id=\"".$dbArray['id']."\" title=\"".strtolower($dbArray['name'])."\">Add Item</a>
					</div>";
		}
		return $returnString;
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
		$_SESSION['debug_info'] .= "<p>Getting brand name @ database.php</p>\n";
		$_SESSION['debug_info'] .= "<p><code>$q</code></p>\n";
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
		$q = "SELECT brands.id AS brandID FROM brands WHERE brands.name = \"$name\"";
		$_SESSION['debug_info'] .= "<p>Getting brand ID @ database.php</p>\n";
		$_SESSION['debug_info'] .= "<p><code>$q</code></p>\n";
		$result = mysql_query($q);
		if(!$result || mysql_num_rows($result) == 0)
		{
			$_SESSION['debug_info'] .= "<p>Brand ID not found for $name @ database.php</p>\n";
			return false;
			
		}
		
		$dbArray = mysql_fetch_assoc($result);
		$id = $dbArray['brandID'];
			
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
		$q = "INSERT INTO ".TBL_SHOPPING_LIST_PRODUCTS."(id, shoppinglistID, productID) VALUES (null, $listId, $productId)";
		$_SESSION['debug_info'] .= "<p><code>$q</code></p>\n";
		$result = mysql_query($q);
		if(!$result)
		{
			return false;
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
		$_SESSION['debug_info'] .= "<p><code>$q</code></p>\n";
		$result = mysql_query($q);
		if(!result)
		{
			$_SESSION['debug_info'] .= "<p>Remove query failed @ dabase.php</p>\n";
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
	function insertProduct($userId, $productName, $categoryId, $brandId, $volumeId, $barcode = -1)
	{
		$q_productInfo = "INSERT INTO products
		(id, created, name, barcode, volumeID, categoryID, brandID, userID)
		VALUES (NULL, CURRENT_TIMESTAMP, \"$productName\", $barcode, $volumeId, $categoryId, $brandId, $userId)";
		
		$_SESSION['debug_info'] .= "<p><code>$q_productInfo</code></p>\n";
		
		$result_productInfo = mysql_query($q_productInfo);
		
		//Return false if INSERT INTO query fails to add new product
		if(!$result_productInfo)
		{
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
		$_SESSION['debug_info'] .= "<p><code>$q_priceInfo</code></p>\n";
		$result_priceInfo = mysql_query($q_priceInfo);
		//Return false if INSERT INTO query fails
		if(!$result_priceInfo)
		{
			$_SESSION['debug_info'] .= "<p>Price info <strong>not</strong> inserted @ database.php</p>\n";
			return false;
		}
		$_SESSION['debug_info'] .= "<p>Price info inserted into database at @ database.php</p>\n";
	
		
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
		
		$_SESSION['debug_info'] .= "<p><code>$q</code></p>\n";
		
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
		$_SESSION['debug_info'] .= "<p><h3>insert new shop query</h3><code>$q</code></p>\n";
		$result = mysql_query($q);
		if(!$result)
		{
			return false;
			$_SESSION['debug_info'] .= "<p>insert query failed @ database.php</p>\n";
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
		$_SESSION['debug_info'] .= "<p>Getting product name from ID $prodID @ database.php</p>\n";
		$q = "SELECT products.name AS productName FROM products WHERE products.id = $prodID";
		$result = mysql_query($q);
		
		$_SESSION['debug_info'] .= "<p><code>$q</code></p>\n";
		if(!$result)
		{
			$_SESSION['debug_info'] .= "<p>Could not find product name</p>\n";
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
