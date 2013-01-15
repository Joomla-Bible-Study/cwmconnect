<?php

/**
 * ChurchDirectory Helper
 *
 * @package    ChurchDirectory.Admin
 * @copyright  (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
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
		self::rendermenu(
			JText::_('COM_CHURCHDIRECTORY_SUBMENU_CPANEL'),
			'index.php?option=com_churchdirectory&view=cpanel',
			$vName == 'cpanel'
		);
		self::rendermenu(
			JText::_('COM_CHURCHDIRECTORY_SUBMENU_MEMBERS'),
			'index.php?option=com_churchdirectory&view=members',
			$vName == 'members'
		);
		self::rendermenu(
			JText::_('COM_CHURCHDIRECTORY_SUBMENU_CATEGORIES'),
			'index.php?option=com_categories&extension=com_churchdirectory',
			$vName == 'categories'
		);
		self::rendermenu(
			JText::_('COM_CHURCHDIRECTORY_SUBMENU_INFO'),
			'index.php?option=com_churchdirectory&view=info',
			$vName == 'info'
		);
		self::rendermenu(
			JText::_('COM_CHURCHDIRECTORY_SUBMENU_KML'),
			'index.php?option=com_churchdirectory&task=kml.edit&id=1',
			$vName == 'kmls'
		);
		self::rendermenu(
			JText::_('COM_CHURCHDIRECTORY_SUBMENU_FAMILYUNITS'),
			'index.php?option=com_churchdirectory&view=familyunits',
			$vName == 'familyunits'
		);
		self::rendermenu(
			JText::_('COM_CHURCHDIRECTORY_SUBMENU_DIRHEADERS'),
			'index.php?option=com_churchdirectory&view=dirheaders',
			$vName == 'dirheaders'
		);
		self::rendermenu(
			JText::_('COM_CHURCHDIRECTORY_SUBMENU_POSITIONS'),
			'index.php?option=com_churchdirectory&view=positions',
			$vName == 'positions'
		);
		self::rendermenu(
			JText::_('COM_CHURCHDIRECTORY_SUBMENU_GEOSTATUS'),
			'index.php?option=com_churchdirectory&view=geostatus',
			$vName == 'geostatus'
		);
		self::rendermenu(
			JText::_('COM_CHURCHDIRECTORY_SUBMENU_DATABASE'),
			'index.php?option=com_churchdirectory&view=database',
			$vName == 'database'
		);

		if ($vName == 'categories')
		{
			JToolBarHelper::title(
				JText::sprintf('COM_CATEGORIES_CATEGORIES_TITLE', JText::_('com_churchdirectory')),
				'churchdirectory-categories');
		}
	}

	/**
	 *  Rendering Menu based on Joomla! Version.
	 *
	 * @param   string  $text   Title
	 * @param   string  $url    URL
	 * @param   string  $vName  The name of the active view.
	 *
	 * @return void
	 */
	public static function rendermenu($text, $url, $vName)
	{
		if (version_compare(JVERSION, '3.0', 'ge'))
		{
			JHtmlSidebar::addEntry($text, $url, $vName);
		}
		else
		{
			JSubMenuHelper::addEntry($text, $url, $vName);
		}
	}

	/**
	 * Gets a list of the actions that can be performed.
	 *
	 * @param   int  $categoryId  The category ID.
	 * @param   int  $contactId   The contact ID.
	 *
	 * @return    JObject
	 *
	 * @since    1.7.0
	 */
	public static function getActions($categoryId = 0, $contactId = 0)
	{
		$user   = JFactory::getUser();
		$result = new JObject;

		if (empty($contactId) && empty($categoryId))
		{
			$assetName = 'com_churchdirectory';
			$level     = 'component';
		}
		elseif (empty($contactId))
		{
			$assetName = 'com_churchdirectory.category.' . (int) $categoryId;
			$level     = 'category';
		}
		else
		{
			$assetName = 'com_churchdirectory.members.' . (int) $contactId;
			$level     = 'category';
		}

		$actions = JAccess::getActions('com_churchdirectory', $level);

		foreach ($actions as $action)
		{
			$result->set($action->name, $user->authorise($action->name, $assetName));
		}

		return $result;
	}

	/**
	 * Get Age of Member
	 *
	 * @param   string  $birthdate  Set Up Birth Date Calculation
	 *
	 * @return string
	 *
	 * @internal requires php5.3.x
	 * @since    1.7.3
	 */
	public static function getAge($birthdate)
	{
		if (!empty($birthdate))
		{
			$date     = new DateTime($birthdate);
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
	 * Ordering presentation for list display
	 *
	 * @param   string  $canChange   True if can false if cannot change
	 * @param   string  $saveOrder   True if can false if cannot order
	 * @param   object  $item        Item information
	 * @param   object  $items       Array of item information
	 * @param   string  $ordering    Number of position in ordering
	 * @param   int     $i           Number that record on
	 * @param   int     $n           Number of records.
	 * @param   string  $controller  Controller name
	 * @param   string  $pagination  Paginatioin system
	 * @param   string  $listDirn    asc or desc for direction of list
	 *
	 * @return null|string
	 */
	public static function ordering($canChange, $saveOrder, $item, $items, $ordering, $i, $n, $controller, $pagination, $listDirn)
	{
		$html = null;
		if (version_compare(JVERSION, '3.0', 'ge'))
		{
			if ($canChange)
			{
				$disableClassName = '';
				$disabledLabel    = '';

				if (!$saveOrder)
				{
					$disabledLabel    = JText::_('JORDERINGDISABLED');
					$disableClassName = 'inactive tip-top';
				}
				$html = ' <span class="sortable-handler hasTooltip' . $disableClassName . '" title="' . $disabledLabel . '">
								<i class="icon-menu"></i>
							</span>
            <input type="text" style="display:none" name="order[]" size="5"
                   value="' . $item->ordering . '" class="width-20 text-area-order "/>';
			}
			else
			{
				$html = '<span class="sortable-handler inactive">
								<i class="icon-menu"></i>
							</span>';
			}
		}
		else
		{
			if ($canChange)
			{
				if ($saveOrder)
				{
					if ($listDirn == 'asc')
					{
						$html = '<span>' . $pagination->orderUpIcon($i, ($item->catid == @$items[$i - 1]->catid), $controller . '.orderup', 'JLIB_HTML_MOVE_UP', $ordering) . '</span>';
						$html .= '<span>' . $pagination->orderDownIcon($i, $n, ($item->catid == @$items[$i + 1]->catid), $controller . '.orderdown', 'JLIB_HTML_MOVE_DOWN', $ordering) . '</span>';
					}
					elseif ($listDirn == 'desc')
					{
						$html = '<span>';
						$html .= $pagination->orderUpIcon($i, ($item->catid == @$items[$i - 1]->catid), $controller . '.orderdown', 'JLIB_HTML_MOVE_UP', $ordering);
						$html .= '</span>';
						$html .= '<span>';
						$html .= $pagination->orderDownIcon(
							$i, $n, ($item->catid == @$items[$i + 1]->catid), $controller .
							'.orderup', 'JLIB_HTML_MOVE_DOWN', $ordering
						);
						$html .= '</span>';
					}
				}
				$disabled = $saveOrder ? '' : 'disabled="disabled"';
				$html .= '<input type="text" name="order[]" size="5" value="' . $item->ordering . '"' . $disabled . ' class="text-area-order"/>';
			}
			else
			{
				$html .= $item->ordering;
			}
		}

		return $html;
	}

}
