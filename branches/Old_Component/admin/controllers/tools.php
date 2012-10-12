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

class QContactsControllerTools extends QContactsController {
	function display() {
		JRequest::setVar('view', 'tools');
		parent::display();
	}
	
	function import() {
		JRequest::checkToken() or die( 'Invalid Token' );
		
		$model = $this->getModel('tools');
				
		if($model->import()) {
			$msg = JText::_( TOOLS_IMPORT_MSG );
		} else {
			$msg = $model->getError();
		}
		$this->setRedirect('index.php?option=com_qcontacts',  $msg);
	}
	
	function backup() {
		JRequest::checkToken() or die( 'Invalid Token' );
		$model = $this->getModel('tools');
		if($model->backup()) {
			$msg = JText::_( TOOLS_BACKUP_MSG );
		} else {
			$msg = $model->getError();
		}
		$this->setRedirect('index.php?option=com_qcontacts',  $msg);
	}
	
	function restore() {
		JRequest::checkToken() or die( 'Invalid Token' );
		$model = $this->getModel('tools');
		if($model->restore()) {
			$msg = JText::_( TOOLS_RESTORE_MSG );
		} else {
			$msg = $model->getError();
		}
		$this->setRedirect('index.php?option=com_qcontacts',  $msg);
	}
}
?>