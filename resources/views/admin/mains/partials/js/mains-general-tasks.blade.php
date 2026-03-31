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
    function setTaskDateInputState(input, isIgnored) {
        if (!input) return;

        input.disabled = isIgnored;
        input.classList.toggle('is-ignored', isIgnored);

        const fp = input._flatpickr;
        const alt = fp?.altInput;
        if (alt) {
            alt.readOnly = isIgnored;
            alt.disabled = isIgnored;
            alt.classList.toggle('is-ignored', isIgnored);
            alt.classList.toggle('fp-locked', isIgnored);
            alt.style.cursor = isIgnored ? 'not-allowed' : '';
        }
    }

    document.querySelectorAll('.js-ignore-row:checked').forEach(cb => {
        const form   = cb.closest('form');
        if (!form) return;

        const tr     = form.closest('tr');
        const start  = tr?.querySelector('.js-start');
        const finish = tr?.querySelector('.js-finish');
        const hidden = form.querySelector('.js-ignore-hidden');

        if (hidden) hidden.value = '1';
        setTaskDateInputState(start, true);
        setTaskDateInputState(finish, true);

        // Если хочешь сразу серить всю строку:
        if (tr) tr.classList.add('row-ignored');
        tr?.querySelectorAll('.js-fade-on-ignore, .js-start, .js-finish, .js-task-name, .js-user-name')
            ?.forEach(el => el.classList.add('is-ignored'));
    });

    // =========================
    // 1. GeneralTask табы (слева) — .js-gt-btn / .js-gt-pane
    // =========================

// 1. GeneralTask табы (слева) — .js-gt-btn / .js-gt-pane

    const gtContainer = document.querySelector('.js-gt-container');
    const leftLoader = document.getElementById('mainLeftLoading');
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
        if (leftLoader) {
            leftLoader.style.display = 'none';
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

    function lockFlatpickrInput(src) {
        // 1) запретить открытие по клику/фокусу
        const stop = (e) => {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            src.blur();
            return false;
        };

        // на самом input
        ['click', 'focus', 'mousedown', 'keydown'].forEach(ev => {
            src.addEventListener(ev, stop, true);
        });

        // если flatpickr уже был инициализирован — закрыть и заблокировать altInput тоже
        if (src._flatpickr) {
            try { src._flatpickr.close(); } catch (_) {}

            const alt = src._flatpickr.altInput;
            if (alt) {
                alt.readOnly = true;
                alt.setAttribute('tabindex', '-1');
                ['click', 'focus', 'mousedown', 'keydown'].forEach(ev => {
                    alt.addEventListener(ev, stop, true);
                });
            }
        }
    }

    // =========================
    // 4. flatpickr для всех input[data-fp] (date_start / date_finish)
    // =========================
    function initDatePickers() {
        if (typeof flatpickr === 'undefined') return;

        document.querySelectorAll('input[data-fp]').forEach(src => {


            if (src._flatpickr) return;
            if (src.disabled) return;
            const isLocked = src.hasAttribute('data-fp-locked');

            flatpickr(src, {
                altInput: true,
                altFormat: "d.M.y",
                dateFormat: "Y-m-d",
               // allowInput: true,
                disableMobile: true,
                clickOpens: !isLocked,
                allowInput: !isLocked,

                onChange(selectedDates, dateStr, instance) {

                    if (isLocked) return; // 🔒 не сабмитим

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
                            if (src.disabled || isLocked) return;
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
    // expose for other handlers (restore after ignore toggle)
    window.__mainsInitDatePickers = initDatePickers;

    // =========================
    // 5. Авто-submit форм js-auto-submit по change инпута
    //    (не для ignore_row — у него свой обработчик)
    // =========================
    document.addEventListener('change', (e) => {
        // если это чекбокс ignore_row, его обрабатывает другой скрипт
        if (e.target.closest('.js-ignore-row')) return;

        // repair_order сохраняется blur/focusout + ajaxSubmit в main.blade.php — иначе дубль сабмита
        if (e.target?.name === 'repair_order') return;

        const input = e.target.closest('form.js-auto-submit input');
        if (!input) return;
        if (input.id === 'showAll') return;

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

        // js-main-inline-ajax forms are submitted by unified handler in main.blade.php
        if (form.classList.contains('js-main-inline-ajax')) return;

        if (form.requestSubmit) form.requestSubmit();
        else form.submit();
    });
});

function applyIgnoreState(cb) {
    const form   = cb.closest('form');
    if (!form) return;

    const tr     = form.closest('tr');
    const start  = tr?.querySelector('.js-start');
    const finish = tr?.querySelector('.js-finish');
    const hidden = form.querySelector('.js-ignore-hidden');

    const isChecked = cb.checked;

    // пишем актуальное значение в hidden
    if (hidden) hidden.value = isChecked ? '1' : '0';

    if (isChecked) {
        if (start) {
            start.disabled = true;
            start.classList.add('is-ignored');
            const alt = start._flatpickr?.altInput;
            if (alt) {
                alt.readOnly = true;
                alt.disabled = true;
                alt.classList.add('is-ignored', 'fp-locked');
                alt.style.cursor = 'not-allowed';
            }
        }
        if (finish) {
            finish.disabled = true;
            finish.classList.add('is-ignored');
            const alt = finish._flatpickr?.altInput;
            if (alt) {
                alt.readOnly = true;
                alt.disabled = true;
                alt.classList.add('is-ignored', 'fp-locked');
                alt.style.cursor = 'not-allowed';
            }
        }
        if (tr) tr.classList.add('row-ignored');
        tr?.querySelectorAll('.js-fade-on-ignore, .js-start, .js-finish, .js-task-name, .js-user-name')
            ?.forEach(el => el.classList.add('is-ignored'));
    } else {
        if (start) {
            start.disabled = false;
            start.classList.remove('is-ignored');
            start.style.display = '';
            const alt = start._flatpickr?.altInput;
            if (alt) {
                alt.readOnly = false;
                alt.disabled = false;
                alt.classList.remove('is-ignored', 'fp-locked');
                alt.style.cursor = '';
                alt.style.display = '';
            }
        }
        if (finish) {
            finish.disabled = false;
            finish.classList.remove('is-ignored');
            finish.style.display = '';
            const alt = finish._flatpickr?.altInput;
            if (alt) {
                alt.readOnly = false;
                alt.disabled = false;
                alt.classList.remove('is-ignored', 'fp-locked');
                alt.style.cursor = '';
                alt.style.display = '';
            }
        }
        if (tr) tr.classList.remove('row-ignored');
        tr?.querySelectorAll('.js-fade-on-ignore, .js-start, .js-finish, .js-task-name, .js-user-name')
            ?.forEach(el => el.classList.remove('is-ignored'));

        // ensure visible date widgets are present immediately after restore
        if (typeof window.__mainsInitDatePickers === 'function') {
            window.__mainsInitDatePickers();
        }
    }
}

document.addEventListener('input', function (e) {
    const input = e.target.closest('input[name="repair_order"]');
    if (!input) return;

    const form = input.closest('form');
    const indicator = form.querySelector('.save-indicator');

    const original = input.dataset.original ?? '';
    const current  = input.value;

    if (current !== original) {
        indicator.classList.remove('d-none'); // показать 💾
    } else {
        indicator.classList.add('d-none');    // скрыть
    }
});
document.addEventListener('submit', function (e) {
    const form = e.target.closest('.auto-submit-form');
    if (!form) return;

    const indicator = form.querySelector('.save-indicator');
    if (indicator) indicator.classList.add('d-none');
});

</script>
