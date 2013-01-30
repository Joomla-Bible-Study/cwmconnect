<?php
/**
 * @package    FrameworkOnFramework
 * @copyright  Copyright (C) 2010 - 2012 Akeeba Ltd. All rights reserved.
 * @license    GNU General Public License version 3 or later; see LICENSE.txt
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

/**
 * Form Field class for FOF
 * Renders the checkbox in browse views which allows you to select rows
 *
 * @since       2.0
 */
class FOFFormFieldSelectrow extends JFormField implements FOFFormField
{
	protected $static;
	
	protected $repeatable;

	/** @var int A monotonically increasing number, denoting the row number in a repeatable view */
	public $rowid;
	
	/**
	 * Method to get certain otherwise inaccessible properties from the form field object.
	 *
	 * @param   string  $name  The property name for which to the the value.
	 *
	 * @return  mixed  The property value or null.
	 *
	 * @since   2.0
	 */
	public function __get($name)
	{
		switch($name) {
			case 'static':
				if(empty($this->static)) {
					$this->static = $this->getStatic();
				}

				return $this->static;
				break;
				
			case 'repeatable':
				if(empty($this->repeatable)) {
					$this->repeatable = $this->getRepeatable();
				}

				return $this->static;
				break;
				
			default:
				return parent::__get($name);
		}
	}
	
	protected function getInput()
	{
		throw new Exception(__CLASS__.' cannot be used in input forms');
	}
	
	public function getStatic()
	{
		throw new Exception(__CLASS__.' cannot be used in single item display forms');
	}
	
	public function getRepeatable()
	{
		if (!($this->item instanceof FOFTable))
		{
			throw new Exception(__CLASS__.' needs a FOFTable to act upon');
		}

		// Is this record checked out?
		$checked_out = false;
		$locked_by_field = $this->item->getColumnAlias('locked_by');
		if(property_exists($this->item, $locked_by_field))
		{
			$locked_by = $this->item->$locked_by_field;
			$checked_out = ($locked_by != 0);
		}
		
		// Get the key id for this record
		$key_field = $this->item->getKeyName();
		$key_id = $this->item->$key_field;
		
		// Get the HTML
		return JHTML::_('grid.id', $this->rowid, $key_id, $checked_out);
	}
}