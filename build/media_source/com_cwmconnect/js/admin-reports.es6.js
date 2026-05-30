/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 *
 * @since   __DEPLOY_VERSION__
 */

/**
 * Turns the "Generate PDF" button on the Reports page into an AJAX action:
 * instead of a full-page navigation that freezes while mpdf renders hundreds
 * of members, it shows a spinner, POSTs to task=reports.generatepdf, and then
 * renders a Download link from the JsonResponse envelope.
 *
 * All DOM construction uses createElement + textContent so server strings
 * (messages, error text, URLs) never reach the HTML parser as markup.
 */
((document, Joomla) => {
    'use strict';

    /**
     * Script options published by ReportsHtmlView.
     *
     * @typedef {object} ReportOptions
     * @property {string} csrfToken       Joomla session form token.
     * @property {string} generateUrl     Route to task=reports.generatepdf.
     * @property {object} i18n            Pre-translated UI strings.
     * @property {string} i18n.building   Spinner label while building.
     * @property {string} i18n.download   Download-link label.
     * @property {string} i18n.ready      "%s members" ready summary.
     * @property {string} i18n.unknownError  Fallback when fetch itself fails.
     */

    /** @type {ReportOptions} */
    const options = Joomla.getOptions('com_cwmconnect.reports');

    if (!options) {
        return;
    }

    const button = document.getElementById('pdfExportBtn');
    const status = document.getElementById('pdf-build-status');
    const result = document.getElementById('pdf-build-result');

    if (!button || !status || !result) {
        return;
    }

    /**
     * Replace a container's children (pure-DOM, no innerHTML).
     *
     * @param {HTMLElement} container
     * @param {Node[]}      children
     */
    const replaceChildren = (container, children) => {
        while (container.firstChild) {
            container.removeChild(container.firstChild);
        }

        children.forEach((node) => container.appendChild(node));
    };

    /**
     * Create an element with optional className and text content.
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

        if (text !== null && text !== undefined) {
            el.textContent = text;
        }

        return el;
    };

    /**
     * Positional %s replacer for Joomla language strings.
     *
     * @param {string}    template
     * @param {...string} args
     * @returns {string}
     */
    const sprintf = (template, ...args) => {
        let i = 0;

        return template.replace(/%s/g, () => {
            if (i < args.length) {
                const val = args[i];
                i += 1;

                return val;
            }

            return '%s';
        });
    };

    /**
     * Show a spinner + message in the status area and clear any prior result.
     *
     * @param {string} message
     */
    const showSpinner = (message) => {
        const wrap    = make('div', 'd-inline-flex align-items-center gap-2');
        const spinner = make('span', 'spinner-border spinner-border-sm');
        spinner.setAttribute('aria-hidden', 'true');

        wrap.appendChild(spinner);
        wrap.appendChild(make('span', null, message));

        replaceChildren(status, [wrap]);
        replaceChildren(result, []);
    };

    /**
     * Render a Bootstrap alert into the status row.
     *
     * @param {string} variant  One of 'success', 'warning', 'danger', 'info'.
     * @param {string} message  Plain text.
     */
    const showStatus = (variant, message) => {
        const alert = make('div', `alert alert-${variant} mb-0`, message);
        alert.setAttribute('role', 'alert');
        replaceChildren(status, [alert]);
    };

    /**
     * Render the success state: a summary line plus a download link.
     *
     * @param {string} url
     * @param {number} count
     */
    const showDownload = (url, count) => {
        showStatus('success', sprintf(options.i18n.ready, String(count)));

        const link = make('a', 'btn btn-success', null);
        link.setAttribute('href', url);
        link.setAttribute('target', '_blank');
        link.setAttribute('rel', 'noopener');

        const icon = make('span', 'icon-download');
        icon.setAttribute('aria-hidden', 'true');
        link.appendChild(icon);
        link.appendChild(document.createTextNode(` ${options.i18n.download}`));

        replaceChildren(result, [link]);
    };

    /**
     * POST to the generate endpoint. The CSRF token is sent as a hidden form
     * field per Joomla convention; the optional include_hidden flag mirrors
     * the checkbox so a staff copy can flag hidden members.
     *
     * @param {boolean} includeHidden
     * @returns {Promise<{success: boolean, message: string, data: *}>}
     */
    const callEndpoint = async (includeHidden) => {
        const body = new FormData();
        body.append(options.csrfToken, '1');

        if (includeHidden) {
            body.append('include_hidden', '1');
        }

        const response = await fetch(options.generateUrl, {
            method:      'POST',
            credentials: 'same-origin',
            body,
            headers: {
                Accept: 'application/json',
            },
        });

        const text = await response.text();

        try {
            return JSON.parse(text);
        } catch {
            return { success: false, message: options.i18n.unknownError, data: null };
        }
    };

    button.addEventListener('click', async (event) => {
        event.preventDefault();

        if (button.classList.contains('disabled')) {
            return;
        }

        const hidden = document.getElementById('includeHidden');
        const includeHidden = Boolean(hidden && hidden.checked);

        button.classList.add('disabled');
        button.setAttribute('aria-disabled', 'true');
        showSpinner(options.i18n.building);

        let json;

        try {
            json = await callEndpoint(includeHidden);
        } catch {
            json = { success: false, message: options.i18n.unknownError, data: null };
        }

        button.classList.remove('disabled');
        button.removeAttribute('aria-disabled');

        if (json && json.success && json.data && json.data.url) {
            showDownload(json.data.url, Number(json.data.count) || 0);
        } else {
            showStatus('danger', (json && json.message) || options.i18n.unknownError);
        }
    });
})(document, window.Joomla);
