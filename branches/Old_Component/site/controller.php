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

jimport( 'joomla.application.component.controller' );

class QContactsController extends JController {
	
	function display() {
		$document =& JFactory::getDocument();

		$viewName	= JRequest::getVar('view', 'category', 'default', 'cmd');
		$viewType	= $document->getType();

		$view = &$this->getView($viewName, $viewType);

		$model	= &$this->getModel($viewName);
		if (!JError::isError($model)) {
			$view->setModel($model, true);
		}

		if ($viewName == 'contact') {
			$modelCat	= &$this->getModel('category');
			$view->setModel($modelCat);
		}

		$view->assign('error', $this->getError());
		$view->display();
	}

	function submit()	{
		global $mainframe;
		JRequest::checkToken() or die('Invalid Token');
		
		$model =& $this->getModel('contact');
		
		if($model->mailTo()) {
			$contact = $model->getContact();
			$params =& $mainframe->getParams('com_qcontacts');
			$cparams =  new JParameter($contact->params);
			$params->merge($cparams);
			if($params->get('after_submit',0) == 0) {
				$msg = JText::_('Thank you for your e-mail');
				$link = JRoute::_('index.php?option=com_qcontacts&view=contact&id='.$contact->slug.'&catid='.$contact->catslug, false);
				$this->setRedirect($link, $msg);
			} else {
				$this->display();
			}
		} else {
			$this->setError($model->getError());
			$this->display();
		}
	}
}