document.addEventListener('DOMContentLoaded', () => {
    const opener = document.getElementById('openAddUnitModal');
    const modal = document.getElementById('addUnitModal');
    if (!opener || !modal) return;
    let form, baseline = '', busy = false, closing = false, pendingManual = null;
    const snapshot = () => form ? JSON.stringify(Array.from(new FormData(form))) : '';
    const dirty = () => form && snapshot() !== baseline;
    const error = message => {
        const box = modal.querySelector('#addUnitError');
        if (box) { box.textContent = message; box.classList.remove('d-none'); }
        else window.showNotification?.(message, 'error');
    };
    function papers(container, types) {
        container.replaceChildren();
        types.forEach(type => container.append(modal.querySelector('#addUnitPaper' + type).content.cloneNode(true)));
    }
    function blink(input) {
        input._emptyDateAnimation?.cancel();
        const borderColor = getComputedStyle(input).borderColor;
        input._emptyDateAnimation = input.animate([
            {borderColor, boxShadow: 'none'},
            {borderColor: '#dc3545', boxShadow: '0 0 0 2px #dc354555', offset: .5},
            {borderColor, boxShadow: 'none'}
        ], {duration: 600, iterations: 3});
    }
    function trainingFriday(value) {
        const match = value.trim().match(/^(\d{1,2})\/([A-Za-z]{3})\/(\d{4})$/);
        if (!match) return null;
        const month = ['jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec'].indexOf(match[2].toLowerCase());
        const date = new Date(Date.UTC(+match[3], month, +match[1]));
        if (month < 0 || date.getUTCMonth() !== month || date.getUTCDate() !== +match[1]) return null;
        const now = new Date(); const today = Date.UTC(now.getFullYear(), now.getMonth(), now.getDate());
        if (+date > today) return null;
        date.setUTCDate(date.getUTCDate() - (date.getUTCDay() + 6) % 7 + 4);
        if (+date > today) date.setUTCDate(date.getUTCDate() - 7);
        return +date;
    }
    function refreshHours() {
        const select = form.querySelector('#manuals_id');
        const rows = [...form.querySelectorAll('.unit-date-row')];
        rows.forEach(row => row.querySelector('.unit-hours').textContent = '—');
        if (!select.value) return;
        const dailyHours = Number(select.selectedOptions[0].dataset.trainingHours || 0);
        const dates = rows.map(row => ({row, date: trainingFriday(row.querySelector('input').value)}))
            .filter(item => item.date !== null).sort((a, b) => a.date - b.date);
        let previous = null;
        dates.forEach(({row, date}) => {
            // Same normalized Friday creates only one Form 112 on the server.
            const hours = date === previous ? 0 : previous === null || (date - previous) / 86400000 >= 365 ? dailyHours * 5 : 10;
            row.querySelector('.unit-hours').textContent = `${hours} hrs`;
            previous = date;
        });
    }
    function dateRow(input, first = false) {
        const row = document.createElement('div');
        row.className = 'unit-date-row';
        const label = document.createElement('span'); label.className = 'text-muted'; label.textContent = first ? 'First date' : 'Form 112';
        const icons = document.createElement('div'); icons.className = 'unit-papers';
        const hours = document.createElement('span'); hours.className = 'unit-hours small text-end text-nowrap'; hours.textContent = '—';
        row.append(label, input, hours, icons);
        const refresh = () => { papers(icons, input.value.trim() ? (first ? ['132', '112'] : ['112']) : []); refreshHours(); };
        input.addEventListener('input', refresh); input.addEventListener('change', refresh);
        if (!first) {
            const remove = document.createElement('button'); remove.type = 'button'; remove.className = 'btn btn-outline-danger btn-sm';
            remove.setAttribute('aria-label', 'Remove date'); remove.innerHTML = '<i class="bi bi-trash"></i>';
            remove.onclick = () => { input._projectDatePicker?.destroy(); row.remove(); refreshHours(); };
            row.append(remove);
        }
        refresh(); return row;
    }
    opener.addEventListener('click', async event => {
        event.preventDefault(); if (busy) return;
        pendingManual = null;
        busy = true;
        try {
            const url = new URL(opener.href); url.searchParams.set('modal', '1');
            const response = await fetch(url, {headers: {'Accept': 'text/html'}});
            if (!response.ok || response.redirected) throw new Error('Could not load Add Component. Please refresh the page.');
            modal.querySelector('.modal-content').getAnimations().forEach(animation => animation.cancel());
            modal.querySelector('.modal-content').innerHTML = await response.text();
            form = modal.querySelector('form'); if (!form) throw new Error('Add Component form is unavailable.');
            const actions = form.querySelector('.text-end'); actions.className = 'modal-footer';
            actions.querySelectorAll('a').forEach(link => link.remove());
            const save = actions.querySelector('[type=submit]'); save.textContent = 'Save'; save.classList.remove('mt-3');
            save.setAttribute('form', form.id);
            modal.querySelector('.modal-body').after(actions);
            const cancel = document.createElement('button'); cancel.type = 'button'; cancel.className = 'btn btn-secondary'; cancel.textContent = 'Cancel'; cancel.dataset.bsDismiss = 'modal'; actions.prepend(cancel);
            cancel.removeAttribute('data-bs-dismiss');
            cancel.addEventListener('click', () => {
                if (busy) return;
                pendingManual = null;
                baseline = snapshot();
                bootstrap.Modal.getInstance(modal)?.hide();
            });
            const firstGroup = form.querySelector('#date_training_group');
            const firstInput = firstGroup.querySelector('input'); firstGroup.replaceChildren(dateRow(firstInput, true));
            form.querySelector('#add_training_date_btn').onclick = () => {
                let missing = false;
                if (!select.value) { blink(form.querySelector('.select2-selection')); missing = true; }
                if (!firstInput.value.trim()) { blink(firstInput); missing = true; }
                if (missing) return;
                const input = document.createElement('input'); input.type = 'text'; input.name = 'training_dates[]'; input.className = 'form-control form-control-sm';
                input.dataset.projectDate = ''; input.autocomplete = 'off'; input.placeholder = 'dd/Mmm/yyyy'; input.required = true;
                const row = dateRow(input); form.querySelector('#training_dates_list').append(row); window.initProjectDatePickers?.(row); input.focus();
            };
            const select = form.querySelector('#manuals_id');
            const defaultMatcher = $.fn.select2.defaults.defaults.matcher;
            $(select).select2({theme: 'bootstrap-5', dropdownParent: $(modal), width: '100%', placeholder: '---', allowClear: true,
                matcher: (params, data) => {
                    if (data.element?.dataset.alreadyAdded && !(params.term || '').trim()) return null;
                    return defaultMatcher(params, data);
                },
                templateResult: data => data.element?.dataset.alreadyAdded
                    ? $('<span>').addClass('text-muted').text(data.text) : data.text
            });
            $(select).on('select2:selecting', event => {
                const item = event.params.args.data;
                if (!item.element?.dataset.alreadyAdded) return;
                event.preventDefault();
                if (busy) return;
                pendingManual = String(item.id);
                $(select).select2('close');
                bootstrap.Modal.getInstance(modal)?.hide();
            });
            $(select).on('change', () => { modal.querySelector('#addUnitPartTitle').textContent = select.value ? select.selectedOptions[0].textContent.trim() : ''; refreshHours(); });
            const legacy = form.querySelector('#is_legacy');
            legacy?.addEventListener('change', () => {
                const on = legacy.checked;
                form.querySelector('#single_manual_group').style.display = on ? 'none' : '';
                form.querySelector('#legacy_manuals_group').style.display = on ? '' : 'none';
                firstGroup.style.display = on ? 'none' : '';
                form.querySelector('#add_training_date_btn').parentElement.style.display = on ? 'none' : '';
                select.required = firstInput.required = !on;
                form.querySelectorAll('[name="training_dates[]"]').forEach(input => input.disabled = on);
            });
            form.querySelector('#legacy_create_132')?.addEventListener('change', event => {
                form.querySelector('#legacy_132_date_group').style.display = event.target.checked ? '' : 'none';
            });
            window.initProjectDatePickers?.(form);
            baseline = snapshot();
            form.addEventListener('submit', async event => {
                event.preventDefault(); if (busy || !form.reportValidity()) return;
                if (!window.TrainingWeekAvailability || !await window.TrainingWeekAvailability.checkAll(form)) return;
                busy = true; save.disabled = true; save.style.visibility = 'hidden'; form.inert = true; modal.querySelector('#addUnitError').classList.add('d-none');
                try {
                    const response = await fetch(form.action, {method: 'POST', headers: {'Accept': 'application/json'}, body: new FormData(form)});
                    const result = await response.json();
                    if (!response.ok || !result.success) throw new Error(Object.values(result.errors || {}).flat().join(' ') || result.message || 'Could not save training.');
                    baseline = snapshot();
                    const content = modal.querySelector('.modal-content');
                    await content.animate([
                        {height: content.getBoundingClientRect().height + 'px', minHeight: '0px', overflow: 'hidden', opacity: 1},
                        {height: '0px', minHeight: '0px', overflow: 'hidden', opacity: 0}
                    ], {duration: 400, easing: 'ease-in', fill: 'forwards'}).finished;
                    busy = false;
                    modal.addEventListener('hidden.bs.modal', () => location.reload(), {once: true});
                    bootstrap.Modal.getInstance(modal).hide();
                } catch (e) { error(e.message); save.style.visibility = ''; }
                finally { busy = false; save.disabled = false; if (form) form.inert = false; }
            });
            bootstrap.Modal.getOrCreateInstance(modal).show();
        } catch (e) { error(e.message); }
        finally { busy = false; }
    });
    modal.addEventListener('hide.bs.modal', async event => {
        if (busy) { event.preventDefault(); return; }
        if (!dirty()) return;
        event.preventDefault(); if (closing) return;
        if (!window.confirmDialog) { error('Confirmation dialog is unavailable.'); return; }
        closing = true;
        try {
            const answer = window.confirmDialog({title: 'Unsaved training changes', message: 'Close without saving the added unit and dates?', okText: 'Discard changes', cancelText: 'Continue editing', danger: true});
            const closed = new Promise(resolve => document.getElementById('globalConfirmModal').addEventListener('hidden.bs.modal', resolve, {once: true}));
            const discard = await answer; await closed; document.body.classList.add('modal-open');
            if (discard) { baseline = snapshot(); bootstrap.Modal.getInstance(modal).hide(); }
            else pendingManual = null;
        } finally { closing = false; }
    });
    modal.addEventListener('hidden.bs.modal', () => {
        if (form) {
            form.querySelectorAll('[data-project-date]').forEach(input => input._projectDatePicker?.destroy());
            const select = form.querySelector('#manuals_id'); if ($(select).hasClass('select2-hidden-accessible')) $(select).select2('destroy');
        }
        form = null;
        if (pendingManual) {
            const manualId = pendingManual;
            pendingManual = null;
            document.dispatchEvent(new CustomEvent('training:select-component', {detail: {manualId}}));
        }
    });
});
