<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('JPATH_BASE') or die;

jimport('joomla.html.html');
jimport('joomla.form.formfield');

/**
 * Supports an HTML select list of contacts
 *
 * @package  ChurchDirectory.Admin
 * @since    1.7.0
 */
class JFormFieldOrdering extends JFormField
{
	/**
	 * The form field type.
	 *
	 * @var  string
	 * @since    1.7.0
	 */
	protected $type = 'Ordering';

	/**
	 * Method to get the field input markup.
	 *
	 * @return    string    The field input markup.
	 *
	 * @since    1.7.0
	 */
	protected function getInput()
	{
		// Initialize variables.
		$html = [];
		$attr = '';

		// Initialize some field attributes.
		$attr .= $this->element['class'] ? ' class="' . (string) $this->element['class'] . '"' : '';
		$attr .= ((string) $this->element['disabled'] == 'true') ? ' disabled="disabled"' : '';
		$attr .= $this->element['size'] ? ' size="' . (int) $this->element['size'] . '"' : '';

		// Initialize JavaScript field attributes.
		$attr .= $this->element['onchange'] ? ' onchange="' . (string) $this->element['onchange'] . '"' : '';

		// Get some field values from the form.
		$contactId  = (int) $this->form->getValue('id');
		$categoryId = (int) $this->form->getValue('catid');

		// Build the query for the ordering list.
		$query = 'SELECT ordering AS value, name AS text' .
			' FROM #__churchdirectory_details' .
			' WHERE catid = ' . (int) $categoryId .
			' ORDER BY ordering';

		// Create a read-only list (no name) with a hidden input to store the value.
		if ((string) $this->element['readonly'] == 'true')
		{
			$html[] = JHtml::_('list.ordering', '', $query, trim($attr), $this->value, $contactId ? 0 : 1);
			$html[] = '<input type="hidden" name="' . $this->name . '" value="' . $this->value . '"/>';
		}
		// Create a regular list.
		else
		{
			$html[] = JHtml::_('list.ordering', $this->name, $query, trim($attr), $this->value, $contactId ? 0 : 1);
		}

		return implode($html);
	}
}
