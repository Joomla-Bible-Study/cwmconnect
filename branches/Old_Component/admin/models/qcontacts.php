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

class QContactsModelQContacts extends JModel {
	var $_data = null;
	var $_total = null;
	var $_pagination = null;
	
	function __construct()	{
			parent::__construct();
			
			global $mainframe, $option;
			
			$limit	= $mainframe->getUserStateFromRequest('global.list.limit', 'limit', $mainframe->getCfg('list_limit'), 'int');
			$limitstart = $mainframe->getUserStateFromRequest($option.'.limitstart', 'limitstart', 0, 'int');
			
			$this->setState('limit', $limit);
			$this->setState('limitstart', $limitstart);
	}
	
	function &getData() {
		if(empty($this->_data)) {
			$query = $this->_buildQuery();
			$this->_data = $this->_getList($query, $this->getState('limitstart'), $this->getState('limit'));
		}

		return $this->_data;
	}
	
	function getTotal() {
		if(empty($this->_total)) {
			$query = $this->_buildQuery();
			$this->_total = $this->_getListCount($query);
		}

		return $this->_total;
	}
	
	function &getPagination() {
		if(empty($this->_pagination)) {
			jimport('joomla.html.pagination');
			$this->_pagination = new JPagination( $this->getTotal(), $this->getState('limitstart'), $this->getState('limit') );
		}

		return $this->_pagination;
	}
	
	function _buildQuery() {
		$where = $this->_buildContentWhere();
		$orderby = $this->_buildContentOrderBy();

		$query = 'SELECT cd.*, cc.title AS category, u.name AS user, v.name as editor, g.name AS groupname'
		. ' FROM #__qcontacts_details AS cd'
		. ' LEFT JOIN #__groups AS g ON g.id = cd.access'
		. ' LEFT JOIN #__categories AS cc ON cc.id = cd.catid'
		. ' LEFT JOIN #__users AS u ON u.id = cd.user_id'
		. ' LEFT JOIN #__users AS v ON v.id = cd.checked_out'
		. $where
		. $orderby
		;

		return $query;
	}
	
	function _buildContentOrderBy() {
		global $mainframe, $option;
		
		$filter_order = $mainframe->getUserStateFromRequest($option.'filter_order', 'filter_order', 'cd.ordering', 'cmd');
		$filter_order_Dir = $mainframe->getUserStateFromRequest($option.'filter_order_Dir', 'filter_order_Dir', '', 'word');
		
		if($filter_order == 'cd.ordering'){
			$orderby = ' ORDER BY category, cd.ordering';
		} else {
			$orderby = ' ORDER BY '. $filter_order .' '. $filter_order_Dir .', category, cd.ordering';
		}
		return $orderby;
	}
	
	function _buildContentWhere() {
		global $mainframe, $option;
		
		$filter_state = $mainframe->getUserStateFromRequest($option.'filter_state', 'filter_state', '', 'word');
		$filter_catid = $mainframe->getUserStateFromRequest($option.'filter_catid', 'filter_catid', 0, 'int');
		$search = $mainframe->getUserStateFromRequest($option.'search', 'search', '', 'string');
		$search = JString::strtolower($search);
		
		$where = array();

		if($search) {
			$where[] = 'cd.name LIKE '.$this->_db->Quote( '%'.$this->_db->getEscaped( $search, true ).'%', false );
		}
		
		if($filter_catid) {
			$where[] = 'cd.catid = '.(int) $filter_catid;
		}
		
		if($filter_state) {
			if ($filter_state == 'P') {
				$where[] = 'cd.published = 1';
			} else if ($filter_state == 'U') {
				$where[] = 'cd.published = 0';
			}
		}

		$where = (count($where) ? ' WHERE ' . implode(' AND ', $where ) : '');
		return $where;
	}
}
?>