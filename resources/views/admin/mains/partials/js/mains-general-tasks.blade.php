<script>

// mains-general-tasks.js
// GeneralTask-таб, выбор Task, flatpickr, авто-submit дат, ignore_row (серые строки)

document.addEventListener('DOMContentLoaded', () => {

    // =========================
    // 0. ignore_row — восстановить состояние при загрузке
    //    Чекбоксы: .js-ignore-row
    //    Скрытый input: .js-ignore-hidden
    //    Инпуты: .js-start, .js-finish
    //    Серые ячейки: .js-task-name, .js-user-name (если используешь)
    // =========================
    document.querySelectorAll('.js-ignore-row:checked').forEach(cb => {
        const form   = cb.closest('form');
        if (!form) return;

        const tr     = form.closest('tr');
        const finish = tr?.querySelector('.js-finish');
        const hidden = form.querySelector('.js-ignore-hidden');

        if (hidden) hidden.value = '1';
        if (finish) {
            finish.disabled = true;
            finish.classList.add('is-ignored');
        }

        // Если хочешь сразу серить всю строку:
        if (tr) tr.classList.add('row-ignored');
        tr?.querySelectorAll('.js-start, .js-finish, .js-task-name, .js-user-name')
            ?.forEach(el => el.classList.add('is-ignored'));
    });

    // =========================
    // 1. GeneralTask табы (слева) — .js-gt-btn / .js-gt-pane
    // =========================

// 1. GeneralTask табы (слева) — .js-gt-btn / .js-gt-pane

    const gtContainer = document.querySelector('.js-gt-container');
    const woId       = gtContainer?.dataset.woId || '';
    const GT_KEY     = woId ? `gt_tab_wo_${woId}` : 'gt_tab_default';

    function activateGtTab(gtId, { save = true } = {}) {
        if (!gtId) return;

        document.querySelectorAll('.js-gt-btn').forEach(b => {
            const on = (b.dataset.gtId === gtId);
            b.classList.toggle('active', on);
            b.setAttribute('aria-pressed', on ? 'true' : 'false');
        });

        document.querySelectorAll('.js-gt-pane').forEach(p => {
            p.classList.toggle('d-none', p.dataset.gtId !== gtId);
        });

        // автопрокрутка к активной вкладке
        const activeBtn = document.querySelector(`.js-gt-btn[data-gt-id="${gtId}"]`);
        if (activeBtn && activeBtn.scrollIntoView) {
            activeBtn.scrollIntoView({
                block: 'nearest',
                inline: 'nearest',
                behavior: 'smooth'
            });
        }


        if (typeof initDatePickers === 'function') initDatePickers();

        if (save) {
            try { localStorage.setItem(GT_KEY, gtId); } catch (e) {}
        }
    }

// клик по кнопке GT
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.js-gt-btn');
        if (!btn) return;
        activateGtTab(btn.dataset.gtId);
    });

