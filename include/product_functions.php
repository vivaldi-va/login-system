<?php

	include_once('database.php');
	include_once('session.php');
	include_once('form.php');
	include_once("constants.php");
	
	/**
	 * 
	 * @author vivaldi
	 *
	 */
	class Product_Functions
	{
		
		var $searchResults;
		
		
		
		function search($searchTerm, $returnArray = false)
		{
			global $database;
			if(strlen($searchTerm) >= 2)
			{
				if($returnArray)
				{
					$searchResults = $database->productSearchArr($searchTerm);
					// Debug function to dump the contents of the totalAvarageArray var
					if(DEBUG_MODE){
						ob_start();
						var_dump($searchResults);
						$totalArrayDump = ob_get_clean();
						$_SESSION['debug_info'] .= "<p>Info in total array: <br>$totalArrayDump</p>\n";
					}
				}
				else
				{
					$searchResults = $database->productSearch($searchTerm);
					//$this->searchResults = $searchResults;
				}
			}
			else 
			{
				return false;
			}
			//if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p><code>$searchResults</code></p>\n";}
			return $searchResults;
		}
		
		
		/**
		 * Return the list of products found from the 
		 */
		function browseSearch($term)
		{
			global $database;
			//$searchTerm = $_SESSION['browseSearchTerm'];
			$escapedTerm = mysql_real_escape_string($term);
			$searchResults = $database->returnSearchList($escapedTerm);
			if($searchResults)
			{
				echo $searchResults;
			}
			else
			{
				echo "No Results Found.";
			}
		}
		
		
		/**
		 * Add/Remove product functions
		 */
		
		/**
		 * Relay from process.php to database.php
		 *
		 * @param string $query
		 * @return string
		 */
		function brandName($query)
		{
			global $database;
			return $database->getBrandName($query);
		}
		
		
		/**
		 * Take a product id, retrieved from a search and, using a function in database.php, add it
		 * to the user's list
		 * @param int $productId
		 * @param int $quantity
		 *
		 * @return boolean
		 */
		function addToList($productId)
		{
			global $database;
			global $session;
			
			
			$worked = $database->addProductToList($productId, $session->userListID);
			return $worked;
		}
		
		
		/**
		 *
		 * @param int $listItemId
		 * @return boolean
		 */
		function removeFromList($listItemId)
		{
			global $database;
			$worked = $database->removeProductFromList($listItemId);
			return $worked;
		}
		
		/**
		 * Validate array of values retrieved from the form, send them
		 * to the database for insertion as a new product
		 * 
		 * @param assoc_array $newProductArray
		 * @return boolean
		 */
		function addNewProduct($newProductArray)
		{
			global $database;
			global $session;
		
			
			if(DEBUG_MODE){
				$_SESSION['debug_info'] .= "<p>Values of array coming into function:<br>\n";
				foreach($newProductArray AS $key => $value)
				{
					$_SESSION['debug_info'] .= "$key = $value<br>\n";
				}
				$_SESSION['debug_info'] .= "</p>\n";
			}
			
			//Get the brand ID from the brand name
			$brandID = $database->getBrandID($newProductArray["brandName"]);
			if(!$brandID)
			{
				if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>New product insert <strong>failed</strong>, brand not found @ session.php</p>\n";}
				return false;
			}
			$brandID = mysql_real_escape_string($brandID);
			
			/*
			 * If barcode is not between 0 and 13 numbers, 
			 * return an error 
			 */
			$barcode  = $newProductArray["barcode"];
			if (!empty($barcode))
			{
				$barcode = -1;
			}
			elseif(!preg_match_all("/[0-9]{0,13}?/", $barcode, $matches))
			{
				return "<div class=\"alert\">barcode is invalid</div>\n";
			}
			else 
			{
				$barcode = -1;
			}
			
			if($newProductArray['catID'] == "null")
			{
				$categoryID = -1;
				if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Category was null, i'll just change that to a default value for you @ product_functions.php</p>\n";}
			}
			else
			{
				$categoryID = mysql_real_escape_string($newProductArray['catID']);
			}
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>cat ID is now: $categoryID</p>\n";}
		
			//Send the values to the database to be inserted
			$retval = $database->insertProduct($session->userid, mysql_real_escape_string($newProductArray["productName"]), $categoryID, $brandID, mysql_real_escape_string($newProductArray["volID"]), mysql_real_escape_string($newProductArray["barcode"]));
		
		
			return $retval;
		}
		
		/**
		 *
		 * @param unknown_type $priceInfoArray
		 */
		function updateProductPrice($priceInfoArray)
		{
			global $database, $session;
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Price info received at @ product_funtions.php</p>\n";}
			
			$priceDecimalFix = mysql_real_escape_string(str_replace(',', '.', $priceInfoArray['price']));
			
			return $database->insertProductPrice($priceInfoArray['productID'], $priceDecimalFix, $priceInfoArray['shopID'], $session->userid);
		}
		
		/**
		 * Call the sort list function in database.php, 
		 * format the returned array into a string.
		 * @return Ambigous <string, boolean>
		 */
		function sortList($location)
		{
			global $database, $session;
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>List sort attempted @ product_functions.php</p>\n";}
			if($location === "" || $location == null || empty($location))
			{
				return "<div class=\"alert\">No location entered</div>";
			}
			if($sortedList = $database->returnSortedList($session->userid, $session->userListID, $location))
			//if($sortedList = $database->returnSortedList($session->userid, $session->userListID))
			{
				return $sortedList;
			}
			else
			{
				return "No prices for items in list";
			}
		}
		
		/**
		 *
		 * @param int $prodID
		 * @return Ambigous <string, Ambigous>
		 */
		function getProductNameFromID($prodID)
		{
			global $database;
			
			return $database->getProdNameFromID($prodID);
		}
		
		
		/**
		 * Retrieve the category list from the database and 
		 * echo it to be displayed as a dropdown list
		 */
		function categoryList($return=false, $selected=null)
		{
			global $database;
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Attempting to get category dropdown list @ product_functions.php</p>\n";}
			$categoryString = $database->getCategoryList($selected);
			if(!$categoryString)
			{
				if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Category list retrieval <strong>failed</strong></p>\n";}
			}
			if($return)
			{
				return $categoryString;
			}
			else 
			{
				echo $categoryString;
			}
		}
		
		
		/**
		 * Relay to the database, intended to return a string listing shops relating to the inputted query
		 * @param string $query
		 * @return string
		 */
		function getShopString ($query)
		{
			global $database;
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>shop search received @ session.php</p>\n";}
			$string = $database->generateShopString($query);
			return $string;
		}
		
		
		/**
		 * Validate the input for the create shop form and 
		 * send to the database using the relevant method
		 * 
		 * @param string $city
		 * @param string $location
		 * @param int $chainID
		 * @return boolean
		 */
		function newStore($city, $location, $chainID)
		{
			global $database;
			global $session;
			global $form;
			
			
			/*
			 * Validation
			 */
			
			//city name validation
			if(empty($city))
			{
				$form->setError("city", "City not entered");
				return false;
			}
			else if(!preg_match("/[A-Za-zäåö]*/", $city, $matches))
			{
				$form->setError("city", "City name must only contain letters");
				return false;
			}
			
			//location name
			if(empty($location))
			{
				$form->setError("location", "Location not entered");
				return false;
			}
			else if(!preg_match("/[A-Za-zäåö]*/", $location, $matches))
			{
				$form->setError("location", "Location name must only contain letters");
				return false;
			}
			
			//Chain ID
			if($chainID < 0 || $chainID == null)
			{
				$form->setError("chainID", "Chain not selected");
				return false;
			}			
			
			if(!$retval = $database->addNewShop($session->userid, $city, $location, $chainID))
			{
				return false;
			}
			
			return true;
		}
		
		
		function addBrand($brandName)
		{
			global $database;
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Attempting to insert brandname @ product_functions.php</p>\n";}
			$insertWorked = $database->insertBrand($brandName);
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>$insertWorked</p>\n";}
			if(!$insertWorked)
			{
				if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>brand insert failed @ product_functions.php</p>\n";}
				return "<div class=\"alert alert-error\">Brand insert failed</div>";
			}
			elseif($insertWorked === -1)
			{
				if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>brand name already exists @ product_functions.php</p>\n";}
				return "<div class=\"alert\">Brand name already exists</div>";
			}
			else 
			{
				return "<div class=\"alert alert-success\">Brand inserted</div>";
			}
			
		}
		
		function setListItemQuantity($listItemID, $quantity)
		{
			global $database;
			
			
			if($quantity > 0)
			{
				$retval = $database->listItemQuantity($listItemID, $quantity);
				if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Set quantity at @ product_functions.php</p>\n";}
			}
			else 
			{
				if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Could <strong>not</strong> set quantity at @ product_functions.php</p>\n";}
				$retval = false;
			}
		}
		
		function getProductName($productID)
		{
			global $database;
			if($retval = $database->getProductName($productID))
			{
				echo $retval; 
			}
			else 
			{
				echo "<em>product name not found</em>";
			}
		}
		
		/**
		 * Echos a value returned from the database for a product, based on the 
		 * supplied ID.
		 * 
		 * Valid Arguments:
		 * name
		 * brand
		 * category
		 * description
		 * barcode
		 * pic
		 * 
		 * @param int $productID
		 * @param string $value
		 */
		function getProductDetails($productID, $value)
		{
			global $database;
			if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Attempting to edit $productID @ product_functions.php</p>\n";}
			//$productInfo = $database->getProductInfo($productID);
			
			if($value === "name")
			{
				echo $database->getProductName($productID);
			}
			else if($value === "brand")
			{
				echo $database->getProductBrand($productID);
			}
			else if($value === "category")
			{
				echo $database->getProductCategory($productID);
			}
			else if($value === "description")
			{
				echo "Havent done descriptions yet";
			}
			else if($value === "barcode")
			{
				echo $database->getProductBarcode($productID);
			}
			else if($value === "pic")
			{
				echo $database->getProductPicture($productID);
			}
			else
			{
				echo "Invalid Argument: \"$value\"";
			}
			
		}
		
		function getProductPrice($productID, $shopID)
		{
			global $database;
			if($price = $database->getProductPrice($productID, $shopID))
			{
				$price =  str_replace('.', ',', strval(round($price, 2)));
				return $this->formatPriceValue($price);
			}
			else
			{
				if(DEBUG_MODE){$_SESSION['debug_info'] .= "<p>Failed to get price @ product_functions.php</p>\n";}
				return false;
			}
		}
		
		function updateProductInfo($productInfo)
		{
			global $database;
			
			$productInfo['productName'] = mysql_real_escape_string($productInfo['productName']);
			$productInfo['barcode'] = mysql_real_escape_string($productInfo['barcode']);
			$productInfo['description'] = mysql_real_escape_string($productInfo['description']);
			if($productInfo['categoryID'] == "null" || !preg_match_all("/[0-9]*/", $productInfo['categoryID'], $matches))
			{
				$productInfo['categoryID'] = -1;
			}
				
			$brandID = $database->getBrandID(mysql_real_escape_string($productInfo['brandName']));
			$fixedProductArray = array(
					"productID" => $productInfo['productID'],
					"productName" => $productInfo['productName'],
					"brandID" => $brandID,
					"categoryID" => $productInfo['categoryID'],
					"barcode" => $productInfo['barcode'],
					"description" => $productInfo['description']);
			
			$retval = $database->updateProductInfo($fixedProductArray);
			if(!$retval)
			{
				return false;
			}
			return true;
		}
		
		/**
		 * Function to format the price float value to use commas as a decimal, and to round the value to 
		 * 2 decimal places.
		 * 
		 * @param float $price
		 * @return string
		 */
		function formatPriceValue($price)
		{
			$formattedPrice = str_replace('.', ',', strval($price));
			return $formattedPrice;
		}
		
	};
	
	$product_functions = new Product_Functions;
?>