(() => {
    if (window.TrainingWeekCalendar) return;
    const endpoint = document.currentScript.dataset.endpoint;
    let modal, context, request, activeWeek;
    const monthOf = date => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
    function draftWeeks(scope) {
        const weeks = new Set();
        scope?.querySelectorAll('.pending-training-date-input, input[name="training_dates[]"]').forEach(input => {
            const date = input._projectDatePicker?.selectedDates?.[0];
            if (!date || input.disabled) return;
            const friday = new Date(date);
            friday.setDate(friday.getDate() - (friday.getDay() + 6) % 7 + 4);
            weeks.add(`${monthOf(friday)}-${String(friday.getDate()).padStart(2, '0')}`);
        });
        return weeks;
    }
    function build() {
        modal = document.createElement('div');
        modal.id = 'trainingWeekCalendar';
        modal.className = 'modal fade';
        modal.tabIndex = -1;
        modal.setAttribute('aria-labelledby', 'trainingWeekCalendarTitle');
        modal.innerHTML = `<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
            <div class="modal-header"><div><h5 class="modal-title" id="trainingWeekCalendarTitle">Training weeks</h5><div class="small text-muted" data-employee></div></div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">
                <div class="training-calendar-navigation">
                    <button type="button" class="btn btn-outline-secondary" data-step="-1" aria-label="Previous year"><i class="bi bi-chevron-left" aria-hidden="true"></i></button>
                    <input type="number" min="1900" max="9998" step="1" class="form-control" aria-label="Calendar year" data-year>
                    <button type="button" class="btn btn-outline-secondary" data-step="1" aria-label="Next year"><i class="bi bi-chevron-right" aria-hidden="true"></i></button>
                </div>
                <div class="training-calendar-legend"><span data-legend="free">● Free</span><span data-legend="occupied">▣ Up to 40 hrs</span><span data-legend="conflict">▣ Over 40 hrs</span><span data-legend="draft">◷ Unsaved</span><span data-legend="future">— Future</span></div>
                <div class="small text-muted mb-2" data-range></div>
                <div class="training-timeline-scroll" tabindex="0" aria-label="54 training weeks; scroll horizontally"><div data-weeks aria-live="polite"></div></div>
                <div data-conflicts hidden></div>
                <div class="small text-muted mt-3" data-calendar-help></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
        </div></div>`;
        document.body.append(modal);
        modal.querySelector('[data-year]').addEventListener('change', load);
        modal.addEventListener('click', event => { if (!event.target.closest('.training-timeline-week')) hideTooltip(); });
        modal.querySelector('.training-timeline-scroll').addEventListener('scroll', hideTooltip);
        window.addEventListener('resize', hideTooltip);
        modal.querySelectorAll('[data-step]').forEach(button => button.addEventListener('click', () => {
            const input = modal.querySelector('[data-year]');
            input.value = Math.min(9998, Math.max(1900, Number(input.value) + Number(button.dataset.step)));
            load();
        }));
        modal.addEventListener('hidden.bs.modal', () => {
            request?.abort();
            hideTooltip();
            if (context?.parent) {
                context.parent.inert = context.wasInert;
                if (context.parent.classList.contains('show')) {
                    document.body.classList.add('modal-open');
                    bootstrap.Modal.getInstance(context.parent)?._focustrap?.activate();
                    context.opener?.focus({preventScroll: true});
                }
            }
            if (!context?.parent) context?.opener?.focus({preventScroll: true});
            context = null;
        });
    }
    function hideTooltip() {
        modal?.querySelector('[data-week-tooltip]')?.remove();
        activeWeek?.removeAttribute('aria-describedby');
        activeWeek = null;
    }
    function hasDrafts(scope) {
        if (!scope) return false;
        if (scope.querySelector('.pending-training-row')) return true;
        if (Array.from(scope.querySelectorAll('.add-training-date-input, input[name="training_dates[]"], #date_training')).some(input => input.value.trim())) return true;
        if (scope.querySelector('#manuals_id')?.value) return true;
        return Array.from(scope.querySelectorAll('.edit-training-row')).some(row => {
            const input = row.querySelector('.edit-training-date-input');
            const picker = input?._projectDatePicker;
            const date = picker?.selectedDates?.[0];
            return date && `${monthOf(date)}-${String(date.getDate()).padStart(2, '0')}` !== row.dataset.originalDate;
        });
    }
    async function editConflict(training) {
        if (!context || context.selecting || !training.edit_url) return;
        context.selecting = true;
        const dirty = hasDrafts(context.scope);
        const parent = context.parent;
        const close = element => new Promise(resolve => {
            element.addEventListener('hidden.bs.modal', resolve, {once: true});
            bootstrap.Modal.getInstance(element).hide();
        });
        await close(modal);
        if (parent?.id === 'globalConfirmModal' && parent.classList.contains('show')) await close(parent);
        if (dirty) {
            if (!window.confirmDialog) { window.showNotification?.('Confirmation dialog is unavailable.', 'error'); return; }
            const proceed = await window.confirmDialog({title: 'Unsaved training changes',
                message: 'Open the selected training dates? Unsaved dates in this window will be discarded.',
                okText: 'Open training dates', cancelText: 'Continue editing', danger: true});
            if (!proceed) return;
        }
        window.location.assign(training.edit_url);
    }
    function showConflicts(week) {
        hideTooltip();
        const panel = modal.querySelector('[data-conflicts]');
        panel.replaceChildren(); panel.hidden = false;
        const title = document.createElement('div'); title.className = 'fw-bold mb-2';
        title.textContent = `${week.label} — ${week.total_hours} / ${week.capacity_hours} hrs. Choose a training to correct:`;
        panel.append(title);
        week.trainings.forEach(training => {
            const button = document.createElement('button');
            button.type = 'button'; button.className = 'btn btn-outline-danger btn-sm d-block w-100 mb-2';
            button.textContent = [training.name, training.description].filter(Boolean).join(' — ') + ' · ' + hoursLabel(training.hours)
                + (training.edit_url ? ' · Edit dates' : ' · Locked / no edit permission');
            button.disabled = !training.edit_url;
            button.addEventListener('click', () => editConflict(training));
            panel.append(button);
        });
        panel.scrollIntoView({block: 'nearest', behavior: 'smooth'});
    }
    function hoursLabel(hours) { return hours == null ? 'Hours not set' : `${hours} hrs`; }
    function showTooltip(button, week, status) {
        hideTooltip();
        activeWeek = button;
        const tooltip = document.createElement('div');
        tooltip.className = 'training-week-tooltip'; tooltip.dataset.weekTooltip = '';
        tooltip.id = 'trainingWeekTooltip'; tooltip.setAttribute('role', 'tooltip');
        const header = document.createElement('div');
        header.className = 'training-week-tooltip-header';
        const heading = document.createElement('strong');
        heading.textContent = week.label;
        const total = document.createElement('span');
        total.textContent = ` · ${week.total_hours} / ${week.capacity_hours} hrs · ${week.remaining_hours} hrs available`;
        header.append(heading, total);
        tooltip.append(header);
        week.trainings.forEach(training => {
            const line = document.createElement('div');
            line.className = 'training-week-tooltip-training';
            const name = document.createElement('span');
            name.className = 'training-week-tooltip-name text-info';
            name.textContent = training.name;
            name.title = training.name;
            line.append(name);
            const separator = () => {
                const dot = document.createElement('span');
                dot.className = 'training-week-tooltip-separator';
                dot.textContent = '·';
                return dot;
            };
            if (training.description) {
                const description = document.createElement('span');
                description.className = 'training-week-tooltip-description';
                description.textContent = training.description;
                line.append(separator(), description);
            }
            const hours = document.createElement('span');
            hours.className = 'text-info';
            hours.textContent = hoursLabel(training.hours);
            line.append(separator(), hours);
            tooltip.append(line);
        });
        modal.append(tooltip);
        // Keep the complete date/hour summary on one line within narrow viewports.
        if (header.scrollWidth > header.clientWidth) {
            header.style.fontSize = `${parseFloat(getComputedStyle(header).fontSize) * header.clientWidth / header.scrollWidth}px`;
        }
        button.setAttribute('aria-describedby', tooltip.id);
        const rect = button.getBoundingClientRect();
        tooltip.style.left = Math.max(8, Math.min(innerWidth - tooltip.offsetWidth - 8, rect.left + rect.width / 2 - tooltip.offsetWidth / 2)) + 'px';
        tooltip.style.top = Math.max(8, rect.top - tooltip.offsetHeight - 8) + 'px';
    }
    async function load() {
        request?.abort();
        const controller = new AbortController();
        request = controller;
        const box = modal.querySelector('[data-weeks]');
        hideTooltip();
        modal.querySelector('[data-conflicts]').hidden = true;
        const year = modal.querySelector('[data-year]').value;
        const month = year + '-01';
        box.getAnimations({subtree: true}).forEach(animation => animation.cancel());
        box.querySelectorAll('[data-outgoing]').forEach(frame => frame.remove());
        box.setAttribute('aria-busy', 'true');
        box.inert = true;
        try {
            if (!/^\d{4}$/.test(year) || Number(year) < 1900 || Number(year) > 9998) throw new Error('Select a valid year.');
            const url = new URL(endpoint, location.href);
            url.searchParams.set('month', month);
            url.searchParams.set('user_id', context.userId);
            const response = await fetch(url, {headers: {'Accept': 'application/json'}, signal: controller.signal});
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Could not load training weeks.');
            if (controller.signal.aborted) return;
            modal.querySelector('[data-employee]').textContent = data.employee;
            const drafts = draftWeeks(context.scope);
            modal.querySelector('[data-range]').textContent = data.range;
            const bars = document.createElement('div'); bars.className = 'training-timeline-bars';
            const months = document.createElement('div'); months.className = 'training-timeline-months';
            let group = null;
            data.weeks.forEach((week, index) => {
                const status = week.status === 'free' && drafts.has(week.friday) ? 'draft' : week.status;
                const button = document.createElement('button');
                button.type = 'button'; button.className = 'training-timeline-week'; button.dataset.status = status;
                const name = {free: 'Free', occupied: 'Training hours', conflict: 'Over 40 hours — click to correct', draft: 'Unsaved', future: 'Future'}[status];
                button.setAttribute('aria-label', `${week.label}: ${name}, ${week.total_hours} / ${week.capacity_hours} hrs`);
                const column = document.createElement('span'); column.className = 'training-timeline-column';
                if (status === 'occupied' || status === 'conflict') column.style.height = `${Math.max(8, Math.min(120, week.total_hours / week.capacity_hours * 96))}px`;
                button.append(column);
                const show = () => showTooltip(button, week, name);
                button.addEventListener('mouseenter', show);
                button.addEventListener('focus', show);
                button.dataset.friday = week.friday;
                const selectable = (status === 'free' || status === 'occupied') && week.remaining_hours > 0 && week.friday <= data.today && context.onSelect;
                if (selectable) {
                    button.dataset.selectable = 'true';
                    button.setAttribute('aria-label', `${week.label}: ${week.total_hours} / ${week.capacity_hours} hrs. Select this week`);
                }
                button.addEventListener('click', () => {
                    if (status === 'conflict') { showConflicts(week); return; }
                    if (!selectable || !context?.onSelect) { show(); return; }
                    if (context.selecting) return;
                    context.selecting = true;
                    const select = context.onSelect;
                    // Restore the parent modal's focus and inert state before changing its input.
                    modal.addEventListener('hidden.bs.modal', () => select(week.friday), {once: true});
                    bootstrap.Modal.getInstance(modal).hide();
                });
                button.addEventListener('mouseleave', () => { if (document.activeElement !== button) hideTooltip(); });
                button.addEventListener('blur', hideTooltip);
                bars.append(button);
                if (!group || group.key !== week.month_key) {
                    group = {key: week.month_key, start: index + 1, size: 0, node: document.createElement('span')};
                    group.node.textContent = week.month_label;
                    group.node.title = week.month_key;
                    months.append(group.node);
                }
                group.size++;
                group.node.style.gridColumn = `${group.start} / span ${group.size}`;
            });
            const frame = document.createElement('div');
            frame.className = 'training-timeline-frame';
            frame.append(bars, months);
            const previous = box.querySelector('.training-timeline-frame');
            const direction = Number(year) >= Number(box.dataset.renderedYear || year) ? 1 : -1;
            box.dataset.renderedYear = year;
            if (previous && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                previous.dataset.outgoing = '';
                previous.setAttribute('aria-hidden', 'true');
                previous.inert = true;
                box.append(frame);
                const options = {duration: 380, easing: 'cubic-bezier(.4,0,.2,1)'};
                const outgoing = previous.animate([
                    {transform: 'translateX(0)'},
                    {transform: `translateX(${-direction * 100}%)`}
                ], {...options, fill: 'forwards'});
                const incoming = frame.animate([
                    {transform: `translateX(${direction * 100}%)`},
                    {transform: 'translateX(0)'}
                ], options);
                modal.querySelector('[data-year]').animate([
                    {opacity: .45, transform: `translateX(${direction * 12}px)`},
                    {opacity: 1, transform: 'translateX(0)'}
                ], options);
                Promise.allSettled([outgoing.finished, incoming.finished]).then(() => previous.remove());
            } else {
                box.replaceChildren(frame);
            }

        } catch (error) {
            if (error.name !== 'AbortError' && !controller.signal.aborted) {
                if (box.dataset.renderedYear) modal.querySelector('[data-year]').value = box.dataset.renderedYear;
                window.showNotification?.(error.message, 'error');
            }
        } finally {
            if (request === controller) {
                box.setAttribute('aria-busy', 'false');
                box.inert = false;
            }
        }
    }
    function open({userId, date, opener, scope, onSelect}) {
        if (context) return;
        if (!modal) build();
        const parent = opener?.closest('.modal.show');
        context = {userId, opener, scope: scope || parent, parent, wasInert: parent?.inert, onSelect};
        modal.querySelector('[data-calendar-help]').textContent = onSelect
            ? '40 hrs per week · Click a week with capacity to use its Friday. Hover or tap for training hours.'
            : '40 hrs per week · Hover or tap a column for trainings, individual hours and the weekly total.';
        if (parent) {
            parent.inert = true;
            bootstrap.Modal.getInstance(parent)?._focustrap?.deactivate();
        }
        modal.querySelector('[data-year]').value = date?.slice(0, 4) || String(new Date().getFullYear());
        const box = modal.querySelector('[data-weeks]');
        box.replaceChildren();
        delete box.dataset.renderedYear;
        modal.querySelector('[data-employee]').textContent = '';
        // Keep the calendar and its backdrop above the floating assistant on narrow screens.
        const assistant = document.getElementById('aiAssistantWidget');
        const zIndex = Math.max(1055, Number(parent && getComputedStyle(parent).zIndex) || 0,
            Number(assistant && getComputedStyle(assistant).zIndex) || 0) + 20;
        modal.style.zIndex = zIndex;
        const existingBackdrops = new Set(document.querySelectorAll('.modal-backdrop'));
        bootstrap.Modal.getOrCreateInstance(modal, {backdrop: 'static'}).show();
        document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
            if (!existingBackdrops.has(backdrop)) backdrop.style.zIndex = zIndex - 1;
        });
        load();
    }
    document.addEventListener('click', event => {
        const button = event.target.closest('.training-week-calendar-btn');
        if (!button) return;
        const scope = button.closest('.modal');
        const input = scope?.querySelector('.add-training-date-input');
        const selected = input?._projectDatePicker?.selectedDates?.[0];
        open({userId: button.dataset.userId, date: selected ? monthOf(selected) : null, opener: button, scope,
            onSelect: input && !input.disabled ? friday => {
                if (!input.isConnected) return;
                window.TrainingWeekAvailability.setDate(input, friday);
                input.classList.remove('is-invalid');
                input.dispatchEvent(new Event('change', {bubbles: true}));
                input.focus({preventScroll: true});
            } : null
        });
    });
    window.TrainingWeekCalendar = {open};
})();

