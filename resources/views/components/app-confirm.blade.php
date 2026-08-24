{{-- Единая модалка подтверждения вместо браузерного confirm().
     JS: await window.appConfirm(message, {title, okText, okClass}) -> boolean.
     Разметка: form/a/button с data-app-confirm="сообщение" перехватываются сами. --}}
<div class="modal fade" id="appConfirmModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="appConfirmTitle">{{ __('Please confirm') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body"><p class="mb-0" id="appConfirmMessage"></p></div>
            <div class="modal-footer py-1">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-primary btn-sm" id="appConfirmOkBtn">{{ __('OK') }}</button>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    let resolver = null;
    let confirmed = false;

    window.appConfirm = function (message, opts = {}) {
        const el = document.getElementById('appConfirmModal');
        if (!el || typeof bootstrap === 'undefined') {
            return Promise.resolve(window.confirm(message)); // страховка
        }
        document.getElementById('appConfirmMessage').textContent = message || '';
        document.getElementById('appConfirmTitle').textContent = opts.title || @json(__('Please confirm'));
        const ok = document.getElementById('appConfirmOkBtn');
        ok.textContent = opts.okText || 'OK';
        ok.className = 'btn btn-sm ' + (opts.okClass || 'btn-primary');
        confirmed = false;
        return new Promise(function (resolve) {
            resolver = resolve;
            bootstrap.Modal.getOrCreateInstance(el).show();
        });
    };

    document.addEventListener('DOMContentLoaded', function () {
        const el = document.getElementById('appConfirmModal');
        if (!el) return;

        document.getElementById('appConfirmOkBtn').addEventListener('click', function () {
            confirmed = true;
            bootstrap.Modal.getInstance(el)?.hide();
        });
        el.addEventListener('hidden.bs.modal', function () {
            if (resolver) {
                const r = resolver;
                resolver = null;
                r(confirmed);
            }
        });

        // Формы с data-app-confirm
        document.addEventListener('submit', function (e) {
            const form = e.target.closest?.('form[data-app-confirm]');
            if (!form || form.dataset.appConfirmOk === '1') return;
            e.preventDefault();
            window.appConfirm(form.dataset.appConfirm).then(function (ok) {
                if (!ok) return;
                form.dataset.appConfirmOk = '1';
                form.requestSubmit ? form.requestSubmit() : form.submit();
                delete form.dataset.appConfirmOk;
            });
        }, true);

        // Ссылки и одиночные кнопки с data-app-confirm
        document.addEventListener('click', function (e) {
            const item = e.target.closest?.('a[data-app-confirm], button[data-app-confirm]');
            if (!item || item.dataset.appConfirmOk === '1') return;
            // submit-кнопка внутри формы с подтверждением — отработает submit-перехватчик
            if (item.tagName === 'BUTTON' && item.closest('form[data-app-confirm]')) return;
            e.preventDefault();
            e.stopImmediatePropagation();
            window.appConfirm(item.dataset.appConfirm).then(function (ok) {
                if (!ok) return;
                if (item.tagName === 'A' && item.href) {
                    window.location.href = item.href;
                    return;
                }
                item.dataset.appConfirmOk = '1';
                item.click();
                delete item.dataset.appConfirmOk;
            });
        }, true);
    });
})();
</script>
