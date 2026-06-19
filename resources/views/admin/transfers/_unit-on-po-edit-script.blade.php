{{-- Shared inline editor for the Transfers "Unit on PO" cell.
     Raw JS (no <script> wrapper) — include INSIDE an existing <script> block.
     Delegated change handler, autosaves on change/blur. Works for the standalone
     page and the AJAX-loaded TDR tab. --}}
(function () {
    if (window.__transfersUnitOnPoBound) return; // guard against double include on one page
    window.__transfersUnitOnPoBound = true;

    var saveUrl = '{{ route('transfers.updateUnitOnPo', ['id' => '__ID__']) }}';

    document.addEventListener('change', function (e) {
        var input = e.target.closest && e.target.closest('.transfers-partial .unit-on-po-input');
        if (!input) return;

        var transferId = input.dataset.transferId;
        if (!transferId) return;

        input.disabled = true;
        fetch(saveUrl.replace('__ID__', transferId), {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ unit_on_po: input.value })
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    input.value = data.unit_on_po || '';
                } else if (typeof showNotification === 'function') {
                    showNotification('Failed to update Unit on PO', 'error');
                }
            })
            .catch(function () {
                if (typeof showNotification === 'function') showNotification('Server error', 'error');
            })
            .finally(function () {
                input.disabled = false;
            });
    });
})();
