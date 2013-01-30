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
 * Renders the row ordering interface checkbox in browse views
 *
 * @since       2.0
 */
class FOFFormFieldOrdering extends JFormField implements FOFFormField
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

		$html = '';
		
		$viewObject = $this->form->getView();
		
		$ordering = $viewObject->getLists()->order == 'ordering';
		
		if (!$viewObject->hasAjaxOrderingSupport())
		{
			// Ye olde Joomla! 2.5 method
			$disabled = $ordering ?  '' : 'disabled="disabled"';
			$html .= '<span>';
			$html .= $viewObject->pagination->orderUpIcon($this->rowid, true, 'orderup', 'Move Up', $ordering);
			$html .= '</span><span>';
			$html .= $viewObject->pagination->orderDownIcon($this->rowid, true, 'orderup', 'Move Up', $ordering);
			$html .= '</span>';
			$html .= '<input type="text" name="order[]" size="5" value="' . $this->value . '" ' . $disabled;
			$html .= 'class="text_area" style="text-align: center" />';
		}
		else
		{
			// The modern drag'n'drop method
			if ($viewObject->getPerms()->editstate)
			{
				$disableClassName = '';
				$disabledLabel	  = '';
				
				$hasAjaxOrderingSupport = $viewObject->hasAjaxOrderingSupport();
				
				if (!$hasAjaxOrderingSupport['saveOrder'])
				{
					$disabledLabel    = JText::_('JORDERINGDISABLED');
					$disableClassName = 'inactive tip-top';
				}
				
				$html .= '<span class="sortable-handler ' . $disableClassName . '" title="' . $disabledLabel . '" rel="tooltip">';
				$html .= '<i class="icon-menu"></i>';
				$html .= '</span>';
				$html .= '<input type="text" style="display:none"  name="order[]" size="5"';
				$html .= 'value="' . $this->value . '"  class="input-mini text-area-order " />';
			}
			else
			{
				$html .= '<span class="sortable-handler inactive" >';
				$html .= '<i class="icon-menu"></i>';
				$html .= '</span>';
			}
		}
		
		return $html;

	}
}