<?php
/**
 * Session.php
 *
 * The Session class is meant to simplify the task of keeping
 * track of logged in users and also guests.
 *
 * Written by: Jpmaster77 a.k.a. The Grandmaster of C++ (GMC)
 * Last Updated: August 19, 2004
 */
include_once("database.php");
include_once("mailer.php");
include_once("form.php");
include_once("constants.php");

/**
 * 
 * @author vivaldi
 * 
 */
class Session
{
	var $email;     //Email given on sign-up that is used to login with
	var $userName; //User's first name to use to display identity
	var $userid;       //Random value generated on current login
	var $userlevel;    //The level to which the user pertains
						// 0: basic user, 1: elevated user, 2: admin
	 
	var $time;         //Time user was last active (page loaded)
	var $logged_in;    //True if user is logged in, false otherwise
	var $userinfo = array();  //The array holding all user info
	var $userListID; // The id of the list that is associated with the user
	var $userListName; // The name or title of the user's list
	var $rootUrl; //the root page of the site
	var $url;          //The page url current being viewed
	var $referrer;     //Last recorded site page viewed
	/**
	 * Note: referrer should really only be considered the actual
	 * page referrer in process.php, any other time it may be
	 * inaccurate.
	 */
	
	var $searchResults;
	
	
	

	/* Class constructor */
	function Session(){
		$this->time = time();
		$this->startSession();
		//unSet($_SESSION['debug_info']);
	}

	/**
	 * startSession - Performs all the actions necessary to
	 * initialize this session object. Tries to determine if the
	 * the user has logged in already, and sets the variables
	 * accordingly. Also takes advantage of this page load to
	 * update the active visitors tables.
	 */
	function startSession()
	{
		global $database;  //The database connection
		session_start();   //Tell PHP to start the session
		//mysql_set_charset("utf8");
		//mysql_set_charset("ISO_8859-1");
		//$_SESSION['debug_info'] = ""; // reset debug string on a new session to generate fresh info
		/**
		 * Session variable to display debug info from all parts of the system
		 * Declared here to be able to append as a concatinated string.
		 *
		 */
		if(!isset($_SESSION['debug_info']))
		{
			$_SESSION['debug_info'] = "";
		}
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<h2>" . date("D M j G:i:s ") . "</h2>"."<h3>Session started</h3>\n";}

		/* Determine if user is logged in */
		$this->logged_in = $this->checkLogin();

		/**
		 * Set guest value to users not logged in, and update
		 * active guests table accordingly.
		*/
		if(!$this->logged_in)
		{
			$this->email = $_SESSION['email'] = GUEST_NAME;
			$this->userlevel = GUEST_LEVEL;
			$database->addActiveGuest($_SERVER['REMOTE_ADDR'], $this->time);
			
			/*
			 * Create token for login-form
			 */
			
			//Use this session var in a hidden form input to validate user logged in using our site.
			$_SESSION['form-token'] = $this->generateRandID();
			
			
		}
		/* Update users last active timestamp */
		else
		{
			$database->addActiveUser($this->email, $this->time);
		}

		/* Remove inactive visitors from database */
		$database->removeInactiveUsers();
		$database->removeInactiveGuests();

		
		/* Set referrer page */
		if(isset($_SESSION['url']))
		{
			$this->referrer = $_SESSION['url'];
			//if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Redirect url set to ".$_SERVER['REQUEST_URI']."/demo.php @ session.php</p>\n";}
			//$this->referrer = strpos($_SERVER['REQUEST_URI'], "/main.php");

		}
		else
		{
			$this->referrer = strpos($_SERVER['REQUEST_URI'],"/index.php");
		}

		/* Set current url */
		$this->url = $_SESSION['url'] = $_SERVER['PHP_SELF'];
		$this->rootUrl = strpos($_SERVER['REQUEST_URI'],"/");
	}

