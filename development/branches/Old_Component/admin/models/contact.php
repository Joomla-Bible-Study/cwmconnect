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

class QContactsModelContact extends JModel {
	var $_id = null;
	var $_data = null;
	
	function __construct() {
		parent::__construct();

		$array = JRequest::getVar('cid',  0, '', 'array');
		$this->setId((int)$array[0]);
	}

	function setId($id) {
		$this->_id = $id;
		$this->_data = null;
	}
	
	function &getData() {
		if(empty($this->_data)) {
			$sql = 'SELECT * FROM #__qcontacts_details '.
					'  WHERE id = '. $this->_id;
			$this->_db->setQuery($sql);
			$this->_data = $this->_db->loadObject();
		}
		if(!$this->_data) {
			$this->_data = new stdClass();
			$this->_data->id = 0;
			$this->_data->name = null;
			$this->_data->alias = null;
			$this->_data->con_position = null;
			$this->_data->address = null;
			$this->_data->suburb = null;
			$this->_data->state = null;
			$this->_data->country = null;
			$this->_data->postcode = null;
			$this->_data->telephone = null;
			$this->_data->fax = null;
			$this->_data->misc = null;
			$this->_data->image = null;
			$this->_data->imagepos = null;
			$this->_data->email_to = null;
			$this->_data->default_con = null;
			$this->_data->published = 0;
			$this->_data->checked_out = 0;
			$this->_data->checked_out_time = 0;
			$this->_data->ordering = null;
			$this->_data->params = null;
			$this->_data->user_id = null;
			$this->_data->catid = null;
			$this->_data->access = null;
			$this->_data->mobile = null;
			$this->_data->webpage = null;
			$this->_data->skype = null;
			$this->_data->yahoo_msg = null;
			
		}
		return $this->_data;
	}
	
	function store() {
		$row =& $this->getTable();
		
		$data = JRequest::get('post');
		$data['misc'] = JRequest::getVar('misc', '', 'POST', 'string', JREQUEST_ALLOWRAW);
		
		if(!$row->bind($data)) {
			$this->setError($row->getError());
			return false;
		}
		
		$params = JRequest::getVar( 'params', array(), 'post', 'array' );
		if (is_array( $params )) {
			$txt = array();
			foreach ( $params as $k=>$v) {
				$txt[] = "$k=$v";
			}
			$row->params = implode( "\n", $txt );
		}
				
		if(!$row->check()) {
			$this->setError($row->getError());
			return false;
		}
		
		if (!$row->id) {
			$where = "catid = " . (int) $row->catid;
			$row->ordering = $row->getNextOrder( $where );
		}
		
		if(!$row->store()) {
			$this->setError($row->getError());
			return false;
		}
		$row->checkin();
		if ($row->default_con) {
			$query = 'UPDATE #__qcontacts_details'
			. ' SET default_con = 0'
			. ' WHERE id <> '. (int) $row->id
			. ' AND default_con = 1'
			;
			$this->_db->setQuery( $query );
			$this->_db->query();
		}
		$this->setState('rid', $row->id);
		return true;
	}
	
	function remove() {
		$cids = JRequest::getVar('cid',  0, '', 'array');
		$row =& $this->getTable();

		if(count($cids)) {
			foreach($cids as $cid) {
				if(!$row->delete($cid)) {
					$this->setError($row->getError());
					return false;
				}
			}						
		}
		return true;
	}
	
	function publish($state) {
		$cid = JRequest::getVar('cid',  0, '', 'array');
		$user =& JFactory::getUser();
		
		if (count($cid) < 1) {
			$this->setError(JText::_('Select an item to' .$task));
			return false;
		}
		
		$cids = implode(',', $cid);
				
		$query = 'UPDATE #__qcontacts_details'
		. ' SET published = ' . $state
		. ' WHERE id IN ( '. $cids .' )'
		. ' AND ( checked_out = 0 OR ( checked_out = '. (int) $user->get('id') .' ) )'
		;
		$this->_db->setQuery( $query );
		if (!$this->_db->query()) {
			$this->setError($this->_db->getErrorMsg());
			return false;
		}

		if (count( $cid ) == 1) {
			$row =& JTable::getInstance('contact', 'Table');
			$row->checkin( intval( $cid[0] ) );
		}
		return true;
	}
	
	function ordering($inc) {
		$row =& $this->getTable();
		$row->load( $this->_id );
		$row->move( $inc, 'catid = '. (int) $row->catid .' AND published != 0' );
		
		return true;
	}
	
	function cancel() {
		$row =& $this->getTable();
		$row->bind( JRequest::get( 'post' ));
		$row->checkin();
	}
	
	function changeAccess($ac) {
		$row =& $this->getTable();
		$row->load( $this->_id );
		$row->access = $ac;

		if (!$row->check()) {
			$this->setError($row->getError());
			return false;
		}
		if(!$row->store()) {
			$this->setError($row->getError());
			return false;
		}
		return true;
	}
	
	function saveorder() {
		$cid 	= JRequest::getVar('cid', array(0), 'post', 'array');
		JArrayHelper::toInteger($cid, array(0));
		$total = count($cid);
		$order = JRequest::getVar( 'order', array(0), 'post', 'array' );
		JArrayHelper::toInteger($order, array(0));
		$row =& $this->getTable();
		
		$groupings = array();

		for($i=0; $i < $total; $i++) {
			$row->load((int) $cid[$i]);
			$groupings[] = $row->catid;
			if($row->ordering != $order[$i]) {
				$row->ordering = $order[$i];
				if(!$row->store()) {
					$this->setError($row->getError());
					return false;
				}
			}
		}
		$groupings = array_unique( $groupings );
		foreach ($groupings as $group){
			$row->reorder('catid = '.(int) $group);
		}
		return true;
	}
}
?>