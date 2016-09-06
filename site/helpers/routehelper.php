<?php
/**
 * @package    ChurchDirectory.Site
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * ChurchDirectory Component Route Helper
 *
 * @package  ChurchDirectory.Site
 * @since    1.5
 */
abstract class ChurchDirectoryHelperRoute
{
	/**
	 * Protect lookup
	 *
	 * @var string protect lookup
	 * @since    1.5
	 */
	protected static $lookup;

	/**
	 * Get member route
	 *
	 * @param   int  $id     The route of the Member
	 * @param   int  $catid  Category id
	 *
	 * @return string
	 *
	 * @since    1.5
	 */
	public static function getMemberRoute($id, $catid)
	{
		$needles = [
			'churchdirectory' => [(int) $id]
		];

		// Create the link
		$link = 'index.php?option=com_churchdirectory&view=member&id=' . $id;

		if ($catid > 1)
		{
			$categories = JCategories::getInstance('ChurchDirectory');
			$category   = $categories->get($catid);

			if ($category)
			{
				$needles['category']   = array_reverse($category->getPath());
				$needles['categories'] = $needles['category'];
				$link .= '&catid=' . $catid;
			}
		}

		if ($item = self::_findItem($needles))
		{
			$link .= '&Itemid=' . $item;
		}
		elseif ($item = self::_findItem())
		{
			$link .= '&Itemid=' . $item;
		}

		return $link;
	}

	/**
	 * Get Category Route
	 *
	 * @param   JCategoryNode  $catid  Category ID
	 *
	 * @return string
	 *
	 * @since    1.5
	 */
	public static function getCategoryRoute($catid)
	{
		if ($catid instanceof JCategoryNode)
		{
			$id       = $catid->id;
			$category = $catid;
		}
		else
		{
			$id       = (int) $catid;
			$category = JCategories::getInstance('ChurchDirectory')->get($id);
		}

		if ($id < 1)
		{
			$link = '';
		}
		else
		{
			$needles = [
				'category' => [$id]
			];

			if ($item = self::_findItem($needles))
			{
				$link = 'index.php?Itemid=' . $item;
			}
			else
			{
				// Create the link
				$link = 'index.php?option=com_churchdirectory&view=category&id=' . $id;

				if ($category)
				{
					$catids  = array_reverse($category->getPath());
					$needles = [
						'category'   => $catids,
						'categories' => $catids
					];

					if ($item = self::_findItem($needles))
					{
						$link .= '&Itemid=' . $item;
					}
					elseif ($item = self::_findItem())
					{
						$link .= '&Itemid=' . $item;
					}
				}
			}
		}

		return $link;
	}

	/**
	 * Find Item
	 *
	 * @param   array  $needles  Not sure what this is
	 *
	 * @return mixed
	 *
	 * @since    1.5
	 */
	protected static function _findItem($needles = null)
	{
		$app   = JFactory::getApplication();
		$menus = $app->getMenu('site');

		// Prepare the reverse lookup array.
		if (self::$lookup === null)
		{
			self::$lookup = [];

			$component = JComponentHelper::getComponent('com_churchdirectory');
			$items     = $menus->getItems('component_id', $component->id);

			foreach ($items as $item)
			{
				if (isset($item->query) && isset($item->query['view']))
				{
					$view = $item->query['view'];

					if (!isset(self::$lookup[$view]))
					{
						self::$lookup[$view] = [];
					}

					if (isset($item->query['id']))
					{
						self::$lookup[$view][$item->query['id']] = $item->id;
					}
				}
			}
		}

		if ($needles)
		{
			foreach ($needles as $view => $ids)
			{
				if (isset(self::$lookup[$view]))
				{
					foreach ($ids as $id)
					{
						if (isset(self::$lookup[$view][(int) $id]))
						{
							return self::$lookup[$view][(int) $id];
						}
					}
				}
			}
		}
		else
		{
			$active = $menus->getActive();

			if ($active)
			{
				return $active->id;
			}
		}

		return null;
	}
}
