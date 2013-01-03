<?php

/**
 * 
 * @author vivaldi
 *
 * @abstract 
 */
class Controller
{
	var $page;
	var $pageName;
	
	/**
	 * Sets the page using a string identifier
	 * @param unknown_type $pageName
	 */
	function setPage($pageName)
	{
		$this->page = $pageName;
	}
	
	function getPage()
	{
		return $this->page;
	}
	
	/**
	 * 
	 * @param string $page
	 */
	function getPageName($page)
	{
		if($page)
		{
			switch ($page)
			{
				case "":
					break;
			}
		}
	}
	
	
};

