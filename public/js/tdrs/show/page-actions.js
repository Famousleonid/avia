window.ProcessesConfig = window.ProcessesConfig || {};
    ProcessesConfig.updateOrderUrl = ("" + String(window.TdrshowConfig0.value0) + "");
    ProcessesConfig.storeVendorUrl = ("" + String(window.TdrshowConfig0.value1) + "");

    window.tdrShowNotify = window.tdrShowNotify || function(message, type, duration) {
        const level = type || 'info';
        if (typeof window.showNotification === 'function') {
            window.showNotification(message, level, duration);
            return;
        }
        const handler = window.NotificationHandler;
        if (handler && typeof handler[level] === 'function') {
            handler[level](message);
            return;
        }
        console[level === 'error' ? 'error' : 'log'](message);
    };

    document.addEventListener('DOMContentLoaded', function() {
        var table = document.getElementById('tdr_process_Table');
        var tbody = table ? table.querySelector('tbody') : null;
        var reorderUrl = table ? table.getAttribute('data-reorder-url') : '';
        if (!tbody || !reorderUrl || typeof window.Sortable === 'undefined') return;

        var rows = tbody.querySelectorAll('tr[data-tdr-id]');
        if (rows.length < 2) return;

        var originalIds = [];
        var sortable = null;

        function currentIds() {
            return Array.from(tbody.querySelectorAll('tr[data-tdr-id]')).map(function(row) {
                return Number(row.getAttribute('data-tdr-id'));
            });
        }

        function setSaving(isSaving) {
            tbody.querySelectorAll('.tdr-row-drag-handle').forEach(function(handle) {
                handle.disabled = isSaving;
            });
            if (sortable) sortable.option('disabled', isSaving);
        }

        function restoreOrder(ids) {
            var rowsById = {};
            tbody.querySelectorAll('tr[data-tdr-id]').forEach(function(row) {
                rowsById[row.getAttribute('data-tdr-id')] = row;
            });
            var firstUtilityRow = tbody.querySelector('#tdrInlineCreateRow, #tdrInlineAddRow');
            ids.forEach(function(id) {
                var row = rowsById[String(id)];
                if (row) tbody.insertBefore(row, firstUtilityRow);
            });
        }

        sortable = window.Sortable.create(tbody, {
            animation: 150,
            draggable: 'tr[data-tdr-id]',
            handle: '.tdr-row-drag-handle',
            ghostClass: 'tdr-sortable-ghost',
            fallbackOnBody: true,
            swapThreshold: 0.65,
            onStart: function() {
                originalIds = currentIds();
            },
            onEnd: function(event) {
                if (event.oldIndex === event.newIndex) return;

                setSaving(true);
                fetch(reorderUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ tdr_ids: currentIds() }),
                    spinner: false
                })
                    .then(function(response) {
                        return response.json().catch(function() { return {}; }).then(function(data) {
                            if (!response.ok || data.success !== true) {
                                var validationMessage = data.errors && data.errors.tdr_ids
                                    ? data.errors.tdr_ids[0]
                                    : null;
                                throw new Error(validationMessage || data.message || ("" + String(window.TdrshowConfig0.value2) + ""));
                            }
                        });
                    })
                    .catch(function(error) {
                        restoreOrder(originalIds);
                        window.tdrShowNotify(error.message || ("" + String(window.TdrshowConfig0.value3) + ""), 'error');
                    })
                    .finally(function() {
                        setSaving(false);
                });
            }
        });
        table.setAttribute('data-sortable-ready', 'true');
    });

    window.syncTdrPrlPaperGroupFromDocument = function(freshDocument) {
        var currentGroup = document.getElementById('tdr-prl-paper-group');
        var freshGroup = freshDocument && freshDocument.getElementById
            ? freshDocument.getElementById('tdr-prl-paper-group')
            : null;
        if (!currentGroup || !freshGroup) return false;

        currentGroup.replaceWith(document.importNode(freshGroup, true));
        return true;
    };

    window.refreshTdrPrlPaperButtons = function() {
        if (window.tdrPrlPaperRefreshPromise) return window.tdrPrlPaperRefreshPromise;

        var showUrl = window.tdrShowFragmentsUrl;
        window.tdrPrlPaperRefreshPromise = fetch(showUrl, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
            credentials: 'same-origin',
            cache: 'no-store',
            spinner: false
        })
            .then(function(response) {
                if (!response.ok) throw new Error('HTTP ' + response.status);
                return response.text();
            })
            .then(function(html) {
                var freshDocument = new DOMParser().parseFromString(html, 'text/html');
                return window.syncTdrPrlPaperGroupFromDocument(freshDocument);
            })
            .catch(function(error) {
                console.warn('Failed to refresh PRL paper buttons', error);
                return false;
            })
            .finally(function() {
                window.tdrPrlPaperRefreshPromise = null;
            });

        return window.tdrPrlPaperRefreshPromise;
    };

    window.tdrShowConfirm = window.tdrShowConfirm || function(message, title, confirmLabel, cancelLabel) {
        return new Promise(function(resolve) {
            let modal = document.getElementById('tdrShowConfirmModal');
            if (!modal) {
                modal = document.createElement('div');
                modal.className = 'modal fade';
                modal.id = 'tdrShowConfirmModal';
                modal.tabIndex = -1;
                modal.innerHTML =
                    '<div class="modal-dialog modal-dialog-centered">' +
                        '<div class="modal-content bg-gradient">' +
                            '<div class="modal-header">' +
                                '<h5 class="modal-title"></h5>' +
                                '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>' +
                            '</div>' +
                            '<div class="modal-body"></div>' +
                            '<div class="modal-footer">' +
                                ("<button type=\"button\" class=\"btn btn-secondary\" data-bs-dismiss=\"modal\" data-confirm-cancel>" + String(window.TdrshowConfig0.value4) + "</button>") +
                                ("<button type=\"button\" class=\"btn btn-danger\" data-confirm-action>" + String(window.TdrshowConfig0.value5) + "</button>") +
                            '</div>' +
                        '</div>' +
                    '</div>';
                document.body.appendChild(modal);
            }

            if (!window.bootstrap || !bootstrap.Modal) {
                window.tdrShowNotify(message, 'warning');
                resolve(false);
                return;
            }

            modal.querySelector('.modal-title').textContent = title || ("" + String(window.TdrshowConfig0.value6) + "");
            modal.querySelector('.modal-body').textContent = message;
            const confirmBtn = modal.querySelector('[data-confirm-action]');
            const cancelBtn = modal.querySelector('[data-confirm-cancel]');
            confirmBtn.textContent = confirmLabel || ("" + String(window.TdrshowConfig0.value7) + "");
            if (cancelBtn) cancelBtn.textContent = cancelLabel || ("" + String(window.TdrshowConfig0.value8) + "");
            let confirmed = false;
            const instance = bootstrap.Modal.getOrCreateInstance(modal);

            function cleanup() {
                confirmBtn.removeEventListener('click', onConfirm);
                modal.removeEventListener('hidden.bs.modal', onHidden);
            }

            function onConfirm() {
                confirmed = true;
                cleanup();
                instance.hide();
                resolve(true);
            }

            function onHidden() {
                cleanup();
                resolve(confirmed);
            }

            confirmBtn.addEventListener('click', onConfirm, { once: true });
            modal.addEventListener('hidden.bs.modal', onHidden, { once: true });
            instance.show();
        });
    };
