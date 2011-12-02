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

jimport('joomla.application.component.model');

class QContactsModelCategory extends JModel {
	
	function _getCatgoriesQuery( &$options ) {
		$db	=& JFactory::getDBO();
		$aid = @$options['aid'];

		$wheres[] = 'a.published = 1';
		$wheres[] = 'cc.section = ' . $db->Quote( 'com_qcontacts_details' );
		$wheres[] = 'cc.published = 1';

		if ($aid !== null) {
			$wheres[] = 'a.access <= ' . (int) $aid;
			$wheres[] = 'cc.access <= ' . (int) $aid;
		}

		$groupBy = 'cc.id';
		$orderBy = 'cc.ordering' ;

		$query = 'SELECT cc.*, COUNT( a.id ) AS numlinks, a.id as cid'.
				' FROM #__categories AS cc'.
				' LEFT JOIN #__qcontacts_details AS a ON a.catid = cc.id'.
				' WHERE ' . implode( ' AND ', $wheres ) .
				' GROUP BY ' . $groupBy .
				' ORDER BY ' . $orderBy;

		return $query;
	}
	function _getCategoryQuery(&$options){
		$aid = @$options['aid'];
		$catid = @$options['category_id'];
		$wheres[] = 'published = 1';
		$wheres[] = "section = 'com_qcontacts_details'";
		$wheres[] = "id = " . (int) $catid;
		if ($aid !== null) {
			$wheres[] = 'access <= ' . (int) $aid;
		}
		$query = 'SELECT * FROM #__categories' .
				' WHERE '. implode( ' AND ', $wheres );
		return $query;
	}
	
	function _getContactsQuery( &$options )	{
		$db			=& JFactory::getDBO();
		$aid		= @$options['aid'];
		$catId		= @$options['category_id'];
		$groupBy	= @$options['group by'];
		$orderBy	= @$options['order by'];

		$select = 'cd.*, ' .
				'cc.title AS category_name, cc.description AS category_description, cc.image AS category_image,'.
				' CASE WHEN CHAR_LENGTH(cd.alias) THEN CONCAT_WS(\':\', cd.id, cd.alias) ELSE cd.id END as slug, '.
				' CASE WHEN CHAR_LENGTH(cc.alias) THEN CONCAT_WS(\':\', cc.id, cc.alias) ELSE cc.id END as catslug ';
		$from	= '#__qcontacts_details AS cd';

		$joins[] = 'INNER JOIN #__categories AS cc on cd.catid = cc.id';

		if ($catId) {
			$wheres[] = 'cd.catid = ' . (int) $catId;
		}
		$wheres[] = 'cc.published = 1';
		$wheres[] = 'cd.published = 1';

		if ($aid !== null) {
			$wheres[] = 'cc.access <= ' . (int) $aid;
			$wheres[] = 'cd.access <= ' . (int) $aid;
		}

		$query = 'SELECT ' . $select .
				' FROM ' . $from .
				' ' . implode ( ' ', $joins ) .
				' WHERE ' . implode( ' AND ', $wheres ) .
				($groupBy ? ' GROUP BY ' . $groupBy : '').
				($orderBy ? ' ORDER BY ' . $orderBy : '');

		return $query;
	}
	function getCategory($options=array()){
		$query	= $this->_getCategoryQuery( $options );
		$result = $this->_getList( $query );
		return @$result[0];
	}
	function getCategories( $options=array() ) {
		$query	= $this->_getCatgoriesQuery( $options );
		return $this->_getList( $query, @$options['limitstart'], @$options['limit'] );
	}

	function getCategoryCount( $options=array() )	{
		$query	= $this->_getCatgoriesQuery( $options );
		return $this->_getListCount( $query );
	}

	function getContacts( $options=array() )	{
		$query	= $this->_getContactsQuery( $options );
		return $this->_getList( $query, @$options['limitstart'], @$options['limit'] );
	}

	function getContactCount( $options=array() ) {
		$query	= $this->_getContactsQuery( $options );
		return $this->_getListCount( $query );
	}
}