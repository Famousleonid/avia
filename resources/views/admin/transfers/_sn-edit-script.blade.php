{{-- Shared "change serial number" handler for the Transfers SN cells.
     Raw JS (no <script> wrapper) — include INSIDE an existing <script> block.
     Self-contained: keeps its own target-cell reference, uses delegated events,
     so it works for both the standalone page and the AJAX-loaded TDR tab. --}}
(function () {
    if (window.__transfersSnEditBound) return; // guard against double include on one page
    window.__transfersSnEditBound = true;

    var snCell = null;
    var updateSnUrl = '{{ route('transfers.updateSn', ['id' => '__ID__']) }}';

    document.addEventListener('click', function (e) {
        var link = e.target.closest && e.target.closest('.transfers-partial .change-sn-link');
        var modal = document.getElementById('changeSnModal');
        if (!link || !modal) return;
        e.preventDefault();
        snCell = link.closest('td');
        var idEl = document.getElementById('snTransferId');
        var inputEl = document.getElementById('component_sn');
        if (idEl) idEl.value = link.dataset.transferId || '';
        if (inputEl) inputEl.value = link.dataset.currentSn || '';
        if (typeof bootstrap !== 'undefined') {
            bootstrap.Modal.getOrCreateInstance(modal).show();
        }
    });

    var form = document.getElementById('changeSnForm');
    if (!form) return;
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var idEl = document.getElementById('snTransferId');
        var inputEl = document.getElementById('component_sn');
        var transferId = idEl ? idEl.value : '';
        var newSn = inputEl ? inputEl.value : '';
        if (!transferId) return;

        fetch(updateSnUrl.replace('__ID__', transferId), {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ component_sn: newSn })
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) {
                    if (typeof showNotification === 'function') showNotification('Failed to update Serial Number', 'error');
                    return;
                }
                if (snCell) {
                    if (data.component_sn) {
                        snCell.textContent = '';
                        var a = document.createElement('a');
                        a.href = '#';
                        a.className = 'text-decoration-underline text-info change-sn-link';
                        a.setAttribute('data-transfer-id', transferId);
                        a.setAttribute('data-current-sn', data.component_sn);
                        a.setAttribute('data-bs-toggle', 'modal');
                        a.setAttribute('data-bs-target', '#changeSnModal');
                        a.textContent = data.component_sn;
                        snCell.appendChild(a);
                    } else {
                        snCell.textContent = '-';
                    }
                }
                var modalEl = document.getElementById('changeSnModal');
                if (modalEl && typeof bootstrap !== 'undefined') {
                    var inst = bootstrap.Modal.getInstance(modalEl);
                    if (inst) inst.hide();
                }
            })
            .catch(function () {
                if (typeof showNotification === 'function') showNotification('Server error', 'error');
            });
    });
})();
