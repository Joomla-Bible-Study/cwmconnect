<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Site\Controller;

use CWM\Component\Cwmconnect\Site\Model\ProfileModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\String\PunycodeHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\User;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Phase 0b: handles the "contact this member" enquiry submitted from the v2
 * profile page. Ported from the legacy MemberController (the no-op
 * `onValidateChurchDirectory` plugin hook is dropped; anti-spam lives in the
 * form's validation rules).
 *
 * @since  __DEPLOY_VERSION__
 */
class ProfileController extends BaseController
{
    /**
     * Send an enquiry email to the member: token check → optional session gate
     * → form validation (anti-spam rules) → delivery → redirect to the profile.
     *
     * @return  boolean
     *
     * @throws  \Exception
     * @since   __DEPLOY_VERSION__
     */
    public function submit(): bool
    {
        if (!Session::checkToken()) {
            throw new \Exception(Text::_('JINVALID_TOKEN_NOTICE'), 403);
        }

        $app    = Factory::getApplication();
        $params = ComponentHelper::getParams('com_cwmconnect');
        $id     = $app->getInput()->getInt('id', 0);
        $data   = $app->getInput()->post->get('jform', [], 'array');

        $back = Route::_('index.php?option=com_cwmconnect&view=profile&id=' . $id, false);

        /** @var ProfileModel $model */
        $model  = $this->getModel('Profile', 'Site', ['ignore_request' => true]);
        $member = $model->getItem($id);

        if ($member === false) {
            $app->enqueueMessage(Text::_('COM_CWMCONNECT_PROFILE_NOT_FOUND'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_cwmconnect&view=members', false));

            return false;
        }

        if ($params->get('validate_session', 0) && $app->getSession()->getState() !== 'active') {
            $app->enqueueMessage(Text::_('COM_CWMCONNECT_SESSION_INVALID'), 'warning');
            $app->setUserState('com_cwmconnect.profile.data', $data);
            $this->setRedirect($back);

            return false;
        }

        $form = $model->getForm();

        if (!$form || $model->validate($form, $data) === false) {
            foreach (array_slice($model->getErrors(), 0, 3) as $error) {
                $app->enqueueMessage($error instanceof \Exception ? $error->getMessage() : $error, 'warning');
            }

            $app->setUserState('com_cwmconnect.profile.data', $data);
            $this->setRedirect($back);

            return false;
        }

        $sent = true;

        if (!$params->get('custom_reply')) {
            $sent = $this->sendEmail($data, $member, (bool) $params->get('show_email_copy'));
        }

        $app->setUserState('com_cwmconnect.profile.data', null);

        $this->setRedirect($back, $sent instanceof \Throwable ? '' : Text::_('COM_CWMCONNECT_EMAIL_THANKS'));

        return true;
    }

    /**
     * Send the enquiry email and (optionally) a copy back to the sender.
     *
     * @param   array<string, mixed>  $data         Validated form data.
     * @param   object                $member       The target member.
     * @param   boolean               $copyEnabled  Whether to copy the sender.
     *
     * @return  bool|\Throwable  Send result, or the throwable on failure.
     *
     * @since   __DEPLOY_VERSION__
     */
    private function sendEmail(array $data, object $member, bool $copyEnabled): bool|\Throwable
    {
        $app = Factory::getApplication();

        if (empty($member->email_to) && (int) ($member->user_id ?? 0) !== 0) {
            $member->email_to = User::getInstance($member->user_id)->get('email');
        }

        if (empty($member->email_to)) {
            return false;
        }

        $mailfrom = $app->get('mailfrom');
        $fromname = $app->get('fromname');
        $sitename = $app->get('sitename');

        $name    = $data['cwmconnect_name'] ?? '';
        $email   = PunycodeHelper::emailToPunycode($data['cwmconnect_email'] ?? '');
        $subject = $data['cwmconnect_subject'] ?? '';
        $body    = $data['cwmconnect_message'] ?? '';

        $body = Text::sprintf('COM_CWMCONNECT_ENQUIRY_TEXT', Uri::base())
            . "\n" . $name . ' <' . $email . '>' . "\r\n\r\n" . stripslashes((string) $body);

        try {
            $mail = Factory::getMailer();
            $mail->addRecipient($member->email_to);
            $mail->addReplyTo([$email, $name]);
            $mail->setSender([$mailfrom, $fromname]);
            $mail->setSubject($sitename . ': ' . $subject);
            $mail->setBody($body);
            $sent = $mail->Send();

            if ($copyEnabled && !empty($data['cwmconnect_email_copy'])) {
                $copy = Text::sprintf('COM_CWMCONNECT_COPYTEXT_OF', $member->name, $sitename) . "\r\n\r\n" . $body;

                $mail = Factory::getMailer();
                $mail->addRecipient($email);
                $mail->addReplyTo([$email, $name]);
                $mail->setSender([$mailfrom, $fromname]);
                $mail->setSubject(Text::sprintf('COM_CWMCONNECT_COPYSUBJECT_OF', $subject));
                $mail->setBody($copy);
                $sent = $mail->Send();
            }

            return (bool) $sent;
        } catch (\Throwable $e) {
            return $e;
        }
    }
}
