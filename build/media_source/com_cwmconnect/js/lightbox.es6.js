/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 *
 * @since   2.0.0
 */

/**
 * Progressive-enhancement lightbox for member / household photos.
 *
 * Any link marked `[data-cwm-lightbox]` (rendered by the shared `photo`
 * layout when `linkFull` is set) normally points at the full-resolution
 * original and opens it in a new tab. This script intercepts the click and
 * shows the full image in an in-page overlay instead. With JS disabled the
 * link still works as a plain new-tab fallback.
 *
 * One overlay element is built lazily and reused for every trigger. It is
 * styled with Bootstrap 5 utility classes plus a few inline values, so no
 * extra stylesheet is required. Dismiss via the close button, a backdrop
 * click, or Escape; focus returns to the trigger that opened it.
 *
 * Visibility is toggled with the `d-none` / `d-flex` utility classes rather
 * than the `hidden` property: Bootstrap's display utilities (and
 * `.spinner-border`) set `display`, which would override the `[hidden]`
 * attribute's `display: none` and leave elements stuck visible.
 *
 * All DOM is built with createElement + textContent so caption/alt strings
 * never reach the HTML parser.
 */
((document) => {
    'use strict';

    const SELECTOR = '[data-cwm-lightbox]';

    /** @type {?HTMLElement} The reused overlay root, built on first open. */
    let overlay = null;
    /** @type {?HTMLImageElement} */
    let image = null;
    /** @type {?HTMLElement} */
    let caption = null;
    /** @type {?HTMLElement} */
    let spinner = null;
    /** @type {?HTMLButtonElement} */
    let closeBtn = null;
    /** @type {?Element} The element focus should return to on close. */
    let lastFocus = null;

    /**
     * Create an element with optional class list and text content.
     *
     * @param {string}  tag
     * @param {?string} className
     * @param {?string} text
     * @returns {HTMLElement}
     */
    const make = (tag, className = null, text = null) => {
        const el = document.createElement(tag);

        if (className) {
            el.className = className;
        }

        if (text !== null) {
            el.textContent = text;
        }

        return el;
    };

    /**
     * Show or hide an element via the `d-none` utility class.
     *
     * @param {HTMLElement} el
     * @param {boolean}     visible
     * @returns {void}
     */
    const setVisible = (el, visible) => {
        el.classList.toggle('d-none', !visible);
    };

    /**
     * Close the overlay, restore body scroll and return focus to the trigger.
     *
     * @returns {void}
     */
    const close = () => {
        if (!overlay || overlay.classList.contains('d-none')) {
            return;
        }

        overlay.classList.remove('d-flex');
        overlay.classList.add('d-none');
        document.body.classList.remove('overflow-hidden');

        // Drop the src so a huge image is not retained between opens.
        if (image) {
            image.removeAttribute('src');
        }

        if (lastFocus && typeof lastFocus.focus === 'function') {
            lastFocus.focus();
        }

        lastFocus = null;
    };

    /**
     * Build the overlay DOM once and wire its dismiss handlers. The overlay
     * starts hidden (`d-none`); `open()` swaps it to `d-flex`.
     *
     * @param {string} closeLabel  Accessible label for the close control.
     * @returns {void}
     */
    const build = (closeLabel) => {
        overlay = make(
            'div',
            'cwm-lightbox position-fixed top-0 start-0 w-100 h-100 d-none '
            + 'align-items-center justify-content-center p-3',
        );
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');
        overlay.style.background = 'rgba(0, 0, 0, 0.85)';
        overlay.style.zIndex = '2000';

        closeBtn = make('button', 'btn-close btn-close-white position-absolute top-0 end-0 m-3');
        closeBtn.type = 'button';
        closeBtn.setAttribute('aria-label', closeLabel);
        closeBtn.style.zIndex = '1';

        spinner = make('span', 'spinner-border text-light position-absolute top-50 start-50 translate-middle');
        spinner.setAttribute('aria-hidden', 'true');

        const figure = make('figure', 'm-0 text-center mw-100 mh-100 d-flex flex-column');

        image = make('img', 'img-fluid rounded shadow mx-auto');
        image.style.maxHeight = '85vh';
        image.style.objectFit = 'contain';
        image.setAttribute('alt', '');

        caption = make('figcaption', 'text-light mt-2 small');

        figure.appendChild(image);
        figure.appendChild(caption);

        overlay.appendChild(spinner);
        overlay.appendChild(closeBtn);
        overlay.appendChild(figure);
        document.body.appendChild(overlay);

        // Backdrop click closes, but a click on the image itself should not.
        overlay.addEventListener('click', (event) => {
            if (event.target === overlay || event.target === figure) {
                close();
            }
        });

        closeBtn.addEventListener('click', close);

        image.addEventListener('load', () => {
            if (spinner) {
                setVisible(spinner, false);
            }
        });
    };

    /**
     * Open the overlay for a given trigger link.
     *
     * @param {HTMLElement} trigger
     * @returns {void}
     */
    const open = (trigger) => {
        const src = trigger.getAttribute('data-cwm-full') || trigger.getAttribute('href');

        if (!src) {
            return;
        }

        if (!overlay) {
            build(trigger.getAttribute('data-cwm-close') || 'Close');
        }

        const text = trigger.getAttribute('data-cwm-caption') || '';
        caption.textContent = text;
        setVisible(caption, text !== '');
        image.setAttribute('alt', text);

        setVisible(spinner, true);

        lastFocus = document.activeElement;
        image.src = src;

        // If the image is already cached it may be complete before the load
        // listener fires — hide the spinner immediately in that case.
        if (image.complete) {
            setVisible(spinner, false);
        }

        overlay.classList.remove('d-none');
        overlay.classList.add('d-flex');
        document.body.classList.add('overflow-hidden');
        closeBtn.focus();
    };

    // Delegated trigger handling — works for photos added after load too.
    document.addEventListener('click', (event) => {
        const trigger = event.target.closest(SELECTOR);

        if (!trigger) {
            return;
        }

        event.preventDefault();
        open(trigger);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            close();
        }
    });
})(document);