// восстановить выбранный таб и только потом показать контейнер
    (function restoreGtTab() {
        let savedId = null;
        try {
            savedId = localStorage.getItem(GT_KEY);
        } catch (e) {
            savedId = null;
        }

        if (savedId && document.querySelector(`.js-gt-btn[data-gt-id="${savedId}"]`)) {
            activateGtTab(savedId, { save: false });
        } else {
            const firstBtn = document.querySelector('.js-gt-btn');
            if (firstBtn) activateGtTab(firstBtn.dataset.gtId, { save: false });
        }

        if (gtContainer) {
            gtContainer.hidden = false;
        }
    })();


    // =========================
    // 2. Task picker (dropdown) — форма добавления строки Main
    //    Форма:  #general_task_form
    //    Поле:   #task_id
    //    Кнопка: #addBtn
    //    Dropdown button: #taskPickerBtn
    //    Табы GT внутри dropdown: #generalTab .nav-link[data-general-id]
    //    Плашки задач: .select-task
    // =========================
    const form          = document.getElementById('general_task_form');
    const taskInput     = document.getElementById('task_id');
    const addBtn        = document.getElementById('addBtn');
    const pickerBtn     = document.getElementById('taskPickerBtn');
    const pickedSummary = document.getElementById('pickedSummary');

    const generalTabs = Array.from(document.querySelectorAll('#generalTab .nav-link[data-general-id]'));
    const taskPanes   = Array.from(document.querySelectorAll('#taskTabContent .tab-pane'));
    const taskButtons = Array.from(document.querySelectorAll('.select-task'));

    function showPaneForGeneral(btn) {
        const gid = btn.dataset.generalId;
        generalTabs.forEach(b => b.classList.remove('active'));
        taskPanes.forEach(p => p.classList.remove('show', 'active'));
        btn.classList.add('active');
        const pane = document.getElementById('pane-g-' + gid);
        if (pane) pane.classList.add('active', 'show');
    }

    function generalNameById(gid) {
        const b = document.getElementById('tab-g-' + gid);
        return (b ? b.textContent : '').trim();
    }

    function updatePickedSummary(gName, tName) {
        if (!pickedSummary) return;
        pickedSummary.textContent = (gName && tName) ? `${gName} → ${tName}` : (tName || '');
    }

    function activateAddButton() {
        if (!addBtn) return;
        addBtn.removeAttribute('disabled');
        addBtn.classList.remove('disabled');
    }

    function initTaskPicker() {
        generalTabs.forEach(btn => {
            btn.addEventListener('mouseenter', () => showPaneForGeneral(btn));
            btn.addEventListener('click', e => e.preventDefault());
        });

        taskButtons.forEach(item => {
            item.addEventListener('click', () => {
                const taskId   = item.dataset.taskId;
                const taskName = item.dataset.taskName;
                const gid      = item.dataset.generalId;

                if (taskInput) taskInput.value = taskId;
                updatePickedSummary(generalNameById(gid), taskName);
                activateAddButton();

                if (pickerBtn && window.bootstrap?.Dropdown) {
                    const dd = bootstrap.Dropdown.getOrCreateInstance(pickerBtn);
                    dd?.hide();
                }
            });
        });

        if (generalTabs[0]) showPaneForGeneral(generalTabs[0]);
        if (taskInput?.value) activateAddButton();
    }

    // =========================
    // 3. submit формы добавления Task
    // =========================
    function bindFormSubmit() {
        if (!form) return;
        form.addEventListener('submit', (e) => {
            if (!taskInput?.value) {
                e.preventDefault();
                alert('Please choose a task first');
                return;
            }
            safeShowSpinner();
            if (addBtn) {
                addBtn.setAttribute('disabled', 'disabled');
                addBtn.classList.add('disabled');
            }
        });
    }

    // =========================
    // 4. flatpickr для всех input[data-fp] (date_start / date_finish)
    // =========================
    function initDatePickers() {
        if (typeof flatpickr === 'undefined') return;

        document.querySelectorAll('input[data-fp]').forEach(src => {
            if (src._flatpickr) return;

            flatpickr(src, {
                altInput: true,
                altFormat: "d.m.Y",
                dateFormat: "Y-m-d",
                allowInput: true,
                disableMobile: true,

                onChange(selectedDates, dateStr, instance) {
                    const form = src.closest('form');
                    if (!form) return;

                    safeShowSpinner();
                    if (form.requestSubmit) form.requestSubmit();
                    else form.submit();
                },

                onReady(selectedDates, dateStr, instance) {
                    instance.altInput.classList.add('form-control', 'form-control-sm', 'w-100', 'fp-alt');

                    if (src.classList.contains('finish-input')) instance.altInput.classList.add('finish-input');
                    if (src.value) instance.altInput.classList.add('has-finish');

                    src.style.display = 'none';
                }
            });
        });

        document.body.classList.add('fp-ready');
    }

    // =========================
    // 5. Авто-submit форм js-auto-submit по change инпута
    //    (не для ignore_row — у него свой обработчик)
    // =========================
    document.addEventListener('change', (e) => {
        // если это чекбокс ignore_row, его обрабатывает другой скрипт
        if (e.target.closest('.js-ignore-row')) return;

        const input = e.target.closest('form.js-auto-submit input');
        if (!input) return;

        if (input.form.requestSubmit) input.form.requestSubmit();
        else input.form.submit();
    });

    // =========================
    // Инициализация блока
    // =========================
    initTaskPicker();
    bindFormSubmit();
    initDatePickers();
});

document.addEventListener('DOMContentLoaded', () => {
    // при загрузке применяем состояние ко ВСЕМ чекбоксам
    document.querySelectorAll('.js-ignore-row').forEach(cb => {
        applyIgnoreState(cb);
    });

    // при изменении чекбокса: применяем состояние + отправляем форму
    document.addEventListener('change', (e) => {
        const cb = e.target.closest('.js-ignore-row');
        if (!cb) return;

        applyIgnoreState(cb);

        const form = cb.closest('form');
        if (!form) return;

        if (form.requestSubmit) form.requestSubmit();
        else form.submit();
    });
});

function applyIgnoreState(cb) {
    const form   = cb.closest('form');
    if (!form) return;

    const tr     = form.closest('tr');
    const finish = tr?.querySelector('.js-finish');
    const hidden = form.querySelector('.js-ignore-hidden');

    const isChecked = cb.checked;

    // пишем актуальное значение в hidden
    if (hidden) hidden.value = isChecked ? '1' : '0';

    if (isChecked) {
        if (finish) {
            finish.disabled = true;
            finish.classList.add('is-ignored');
        }
        if (tr) tr.classList.add('row-ignored');
        tr?.querySelectorAll('.js-start, .js-finish, .js-task-name, .js-user-name')
            ?.forEach(el => el.classList.add('is-ignored'));
    } else {
        if (finish) {
            finish.disabled = false;
            finish.classList.remove('is-ignored');
        }
        if (tr) tr.classList.remove('row-ignored');
        tr?.querySelectorAll('.js-start, .js-finish, .js-task-name, .js-user-name')
            ?.forEach(el => el.classList.remove('is-ignored'));
    }
}

</script>
