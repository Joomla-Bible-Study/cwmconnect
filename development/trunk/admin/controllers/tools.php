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
defined('_JEXEC') or die;

class ChurchDirectoryControllerTools extends JControllerAdmin
{
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
		$this->setRedirect('index.php?option=com_churchdirectory',  $msg);
	}

	function backup() {
		JRequest::checkToken() or die( 'Invalid Token' );
		$model = $this->getModel('tools');
		if($model->backup()) {
			$msg = JText::_( TOOLS_BACKUP_MSG );
		} else {
			$msg = $model->getError();
		}
		$this->setRedirect('index.php?option=com_churchdirectory',  $msg);
	}

	function restore() {
		JRequest::checkToken() or die( 'Invalid Token' );
		$model = $this->getModel('tools');
		if($model->restore()) {
			$msg = JText::_( TOOLS_RESTORE_MSG );
		} else {
			$msg = $model->getError();
		}
		$this->setRedirect('index.php?option=com_churchdirectory',  $msg);
	}
}