@extends('admin.master-embed')

@section('content')
    @include('admin.tdr-processes.partials.edit-form')
@endsection

@section('scripts')
    <script src="{{ asset('js/tdr-processes/edit-process/edit-process.js') }}"></script>
    @if(request()->query('modal'))
    <script>
    (function() {
        if (window.parent === window) return;
        var form = document.getElementById('editCPForm');
        var cancelBtn = document.querySelector('.cancel-edit-process');
        if (window.__editFormConfig) window.__editFormConfig.dropdownParent = document.body;
        if (window.TdrProcessEditForm && window.__editFormConfig) {
            window.TdrProcessEditForm.init(window.__editFormConfig);
        }
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                var submitBtn = form.querySelector('button[type="submit"]');
                var origText = submitBtn ? submitBtn.innerHTML : '';
                if (submitBtn) { submitBtn.disabled = true; submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>'; }
                fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                    .then(function(r) { return r.json().then(function(d) { return { status: r.status, data: d }; }).catch(function() { return { status: 0, data: {} }; }); })
                    .then(function(res) {
                        if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = origText; }
                        var data = res.data;
                        if (data.success) {
                            window.parent.postMessage({ type: 'editProcessSuccess', tdrId: data.tdrId || form.querySelector('input[name="tdrs_id"]')?.value }, '*');
                        } else {
                            var msg = data.message || 'Error';
                            if (res.status === 422 && data.errors) {
                                var first = Object.values(data.errors)[0];
                                if (Array.isArray(first) && first[0]) msg = first[0];
                            }
                            (window.NotificationHandler?.error || window.notifyError)(msg);
                        }
                    })
                    .catch(function() {
                        if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = origText; }
                        (window.NotificationHandler?.error || window.notifyError)('Error');
                    });
            });
        }
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function() {
                window.parent.postMessage({ type: 'editProcessCancel' }, '*');
            });
        }
    })();
    </script>
    @endif
@endsection
