/**
 * Geocoding worker driver for com_cwmconnect.
 *
 * Walks the geoupdate queue by POSTing to two endpoints:
 *   task=geoupdate.start  → reset + first slice
 *   task=geoupdate.slice  → next slice
 *
 * The controller returns JSON { state, total, done, percent }. We loop on
 * `slice` until state === 'done', updating the progress bar between calls.
 *
 * Expected DOM, rendered by admin/tmpl/geostatus/default.php:
 *   <div id="geoupdateModal" class="modal ...">  ← Bootstrap 5 modal
 *     <h5 id="geoupdateHeading">…</h5>
 *     <div class="progress"><div id="geoupdateBar" ...></div></div>
 *     <p id="geoupdateStatus">…</p>
 *     <button type="button" data-geoupdate-start>Run</button>
 *   </div>
 *
 * CSRF token is read from Joomla.getOptions('csrf.token'), which the
 * admin template populates from the session — same source Joomla core
 * AJAX callers use.
 */
(function () {
    'use strict';

    const ENDPOINT_BASE = 'index.php?option=com_cwmconnect';

    function getToken() {
        if (typeof window.Joomla === 'object' && typeof window.Joomla.getOptions === 'function') {
            const token = window.Joomla.getOptions('csrf.token', '');
            if (token) {
                return token;
            }
        }
        // Fallback: read the first hidden input whose value is "1" on the
        // admin form (Joomla's HTMLHelper::form.token convention).
        const fallback = document.querySelector('#adminForm input[type=hidden][value="1"]');
        return fallback ? fallback.name : '';
    }

    async function callTask(task, id) {
        const token = getToken();
        if (!token) {
            throw new Error('CSRF token unavailable');
        }
        const body = new URLSearchParams();
        body.set('task', task);
        body.set(token, '1');
        if (id) {
            body.set('id', String(id));
        }

        const response = await fetch(ENDPOINT_BASE, {
            method:      'POST',
            credentials: 'same-origin',
            headers:     { 'X-Requested-With': 'XMLHttpRequest' },
            body,
        });

        if (!response.ok) {
            throw new Error('HTTP ' + response.status);
        }

        // Joomla's JsonResponse wraps payload under "data".
        const envelope = await response.json();
        if (envelope && envelope.success === false) {
            throw new Error(envelope.message || 'Worker error');
        }
        return envelope && envelope.data ? envelope.data : envelope;
    }

    function renderProgress(state, root) {
        const bar    = root.querySelector('#geoupdateBar');
        const status = root.querySelector('#geoupdateStatus');

        if (bar) {
            bar.style.width = state.percent + '%';
            bar.setAttribute('aria-valuenow', String(state.percent));
            bar.textContent = state.percent + '%';
        }
        if (status) {
            status.textContent = state.done + ' / ' + state.total;
        }
    }

    async function drive(root, id) {
        const startButton = root.querySelector('[data-geoupdate-start]');
        if (startButton) {
            startButton.disabled = true;
        }

        try {
            let state = await callTask('geoupdate.start', id);
            renderProgress(state, root);

            while (state.state !== 'done') {
                state = await callTask('geoupdate.slice', id);
                renderProgress(state, root);
            }

            const heading = root.querySelector('#geoupdateHeading');
            if (heading) {
                heading.textContent = heading.dataset.doneLabel || 'Done';
            }
        } catch (err) {
            const status = root.querySelector('#geoupdateStatus');
            if (status) {
                status.textContent = 'Error: ' + err.message;
            }
        } finally {
            if (startButton) {
                startButton.disabled = false;
            }
        }
    }

    function init() {
        const root = document.getElementById('geoupdateModal');
        if (!root) {
            return;
        }

        root.addEventListener('click', function (event) {
            const trigger = event.target.closest('[data-geoupdate-start]');
            if (!trigger) {
                return;
            }
            event.preventDefault();
            const id = trigger.getAttribute('data-geoupdate-id') || 0;
            drive(root, id ? Number(id) : 0);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
