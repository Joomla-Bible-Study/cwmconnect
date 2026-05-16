/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 *
 * @since   __DEPLOY_VERSION__
 */

/**
 * Wires up the Planning Center card on the Cpanel: a "Test connection"
 * button that probes /people/v2/me and a "Sync now" button that runs one
 * SyncEngine pass. Both call the same admin controller via fetch() and
 * render the JsonResponse envelope into the status / result panels.
 *
 * All DOM construction uses createElement + textContent so untrusted strings
 * (message bodies, error text) never reach the HTML parser.
 */
((document, Joomla) => {
    'use strict';

    /**
     * Script options block published by CpanelHtmlView::registerPcAssets().
     *
     * @typedef {object} PcOptions
     * @property {string} csrfToken      Joomla session form token.
     * @property {string} syncUrl        Route to task=cpanel.pcSync.
     * @property {string} testUrl        Route to task=cpanel.pcTestConnection.
     * @property {object} i18n           Pre-translated UI strings.
     * @property {string} i18n.syncing   Status text while a sync is running.
     * @property {string} i18n.testing   Status text while a test is running.
     * @property {string} i18n.unknownError  Fallback when fetch itself fails.
     * @property {string} i18n.summary   Heading prefix for the result panel.
     */

    /** @type {PcOptions} */
    const options = Joomla.getOptions('com_cwmconnect.pc');

    if (!options) {
        return;
    }

    const card    = document.getElementById('pc-sync-card');
    const status  = document.getElementById('pc-sync-status');
    const result  = document.getElementById('pc-sync-result');

    if (!card || !status || !result) {
        return;
    }

    /**
     * Replace a container's children. Pure-DOM helper used everywhere we'd
     * otherwise be tempted to assign innerHTML.
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
     * Show a transient "working…" message while a request is in flight.
     *
     * @param {string} message
     */
    const showSpinner = (message) => {
        const wrap    = make('div', 'd-inline-flex align-items-center gap-2');
        const spinner = make('span', 'spinner-border spinner-border-sm');
        spinner.setAttribute('aria-hidden', 'true');
        const label   = make('span', null, message);

        wrap.appendChild(spinner);
        wrap.appendChild(label);

        replaceChildren(status, [wrap]);
        replaceChildren(result, []);
    };

    /**
     * Render one row of the SyncReport summary table.
     *
     * @param {string} label
     * @param {number} value
     * @returns {HTMLTableRowElement}
     */
    const reportRow = (label, value) => {
        const tr = make('tr');
        tr.appendChild(make('th', 'pe-3', label));
        tr.appendChild(make('td', null, String(value)));

        return tr;
    };

    /**
     * Render the SyncReport summary table.
     *
     * @param {object} data   The data field from the JsonResponse envelope
     *                        (i.e. SyncReport::toArray()).
     */
    const renderReport = (data) => {
        if (!data || typeof data !== 'object') {
            return;
        }

        const heading = make('h5', null, options.i18n.summary);
        const table   = make('table', 'table table-sm table-borderless w-auto');
        const tbody   = make('tbody');

        tbody.appendChild(reportRow('Seen',       Number(data.seen)       || 0));
        tbody.appendChild(reportRow('Added',      Number(data.added)      || 0));
        tbody.appendChild(reportRow('Updated',    Number(data.updated)    || 0));
        tbody.appendChild(reportRow('Unarchived', Number(data.unarchived) || 0));
        tbody.appendChild(reportRow('Archived',   Number(data.archived)   || 0));
        tbody.appendChild(reportRow('Errors',     Number(data.errorCount) || 0));

        table.appendChild(tbody);

        replaceChildren(result, [heading, table]);
    };

    /**
     * Issue a POST against a Joomla AJAX controller endpoint. Posts the CSRF
     * token via a hidden form field per Joomla convention (the controller's
     * `checkToken()` call validates it).
     *
     * @param {string} url
     * @returns {Promise<{success: boolean, message: string, messages: ?object, data: *}>}
     */
    const callEndpoint = async (url) => {
        const body = new FormData();
        body.append(options.csrfToken, '1');

        const response = await fetch(url, {
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
        } catch (e) {
            return {
                success: false,
                message: options.i18n.unknownError,
                data:    null,
            };
        }
    };

    /**
     * Handle a click on a `[data-pc-action]` button. Dispatches to the
     * right endpoint and manages the spinner / status / result panels.
     *
     * @param {string} action  'test' or 'sync'.
     */
    const handleAction = async (action) => {
        const url = action === 'sync' ? options.syncUrl : options.testUrl;

        showSpinner(action === 'sync' ? options.i18n.syncing : options.i18n.testing);

        const json = await callEndpoint(url);

        if (!json) {
            showStatus('danger', options.i18n.unknownError);
            return;
        }

        const variant = json.success ? 'success' : (json.messages ? 'warning' : 'danger');

        showStatus(variant, json.message || '');

        if (action === 'sync' && json.success) {
            renderReport(json.data);
        }
    };

    card.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-pc-action]');

        if (!trigger) {
            return;
        }

        event.preventDefault();

        handleAction(trigger.getAttribute('data-pc-action'));
    });
})(document, window.Joomla);
