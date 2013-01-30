<?php
/**
 * @package    FrameworkOnFramework
 * @copyright  Copyright (C) 2010 - 2012 Akeeba Ltd. All rights reserved.
 * @license    GNU General Public License version 3 or later; see LICENSE.txt
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

/**
 * FrameworkOnFramework input handling class. Extends upon the JInput class.
 */
class FOFInput extends JInput
{
	/**
	 * Public constructor. Overriden to allow specifying the global input array
	 * to use as a string and instantiate from an objetc holding variables.
	 * 
	 * @param array|string|object|null $source Source data; set null to use $_REQUEST
	 * @param array $options Filter options
	 */
	public function __construct($source = null, array $options = array())
	{
		if(is_string($source)) {
			$hash = strtoupper($source);
			switch($hash) {
				case 'GET':
					$source = $_GET;
					break;
				case 'POST':
					$source = $_POST;
					break;
				case 'FILES':
					$source = $_FILES;
					break;
				case 'COOKIE':
					$source = $_COOKIE;
					break;
				case 'ENV':
					$source = $_ENV;
					break;
				case 'SERVER':
					$source = $_SERVER;
					break;
				default:
					$source = $_REQUEST;
					$hash = 'REQUEST';
					break;
			}
		} elseif(is_object($source)) {
			try {
				$source = (array)$source;
			} catch (Exception $exc) {
				$source = null;
			}
		} elseif(is_array($source)) {
			// Nothing, it's already an array
		} else {
			// Any other case
			$source = $_REQUEST;
		}
		
		parent::__construct($source, $options);
	}
	
	/**
	 * Gets a value from the input data. Overriden to allow specifying a filter
	 * mask.
	 *
	 * @param   string  $name     Name of the value to get.
	 * @param   mixed   $default  Default value to return if variable does not exist.
	 * @param   string  $filter   Filter to apply to the value.
	 *
	 * @return  mixed  The filtered input value.
	 */
	public function get($name, $default = null, $filter = 'cmd', $mask = 0)
	{
		if (isset($this->data[$name]))
		{
			return $this->_cleanVar($this->data[$name], $mask, $filter);
		}

		return $default;
	}
	
	/**
	 * Returns a copy of the raw data stored in the class
	 * 
	 * @return type
	 */
	public function getData()
	{
		return $this->data;
	}
	
	/**
	 * Old static methods are now deprecated. This magic method makes sure there
	 * is a continuity in our approach. The downside is that it's only compatible
	 * with PHP 5.3.0. Sorry!
	 * 
	 * @param string $name Name of the method we're calling
	 * @param array $arguments The arguments passed to the method
	 * @return mixed
	 */
	public static function __callStatic($name, $arguments) {
		JLog::add('FOFInput: static getXXX() methods are deprecated. Use the input object\'s methods instead.', JLog::WARNING, 'deprecated');
		
		if(substr($name, 0, 3) == 'get') {
			// Initialise arguments
			$key = array_shift($arguments);
			$default = array_shift($arguments);
			$input = array_shift($arguments);
			$type = 'none';
			$mask = 0;
			
			$type = strtolower(substr($name, 3));
			if($type == 'var') {
				$type = array_shift($arguments);
				$mask = array_shift($arguments);
			}
			if(is_null($type)) {
				$type = 'none';
			}
			if(is_null($mask)) {
				$mask = 0;
			}
			
			if(!($input instanceof FOFInput) && !($input instanceof JInput)) {
				$input = new FOFInput($input);
			}
			return $input->get($key, $default, $type, $mask);
		}
		
		return false;
	}
	
	/**
	 * Sets an input variable. WARNING: IT SHOULD NO LONGER BE USED!
	 * 
	 * @param type $name
	 * @param type $value
	 * @param type $input
	 * @param type $overwrite
	 * @return type
	 * 
	 * @deprecated
	 */
	public static function setVar($name, $value = null, &$input = array(), $overwrite = true)
	{
		JLog::add('FOFInput::setVar() is deprecated. Use set() instead.', JLog::WARNING, 'deprecated');
		
		if(empty($input)) {
			return JRequest::setVar($name, $value, 'default', $overwrite);
		} elseif(is_string($input)) {
			return JRequest::setVar($name, $value, $input, $overwrite);
		} else {
			if(!$overwrite && array_key_exists($name, $input)) {
				return $input[$name];
			}
			
			$previous = array_key_exists($name, $input) ? $input[$name] : null;
			
			if(is_array($input)) {
				$input[$name] = $value;
			} elseif($input instanceof FOFInput) {
				$input->set($name, $value);
			}
			
			return $previous;
		}
	}
	
	public static function getString($name, $default = '', $input = array(), $mask = 0)
	{
		JLog::add('FOFInput::getString() is deprecated. Use get() instead.', JLog::WARNING, 'deprecated');
		// Cast to string, in case JREQUEST_ALLOWRAW was specified for mask
		return (string) self::getVar($name, $default, $input, 'string', $mask);
	}
	
	/**
	 * Custom filter implementation. Works better with arrays and allows the use
	 * of a filter mask.
	 * 
	 * @param string $var
	 * @param int $mask
	 * @param string $type
	 * 
	 * @return mixed
	 */
	protected function _cleanVar($var, $mask = 0, $type = null)
	{
		if(is_array($var)) {
			$temp = array();
			foreach($var as $k => $v) {
				$temp[$k] = self::_cleanVar($v, $mask);
			}
			return $temp;
		}
		
		// If the no trim flag is not set, trim the variable
		if (!($mask & 1) && is_string($var))
		{
			$var = trim($var);
		}

		// Now we handle input filtering
		if ($mask & 2)
		{
			// If the allow raw flag is set, do not modify the variable
			$var = $var;
		}
		elseif ($mask & 4)
		{
			// If the allow HTML flag is set, apply a safe HTML filter to the variable
			$safeHtmlFilter = JFilterInput::getInstance(null, null, 1, 1);
			$var = $safeHtmlFilter->clean($var, $type);
		}
		else
		{
			$this->filter->clean($var, $type);
		}
		return $var;
	}
}