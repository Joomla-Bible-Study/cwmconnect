<?php
/**
 * @package    FrameworkOnFramework
 * @copyright  Copyright (C) 2010 - 2012 Akeeba Ltd. All rights reserved.
 * @license    GNU General Public License version 3 or later; see LICENSE.txt
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

/**
 * Generic field header, with text input (search) filter
 * 
 * @since 2.0
 */
class FOFFormHeaderFieldsearchable extends FOFFormHeaderField
{
	protected function getFilter()
	{
		// Initialize some field attributes.
		$size = $this->element['size'] ? ' size="' . (int) $this->element['size'] . '"' : '';
		$maxLength = $this->element['maxlength'] ? ' maxlength="' . (int) $this->element['maxlength'] . '"' : '';
		$filterclass = $this->element['filterclass'] ? ' class="' . (string) $this->element['filterclass'] . '"' : '';
		$placeholder = $this->element['placeholder'] ? $this->element['placeholder'] : $this->getLabel();
		$placeholder = 'placeholder="' . JText::_($placeholder) . '"';

		// Initialize JavaScript field attributes.
		if ($this->element['onchange'])
		{
			$onchange = ' onchange="' . (string) $this->element['onchange'] . '"';
		}
		else
		{
			$onchange = ' onchange="document.adminForm.submit();"';
		}
		

		return '<input type="text" name="' . $this->name . '" id="' . $this->id . '"' . ' value="'
			. htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8') . '"' . $filterclass . $size . $placeholder . $onchange . $maxLength . '/>';		
	}
	
	protected function getButtons() {
		$buttonclass = $this->element['buttonclass'] ? ' class="' . (string) $this->element['buttonclass'] . '"' : '';
		$show_buttons = !($this->element['buttons'] == 'false');
		
		if(!$show_buttons)
		{
			return '';
		}
		
		$html = '';
		
		$html .= '<button ' . $buttonclass . ' onclick="this.form.submit();">' . "\n";
		$html .= "\t" . JText::_('JSEARCH_FILTER') . "\n";
		$html .= '</button>' . "\n";
		$html .= '<button ' . $buttonclass . ' onclick="document.adminForm.' . $this->id . '.value=\'\';this.form.submit();">' . "\n";
		$html .= "\t" . JText::_('JSEARCH_RESET') . "\n";
		$html .= '</button>' . "\n";
		
		return $html;
	}
}