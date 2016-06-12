<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2014 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('JPATH_BASE') or die;

jimport('joomla.html.html');
jimport('joomla.form.formfield');

use Joomla\Registry\Registry;

/**
 * Supports th look up of a Spouse
 *
 * @package  ChurchDirectory.Admin
 * @since    1.7.0
 */
class JFormFieldSpouse extends JFormField
{

	/**
	 * The form field type.
	 *
	 * @var  string
	 * @since    1.7.0
	 */
	protected $type = 'Spouse';

	/**
	 * Method to get the field input markup.
	 *
	 * @return    string    The field input markup.
	 *
	 * @since    1.7.0
	 */
	protected function getInput ()
	{
		// Initialize variables.
		$html = '';
		$attr = '';

		// Initialize some field attributes.
		$attr .= $this->element['class'] ? ' class="' . (string) $this->element['class'] . '"' : '';
		$attr .= ((string) $this->element['disabled'] == 'true') ? ' disabled="disabled"' : '';
		$attr .= $this->element['size'] ? ' size="' . (int) $this->element['size'] . '"' : '';

		// Initialize JavaScript field attributes.
		$attr .= $this->element['onchange'] ? ' onchange="' . (string) $this->element['onchange'] . '"' : '';

		// Get some field values from the form.
		$memberId        = (int) $this->form->getValue('id');
		$categoryId      = (int) $this->form->getValue('catid');
		$funitid         = (int) $this->form->getValue('funitid');

		// Build the query for the ordering list.
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('id, name, funitid, attribs, spouse')
				->from('#__churchdirectory_details')
				->where('catid = ' . (int) $categoryId)
				->where('published = 1')
				->where('funitid = ' . (int) $funitid);
		$db->setQuery($query);
		$results = $db->loadObjectList();

		foreach ($results AS $item)
		{
			$registry = new Registry;
			$registry->loadString($item->attribs);
			// Todo Need to fix this. Bcc;
			$family_position = $registry->toObject('familypostion');
			$item            = (object) array_merge((array) $item, (array) $family_position);

			if ($item->funitid != '0' && $item->id != $memberId)
			{
				$link = 'index.php?option=com_churchdirectory&task=member.edit&id=' . (int) $item->id . '&tmpl=component&layout=modal';
				$html = '<h4><a class="btn btn-primary" onclick="SqueezeBox.fromElement(this, {handler:\'iframe\', size: {x: 900, y: 550}, url:\'' . $link . '\'})"
			   title="' . $item->name . '">';

				$html .= $db->escape($item->name);
				$html .= '</a>';
			}
			elseif ($item->funitid <= '0' && $item->id == $memberId)
			{

				$html = '<h4>Old Record: ' . $item->spouse . '</h4>';
			}
		}

		return $html;
	}

}
