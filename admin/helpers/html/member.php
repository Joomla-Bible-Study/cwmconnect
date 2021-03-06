<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

JLoader::register('ChurchDirectoryHelper', JPATH_ADMINISTRATOR . '/components/com_churchdirectory/helpers/helper.php');

/**
 * Abstract class for JhtmlMember
 *
 * @package  ChurchDirecotry.Admin
 * @since    1.7.5
 */
abstract class JHtmlMember
{
	/**
	 * Association of members
	 *
	 * @param   int  $memberid  The Member item id
	 *
	 * @since 1.7.5
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
		$text = [];

		foreach ($associations as $tag => $associated)
		{
			if ($associated != $memberid)
			{
				$text[] = JText::sprintf('COM_CHURCHDIRECTORY_TIP_ASSOCIATED_LANGUAGE', JHtml::_('image', 'mod_languages/' . $items[$associated]->image . '.gif',
						$items[$associated]->language_title, ['title' => $items[$associated]->language_title], true
				), $items[$associated]->name, $items[$associated]->category_title
				);
			}
		}

		return JHtml::_('tooltip', implode('<br />', $text), JText::_('COM_CHURCHDIRECTORY_TIP_ASSOCIATION'), 'admin/icon-16-links.png');
	}

	/**
	 * Fetured
	 *
	 * @param   int   $value      The featured value
	 * @param   int   $i          ?
	 * @param   bool  $canChange  Whether the value can be changed or not
	 *
	 * @return  string    The anchor tag to toggle featured/unfeatured contacts.
	 *
	 * @since   1.6
	 */
	public static function featured($value = 0, $i = 0, $canChange = true)
	{
		// Array of image, task, title, action
		$states = [
			0 => ['unfeatured', 'members.featured', 'COM_CHURCHDIRECTORY_UNFEATURED', 'COM_CHURCHDIRECTORY_TOGGLE_TO_FEATURE'],
			1 => ['featured', 'members.unfeatured', 'JFEATURED', 'COM_CHURCHDIRECTORY_TOGGLE_TO_UNFEATURE'],
		];
		$state  = Joomla\Utilities\ArrayHelper::getValue($states, (int) $value, $states[1]);
		$icon  = $state[0];

		if ($canChange)
		{
			$html = '<a href="#" onclick="return listItemTask(\'cb' . $i . '\',\'' . $state[1] . '\')" class="btn btn-micro hasTooltip'
				. ($value == 1 ? ' active' : '') . '" title="' . JHtml::_('tooltipText', $state[3]) . '"><span class="icon-' . $icon . '"></span></a>';
		}
		else
		{
			$html = '<a class="btn btn-micro hasTooltip disabled' . ($value == 1 ? ' active' : '') . '" title="' . JHtml::_('tooltipText', $state[2])
				. '"><span class="icon-' . $icon . '"></span></a>';
		}

		return $html;
	}

	/**
	 * Method to get the field options.
	 *
	 * @return   object    The field option objects.
	 *
	 * @since    1.6
	 */
	public static function status()
	{
		$options = [];

		$options[] = ['value' => 0, 'text' => JText::_('COM_CHURCHDIRECTORY_ACTIVE_MEMBER')];
		$options[] = ['value' => 1, 'text' => JText::_('COM_CHURCHDIRECTORY_INACTIVE')];
		$options[] = ['value' => 2, 'text' => JText::_('COM_CHURCHDIRECTORY_ACTIVE_ATTENDEE')];
		$options[] = ['value' => 3, 'text' => JText::_('COM_CHURCHDIRECTORY_NONE_MEMBER')];

		$object = new stdClass;

		foreach ($options as $key => $value)
		{
			$object->$key = $value;
		}

		return $object;
	}
}
