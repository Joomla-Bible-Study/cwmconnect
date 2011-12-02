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
defined('_JEXEC') or die('Restricted Access');

class QContactsControllerContact extends QContactsController {
	function __construct() {
		parent::__construct();

		$this->registerTask('add', 'edit');
		$this->registerTask('apply', 'save');
		$this->registerTask('unpublish', 'publish');
		$this->registerTask('orderup', 'ordering');
		$this->registerTask('orderdown', 'ordering');
		$this->registerTask('accesspublic', 'changeAccess');
		$this->registerTask('accessregistered', 'changeAccess');
		$this->registerTask('accessspecial', 'changeAccess');
	}
	
	function edit() {
		JRequest::setVar('view', 'contact');
		JRequest::setVar('layout', 'form');
		JRequest::setVar('hidemainmenu', 1);

		parent::display();
	}
	
	function save() {
		JRequest::checkToken() or die( 'Invalid Token' );
		
		$model = $this->getModel('contact');
				
		if(!$model->store()) {
			JError::raiseError(500, $model->getError() );
		}
		
		switch($this->getTask()) {
			case 'apply':
				$msg	= JText::sprintf( 'Changes to X saved', 'Contact' );
				$link	= 'index.php?option=com_qcontacts&controller=contact&task=edit&cid[]='. $model->getState('rid') .'';
				break;
			case 'save':
			default:
				$msg	= JText::_( 'Contact saved' );
				$link	= 'index.php?option=com_qcontacts';
				break;
		}
		$this->setRedirect($link, $msg);
	}
	
	function remove() {
		JRequest::checkToken() or die( 'Invalid Token' );
		$model = $this->getModel('contact');
		if(!$model->remove()) {
			JError::raiseError(500, $model->getError() );
		}
		$this->setRedirect('index.php?option=com_qcontacts', JText::_( 'Contact(s) removed' ));
	}
	
	function publish() {
		JRequest::checkToken() or die( 'Invalid Token' );
		$model = $this->getModel('contact');
				
		if(!$model->publish($this->getTask() == 'publish' ? 1:0)) {
			JError::raiseError(500, $model->getError() );
		}
		$this->setRedirect('index.php?option=com_qcontacts');
	}
	
	function ordering() {
		JRequest::checkToken() or die( 'Invalid Token' );
		$model = $this->getModel('contact');
		if($this->getTask() == 'orderdown') {
			$inc = 1;
		} else {
			$inc = -1;
		}
		
		if(!$model->ordering($inc)) {
			JError::raiseError(500, $model->getError() );
		}
		$this->setRedirect('index.php?option=com_qcontacts');
	}
	
	function cancel() {
		JRequest::checkToken() or die( 'Invalid Token' );
		$model = $this->getModel('contact');
		$model->cancel();
		$this->setRedirect('index.php?option=com_qcontacts');
	}
	
	function changeAccess() {
		JRequest::checkToken() or die( 'Invalid Token' );
		
		switch($this->getTask()) {
			case 'accesspublic':
			$ac = 0;
			break;
			case 'accessregistered':
			$ac = 1;
			break;
			case 'accessspecial':
			$ac = 2;
			break;
		}
		$model = $this->getModel('contact');
		if(!$model->changeAccess($ac)) {
			JError::raiseError(500, $model->getError());
		}
		$this->setRedirect('index.php?option=com_qcontacts');
	}
	
	function saveorder() {
		JRequest::checkToken() or die( 'Invalid Token' );
		$model = $this->getModel('contact');
		if(!$model->saveorder()) {
			JError::raiseError(500, $model->getError());
		}
		$this->setRedirect('index.php?option=com_qcontacts', JText::_('New ordering saved'));
	}
}
?>