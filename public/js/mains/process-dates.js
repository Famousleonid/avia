document.addEventListener('DOMContentLoaded', () => {
    function initDatePickers() {
        if (typeof flatpickr === 'undefined') return;

        document.querySelectorAll('input[data-fp]').forEach(src => {
            if (src._flatpickr || src.getClientRects().length === 0) return;
            // Disabled dates still need the formatted altInput; Flatpickr preserves disabled.
            const isLocked = src.hasAttribute('data-fp-locked');

            flatpickr(src, {
                altInput: true,
                altFormat: "d/M/Y",
                dateFormat: "Y-m-d",
                disableMobile: true,
                clickOpens: !isLocked,
                allowInput: !isLocked,

                onChange(selectedDates, dateStr, instance) {
                    if (src.disabled || src.hasAttribute('data-fp-locked')) return;

                    const filled = String(dateStr || '').trim() !== '';
                    if (src.classList.contains('finish-input')) {
                        src.classList.toggle('has-finish', filled);
                    }
                    const altIn = instance.altInput;
                    if (altIn && altIn.classList.contains('finish-input')) {
                        altIn.classList.toggle('has-finish', filled);
                    }

                    const form = src.closest('form');
                    if (!form) return;

                    if (typeof window.refreshWoBushingStripCounts === 'function') {
                        window.refreshWoBushingStripCounts(form);
                    }

                    if (form.requestSubmit) form.requestSubmit();
                    else form.submit();
                },

                onReady(selectedDates, dateStr, instance) {
                    instance.altInput.classList.add('form-control', 'form-control-sm', 'w-100', 'fp-alt');
                    if (src.title) instance.altInput.title = src.title;
                    const label = src.getAttribute('aria-label');
                    if (label) instance.altInput.setAttribute('aria-label', label);

                    if (src.classList.contains('finish-input')) instance.altInput.classList.add('finish-input');
                    if (src.value) instance.altInput.classList.add('has-finish');

                    if (isLocked) {
                        instance.altInput.readOnly = true;
                        instance.altInput.setAttribute('tabindex', '-1');
                        instance.altInput.classList.add('fp-locked');
                    }

                    // Firefox-friendly explicit calendar trigger icon
                    const alt = instance.altInput;
                    const parent = alt?.parentElement;
                    if (alt && parent && !parent.classList.contains('fp-alt-wrap')) {
                        parent.classList.add('fp-alt-wrap');
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'fp-cal-btn';
                        btn.setAttribute('tabindex', '-1');
                        btn.innerHTML = '<i class="bi bi-calendar3"></i>';
                        btn.addEventListener('click', (e) => {
                            e.preventDefault();
                            e.stopPropagation();
                            if (src.disabled || src.hasAttribute('data-fp-locked')) return;
                            try { instance.open(); } catch (_) {}
                        });
                        parent.appendChild(btn);
                    }

                    src.style.display = 'none';
                }
            });
        });

        document.body.classList.add('fp-ready');
    }
    // Reinitialize when another Main panel becomes visible.
    window.__mainsInitDatePickers = initDatePickers;
    // Hidden accordion dates become interactive only when their section opens.
    document.addEventListener('shown.bs.collapse', initDatePickers);
    initDatePickers();
});

// Bushing batch dates use the same record as the Bushing Processes status badges.
document.addEventListener('submit', async function (event) {
    const form = event.target.closest('form[data-bushing-batch-dates]');
    if (!form) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    const input = form.querySelector('input[data-fp]');
    const restore = () => {
        input._flatpickr?.setDate(input.dataset.original || '', false, 'Y-m-d');
        const filled = String(input.dataset.original || '') !== '';
        input.classList.toggle('has-finish', filled);
        input._flatpickr?.altInput?.classList.toggle('has-finish', filled);
        window.refreshWoBushingStripCounts?.(form);
    };
    if (typeof window.confirmDialog !== 'function') {
        restore();
        window.notifyError?.('Confirmation dialog is unavailable. Date was not saved.');
        return;
    }
    if (!await window.confirmDialog({title: 'Machining', message: 'Save this batch date?', okText: 'Save'})) {
        restore();
        return;
    }
    await window.ajaxSubmit(form);
}, true);
