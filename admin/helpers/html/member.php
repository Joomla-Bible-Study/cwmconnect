<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

JLoader::register('ChurchDirectoryHelper', JPATH_ADMINISTRATOR . '/components/com_churchdirectory/helpers/churchdirectory.php');

/**
 * Abstract class for JhtmlMember
 *
 * @package  ChurchDirecotry.Admin
 * @since    1.7.5
 */
abstract class JHtmlMember
{
	/**
	 * ?
	 *
	 * @param   int $memberid  The Member item id
	 *
	 * @return mixed
	 */
	public static function association($memberid)
	{
		// Get the associations
		$associations = ChurchDirectoryHelper::getAssociations($memberid);

		foreach ($associations as $tag => $associated)
		{
			$associations[$tag] = (int) $associated->id;
		}

		// Get the associated contact items
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('c.*');
		$query->from('#__contact_details as c');
		$query->select('cat.title as category_title');
		$query->leftJoin('#__categories as cat ON cat.id=c.catid');
		$query->where('c.id IN (' . implode(',', array_values($associations)) . ')');
		$query->leftJoin('#__languages as l ON c.language=l.lang_code');
		$query->select('l.image');
		$query->select('l.title as language_title');
		$db->setQuery($query);
		$items = $db->loadObjectList('id');

		// Check for a database error.
		if ($error = $db->getErrorMsg())
		{
			JError::raiseWarning(500, $error);

			return false;
		}

		// Construct html
		$text = array();

		foreach ($associations as $tag => $associated)
		{
			if ($associated != $memberid)
			{
				$text[] = JText::sprintf('COM_CHURCHDIRECTORY_TIP_ASSOCIATED_LANGUAGE', JHtml::_('image', 'mod_languages/'
					. $items[$associated]->image . '.gif', $items[$associated]->language_title, array('title' => $items[$associated]->language_title), true), $items[$associated]->name, $items[$associated]->category_title);
			}
		}

		return JHtml::_('tooltip', implode('<br />', $text), JText::_('COM_CHURCHDIRECTORY_TIP_ASSOCIATION'), 'admin/icon-16-links.png');
	}

	/**
	 * Fetured
	 *
	 * @param   int  $value      The featured value
	 * @param   int  $i          ?
	 * @param   bool $canChange  Whether the value can be changed or not
	 *
	 * @return  string    The anchor tag to toggle featured/unfeatured contacts.
	 *
	 * @since   1.6
	 */
	public static function featured($value = 0, $i, $canChange = true)
	{
		// Array of image, task, title, action
		$states = array(
			0 => array('disabled.png', 'members.featured', 'COM_CHURCHDIRECTORY_UNFEATURED', 'COM_CHURCHDIRECTORY_TOGGLE_TO_FEATURE'),
			1 => array('featured.png', 'members.unfeatured', 'JFEATURED', 'COM_CHURCHDIRECTORY_TOGGLE_TO_UNFEATURE'),
		);
		$state  = JArrayHelper::getValue($states, (int) $value, $states[1]);
		$html   = JHtml::_('image', 'admin/' . $state[0], JText::_($state[2]), null, true);

		if ($canChange)
		{
			$html = '<a href="#" onclick="return listItemTask(\'cb' . $i . '\',\'' . $state[1] . '\')" title="' . JText::_($state[3]) . '">'
				. $html . '</a>';
		}

		return $html;
	}

	/**
	 * Method to get the field options.
	 *
	 * @return   array    The field option objects.
	 *
	 * @since    1.6
	 */
	public static function status()
	{
		$options = array();

		$options[] = array('value' => 0, 'text' => JText::_('COM_CHURCHDIRECTORY_ACTIVE_MEMBER'));
		$options[] = array('value' => 1, 'text' => JText::_('COM_CHURCHDIRECTORY_INACTIVE'));
		$options[] = array('value' => 2, 'text' => JText::_('COM_CHURCHDIRECTORY_ACTIVE_ATTENDEE'));

		$object = new stdClass;

		foreach ($options as $key => $value)
		{
			$object->$key = $value;
		}

		return $object;
	}
}
