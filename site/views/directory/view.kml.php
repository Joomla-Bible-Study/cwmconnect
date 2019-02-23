<?php
/**
 * @package    ChurchDirectory.Site
 * @copyright  Copyright (C) 2005 - 2016 Joomla Bible Study, All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\Registry\Registry;

/**
 * HTML Contact View class for the Contact component
 *
 * @package  ChurchDirectory.Site
 * @since    1.7.0
 */
class ChurchDirectoryViewDirectory extends JViewLegacy
{
	/**
	 * Protected
	 *
	 * @var array
	 * @since       1.7.2
	 */
	protected $state;

	/**
	 * Protected
	 *
	 * @var array
	 * @since       1.7.2
	 */
	protected $items;

	/**
	 * Protected
	 *
	 * @var array
	 * @since       1.7.2
	 */
	protected $params;

	/**
	 * Protected
	 *
	 * @var array
	 * @since       1.7.2
	 */
	protected $kml_params;

	/**
	 * Protected
	 *
	 * @var array
	 * @since       1.7.2
	 */
	protected $category_params;

	/**
	 * Protected
	 *
	 * @var array
	 * @since       1.7.2
	 */
	protected $category;

	/**
	 * Protected
	 *
	 * @var array
	 * @since       1.7.2
	 */
	protected $children;

	/**
	 * Protected
	 *
	 * @var array
	 * @since       1.7.2
	 */
	protected $pagination;

	protected $maxLevel;

	protected $parent;

	/**
	 * Display function
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a Error object.
	 *
	 * @since       1.7.2
	 * @throws      Exception
	 */
	public function display ($tpl = null)
	{
		$reportbuild  = new ChurchDirectoryReportBuild;

		// Get some data from the models
		$state      = $this->get('State');
		/** @var Registry $params */
		$params     = $state->params;
		$items      = $this->get('Items');
		$category   = $this->get('Category');

		// Check whether category access level allows access.
		$user   = JFactory::getUser();
		$groups = $user->getAuthorisedViewLevels();

		if (!in_array($category->access, $groups))
		{
			echo JText::_('JERROR_ALERTNOAUTHOR');

			return false;
		}

		if ($items == false || empty($items))
		{
			echo JText::_('COM_CHURCHDIRECTOY_ERROR_DIRECTORY_NOT_FOUND');

			return false;
		}

		// Prepare the data.
		for ($i = 0, $n = count($items); $i < $n; $i++)
		{
			$item = &$items[$i];

			// Compute the contact slug.
			$item->slug = $item->alias ? ($item->id . ':' . $item->alias) : $item->id;

			$item->event = new stdClass;
			$temp        = new Registry;
			$temp->loadString($item->params);
			$item->params = clone $params;
			$item->params->merge($temp);

			// Build Cat params
			$reg = new Joomla\Registry\Registry;
			$reg->loadString($item->category_params);
			$item->category_params = $reg;

			if ($item->params->get('dr_show_email', 0) == 1)
			{
				$item->email_to = trim($item->email_to);

				if (empty($item->email_to) && !JMailHelper::isEmailAddress($item->email_to))
				{
					$item->email_to = null;
				}
			}
		}

		$reportbuild->getKML($items);

		return true;
	}
}