	/**
	 * checkLogin - Checks if the user has already previously
	 * logged in, and a session with the user has already been
	 * established. Also checks to see if user has been remembered.
	 * If so, the database is queried to make sure of the user's
	 * authenticity. Returns true if the user has logged in.
	 */
	function checkLogin()
	{
		global $database;  //The database connection
		/* Check if user has been remembered */
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Checking if user logged in @ session.php</p>\n";}
		if(isset($_COOKIE['cookname']) && isset($_COOKIE['cookid']))
		{
			$this->email = $_SESSION['email'] = $_COOKIE['cookname'];
			$this->userid = $_SESSION['userid'] = $_COOKIE['cookid'];
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Email and userid cookies are set @ session.php</p>\n";}
		}

		/* email and userid have been set and not guest */
		if(isset($_SESSION['email']) && isset($_SESSION['userid']) &&
				$_SESSION['email'] != GUEST_NAME)
		{
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Excuting confirmUserID @ session.php</p>\n";}
			/* Confirm that email and userid are valid */
			if($database->confirmUserID($_SESSION['email'], $_SESSION['userid']) != 0)
			{
				/* Variables are incorrect, user not logged in */
				unset($_SESSION['email']);
				unset($_SESSION['userid']);
				if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>UserID not confirmed, not logged in or errors. @ session.php</p>\n";}
				return false;
			}

			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>UserID confirmed @ session.php</p>\n";}

			/* User is logged in, set class variables */
			$this->userinfo  = $database->getUserInfo($_SESSION['email']);

			/**
			 * If getUserInfo() returns null, this indicates an error with the SQL query, so
			 * display an error accordingly
			*/
			/*if($this->userinfo == null)
				echo "<p>Error with database query when getting user info.</p>\n";*/
			$this->email  = $this->userinfo['email'];
			$this->userName = $this->userinfo['name'];
			$this->userid = $this->userinfo['id'];
			$this->userlevel = $this->userinfo['userLevel'];
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Email: ".$this->email."<br>User ID: ".$this->userid."<br>User Level: ".$this->userlevel."</p>\n";}
			/*foreach($this->userinfo AS $key=>$value)
			{
				if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>$key set to $value</p>\n";}
			}*/
			//$this->userlevel = $this->userinfo['userlevel'];
			
			$database->setLastActive($this->userid);

			//Get user's shopping list
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Retrieving shopping list for email ".$this->email." @ session.php</p>\n";}
			$this->userListID = $database->getListID($this->email);
			if(!$this->userListID)
			{
				if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Shopping list could not be found for user #".$this->userid." @ session.php</p>\n";}
			}

			return true;
		}
		/* User not logged in */
		else
		{
			return false;
		}
	}

