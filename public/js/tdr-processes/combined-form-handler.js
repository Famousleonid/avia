/**
 * Selects any number of visible process rows with the same Process Name and
 * opens them as one process form.
 */
class CombinedProcessFormHandler {
    static init(root) {
        const scope = root && root.querySelector ? root : document;
        const container = scope.matches?.('.processes-modal-body')
            ? scope
            : scope.querySelector('.processes-modal-body');

        if (!container || container.dataset.combinedFormInitialized === '1') {
            return;
        }

        const button = container.querySelector('[data-combined-form-button]');
        if (!button) {
            return;
        }

        container.dataset.combinedFormInitialized = '1';
        container.addEventListener('change', (event) => {
            if (event.target.matches('.combined-form-select')) {
                CombinedProcessFormHandler.refresh(container);
            }
        });
        button.addEventListener('click', () => CombinedProcessFormHandler.open(container));
        CombinedProcessFormHandler.refresh(container);
    }

    static selected(container) {
        return Array.from(container.querySelectorAll('.combined-form-select:checked'));
    }

    static refresh(container) {
        const selected = CombinedProcessFormHandler.selected(container);
        const selectedProcessNameId = selected[0]?.dataset.processNameId || null;
        const button = container.querySelector('[data-combined-form-button]');

        container.querySelectorAll('.combined-form-select').forEach((checkbox) => {
            checkbox.disabled = selectedProcessNameId !== null
                && checkbox.dataset.processNameId !== selectedProcessNameId
                && !checkbox.checked;
        });

        if (button) {
            button.disabled = selected.length < 2;
            button.textContent = `Combined Form (${selected.length})`;
            button.title = selected.length < 2
                ? 'Select at least two processes with the same name'
                : `Open one form for ${selected.length} processes`;
        }
    }

    static open(container) {
        const selected = CombinedProcessFormHandler.selected(container);
        if (selected.length < 2) {
            CombinedProcessFormHandler.notify('Select at least two processes with the same name.', 'warning');
            return;
        }

        const processNameIds = new Set(selected.map((checkbox) => checkbox.dataset.processNameId));
        if (processNameIds.size !== 1) {
            CombinedProcessFormHandler.notify('All selected processes must have the same Process Name.', 'error');
            return;
        }

        const selections = selected.map((checkbox) => ({
            tdr_process_id: Number(checkbox.dataset.tdrProcessId),
            process_id: checkbox.dataset.processId ? Number(checkbox.dataset.processId) : null,
        }));
        const vendorIds = new Set(
            selected
                .map((checkbox) => checkbox.closest('tr')?.querySelector('.vendor-select')?.value || '')
                .filter(Boolean)
        );

        if (vendorIds.size > 1) {
            CombinedProcessFormHandler.notify('Selected rows have different vendors. Select one vendor for the combined form.', 'warning');
            return;
        }

        const baseUrl = container.dataset.combinedFormUrl;
        if (!baseUrl) {
            CombinedProcessFormHandler.notify('Combined form URL is not configured.', 'error');
            return;
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (!csrfToken) {
            CombinedProcessFormHandler.notify('Unable to open the combined form: CSRF token is missing.', 'error');
            return;
        }

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = new URL(baseUrl, window.location.origin).toString();
        form.target = '_blank';
        form.className = 'd-none';
        const fields = {
            _token: csrfToken,
            selections: JSON.stringify(selections),
        };
        const vendorId = Array.from(vendorIds)[0];
        if (vendorId) {
            fields.vendor_id = vendorId;
        }
        if (container.dataset.omitFormHeaderDate === '1') {
            fields.omit_form_header_date = '1';
        }

        Object.entries(fields).forEach(([name, value]) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;
            form.appendChild(input);
        });
        document.body.appendChild(form);
        form.submit();
        form.remove();
    }

    static notify(message, type) {
        if (typeof window.tdrShowNotify === 'function') {
            window.tdrShowNotify(message, type);
            return;
        }

        const handler = window.NotificationHandler;
        if (handler && typeof handler[type] === 'function') {
            handler[type](message);
            return;
        }

        console[type === 'error' ? 'error' : 'warn'](message);
    }
}

if (typeof module !== 'undefined' && module.exports) {
    module.exports = CombinedProcessFormHandler;
}
