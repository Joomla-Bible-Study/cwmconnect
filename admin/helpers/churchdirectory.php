<?php
/**
 * ChurchDirectory Helper
 *
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

/**
 * Contact component helper.
 *
 * @package  ChurchDirectory.Admin
 * @since    1.7.0
 */
class ChurchDirectoryHelper
{
	/**
	 * Set Extension Name
	 *
	 * @var string
	 * @since    1.7.0
	 */
	public static $extension = 'com_churchdirectory';

	/**
	 * Configure the Linkbar.
	 *
	 * @param   string  $vName  The name of the active view.
	 *
	 * @return    void
	 *
	 * @since    1.7.0
	 */
	public static function addSubmenu($vName)
	{
		JHtmlSidebar::addEntry(
			JText::_('COM_CHURCHDIRECTORY_SUBMENU_CPANEL'),
			'index.php?option=com_churchdirectory&view=cpanel',
			$vName == 'cpanel'
		);
		JHtmlSidebar::addEntry(
			JText::_('COM_CHURCHDIRECTORY_SUBMENU_MEMBERS'),
			'index.php?option=com_churchdirectory&view=members',
			$vName == 'members'
		);
		JHtmlSidebar::addEntry(
			JText::_('COM_CHURCHDIRECTORY_SUBMENU_CATEGORIES'),
			'index.php?option=com_categories&extension=com_churchdirectory',
			$vName == 'categories'
		);
		JHtmlSidebar::addEntry(
			JText::_('COM_CHURCHDIRECTORY_SUBMENU_INFO'),
			'index.php?option=com_churchdirectory&view=info',
			$vName == 'info'
		);
		JHtmlSidebar::addEntry(
			JText::_('COM_CHURCHDIRECTORY_SUBMENU_KML'),
			'index.php?option=com_churchdirectory&task=kml.edit&id=1',
			$vName == 'kmls'
		);
		JHtmlSidebar::addEntry(
			JText::_('COM_CHURCHDIRECTORY_SUBMENU_FAMILYUNITS'),
			'index.php?option=com_churchdirectory&view=familyunits',
			$vName == 'familyunits'
		);
		JHtmlSidebar::addEntry(
			JText::_('COM_CHURCHDIRECTORY_SUBMENU_DIRHEADERS'),
			'index.php?option=com_churchdirectory&view=dirheaders',
			$vName == 'dirheaders'
		);
		JHtmlSidebar::addEntry(
			JText::_('COM_CHURCHDIRECTORY_SUBMENU_POSITIONS'),
			'index.php?option=com_churchdirectory&view=positions',
			$vName == 'positions'
		);
		JHtmlSidebar::addEntry(
			JText::_('COM_CHURCHDIRECTORY_SUBMENU_GEOSTATUS'),
			'index.php?option=com_churchdirectory&view=geostatus',
			$vName == 'geostatus'
		);
		JHtmlSidebar::addEntry(
			JText::_('COM_CHURCHDIRECTORY_SUBMENU_REPORTS'),
			'index.php?option=com_churchdirectory&view=reports',
			$vName == 'reports'
		);
		JHtmlSidebar::addEntry(
			JText::_('COM_CHURCHDIRECTORY_SUBMENU_DATABASE'),
			'index.php?option=com_churchdirectory&view=database',
			$vName == 'database'
		);

		if ($vName == 'categories')
		{
			JToolbarHelper::title(
				JText::sprintf('COM_CATEGORIES_CATEGORIES_TITLE', JText::_('com_churchdirectory')),
				'churchdirectory-categories');
		}
	}

	/**
	 * Gets a list of the actions that can be performed.
	 *
	 * @param   string   $component  The component name.
	 * @param   string   $section    The access section name.
	 * @param   integer  $id         The item ID.
	 *
	 * @return    JObject
	 *
	 * @since    1.7.0
	 */
	public static function getActions($component = '', $section = '', $id = 0)
	{
		$user   = JFactory::getUser();
		$result = new JObject;

		if (empty($component))
		{
			$component = self::$extension;
		}

		$path = JPATH_ADMINISTRATOR . '/components/' . $component . '/access.xml';

		if ($section && $id)
		{
			$assetName = $component . '.' . $section . '.' . (int) $id;
		}
		else
		{
			$assetName = $component;
		}

		$actions = JAccess::getActionsFromFile($path, "/access/section[@name='component']/");

		foreach ($actions as $action)
		{
			$result->set($action->name, $user->authorise($action->name, $assetName));
		}

		return $result;
	}

	/**
	 * Get Age of Member
	 *
	 * @param   string  $birthDate  Set Up Birth Date Calculation
	 *
	 * @return  string
	 *
	 * @since    1.7.3
	 */
	public static function getAge($birthDate)
	{
		if (!empty($birthDate))
		{
			$date     = new DateTime($birthDate);
			$now      = new DateTime;
			$interval = $now->diff($date);

			if ($interval->y !== intval(date('Y')))
			{
				return $interval->y;
			}
		}

		return '0';
	}

	/**
	 * Associations to the Member Record
	 *
	 * @param   int  $pk  ID
	 *
	 * @return array|bool
	 *
	 * @since    1.7.0
	 */
	public static function getAssociations($pk)
	{
		$associations = [];
		$db           = JFactory::getDbo();
		$query        = $db->getQuery(true);
		$query->from('#__churchdirectory_details as c');
		$query->innerJoin('#__associations as a ON a.id = c.id AND a.context=' . $db->quote('com_churchdirectory.item'));
		$query->innerJoin('#__associations as a2 ON a.key = a2.key');
		$query->innerJoin('#__contact_details as c2 ON a2.id = c2.id');
		$query->innerJoin('#__categories as ca ON c2.catid = ca.id AND ca.extension = ' . $db->quote('com_churchdirectory'));
		$query->where('c.id =' . (int) $pk);
		$select = [
			'c2.language',
			$query->concatenate(['c2.id', 'c2.alias'], ':') . ' AS id',
			$query->concatenate(['ca.id', 'ca.alias'], ':') . ' AS catid'
		];
		$query->select($select);
		$db->setQuery($query);
		$memberitems = $db->loadObjectList('language');

		// Check for a database error.
		if ($error = $db->getErrorMsg())
		{
			JError::raiseWarning(500, $error);

			return false;
		}

		foreach ($memberitems as $tag => $item)
		{
			$associations[$tag] = $item;
		}

		return $associations;
	}

	/**
	 * Adds Count Items for Category Manager.
	 *
	 * @param   stdClass[]  &$items  The contact category objects
	 *
	 * @return  stdClass[]
	 *
	 * @since   3.5
	 */
	public static function countItems(&$items)
	{
		$db = JFactory::getDbo();

		foreach ($items as $item)
		{
			$item->count_trashed     = 0;
			$item->count_archived    = 0;
			$item->count_unpublished = 0;
			$item->count_published   = 0;
			$query                   = $db->getQuery(true);
			$query->select('published AS state, count(*) AS count')
				->from($db->qn('#__churchdirectory_details'))
				->where('catid = ' . (int) $item->id)
				->group('published');
			$db->setQuery($query);
			$contacts = $db->loadObjectList();

			foreach ($contacts as $contact)
			{
				if ($contact->state == 1)
				{
					$item->count_published = $contact->count;
				}

				if ($contact->state == 0)
				{
					$item->count_unpublished = $contact->count;
				}

				if ($contact->state == 2)
				{
					$item->count_archived = $contact->count;
				}

				if ($contact->state == -2)
				{
					$item->count_trashed = $contact->count;
				}
			}
		}

		return $items;
	}
}