	/**
	 * login - The user has submitted his email and password
	 * through the login form, this function checks the authenticity
	 * of that information in the database and creates the session.
	 * Effectively logging in the user if all goes well.
	 *
	 * @method
	 * @param subemail - string
	 * @param subpass - string
	 * @param subremember - boolean (default false)
	 *
	 */
	function login($subemail, $subpass, $subremember=false)
	{
		global $database, $form;  //The database and form object

		/* email error checking */
		$field = "email";  //Use field name for email
		if(!$subemail || strlen($subemail = trim($subemail)) == 0)
		{
			$form->setError($field, "* email not entered");
		}
		else
		{
			/* Check if email is not alphanumeric */
			if(!preg_match("/^([0-9a-z@.-_])*$/", $subemail, $matches))
			{
				$form->setError($field, "* email not alphanumeric");
				if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Email didnt match preg() pattern @ session.php</p>\n";}
			}
		}

		/* Password error checking */
		$field = "pass";  //Use field name for password
		if(!$subpass)
		{
			$form->setError($field, "* Password not entered");
		}

		/* Return if form errors exist */
		if($form->num_errors > 0)
		{
			return false;
		}

		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>No validation errors in login() at session.php</p>\n";}

		/* Checks that email is in database and password is correct */
		$subemail = stripslashes($subemail);
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Calling confirmUserPass(".$subemail.", *****)</p>\n";}
		$result = $database->confirmUserPass($subemail, $subpass);


		/* Check error codes */
		if($result == 1){
			$field = "email";
			$form->setError($field, "* email not found");
		}
		else if($result == 2){
			$field = "pass";
			$form->setError($field, "* Invalid password");
		}

		/* Return if form errors exist */
		if($form->num_errors > 0){
			return false;
		}

		/* email and password correct, register session variables */
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Calling getUserInfo(".$subemail.")</p>\n";}
		$this->userinfo = $database->getUserInfo($subemail);
		$this->email = $_SESSION['email'] = $this->userinfo['email'];
		$this->userid = $_SESSION['userid'] = $this->userinfo['id'];//generateRandID();
		$this->userlevel = $this->userinfo['userLevel'];
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Email: ".$this->email."<br>User ID: ".$this->userid."<br>User Level: ".$this->userlevel."</p>\n";}
		$database->setLastActive($this->userid);

		/* Insert userid into database and update active users table */
		$database->updateUserField($this->email, "userid", $this->userid);
		$database->addActiveUser($this->email, $this->time);
		$database->removeActiveGuest($_SERVER['REMOTE_ADDR']);

		/**
		 * This is the cool part: the user has requested that we remember that
		 * he's logged in, so we set two cookies. One to hold his email,
		 * and one to hold his random value userid. It expires by the time
		 * specified in constants.php. Now, next time he comes to our site, we will
		 * log him in automatically, but only if he didn't log out before he left.
		*/
		if($subremember)
		{
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Userinfo remembered. Cookies created @ session.php</p>\n";}
			setcookie("cookname", $this->email, time()+COOKIE_EXPIRE, COOKIE_PATH);
			setcookie("cookid",   $this->userid,   time()+COOKIE_EXPIRE, COOKIE_PATH);

		}

		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Retrieving user's shopping list @ session.php</p>\n";}
		$listID = $database->getListID($this->email);
		if(!$listID)
		{
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>User list does not exist or errors in query, attempting to create a new one @ session.php</p>\n";}
			$newList = $database->createList($this->userid);
			if(!$newList)
			{
				if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Attempt to create new list <strong>failed</strong> @ session.php</p>\n";}
				return false;
			}
		}

		/* Login completed successfully */
		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Login function completed successfully @ session.php</p>\n";}
		return true;
	}

	/**
	 * logout - Gets called when the user wants to be logged out of the
	 * website. It deletes any cookies that were stored on the users
	 * computer as a result of him wanting to be remembered, and also
	 * unsets session variables and demotes his user level to guest.
	 */
	function logout()
	{
		global $database;  //The database connection
		/**
		 * Delete cookies - the time must be in the past,
		 * so just negate what you added when creating the
		 * cookie.
		 */
		if(isset($_COOKIE['cookname']) && isset($_COOKIE['cookid'])){
			setcookie("cookname", "", time()-COOKIE_EXPIRE, COOKIE_PATH);
			setcookie("cookid",   "", time()-COOKIE_EXPIRE, COOKIE_PATH);
		}

		/* Unset PHP session variables */
		unset($_SESSION['email']);
		unset($_SESSION['userid']);

		/* Reflect fact that user has logged out */
		$this->logged_in = false;

		/**
		 * Remove from active users table and add to
		 * active guests tables.
		 */
		$database->removeActiveUser($this->email);
		$database->addActiveGuest($_SERVER['REMOTE_ADDR'], $this->time);

		/* Set user level to guest */
		$this->email  = GUEST_NAME;
		$this->userlevel = GUEST_LEVEL;
	}

