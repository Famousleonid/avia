<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
(function () {
    window.machiningOpenMessageToOwner = function (userId) {
        var id = parseInt(userId, 10);
        if (!id) return;
        window.__msgPreselectUserIds = [id];
        var modalEl = document.getElementById('sendMsgModal');
        if (!modalEl || typeof bootstrap === 'undefined') return;
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    };

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.js-machining-msg-owner');
        if (!btn) return;
        e.preventDefault();
        var uid = btn.getAttribute('data-user-id');
        if (uid) window.machiningOpenMessageToOwner(uid);
    }, true);

    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form || !form.classList || !form.classList.contains('js-ajax')) return;
        e.preventDefault();
        if (typeof window.ajaxSubmit === 'function') window.ajaxSubmit(form);
    }, true);

    /**
     * Дата на экране: dd.mon.yyyy (напр. 02.feb.2026); в PATCH уходит Y-m-d со скрытого поля.
     * Выбор — нативный input[type=date] (скрыт), вызов через showPicker по клику на ячейку.
     */
    function formatMachiningDateFromYmd(ymd) {
        var s = String(ymd || '').trim();
        if (!/^\d{4}-\d{2}-\d{2}$/.test(s)) return '';
        var p = s.split('-');
        var d = new Date(parseInt(p[0], 10), parseInt(p[1], 10) - 1, parseInt(p[2], 10));
        var months = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];
        return String(d.getDate()).padStart(2, '0') + '.' + months[d.getMonth()] + '.' + d.getFullYear();
    }

    function syncMachiningDateDisplayState(display) {
        var empty = !String(display.value || '').trim();
        display.classList.toggle('machining-date-empty', empty);
        display.classList.toggle('has-finish', !empty);
    }

    function openMachiningDatePicker(aid) {
        if (typeof aid.showPicker === 'function') {
            try {
                aid.showPicker();
                return;
            } catch (_) {}
        }
        aid.focus();
    }

    function initMachiningNativeDateInputs() {
        function clearMachiningDateWrap(wrap) {
            var ymd = wrap.querySelector('.js-machining-date-ymd');
            var display = wrap.querySelector('.machining-date-display');
            var aid = wrap.querySelector('.js-machining-picker-aid');
            if (ymd) ymd.value = '';
            if (aid) aid.value = '';
            if (display) {
                display.value = '';
                syncMachiningDateDisplayState(display);
                display.classList.add('is-invalid');
                setTimeout(function () { display.classList.remove('is-invalid'); }, 1200);
            }
        }

        function collectFirstError(data) {
            if (!data) return '';
            if (data.errors && typeof data.errors === 'object') {
                var firstKey = Object.keys(data.errors)[0];
                if (firstKey && Array.isArray(data.errors[firstKey]) && data.errors[firstKey][0]) {
                    return String(data.errors[firstKey][0]);
                }
            }
            return String(data.message || '');
        }

        async function submitMachiningDateForm(form, wrap) {
            var url = form && form.getAttribute ? form.getAttribute('action') : '';
            if (!url) return;
            if (form.classList.contains('is-saving')) return;
            form.classList.add('is-saving');

            try {
                var fd = new FormData(form);
                var res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                        'Accept': 'application/json'
                    },
                    body: fd
                });
                var data = await res.json().catch(function () { return {}; });

                if (res.status === 422 || data?.success === false) {
                    var msg = collectFirstError(data) || 'Validation error';
                    if (typeof window.notifyError === 'function') window.notifyError(msg, 2500);

                    if (msg === 'The end date cannot be earlier than the start date.') {
                        clearMachiningDateWrap(wrap);
                        var clearedYmd = wrap.querySelector('.js-machining-date-ymd');
                        if (clearedYmd) clearedYmd.dataset.original = '';
                        return;
                    }

                    var ymd = wrap.querySelector('.js-machining-date-ymd');
                    var display = wrap.querySelector('.machining-date-display');
                    var aid = wrap.querySelector('.js-machining-picker-aid');
                    var prev = String(wrap.dataset.prevYmd || '');
                    if (ymd) ymd.value = prev;
                    if (aid) aid.value = prev;
                    if (display) {
                        display.value = prev ? formatMachiningDateFromYmd(prev) : '';
                        syncMachiningDateDisplayState(display);
                    }
                    return;
                }

                if (!res.ok) throw new Error('Request failed');

                var okYmd = wrap.querySelector('.js-machining-date-ymd');
                if (okYmd) okYmd.dataset.original = okYmd.value || '';
                if (typeof window.showNotification === 'function') {
                    var okText = (data && data.message) ? data.message : (form.getAttribute('data-success') || 'Saved');
                    window.showNotification(okText, 'success', 2000);
                }
            } catch (e) {
                if (typeof window.notifyError === 'function') window.notifyError('Request failed', 2500);
            } finally {
                form.classList.remove('is-saving');
                if (typeof window.safeHideSpinner === 'function') window.safeHideSpinner();
            }
        }

        document.querySelectorAll('#machining-wo-table form.js-ajax .machining-date-input-wrap').forEach(function (wrap) {
            if (wrap.dataset.machiningDateBound === '1') return;
            wrap.dataset.machiningDateBound = '1';
            var ymd = wrap.querySelector('.js-machining-date-ymd');
            var display = wrap.querySelector('.machining-date-display');
            var aid = wrap.querySelector('.js-machining-picker-aid');
            if (!ymd || !display || !aid) return;

            aid.value = ymd.value || '';
            syncMachiningDateDisplayState(display);

            wrap.addEventListener('click', function (e) {
                if (e.target && e.target.closest('.machining-date-display')) {
                    e.preventDefault();
                    openMachiningDatePicker(aid);
                }
            });

            display.addEventListener('click', function (e) {
                e.preventDefault();
                openMachiningDatePicker(aid);
            });

            display.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    openMachiningDatePicker(aid);
                }
            });

            aid.addEventListener('change', function () {
                wrap.dataset.prevYmd = String(ymd.value || '');
                ymd.value = aid.value || '';
                display.value = aid.value ? formatMachiningDateFromYmd(aid.value) : '';
                syncMachiningDateDisplayState(display);
                var form = wrap.closest('form');
                if (!form) return;
                submitMachiningDateForm(form, wrap);
            });
        });
    }

    function initMachiningTableSearch() {
        var inp = document.getElementById('machiningTableSearch');
        if (!inp) return;
        inp.addEventListener('input', function () {
            var q = String(inp.value || '').trim().toLowerCase();
            document.querySelectorAll('#machining-sortable-tbody tr[data-machining-search]').forEach(function (tr) {
                var hay = tr.getAttribute('data-machining-search') || '';
                tr.classList.toggle('d-none', q !== '' && hay.indexOf(q) === -1);
            });
        });
    }

    function bootMachiningPage() {
        initMachiningNativeDateInputs();
        initMachiningTableSearch();

        @if($canReorderMachining ?? false)
        (function initMachiningSortable() {
            var tbody = document.getElementById('machining-sortable-tbody');
            if (!tbody || typeof Sortable === 'undefined') return;
            if (!tbody.querySelector('tr.machining-row-queued.machining-row-master')) return;

            var reorderUrl = @json(route('machining.reorder'));
            var token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            Sortable.create(tbody, {
                handle: '.machining-drag-handle',
                draggable: 'tr.machining-row-queued.machining-row-master',
                animation: 150,
                ghostClass: 'table-active',
                onMove: function (evt) {
                    var rel = evt.related;
                    if (rel && rel.classList.contains('machining-row-unqueued') && evt.willInsertAfter) {
                        return false;
                    }
                    return true;
                },
                onEnd: function () {
                    if (typeof window.safeShowSpinner === 'function') window.safeShowSpinner();
                    var ids = Array.from(tbody.querySelectorAll('tr.machining-row-queued.machining-row-master')).map(function (tr) {
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

        (function initMachiningPositionInputs() {
            var positionUrl = @json(route('machining.position'));
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

            document.querySelectorAll('.js-machining-position-input').forEach(function (inp) {
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
        @endif
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootMachiningPage);
    } else {
        bootMachiningPage();
    }
})();
</script>
