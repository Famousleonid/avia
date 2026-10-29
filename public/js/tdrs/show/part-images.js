document.addEventListener('DOMContentLoaded', function () {
    document.addEventListener('click', async function (event) {
        const button = event.target.closest('[data-tdr-manufacture-pair-delete]');
        if (! button) {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();

        if (typeof window.confirmDialog !== 'function') {
            window.tdrShowNotify(("" + String(window.TdrshowConfig4.value0) + ""), 'error');
            return;
        }

        const confirmed = await window.confirmDialog({
            title: ("" + String(window.TdrshowConfig4.value1) + ""),
            message: ("" + String(window.TdrshowConfig4.value2) + ""),
            okText: ("" + String(window.TdrshowConfig4.value3) + ""),
            cancelText: ("" + String(window.TdrshowConfig4.value4) + ""),
            danger: true,
        });

        if (confirmed) {
            button.closest('form')?.submit();
        }
    }, true);

    const modal = document.getElementById('useConfirmDelete');
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    let deleteForm = null;

    modal?.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        deleteForm = button ? button.closest('form') : null;
        const title = button?.getAttribute('data-title');
        const lbl = document.getElementById('confirmDeleteLabel');
        if (lbl && title) lbl.textContent = title;
    });

    confirmBtn?.addEventListener('click', function () {
        if (deleteForm) deleteForm.submit();
    });

    let tdrCreatedFromMeasurements = false;
    document.addEventListener('tdr-created-from-measurements', () => {
        tdrCreatedFromMeasurements = true;
    });
    document.getElementById('tab-tdr')?.addEventListener('click', function () {
        if (tdrCreatedFromMeasurements) {
            window.location.reload();
        }
    });
});
