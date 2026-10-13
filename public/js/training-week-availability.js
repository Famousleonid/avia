(() => {
    if (window.TrainingWeekAvailability) return;
    const config = document.currentScript.dataset;
    const selector = '#date_training, input[name="training_dates[]"], .add-training-date-input, .pending-training-date-input, .edit-training-date-input, #matrixTrainDate, [data-training-week]';
    const pending = new WeakMap();
    let dialogs = Promise.resolve();
    const notify = message => window.showNotification?.(message, 'error');
    function dateOf(input) {
        const value = input.value.trim();
        if (/^\d{4}-\d{2}-\d{2}$/.test(value)) return value;
        const match = value.match(/^(\d{2})\/([a-z]{3})\/(\d{4})$/i);
        const month = match && ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'].indexOf(match[2].toLowerCase());
        return match && month >= 0 ? `${match[3]}-${String(month + 1).padStart(2, '0')}-${match[1]}` : null;
    }
    function setDate(input, value) {
        if (input._projectDatePicker) input._projectDatePicker.setDate(new Date(value + 'T00:00:00'), false);
        else if (input.type === 'date') input.value = value;
        else {
            const [year, month, day] = value.split('-');
            input.value = `${day}/${['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'][Number(month) - 1]}/${year}`;
        }
        input.dispatchEvent(new Event('input', {bubbles: true}));
    }
    function applicable(input) {
        return !input.disabled && input.getClientRects().length && input.value.trim()
            && !input.closest('form')?.querySelector('#is_legacy:checked')
            && !(input.id === 'matrixTrainDate' && document.querySelector('#matrixTrainLegacy:checked'));
    }
    async function run(input) {
        if (!applicable(input)) return true;
        const original = input.value;
        const date = dateOf(input);
        if (!date) { notify('Enter a valid date: dd/Mmm/yyyy.'); return false; }
        const scope = input.closest('form') || input.closest('.modal') || document;
        const trainingId = input.dataset.trainingId || input.closest('[data-training-id]')?.dataset.trainingId;
        const originalDate = input.closest('[data-original-date]')?.dataset.originalDate;
        // Historical collisions are not changed by saving unrelated fields.
        if (trainingId && date === originalDate) return true;
        const reserved = Array.from(scope.querySelectorAll(selector)).filter(other => other !== input && applicable(other)
            && !other.closest('[data-training-id]') && !other.dataset.trainingId && !other.matches('.add-training-date-input'))
            .map(dateOf).filter(Boolean);
        try {
            const response = await fetch(config.endpoint, {method: 'POST', headers: {
                'Content-Type': 'application/json', 'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }, body: JSON.stringify({user_id: input.dataset.trainingUserId || scope.querySelector('[name="user_id"]')?.value || config.userId,
                date_training: date, training_id: trainingId || null, reserved_dates: reserved})});
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || 'Could not check the training week.');
            if (!input.isConnected || input.value !== original) return false;
            if (result.available) { setDate(input, result.date); input.classList.remove('is-invalid'); return true; }
            input.classList.add('is-invalid');
            if (!window.confirmDialog) throw new Error('Confirmation dialog is unavailable. The date has not been moved.');
            const ask = async () => {
                if (!input.isConnected || input.value !== original) return false;
                const parentModal = input.closest('.modal.show');
                const wasInert = parentModal?.inert;
                if (parentModal) {
                    parentModal.inert = true;
                    bootstrap.Modal.getInstance(parentModal)?._focustrap?.deactivate();
                }
                try {
                    const answer = window.confirmDialog({title: 'Training week is occupied',
                        message: result.message + ' Move this date to the nearest free week?',
                        okText: 'Move to free week', cancelText: 'Choose another date'});
                    const dialog = document.getElementById('globalConfirmModal');
                    const title = dialog.querySelector('#globalConfirmTitle');
                    title.classList.add('text-warning');
                    dialog.addEventListener('hidden.bs.modal', () => title.classList.remove('text-warning'), {once: true});
                    const calendarButton = document.createElement('button');
                    calendarButton.type = 'button';
                    calendarButton.className = 'btn btn-outline-info btn-sm me-auto';
                    calendarButton.title = 'Training week calendar';
                    calendarButton.setAttribute('aria-label', 'Training week calendar');
                    calendarButton.innerHTML = '<i class="bi bi-calendar3" aria-hidden="true"></i>';
                    let selectedFromCalendar = null;
                    calendarButton.addEventListener('click', () => {
                        if (!window.TrainingWeekCalendar) { notify('Training calendar is unavailable.'); return; }
                        window.TrainingWeekCalendar.open({
                            userId: input.dataset.trainingUserId || scope.querySelector('[name="user_id"]')?.value || config.userId,
                            date: result.date, opener: calendarButton, scope,
                            onSelect: friday => {
                                if (!input.isConnected) return;
                                selectedFromCalendar = friday;
                                setDate(input, friday);
                                input.classList.remove('is-invalid');
                                bootstrap.Modal.getInstance(dialog).hide();
                            }
                        });
                    });
                    dialog.querySelector('.modal-footer').prepend(calendarButton);
                    const hidden = new Promise(resolve => dialog.addEventListener('hidden.bs.modal', resolve, {once: true}));
                    const accepted = await answer;
                    await hidden;
                    calendarButton.remove();
                    if (selectedFromCalendar && dateOf(input) === selectedFromCalendar) return true;
                    if (accepted && input.isConnected && input.value === original) {
                        setDate(input, result.suggested_date);
                        input.classList.remove('is-invalid');
                        return true;
                    }
                    return false;
                } finally {
                    if (parentModal) parentModal.inert = wasInert;
                    if (parentModal?.classList.contains('show')) {
                        document.body.classList.add('modal-open');
                        bootstrap.Modal.getInstance(parentModal)?._focustrap?.activate();
                    }
                }
            };
            const answer = dialogs.then(ask);
            dialogs = answer.catch(() => false);
            return await answer;
        } catch (error) { input.classList.add('is-invalid'); notify(error.message); return false; }
    }
    function check(input) {
        if (!input) return Promise.resolve(true);
        if (pending.has(input)) {
            const current = pending.get(input);
            return current.value === input.value ? current.task : current.task.then(() => check(input));
        }
        const task = run(input).finally(() => pending.delete(input));
        pending.set(input, {value: input.value, task});
        return task;
    }
    async function checkAll(scope) {
        for (const input of scope.querySelectorAll(selector)) {
            if (!(await check(input))) return false;
        }
        return true;
    }
    document.addEventListener('change', event => {
        if (event.target.matches(selector)) check(event.target);
    });
    window.TrainingWeekAvailability = {check, checkAll, setDate};
})();
