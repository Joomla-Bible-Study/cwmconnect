<?php

/**
 * @version		$Id: view.feed.php 71 $
 * @package		com_churchdirectory
 * @copyright           (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.view');

/**
 * HTML View class for the ChurchDirectory component
 *
 * @package	com_churchdirectory
 * @since       1.7.0
 */
class ChurchDirectoryViewCategory extends JView {

    function display() {
        // Get some data from the models
        $category = $this->get('Category');
        $rows = $this->get('Items');

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            JError::raiseError(500, implode("\n", $errors));
            return false;
        }

        $app = JFactory::getApplication();

        $doc = JFactory::getDocument();
        $params = $app->getParams();

        $doc->link = JRoute::_(ChurchDirectoryHelperRoute::getCategoryRoute($category->id));

        foreach ($rows as $row) {
            // strip html from feed item title
            $title = $this->escape($row->name);
            $title = html_entity_decode($title, ENT_COMPAT, 'UTF-8');

            // Compute the churchdirectory slug
            $row->slug = $row->alias ? ($row->id . ':' . $row->alias) : $row->id;

            // url link to article
            $link = JRoute::_(ChurchDirectoryHelperRoute::getChurchDirectoryRoute($row->slug, $row->catid));

            $description = $row->introtext;
            $author = $row->created_by_alias ? $row->created_by_alias : $row->author;
            @$date = ($row->created ? date('r', strtotime($row->created)) : '');

            // load individual item creator class
            $item = new JFeedItem();
            $item->title = $title;
            $item->link = $link;
            $item->description = $description;
            $item->date = $date;
            $item->category = $row->category;

            // loads item info into rss array
            $doc->addItem($item);
        }
    }

}
