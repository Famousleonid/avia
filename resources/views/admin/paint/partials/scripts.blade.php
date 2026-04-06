<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
(function () {
    window.paintOpenMessageToOwner = function (userId) {
        var id = parseInt(userId, 10);
        if (!id) return;
        window.__msgPreselectUserIds = [id];
        var modalEl = document.getElementById('sendMsgModal');
        if (!modalEl || typeof bootstrap === 'undefined') return;
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    };

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.js-paint-msg-owner');
        if (!btn) return;
        e.preventDefault();
        var uid = btn.getAttribute('data-user-id');
        if (uid) window.paintOpenMessageToOwner(uid);
    }, true);

    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form || !form.classList || !form.classList.contains('js-ajax')) return;
        e.preventDefault();
        if (typeof window.ajaxSubmit === 'function') window.ajaxSubmit(form);
    }, true);

    /**
     * Нативный type=date — без flatpickr (стили mains + порядок скриптов ломали видимость/клики).
     */
    function syncPaintDateEmptyState(inp) {
        var empty = !String(inp.value || '').trim();
        inp.classList.toggle('paint-date-empty', empty);
        inp.classList.toggle('has-finish', !empty);
    }

    function initPaintNativeDateInputs() {
        document.querySelectorAll('#paint-wo-table form.js-ajax input[type="date"].paint-native-date').forEach(function (inp) {
            if (inp.dataset.paintNativeBound === '1') return;
            inp.dataset.paintNativeBound = '1';
            syncPaintDateEmptyState(inp);
            inp.addEventListener('change', function () {
                syncPaintDateEmptyState(inp);
                var form = inp.closest('form');
                if (!form || typeof window.ajaxSubmit !== 'function') return;
                window.ajaxSubmit(form);
            });
        });
    }

    function initPaintTableSearch() {
        var inp = document.getElementById('paintTableSearch');
        if (!inp) return;
        inp.addEventListener('input', function () {
            var q = String(inp.value || '').trim().toLowerCase();
            document.querySelectorAll('#paint-sortable-tbody tr[data-paint-search]').forEach(function (tr) {
                var hay = tr.getAttribute('data-paint-search') || '';
                tr.classList.toggle('d-none', q !== '' && hay.indexOf(q) === -1);
            });
        });
    }

    function bootPaintPage() {
        initPaintNativeDateInputs();
        initPaintTableSearch();

        @if($canReorderPaint ?? false)
        (function initPaintSortable() {
            var tbody = document.getElementById('paint-sortable-tbody');
            if (!tbody || typeof Sortable === 'undefined') return;
            if (!tbody.querySelector('tr.paint-row-queued')) return;

            var reorderUrl = @json(route('paint.reorder'));
            var token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            Sortable.create(tbody, {
                handle: '.paint-drag-handle',
                draggable: 'tr.paint-row-queued',
                animation: 150,
                ghostClass: 'table-active',
                onMove: function (evt) {
                    var rel = evt.related;
                    if (rel && rel.classList.contains('paint-row-unqueued') && evt.willInsertAfter) {
                        return false;
                    }
                    return true;
                },
                onEnd: function () {
                    if (typeof window.safeShowSpinner === 'function') window.safeShowSpinner();
                    var ids = Array.from(tbody.querySelectorAll('tr.paint-row-queued')).map(function (tr) {
                        return parseInt(tr.getAttribute('data-wo-id'), 10);
                    }).filter(function (id) { return id > 0; });

                    fetch(reorderUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ workorder_ids: ids })
                    }).then(function (r) {
                        if (r.ok) {
                            window.location.reload();
                            return;
                        }
                        if (typeof window.safeHideSpinner === 'function') window.safeHideSpinner();
                        return r.json().then(function (d) {
                            var msg = (d && d.message) ? d.message : 'Reorder failed';
                            if (typeof window.notifyError === 'function') window.notifyError(msg, 2500);
                        });
                    }).catch(function () {
                        if (typeof window.safeHideSpinner === 'function') window.safeHideSpinner();
                        if (typeof window.notifyError === 'function') window.notifyError('Network error', 2500);
                    });
                }
            });
        })();

        (function initPaintPositionInputs() {
            var positionUrl = @json(route('paint.position'));
            var token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            function showSpin() {
                if (typeof window.safeShowSpinner === 'function') window.safeShowSpinner();
            }
            function hideSpin() {
                if (typeof window.safeHideSpinner === 'function') window.safeHideSpinner();
            }

            function revertValue(inp, was, inQueue) {
                if (inQueue && was > 0) {
                    inp.value = String(was);
                } else {
                    inp.value = '';
                }
            }

            function submit(inp) {
                var wid = parseInt(inp.getAttribute('data-wo-id'), 10);
                var inQueue = inp.getAttribute('data-in-queue') === '1';
                var was = parseInt(inp.getAttribute('data-was') || '0', 10) || 0;
                var raw = String(inp.value || '').replace(/\D/g, '');
                if (raw === '') {
                    revertValue(inp, was, inQueue);
                    return;
                }
                var pos = parseInt(raw, 10);
                if (pos === was) return;

                showSpin();
                fetch(positionUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ workorder_id: wid, position: pos })
                }).then(function (r) {
                    if (r.ok) {
                        window.location.reload();
                        return;
                    }
                    hideSpin();
                    revertValue(inp, was, inQueue);
                    return r.json().then(function (d) {
                        var msg = (d && d.message) ? d.message : 'Update failed';
                        if (typeof window.notifyError === 'function') window.notifyError(msg, 2500);
                    });
                }).catch(function () {
                    hideSpin();
                    revertValue(inp, was, inQueue);
                    if (typeof window.notifyError === 'function') window.notifyError('Network error', 2500);
                });
            }

            document.querySelectorAll('.js-paint-position-input').forEach(function (inp) {
                inp.addEventListener('input', function () {
                    var cur = inp.selectionStart;
                    var filtered = String(inp.value || '').replace(/\D/g, '');
                    if (inp.value !== filtered) {
                        inp.value = filtered;
                        if (cur !== null) {
                            try { inp.setSelectionRange(filtered.length, filtered.length); } catch (_) {}
                        }
                    }
                });
                inp.addEventListener('blur', function () { submit(inp); });
                inp.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        inp.blur();
                    }
                });
            });
        })();

        (function initPaintAddModal() {
            var btn = document.getElementById('paintAddWoBtn');
            var numEl = document.getElementById('paintAddWoNumber');
            var errEl = document.getElementById('paintAddWoErr');
            if (!btn || !numEl) return;

            var addUrl = @json(route('paint.add'));
            var token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            btn.addEventListener('click', function () {
                if (errEl) errEl.classList.add('d-none');
                var n = parseInt(numEl.value, 10);
                if (!n) {
                    if (errEl) { errEl.textContent = 'Enter number'; errEl.classList.remove('d-none'); }
                    return;
                }

                btn.disabled = true;
                fetch(addUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ number: n })
                }).then(function (r) {
                    return r.json().then(function (d) {
                        if (!r.ok) {
                            if (errEl) {
                                errEl.textContent = (d && d.message) ? d.message : 'Error';
                                errEl.classList.remove('d-none');
                            }
                            return;
                        }
                        var modalEl = document.getElementById('paintAddWoModal');
                        if (modalEl && typeof bootstrap !== 'undefined') {
                            bootstrap.Modal.getInstance(modalEl)?.hide();
                        }
                        numEl.value = '';
                        window.location.reload();
                    });
                }).catch(function () {
                    if (errEl) {
                        errEl.textContent = 'Network error';
                        errEl.classList.remove('d-none');
                    }
                }).finally(function () {
                    btn.disabled = false;
                });
            });
        })();
        @endif
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootPaintPage);
    } else {
        bootPaintPage();
    }
})();
</script>
