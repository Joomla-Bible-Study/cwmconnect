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
defined('_JEXEC') or die('Restricted access');
jimport('joomla.application.component.model');

class QContactsModelTools extends JModel {
	
	function import() {
	
		$sql = "SELECT * FROM #__categories" .
		" WHERE section = 'com_contact_details'";
		
		$this->_db->setQuery($sql);
		$categs = $this->_db->loadObjectList();
		if($this->_db->getErrorNum()) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		}
		$nc_params="\ncimage_align=\nshow_skype=\nshow_yahoo=\nicon_skype=\nicon_yahoo=\nicon_web=\nshow_captcha=\ncust1_show=\ncust1_label=\ncust1_type=\ncust1_size=\ncust2_show=\ncust2_label=\ncust2_type=\ncust2_size=\ncust3_show=\ncust3_label=\ncust3_type=\ncust3_size=\n";
		foreach($categs as $cat) {
			$vals = array();
			$vals[] = $cat->parent_id;
			$vals[] = $this->_db->Quote($cat->title);
			$vals[] = $this->_db->Quote($cat->name);
			$vals[] = $this->_db->Quote($cat->alias);
			$vals[] = $this->_db->Quote($cat->image);
			$vals[] = $this->_db->Quote('com_qcontacts_details');
			$vals[] = $this->_db->Quote($cat->image_position);
			$vals[] = $this->_db->Quote($cat->description);
			$vals[] = $cat->published;
			$vals[] = $this->_db->Quote($cat->checked_out);
			$vals[] = $this->_db->Quote($cat->checked_out_time);
			$vals[] = $this->_db->Quote($cat->editor);
			$vals[] = $cat->ordering;
			$vals[] = $cat->access;
			$vals[] = $cat->count;
			$vals[] = $this->_db->Quote($cat->params);
			
			$sql = "INSERT INTO #__categories" .
			" (parent_id, title, name, alias, image, section, image_position, description, published, checked_out, checked_out_time, editor, ordering, access, count, params)" .
			" VALUES (" . implode(',', $vals) . ")";
			$this->_db->setQuery($sql);
			if(!$this->_db->query()) {
				$this->setError($this->_db->getErrorMsg());
				return false;
			}
			$catid = $this->_db->insertid();
			
			$sql = "SELECT * FROM #__contact_details " .
			"WHERE catid=" . $cat->id;
			$this->_db->setQuery($sql);
			$contacts = $this->_db->loadObjectList();
			if($this->_db->getErrorNum()) {
				$this->setError($this->_db->getErrorMsg());
				return false;
			}
			if(count($contacts)) {
				foreach($contacts as $cont) {
					$vals = array();
					$vals[] = $this->_db->Quote($cont->name);
					$vals[] = $this->_db->Quote($cont->alias);
					$vals[] = $this->_db->Quote($cont->con_position);
					$vals[] = $this->_db->Quote($cont->address);
					$vals[] = $this->_db->Quote($cont->suburb);
					$vals[] = $this->_db->Quote($cont->state);
					$vals[] = $this->_db->Quote($cont->country);
					$vals[] = $this->_db->Quote($cont->postcode);
					$vals[] = $this->_db->Quote($cont->telephone);
					$vals[] = $this->_db->Quote($cont->fax);
					$vals[] = $this->_db->Quote($cont->misc);
					$vals[] = $this->_db->Quote($cont->image);
					$vals[] = $this->_db->Quote($cont->imagepos);
					$vals[] = $this->_db->Quote($cont->email_to);
					$vals[] = $cont->default_con;
					$vals[] = $cont->published;
					$vals[] = $this->_db->Quote($cont->checked_out);
					$vals[] = $this->_db->Quote($cont->checked_out_time);
					$vals[] = $cont->ordering;
					$vals[] = $this->_db->Quote($cont->params.$nc_params);
					$vals[] = $cont->user_id;
					$vals[] = $catid;
					$vals[] = $cont->access;
					$vals[] = $this->_db->Quote($cont->mobile);
					$vals[] = $this->_db->Quote($cont->webpage);
					
					$sql = "INSERT INTO #__qcontacts_details" .
					" (name, alias, con_position, address, suburb, state, country, postcode, telephone, fax, misc, image, imagepos, email_to, " .
					"default_con, published, checked_out, checked_out_time, ordering, params, user_id, catid, access, mobile, webpage) " .
					"VALUES (" . implode(',', $vals) . ")";
					$this->_db->setQuery($sql);
					if(!$this->_db->query()) {
						$this->setError($this->_db->getErrorMsg());
						return false;
					}
				}
			}
		}
		return true;
	}
	
	function backup() {
		$sql = "INSERT INTO #__qcontacts_config" .
		" (id, params) VALUES (1, '')";
		$this->_db->setQuery($sql);
		$this->_db->query();
		
		$sql = "SELECT params FROM #__components" .
		" WHERE `option` = 'com_qcontacts' AND parent=0";
		$this->_db->setQuery($sql);
		$r = $this->_db->loadOBject();
		if(!is_object($r)) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		}
		$sql = "UPDATE #__qcontacts_config" .
		" SET params = " . $this->_db->Quote($r->params);
		$this->_db->setQuery($sql);
		if(!$this->_db->query()) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		}
		return true;
	}
	
	function restore() {
		$sql = "SELECT params FROM #__qcontacts_config" .
		" WHERE id = 1";
		$this->_db->setQuery($sql);
		$r = $this->_db->loadOBject();
		if(!is_object($r)) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		}
		if(!$r->params) {
			$this->setError(JText::_( 'No backup to restore!' ));
			return false;
		}
		
		$sql = "UPDATE #__components" .
		" SET params = " . $this->_db->Quote($r->params) .
		" WHERE `option` = 'com_qcontacts' AND parent=0";
		$this->_db->setQuery($sql);
		if(!$this->_db->query()) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		}
		
		return true;
	}
}
?>