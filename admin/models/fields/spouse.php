<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('JPATH_BASE') or die;

jimport('joomla.html.html');
jimport('joomla.form.formfield');

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
	protected function getInput()
	{
		// Initialize variables.
		$html = '';
		$attr = '';
		$find = '';

		// Initialize some field attributes.
		$attr .= $this->element['class'] ? ' class="' . (string) $this->element['class'] . '"' : '';
		$attr .= ((string) $this->element['disabled'] == 'true') ? ' disabled="disabled"' : '';
		$attr .= $this->element['size'] ? ' size="' . (int) $this->element['size'] . '"' : '';

		// Initialize JavaScript field attributes.
		$attr .= $this->element['onchange'] ? ' onchange="' . (string) $this->element['onchange'] . '"' : '';

		// Get some field values from the form.
		$memberId      = (int) $this->form->getValue('id');
		$categoryId    = (int) $this->form->getValue('catid');
		$funitid       = (int) $this->form->getValue('funitid');
		$familypostion = (int) $this->form->getValue('familypostion');


		if ($familypostion == '0')
		{
			$find = 1;
		}
		elseif ($familypostion == '1')
		{
			$find = 0;
		}

		// Build the query for the ordering list.
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('id, name, funitid, attribs, spouse')
			->from('#__churchdirectory_details')
			->where('catid = ' . (int) $categoryId)
			->where('funitid = ' . (int) $funitid);
		$db->setQuery($query);
		$results = $db->loadObjectList();

		foreach ($results AS $item)
		{
			$registry = new JRegistry;
			$registry->loadString($item->attribs);
			$familypostion = $registry->toObject('familypostion');
			$item          = (object) array_merge((array) $item, (array) $familypostion);

			if ($item->funitid >= '1' && $item->familypostion == $find)
			{
				$html = '<h4>' . $item->name . '</h4>';
			}
			elseif ($item->funitid <= '0' && $item->id == $memberId)
			{

				$html = '<h4>Old: ' . $item->spouse . '</h4>';
			}
		}

		return $html;
	}

}
