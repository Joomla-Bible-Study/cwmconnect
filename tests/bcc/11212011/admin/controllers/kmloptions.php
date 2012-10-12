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

class ChurchDirectoryControllerKMLOptions extends JControllerAdmin {
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
				$msg	= JText::sprintf( 'Changes to X saved', 'contact' );
				$link	= 'index.php?option=com_churchdirectory&controller=kmloptions&task=edit&cid[]='. $model->getState('rid') .'';
				break;
			case 'save':
			default:
				$msg	= JText::_( 'KML Options saved' );
				$link	= 'index.php?option=com_churchdirectory&view=kmloptoins';
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
		$this->setRedirect('index.php?option=com_churchdirectory&view=kmloptoins', JText::_( 'KML Options(s) removed' ));
	}

	function publish() {
		JRequest::checkToken() or die( 'Invalid Token' );
		$model = $this->getModel('contact');

		if(!$model->publish($this->getTask() == 'publish' ? 1:0)) {
			JError::raiseError(500, $model->getError() );
		}
		$this->setRedirect('index.php?option=com_churchdirectory&view=kmloptoins');
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
		$this->setRedirect('index.php?option=com_churchdirectory&view=kmloptoins');
	}

	function cancel() {
		JRequest::checkToken() or die( 'Invalid Token' );
		$model = $this->getModel('contact');
		$model->cancel();
		$this->setRedirect('index.php?option=com_churchdirectory&view=kmloptoins');
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
		$this->setRedirect('index.php?option=com_churchdirectory&view=kmloptoins');
	}

	function saveorder() {
		JRequest::checkToken() or die( 'Invalid Token' );
		$model = $this->getModel('contact');
		if(!$model->saveorder()) {
			JError::raiseError(500, $model->getError());
		}
		$this->setRedirect('index.php?option=com_churchdirectory&view=kmloptoins', JText::_('New ordering saved'));
	}
}
?>