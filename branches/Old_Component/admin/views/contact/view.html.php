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

class QContactsViewContact extends JView{
	function display($tpl = null) {
				
		$contact =& $this->get('Data');
		$isnew = ($contact->id < 1);
		$this->assignRef('isnew', $isnew);
				
		if ($isnew) {
			$contact->imagepos = 'top';
			$contact->ordering 	= 0;
			$contact->published = 1;
		}
		$lists = array();
		
		$query = 'SELECT ordering AS value, name AS text'
		. ' FROM #__qcontacts_details'
		. ' WHERE published >= 0'
		. ' AND catid = '.(int) $contact->catid
		. ' ORDER BY ordering'
		;
		
		if($isnew) {
			$lists['ordering'] = JHTML::_('list.specificordering',  $contact, '', $query, 1);
		} else {
			$lists['ordering'] = JHTML::_('list.specificordering',  $contact, $contact->id, $query, 1);
		}
		$params =& JComponentHelper::getParams('com_qcontacts');
		$img_path = trim($params->get('image_path','images/stories'),'/');
		$file = JPATH_ADMINISTRATOR .'/components/com_qcontacts/contact_items.xml';
		$params = new JParameter($contact->params, $file, 'component');
				
		$lists['user_id'] = JHTML::_('list.users', 'user_id', $contact->user_id, 1, NULL, 'name', 0);
		$lists['catid'] = JHTML::_('list.category', 'catid', 'com_qcontacts_details', intval( $contact->catid ));
		$lists['image'] = JHTML::_('list.images', 'image', $contact->image, NULL, '/'.$img_path.'/');
		$lists['access'] = JHTML::_('list.accesslevel', $contact);
		$lists['published'] = JHTML::_('select.booleanlist', 'published', '', $contact->published);
		$lists['default_con'] = JHTML::_('select.booleanlist', 'default_con', '', $contact->default_con);
				
		$this->assignRef('contact', $contact);
		$this->assignRef('lists', $lists);
		$this->assignRef('params', $params);
		$this->assign('image_path',$img_path);
		parent::display($tpl);
	}
}
?>