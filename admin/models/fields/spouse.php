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

		// Get some field values from the form.
		$memberId        = (int) $this->form->getValue('id');
		$categoryId      = (int) $this->form->getValue('catid');
		$funitid         = (int) $this->form->getValue('funitid');
		$memberfustatus  = (int) $this->form->getValue('familypostion', 'attribs');

		// Build the query for the ordering list.
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('id, name, funitid, attribs, spouse, mstatus')
				->from('#__churchdirectory_details')
				->where('catid = ' . (int) $categoryId)
				->where('published = 1')
				->where('funitid = ' . (int) $funitid);
		$db->setQuery($query);
		$results = $db->loadObjectList();

		foreach ($results AS $item)
		{
			$registry = new Registry($item->attribs);

			$inputAttributes = array(
				'type' => 'text', 'id' => $item->id, 'value' => $db->escape($item->name) . ' '
					. ChurchDirectoryHelper::memberStatusShort($item->mstatus)
			);

			if ($item->funitid !== '0' && $item->id != $memberId
				&& $registry->get('familypostion', 2) !== (int) $memberfustatus
				&& $memberfustatus !== 2)
			{
				return '<input ' . ArrayHelper::toString($inputAttributes) . ' readonly />';
			}
			elseif ($item->funitid <= '0' && $item->id == $memberId && !empty($item->spouse))
			{
				$inputAttributes['value'] = 'Old Record: ' . $db->escape($item->spouse);

				return '<input ' . ArrayHelper::toString($inputAttributes) . ' readonly />';
			}
		}

		$inputAttributes['value'] = ' ';

		return '<input ' . ArrayHelper::toString($inputAttributes) . ' readonly />';
	}
}
