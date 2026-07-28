<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\Field;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Cwmconnect\Administrator\Service\AccessCode\AccessCodeService;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\TextField;
use Joomla\CMS\Language\Text;

/**
 * The shared access code, with a rotate button beside it.
 *
 * Rotating is the whole mitigation for a code that gets read out loud and
 * forwarded around, so it has to be one obvious click rather than "think of a
 * code yourself" — an admin inventing one picks something guessable, and an
 * admin who has to generate one elsewhere never rotates at all.
 *
 * The new value is minted in the browser and simply typed into the field, so
 * rotation is not committed until Options is saved: an admin who clicks by
 * accident can navigate away and the live code is untouched.
 *
 * @since  __DEPLOY_VERSION__
 */
class AccesscodeField extends TextField
{
    /**
     * The form field type.
     *
     * @var    string
     * @since  __DEPLOY_VERSION__
     */
    protected $type = 'Accesscode';

    /**
     * Render the input, plus the rotate button and its handler.
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    protected function getInput(): string
    {
        $id = $this->id;

        // Mirrors AccessCodeService::ALPHABET / LENGTH — kept in step by the
        // unit test rather than by hope.
        Factory::getApplication()->getDocument()->getWebAssetManager()->addInlineScript(
            <<<JS
                document.addEventListener('click', (event) => {
                    const trigger = event.target.closest('[data-cwm-rotate="{$id}"]');

                    if (!trigger) {
                        return;
                    }

                    const alphabet = '234679ACDEFGHJKMNPQRTWXYZ';
                    const bytes    = new Uint8Array(8);

                    window.crypto.getRandomValues(bytes);

                    const code = Array.from(bytes, (b) => alphabet[b % alphabet.length]).join('');
                    const field = document.getElementById('{$id}');

                    field.value = code.slice(0, 4) + '-' + code.slice(4);
                    field.dispatchEvent(new Event('change', { bubbles: true }));
                });
                JS
        );

        return '<div class="input-group">'
            . parent::getInput()
            . '<button type="button" class="btn btn-secondary" data-cwm-rotate="' . $this->escape($id) . '">'
            . $this->escape(Text::_('COM_CWMCONNECT_FIELD_ACCESS_CODE_ROTATE'))
            . '</button>'
            . '</div>'
            . '<div class="form-text">' . $this->escape($this->statusLine()) . '</div>';
    }

    /**
     * A one-line summary of whether the code is actually usable right now, so
     * the admin is not left guessing why nobody can redeem it.
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    private function statusLine(): string
    {
        $service = new AccessCodeService($this->form->getData());

        if (AccessCodeService::canonical((string) $this->value) === '') {
            return Text::_('COM_CWMCONNECT_ACCESS_CODE_STATUS_UNSET');
        }

        if ($service->hasExpired()) {
            return Text::_('COM_CWMCONNECT_ACCESS_CODE_STATUS_EXPIRED');
        }

        if (!$service->isEnabled()) {
            return Text::_('COM_CWMCONNECT_ACCESS_CODE_STATUS_OFF');
        }

        return Text::_('COM_CWMCONNECT_ACCESS_CODE_STATUS_ACTIVE');
    }
}
