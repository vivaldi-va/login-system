<?php
include_once 'session.php';

class Vendor
{
	
	/*
	 * User vars
	 *  - shop user is connected to
	 *  - user permissions
	 */
	var $shop;
	var $permissions;
	/*
	 * Permissions: 
	 * 5 - basic shop user
	 * 		can change prices and add discounts
	 * 6 - elevated shop user
	 * 		can add, remove and edit products
	 * 7 - manager shop user
	 * 		can add and remove users from shop
	 * 		can change user permissions
	 */
	
	
	/*
	 * shop-specific variables
	 * stats
	 * etc.
	 */
	var $products;
	var $discounts;
	
	/**
	 * Get the shop the user is associated with and set the related variables
	 * @return boolean
	 */
	function Vendor()
	{
		$this->shop = 70;
	}
	
	function getUserShop()
	{
		global $database, $session;
		
		// return associative array with the found user info, or return false if error
		$retval = $database->queryUserVendor($session->userid);
		if(!$retval)
		{
			return false;
		}
		
		$this->shop = $retval['shop'];
		$this->permissions = $retval['permissions'];
		
		return true;
	}
	
	/**
	 * Takes the product price and adds a discount modifier to return a discounted price value.
	 * The modifier can either be set as a defined value, or a percentage discount value. 
	 * The third argument will use the modifier as a fixed return value if true.
	 * 
	 * 
	 * @param float $productID
	 * @param float $modifier
	 * @param boolean $fixedVal
	 * @return boolean|string
	 */
	function getCalculatedPrice($productID, $modifier, $fixedVal = false)
	{
		global $product_functions;
		$basePrice = $product_functions->getProductPrice($productID, $this->shop);
		if(!$basePrice)
		{
			return false;
		}
		
		$calculatedPrice = $basePrice;
		
		if($fixedVal)
		{
			$calculatedPrice = $modifier;
		}
		else
		{
			$calculatedPrice *= $modifier;
		}
		
		return $calculatedPrice;
	}
	
};

$vendor = new Vendor;