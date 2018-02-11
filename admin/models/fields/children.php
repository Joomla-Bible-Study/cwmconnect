<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('JPATH_BASE') or die;

jimport('joomla.html.html');
jimport('joomla.form.formfield');

use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

/**
 * Supports the look up of a Children
 *
 * @package  ChurchDirectory.Admin
 * @since    1.8.4
 */
class JFormFieldChildren extends JFormField
{
	/**
	 * The form field type.
	 *
	 * @var  string
	 * @since    1.8.4
	 */
	protected $type = 'Children';

	/**
	 * Method to get the field input markup.
	 *
	 * @return    string    The field input markup.
	 *
	 * @since    1.8.4
	 */
	protected function getInput ()
	{
		// Initialize variables.
		$html = '';

		// Get some field values from the form.
		$memberId        = (int) $this->form->getValue('id');
		$categoryId      = (int) $this->form->getValue('catid');
		$funitid         = (int) $this->form->getValue('funitid');
		$memberfustatus  = (int) $this->form->getValue('familypostion', 'attribs');
		$chiledren       = [];

		$inputAttributes = array(
			'type' => 'text', 'id' => $memberId, 'style' => 'width: 500px'
		);

		// Build the query for the ordering list.
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('id, name, funitid, attribs, spouse, mstatus')
			->from('#__churchdirectory_details')
			->where('catid = ' . (int) $categoryId)
			->where('funitid = ' . (int) $funitid);
		$db->setQuery($query);
		$results = $db->loadObjectList();

		foreach ($results AS $item)
		{
			$registry = new Registry($item->attribs);

			if ($item->funitid !== '0' && $item->id != $memberId
				&& (int) $registry->get('familypostion', 2) == 2
				&& $memberfustatus !== 2)
			{
				$chiledren[] = $db->escape($item->name) . " "
					. ChurchDirectoryHelper::memberStatusShort($item->mstatus);
			}
		}

		$inputAttributes['value'] = implode(", ", $chiledren);

		return '<input ' . ArrayHelper::toString($inputAttributes) . ' readonly />';
	}
}
