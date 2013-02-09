<?php
/**
 * @package    ChurchDirectory.Site
 * @copyright  Copyright (C) 2005 - 2011 Joomla! Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

jimport('joomla.application.component.controllerform');

/**
 * Controller for ChurchDirectory
 *
 * @package  ChurchDirectory.Site
 * @since    1.7.0
 */
class ChurchDirectoryControllerChurchDirectory extends JControllerForm
{

	/**
	 * Get model
	 *
	 * @param   string  $name    The model name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  object  The model.
	 */
	public function getModel($name = '', $prefix = '', $config = array('ignore_request' => true))
	{
		return parent::getModel($name, $prefix, array('ignore_request' => false));
	}

	/**
	 * Custom Submit
	 *
	 * @return boolean
	 */
	public function submit()
	{
		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		// Initialise variables.
		$app    = JFactory::getApplication();
		$model  = $this->getModel('member');
		$params = JComponentHelper::getParams('com_churchdirectory');
		$stub   = $this->input->getString('id');
		$id     = (int) $stub;

		// Get the data from POST
		$data = $this->input->get('jform', array(), 'post', 'array');

		$churchdirectory = $model->getItem($id);

		$params->merge($churchdirectory->params);

		// Check for a valid session cookie
		if ($params->get('validate_session', 0))
		{
			if (JFactory::getSession()->getState() != 'active')
			{
				$app->enqueueMessage(JText::_('COM_CHURCHDIRECTORY_SESSION_INVALID'), 'warning');

				// Save the data in the session.
				$app->setUserState('com_churchdirectory.member.data', $data);

				// Redirect back to the member form.
				$this->setRedirect(JRoute::_('index.php?option=com_churchdirectory&view=member&id=' . $stub, false));

				return false;
			}
		}

		// ChurchDirectory plugins
		JPluginHelper::importPlugin('churchdirectory');
		$dispatcher = JDispatcher::getInstance();

		// Validate the posted data.
		$form = $model->getForm();

		if (!$form)
		{
			$app->enqueueMessage($model->getError(), 'error');

			return false;
		}

		$validate = $model->validate($form, $data);

		if ($validate === false)
		{
			// Get the validation messages.
			$errors = $model->getErrors();

			// Push up to three validation messages out to the user.
			for ($i = 0, $n = count($errors); $i < $n && $i < 3; $i++)
			{
				if ($errors[$i] instanceof Exception)
				{
					$app->enqueueMessage($errors[$i]->getMessage(), 'warning');
				}
				else
				{
					$app->enqueueMessage($errors[$i], 'warning');
				}
			}

			// Save the data in the session.
			$app->setUserState('com_churchdirectory.member.data', $data);

			// Redirect back to the member form.
			$this->setRedirect(JRoute::_('index.php?option=com_churchdirectory&view=member&id=' . $stub, false));

			return false;
		}

		// Validation succeeded, continue with custom handlers
		$results = $dispatcher->trigger('onValidateChurchDirectory', array(&$churchdirectory, &$data));

		foreach ($results as $result)
		{
			if ($result instanceof Exception)
			{
				return false;
			}
		}

		// Passed Validation: Process the churchdirectory plugins to integrate with other applications
		$results = $dispatcher->trigger('onSubmitChurchDirectory', array(&$churchdirectory, &$data));

		// Send the email
		$sent = false;

		if (!$params->get('custom_reply'))
		{
			$sent = $this->_sendEmail($data, $churchdirectory);
		}

		// Set the success message if it was a success
		if (!($sent instanceof Exception))
		{
			$msg = JText::_('COM_CHURCHDIRECTORY_EMAIL_THANKS');
		}
		else
		{
			$msg = '';
		}

		// Flush the data from the session
		$app->setUserState('com_churchdirectory.member.data', null);

		// Redirect if it is set in the parameters, otherwise redirect back to where we came from
		if ($churchdirectory->params->get('redirect'))
		{
			$this->setRedirect($churchdirectory->params->get('redirect'), $msg);
		}
		else
		{
			$this->setRedirect(JRoute::_('index.php?option=com_churchdirectory&view=member&id=' . $stub, false), $msg);
		}

		return true;
	}

	/**
	 * Send email
	 *
	 * @param   array   $data             ?
	 * @param   object  $churchdirectory  ?
	 *
	 * @return array
	 */
	private function _sendEmail($data, $churchdirectory)
	{
		$app    = JFactory::getApplication();
		$params = JComponentHelper::getParams('com_churchdirectory');

		if ($churchdirectory->email_to == '' && $churchdirectory->user_id != 0)
		{
			$churchdirectory_user      = JUser::getInstance($churchdirectory->user_id);
			$churchdirectory->email_to = $churchdirectory_user->get('email');
		}
		$mailfrom = $app->getCfg('mailfrom');
		$fromname = $app->getCfg('fromname');
		$sitename = $app->getCfg('sitename');
		$copytext = JText::sprintf('COM_CHURCHDIRECTORY_COPYTEXT_OF', $churchdirectory->name, $sitename);

		$name    = $data['churchdirectory_name'];
		$email   = $data['churchdirectory_email'];
		$subject = $data['churchdirectory_subject'];
		$body    = $data['churchdirectory_message'];

		// Prepare email body
		$prefix = JText::sprintf('COM_CHURCHDIRECTORY_ENQUIRY_TEXT', JURI::base());
		$body   = $prefix . "\n" . $name . ' <' . $email . '>' . "\r\n\r\n" . stripslashes($body);

		$mail = JFactory::getMailer();
		$mail->addRecipient($churchdirectory->email_to);
		$mail->addReplyTo(array($email, $name));
		$mail->setSender(array($mailfrom, $fromname));
		$mail->setSubject($sitename . ': ' . $subject);
		$mail->setBody($body);
		$sent = $mail->Send();

		// If we are supposed to copy the sender, do so.

		// Check whether email copy function activated
		if (array_key_exists('churchdirectory_email_copy', $data))
		{
			$copytext = JText::sprintf('COM_CHURCHDIRECTORY_COPYTEXT_OF', $churchdirectory->name, $sitename);
			$copytext .= "\r\n\r\n" . $body;
			$copysubject = JText::sprintf('COM_CHURCHDIRECTORY_COPYSUBJECT_OF', $subject);

			$mail = JFactory::getMailer();
			$mail->addRecipient($email);
			$mail->addReplyTo(array($email, $name));
			$mail->setSender(array($mailfrom, $fromname));
			$mail->setSubject($copysubject);
			$mail->setBody($copytext);
			$sent = $mail->Send();
		}

		return $sent;
	}

}
