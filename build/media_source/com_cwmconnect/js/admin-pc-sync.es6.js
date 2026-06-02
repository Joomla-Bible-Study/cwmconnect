/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 *
 * @since   2.0.0
 */

/**
 * Wires up the Planning Center card on the Cpanel: a "Test connection"
 * button that probes /people/v2/me and a "Sync now" button that runs one
 * SyncEngine pass. Both call the same admin controller via fetch() and
 * render the JsonResponse envelope into the status / result panels.
 *
 * While a sync is running, a polling loop hits the pcSyncProgress endpoint
 * every second and updates the status area with real page/member counts so
 * the user sees intermediate feedback instead of a frozen spinner.
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
     * @property {string} csrfToken        Joomla session form token.
     * @property {string} syncUrl          Route to task=cpanel.pcSync.
     * @property {string} testUrl          Route to task=cpanel.pcTestConnection.
     * @property {string} progressUrl      Route to task=cpanel.pcSyncProgress.
     * @property {object} i18n             Pre-translated UI strings.
     * @property {string} i18n.syncing     Status text while a sync is running.
     * @property {string} i18n.testing     Status text while a test is running.
     * @property {string} i18n.unknownError  Fallback when fetch itself fails.
     * @property {string} i18n.summary     Heading prefix for the result panel.
     * @property {string} i18n.progressPage   "Processing page %s… %s members so far"
     * @property {string} i18n.progressSweep  "Archiving removed members…"
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

    /** @type {number|null} */
    let pollTimer = null;

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
     * Show a spinner + message in the status area.
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
     * Update the spinner label text in-place without rebuilding the DOM.
     * Falls back to showSpinner() if the expected structure isn't present.
     *
     * @param {string} message
     */
    const updateSpinnerText = (message) => {
        const label = status.querySelector('span:not(.spinner-border)');

        if (label) {
            label.textContent = message;
        } else {
            showSpinner(message);
        }
    };

    /**
     * Simple sprintf-style replacer for Joomla language strings that use
     * %s placeholders (positional, left-to-right).
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

        tbody.appendChild(reportRow('Seen',       Number(data.seen)             || 0));
        tbody.appendChild(reportRow('Added',      Number(data.added)            || 0));
        tbody.appendChild(reportRow('Updated',    Number(data.updated)          || 0));
        tbody.appendChild(reportRow('Households',  Number(data.householdsLinked) || 0));
        tbody.appendChild(reportRow('Deleted',    Number(data.deleted)          || 0));
        tbody.appendChild(reportRow('Errors',     Number(data.errorCount)       || 0));

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
        } catch {
            return {
                success: false,
                message: options.i18n.unknownError,
                data:    null,
            };
        }
    };

    /**
     * Stop the progress polling loop.
     */
    const stopPolling = () => {
        if (pollTimer !== null) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
    };

    /**
     * Start polling the progress endpoint. Updates the spinner text with
     * real page/member counts from the server.
     */
    const startPolling = () => {
        stopPolling();

        pollTimer = setInterval(async () => {
            try {
                const json = await callEndpoint(options.progressUrl);

                if (!json || !json.success || !json.data || !json.data.running) {
                    return;
                }

                const d = json.data;

                if (d.phase === 'sweeping') {
                    updateSpinnerText(options.i18n.progressSweep);
                } else if (d.phase === 'fetching' && d.pagesCompleted > 0) {
                    updateSpinnerText(
                        sprintf(options.i18n.progressPage, String(d.pagesCompleted), String(d.totalSeen)),
                    );
                }
            } catch {
                // Polling failure is non-fatal — the sync POST still completes.
            }
        }, 1000);
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

        if (action === 'sync') {
            startPolling();
        }

        const json = await callEndpoint(url);

        stopPolling();

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