	/**
	 * register - Gets called when the user has just submitted the
	 * registration form. Determines if there were any errors with
	 * the entry fields, if so, it records the errors and returns
	 * 1. If no errors were found, it registers the new user and
	 * returns 0. Returns 2 if registration failed.
	 */
	function register($subemail, $subpass1, $subpass2, $subname){
		global $database, $form, $mailer;  //The database, form and mailer object

		if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Register function called @ session.php</p>\n";}
		
		/* email error checking */
		$field = "email";  //Use field name for email
		if(!$subemail || strlen($subemail = trim($subemail)) == 0){
			$form->setError($field, "* Email not entered");
		}
		else{
			//If no errors:

			/* Spruce up email, check length */
			$subemail = stripslashes($subemail);

			/* Check if email is not alphanumeric */
			$regex = "^[_+a-z0-9-]+(\.[_+a-z0-9-]+)*"
					."@[a-z0-9-]+(\.[a-z0-9-]{1,})*"
							."\.([a-z]{2,}){1}$";

			if(!eregi($regex,$subemail)){
				$form->setError($field, "* Email not valid");
				$_SESSION['regerrors'] .= "<p>Entered email not valid</p>\n";
				if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>email not valid</p>\n";}
			}

			/* Check if email is already in use */
			else if($database->emailTaken($subemail)){
				$form->setError($field, "* Email already in use");
				if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>email in use</p>\n";}
			}
			/* Check if email is banned */
			 	
			/*else if($database->emailBanned($subemail)){
			 $form->setError($field, "* Email banned");
			}*/
		}

		/* Password error checking */
		$field = "pass";  //Use field name for password
		if($subpass1 == $subpass2)
		{
			if(!$subpass1){
				$form->setError($field, "* Password not entered");
				$_SESSION['regerrors'] .= "<p>Password not entered</p>\n";
				if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Password not entered</p>\n";}
			}
			else
			{
				/* Spruce up password and check length*/
				$subpass1 = stripslashes($subpass1);
				if(strlen($subpass1) < 4){
					$form->setError($field, "* Password too short");
				}
				/* Check if password is not alphanumeric */
				else if(!eregi("^([0-9a-zA-Z])+$", ($subpass1 = trim($subpass1)))){
					$form->setError($field, "* Password not alphanumeric");
					if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Password not alphanumeric</p>\n";}
				}
				/**
				 * Note: I trimmed the password only after I checked the length
				 * because if you fill the password field up with spaces
				 * it looks like a lot more characters than 4, so it looks
				 * kind of stupid to report "password too short".
				 */
			}
		}
		



		/* Name error checking */
		$field = "name";  //Use field name for name
		if(!$subname || strlen($subname = trim($subname)) == 0)
		{
			$form->setError($field, "* Name not entered");
		}
		else
		{
			/* Check if valid email address
			 $regex = "^[_+a-z0-9-]+(\.[_+a-z0-9-]+)*"
			."@[a-z0-9-]+(\.[a-z0-9-]{1,})*"
			."\.([a-z]{2,}){1}$";
			if(!eregi($regex,$subemail)){
			$form->setError($field, "* Email invalid");
			}*/
			$subname = stripslashes($subname);
		}



		/* Errors exist, have user correct them */
		if($form->num_errors > 0)
		{
			return 1;  //Errors with form
		}
		/* No errors, add the new account to the */
		else
		{
			if($database->addNewUser($subemail, $subpass, $subname))
			{
				/*if(EMAIL_WELCOME)
				 {
				$mailer->sendWelcome($subemail,$subpass,$subname);
				}*/
				return 0;  //New user added succesfully
			}
			else
			{
				return 2;  //Registration attempt failed
			}
		}
	}

