<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
(function () {
    var canManagePaintQueue = @json($canReorderPaint ?? false);

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
     * Дата на экране: dd.mon.yyyy (напр. 02.feb.2026); в PATCH уходит Y-m-d со скрытого поля.
     * Выбор — нативный input[type=date] (скрыт), вызов через showPicker по клику на ячейку.
     */
    function formatPaintDateFromYmd(ymd) {
        var s = String(ymd || '').trim();
        if (!/^\d{4}-\d{2}-\d{2}$/.test(s)) return '';
        var p = s.split('-');
        var d = new Date(parseInt(p[0], 10), parseInt(p[1], 10) - 1, parseInt(p[2], 10));
        var months = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];
        return String(d.getDate()).padStart(2, '0') + '.' + months[d.getMonth()] + '.' + d.getFullYear();
    }

    function syncPaintDateDisplayState(display) {
        var empty = !String(display.value || '').trim();
        display.classList.toggle('paint-date-empty', empty);
        display.classList.toggle('has-finish', !empty);
    }

    function paintDateTitle(kind, userName) {
        var label = kind === 'date_finish' ? 'Finish date' : 'Start date';
        return userName ? (label + ' last edited by ' + userName) : label;
    }

    function syncPaintRowClosedState(row) {
        if (!row) return;
        var start = row.querySelector('input[name="date_start"].js-paint-date-ymd');
        var finish = row.querySelector('input[name="date_finish"].js-paint-date-ymd');
        var closed = !!(start && start.value && finish && finish.value);
        row.setAttribute('data-paint-closed', closed ? '1' : '0');
        row.classList.toggle('paint-row-closed', closed);
        row.classList.toggle('paint-row-open', !closed);
    }

    function syncPaintQueueAddControl(row) {
        if (!canManagePaintQueue) return;
        if (!row || row.classList.contains('paint-row-queued')) return;
        var woId = row.getAttribute('data-wo-id') || '';
        if (!woId) return;

        var rows = Array.from(document.querySelectorAll('#paint-sortable-tbody tr[data-wo-id="' + woId + '"]'));
        var masterRow = rows.find(function (r) { return r.classList.contains('paint-row-master'); }) || row;
        if (masterRow.classList.contains('paint-row-queued')) return;

        var cell = masterRow.querySelector('.paint-col-priority');
        if (!cell) return;

        var fullyClosed = rows.length > 0 && rows.every(function (r) {
            var finish = r.querySelector('input[name="date_finish"].js-paint-date-ymd');
            if (finish) return !!finish.value;
            return r.getAttribute('data-paint-closed') === '1';
        });

        if (fullyClosed) {
            cell.innerHTML = '<span class="text-muted small" title="Clear finish date before returning to queue">&mdash;</span>';
            return;
        }

        if (cell.querySelector('.js-paint-position-input')) return;

        var input = document.createElement('input');
        input.type = 'text';
        input.inputMode = 'numeric';
        input.autocomplete = 'off';
        input.className = 'form-control js-paint-position-input dir-input paint-queue-position-input is-unqueued text-info';
        input.dataset.woId = woId;
        input.dataset.inQueue = '0';
        input.dataset.was = '0';
        input.value = '';
        input.placeholder = '+';
        input.title = 'Enter queue position (0 = not in queue)';
        cell.replaceChildren(input);

        if (typeof window.paintBindPositionInput === 'function') {
            window.paintBindPositionInput(input);
        }
    }

    function syncPaintDateFieldFromPayload(row, fieldName, ymd, userName) {
        if (!row) return;
        var hidden = row.querySelector('input[name="' + fieldName + '"].js-paint-date-ymd');
        if (!hidden) return;

        var wrap = hidden.closest('.paint-date-input-wrap');
        var display = wrap ? wrap.querySelector('.paint-date-display') : null;
        var aid = wrap ? wrap.querySelector('.js-paint-picker-aid') : null;
        var normalized = ymd || '';

        hidden.value = normalized;
        hidden.dataset.original = normalized;
        if (aid) aid.value = normalized;
        if (display) {
            display.value = normalized ? formatPaintDateFromYmd(normalized) : '';
            syncPaintDateDisplayState(display);
            display.title = paintDateTitle(fieldName, userName || '');
        }
    }

    function openPaintDatePicker(aid) {
        if (typeof aid.showPicker === 'function') {
            try {
                aid.showPicker();
                return;
            } catch (_) {}
        }
        aid.focus();
    }

    function initPaintNativeDateInputs() {
        function clearPaintDateWrap(wrap) {
            var ymd = wrap.querySelector('.js-paint-date-ymd');
            var display = wrap.querySelector('.paint-date-display');
            var aid = wrap.querySelector('.js-paint-picker-aid');
            if (ymd) ymd.value = '';
            if (aid) aid.value = '';
            if (display) {
                display.value = '';
                syncPaintDateDisplayState(display);
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

        async function submitPaintDateForm(form, wrap) {
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
                        clearPaintDateWrap(wrap);
                        var clearedYmd = wrap.querySelector('.js-paint-date-ymd');
                        if (clearedYmd) clearedYmd.dataset.original = '';
                        return;
                    }

                    var ymd = wrap.querySelector('.js-paint-date-ymd');
                    var display = wrap.querySelector('.paint-date-display');
                    var aid = wrap.querySelector('.js-paint-picker-aid');
                    var prev = String(wrap.dataset.prevYmd || '');
                    if (ymd) ymd.value = prev;
                    if (aid) aid.value = prev;
                    if (display) {
                        display.value = prev ? formatPaintDateFromYmd(prev) : '';
                        syncPaintDateDisplayState(display);
                    }
                    return;
                }

                if (!res.ok) throw new Error('Request failed');

                if (data && data.paint_queue_changed) {
                    window.location.reload();
                    return;
                }

                var okYmd = wrap.querySelector('.js-paint-date-ymd');
                if (okYmd) okYmd.dataset.original = okYmd.value || '';
                var display = wrap.querySelector('.paint-date-display');
                if (display && okYmd) {
                    var userName = okYmd.name === 'date_finish' ? data.date_finish_user : data.date_start_user;
                    display.title = paintDateTitle(okYmd.name, userName || data.user || '');
                }
                var row = wrap.closest('tr[data-wo-id]');
                if (row && Object.prototype.hasOwnProperty.call(data, 'date_start')) {
                    syncPaintDateFieldFromPayload(row, 'date_start', data.date_start, data.date_start_user || data.user || '');
                }
                if (row && Object.prototype.hasOwnProperty.call(data, 'date_finish')) {
                    syncPaintDateFieldFromPayload(row, 'date_finish', data.date_finish, data.date_finish_user || data.user || '');
                }
                syncPaintRowClosedState(row);
                syncPaintQueueAddControl(row);
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

        document.querySelectorAll('#paint-wo-table form.js-ajax .paint-date-input-wrap').forEach(function (wrap) {
            if (wrap.dataset.paintDateBound === '1') return;
            wrap.dataset.paintDateBound = '1';
            var ymd = wrap.querySelector('.js-paint-date-ymd');
            var display = wrap.querySelector('.paint-date-display');
            var aid = wrap.querySelector('.js-paint-picker-aid');
            if (!ymd || !display || !aid) return;

            aid.value = ymd.value || '';
            syncPaintDateDisplayState(display);

            wrap.addEventListener('click', function (e) {
                if (e.target && e.target.closest('.paint-date-display')) {
                    e.preventDefault();
                    openPaintDatePicker(aid);
                }
            });

            display.addEventListener('click', function (e) {
                e.preventDefault();
                openPaintDatePicker(aid);
            });

            display.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    openPaintDatePicker(aid);
                }
            });

            aid.addEventListener('change', function () {
                wrap.dataset.prevYmd = String(ymd.value || '');
                ymd.value = aid.value || '';
                display.value = aid.value ? formatPaintDateFromYmd(aid.value) : '';
                syncPaintDateDisplayState(display);
                var form = wrap.closest('form');
                if (!form) return;
                submitPaintDateForm(form, wrap);
            });
        });
    }

    function initPaintTableSearch() {
        var inp = document.getElementById('paintTableSearch');
        var hideClosed = document.getElementById('paintHideClosedRows');
        if (!inp) return;

        var KEY = 'paint_hide_closed_rows';
        if (hideClosed) {
            try {
                hideClosed.checked = localStorage.getItem(KEY) === '1';
            } catch (_) {
                hideClosed.checked = false;
            }
        }

        function applyFilter() {
            var q = String(inp.value || '').trim().toLowerCase();
            var needHideClosed = !!(hideClosed && hideClosed.checked);
            document.querySelectorAll('#paint-sortable-tbody tr[data-paint-search]').forEach(function (tr) {
                var hay = tr.getAttribute('data-paint-search') || '';
                var isClosed = tr.getAttribute('data-paint-closed') === '1';
                var hideBySearch = q !== '' && hay.indexOf(q) === -1;
                var hideByClosed = needHideClosed && isClosed;
                tr.classList.toggle('d-none', hideBySearch || hideByClosed);
            });
        }

        inp.addEventListener('input', applyFilter);
        if (hideClosed) {
            hideClosed.addEventListener('change', function () {
                try {
                    localStorage.setItem(KEY, hideClosed.checked ? '1' : '0');
                } catch (_) {}
                applyFilter();
            });
        }

        applyFilter();
    }

    function initPaintLostSearch() {
        var wrap = document.getElementById('paintLostCountWrap');
        var inp = document.getElementById('paintLostSearch');
        var textEl = document.getElementById('paintLostCountText');
        if (!wrap || !inp || !textEl) return;

        function applyFilter() {
            var total = parseInt(wrap.getAttribute('data-total') || '0', 10) || 0;
            var q = String(inp.value || '').trim().toLowerCase();
            var items = document.querySelectorAll('#paintLostPanel .paint-lost-item');
            var visible = 0;
            items.forEach(function (el) {
                var hay = String(el.getAttribute('data-paint-lost-search') || '').toLowerCase();
                var show = q === '' || hay.indexOf(q) !== -1;
                el.classList.toggle('d-none', !show);
                if (show) visible++;
            });
            if (q === '') {
                textEl.textContent = total + (total === 1 ? ' part' : ' parts');
            } else {
                textEl.textContent = visible + ' of ' + total + (total === 1 ? ' part' : ' parts');
            }
        }

        inp.addEventListener('input', applyFilter);
    }

    function initPaintLostDelete() {
        if (!document.getElementById('paintLostPanel')) return;
        var token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        function runPaintLostDelete(url) {
            if (typeof window.safeShowSpinner === 'function') window.safeShowSpinner();
            fetch(url, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            }).then(function (r) {
                if (r.ok) {
                    window.location.reload();
                    return;
                }
                return r.json().then(function (d) {
                    var msg = (d && d.message) ? d.message : 'Delete failed';
                    if (typeof window.notifyError === 'function') window.notifyError(msg, 2500);
                });
            }).catch(function () {
                if (typeof window.notifyError === 'function') window.notifyError('Network error', 2500);
            }).finally(function () {
                if (typeof window.safeHideSpinner === 'function') window.safeHideSpinner();
            });
        }

        document.querySelectorAll('.js-paint-lost-delete').forEach(function (btnDel) {
            btnDel.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var url = btnDel.getAttribute('data-delete-url');
                if (!url) return;

                if (typeof window.confirmDialog === 'function') {
                    window.confirmDialog({
                        title: 'Delete',
                        message: 'Delete this lost part record?',
                        okText: 'Delete',
                        cancelText: 'Cancel',
                        danger: true
                    }).then(function (ok) {
                        if (ok) runPaintLostDelete(url);
                    });
                } else {
                    if (!window.confirm('Delete this record?')) return;
                    runPaintLostDelete(url);
                }
            });
        });
    }

    function initPaintLostParts() {
        var form = document.getElementById('paintLostForm');
        if (!form) return;

        var storeUrl = @json(route('paint.lost.store'));
        var errEl = document.getElementById('paintLostErr');
        var btn = document.getElementById('paintLostSubmit');
        var token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        var lostModalEl = document.getElementById('paintLostAddModal');

        function showErr(msg) {
            if (!errEl) return;
            errEl.textContent = msg || 'Error';
            errEl.classList.remove('d-none');
        }
        function hideErr() {
            if (!errEl) return;
            errEl.classList.add('d-none');
            errEl.textContent = '';
        }

        if (lostModalEl) {
            lostModalEl.addEventListener('show.bs.modal', function () {
                form.reset();
                hideErr();
            });
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            hideErr();
            if (btn) btn.disabled = true;

            var fd = new FormData(form);
            fetch(storeUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: fd
            }).then(function (r) {
                return r.text().then(function (text) {
                    var d = {};
                    try {
                        d = text ? JSON.parse(text) : {};
                    } catch (e) {
                        d = {};
                    }
                    if (!r.ok) {
                        var msg = (d && d.message) ? String(d.message) : '';
                        if (d && d.errors) {
                            var k = Object.keys(d.errors)[0];
                            if (k && d.errors[k] && d.errors[k][0]) msg = d.errors[k][0];
                        }
                        if (!msg) {
                            msg = r.status === 422 ? 'Validation failed' : ('Request failed' + (r.status ? ' (' + r.status + ')' : ''));
                        }
                        showErr(msg);
                        return;
                    }
                    if (typeof window.showNotification === 'function') {
                        window.showNotification((d && d.message) ? d.message : 'Saved', 'success', 2000);
                    }
                    if (lostModalEl && typeof bootstrap !== 'undefined') {
                        var m = bootstrap.Modal.getInstance(lostModalEl);
                        if (m) m.hide();
                    }
                    window.location.reload();
                });
            }).catch(function () {
                showErr('Network error');
            }).finally(function () {
                if (btn) btn.disabled = false;
            });
        });
    }

    function initPaintLostDrawer() {
        var wrap = document.getElementById('paintLostDrawerWrap');
        var btn = document.getElementById('paintLostDrawerToggle');
        if (!wrap || !btn) return;

        var KEY = 'paint_lost_drawer_open';
        var isOpen = false;
        var shouldAnimateOpen = false;
        try {
            var saved = sessionStorage.getItem(KEY);
            if (saved === '1') {
                shouldAnimateOpen = true;
            }
        } catch (_) {}

        function render() {
            wrap.classList.toggle('is-collapsed', !isOpen);
            btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            btn.setAttribute('aria-label', isOpen ? 'Hide lost panel' : 'Show lost panel');
            btn.title = isOpen ? 'Hide lost panel' : 'Show lost panel';
        }

        btn.addEventListener('click', function () {
            isOpen = !isOpen;
            render();
            try {
                sessionStorage.setItem(KEY, isOpen ? '1' : '0');
            } catch (_) {}
        });

        render();
        if (shouldAnimateOpen) {
            setTimeout(function () {
                isOpen = true;
                render();
            }, 90);
        }
    }

    function bootPaintPage() {
        initPaintNativeDateInputs();
        initPaintTableSearch();
        initPaintLostSearch();
        initPaintLostDelete();
        initPaintLostParts();
        initPaintLostDrawer();

        @if($canReorderPaint ?? false)
        (function initPaintSortable() {
            var tbody = document.getElementById('paint-sortable-tbody');
            if (!tbody || typeof Sortable === 'undefined') return;
            if (!tbody.querySelector('tr.paint-row-queued.paint-row-master')) return;

            var reorderUrl = @json(route('paint.reorder'));
            var token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            Sortable.create(tbody, {
                handle: '.paint-drag-handle',
                draggable: 'tr.paint-row-queued.paint-row-master',
                animation: 150,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'sortable-drag',
                onMove: function (evt) {
                    var rel = evt.related;
                    if (rel && rel.classList.contains('paint-row-unqueued') && evt.willInsertAfter) {
                        return false;
                    }
                    return true;
                },
                onEnd: function () {
                    if (typeof window.safeShowSpinner === 'function') window.safeShowSpinner();
                    var rawIds = Array.from(tbody.querySelectorAll('tr.paint-row-queued.paint-row-master')).map(function (tr) {
                        return parseInt(tr.getAttribute('data-wo-id'), 10);
                    }).filter(function (id) { return id > 0; });
                    var seen = {};
                    var ids = rawIds.filter(function (id) {
                        if (seen[id]) return false;
                        seen[id] = true;
                        return true;
                    });

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

            window.paintBindPositionInput = function (inp) {
                if (!inp || inp.dataset.paintPositionBound === '1') return;
                inp.dataset.paintPositionBound = '1';
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
            };

            document.querySelectorAll('.js-paint-position-input').forEach(window.paintBindPositionInput);
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
