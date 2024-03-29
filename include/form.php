<?php
/**
 * Form.php
 *
 * The Form class is meant to simplify the task of keeping
 * track of errors in user submitted forms and the form
 * field values that were entered correctly.
 *
 * Written by: Jpmaster77 a.k.a. The Grandmaster of C++ (GMC)
 * Last Updated: August 19, 2004
 */

class Form
{
	var $values = array();  //Holds submitted form field values
	var $errors = array();  //Holds submitted form error messages
	var $num_errors;   //The number of errors in submitted form

	var $debug_info = array(); //Debug messages to return
	var $num_debug_messages = 0; // Number of debug messages (used to define key for array)


	/* Class constructor */
	function Form()
	{
		/**
		 * Get form value and error arrays, used when there
		 * is an error with a user-submitted form.
		 */
		if(isset($_SESSION['value_array']) && isset($_SESSION['error_array'])){
			$this->values = $_SESSION['value_array'];
			$this->errors = $_SESSION['error_array'];
			$this->num_errors = count($this->errors);

			unset($_SESSION['value_array']);
			unset($_SESSION['error_array']);
		}
		else
		{
			$this->num_errors = 0;
		}
	}

	/**
	 * setValue - Records the value typed into the given
	 * form field by the user.
	 * 
	 * @param unknown_type $field
	 * @param unknown_type $value
	 */
	function setValue($field, $value)
	{
		$this->values[$field] = $value;
	}

	/**
	 * setError - Records new form error given the form
	 * field name and the error message attached to it.
	 * 
	 * @param unknown_type $field
	 * @param unknown_type $errmsg
	 */
	function setError($field, $errmsg)
	{
		$this->errors[$field] = $errmsg;
		$this->num_errors = count($this->errors);
	}

	/**
	 * value - Returns the value attached to the given
	 * field, if none exists, the empty string is returned.
	 */
	function value($field)
	{
		if(array_key_exists($field,$this->values))
		{
			return htmlspecialchars(stripslashes($this->values[$field]));
		}
		else
		{
			return "";
		}
	}

	/**
	 * error - Returns the error message attached to the
	 * given field, if none exists, the empty string is returned.
	 * 
	 * @param unknown_type $field
	 * @return string
	 */
	function error($field)
	{
		if(array_key_exists($field,$this->errors))
		{
			return "<span class=\"form-error\">".$this->errors[$field]."</span>";
		}
		else
		{
			return "";
		}
	}

	/**
	 * getErrorArray - Returns the array of error messages
	 * @return multitype:
	 */
	function getErrorArray()
	{
		return $this->errors;
	}
	
	
	/**
	 * Function to add a debug message to the array, for future display
	 * @param string $message
	 * @return void
	 */
	function setDebugMessage($message)
	{
		$this->debug_info[$this->num_debug_messages] = $message;
		$this->num_debug_messages += 1;
	}
	
	/**
	 * Function to retrieve the debug info array 
	 * @return array:
	 */
	function getDebugMessages()
	{
		return $this->debug_info;
	}
};

?>
