(function () {
    'use strict';

    const pendingComponentIds = new Set();

    function notify(type, message) {
        const helper = type === 'success' ? window.notifySuccess : window.notifyError;

        if (typeof helper === 'function') {
            helper(message);
            return;
        }

        if (typeof window.showNotification === 'function') {
            window.showNotification(message, type);
        }
    }

    async function responseError(response) {
        let payload = null;

        try {
            payload = await response.json();
        } catch (error) {
            // The response may be HTML when the session expires.
        }

        if (payload?.errors) {
            const firstError = Object.values(payload.errors).flat().find(Boolean);
            if (firstError) return String(firstError);
        }

        return payload?.message || 'The KIT position could not be updated.';
    }

    function componentElements(componentId) {
        return Array.from(document.querySelectorAll(
            '[data-kit-prl-component-id="' + CSS.escape(componentId) + '"]'
        ));
    }

    function optionIsCrossedOut(element) {
        return element.dataset.kitPrlControllerCrossedOut === '1'
            || element.dataset.kitPrlManualCrossedOut === '1';
    }

    function refreshRows(elements) {
        const rows = new Set(elements.map((element) => element.closest('.data-row-prl')).filter(Boolean));

        rows.forEach((row) => {
            const options = Array.from(row.querySelectorAll('[data-kit-prl-component-id]'));
            const crossedOut = options.length > 0 && options.every(optionIsCrossedOut);

            row.classList.toggle('prl-row-crossed-out', crossedOut);
            if (crossedOut) {
                row.dataset.prlCrossedOut = '1';
            } else {
                delete row.dataset.prlCrossedOut;
            }
        });
    }

    function applyManualState(componentId, crossedOut) {
        const elements = componentElements(componentId);

        elements.forEach((element) => {
            const resultingCrossedOut = element.dataset.kitPrlControllerCrossedOut === '1' || crossedOut;

            element.dataset.kitPrlManualCrossedOut = crossedOut ? '1' : '0';
            element.classList.toggle('kit-prl-option-crossed-out', resultingCrossedOut);
            if (element.classList.contains('prl-part-number-line')) {
                element.classList.toggle('prl-part-number-crossed-out', resultingCrossedOut);
                if (resultingCrossedOut) {
                    element.dataset.prlPartNumberCrossedOut = '1';
                } else {
                    delete element.dataset.prlPartNumberCrossedOut;
                }
            } else {
                delete element.dataset.prlPartNumberCrossedOut;
            }
        });

        refreshRows(elements);
        return elements;
    }

    async function updateCrossout(target, crossedOut) {
        const componentId = target.dataset.kitPrlComponentId;
        const url = target.dataset.kitPrlToggleUrl;

        if (!componentId || !url || pendingComponentIds.has(componentId)) return;

        pendingComponentIds.add(componentId);
        const elements = applyManualState(componentId, crossedOut);
        elements.forEach((element) => element.setAttribute('aria-busy', 'true'));

        try {
            const response = await fetch(url, {
                method: 'PATCH',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window.kitPrlCrossoutConfig?.csrfToken || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ crossed_out: crossedOut }),
            });

            if (!response.ok) {
                throw new Error(await responseError(response));
            }

            const payload = await response.json();
            applyManualState(componentId, Boolean(payload.crossed_out));
        } catch (error) {
            applyManualState(componentId, ! crossedOut);
            notify('error', error instanceof Error ? error.message : 'The KIT position could not be updated.');
        } finally {
            pendingComponentIds.delete(componentId);
            elements.forEach((element) => element.removeAttribute('aria-busy'));
        }
    }

    async function handleToggle(event) {
        const origin = event.target instanceof Element ? event.target : null;
        const target = origin?.closest('.kit-prl-manual-toggle[data-kit-prl-toggle-url]');
        if (!target) return;

        event.preventDefault();

        if (target.dataset.kitPrlControllerCrossedOut === '1') return;

        const crossedOut = target.dataset.kitPrlManualCrossedOut !== '1';
        await updateCrossout(target, crossedOut);
    }

    document.addEventListener('click', handleToggle);
    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Enter' && event.key !== ' ') return;
        const origin = event.target instanceof Element ? event.target : null;
        if (!origin?.closest('.kit-prl-manual-toggle[data-kit-prl-toggle-url]')) return;
        handleToggle(event);
    });
})();
