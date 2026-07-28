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

use Joomla\CMS\Form\Field\MediaField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/**
 * A `media` field that degrades to a read-only preview instead of the media
 * picker when the form marks it `readonly` / `disabled`.
 *
 * Joomla's core media layout cannot render a disabled state: it suppresses the
 * Select and Clear buttons (`layouts/joomla/form/field/media.php`) but still
 * emits the `<joomla-field-media>` custom element, whose `connectedCallback()`
 * throws `Error('Misconfiguaration...')` when either button is missing. The
 * same layout never puts `readonly` / `disabled` on the `<input>` itself, so
 * the path stayed freely editable and the field looked unlocked even though
 * `filter=unset` was already discarding the posted value server-side.
 *
 * The locked branch therefore renders no web component at all — just the
 * cached photo (streamed through `task=photo.serve`, since the photo cache is
 * blocked from direct web access) and the stored path in a disabled input.
 *
 * Only the locked branch is custom; an unlocked field falls straight through
 * to {@see MediaField} so standalone (non-PC) members keep the picker.
 *
 * @since  __DEPLOY_VERSION__
 */
class LockedmediaField extends MediaField
{
    /**
     * The form field type.
     *
     * @var    string
     * @since  __DEPLOY_VERSION__
     */
    protected $type = 'Lockedmedia';

    /**
     * Render the picker, or the read-only preview when the field is locked.
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    protected function getInput(): string
    {
        if (!$this->readonly && !$this->disabled) {
            return parent::getInput();
        }

        return '<div class="field-media-locked">'
            . $this->getPreview()
            . '<input type="text" class="form-control"'
            . ' id="' . htmlspecialchars($this->id, \ENT_COMPAT, 'UTF-8') . '"'
            . ' value="' . htmlspecialchars((string) $this->value, \ENT_COMPAT, 'UTF-8') . '"'
            . ' readonly disabled>'
            . '</div>';
    }

    /**
     * The thumbnail block shown above the disabled input, or the core
     * "no preview" notice when the member has no cached photo.
     *
     * Photos live under `media/com_cwmconnect/photos/`, which is blocked from
     * direct web access, so the preview goes through the admin photo proxy
     * keyed on the member id rather than linking the stored path.
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    private function getPreview(): string
    {
        $memberId = (int) $this->form->getValue('id');

        if ($memberId <= 0 || trim((string) $this->value) === '') {
            return '<div class="field-media-preview mb-2">'
                . '<div class="preview_empty">' . Text::_('JLIB_FORM_MEDIA_PREVIEW_EMPTY') . '</div>'
                . '</div>';
        }

        // Route::_() already returns an attribute-safe (&amp;-encoded) URL, and
        // the id is cast to int above — escaping it again would double-encode
        // the ampersands and break the query string.
        $src = Route::_('index.php?option=com_cwmconnect&task=photo.serve&id=' . $memberId);

        return '<div class="field-media-preview mb-2">'
            . '<img class="media-preview" alt="" style="max-width:200px;max-height:200px"'
            . ' src="' . $src . '">'
            . '</div>';
    }
}
