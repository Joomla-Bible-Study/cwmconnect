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

jimport('joomla.application.component.view');

class ChurchDirectoryViewSection extends JView {
	function display() {
		global $mainframe;
		
		$params =& $mainframe->getParams();
		$document =& JFactory::getDocument();
		
		$document->setTitle($params->get('page_title'));
		
		$categories	=& $this->get('Categories');
		for($i = 0; $i < count($categories); $i++) {
			$category =& $categories[$i];
			$category->link = JRoute::_($this->_getCategoryRoute($category->slug, $category->section));
		}
		$this->assignRef('params',	$params);
		$this->assignRef('categories',	$categories);
		parent::display($tpl);
	}
	
	function _getCategoryRoute($catid) {
		$needles = array(
			'category' => (int) $catid,
		);

		//Create the link
		$link = 'index.php?option=com_churchdirectory&view=category&catid='.$catid;

		if($item = $this->_findItem($needles)) {
			
			$link .= '&Itemid='.$item->id;
		};

		return $link;
	}
	function _findItem($needles) {
		$component =& JComponentHelper::getComponent('com_churchdirectory');

		$menus	= &JApplication::getMenu('site', array());
		$items	= $menus->getItems('componentid', $component->id);

		$match = null;

		foreach($needles as $needle => $id) {
			foreach($items as $item) {
				if ((@$item->query['view'] == $needle) && (@$item->query['catid'] == $id)) {
					$match = $item;
					break;
				}
			}
			if(isset($match)) {
				break;
			}
		}

		return $match;
	}
}
