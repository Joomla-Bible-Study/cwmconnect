<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Connect\Site\Controller;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Connect\Site\Model\MemberModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Mail\Mail;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\String\PunycodeHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\User;

/**
 * Member item / enquiry form controller.
 *
 * @since  2.0.0
 */
class MemberController extends FormController
{
    /**
     * Site member items honor ignore_request=false so list filters survive.
     *
     * @return  \Joomla\CMS\MVC\Model\BaseDatabaseModel|false
     *
     * @since   2.0.0
     */
    public function getModel($name = 'Member', $prefix = '', $config = ['ignore_request' => false])
    {
        return parent::getModel($name, $prefix, $config);
    }

    /**
     * Handle the enquiry-form submission: token check → session-cookie gate →
     * onValidateChurchDirectory plugin chain → email delivery → redirect.
     *
     * @return  bool
     *
     * @throws  \Exception
     * @since   2.0.0
     */
    public function submit(): bool
    {
        if (!Session::checkToken()) {
            throw new \Exception(Text::_('JINVALID_TOKEN_NOTICE'), 403);
        }

        $app    = Factory::getApplication();
        $params = ComponentHelper::getParams('com_cwmconnect');
        $stub   = $app->getInput()->getString('id');
        $id     = (int) $stub;

        $data = $app->getInput()->post->get('jform', [], 'array');

        /** @var MemberModel $model */
        $model     = $this->getModel('Member');
        $member    = $model->getItem($id);
        $params->merge($member->params);

        if ($params->get('validate_session', 0) && $app->getSession()->getState() !== 'active') {
            $app->enqueueMessage(Text::_('COM_CWMCONNECT_SESSION_INVALID'), 'warning');
            $app->setUserState('com_cwmconnect.member.data', $data);
            $this->setRedirect(Route::_('index.php?option=com_cwmconnect&view=member&id=' . $stub, false));

            return false;
        }

        PluginHelper::importPlugin('cwmconnect');

        $form = $model->getForm();

        if (!$form) {
            $app->enqueueMessage($model->getError(), 'error');
            return false;
        }

        if ($model->validate($form, $data) === false) {
            foreach (array_slice($model->getErrors(), 0, 3) as $error) {
                $app->enqueueMessage($error instanceof \Exception ? $error->getMessage() : $error, 'warning');
            }

            $app->setUserState('com_cwmconnect.member.data', $data);
            $this->setRedirect(Route::_('index.php?option=com_cwmconnect&view=member&id=' . $stub, false));

            return false;
        }

        // Validation succeeded — run custom validation plugins. Plugins that
        // return an Exception object are treated as a veto.
        $results = $app->triggerEvent('onValidateChurchDirectory', [&$member, &$data]);

        foreach ($results as $result) {
            if ($result instanceof \Exception) {
                return false;
            }
        }

        $sent = false;

        if (!$params->get('custom_reply')) {
            $sent = $this->sendEmail($data, $member, (bool) $params->get('show_email_copy'));
        }

        $msg = !($sent instanceof \Exception) ? Text::_('COM_CWMCONNECT_EMAIL_THANKS') : '';

        $app->setUserState('com_cwmconnect.member.data', null);

        if ($member->params->get('redirect')) {
            $this->setRedirect($member->params->get('redirect'), $msg);
        } else {
            $this->setRedirect(Route::_('index.php?option=com_cwmconnect&view=member&id=' . $stub, false), $msg);
        }

        return true;
    }

    /**
     * Send the enquiry email and (optionally) a copy back to the sender.
     *
     * Returns the boolean result of the (last) Mail::send() call, or a
     * \Throwable if the mailer was missing / send() threw.
     *
     * @since   2.0.0
     */
    private function sendEmail(array $data, object $member, bool $copyEnabled): bool|\Throwable
    {
        $app = Factory::getApplication();

        if (empty($member->email_to) && (int) $member->user_id !== 0) {
            $user             = User::getInstance($member->user_id);
            $member->email_to = $user->get('email');
        }

        $mailfrom = $app->get('mailfrom');
        $fromname = $app->get('fromname');
        $sitename = $app->get('sitename');

        $name    = $data['cwmconnect_name'] ?? '';
        $email   = PunycodeHelper::emailToPunycode($data['cwmconnect_email'] ?? '');
        $subject = $data['cwmconnect_subject'] ?? '';
        $body    = $data['cwmconnect_message'] ?? '';

        $prefix = Text::sprintf('COM_CWMCONNECT_ENQUIRY_TEXT', Uri::base());
        $body   = $prefix . "\n" . $name . ' <' . $email . '>' . "\r\n\r\n" . stripslashes($body);

        try {
            /** @var Mail $mail */
            $mail = Factory::getMailer();
            $mail->addRecipient($member->email_to);
            $mail->addReplyTo([$email, $name]);
            $mail->setSender([$mailfrom, $fromname]);
            $mail->setSubject($sitename . ': ' . $subject);
            $mail->setBody($body);
            $sent = $mail->Send();

            if ($copyEnabled && !empty($data['cwmconnect_email_copy'])) {
                $copytext = Text::sprintf('COM_CWMCONNECT_COPYTEXT_OF', $member->name, $sitename);
                $copytext .= "\r\n\r\n" . $body;
                $copysubject = Text::sprintf('COM_CWMCONNECT_COPYSUBJECT_OF', $subject);

                $mail = Factory::getMailer();
                $mail->addRecipient($email);
                $mail->addReplyTo([$email, $name]);
                $mail->setSender([$mailfrom, $fromname]);
                $mail->setSubject($copysubject);
                $mail->setBody($copytext);
                $sent = $mail->Send();
            }

            return (bool) $sent;
        } catch (\Throwable $e) {
            return $e;
        }
    }
}
