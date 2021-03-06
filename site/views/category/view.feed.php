<?php
/**
 * @package    ChurchDirectory.Site
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * HTML View class for the ChurchDirectory component
 *
 * @package  ChurchDirectory.Site
 * @since    1.7.0
 */
class ChurchDirectoryViewCategory extends JViewLegacy
{
	/**
	 * Display Function
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a Error object.
	 *
	 * @since       1.7.2
	 */
	public function display($tpl = null)
	{
		/** @var JApplicationSite $app */
		$app = JFactory::getApplication();

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			$app->enqueueMessage(implode("\n", $errors), 'error');

			return false;
		}

		$doc       = JFactory::getDocument();
		$params    = $app->getParams();
		$feedEmail = $app->get('feed_email', 'author');
		$siteEmail = $app->get('mailfrom');
		$fromName  = $app->get('fromname');

		$app->input->set('limit', $app->get('feed_limit'));

		// Get some data from the models
		$category = $this->get('Category');
		$rows     = $this->get('Items');

		$doc->link = JRoute::_(ChurchDirectoryHelperRoute::getCategoryRoute($category->id));

		foreach ($rows as $row)
		{
			// Strip html from feed item title
			$title = $this->escape($row->name);
			$title = html_entity_decode($title, ENT_COMPAT, 'UTF-8');

			// Compute the churchdirectory slug
			$row->slug = $row->alias ? ($row->id . ':' . $row->alias) : $row->id;

			// Url link to article
			$link = JRoute::_(ChurchDirectoryHelperRoute::getMemberRoute($row->slug, $row->catid));

			$description = $row->introtext;
			$author      = $row->created_by_alias ? $row->created_by_alias : $row->author;
			@$date = ($row->created ? date('r', strtotime($row->created)) : '');

			// Load individual item creator class
			$item              = new JFeedItem;
			$item->title       = $title;
			$item->link        = $link;
			$item->description = $description;
			$item->date        = $date;
			$item->category    = $category->title;
			$item->author      = $author;

			// We don't have the author email so we have to use site in both cases.
			if ($feedEmail == 'site')
			{
				$item->authorEmail = $siteEmail;
			}
			elseif ($feedEmail == 'author')
			{
				$item->authorEmail = $row->author_email;
			}

			// Loads item info into rss array
			$doc->addItem($item);
		}

		return true;
	}
}