	/**
	 * editAccount - Attempts to edit the user's account information
	 * including the password, which it first makes sure is correct
	 * if entered, if so and the new password is in the right
	 * format, the change is made. All other fields are changed
	 * automatically.
	 */
	function editAccount($subcurpass, $subnewpass, $subemail)
	{
		global $database, $form;  //The database and form object
		/* New password entered */
		if($subnewpass){
			/* Current Password error checking */
			$field = "curpass";  //Use field name for current password
			if(!$subcurpass){
				$form->setError($field, "* Current Password not entered");
			}
			else{
				/* Check if password too short or is not alphanumeric */
				$subcurpass = stripslashes($subcurpass);
				if(strlen($subcurpass) < 4 ||
						!eregi("^([0-9a-z])+$", ($subcurpass = trim($subcurpass)))){
					$form->setError($field, "* Current Password incorrect");
				}
				/* Password entered is incorrect */
				if($database->confirmUserPass($this->email,$subcurpass) != 0){
					$form->setError($field, "* Current Password incorrect");
				}
			}

			/* New Password error checking */
			$field = "newpass";  //Use field name for new password
			/* Spruce up password and check length*/
			$subpass = stripslashes($subnewpass);
			if(strlen($subnewpass) < 4){
				$form->setError($field, "* New Password too short");
			}
			/* Check if password is not alphanumeric */
			else if(!eregi("^([0-9a-z])+$", ($subnewpass = trim($subnewpass)))){
				$form->setError($field, "* New Password not alphanumeric");
			}
		}
		/* Change password attempted */
		else if($subcurpass){
			/* New Password error reporting */
			$field = "newpass";  //Use field name for new password
			$form->setError($field, "* New Password not entered");
		}

		/* Email error checking */
		$field = "email";  //Use field name for email
		if($subemail && strlen($subemail = trim($subemail)) > 0){
			/* Check if valid email address */
			$regex = "^[_+a-z0-9-]+(\.[_+a-z0-9-]+)*"
					."@[a-z0-9-]+(\.[a-z0-9-]{1,})*"
							."\.([a-z]{2,}){1}$";
			if(!eregi($regex,$subemail)){
				$form->setError($field, "* Email invalid");
			}
			$subemail = stripslashes($subemail);
		}

		/* Errors exist, have user correct them */
		if($form->num_errors > 0){
			return false;  //Errors with form
		}

		/* Update password since there were no errors */
		if($subcurpass && $subnewpass){
			$database->updateUserField($this->email,"password",md5($subnewpass));
		}

		/* Change Email */
		if($subemail){
			$database->updateUserField($this->email,"email",$subemail);
		}

		/* Success! */
		return true;
	}


	/**
	 * Middle man to get the list from the database, and pass it on to the rendered window
	 */
	function theShoppingList()
	{
		global $database;
	
		$listString = $database->getListItems($this->userid, $this->userListID);
		echo $listString;
	}
	



	/**
	 * isAdmin - Returns true if currently logged in user is
	 * an administrator, false otherwise.
	 */
	function isAdmin()
	{
		return ($this->userlevel == ADMIN_LEVEL ||
				$this->email  == ADMIN_NAME);
	}

	/**
	 * generateRandID - Generates a string made up of randomized
	 * letters (lower and upper case) and digits and returns
	 * the md5 hash of it to be used as a userid.
	 */
	function generateRandID()
	{
		return md5($this->generateRandStr(16));
	}

	/**
	 * generateRandStr - Generates a string made up of randomized
	 * letters (lower and upper case) and digits, the length
	 * is a specified parameter.
	 */
	function generateRandStr($length){
		$randstr = "";
		for($i=0; $i<$length; $i++){
			$randnum = mt_rand(0,61);
			if($randnum < 10){
				$randstr .= chr($randnum+48);
			}else if($randnum < 36){
				$randstr .= chr($randnum+55);
			}else{
				$randstr .= chr($randnum+61);
			}
		}
		return $randstr;
	}
};


/**
 * Initialize session object - This must be initialized before
 * the form object because the form uses session variables,
 * which cannot be accessed unless the session has started.
 */
$session = new Session;

/* Initialize form object */
$form = new Form;

?>
