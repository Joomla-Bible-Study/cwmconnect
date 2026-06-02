/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 *
 * @since   2.0.0
 */

/**
 * Backdrop-dismiss for Joomla's native dialog.
 *
 * Core's JoomlaDialog closes on the ✕ button and Escape, but NOT on a click
 * outside the content box. This adds that one missing affordance without
 * replacing the native widget: a modal <dialog> shown with showModal() routes
 * clicks on its backdrop to the <dialog> element itself, so a click whose
 * target is the dialog (and not its inner content) means "outside" — close it.
 *
 * Delegated on document so it covers every Joomla dialog, including ones
 * created lazily by joomla.dialog-autocreate.
 */
((document) => {
    'use strict';

    document.addEventListener('click', (event) => {
        const dialog = event.target;

        if (
            dialog instanceof HTMLDialogElement
            && dialog.open
            && dialog.closest('joomla-dialog')
        ) {
            dialog.close();
        }
    });
})(document);
