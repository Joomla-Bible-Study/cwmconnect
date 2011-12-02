<?php
/**
 * ChurchDirectory Contact manager component for Joomla! 1.5 and 1.6
 *
 * @version 1.6.0
 * @package churchdirectory
 * @author NFSDA
 * @copyright Copyright (C) 2011 NFSDA. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */
 /*
This file is part of ChurchDirectory.
ChurchDirectory is free software: you can redistribute it and/or modify
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

class ChurchDirectoryModelSection extends JModel {
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
				' LEFT JOIN #__churchdirectory_details AS b ON b.catid = a.id'.
				$xwhere2 .
				" WHERE a.section = 'com_churchdirectory_details'".
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