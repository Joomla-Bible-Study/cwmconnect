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
	var $max_cust_fields = 6;
	var $_id = 0;
	var $_data = null;
	var $_contact = null;
		
	function __construct() {
		parent::__construct();

		$this->_id = JRequest::getInt('id', 0);
		$this->_data = new stdClass();
		
		$this->_data->name = JRequest::getString('name', '', 'post');
		$this->_data->email = JRequest::getString('email', '', 'post');
		$this->_data->subject = JRequest::getString('subject', '', 'post');
		$this->_data->body = JRequest::getString('body', '', 'post');
		$this->_data->email_copy = JRequest::getString('email_copy', '', 'post');
		$this->_data->captcha = JRequest::getString('captcha', '', 'post');
		for($i=1; $i<=$this->max_cust_fields; $i++) {
			$c = "cust$i";
			$this->_data->$c = JRequest::getString($c, '', 'post');
		}
	}

	function &getFormData() {
		return $this->_data;
	}
	
	function _getContactQuery( &$options ) {
		
		$aid = @$options['aid'];
		$id = $this->_id;
		$groupBy = @$options['group by'];
		$orderBy = @$options['order by'];

		$select = 'a.*, cc.title as category_name, '
		. ' CASE WHEN CHAR_LENGTH(a.alias) THEN CONCAT_WS(\':\', a.id, a.alias) ELSE a.id END as slug, '
		. ' CASE WHEN CHAR_LENGTH(cc.alias) THEN CONCAT_WS(\':\', cc.id, cc.alias) ELSE cc.id END AS catslug ';
		$from	= '#__qcontacts_details AS a';

		$joins[] = 'INNER JOIN #__categories AS cc on cc.id = a.catid';

		$wheres[] = 'a.id = ' . (int) $id;
		$wheres[] = 'a.published = 1';
		$wheres[] = 'cc.published = 1';

		if ($aid !== null) {
			$wheres[] = 'a.access <= ' . (int) $aid;
			$wheres[] = 'cc.access <= ' . (int) $aid;
		}

		$query = 'SELECT ' . $select .
				' FROM ' . $from .
				' '. implode ( ' ', $joins ) .
				' WHERE ' . implode( ' AND ', $wheres );

		return $query;
	}

	function getContact($options=array())	{
		//global $mainframe;
		if(!$this->_contact) {
			$query	= $this->_getContactQuery( $options );
			$result = $this->_getList( $query );
			$this->_contact = @$result[0];
		}
		return $this->_contact;
	}
	
	function mailTo() {
		global $mainframe;
		
		$pparams =& $mainframe->getParams('com_qcontacts');
		$SiteName = $mainframe->getCfg('sitename');
		$default = JText::sprintf('MAILENQUIRY', $SiteName);
		
		$subject = $this->_data->subject;
		if(!$subject) { $subject = $default; }
		
		$contact = $this->getContact();
		$cparams =  new JParameter($contact->params);
		$pparams->merge($cparams);
		
		if($contact->email_to == '' && $contact->user_id != 0){
			$contact_user = JUser::getInstance($contact->user_id);
			$contact->email_to = $contact_user->get('email');
		}
				
		jimport('joomla.mail.helper');
		if (($pparams->get('email_show',2) == 2 && !$this->_data->email) || 
		($pparams->get('message_show',2) == 2 && !$this->_data->body) || 
		($pparams->get('name_show',2) == 2 && !$this->_data->name) ||
		($this->_data->email && JMailHelper::isEmailAddress($this->_data->email) == false))
		{
			$this->setError(JText::_('CONTACT_FORM_NC'));
			return false;
		}
		
		for($i=1; $i<=$this->max_cust_fields; $i++){
			$cust = $pparams->get('cust'.$i.'_show');
			$cf = "cust$i";
			if((int)$cust == 2 && !$this->_data->$cf) {
				$this->setError(JText::_('CONTACT_FORM_NC'));
				return false;
			}
		}
		
		JPluginHelper::importPlugin('contact');
		$dispatcher	=& JDispatcher::getInstance();
				
		if  (!$this->_validateInputs($contact, $this->_data->email, $this->_data->subject, $this->_data->body, $this->_data->captcha)) {
			return false;
		}

		$post = JRequest::get('post');
		$results = $dispatcher->trigger('onValidateContact', array( &$contact, &$post));

		foreach ($results as $result) {
			if (JError::isError($result)) {
				return false;
			}
		}

		$results = $dispatcher->trigger('onSubmitContact', array(&$contact, &$post));
		$params = new JParameter($contact->params);
		
		if (!$pparams->get('custom_reply')) {
			$MailFrom = $mainframe->getCfg('mailfrom');
			$FromName = $mainframe->getCfg('fromname');

			$prefix = JText::sprintf('ENQUIRY_TEXT', JURI::base());
			$body = $prefix."\n".$this->_data->name;
			if($this->_data->email) {
				$body .= ' <'.$this->_data->email.'>';
			}
			if($this->_data->body) {
				$body .= "\r\n\r\n".stripslashes($this->_data->body);
			}
			for($i=1; $i<=$this->max_cust_fields; $i++){
				$custs = $pparams->get('cust'.$i.'_show');
				
				if($custs) {
					$cust = $pparams->get('cust'.$i.'_label');
					if($cust) {
						$cf = "cust$i";
						$body .= "\r\n\r\n" . $cust . ":\r\n" . stripslashes($this->_data->$cf);
					}
				}
			}
			if($pparams->get('show_ip')) {
				$body .= "\r\n\r\n" . JText::_('Sender IP').': '.$_SERVER['REMOTE_ADDR'];
			}
			$mail = JFactory::getMailer();

			$mail->addRecipient($contact->email_to);
			if($this->_data->email && $pparams->get('email_from',0)==0) {
				$mail->setSender(array($this->_data->email, $this->_data->name));
			} else {
				$mail->setSender(array($MailFrom, $FromName));
			}
			$mail->setSubject($FromName.': '. $subject);
			$mail->setBody($body);

			$sent = $mail->Send();

			
			$emailcopyCheck = $params->get('show_email_copy', 0);

			if ($this->_data->email && $this->_data->email_copy && $emailcopyCheck)
			{
				$copyText = JText::sprintf('Copy of:', $contact->name, $SiteName);
				$copyText .= "\r\n\r\n".$body;
				$copySubject = JText::_('Copy of:')." ".$subject;

				$mail = JFactory::getMailer();

				$mail->addRecipient($this->_data->email);
				$mail->setSender(array($MailFrom, $FromName));
				$mail->setSubject($copySubject);
				$mail->setBody($copyText);

				$sent = $mail->Send();
			}
		}
		
		return true;
	}
	
	function _validateInputs($contact, $email, $subject, $body, $captcha) {
		global $mainframe;

		$session =& JFactory::getSession();

		$params	= new JParameter($contact->params);
		$pparams = &$mainframe->getParams('com_qcontacts');

		$sessionCheck = $pparams->get( 'validate_session', 1 );
		$sessionName = $session->getName();
		if  ( $sessionCheck ) {
			if ( !isset($_COOKIE[$sessionName]) ) {
				$this->setError( JText::_('ALERTNOTAUTH') );
				return false;
			}
		}

		$configEmail = $pparams->get( 'banned_email', '' );
		$paramsEmail = $params->get( 'banned_mail', '' );
		$bannedEmail = $configEmail . ($paramsEmail ? ';'.$paramsEmail : '');

		if ( $bannedEmail ) {
			$bannedEmail = explode( ';', $bannedEmail );
			foreach ($bannedEmail as $value) {
				if ( JString::stristr($email, $value) ) {
					$this->setError( JText::sprintf('MESGHASBANNEDTEXT', 'Email') );
					return false;
				}
			}
		}

		$configSubject = $pparams->get( 'banned_subject', '' );
		$paramsSubject = $params->get( 'banned_subject', '' );
		$bannedSubject = $configSubject . ( $paramsSubject ? ';'.$paramsSubject : '');

		if ( $bannedSubject ) {
			$bannedSubject = explode( ';', $bannedSubject );
			foreach ($bannedSubject as $value) {
				if ( $value && JString::stristr($subject, $value) ) {
					$this->setError( JText::sprintf('MESGHASBANNEDTEXT', 'Subject') );
					return false;
				}
			}
		}

		$configText = $pparams->get( 'banned_text', '' );
		$paramsText = $params->get( 'banned_text', '' );
		$bannedText = $configText . ( $paramsText ? ';'.$paramsText : '' );

		if ( $bannedText ) {
			$bannedText = explode( ';', $bannedText );
			foreach ($bannedText as $value) {
				if ( $value && JString::stristr($body, $value) ) {
					$this->setError( JText::sprintf('MESGHASBANNEDTEXT', 'Message') );
					return false;
				}
			}
		}

		$check = explode( '@', $email );
		if ( strpos( $email, ';' ) || strpos( $email, ',' ) || strpos( $email, ' ' ) || count( $check ) > 2 ) {
			$this->setError( JText::_('You cannot enter more than one email address', true));
			return false;
		}
		$sc = $params->get('show_captcha');
		if($sc == '') {
			$sc = $pparams->get('show_captcha');
		}
		if($sc) {
			require_once JPATH_COMPONENT . DS . 'includes' . DS . 'securimage' . DS . 'securimage.php';
			$img = new securimage();
			if($captcha == '' || $img->check($captcha) == false) {
				$this->setError( JText::_('Wrong security code', true));
				return false;
			}
		}
		return true;
	}
	
}