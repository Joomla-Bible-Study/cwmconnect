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

class QContactsModelSection extends JModel {
	var $_categories = null;
	
	function getCategories() {
		$this->_loadCategories();
		return $this->_categories;
	}
	function _loadCategories() {
		global $mainframe;
		if (empty($this->_categories)) {
			$user =& JFactory::getUser();
			$params = &$mainframe->getParams();
			$gid = $user->get('aid', 0);
			
			$orderby = $params->get('orderby', '');
			switch($orderby) {
				case 'alpha':
					$orderby = 'a.title';
					break;
				case 'ralpha':
					$orderby = 'a.title DESC';
					break;
				default:
					$orderby = 'a.ordering';
			}
			
			$exclude = explode(',',$params->get('exclude_categories', ''));
			$cids = null;
			if(count($exclude)) {
				
				foreach($exclude as $cid) {
					if($cid = (int)trim($cid)) {
						$cids[] = $cid;
					}
				}
				if(isset($cids)) {
					
				}
			}
			$xwhere2 = ' AND b.published = 1 AND b.access <= '.(int) $gid;
			$empty = '';
			if (!$params->get('show_empty_categories')) {
				$empty = ' HAVING numitems > 0';
			}
			
			$query = 'SELECT a.*, COUNT( b.id ) AS numitems,' .
				' CASE WHEN CHAR_LENGTH(a.alias) THEN CONCAT_WS(\':\', a.id, a.alias) ELSE a.id END as slug'.
				' FROM #__categories AS a' .
				' LEFT JOIN #__qcontacts_details AS b ON b.catid = a.id'.
				$xwhere2 .
				" WHERE a.section = 'com_qcontacts_details'".
				' AND a.published = 1'.
				' AND a.access <= '.(int) $gid.
				(isset($cids)? ' AND a.id NOT IN ('.implode(',',$cids).')' : '').
				' GROUP BY a.id'.$empty.
				' ORDER BY '. $orderby;
				$this->_db->setQuery($query);
				$this->_categories = $this->_db->loadObjectList();
		}
		return true;
	}
}
?>