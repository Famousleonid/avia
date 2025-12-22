<script>

// mains-common.js
// Общее между всеми: безопасный спиннер, debounce, delete-модал, showAll

document.addEventListener('DOMContentLoaded', () => {
    // =========================
    // 1. Безопасный спиннер + debounce
    // =========================
    window.safeShowSpinner = () => {
        try {
            if (typeof showLoadingSpinner === 'function') showLoadingSpinner();
        } catch (_) {}
    };

    window.safeHideSpinner = () => {
        try {
            if (typeof hideLoadingSpinner === 'function') hideLoadingSpinner();
        } catch (_) {}
    };

    // Скрываем спиннер, если страница вернулась из bfcache
    safeHideSpinner();
    window.addEventListener('pageshow', safeHideSpinner);

    window.debounce = (fn, ms) => {
        let t;
        return (...a) => {
            clearTimeout(t);
            t = setTimeout(() => fn.apply(null, a), ms);
        };
    };

    // =========================
    // 2. Общий delete-модал (tasks / mains / tdrprocesses)
    //    Модалка: #useConfirmDelete
    //    Форма:  #deleteForm
    //    Кнопка: #confirmDeleteBtn
    //    Кнопки-триггеры: data-action / data-title
    // =========================
    const modalEl   = document.getElementById('useConfirmDelete');
    const confirmBt = document.getElementById('confirmDeleteBtn');
    const delForm   = document.getElementById('deleteForm');
    let pendingAction = null;

    modalEl?.addEventListener('show.bs.modal', function (event) {
        const trigger = event.relatedTarget;
        pendingAction = trigger?.getAttribute('data-action') || null;

        const title = trigger?.getAttribute('data-title') || 'Delete Confirmation';
        const lbl   = document.getElementById('confirmDeleteLabel');
        if (lbl) lbl.textContent = title;
    });

    confirmBt?.addEventListener('click', function () {
        if (!pendingAction) return;
        delForm.setAttribute('action', pendingAction);
        safeShowSpinner();
        delForm.submit();
    });

    // =========================
    // 3. Переключатель "Show all components"
    //    Чекбокс: #showAll (в своей форме)
    // =========================
    document.getElementById('showAll')?.addEventListener('change', function () {
        safeShowSpinner();
        if (this.form?.requestSubmit) this.form.requestSubmit();
        else this.form?.submit();
    });

    // =========================
    // 4. Общий вызов initAutoSubmit (если где-то определён)
    // =========================
    if (typeof initAutoSubmit === 'function') {
        initAutoSubmit();
    }
});
</script>
