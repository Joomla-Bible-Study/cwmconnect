<?php
/**
 * QContacts Contact manager component for Joomla! 1.5
 *
 * @version 1.0.6
 * @package qcontacts
 * @author Massimo Giagnoni
 * @copyright Copyright (C) 2008 Massimo Giagnoni. All rights reserved.
 * @copyright Copyright (C) 2005 - 2008 Open Source Matters. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */
 /*
This file is part of QContacts.
QContacts is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.application.component.view');

class QContactsViewCategory extends JView {
	function display()
	{
		global $mainframe;

		$db			=& JFactory::getDBO();
		$document	=& JFactory::getDocument();
		$document->link = JRoute::_('index.php?option=com_qcontacts&view=category&catid='.JRequest::getVar('catid',null, '', 'int'));

		$limit 		= JRequest::getVar('limit', $mainframe->getCfg('feed_limit'), '', 'int');
		$limitstart = JRequest::getVar('limitstart', 0, '', 'int');
		$catid  	= JRequest::getVar('catid', 0, '', 'int');

		$where		= ' WHERE a.published = 1';

		if ( $catid ) {
			$where .= ' AND a.catid = '. (int) $catid;
		}

		$query = 'SELECT'
		. ' a.name AS title,'
		. ' CONCAT( a.con_position, \' - \', a.misc ) AS description,'
		. ' "" AS date,'
		. ' c.title AS category,'
		. ' CASE WHEN CHAR_LENGTH(a.alias) THEN CONCAT_WS(":", a.id, a.alias) ELSE a.id END as slug,'
		. ' CASE WHEN CHAR_LENGTH(c.alias) THEN CONCAT_WS(":", c.id, c.alias) ELSE c.id END as catslug'
		. ' FROM #__qcontacts_details AS a'
		. ' LEFT JOIN #__categories AS c ON c.id = a.catid'
		. $where
		. ' ORDER BY a.catid, a.ordering'
		;
		$db->setQuery( $query, 0, $limit );
		$rows = $db->loadObjectList();

		foreach ( $rows as $row )
		{
			// strip html from feed item title
			$title = $this->escape( $row->title );
			$title = html_entity_decode( $title );

			// url link to article
			$link = JRoute::_('index.php?option=com_qcontacts&view=contact&id='. $row->slug .'&catid='.$row->catslug );

			// strip html from feed item description text
			$description = $row->description;
			$date = ( $row->date ? date( 'r', strtotime($row->date) ) : '' );

			// load individual item creator class
			$item = new JFeedItem();
			$item->title 		= $title;
			$item->link 		= $link;
			$item->description 	= $description;
			$item->date			= $date;
			$item->category   	= $row->category;

			// loads item info into rss array
			$document->addItem( $item );
		}
	}
}