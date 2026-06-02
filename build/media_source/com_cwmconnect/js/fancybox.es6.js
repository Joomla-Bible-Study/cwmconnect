/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 *
 * @since   2.0.0
 */

/* globals Fancybox */

/**
 * Initialise Fancybox for member / household photos.
 *
 * The vendored @fancyapps/ui UMD build (loaded first via the
 * `com_cwmconnect.fancybox-vendor` asset) exposes the global `Fancybox`.
 * `Fancybox.bind()` delegates on the document, so it picks up every link
 * marked `[data-fancybox]` (rendered by the shared `photo` layout when
 * `linkFull` is set) — including any added to the page later. The link's
 * `href` is the full-resolution image and `data-caption` the member name.
 *
 * Mirrors Proclaim's Fancybox integration so the lightbox looks and behaves
 * consistently across the CWM family of extensions.
 */
((document) => {
    'use strict';

    const init = () => {
        if (typeof Fancybox === 'undefined') {
            return;
        }

        Fancybox.bind('[data-fancybox]', {
            backdropClick: 'close',
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})(document);
