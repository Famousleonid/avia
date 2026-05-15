<script>
    window.currentWorkorderId = {{ $current_wo->id }};
    window.tdrShowUrl = '{{ route("tdrs.show", ["id" => $current_wo->id]) }}';
</script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script src="{{ asset('js/tdr-processes/sortable-handler.js') }}"></script>
<script src="{{ asset('js/tdr-processes/form-link-handler.js') }}"></script>
<script src="{{ asset('js/tdr-processes/edit-process/edit-process.js') }}"></script>
<script src="{{ asset('js/delete-confirm-handler.js') }}"></script>
<script>
    window.ProcessesConfig = window.ProcessesConfig || {};
    ProcessesConfig.updateOrderUrl = '{{ route("tdr-processes.update-order") }}';
    ProcessesConfig.storeVendorUrl = '{{ route("vendors.store") }}';

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

    window.tdrShowConfirm = window.tdrShowConfirm || function(message, title, confirmLabel) {
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
                                '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __("Cancel") }}</button>' +
                                '<button type="button" class="btn btn-danger" data-confirm-action>{{ __("Delete") }}</button>' +
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

            modal.querySelector('.modal-title').textContent = title || '{{ __("Delete Confirmation") }}';
            modal.querySelector('.modal-body').textContent = message;
            const confirmBtn = modal.querySelector('[data-confirm-action]');
            confirmBtn.textContent = confirmLabel || '{{ __("Delete") }}';
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
</script>
@include('admin.tdrs.partials.component-inspection-scripts')
@include('admin.tdrs.partials.all-parts-group-forms-modal-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var pendingTdrNotification = null;
    try {
        pendingTdrNotification = window.sessionStorage.getItem('tdrShowPendingNotification');
        if (pendingTdrNotification) {
            window.sessionStorage.removeItem('tdrShowPendingNotification');
            window.tdrShowNotify(pendingTdrNotification, 'success', 2500);
        }
    } catch (e) {}

    var tdrShowTabListEl = document.getElementById('tdrShowTabList');
    var tdrShowTabsHeaderEl = document.getElementById('tdrShowTabsHeader');
    var tdrShowTabsLoadingEl = document.getElementById('tdrShowTabsLoading');
    var tdrShowTabContentEl = document.getElementById('tdrShowTabContent');
    var TAB_STORAGE_KEY = 'tdr_show_active_tab_wo_{{ $current_wo->id }}';
    var PERSISTENT_TAB_IDS = [
        'tab-tdr',
        'tab-log-card',
        'tab-bushing',
        'tab-rm-reports',
        'tab-transfers'
    ];

    function isPersistentTabId(tabId) {
        return !!tabId && PERSISTENT_TAB_IDS.indexOf(tabId) !== -1;
    }

    function revealTabsContent() {
        if (tdrShowTabsHeaderEl) {
            tdrShowTabsHeaderEl.style.visibility = '';
        }
        if (tdrShowTabContentEl) {
            tdrShowTabContentEl.style.visibility = '';
        }
        if (tdrShowTabsLoadingEl) {
            tdrShowTabsLoadingEl.style.display = 'none';
        }
    }
    var editTdrModal = document.getElementById('editTdrModal');
    var processesBodyUrl = '{{ route("tdr-processes.processesBody", ["tdrId" => "__ID__"]) }}';
    var storeProcessUrl = '{{ route("tdr-processes.store") }}';
    var processOptionsUrl = '{{ route("processes.getProcesses") }}';
    var editFormUrl = '{{ route("tdr-processes.editForm", ["id" => "__ID__"]) }}';
    var updateOrderUrl = '{{ route("tdr-processes.update-order") }}';
    var body = document.getElementById('componentProcessesTabBody');
    var activeProcessesContainer = body;
    var tabLi = document.getElementById('tab-part-processes-li');
    var tabBtn = document.getElementById('tab-part-processes');
    var partProcessesShortcutActions = document.getElementById('partProcessesShortcutActions');
    var woNum = document.getElementById('compProcessesWoNumber');
    var itemName = document.getElementById('compProcessesName');
    var itemIpl = document.getElementById('compProcessesIpl');
    var itemPn = document.getElementById('compProcessesPn');
    var itemSn = document.getElementById('compProcessesSn');
    var addProcessBtn = document.getElementById('compProcessesAddProcessBtn');
    var compProcessesGroupFormsBtn = document.getElementById('compProcessesGroupFormsBtn');
    var allPartsProcessesUrl = '{{ route("tdrs.processesPartial", ["workorder_id" => $current_wo->id]) }}';
    var allPartsBody = document.getElementById('allPartsProcessesTabBody');
    {{-- TODO(tdr-refactor): Replace this hardcoded compatibility URL with route() after route-cache drift is no longer a deploy risk. --}}
    var extraPartsProcessesUrl = @json(url('/extra_processes/partial/'.$current_wo->id));
    var extraPartsBody = document.getElementById('extraPartsProcessesTabBody');
    var extraProcessesTabBody = document.getElementById('extraProcessesTabBody');
    var tabExtraProcessesLi = document.getElementById('tab-extra-processes-li');
    var tabExtraProcessesBtn = document.getElementById('tab-extra-processes');
    var extraProcessesProcessesUrl = '{{ route("extra_processes.processesPartial", ["workorderId" => "__WO__", "componentId" => "__COMP__"]) }}';
    var editExtraProcessUrl = '{{ route("extra_processes.edit_component", ["id" => "__ID__"]) }}';
    var addExtraProcessUrl = '{{ route("extra_processes.create_processes", ["workorderId" => "__WO__", "componentId" => "__COMP__"]) }}';
    var editExtraProcessProcessUrl = '{{ route("extra_processes.edit", ["extra_process" => "__ID__"]) }}';
    var addExtraPartCreateUrl = '{{ route("extra_process.create", ["id" => "__ID__"]) }}';
    var tabExtraPartsProcessesBtn = document.getElementById('tab-extra-parts-processes');
    var extraGroupFormsHeaderBtn = document.getElementById('extraGroupFormsHeaderBtn');
    var extraPartsTabActions = document.getElementById('extraPartsTabActions');
    var allPartsGroupFormsTabActions = document.getElementById('allPartsGroupFormsTabActions');
    var logCardTabBody = document.getElementById('logCardTabBody');
    var logCardPartialUrl = '{{ route("log_card.partial", ["workorder_id" => $current_wo->id]) }}';
    var transfersTabBody = document.getElementById('transfersTabBody');
    var transfersPartialUrl = @json(($hasTransfers ?? false) ? route('transfers.partial', ['workorder' => $current_wo->id]) : null);
    var transfersTabActions = document.getElementById('transfersTabActions');
    var transfersSnCell = null;
    var transfersUpdateSnUrlTemplate = '{{ route("transfers.updateSn", ["id" => "__ID__"]) }}';
    var bushingTabBody = document.getElementById('bushingTabBody');
    var bushingPartialUrl = '{{ route("wo_bushings.partial", ["workorder_id" => $current_wo->id]) }}';
    var rmReportsTabBody = document.getElementById('rmReportsTabBody');
    var rmReportsPartialUrl = '{{ route("rm_reports.partial", ["workorder_id" => $current_wo->id]) }}';
    var logCardStoreUrl = '{{ route("log_card.store") }}';
    var logCardUpdateUrlTemplate = '{{ route('log_card.update', ['log_card' => 9999991]) }}'.replace('9999991', '__LC__');
    var logCardInlineFieldUpdateUrlTemplate = '{{ route('log_card.inline_field.update', ['log_card' => 9999991]) }}'.replace('9999991', '__LC__');
    var logCardDeleteUrlTemplate = '{{ route('log_card.destroy', ['log_card' => 9999991]) }}'.replace('9999991', '__LC__');
    var editBushingUrl = '{{ route("wo_bushings.edit", ["wo_bushing" => "__ID__"]) }}';
    var getProcessesBaseUrl = '{{ url("/get-processes") }}';

    function syncAllPartsGroupFormsBtnVisibility() {
        var btn = document.getElementById('allPartsGroupFormsBtn');
        if (!btn) return;
        if (!allPartsBody) {
            btn.classList.add('d-none');
            return;
        }
        var hasModal = !!allPartsBody.querySelector('#groupFormsModal');
        if (hasModal) btn.classList.remove('d-none');
        else btn.classList.add('d-none');
    }

    function loadAllPartsProcesses() {
        if (!allPartsBody) return;
        allPartsBody.innerHTML = '<div class="text-center py-5 text-muted">{{ __("Loading...") }}</div>';
        syncAllPartsGroupFormsBtnVisibility();
        fetch(allPartsProcessesUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }, spinner: false })
            .then(function(r) { return r.text(); })
            .then(function(html) {
                allPartsBody.innerHTML = html;
                initAllPartsGroupForms(allPartsBody);
                syncAllPartsGroupFormsBtnVisibility();
            })
            .catch(function() {
                allPartsBody.innerHTML = '<div class="alert alert-danger">{{ __("Failed to load.") }}</div>';
                syncAllPartsGroupFormsBtnVisibility();
            });
    }

    function updateExtraPartsTabAsterisk() {
        if (!tabExtraPartsProcessesBtn) return;
        var wrapper = extraPartsBody && extraPartsBody.querySelector('.extra-part-processes');
        var hasRecords = wrapper && wrapper.dataset.hasRecords === '1';
        var baseText = tabExtraPartsProcessesBtn.dataset.baseText || '{{ __("Extra Parts Processes") }}';
        tabExtraPartsProcessesBtn.textContent = baseText + (hasRecords ? ' *' : '');
    }
    function updateExtraGroupFormsButtonVisibility() {
        if (!extraGroupFormsHeaderBtn) return;
        var wrapper = extraPartsBody && extraPartsBody.querySelector('.extra-part-processes');
        var count = wrapper && wrapper.dataset.extraProcessCount ? parseInt(wrapper.dataset.extraProcessCount, 10) : 0;
        var show = count > 1;
        if (show) extraGroupFormsHeaderBtn.classList.remove('d-none');
        else extraGroupFormsHeaderBtn.classList.add('d-none');
    }
    function loadExtraPartProcesses() {
        if (!extraPartsBody) return;
        extraPartsBody.innerHTML = '<div class="text-center py-5 text-muted">{{ __("Loading...") }}</div>';
        fetch(extraPartsProcessesUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }, spinner: false })
            .then(function(r) { return r.text(); })
            .then(function(html) {
                extraPartsBody.innerHTML = html;
                initExtraPartGroupForms(extraPartsBody);
                updateExtraPartsTabAsterisk();
                updateExtraGroupFormsButtonVisibility();
            })
            .catch(function() {
                extraPartsBody.innerHTML = '<div class="alert alert-danger">{{ __("Failed to load.") }}</div>';
            });
    }

    function loadBushingPartial() {
        if (!bushingTabBody) return;
        bushingTabBody.innerHTML = '<div class="text-center py-5 text-muted">{{ __("Loading...") }}</div>';
        fetch(bushingPartialUrl + (bushingPartialUrl.indexOf('?') >= 0 ? '&' : '?') + '_=' + Date.now(), { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }, credentials: 'same-origin', cache: 'no-store' })
            .then(function(r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.text().then(function(html) {
                    return { response: r, html: html };
                });
            })
            .then(function(payload) {
                var r = payload.response;
                var html = payload.html;
                var finalUrl = (r && r.url) ? String(r.url) : '';
                var looksLikeLoginUrl = finalUrl.indexOf('/login') !== -1;
                var looksLikeLoginHtml = /name=["']email["']/i.test(html)
                    && /name=["']password["']/i.test(html)
                    && /remember/i.test(html);

                if (looksLikeLoginUrl || looksLikeLoginHtml) {
                    bushingTabBody.innerHTML = '<div class="alert alert-warning mb-0">{{ __("Session expired. Please log in again.") }}</div>';
                    window.tdrShowNotify('{{ __("Session expired. Please log in again.") }}', 'warning', 5000);
                    return;
                }

                bushingTabBody.innerHTML = html;
                bushingTabBody.dataset.loaded = '1';
                bushingTabBody.querySelectorAll('script').forEach(function(oldScript) {
                    var newScript = document.createElement('script');
                    Array.from(oldScript.attributes || []).forEach(function(attr) {
                        newScript.setAttribute(attr.name, attr.value);
                    });
                    newScript.textContent = oldScript.textContent;
                    oldScript.parentNode.replaceChild(newScript, oldScript);
                });
                var wrap = bushingTabBody.querySelector('.bushing-partial');
                if (wrap && bushingTabActions) {
                    var hasWoBushing = wrap.dataset.hasWoBushing === '1';
                    var editUrl = wrap.dataset.editUrl || '';
                    var specFormUrl = wrap.dataset.specFormUrl || '';
                    var woBushingId = wrap.dataset.woBushingId || '';
                    if (hasWoBushing && editUrl) {
                        bushingTabActions.innerHTML = '<button type="button" class="btn btn-outline-primary btn-sm open-edit-bushing-modal" data-wo-bushing-id="' + woBushingId + '"><i class="fas fa-edit"></i> {{ __("Update Bushings List") }}</button>';
                    } else if (wrap.dataset.hasBushings === '1') {
                        bushingTabActions.innerHTML = '<button type="submit" form="bushings-form" class="btn btn-success btn-sm"><i class="fas fa-plus"></i> {{ __("Create Bushing Data") }}</button><button type="button" class="btn btn-secondary btn-sm bushing-clear-btn"><i class="fas fa-eraser"></i> {{ __("Clear All") }}</button>';
                    }
                    if (hasWoBushing && specFormUrl) {
                        var headerBtn = document.getElementById('bushingSpFormHeaderBtn');
                        if (headerBtn && !headerBtn.querySelector('a[href]')) {
                            var a = document.createElement('a');
                            a.href = specFormUrl;
                            a.target = '_blank';
                            a.className = 'paper-btn btn-outline-primary paper-portrait p-0';
                            a.setAttribute('aria-label', '{{ __("Bushing SP Form") }}');
                            a.innerHTML = '<svg viewBox="0 0 190 270" width="60" height="80" preserveAspectRatio="xMidYMid meet" xmlns="http://www.w3.org/2000/svg"><path class="paper" d="M10 10 H140 L180 50 V240 H10 Z"/><polygon class="fold" points="140,10 140,50 180,50"/><path class="line" d="M140 12 V50 H180"/><foreignObject x="20" y="60" width="140" height="140"><div xmlns="http://www.w3.org/1999/xhtml" style="font: 34px Arial,sans-serif;text-align:center;display:flex;align-items:center;justify-content:center;height:100%;width:100%;word-wrap:break-word;">Bushing SP Form</div></foreignObject></svg>';
                            headerBtn.appendChild(a);
                        }
                    }
                }
            })
            .catch(function(err) {
                bushingTabBody.innerHTML = '<div class="alert alert-danger">{{ __("Failed to load.") }} (' + (err && err.message ? err.message : '') + ')</div>';
            });
    }

    function logCardTabCsrfToken() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.content : '';
    }

    function syncPartProcessesShortcutActions() {
        if (!partProcessesShortcutActions) return;
        var show = !!tabLi && !tabLi.classList.contains('d-none');
        partProcessesShortcutActions.classList.toggle('d-none', !show);
    }

    function syncProcessShortcutButtonState(targetSelector) {
        if (!partProcessesShortcutActions) return;
        partProcessesShortcutActions.querySelectorAll('[data-process-shortcut-target]').forEach(function(btn) {
            var isActive = targetSelector && btn.dataset.processShortcutTarget === targetSelector;
            btn.classList.toggle('active', !!isActive);
            btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
    }

    function showOnlyTdrTabPane(targetSelector) {
        if (!targetSelector) return;
        var pane = document.querySelector(targetSelector);
        if (!pane) return;
        if (tdrShowTabContentEl) {
            Array.prototype.forEach.call(tdrShowTabContentEl.children, function(item) {
                if (item.classList && item.classList.contains('tab-pane') && item !== pane) {
                    item.classList.remove('show', 'active');
                }
            });
        }
        pane.classList.add('show', 'active');
    }

    function activateDetachedTabPane(targetSelector) {
        if (!targetSelector) return;
        if (tdrShowTabListEl) {
            tdrShowTabListEl.querySelectorAll('.nav-link.active').forEach(function(item) {
                item.classList.remove('active');
                item.setAttribute('aria-selected', 'false');
            });
        }
        showOnlyTdrTabPane(targetSelector);
    }

    function openProcessShortcutPane(targetSelector) {
        if (!targetSelector) return;
        if (tabLi) tabLi.classList.remove('d-none');
        syncPartProcessesShortcutActions();
        syncProcessShortcutButtonState(targetSelector);
        if (logCardTabActions) logCardTabActions.classList.add('d-none');
        if (bushingTabActions) bushingTabActions.classList.add('d-none');
        if (transfersTabActions) transfersTabActions.classList.add('d-none');

        if (targetSelector.indexOf('content-all-parts-processes') !== -1) {
            if (allPartsGroupFormsTabActions) allPartsGroupFormsTabActions.classList.remove('d-none');
            if (extraGroupFormsHeaderBtn) extraGroupFormsHeaderBtn.classList.add('d-none');
            if (extraPartsTabActions) extraPartsTabActions.classList.add('d-none');
            syncAllPartsGroupFormsBtnVisibility();
            if (allPartsBody && !allPartsBody.dataset.loaded) {
                allPartsBody.dataset.loaded = '1';
                loadAllPartsProcesses();
            }
        } else if (targetSelector.indexOf('content-extra-parts-processes') !== -1) {
            if (allPartsGroupFormsTabActions) allPartsGroupFormsTabActions.classList.add('d-none');
            if (extraPartsTabActions) extraPartsTabActions.classList.remove('d-none');
            if (extraPartsBody && !extraPartsBody.dataset.loaded) {
                extraPartsBody.dataset.loaded = '1';
                loadExtraPartProcesses();
            } else {
                updateExtraPartsTabAsterisk();
                updateExtraGroupFormsButtonVisibility();
            }
        }

        activateDetachedTabPane(targetSelector);
    }

    if (partProcessesShortcutActions) {
        partProcessesShortcutActions.addEventListener('click', function(e) {
            var btn = e.target.closest('[data-process-shortcut-target]');
            if (!btn) return;
            e.preventDefault();
            openProcessShortcutPane(btn.dataset.processShortcutTarget);
        });
    }

    function logCardTabClearEditingUi() {
        var prim = document.getElementById('logCardEnterDataBtn');
        var sv = document.getElementById('logCardSaveBtn');
        var cx = document.getElementById('logCardCancelBtn');
        if (prim) prim.classList.remove('d-none');
        if (sv) sv.classList.add('d-none');
        if (cx) cx.classList.add('d-none');
    }

    function syncLogCardToolbarFromPartial() {
        var shell = document.getElementById('log-card-partial-shell');
        var btn = document.getElementById('logCardEnterDataBtn');
        var lid = shell && shell.getAttribute('data-log-card-id');
        var readOnly = shell && shell.getAttribute('data-readonly') === '1';
        var readOnlyMessage = shell ? (shell.getAttribute('data-readonly-message') || '') : '';
        if (btn) {
            if (lid) {
                btn.setAttribute('data-log-card-id', lid);
                btn.setAttribute('data-has-log', '1');
                btn.classList.remove('btn-success');
                btn.classList.add('btn-danger');
                btn.innerHTML = '<i class="fas fa-undo"></i> {{ __("Reset Log card") }}';
            } else {
                btn.setAttribute('data-log-card-id', '');
                btn.setAttribute('data-has-log', '0');
                btn.classList.remove('btn-danger');
                btn.classList.add('btn-success');
                btn.innerHTML = '<i class="fas fa-keyboard"></i> {{ __("Create Log card") }}';
            }

            btn.dataset.readonly = readOnly ? '1' : '0';
            btn.dataset.readonlyMessage = readOnlyMessage;
            btn.disabled = !!readOnly;
            btn.title = readOnlyMessage;
        }
        var sv = document.getElementById('logCardSaveBtn');
        var cx = document.getElementById('logCardCancelBtn');
        if (sv) sv.classList.add('d-none');
        if (cx) cx.classList.add('d-none');
        var paperWrap = document.getElementById('logCardFormPaperWrap');
        if (paperWrap && shell) {
            if (lid) {
                paperWrap.classList.remove('d-none');
            } else {
                paperWrap.classList.add('d-none');
            }
        }
    }

    function logCardTabIsReadOnly() {
        var shell = document.getElementById('log-card-partial-shell');
        return !!(shell && shell.getAttribute('data-readonly') === '1');
    }

    function logCardTabReadOnlyMessage() {
        var shell = document.getElementById('log-card-partial-shell');
        return shell ? (shell.getAttribute('data-readonly-message') || '') : '';
    }

    function logCardTabFindByName(root, fieldName) {
        if (!root || !fieldName) return null;
        var all = root.querySelectorAll('input, select');
        for (var i = 0; i < all.length; i++) {
            if (all[i].name === fieldName) return all[i];
        }
        return null;
    }

    function logCardTabSelectedAssembly(root, groupKey, componentId) {
        if (!root || !groupKey || !componentId) return null;
        var name = 'lc_selected_assembly[' + groupKey + ']';
        var all = Array.prototype.filter.call(root.querySelectorAll('input'), function(input) {
            return input.name === name && String(input.dataset.componentId || '') === String(componentId);
        });
        var fallback = null;
        for (var i = 0; i < all.length; i++) {
            if (!fallback) fallback = all[i];
            if ((all[i].type === 'radio' && all[i].checked) || all[i].type === 'hidden') {
                return all[i];
            }
        }

        return fallback;
    }

    function logCardTabAssemblyPayload(input) {
        if (!input || !input.value) return {};

        return {
            component_assembly_id: input.value,
            assy_part_number: input.dataset.assyPartNumber || '',
            assy_ipl_num: input.dataset.assyIplNum || '',
            units_assy: input.dataset.unitsAssy || ''
        };
    }

    function logCardTabSavedPayload(root) {
        if (!root) return null;
        var data = [];
        root.querySelectorAll('tr.lc-saved-row').forEach(function(row) {
            var item = {
                component_id: row.dataset.componentId || '',
                serial_number: '',
                assy_serial_number: '',
                reason: '',
                new_serial_number: ''
            };
            if (row.dataset.iplGroup) item.ipl_group = row.dataset.iplGroup;
            if (row.dataset.componentAssemblyId) item.component_assembly_id = row.dataset.componentAssemblyId;
            if (row.dataset.assyPartNumber) item.assy_part_number = row.dataset.assyPartNumber;
            if (row.dataset.assyIplNum) item.assy_ipl_num = row.dataset.assyIplNum;
            if (row.dataset.unitsAssy) item.units_assy = row.dataset.unitsAssy;
            row.querySelectorAll('input, select').forEach(function(field) {
                if (field.name) item[field.name] = field.value || '';
            });
            if (item.component_id) data.push(item);
        });

        return data.length ? data : null;
    }

    function logCardTabBuildPayload() {
        var metaEl = document.getElementById('log-card-tab-meta');
        var root = document.getElementById('log-card-partial-shell');
        if (!metaEl || !root) return null;
        if (root.dataset.state === 'saved') {
            return logCardTabSavedPayload(root);
        }
        var meta;
        try { meta = JSON.parse(metaEl.textContent); } catch (e) { return null; }
        var groupMap = meta.group_map || {};
        var data = [];
        var gKeys = Array.isArray(meta.group_keys_ordered) && meta.group_keys_ordered.length
            ? meta.group_keys_ordered
            : Object.keys(groupMap);
        gKeys.forEach(function(gix) {
            var ipl = groupMap[gix];
            var bnSel = 'lc_selected_component[' + gix + ']';
            var sel = null;
            var rAll = root.querySelectorAll('input[type="radio"]');
            for (var ri = 0; ri < rAll.length; ri++) {
                if (rAll[ri].name === bnSel && rAll[ri].checked) {
                    sel = rAll[ri];
                    break;
                }
            }
            if (!sel) {
                sel = logCardTabFindByName(root, bnSel);
            }
            if (!sel || !sel.value) return;
            var snEl = logCardTabFindByName(root, 'lc_serial_numbers[' + gix + ']');
            var asEl = logCardTabFindByName(root, 'lc_assy_serial_numbers[' + gix + ']');
            var rsEl = logCardTabFindByName(root, 'lc_reasons[' + gix + ']');
            var row = {
                component_id: sel.value,
                ipl_group: ipl,
                serial_number: snEl ? snEl.value : '',
                assy_serial_number: asEl ? asEl.value : '',
                reason: rsEl && rsEl.value ? rsEl.value : '',
                new_serial_number: ''
            };
            Object.assign(row, logCardTabAssemblyPayload(logCardTabSelectedAssembly(root, gix, sel.value)));
            data.push(row);
        });
        var sepKeys = [];
        root.querySelectorAll('input[type="hidden"][name*="separate_"]').forEach(function(inp) {
            var m = inp.name.match(/lc_selected_component\[(separate_\d+)\]/);
            if (m && sepKeys.indexOf(m[1]) === -1) sepKeys.push(m[1]);
        });
        sepKeys.sort(function(a, b) {
            var na = parseInt(String(a).replace('separate_', ''), 10);
            var nb = parseInt(String(b).replace('separate_', ''), 10);
            return na - nb;
        });
        sepKeys.forEach(function(sk) {
            var hs = logCardTabFindByName(root, 'lc_selected_component[' + sk + ']');
            if (!hs || !hs.value) return;
            var snEl = logCardTabFindByName(root, 'lc_serial_numbers[' + sk + ']');
            var asEl = logCardTabFindByName(root, 'lc_assy_serial_numbers[' + sk + ']');
            var rsEl = logCardTabFindByName(root, 'lc_reasons[' + sk + ']');
            var row = {
                component_id: hs.value,
                serial_number: snEl ? snEl.value : '',
                assy_serial_number: asEl ? asEl.value : '',
                reason: rsEl && rsEl.value ? rsEl.value : '',
                new_serial_number: ''
            };
            if (hs.dataset.unitIndex) row.unit_index = hs.dataset.unitIndex;
            if (hs.dataset.unitsAssy) row.units_assy = hs.dataset.unitsAssy;
            Object.assign(row, logCardTabAssemblyPayload(logCardTabSelectedAssembly(root, sk, hs.value)));
            data.push(row);
        });
        return data.length ? data : null;
    }

    function syncLogCardDraftAssyChoices(root) {
        if (!root || root.dataset.state !== 'draft') return;

        root.querySelectorAll('.lc-assy-choice[data-component-id]').forEach(function(choice) {
            var componentId = choice.dataset.componentId || '';
            var selector = 'input[name^="lc_selected_component"][value="' + componentId.replace(/"/g, '\\"') + '"]';
            var componentInput = root.querySelector(selector);
            var isSelected = !!componentInput && (componentInput.type === 'hidden' || componentInput.checked);

            choice.querySelectorAll('.lc-assy-radio').forEach(function(radio) {
                radio.classList.toggle('d-none', !isSelected);
            });
            if (isSelected) {
                var checked = choice.querySelector('input[type="radio"]:checked');
                var firstRadio = choice.querySelector('input[type="radio"]');
                if (!checked && firstRadio) firstRadio.checked = true;
            }
        });
    }

    function loadLogCardPartial() {
        if (!logCardTabBody) return;
        logCardTabBody.innerHTML = '<div class="text-center py-5 text-muted">{{ __("Loading...") }}</div>';
        fetch(logCardPartialUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }, credentials: 'same-origin' })
            .then(function(r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.text();
            })
            .then(function(html) {
                logCardTabBody.innerHTML = html;
                syncLogCardToolbarFromPartial();
                syncLogCardDraftAssyChoices(document.getElementById('log-card-partial-shell'));
            })
            .catch(function(err) {
                logCardTabBody.innerHTML = '<div class="alert alert-danger">{{ __("Failed to load.") }} (' + (err && err.message ? err.message : '') + ')</div>';
            });
    }

    function loadTransfersPartial() {
        if (!transfersTabBody || !transfersPartialUrl) return;
        transfersTabBody.innerHTML = '<div class="text-center py-5 text-muted">{{ __("Loading...") }}</div>';
        fetch(transfersPartialUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }, credentials: 'same-origin' })
            .then(function(r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.text();
            })
            .then(function(html) {
                transfersTabBody.innerHTML = html;
            })
            .catch(function(err) {
                transfersTabBody.innerHTML = '<div class="alert alert-danger">{{ __("Failed to load.") }} (' + (err && err.message ? err.message : '') + ')</div>';
            });
    }

    // STD list paper buttons are always visible in the TDR header.
    window.updateTdrStdPaperButtonsFromCounts = function(c) {
        return c;
    };

    function loadRmReportsPartial() {
        if (!rmReportsTabBody) return;
        rmReportsTabBody.innerHTML = '<div class="text-center py-5 text-muted">{{ __("Loading...") }}</div>';
        fetch(rmReportsPartialUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }, credentials: 'same-origin' })
            .then(function(r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.text();
            })
            .then(function(html) {
                rmReportsTabBody.innerHTML = html;
                rmReportsTabBody.dataset.loaded = '1';
                rmReportsTabBody.querySelectorAll('script').forEach(function(oldScript) {
                    var newScript = document.createElement('script');
                    Array.from(oldScript.attributes || []).forEach(function(attr) {
                        newScript.setAttribute(attr.name, attr.value);
                    });
                    newScript.textContent = oldScript.textContent;
                    oldScript.parentNode.replaceChild(newScript, oldScript);
                });
            })
            .catch(function(err) {
                rmReportsTabBody.innerHTML = '<div class="alert alert-danger">{{ __("Failed to load.") }} (' + (err && err.message ? err.message : '') + ')</div>';
            });
    }

    function initExtraPartGroupForms(container) {
        if (!container) return;
        var vendorSelects = container.querySelectorAll('.vendor-select');
        var groupFormButtons = container.querySelectorAll('.group-form-button');
        var componentCheckboxes = container.querySelectorAll('.component-checkbox');
        function updateLinkUrl(processNameId) {
            var link = container.querySelector('.group-form-button[data-process-name-id="' + processNameId + '"]');
            if (!link || !link.getAttribute('href')) return;
            var url = new URL(link.getAttribute('href'), window.location.origin);
            var vendorSelect = container.querySelector('.vendor-select[data-process-name-id="' + processNameId + '"]');
            if (vendorSelect && vendorSelect.value) url.searchParams.set('vendor_id', vendorSelect.value);
            else url.searchParams.delete('vendor_id');
            var checkedBoxes = container.querySelectorAll('.component-checkbox[data-process-name-id="' + processNameId + '"]:checked:not([disabled])');
            if (checkedBoxes.length > 0) {
                url.searchParams.set('component_ids', Array.from(checkedBoxes).map(function(c){ return c.value; }).join(','));
            } else {
                url.searchParams.delete('component_ids');
            }
            link.setAttribute('href', url.toString());
        }
        function updateQuantityBadge(processNameId) {
            var checkedBoxes = container.querySelectorAll('.component-checkbox[data-process-name-id="' + processNameId + '"]:checked:not([disabled])');
            var badge = container.querySelector('.process-qty-badge[data-process-name-id="' + processNameId + '"]');
            if (badge && checkedBoxes.length > 0) {
                var totalQty = 0;
                checkedBoxes.forEach(function(c){ totalQty += parseInt(c.getAttribute('data-qty')) || 0; });
                badge.textContent = totalQty + ' pcs';
            }
        }
        vendorSelects.forEach(function(s){ s.addEventListener('change', function(){ updateLinkUrl(this.getAttribute('data-process-name-id')); }); });
        componentCheckboxes.forEach(function(c){ c.addEventListener('change', function(){ updateLinkUrl(this.getAttribute('data-process-name-id')); updateQuantityBadge(this.getAttribute('data-process-name-id')); }); });
        groupFormButtons.forEach(function(b){ b.addEventListener('click', function(){ updateLinkUrl(this.getAttribute('data-process-name-id')); }); });
        groupFormButtons.forEach(function(b){ var pid = b.getAttribute('data-process-name-id'); if (pid) { updateLinkUrl(pid); updateQuantityBadge(pid); } });
    }

    function loadExtraProcesses(workorderId, componentId) {
        if (!extraProcessesTabBody) return;
        extraProcessesTabBody.innerHTML = '<div class="text-center py-5 text-muted">{{ __("Loading...") }}</div>';
        var url = extraProcessesProcessesUrl.replace('__WO__', workorderId).replace('__COMP__', componentId);
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }, spinner: false })
            .then(function(r) { return r.text(); })
            .then(function(html) {
                extraProcessesTabBody.innerHTML = html;
                extraProcessesTabBody.dataset.workorderId = workorderId;
                extraProcessesTabBody.dataset.componentId = componentId;
                bindExtraProcessesHandlers(extraProcessesTabBody);
            })
            .catch(function() {
                extraProcessesTabBody.innerHTML = '<div class="alert alert-danger">{{ __("Failed to load.") }}</div>';
            });
    }

    function bindExtraProcessesHandlers(container) {
        if (!container) return;
        container.querySelectorAll('.form-link[data-extra-process-id][data-process-name-id]').forEach(function(link) {
            link.addEventListener('click', function() {
                var extraProcessId = link.getAttribute('data-extra-process-id');
                var processNameId = link.getAttribute('data-process-name-id');
                var vendorSelect = container.querySelector('select.vendor-select[data-extra-process-id="' + extraProcessId + '"][data-process-name-id="' + processNameId + '"]');
                var href = link.getAttribute('href');
                if (!href) return;
                var url = new URL(href, window.location.origin);
                if (vendorSelect && vendorSelect.value) {
                    url.searchParams.set('vendor_id', vendorSelect.value);
                } else {
                    url.searchParams.delete('vendor_id');
                }
                link.setAttribute('href', url.pathname + url.search + url.hash);
            });
        });
        container.querySelectorAll('.load-edit-extra-process-process').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var extraProcessId = btn.dataset.extraProcessId;
                var processIndex = btn.dataset.processIndex;
                var processNameId = btn.dataset.processNameId;
                var workorderId = btn.dataset.workorderId;
                var componentId = btn.dataset.componentId;
                if (!extraProcessId) return;
                var url = editExtraProcessProcessUrl.replace('__ID__', extraProcessId);
                if (processIndex !== undefined) url += '?process_index=' + processIndex + '&modal=1';
                else if (processNameId !== undefined) url += '?process_name_id=' + processNameId + '&modal=1';
                else url += '?modal=1';
                var editModal = document.getElementById('editTdrProcessModal');
                var iframe = document.getElementById('editTdrProcessIframe');
                if (!editModal || !iframe) return;
                iframe.src = url;
                var inst = bootstrap.Modal.getOrCreateInstance(editModal);
                inst.show();
                editModal.addEventListener('shown.bs.modal', function setZ() { editModal.style.zIndex = '1080'; var b = document.querySelectorAll('.modal-backdrop'); if (b.length) b[b.length-1].style.zIndex = '1075'; }, { once: true });
            });
        });
        container.querySelectorAll('.open-add-extra-process-modal').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                if (btn.dataset.workorderId && btn.dataset.componentId) openAddExtraProcessModal(btn.dataset.workorderId, btn.dataset.componentId);
            });
        });
        container.querySelectorAll('.delete-process-form').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                var fd = new FormData(form);
                fetch(form.action, { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '', 'Accept': 'application/json' }, body: fd })
                    .then(function(r) { return r.json().catch(function() { return {}; }); })
                    .then(function(data) {
                        if (data.success) {
                            var woId = container.dataset.workorderId || window.currentWorkorderId;
                            var compId = container.dataset.componentId;
                            if (woId && compId) loadExtraProcesses(woId, compId);
                            if (extraPartsBody && extraPartsBody.dataset.loaded) loadExtraPartProcesses();
                        } else {
                            window.tdrShowNotify(data.message || '{{ __("Failed to delete.") }}', 'error');
                        }
                    })
                    .catch(function() { window.tdrShowNotify('{{ __("Failed to delete.") }}', 'error'); });
            });
        });
        var addVendorBtn = container.querySelector('#saveVendorButtonExtra');
        var addVendorForm = container.querySelector('#addVendorFormExtra');
        if (addVendorBtn && addVendorForm && ProcessesConfig.storeVendorUrl) {
            addVendorBtn.onclick = function() {
                var nameInput = addVendorForm.querySelector('input[name="name"]');
                if (!nameInput || !nameInput.value.trim()) return;
                fetch(ProcessesConfig.storeVendorUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '', 'Accept': 'application/json', 'Content-Type': 'application/json' }, body: JSON.stringify({ name: nameInput.value.trim() }) })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.id) {
                            var m = bootstrap.Modal.getInstance(container.querySelector('#addVendorModalExtra'));
                            if (m) m.hide();
                            nameInput.value = '';
                            var woId = container.dataset.workorderId; var compId = container.dataset.componentId;
                            if (woId && compId) loadExtraProcesses(woId, compId);
                        }
                    });
            };
        }
    }

    function openEditExtraProcessModal(extraProcessId) {
        var iframe = document.getElementById('editExtraProcessIframe');
        var modal = document.getElementById('editExtraProcessModal');
        if (iframe && modal && extraProcessId) {
            iframe.src = editExtraProcessUrl.replace('__ID__', extraProcessId) + '?modal=1';
            var inst = bootstrap.Modal.getOrCreateInstance(modal);
            inst.show();
            modal.addEventListener('shown.bs.modal', function setZ() { modal.style.zIndex = '1080'; var b = document.querySelectorAll('.modal-backdrop'); if (b.length) b[b.length-1].style.zIndex = '1075'; }, { once: true });
        }
    }

    function openAddExtraProcessModal(workorderId, componentId) {
        var iframe = document.getElementById('addExtraProcessIframe');
        var modal = document.getElementById('addExtraProcessModal');
        if (iframe && modal && workorderId && componentId) {
            iframe.src = addExtraProcessUrl.replace('__WO__', workorderId).replace('__COMP__', componentId) + '?modal=1';
            var inst = bootstrap.Modal.getOrCreateInstance(modal);
            inst.show();
            modal.addEventListener('shown.bs.modal', function setZ() { modal.style.zIndex = '1080'; var b = document.querySelectorAll('.modal-backdrop'); if (b.length) b[b.length-1].style.zIndex = '1075'; }, { once: true });
        }
    }
    function openAddExtraPartModal(workorderId) {
        var iframe = document.getElementById('addExtraPartIframe');
        var modal = document.getElementById('addExtraPartModal');
        if (iframe && modal && workorderId) {
            iframe.src = addExtraPartCreateUrl.replace('__ID__', workorderId) + '?modal=1';
            var inst = bootstrap.Modal.getOrCreateInstance(modal);
            inst.show();
            modal.addEventListener('shown.bs.modal', function setZ() { modal.style.zIndex = '1080'; var b = document.querySelectorAll('.modal-backdrop'); if (b.length) b[b.length-1].style.zIndex = '1075'; }, { once: true });
        }
    }
    function initAllPartsGroupForms(container) {
        if (typeof window.initAllPartsGroupFormModalRows === 'function') {
            window.initAllPartsGroupFormModalRows(container);
        }
    }

    /** Group Process Forms: модалка на вкладке Part Processes (#partProcessesGroupFormsModal). */
    function initPartProcessesGroupForms(modal) {
        if (!modal) return;
        var vendorSelects = modal.querySelectorAll('.vendor-select');
        var groupFormButtons = modal.querySelectorAll('.group-form-button');
        var processCheckboxes = modal.querySelectorAll('.process-checkbox');
        function updateLinkUrl(processNameId) {
            var link = modal.querySelector('.group-form-button[data-process-name-id="' + processNameId + '"]');
            if (!link || !link.getAttribute('href')) return;
            var url = new URL(link.getAttribute('href'), window.location.origin);
            var existingTdrId = url.searchParams.get('tdrId');
            if (existingTdrId) {
                url.searchParams.set('tdrId', existingTdrId);
            }
            var vendorSelect = modal.querySelector('.vendor-select[data-process-name-id="' + processNameId + '"]');
            if (vendorSelect && vendorSelect.value) {
                url.searchParams.set('vendor_id', vendorSelect.value);
            } else {
                url.searchParams.delete('vendor_id');
            }
            var checkedBoxes = modal.querySelectorAll('.process-checkbox[data-process-name-id="' + processNameId + '"]:checked');
            if (checkedBoxes.length > 0) {
                url.searchParams.set('process_ids', Array.from(checkedBoxes).map(function(cb) { return cb.value; }).join(','));
            } else {
                url.searchParams.set('process_ids', '');
            }
            link.setAttribute('href', url.toString());
        }
        function updateQuantityBadge(processNameId) {
            var checkedBoxes = modal.querySelectorAll('.process-checkbox[data-process-name-id="' + processNameId + '"]:checked:not([disabled])');
            var badge = modal.querySelector('.process-qty-badge[data-process-name-id="' + processNameId + '"]');
            if (badge && checkedBoxes.length > 0) {
                var totalQty = 0;
                checkedBoxes.forEach(function(cb) { totalQty += parseInt(cb.getAttribute('data-qty'), 10) || 0; });
                badge.textContent = totalQty + ' pcs';
            }
        }
        vendorSelects.forEach(function(s) {
            s.addEventListener('change', function() { updateLinkUrl(s.getAttribute('data-process-name-id')); });
        });
        processCheckboxes.forEach(function(c) {
            c.addEventListener('change', function() {
                updateLinkUrl(c.getAttribute('data-process-name-id'));
                updateQuantityBadge(c.getAttribute('data-process-name-id'));
            });
        });
        groupFormButtons.forEach(function(b) {
            b.addEventListener('click', function() { updateLinkUrl(b.getAttribute('data-process-name-id')); });
        });
        groupFormButtons.forEach(function(b) {
            var pid = b.getAttribute('data-process-name-id');
            if (pid) {
                updateLinkUrl(pid);
                updateQuantityBadge(pid);
            }
        });
    }

    function initTravelerGroupHandlers(container) {
        var target = container || body;
        if (!target) return;
        var wrapper = target.querySelector('.processes-modal-body');
        if (!wrapper) return;
        var tdrId = wrapper.dataset.tdrId;
        var groupUrl = wrapper.dataset.travelerGroupUrl;
        var ungroupUrl = wrapper.dataset.travelerUngroupUrl;
        var createBtn = target.querySelector('#btnCreateTraveler');
        var ungroupBtn = target.querySelector('#btnUngroupTraveler');
        function selectedTravelerRows() {
            return Array.from(target.querySelectorAll('.traveler-row-checkbox:checked'));
        }
        function selectedTravelerProcessIds(rows) {
            var ids = [];
            var seen = new Set();
            (rows || selectedTravelerRows()).forEach(function(box) {
                var id = box.value || box.getAttribute('value');
                if (id && !seen.has(id)) {
                    seen.add(id);
                    ids.push(parseInt(id, 10));
                }
            });
            return ids;
        }
        function syncTravelerButtonLabel() {
            if (!createBtn) return;
            var selectedRows = selectedTravelerRows();
            var groupedRows = selectedRows.filter(function(box) {
                return box.getAttribute('data-in-traveler') === '1';
            });
            var ungroupedRows = selectedRows.filter(function(box) {
                return box.getAttribute('data-in-traveler') !== '1';
            });

            createBtn.textContent = groupedRows.length > 0 && ungroupedRows.length === 0
                ? '{{ __("Ungroup") }}'
                : '{{ __("Traveler") }}';
        }
        function syncTravelerGroupSelection(changedBox) {
            if (!changedBox || changedBox.getAttribute('data-in-traveler') !== '1') return;
            var group = changedBox.getAttribute('data-traveler-group') || '';
            if (!group || group === '0') return;

            target.querySelectorAll('.traveler-row-checkbox[data-in-traveler="1"][data-traveler-group="' + group + '"]').forEach(function(box) {
                box.checked = changedBox.checked;
            });
        }
        target.querySelectorAll('.traveler-row-checkbox').forEach(function(box) {
            box.addEventListener('change', function() {
                syncTravelerGroupSelection(box);
                syncTravelerButtonLabel();
            });
        });
        syncTravelerButtonLabel();
        if (createBtn && groupUrl) {
            createBtn.addEventListener('click', function() {
                var selectedRows = selectedTravelerRows();
                var groupedRows = selectedRows.filter(function(box) {
                    return box.getAttribute('data-in-traveler') === '1';
                });
                var ungroupedRows = selectedRows.filter(function(box) {
                    return box.getAttribute('data-in-traveler') !== '1';
                });
                if (groupedRows.length > 0 && ungroupedRows.length === 0 && ungroupUrl) {
                    fetch(ungroupUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({
                            process_ids: selectedTravelerProcessIds(groupedRows),
                        }),
                    })
                        .then(function(r) { return r.json().then(function(data) { return { ok: r.ok, data: data }; }); })
                        .then(function(res) {
                            if (res.ok && res.data.success) {
                                loadProcessesAndBind(tdrId, target);
                                if (allPartsBody && allPartsBody.dataset.loaded) loadAllPartsProcesses();
                            } else {
                                var ungroupMsg = (res.data && res.data.message) ? res.data.message : '{{ __("Request failed.") }}';
                                window.tdrShowNotify(ungroupMsg, 'warning');
                            }
                        })
                        .catch(function() {
                            window.tdrShowNotify('{{ __("Request failed.") }}', 'error');
                        });
                    return;
                }

                if (groupedRows.length > 0) {
                    window.tdrShowNotify('{{ __("Select only ungrouped rows to create a Traveler, or only grouped rows to ungroup.") }}', 'warning');
                    return;
                }

                var ids = selectedTravelerProcessIds(ungroupedRows);
                if (ids.length < 1) {
                    window.tdrShowNotify('{{ __("Select rows in the Traveler column.") }}', 'warning');
                    return;
                }
                function submitTravelerGroup(clearConflictingValues) {
                    return fetch(groupUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({
                            process_ids: ids,
                            clear_conflicting_values: clearConflictingValues ? 1 : 0,
                        }),
                    });
                }

                submitTravelerGroup(false)
                    .then(function(r) { return r.json().then(function(data) { return { ok: r.ok, data: data }; }); })
                    .then(function(res) {
                        if (res.ok && res.data.success) {
                            loadProcessesAndBind(tdrId, target);
                            if (allPartsBody && allPartsBody.dataset.loaded) loadAllPartsProcesses();
                        } else if (res.data && res.data.requires_confirmation) {
                            return window.tdrShowConfirm(
                                res.data.message || '{{ __("Selected values will be cleared before grouping. Continue?") }}',
                                '{{ __("Create Traveler") }}',
                                '{{ __("Create") }}'
                            ).then(function(confirmed) {
                                if (!confirmed) return;
                                return submitTravelerGroup(true)
                                    .then(function(r) { return r.json().then(function(data) { return { ok: r.ok, data: data }; }); })
                                    .then(function(confirmRes) {
                                        if (confirmRes.ok && confirmRes.data.success) {
                                            loadProcessesAndBind(tdrId, target);
                                            if (allPartsBody && allPartsBody.dataset.loaded) loadAllPartsProcesses();
                                        } else {
                                            var confirmMsg = (confirmRes.data && confirmRes.data.message) ? confirmRes.data.message : '{{ __("Request failed.") }}';
                                            window.tdrShowNotify(confirmMsg, 'warning');
                                        }
                                    });
                            });
                        } else {
                            var msg = (res.data && res.data.message) ? res.data.message : '{{ __("Request failed.") }}';
                            window.tdrShowNotify(msg, 'warning');
                        }
                    })
                    .catch(function() {
                        window.tdrShowNotify('{{ __("Request failed.") }}', 'error');
                    });
            });
        }
        if (ungroupBtn && ungroupUrl) {
            ungroupBtn.addEventListener('click', function() {
                fetch(ungroupUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                })
                    .then(function(r) { return r.json().then(function(data) { return { ok: r.ok, data: data }; }); })
                    .then(function(res) {
                        if (res.ok && res.data.success) {
                            loadProcessesAndBind(tdrId, target);
                            if (allPartsBody && allPartsBody.dataset.loaded) loadAllPartsProcesses();
                        } else {
                            var msg2 = (res.data && res.data.message) ? res.data.message : '{{ __("Request failed.") }}';
                            window.tdrShowNotify(msg2, 'warning');
                        }
                    })
                    .catch(function() {
                        window.tdrShowNotify('{{ __("Request failed.") }}', 'error');
                    });
            });
        }
        target.querySelectorAll('.travel-form-link').forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                var u = new URL(link.getAttribute('href'), window.location.origin);
                window.open(u.toString(), '_blank');
            });
        });
    }

    function loadProcessesAndBind(tdrId, container) {
        var target = container || body;
        var isTabTarget = target === body;
        if (!target) return;
        activeProcessesContainer = target;
        target.innerHTML = '<div class="text-center py-5 text-muted">{{ __("Loading...") }}</div>';
        if (isTabTarget) {
            if (woNum) woNum.textContent = '-';
            if (itemName) itemName.textContent = '-';
            if (itemIpl) itemIpl.textContent = '-';
            if (itemPn) itemPn.textContent = '-';
            if (itemSn) itemSn.textContent = '-';
            if (addProcessBtn) { addProcessBtn.dataset.tdrId = tdrId; addProcessBtn.disabled = true; }
            if (compProcessesGroupFormsBtn) compProcessesGroupFormsBtn.classList.add('d-none');
        }
        var url = processesBodyUrl.replace('__ID__', tdrId);
        url += (url.indexOf('?') >= 0 ? '&' : '?') + '_=' + Date.now();
        return fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html',
                'Cache-Control': 'no-cache',
                'Pragma': 'no-cache',
            },
            credentials: 'same-origin',
            cache: 'no-store',
        })
            .then(function(r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.text();
            })
            .then(function(html) {
                if (html.indexOf('processes-modal-body') === -1 || /<\s*body[\s>]/i.test(html)) {
                    throw new Error('Unexpected full page response for Part Processes partial');
                }
                target.innerHTML = html;
                try {
                    var wrapper = target.querySelector('.processes-modal-body');
                    if (isTabTarget && wrapper) {
                        if (woNum) woNum.textContent = wrapper.dataset.woNumber || '-';
                        if (itemName) itemName.textContent = wrapper.dataset.componentName || 'N/A';
                        if (itemIpl) itemIpl.textContent = wrapper.dataset.componentIpl || 'N/A';
                        if (itemPn) itemPn.textContent = wrapper.dataset.componentPn || 'N/A';
                        if (itemSn) itemSn.textContent = wrapper.dataset.serialNumber || 'N/A';
                    }
                    var processesWrapper = target.querySelector('.processes-modal-body');
                    if (typeof Sortable !== 'undefined' && typeof SortableHandler !== 'undefined') {
                        if (!processesWrapper || processesWrapper.dataset.travelerBlock !== '1') {
                            if (isTabTarget) {
                                SortableHandler.init(updateOrderUrl);
                            } else {
                                var modalSortableBody = target.querySelector('#sortable-tbody');
                                if (modalSortableBody) {
                                    Sortable.create(modalSortableBody, {
                                        animation: 150,
                                        ghostClass: 'dragging',
                                        dragClass: 'dragging',
                                        filter: '.disabled',
                                        onEnd: function(evt) {
                                            var newOrder = Array.from(evt.to.children)
                                                .filter(function(row) { return !row.querySelector('.disabled') || !row.querySelector('[aria-disabled="true"]'); })
                                                .map(function(row, index) {
                                                    return { id: row.getAttribute('data-id'), sort_order: index + 1 };
                                                });
                                            SortableHandler.updateProcessOrder(newOrder, updateOrderUrl);
                                        }
                                    });
                                }
                            }
                        }
                    }
                    bindProcessHandlers(wrapper, target);
                    if (typeof FormLinkHandler !== 'undefined') FormLinkHandler.init(target);
                    initTravelerGroupHandlers(target);
                    if (isTabTarget && compProcessesGroupFormsBtn) {
                        var processesWrapperForGroup = target.querySelector('.processes-modal-body');
                        var allowGroupForms = processesWrapperForGroup
                            && processesWrapperForGroup.getAttribute('data-group-process-forms') === '1';
                        var partGroupModal = target.querySelector('#partProcessesGroupFormsModal');
                        if (allowGroupForms && partGroupModal) {
                            compProcessesGroupFormsBtn.classList.remove('d-none');
                            initPartProcessesGroupForms(partGroupModal);
                        } else {
                            compProcessesGroupFormsBtn.classList.add('d-none');
                        }
                    }
                    if (isTabTarget && addProcessBtn) {
                        addProcessBtn.disabled = false;
                        addProcessBtn.onclick = function() {
                            var inlineAddBtn = target.querySelector('[data-inline-process-add]');
                            if (!inlineAddBtn) {
                                (typeof showNotification === 'function' ? (m) => showNotification(m, 'warning') : (window.NotificationHandler?.warning || window.notifyWarn))('{{ __("Please select a component first.") }}');
                                return;
                            }
                            inlineAddBtn.click();
                        };
                    }
                } catch (initError) {
                    console.error('Part Processes init failed:', initError);
                    if (isTabTarget && addProcessBtn) addProcessBtn.disabled = false;
                }
            })
            .catch(function(error) {
                console.error('Part Processes fetch failed:', error);
                target.innerHTML = '<div class="alert alert-danger">{{ __("Failed to load processes.") }}<div class="small mt-1">' + (error && error.message ? error.message : '') + '</div></div>';
                if (isTabTarget && addProcessBtn) addProcessBtn.disabled = false;
                if (isTabTarget && compProcessesGroupFormsBtn) compProcessesGroupFormsBtn.classList.add('d-none');
            });
    }

    function initInlineProcessCreate(wrapper, container) {
        if (!wrapper || !container || wrapper.dataset.inlineProcessInitialized === '1') return;
        wrapper.dataset.inlineProcessInitialized = '1';

        var addRow = container.querySelector('.tdr-process-inline-add-row');
        var createRow = container.querySelector('[data-inline-process-row]');
        var addBtn = container.querySelector('[data-inline-process-add]');
        var nameSelect = container.querySelector('[data-inline-process-name]');
        var processOptions = container.querySelector('[data-inline-process-options]');
        var processText = container.querySelector('[data-inline-process-text]');
        var descriptionInput = container.querySelector('[data-inline-process-description]');
        var saveBtn = container.querySelector('[data-inline-process-save]');
        var selectedProcessId = '';
        var closeInlineProcessTimer = null;

        if (!addRow || !createRow || !addBtn || !nameSelect || !saveBtn) return;

        function inlineProcessScroller() {
            return createRow.closest('.table-wrapper');
        }

        function reserveInlineProcessScrollSpace() {
            clearInlineProcessScrollSpace();
        }

        function clearInlineProcessScrollSpace() {
            var scroller = inlineProcessScroller();
            if (!scroller) return;

            scroller.style.paddingBottom = '';
            scroller.style.scrollPaddingBottom = '';
        }

        function scrollInlineProcessRowIntoView() {
            var scroller = inlineProcessScroller();
            if (!scroller) return;

            window.requestAnimationFrame(function() {
                reserveInlineProcessScrollSpace();

                var rowRect = createRow.getBoundingClientRect();
                var scrollerRect = scroller.getBoundingClientRect();
                var footer = document.querySelector('footer.footer');
                var footerTop = footer ? footer.getBoundingClientRect().top : window.innerHeight;
                var visibleBottom = Math.min(scrollerRect.bottom, footerTop - 8, window.innerHeight - 8);
                var bottomOverflow = rowRect.bottom - visibleBottom;
                var topOverflow = scrollerRect.top - rowRect.top;

                if (bottomOverflow > 0) {
                    scroller.scrollTop += bottomOverflow + 24;
                } else if (topOverflow > 0) {
                    scroller.scrollTop -= topOverflow + 12;
                }
            });
        }

        function resetInlineProcessRow() {
            selectedProcessId = '';
            nameSelect.value = '';
            if (processOptions) {
                processOptions.innerHTML = '';
                processOptions.classList.add('d-none');
            }
            if (processText) {
                processText.textContent = '{{ __("Select process name") }}';
                processText.classList.add('text-muted');
                processText.classList.remove('d-none');
            }
            if (descriptionInput) descriptionInput.value = '';
            saveBtn.disabled = false;
            saveBtn.textContent = '{{ __("Save") }}';
        }

        function closeInlineProcessRow() {
            resetInlineProcessRow();
            createRow.classList.add('d-none');
            addRow.classList.remove('d-none');
            clearInlineProcessScrollSpace();
        }

        function scheduleInlineProcessClose() {
            window.clearTimeout(closeInlineProcessTimer);
            closeInlineProcessTimer = window.setTimeout(function() {
                if (createRow.classList.contains('d-none')) return;
                if (createRow.matches(':hover')) return;
                if (createRow.contains(document.activeElement)) return;
                closeInlineProcessRow();
            }, 220);
        }

        createRow.addEventListener('mouseenter', function() {
            window.clearTimeout(closeInlineProcessTimer);
        });

        createRow.addEventListener('mouseleave', scheduleInlineProcessClose);
        createRow.addEventListener('focusout', function() {
            window.setTimeout(scheduleInlineProcessClose, 0);
        });

        addBtn.addEventListener('click', function() {
            resetInlineProcessRow();
            createRow.classList.remove('d-none');
            addRow.classList.add('d-none');
            reserveInlineProcessScrollSpace();
            scrollInlineProcessRowIntoView();
            nameSelect.focus();
        });

        nameSelect.addEventListener('change', function() {
            selectedProcessId = '';
            var processNameId = nameSelect.value;
            var manualId = wrapper.dataset.manualId || '';
            if (!processNameId) {
                if (processOptions) {
                    processOptions.innerHTML = '';
                    processOptions.classList.add('d-none');
                }
                if (processText) {
                    processText.textContent = '{{ __("Select process name") }}';
                    processText.classList.add('text-muted');
                    processText.classList.remove('d-none');
                }
                saveBtn.disabled = false;
                return;
            }

            if (processText) {
                processText.textContent = '{{ __("Loading...") }}';
                processText.classList.add('text-muted');
                processText.classList.remove('d-none');
            }
            if (processOptions) {
                processOptions.innerHTML = '';
                processOptions.classList.add('d-none');
            }
            saveBtn.disabled = true;

            var url = new URL(processOptionsUrl, window.location.origin);
            url.searchParams.set('processNameId', processNameId);
            url.searchParams.set('manualId', manualId);

            fetch(url.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                credentials: 'same-origin',
            })
                .then(function(response) {
                    return response.json().catch(function() { return {}; }).then(function(data) {
                        return { ok: response.ok, data: data };
                    });
                })
                .then(function(result) {
                    if (!result.ok) {
                        throw new Error(result.data.message || result.data.error || '{{ __("Failed to load processes.") }}');
                    }
                    var processes = result.data.existingProcesses || [];
                    if (!processes.length) {
                        selectedProcessId = '';
                        if (processText) {
                            processText.textContent = '{{ __("No process found") }}';
                            processText.classList.add('text-muted');
                            processText.classList.remove('d-none');
                        }
                        saveBtn.disabled = false;
                        return;
                    }

                    if (processOptions) {
                        processOptions.innerHTML = '';
                        processes.forEach(function(process) {
                            var label = document.createElement('label');
                            label.className = 'tdr-process-inline-option';

                            var input = document.createElement('input');
                            input.type = 'radio';
                            input.name = 'inline_process_' + wrapper.dataset.tdrId;
                            input.value = String(process.id);

                            var text = document.createElement('span');
                            text.textContent = process.process || ('#' + process.id);

                            if (process.process_comment) {
                                var comment = document.createElement('span');
                                comment.className = 'tdr-process-inline-option-comment';
                                comment.textContent = ' (' + process.process_comment + ')';
                                text.appendChild(comment);
                            }

                            label.appendChild(input);
                            label.appendChild(text);
                            processOptions.appendChild(label);

                            input.addEventListener('change', function() {
                                selectedProcessId = input.checked ? input.value : '';
                            });
                        });
                        if (processes.length === 1) {
                            var onlyInput = processOptions.querySelector('input[type="radio"]');
                            if (onlyInput) {
                                onlyInput.checked = true;
                                selectedProcessId = onlyInput.value;
                            }
                        }
                        processOptions.classList.remove('d-none');
                        scrollInlineProcessRowIntoView();
                    } else {
                        selectedProcessId = processes.length === 1 ? String(processes[0].id) : '';
                    }
                    if (processText) {
                        processText.classList.add('d-none');
                    }
                    saveBtn.disabled = false;
                })
                .catch(function(error) {
                    selectedProcessId = '';
                    if (processText) {
                        processText.textContent = error.message || '{{ __("Failed to load processes.") }}';
                        processText.classList.add('text-muted');
                        processText.classList.remove('d-none');
                    }
                    if (processOptions) {
                        processOptions.innerHTML = '';
                        processOptions.classList.add('d-none');
                    }
                    saveBtn.disabled = false;
                });
        });

        saveBtn.addEventListener('click', function() {
            var processNameId = nameSelect.value;
            if (!processNameId || !selectedProcessId) {
                window.tdrShowNotify('{{ __("Select process and process name.") }}', 'warning');
                return;
            }

            saveBtn.disabled = true;
            saveBtn.textContent = '{{ __("Saving...") }}';

            fetch(storeProcessUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    tdrs_id: wrapper.dataset.tdrId,
                    processes: [{
                        process_names_id: processNameId,
                        processes: [selectedProcessId],
                        description: descriptionInput ? descriptionInput.value : '',
                        in_traveler: 0
                    }]
                })
            })
                .then(function(response) {
                    return response.json().catch(function() { return {}; }).then(function(data) {
                        return { ok: response.ok, data: data };
                    });
                })
                .then(function(result) {
                    if (!result.ok) {
                        throw new Error(result.data.error || result.data.message || '{{ __("Save failed.") }}');
                    }
                    window.tdrShowNotify(result.data.message || '{{ __("Process added successfully.") }}', 'success', 2000);
                    loadProcessesAndBind(wrapper.dataset.tdrId, container);
                    if (allPartsBody && allPartsBody.dataset.loaded) loadAllPartsProcesses();
                })
                .catch(function(error) {
                    window.tdrShowNotify(error.message || '{{ __("Save failed.") }}', 'error');
                    saveBtn.disabled = false;
                    saveBtn.textContent = '{{ __("Save") }}';
                });
        });
    }

    function bindProcessHandlers(wrapper, container) {
        var target = container || body;
        if (!target) return;
        initInlineProcessCreate(wrapper, target);
        target.querySelectorAll('.load-edit-process').forEach(function(b) {
            b.addEventListener('click', function() {
                var tdrProcessId = this.dataset.tdrProcessId;
                var editModal = document.getElementById('editTdrProcessModal');
                var iframe = document.getElementById('editTdrProcessIframe');
                if (!editModal || !iframe) return;
                iframe.src = editFormUrl.replace('__ID__', tdrProcessId) + '?modal=1';
                var inst = bootstrap.Modal.getOrCreateInstance(editModal);
                inst.show();
                editModal.addEventListener('shown.bs.modal', function setEditModalZ() {
                    editModal.style.zIndex = '1080';
                    var backdrops = document.querySelectorAll('.modal-backdrop');
                    if (backdrops.length) backdrops[backdrops.length - 1].style.zIndex = '1075';
                }, { once: true });
            });
        });
        target.querySelectorAll('.ajax-delete-process').forEach(function(b) {
            b.addEventListener('click', function() {
                var button = this;
                window.tdrShowConfirm('{{ __("Are you sure you want to delete this process?") }}').then(function(confirmed) {
                    if (!confirmed) return;
                    var tdrProcessId = button.dataset.tdrProcessId;
                    var tdrId = button.dataset.tdrId || (wrapper && wrapper.dataset.tdrId);
                    var process = button.dataset.process || '';
                    var formData = new FormData();
                    formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
                    formData.append('_method', 'DELETE');
                    formData.append('tdrId', tdrId);
                    if (process) formData.append('process', process);
                    fetch('{{ route("tdr-processes.destroy", ["tdr_process" => "__ID__"]) }}'.replace('__ID__', tdrProcessId), { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                        .then(function(r) { return r.json().catch(function() { return {}; }).then(function(data) { return { ok: r.ok, data: data }; }); })
                        .then(function(res) {
                            if (res.ok && res.data.success !== false) {
                                loadProcessesAndBind(tdrId, target);
                                if (allPartsBody && allPartsBody.dataset.loaded) loadAllPartsProcesses();
                            } else {
                                var dm = (res.data && res.data.message) ? res.data.message : '{{ __("Delete failed.") }}';
                                window.tdrShowNotify(dm, 'warning');
                            }
                        })
                        .catch(function() {
                            window.tdrShowNotify('{{ __("Delete failed.") }}', 'error');
                        });
                });
            });
        });
    }

    var tdrProcessTable = document.getElementById('tdr_process_Table');
    if (tdrProcessTable) {
        tdrProcessTable.addEventListener('click', function(e) {
            var btn = e.target.closest('.open-part-processes-tab');
            if (!btn || !tdrProcessTable.contains(btn)) return;
            e.preventDefault();
            var tdrId = btn.dataset.tdrId;
            if (!tdrId) return;
            if (tabLi) tabLi.classList.remove('d-none');
            syncPartProcessesShortcutActions();
            loadProcessesAndBind(tdrId);
            if (tabBtn) {
                var tab = new bootstrap.Tab(tabBtn);
                tab.show();
            }
        });
    }
    if (allPartsBody) {
        allPartsBody.addEventListener('click', function(e) {
            var btn = e.target.closest('.open-part-processes-tab');
            if (!btn || !btn.dataset.tdrId) return;
            if (tabLi) tabLi.classList.remove('d-none');
            syncPartProcessesShortcutActions();
            var shouldOpenInlineAdd = btn.dataset.openInlineProcessAdd === '1';
            var loading = loadProcessesAndBind(btn.dataset.tdrId);
            if (tabBtn) { var tab = new bootstrap.Tab(tabBtn); tab.show(); }
            if (shouldOpenInlineAdd && loading && typeof loading.then === 'function') {
                loading.then(function() {
                    body?.querySelector('[data-inline-process-add]')?.click();
                });
            }
        });
    }
    var logCardTabActions = document.getElementById('logCardTabActions');
    var bushingTabActions = document.getElementById('bushingTabActions');
    function openAddProcessesModalByUrl(url) {
        var ifr = document.getElementById('addProcessesIframe');
        var modal = document.getElementById('addProcessesModal');
        if (ifr && modal && url) {
            var fullUrl = url + (url.indexOf('?') >= 0 ? '&' : '?') + 'modal=1';
            ifr.src = fullUrl;
            var inst = bootstrap.Modal.getOrCreateInstance(modal);
            inst.show();
            modal.addEventListener('shown.bs.modal', function setZ() { modal.style.zIndex = '1090'; var b = document.querySelectorAll('.modal-backdrop'); if (b.length) b[b.length-1].style.zIndex = '1085'; }, { once: true });
        }
    }
    function openAddPartModalByUrl(url) {
        var ifr = document.getElementById('addPartIframe');
        var modal = document.getElementById('addPartModal');
        if (ifr && modal && url) {
            var fullUrl = url + (url.indexOf('?') >= 0 ? '&' : '?') + 'modal=1';
            ifr.src = fullUrl;
            var inst = bootstrap.Modal.getOrCreateInstance(modal);
            inst.show();
            modal.addEventListener('shown.bs.modal', function setZ() { modal.style.zIndex = '1090'; var b = document.querySelectorAll('.modal-backdrop'); if (b.length) b[b.length-1].style.zIndex = '1085'; }, { once: true });
        }
    }
    if (bushingTabBody) {
        function bushingToastWarn(m) {
            if (typeof window.notifyWarn === 'function') {
                window.notifyWarn(m);
            } else {
                window.tdrShowNotify(m, 'warning');
            }
        }
        function bushingToastErr(m) {
            if (typeof window.notifyError === 'function') {
                window.notifyError(m);
            } else {
                window.tdrShowNotify(m, 'error');
            }
        }
        bushingTabBody.addEventListener('click', function(e) {
            var addProcessesBtn = e.target.closest('.open-add-processes-modal');
            if (addProcessesBtn && addProcessesBtn.getAttribute('data-add-processes-url')) {
                e.preventDefault();
                openAddProcessesModalByUrl(addProcessesBtn.getAttribute('data-add-processes-url'));
                return;
            }
            var addPartBtn = e.target.closest('.open-add-part-modal');
            if (addPartBtn && addPartBtn.getAttribute('data-add-part-url')) {
                e.preventDefault();
                openAddPartModalByUrl(addPartBtn.getAttribute('data-add-part-url'));
                return;
            }
            var formBtn = e.target.closest('.form-btn');
            if (formBtn && formBtn.getAttribute('href')) {
                e.preventDefault();
                var vendorSelectId = formBtn.getAttribute('data-vendor-select');
                var vendorSelect = vendorSelectId ? document.getElementById(vendorSelectId) : null;
                var vendorId = vendorSelect ? vendorSelect.value : '';
                var baseUrl = formBtn.getAttribute('href');
                var processKey = formBtn.getAttribute('data-process-key');
                var queryParts = [];
                if (processKey) {
                    var seen = {};
                    document.querySelectorAll(
                        '.bushing-batch-group-checkbox[data-process-key="' + processKey + '"]:checked, .bushing-batch-ungroup-checkbox[data-process-key="' + processKey + '"]:checked'
                    ).forEach(function(cb) {
                        var cid = cb.getAttribute('data-component-id');
                        if (cid && !seen[cid]) {
                            seen[cid] = true;
                            queryParts.push('bushing_component_ids[]=' + encodeURIComponent(cid));
                        }
                    });
                    if (queryParts.length === 0) {
                        bushingToastWarn({!! json_encode(__('Select at least one bushing for this process using Group checkboxes in the table.')) !!});
                        return;
                    }
                }
                if (vendorId) {
                    queryParts.push('vendor_id=' + encodeURIComponent(vendorId));
                }
                var finalUrl = baseUrl + (queryParts.length ? (baseUrl.indexOf('?') === -1 ? '?' : '&') + queryParts.join('&') : '');
                window.open(finalUrl, '_blank');
                return;
            }

            var groupLabelBtn = e.target.closest('.js-bushing-batch-label');
            if (groupLabelBtn) {
                e.preventDefault();
                var grpProcessKey = groupLabelBtn.getAttribute('data-process-key') || '';
                var grpBatchId = groupLabelBtn.getAttribute('data-batch-id');
                if (grpBatchId === null || typeof grpBatchId === 'undefined') {
                    grpBatchId = '';
                }
                var grpWoPid = groupLabelBtn.getAttribute('data-wo-process-id') || '';
                if (!grpProcessKey) return;
                var groupBoxes;
                if (grpBatchId !== '' && grpBatchId !== '0') {
                    groupBoxes = Array.from(document.querySelectorAll(
                        '.bushing-batch-ungroup-checkbox[data-process-key="' + grpProcessKey + '"][data-batch-id="' + grpBatchId + '"]'
                    ));
                } else if (grpWoPid) {
                    groupBoxes = Array.from(document.querySelectorAll(
                        '.bushing-batch-ungroup-checkbox[data-process-key="' + grpProcessKey + '"][data-wo-process-id="' + grpWoPid + '"]'
                    ));
                } else {
                    return;
                }
                if (!groupBoxes.length) return;
                var allChecked = groupBoxes.every(function (cb) { return !!cb.checked; });
                groupBoxes.forEach(function (cb) { cb.checked = !allChecked; });
                return;
            }

            var createBatchBtn = e.target.closest('.js-bushing-create-batch');
            var ungroupBatchBtn = e.target.closest('.js-bushing-ungroup-batch');
            if (createBatchBtn || ungroupBatchBtn) {
                e.preventDefault();
                var actionBtn = createBatchBtn || ungroupBatchBtn;
                var actionUrl = actionBtn.getAttribute('data-url');
                if (!actionUrl) return;

                var scopeKey = actionBtn.getAttribute('data-process-key') || '';

                var selector = createBatchBtn
                    ? '.bushing-batch-group-checkbox:checked'
                    : '.bushing-batch-ungroup-checkbox:checked';
                var checkboxes = Array.from(document.querySelectorAll(selector));
                if (scopeKey) {
                    checkboxes = checkboxes.filter(function (cb) {
                        return (cb.getAttribute('data-process-key') || '') === scopeKey;
                    });
                }
                var selected = checkboxes
                    .map(function (cb) {
                        return {
                            processKey: cb.getAttribute('data-process-key') || '',
                            id: cb.getAttribute('data-wo-process-id') || ''
                        };
                    })
                    .filter(function (row) { return !!row.id; });

                if (selected.length === 0) {
                    bushingToastWarn(createBatchBtn
                        ? {!! json_encode(__('Select rows using the small “batch” checkbox (not grouped yet).')) !!}
                        : {!! json_encode(__('Select rows using the small checkbox next to “Grp” to ungroup.')) !!});
                    return;
                }

                var processKeys = Array.from(new Set(selected.map(function (r) { return r.processKey; })));
                if (processKeys.length !== 1) {
                    bushingToastWarn({!! json_encode(__('Please select rows from one process column only.')) !!});
                    return;
                }

                var tokenEl = document.querySelector('meta[name="csrf-token"]');
                var csrf = tokenEl ? tokenEl.getAttribute('content') : '';
                fetch(actionUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrf
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        wo_bushing_process_ids: selected.map(function (r) { return parseInt(r.id, 10); })
                    })
                })
                .then(function (r) {
                    return r.text().then(function (text) {
                        try { return { status: r.status, json: JSON.parse(text) }; } catch (e) { return { status: r.status, json: null }; }
                    });
                })
                .then(function (res) {
                    if (res.status >= 200 && res.status < 300) {
                        if (typeof loadBushingPartial === 'function') loadBushingPartial();
                        return;
                    }
                    var msg = (res.json && (res.json.message || res.json.error)) ? (res.json.message || res.json.error) : ('HTTP ' + res.status);
                    if (res.json && res.json.errors) {
                        try { msg += '\n' + JSON.stringify(res.json.errors); } catch (e2) {}
                    }
                    if (res.status >= 500) {
                        bushingToastErr(msg);
                    } else {
                        bushingToastWarn(msg);
                    }
                })
                .catch(function (err) {
                    bushingToastErr('Batch operation failed' + (err && err.message ? ': ' + err.message : '.'));
                });
            }
        });
        bushingTabBody.addEventListener('submit', function(e) {
            var form = e.target;
            if (form.id !== 'bushings-form' || form.dataset.embed !== '1') return;
            e.preventDefault();
            function hideGlobalSpinner() {
                if (typeof window.hideLoadingSpinner === 'function') window.hideLoadingSpinner();
            }
            var selected = form.querySelectorAll('.component-checkbox:checked');
            if (selected.length === 0) {
                window.tdrShowNotify('{{ __("Please select at least one component before submitting.") }}', 'warning');
                hideGlobalSpinner();
                return;
            }
            var groups = new Set();
            selected.forEach(function(c){ groups.add(c.getAttribute('data-group')); });
            var hasErr = false;
            groups.forEach(function(gn) {
                var q = form.querySelector('input[name="group_bushings[' + gn + '][qty]"]');
                if (!q || !q.value || parseInt(q.value, 10) <= 0) { if (q) q.style.borderColor = 'red'; hasErr = true; }
                else if (q) q.style.borderColor = '';
            });
            if (hasErr) {
                window.tdrShowNotify('{{ __("Please enter quantity for all groups with selected components.") }}', 'warning');
                hideGlobalSpinner();
                return;
            }
            var submitBtn = document.querySelector('button[form="bushings-form"]');
            if (submitBtn) { submitBtn.disabled = true; }
            var fd = new FormData(form);
            var controller = new AbortController();
            var timeoutId = setTimeout(function() { controller.abort(); }, 30000);
            fetch(form.action, {
                method: 'POST',
                body: fd,
                signal: controller.signal,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                credentials: 'same-origin'
            })
            .then(function(r) {
                return r.text().then(function(text) {
                    try { return { json: JSON.parse(text), status: r.status }; } catch (e) { return { json: null, status: r.status }; }
                });
            })
            .then(function(result) {
                if (result.json) {
                    if (result.json.success) {
                        if (typeof loadBushingPartial === 'function') loadBushingPartial();
                    } else {
                        window.tdrShowNotify(result.json.message || (result.json.errors ? JSON.stringify(result.json.errors) : '') || '{{ __("Error creating bushings data.") }}', 'error');
                    }
                } else {
                    window.tdrShowNotify('{{ __("Failed to submit.") }} (HTTP ' + result.status + ')', 'error');
                }
            })
            .catch(function(err) {
                window.tdrShowNotify(err.name === 'AbortError' ? '{{ __("Request timed out. Please try again.") }}' : ('{{ __("Failed to submit.") }}' + (err.message ? ': ' + err.message : '')), 'error');
            })
            .finally(function() {
                clearTimeout(timeoutId);
                hideGlobalSpinner();
                if (submitBtn) { submitBtn.disabled = false; }
            });
        });
    }
    if (bushingTabActions) {
        bushingTabActions.addEventListener('click', function(e) {
            var clearBtn = e.target.closest('.bushing-clear-btn');
            if (clearBtn) {
                e.preventDefault();
                if (typeof window.bushingClearForm === 'function') window.bushingClearForm();
                return;
            }
            var editBtn = e.target.closest('.open-edit-bushing-modal');
            if (editBtn && editBtn.dataset.woBushingId) {
                e.preventDefault();
                var iframe = document.getElementById('editBushingIframe');
                var modal = document.getElementById('editBushingModal');
                if (iframe && modal) {
                    iframe.src = editBushingUrl.replace('__ID__', editBtn.dataset.woBushingId) + '?modal=1';
                    var inst = bootstrap.Modal.getOrCreateInstance(modal);
                    inst.show();
                    modal.addEventListener('shown.bs.modal', function setZ() { modal.style.zIndex = '1080'; var b = document.querySelectorAll('.modal-backdrop'); if (b.length) b[b.length-1].style.zIndex = '1075'; }, { once: true });
                }
            }
        });
    }
    var logCardEnterBtn = document.getElementById('logCardEnterDataBtn');
    var logCardSaveBtn = document.getElementById('logCardSaveBtn');
    var logCardCancelBtn = document.getElementById('logCardCancelBtn');
    var logCardInlineSaveTimer = null;
    var logCardInlineSaveInFlight = false;
    var logCardInlineQueuedUpdates = [];

    function logCardTabPersistPayload(payload, options) {
        options = options || {};
        if (!payload || payload.length < 1) {
            var warning = '{{ __("Отметьте хотя бы один компонент для Log Card.") }}';
            if (typeof window.tdrShowNotify === 'function') window.tdrShowNotify(warning, 'warning');
            else if (window.showNotification) window.showNotification(warning, 'warning');
            return Promise.resolve(false);
        }

        var metaEl = document.getElementById('log-card-tab-meta');
        var meta;
        try { meta = metaEl ? JSON.parse(metaEl.textContent) : {}; } catch (e2) { meta = {}; }

        var fd = new FormData();
        fd.append('_token', logCardTabCsrfToken());
        fd.append('workorder_id', String(meta.workorder_id || ''));
        fd.append('component_data', JSON.stringify(payload));
        if (meta.log_card_id) fd.append('_method', 'PUT');

        var url = meta.log_card_id
            ? logCardUpdateUrlTemplate.replace('__LC__', String(meta.log_card_id))
            : logCardStoreUrl;

        return fetch(url, {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            credentials: 'same-origin'
        })
            .then(function(r) {
                return r.json().then(function(data) {
                    return { ok: r.ok, data: data };
                }).catch(function() {
                    return { ok: r.ok, data: {} };
                });
            })
            .then(function(res) {
                if (res.ok && res.data && res.data.success) {
                    if (options.reload) loadLogCardPartial();
                    if (options.notify && typeof window.tdrShowNotify === 'function') {
                        window.tdrShowNotify(res.data.message || '{{ __("Saved.") }}', 'success');
                    }
                    return true;
                }
                var errMsg = (res.data && res.data.message)
                    ? res.data.message
                    : ((res.data && res.data.errors) ? Object.values(res.data.errors).flat().join(' ') : '{{ __("Could not save.") }}');
                if (typeof window.tdrShowNotify === 'function') window.tdrShowNotify(errMsg, 'error');
                else if (window.notifyError) window.notifyError(errMsg);
                return false;
            })
            .catch(function() {
                if (typeof window.tdrShowNotify === 'function') window.tdrShowNotify('{{ __("Request failed.") }}', 'error');
                return false;
            });
    }

    function logCardTabPersistInlineField(rowIndex, fieldName, fieldValue) {
        var metaEl = document.getElementById('log-card-tab-meta');
        var meta;
        try { meta = metaEl ? JSON.parse(metaEl.textContent) : {}; } catch (e2) { meta = {}; }

        if (!meta.log_card_id) {
            return Promise.resolve(false);
        }

        var fd = new FormData();
        fd.append('_token', logCardTabCsrfToken());
        fd.append('_method', 'PATCH');
        fd.append('row', String(rowIndex));
        fd.append('field', String(fieldName || ''));
        fd.append('value', fieldValue == null ? '' : String(fieldValue));

        return fetch(logCardInlineFieldUpdateUrlTemplate.replace('__LC__', String(meta.log_card_id)), {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            credentials: 'same-origin'
        })
            .then(function(r) {
                return r.json().then(function(data) {
                    return { ok: r.ok, data: data };
                }).catch(function() {
                    return { ok: r.ok, data: {} };
                });
            })
            .then(function(res) {
                if (res.ok && res.data && res.data.success) {
                    return true;
                }

                var errMsg = (res.data && res.data.message)
                    ? res.data.message
                    : ((res.data && res.data.errors) ? Object.values(res.data.errors).flat().join(' ') : '{{ __("Could not save.") }}');
                if (typeof window.tdrShowNotify === 'function') window.tdrShowNotify(errMsg, 'error');
                else if (window.notifyError) window.notifyError(errMsg);
                return false;
            })
            .catch(function() {
                if (typeof window.tdrShowNotify === 'function') window.tdrShowNotify('{{ __("Request failed.") }}', 'error');
                return false;
            });
    }

    function logCardScheduleNextInlineSave() {
        if (logCardInlineSaveInFlight || !logCardInlineQueuedUpdates.length) {
            return;
        }

        var nextUpdate = logCardInlineQueuedUpdates.shift();
        logCardInlineSaveInFlight = true;

        logCardTabPersistInlineField(nextUpdate.rowIndex, nextUpdate.fieldName, nextUpdate.fieldValue).finally(function() {
            logCardInlineSaveInFlight = false;
            if (logCardInlineQueuedUpdates.length) {
                logCardScheduleNextInlineSave();
            }
        });
    }

    function logCardEnqueueInlineSave(rowIndex, fieldName, fieldValue) {
        var updateKey = String(rowIndex) + '::' + String(fieldName);
        var replaced = false;

        logCardInlineQueuedUpdates = logCardInlineQueuedUpdates.map(function(item) {
            if (String(item.rowIndex) + '::' + String(item.fieldName) === updateKey) {
                replaced = true;
                return {
                    rowIndex: rowIndex,
                    fieldName: fieldName,
                    fieldValue: fieldValue
                };
            }

            return item;
        });

        if (!replaced) {
            logCardInlineQueuedUpdates.push({
                rowIndex: rowIndex,
                fieldName: fieldName,
                fieldValue: fieldValue
            });
        }

        logCardScheduleNextInlineSave();
    }

    function logCardTabReset(logCardId) {
        var fd = new FormData();
        fd.append('_token', logCardTabCsrfToken());
        fd.append('_method', 'DELETE');

        return fetch(logCardDeleteUrlTemplate.replace('__LC__', String(logCardId)), {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            credentials: 'same-origin'
        })
            .then(function(r) {
                return r.json().then(function(data) {
                    return { ok: r.ok, data: data };
                }).catch(function() {
                    return { ok: r.ok, data: {} };
                });
            })
            .then(function(res) {
                if (res.ok && res.data && res.data.success) {
                    loadLogCardPartial();
                    if (typeof window.tdrShowNotify === 'function') window.tdrShowNotify(res.data.message || '{{ __("Reset.") }}', 'success');
                    return true;
                }
                var msg = (res.data && res.data.message) ? res.data.message : '{{ __("Could not reset Log Card.") }}';
                if (typeof window.tdrShowNotify === 'function') window.tdrShowNotify(msg, 'error');
                return false;
            })
            .catch(function() {
                if (typeof window.tdrShowNotify === 'function') window.tdrShowNotify('{{ __("Request failed.") }}', 'error');
                return false;
            });
    }

    if (logCardEnterBtn) {
        logCardEnterBtn.addEventListener('click', function() {
            if (logCardTabIsReadOnly()) {
                var readOnlyMessage = logCardTabReadOnlyMessage() || '{{ __("Log Card editing is locked. Please contact Quality Manager.") }}';
                if (typeof window.tdrShowNotify === 'function') window.tdrShowNotify(readOnlyMessage, 'warning');
                return;
            }

            var logCardId = logCardEnterBtn.getAttribute('data-log-card-id') || '';
            if (logCardId) {
                window.tdrShowConfirm(
                    '{{ __("Reset Log Card for this workorder? This will delete the saved row and return to component selection.") }}',
                    '{{ __("Reset Log Card") }}',
                    '{{ __("Reset") }}'
                ).then(function(confirmed) {
                    if (!confirmed) return;
                    logCardEnterBtn.disabled = true;
                    logCardEnterBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> {{ __("Resetting...") }}';
                    logCardTabReset(logCardId).finally(function() {
                        logCardEnterBtn.disabled = false;
                    });
                });
                return;
            }

            logCardEnterBtn.disabled = true;
            logCardEnterBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> {{ __("Creating...") }}';
            logCardTabPersistPayload(logCardTabBuildPayload(), { reload: true, notify: true }).finally(function() {
                logCardEnterBtn.disabled = false;
            });
        });
    }

    if (logCardTabBody) {
        logCardTabBody.addEventListener('change', function(e) {
            if (logCardTabIsReadOnly()) return;
            var componentRadio = e.target.closest && e.target.closest('.lc-comp-radio');
            if (!componentRadio) return;
            syncLogCardDraftAssyChoices(document.getElementById('log-card-partial-shell'));
        });

        function logCardPersistInlineSave(field) {
            clearTimeout(logCardInlineSaveTimer);
            var row = field && field.closest ? field.closest('.lc-saved-row') : null;
            if (!field || !row) return;

            var rowIndex = row.dataset.rowIndex;
            var fieldName = field.name || '';
            var fieldValue = field.value;

            logCardInlineSaveTimer = setTimeout(function() {
                logCardEnqueueInlineSave(rowIndex, fieldName, fieldValue);
            }, 350);
        }

        function logCardQueueInlineSave(e) {
            if (logCardTabIsReadOnly()) return;
            var field = e.target.closest && e.target.closest('.lc-saved-field');
            if (!field) return;

            var isTextInput = field.tagName === 'INPUT' && field.type === 'text';

            if (isTextInput) {
                if (e.type === 'input') {
                    return;
                }

                if (e.type === 'change' && field.dataset.logCardSkipNextChange === '1') {
                    field.dataset.logCardSkipNextChange = '0';
                    return;
                }
            }

            logCardPersistInlineSave(field);
        }

        logCardTabBody.addEventListener('change', logCardQueueInlineSave);
        logCardTabBody.addEventListener('input', logCardQueueInlineSave);
        logCardTabBody.addEventListener('keydown', function(e) {
            if (logCardTabIsReadOnly()) return;
            var field = e.target.closest && e.target.closest('.lc-saved-field');
            var isTextInput = field && field.tagName === 'INPUT' && field.type === 'text';
            if (!isTextInput || e.key !== 'Enter') return;

            e.preventDefault();
            field.dataset.logCardSkipNextChange = '1';
            clearTimeout(logCardInlineSaveTimer);
            var row = field.closest('.lc-saved-row');
            if (row) {
                logCardEnqueueInlineSave(row.dataset.rowIndex, field.name || '', field.value);
            }
            if (typeof field.blur === 'function') field.blur();
        });
    }
    document.addEventListener('click', function(e) {
        var snLink = e.target.closest && e.target.closest('.transfers-partial .change-sn-link');
        if (!snLink || !document.getElementById('changeSnModal')) return;
        e.preventDefault();
        var transferId = snLink.dataset.transferId;
        var currentSn = snLink.dataset.currentSn || '';
        transfersSnCell = snLink.closest('td');
        var snTransferIdEl = document.getElementById('snTransferId');
        var snInputEl = document.getElementById('component_sn');
        if (snTransferIdEl) snTransferIdEl.value = transferId;
        if (snInputEl) snInputEl.value = currentSn;
        var snModal = document.getElementById('changeSnModal');
        if (snModal && typeof bootstrap !== 'undefined') {
            var snInst = bootstrap.Modal.getOrCreateInstance(snModal);
            snInst.show();
        }
    });
    var changeSnFormTransfer = document.getElementById('changeSnForm');
    if (changeSnFormTransfer && transfersPartialUrl) {
        changeSnFormTransfer.addEventListener('submit', function(e) {
            e.preventDefault();
            var transferId = document.getElementById('snTransferId') && document.getElementById('snTransferId').value;
            var newSn = document.getElementById('component_sn') ? document.getElementById('component_sn').value : '';
            if (!transferId) return;
            var url = transfersUpdateSnUrlTemplate.replace('__ID__', transferId);
            fetch(url, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ component_sn: newSn })
            })
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    if (data.success) {
                        if (transfersSnCell) {
                            if (data.component_sn) {
                                transfersSnCell.textContent = '';
                                var aSn = document.createElement('a');
                                aSn.href = '#';
                                aSn.className = 'text-decoration-underline text-info change-sn-link';
                                aSn.setAttribute('data-transfer-id', transferId);
                                aSn.setAttribute('data-current-sn', data.component_sn);
                                aSn.setAttribute('data-bs-toggle', 'modal');
                                aSn.setAttribute('data-bs-target', '#changeSnModal');
                                aSn.textContent = data.component_sn;
                                transfersSnCell.appendChild(aSn);
                            } else {
                                transfersSnCell.textContent = '-';
                            }
                        }
                        var modalEl = document.getElementById('changeSnModal');
                        if (modalEl && typeof bootstrap !== 'undefined') {
                            var modalInstance = bootstrap.Modal.getInstance(modalEl);
                            if (modalInstance) modalInstance.hide();
                        }
                    } else if (typeof showNotification === 'function') {
                        showNotification('Failed to update Serial Number', 'error');
                    }
                })
                .catch(function() {
                    if (typeof showNotification === 'function') showNotification('Server error', 'error');
                });
        });
    }
    if (extraPartsBody) {
        extraPartsBody.addEventListener('click', function(e) {
            var editBtn = e.target.closest('.open-edit-extra-process-modal');
            if (editBtn && editBtn.dataset.extraProcessId) {
                e.preventDefault();
                openEditExtraProcessModal(editBtn.dataset.extraProcessId);
                return;
            }
            var addBtn = e.target.closest('.open-add-extra-process-modal');
            if (addBtn && addBtn.dataset.workorderId && addBtn.dataset.componentId) {
                e.preventDefault();
                openAddExtraProcessModal(addBtn.dataset.workorderId, addBtn.dataset.componentId);
                return;
            }
            var addExtraPartBtn = e.target.closest('.open-add-extra-part-modal-btn');
            if (addExtraPartBtn) {
                e.preventDefault();
                var woId = addExtraPartBtn.dataset.workorderId || window.currentWorkorderId;
                if (woId) openAddExtraPartModal(woId);
                return;
            }
            var procBtn = e.target.closest('.open-extra-processes-tab');
            if (procBtn && procBtn.dataset.workorderId && procBtn.dataset.componentId) {
                e.preventDefault();
                if (tabExtraProcessesLi) tabExtraProcessesLi.classList.remove('d-none');
                loadExtraProcesses(procBtn.dataset.workorderId, procBtn.dataset.componentId);
                if (tabExtraProcessesBtn) { var tab = new bootstrap.Tab(tabExtraProcessesBtn); tab.show(); }
            }
        });
    }
    var openAddExtraPartModalBtn = document.getElementById('openAddExtraPartModalBtn');
    if (openAddExtraPartModalBtn) {
        openAddExtraPartModalBtn.addEventListener('click', function() {
            var woId = this.dataset.workorderId || window.currentWorkorderId;
            if (woId) openAddExtraPartModal(woId);
        });
    }

    tdrShowTabListEl?.addEventListener('shown.bs.tab', function(e) {
        var activeTabId = e?.target?.id || '';
        if (isPersistentTabId(activeTabId)) {
            try { localStorage.setItem(TAB_STORAGE_KEY, activeTabId); } catch (_) {}
        }

        var target = (e.target.getAttribute && e.target.getAttribute('data-bs-target')) || (e.target.getAttribute && e.target.getAttribute('href'));
        var targetName = target ? String(target) : '';
        showOnlyTdrTabPane(targetName);
        if (targetName.indexOf('content-all-parts-processes') === -1 && targetName.indexOf('content-extra-parts-processes') === -1) {
            syncProcessShortcutButtonState('');
        }
        if (target && targetName.indexOf('content-part-processes') !== -1) {
            syncPartProcessesShortcutActions();
            if (allPartsGroupFormsTabActions) allPartsGroupFormsTabActions.classList.add('d-none');
            if (extraPartsTabActions) extraPartsTabActions.classList.add('d-none');
            if (logCardTabActions) logCardTabActions.classList.add('d-none');
            if (bushingTabActions) bushingTabActions.classList.add('d-none');
            if (transfersTabActions) transfersTabActions.classList.add('d-none');
            if (rmReportsTabBody) rmReportsTabBody.dataset.loaded = '';
            return;
        }
        if (target && targetName.indexOf('content-all-parts-processes') !== -1) {
            syncProcessShortcutButtonState('#content-all-parts-processes');
            if (partProcessesShortcutActions) partProcessesShortcutActions.classList.add('d-none');
            if (allPartsGroupFormsTabActions) allPartsGroupFormsTabActions.classList.remove('d-none');
            if (extraGroupFormsHeaderBtn) extraGroupFormsHeaderBtn.classList.add('d-none');
            if (extraPartsTabActions) extraPartsTabActions.classList.add('d-none');
            if (logCardTabActions) logCardTabActions.classList.add('d-none');
            if (bushingTabActions) bushingTabActions.classList.add('d-none');
            if (transfersTabActions) transfersTabActions.classList.add('d-none');
            syncAllPartsGroupFormsBtnVisibility();
            if (allPartsBody && !allPartsBody.dataset.loaded) {
                allPartsBody.dataset.loaded = '1';
                loadAllPartsProcesses();
            }
        } else if (target && targetName.indexOf('content-extra-parts-processes') !== -1) {
            syncProcessShortcutButtonState('#content-extra-parts-processes');
            if (partProcessesShortcutActions) partProcessesShortcutActions.classList.add('d-none');
            if (allPartsGroupFormsTabActions) allPartsGroupFormsTabActions.classList.add('d-none');
            if (extraPartsTabActions) extraPartsTabActions.classList.remove('d-none');
            if (logCardTabActions) logCardTabActions.classList.add('d-none');
            if (bushingTabActions) bushingTabActions.classList.add('d-none');
            if (transfersTabActions) transfersTabActions.classList.add('d-none');
            if (extraPartsBody && !extraPartsBody.dataset.loaded) {
                extraPartsBody.dataset.loaded = '1';
                loadExtraPartProcesses();
            } else {
                updateExtraPartsTabAsterisk();
                updateExtraGroupFormsButtonVisibility();
            }
        } else if (target && String(target).indexOf('content-log-card') !== -1) {
            if (partProcessesShortcutActions) partProcessesShortcutActions.classList.add('d-none');
            if (allPartsGroupFormsTabActions) allPartsGroupFormsTabActions.classList.add('d-none');
            if (extraGroupFormsHeaderBtn) extraGroupFormsHeaderBtn.classList.add('d-none');
            if (extraPartsTabActions) extraPartsTabActions.classList.add('d-none');
            if (bushingTabActions) bushingTabActions.classList.add('d-none');
            if (transfersTabActions) transfersTabActions.classList.add('d-none');
            if (logCardTabActions) logCardTabActions.classList.remove('d-none');
            if (logCardTabBody && !logCardTabBody.dataset.loaded) {
                logCardTabBody.dataset.loaded = '1';
                loadLogCardPartial();
            }
        } else if (target && String(target).indexOf('content-bushing') !== -1) {
            if (partProcessesShortcutActions) partProcessesShortcutActions.classList.add('d-none');
            if (allPartsGroupFormsTabActions) allPartsGroupFormsTabActions.classList.add('d-none');
            if (extraGroupFormsHeaderBtn) extraGroupFormsHeaderBtn.classList.add('d-none');
            if (extraPartsTabActions) extraPartsTabActions.classList.add('d-none');
            if (logCardTabActions) logCardTabActions.classList.add('d-none');
            if (transfersTabActions) transfersTabActions.classList.add('d-none');
            if (bushingTabActions) bushingTabActions.classList.remove('d-none');
            if (bushingTabBody && !bushingTabBody.dataset.loaded) {
                bushingTabBody.dataset.loaded = '1';
                loadBushingPartial();
            }
        } else if (target && String(target).indexOf('content-rm-reports') !== -1) {
            if (partProcessesShortcutActions) partProcessesShortcutActions.classList.add('d-none');
            if (allPartsGroupFormsTabActions) allPartsGroupFormsTabActions.classList.add('d-none');
            if (extraGroupFormsHeaderBtn) extraGroupFormsHeaderBtn.classList.add('d-none');
            if (extraPartsTabActions) extraPartsTabActions.classList.add('d-none');
            if (logCardTabActions) logCardTabActions.classList.add('d-none');
            if (bushingTabActions) bushingTabActions.classList.add('d-none');
            if (transfersTabActions) transfersTabActions.classList.add('d-none');
            if (rmReportsTabBody && !rmReportsTabBody.dataset.loaded) {
                rmReportsTabBody.dataset.loaded = '1';
                loadRmReportsPartial();
            }
        } else if (target && String(target).indexOf('content-transfers') !== -1) {
            if (partProcessesShortcutActions) partProcessesShortcutActions.classList.add('d-none');
            if (allPartsGroupFormsTabActions) allPartsGroupFormsTabActions.classList.add('d-none');
            if (extraGroupFormsHeaderBtn) extraGroupFormsHeaderBtn.classList.add('d-none');
            if (extraPartsTabActions) extraPartsTabActions.classList.add('d-none');
            if (logCardTabActions) logCardTabActions.classList.add('d-none');
            if (bushingTabActions) bushingTabActions.classList.add('d-none');
            if (transfersTabActions) transfersTabActions.classList.remove('d-none');
            if (transfersTabBody && !transfersTabBody.dataset.loaded) {
                transfersTabBody.dataset.loaded = '1';
                loadTransfersPartial();
            }
        } else if (target && String(target).indexOf('content-extra-processes') !== -1) {
            if (partProcessesShortcutActions) partProcessesShortcutActions.classList.add('d-none');
            if (allPartsGroupFormsTabActions) allPartsGroupFormsTabActions.classList.add('d-none');
            if (extraGroupFormsHeaderBtn) extraGroupFormsHeaderBtn.classList.add('d-none');
            if (extraPartsTabActions) extraPartsTabActions.classList.add('d-none');
            if (logCardTabActions) logCardTabActions.classList.add('d-none');
            if (bushingTabActions) bushingTabActions.classList.add('d-none');
            if (transfersTabActions) transfersTabActions.classList.add('d-none');
        } else {
            if (partProcessesShortcutActions) partProcessesShortcutActions.classList.add('d-none');
            if (allPartsGroupFormsTabActions) allPartsGroupFormsTabActions.classList.add('d-none');
            if (extraGroupFormsHeaderBtn) extraGroupFormsHeaderBtn.classList.add('d-none');
            if (extraPartsTabActions) extraPartsTabActions.classList.add('d-none');
            if (logCardTabActions) logCardTabActions.classList.add('d-none');
            if (bushingTabActions) bushingTabActions.classList.add('d-none');
            if (transfersTabActions) transfersTabActions.classList.add('d-none');
        }
        if (target && String(target).indexOf('content-part-processes') === -1) {
            if (tabLi) tabLi.classList.add('d-none');
            syncPartProcessesShortcutActions();
            if (body) body.innerHTML = '<div class="text-center py-5 text-muted">{{ __("Click a component processes button to load.") }}</div>';
        }
        if (target && String(target).indexOf('content-extra-processes') === -1) {
            if (tabExtraProcessesLi) tabExtraProcessesLi.classList.add('d-none');
            if (extraProcessesTabBody) extraProcessesTabBody.innerHTML = '<div class="text-center py-5 text-muted">{{ __("Click Processes in Extra Part Processes table to load.") }}</div>';
        }
    });

    (function restorePersistentTab() {
        var savedTabId = null;
        try { savedTabId = localStorage.getItem(TAB_STORAGE_KEY); } catch (_) {}

        if (!isPersistentTabId(savedTabId)) {
            revealTabsContent();
            return;
        }

        var savedTabBtn = document.getElementById(savedTabId);
        if (!savedTabBtn) {
            revealTabsContent();
            return;
        }

        var isHiddenTab = !!savedTabBtn.closest('.d-none');
        if (isHiddenTab) {
            revealTabsContent();
            return;
        }

        try {
            var tabInstance = bootstrap.Tab.getOrCreateInstance(savedTabBtn);
            tabInstance.show();
        } catch (_) {
            // keep default tab
        } finally {
            revealTabsContent();
        }
    })();

    if (bushingTabBody && typeof loadBushingPartial === 'function') {
        loadBushingPartial();
    }

    function loadModalContentViaAjax(modalId, bodyId, url, type) {
        var modal = document.getElementById(modalId);
        var bodyEl = document.getElementById(bodyId);
        if (!modal || !bodyEl) return;
        if (!url || typeof url !== 'string') { bodyEl.innerHTML = '<div class="alert alert-danger">{{ __("Invalid URL.") }}</div>'; return; }
        var fetchUrl = url;
        if (url.startsWith('/') && !url.startsWith('//')) {
            fetchUrl = window.location.origin + url;
        } else if (url.startsWith('http')) {
            try {
                var u = new URL(url);
                if (u.origin !== window.location.origin) {
                    fetchUrl = window.location.origin + u.pathname + u.search;
                }
            } catch (e) {}
        }
        bodyEl.innerHTML = '<div class="text-center py-5 text-muted">{{ __("Loading...") }}</div>';
        var inst = bootstrap.Modal.getOrCreateInstance(modal);
        inst.show();
        modal.addEventListener('shown.bs.modal', function setZ() { modal.style.zIndex = '1090'; var b = document.querySelectorAll('.modal-backdrop'); if (b.length) b[b.length-1].style.zIndex = '1085'; }, { once: true });
        fetch(fetchUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }, credentials: 'same-origin' })
            .then(function(r) {
                if (!r.ok) throw new Error('HTTP ' + r.status + ' ' + r.statusText);
                return r.text();
            })
            .then(function(html) {
                var parser = new DOMParser();
                var doc = parser.parseFromString(html, 'text/html');
                var formEl = doc.querySelector('form');
                var container = doc.querySelector('.container') || doc.querySelector('.card') || (formEl && formEl.parentElement);
                var content = container ? container.outerHTML : (doc.body ? doc.body.innerHTML : html);
                bodyEl.innerHTML = content;
                var scope = bodyEl;
                var form = scope.querySelector('form');
                var hasExpectedForm = (type === 'processes' && scope.querySelector('#createCMMForm')) || (type === 'components' && (scope.querySelector('#createForm') || scope.querySelector('#createComponentForm')));
                if (!form || !hasExpectedForm) {
                    bodyEl.innerHTML = '<div class="alert alert-danger">{{ __("Failed to load.") }} {{ __("Form not found. You may need to log in again.") }}</div>';
                    return;
                }
                if (window.$ && $.fn.select2) {
                    var $ = window.$;
                    if (type === 'processes') {
                        var pn = scope.querySelector('#process_name_id');
                        if (pn) $(pn).select2({ theme: 'bootstrap-5', width: '100%', dropdownParent: $(modal) });
                        var exList = scope.querySelector('#ex_process-list');
                        var manualInput = scope.querySelector('input[name="manual_id"]');
                        if (pn && exList && manualInput) {
                            function loadExisting() {
                                var pid = $(pn).val();
                                if (!pid) { exList.innerHTML = ''; return; }
                                var gpBase = (typeof getProcessesBaseUrl !== 'undefined' ? getProcessesBaseUrl : '/get-processes');
                                fetch(gpBase + (gpBase.indexOf('?') >= 0 ? '&' : '?') + 'processNameId=' + encodeURIComponent(pid) + '&manualId=' + encodeURIComponent(manualInput.value))
                                    .then(function(r) { return r.json(); })
                                    .then(function(data) {
                                        exList.innerHTML = '';
                                        if (data.existingProcesses && data.existingProcesses.length) {
                                            data.existingProcesses.forEach(function(p) {
                                                var d = document.createElement('div'); d.className = 'process-item'; d.textContent = p.process; d.style.marginBottom = '5px'; exList.appendChild(d);
                                            });
                                        } else exList.innerHTML = '<div class="text-muted small">There are no existing processes</div>';
                                    })
                                    .catch(function() { exList.innerHTML = '<div class="text-danger small">Failed to load processes</div>'; });
                            }
                            $(pn).on('change', loadExisting);
                            loadExisting();
                        }
                    } else if (type === 'components') {
                        var manualSel = scope.querySelector('#manual_id');
                        if (manualSel) $(manualSel).select2({ theme: 'bootstrap-5', width: '100%', dropdownParent: $(modal) });
                        var bushCb = scope.querySelector('#is_bush');
                        var bushCnt = scope.querySelector('#bush_ipl_container');
                        var bushInp = scope.querySelector('#bush_ipl_num');
                        if (bushCb && bushCnt && bushInp) {
                            bushCb.addEventListener('change', function() {
                                if (this.checked) { bushCnt.style.display = 'block'; bushInp.required = true; } else { bushCnt.style.display = 'none'; bushInp.required = false; bushInp.value = ''; }
                            });
                            if (bushCb.checked) { bushCnt.style.display = 'block'; bushInp.required = true; }
                        }
                    }
                }
                scope.querySelectorAll('a.btn-outline-secondary, a[href*="return_to"], a[href*="redirect"]').forEach(function(lnk) {
                    lnk.addEventListener('click', function(ev) {
                        ev.preventDefault();
                        var m = bootstrap.Modal.getInstance(modal);
                        if (m) m.hide();
                    });
                });
                var form = scope.querySelector('form');
                if (form) {
                    form.addEventListener('submit', function(ev) {
                        ev.preventDefault();
                        var fd = new FormData(form);
                        var submitBtn = form.querySelector('button[type="submit"]');
                        if (submitBtn) { submitBtn.disabled = true; }
                        fetch(form.action, {
                            method: 'POST',
                            body: fd,
                            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                            credentials: 'same-origin'
                        })
                        .then(function(r) { return r.json().catch(function() { return {}; }); })
                        .then(function(data) {
                            if (data.success) {
                                var m = bootstrap.Modal.getInstance(modal);
                                if (m) m.hide();
                                bodyEl.innerHTML = '';
                                if (bushingTabBody) loadBushingPartial();
                                var editIfr = document.getElementById('editBushingIframe');
                                if (editIfr && editIfr.src && editIfr.src !== 'about:blank') { try { editIfr.contentWindow.location.reload(); } catch(e){} }
                            } else {
                                window.tdrShowNotify(data.message || (data.errors ? JSON.stringify(data.errors) : '') || '{{ __("Error.") }}', 'error');
                            }
                        })
                        .catch(function() {
                            window.tdrShowNotify('{{ __("Failed to submit.") }}', 'error');
                        })
                        .finally(function() {
                            if (typeof window.hideLoadingSpinner === 'function') window.hideLoadingSpinner();
                            if (submitBtn) { submitBtn.disabled = false; }
                        });
                    });
                }
            })
            .catch(function(err) {
                var msg = '{{ __("Failed to load.") }}';
                if (err && err.message && err.message.indexOf('HTTP') === 0) msg += ' (' + err.message + ')';
                bodyEl.innerHTML = '<div class="alert alert-danger">' + msg + '<br><a href="' + fetchUrl + '" target="_blank" class="alert-link mt-2 d-inline-block">{{ __("Open in new tab") }}</a></div>';
            });
    }

    window.addEventListener('message', function(e) {
        if (e.data && e.data.type === 'createProcessSuccess' && e.data.tdrId) {
            loadProcessesAndBind(e.data.tdrId, activeProcessesContainer || body);
            if (allPartsBody && allPartsBody.dataset.loaded) loadAllPartsProcesses();
            window.tdrShowNotify(e.data.message || '{{ __("Process added successfully.") }}', 'success', 2500);
        } else if (e.data && e.data.type === 'createProcessCancel') {
            return;
        } else if (e.data && e.data.type === 'editProcessSuccess' && e.data.tdrId) {
            var m = bootstrap.Modal.getInstance(document.getElementById('editTdrProcessModal'));
            if (m) m.hide();
            var ifr = document.getElementById('editTdrProcessIframe');
            if (ifr) ifr.src = 'about:blank';
            loadProcessesAndBind(e.data.tdrId, activeProcessesContainer || body);
            if (allPartsBody && allPartsBody.dataset.loaded) loadAllPartsProcesses();
            window.tdrShowNotify(e.data.message || '{{ __("Process updated successfully.") }}', 'success', 2500);
        } else if (e.data && e.data.type === 'editProcessCancel') {
            var m = bootstrap.Modal.getInstance(document.getElementById('editTdrProcessModal'));
            if (m) m.hide();
            var ifr = document.getElementById('editTdrProcessIframe');
            if (ifr) ifr.src = 'about:blank';
        } else if (e.data && e.data.type === 'editExtraProcessSuccess') {
            var m = bootstrap.Modal.getInstance(document.getElementById('editExtraProcessModal'));
            if (m) m.hide();
            var ifr = document.getElementById('editExtraProcessIframe');
            if (ifr) ifr.src = 'about:blank';
            if (extraPartsBody && extraPartsBody.dataset.loaded) loadExtraPartProcesses();
        } else if (e.data && e.data.type === 'editExtraProcessCancel') {
            var m = bootstrap.Modal.getInstance(document.getElementById('editExtraProcessModal'));
            if (m) m.hide();
            var ifr = document.getElementById('editExtraProcessIframe');
            if (ifr) ifr.src = 'about:blank';
        } else if (e.data && e.data.type === 'createExtraProcessSuccess') {
            var m = bootstrap.Modal.getInstance(document.getElementById('addExtraProcessModal'));
            if (m) m.hide();
            var ifr = document.getElementById('addExtraProcessIframe');
            if (ifr) ifr.src = 'about:blank';
            if (extraPartsBody && extraPartsBody.dataset.loaded) loadExtraPartProcesses();
            var woId = e.data.workorderId; var compId = e.data.componentId;
            if (extraProcessesTabBody && woId && compId) loadExtraProcesses(woId, compId);
        } else if (e.data && e.data.type === 'createExtraProcessCancel') {
            var m = bootstrap.Modal.getInstance(document.getElementById('addExtraProcessModal'));
            if (m) m.hide();
            var ifr = document.getElementById('addExtraProcessIframe');
            if (ifr) ifr.src = 'about:blank';
        } else if (e.data && e.data.type === 'addExtraPartSuccess') {
            var m = bootstrap.Modal.getInstance(document.getElementById('addExtraPartModal'));
            if (m) m.hide();
            var ifr = document.getElementById('addExtraPartIframe');
            if (ifr) ifr.src = 'about:blank';
            if (extraPartsBody && extraPartsBody.dataset.loaded) loadExtraPartProcesses();
        } else if (e.data && e.data.type === 'addExtraPartCancel') {
            var m = bootstrap.Modal.getInstance(document.getElementById('addExtraPartModal'));
            if (m) m.hide();
            var ifr = document.getElementById('addExtraPartIframe');
            if (ifr) ifr.src = 'about:blank';
        } else if (e.data && e.data.type === 'editExtraProcessProcessSuccess') {
            var m = bootstrap.Modal.getInstance(document.getElementById('editTdrProcessModal'));
            if (m) m.hide();
            var ifr = document.getElementById('editTdrProcessIframe');
            if (ifr) ifr.src = 'about:blank';
            var woId = e.data.workorderId; var compId = e.data.componentId;
            if (extraProcessesTabBody && woId && compId) loadExtraProcesses(woId, compId);
            if (extraPartsBody && extraPartsBody.dataset.loaded) loadExtraPartProcesses();
        } else if (e.data && e.data.type === 'editExtraProcessProcessCancel') {
            var m = bootstrap.Modal.getInstance(document.getElementById('editTdrProcessModal'));
            if (m) m.hide();
            var ifr = document.getElementById('editTdrProcessIframe');
            if (ifr) ifr.src = 'about:blank';
        } else if (e.data && e.data.type === 'editBushingSuccess') {
            var m = bootstrap.Modal.getInstance(document.getElementById('editBushingModal'));
            if (m) m.hide();
            var ifr = document.getElementById('editBushingIframe');
            if (ifr) ifr.src = 'about:blank';
            if (bushingTabBody) loadBushingPartial();
        } else if (e.data && e.data.type === 'editBushingCancel') {
            var m = bootstrap.Modal.getInstance(document.getElementById('editBushingModal'));
            if (m) m.hide();
            var ifr = document.getElementById('editBushingIframe');
            if (ifr) ifr.src = 'about:blank';
        } else if (e.data && e.data.type === 'openAddProcessesModal' && e.data.url) {
            openAddProcessesModalByUrl(e.data.url);
        } else if (e.data && e.data.type === 'addProcessesCancel') {
            var m = bootstrap.Modal.getInstance(document.getElementById('addProcessesModal'));
            if (m) m.hide();
            var ifr = document.getElementById('addProcessesIframe');
            if (ifr) ifr.src = 'about:blank';
        } else if (e.data && e.data.type === 'openAddPartModal' && e.data.url) {
            openAddPartModalByUrl(e.data.url);
        } else if (e.data && e.data.type === 'addPartCancel') {
            var m = bootstrap.Modal.getInstance(document.getElementById('addPartModal'));
            if (m) m.hide();
            var ifr = document.getElementById('addPartIframe');
            if (ifr) ifr.src = 'about:blank';
        } else if (e.data && (e.data.type === 'addProcessesSuccess' || e.data.type === 'addPartSuccess')) {
            var procModal = document.getElementById('addProcessesModal');
            var partModal = document.getElementById('addPartModal');
            var m1 = procModal ? bootstrap.Modal.getInstance(procModal) : null;
            var m2 = partModal ? bootstrap.Modal.getInstance(partModal) : null;
            if (m1) m1.hide();
            if (m2) m2.hide();
            var ifr1 = document.getElementById('addProcessesIframe');
            var b2 = document.getElementById('addPartModalBody');
            if (ifr1) ifr1.src = 'about:blank';
            if (b2) b2.innerHTML = '';
            if (bushingTabBody) loadBushingPartial();
            var editIfr = document.getElementById('editBushingIframe');
            if (editIfr && editIfr.src && editIfr.src !== 'about:blank') { try { editIfr.contentWindow.location.reload(); } catch(e){} }
            window.tdrShowNotify(e.data.message || (e.data.type === 'addPartSuccess' ? '{{ __("Part added successfully.") }}' : '{{ __("Process added successfully.") }}'), 'success', 2500);
        }
    });
    document.getElementById('editTdrProcessModal')?.addEventListener('hidden.bs.modal', function() {
        var ifr = document.getElementById('editTdrProcessIframe');
        if (ifr) ifr.src = 'about:blank';
    });
    document.getElementById('editExtraProcessModal')?.addEventListener('hidden.bs.modal', function() {
        var ifr = document.getElementById('editExtraProcessIframe');
        if (ifr) ifr.src = 'about:blank';
    });
    document.getElementById('addExtraProcessModal')?.addEventListener('hidden.bs.modal', function() {
        var ifr = document.getElementById('addExtraProcessIframe');
        if (ifr) ifr.src = 'about:blank';
    });
    document.getElementById('editBushingModal')?.addEventListener('hidden.bs.modal', function() {
        var ifr = document.getElementById('editBushingIframe');
        if (ifr) ifr.src = 'about:blank';
    });
    document.getElementById('addProcessesModal')?.addEventListener('hidden.bs.modal', function() {
        var ifr = document.getElementById('addProcessesIframe');
        if (ifr) ifr.src = 'about:blank';
        var editIfr = document.getElementById('editBushingIframe');
        if (editIfr && editIfr.src && editIfr.src !== 'about:blank') { try { editIfr.contentWindow.location.reload(); } catch(e){} }
    });
    document.getElementById('addPartModal')?.addEventListener('hidden.bs.modal', function() {
        var ifr = document.getElementById('addPartIframe');
        if (ifr) ifr.src = 'about:blank';
        var editIfr = document.getElementById('editBushingIframe');
        if (editIfr && editIfr.src && editIfr.src !== 'about:blank') { try { editIfr.contentWindow.location.reload(); } catch(e){} }
    });
    if (editTdrModal) {
        editTdrModal.addEventListener('show.bs.modal', function(e) {
            var btn = e.relatedTarget;
            var body = document.getElementById('editTdrModalBody');
            if (!body) return;
            body.innerHTML = '<div class="text-center py-5 text-muted">{{ __("Loading...") }}</div>';
            if (btn && btn.dataset.tdrId) {
                var tdrId = btn.dataset.tdrId;
                var url = '{{ route("tdrs.editForm", ["id" => "__ID__"]) }}'.replace('__ID__', tdrId);
                fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' } })
                    .then(function(r) { return r.text(); })
                    .then(function(html) {
                        body.innerHTML = html;
                        if (window.$ && window.$.fn.select2) {
                            var $ = window.$;
                            $('#edit_codes_id, #edit_necessaries_id').select2({ theme: 'bootstrap-5', width: '100%', dropdownParent: $(document.body) });
                        }
                        var form = body.querySelector('#editTdrForm');
                        if (form) {
                            form.addEventListener('submit', function(ev) {
                                ev.preventDefault();
                                var submitBtn = form.querySelector('button[type="submit"]');
                                var origText = submitBtn ? submitBtn.innerHTML : '';
                                if (submitBtn) { submitBtn.disabled = true; submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> {{ __("Saving...") }}'; }
                                var formData = new FormData(form);
                                fetch(form.action, { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '' } })
                                    .then(function(res) { return res.json().catch(function() { return {}; }); })
                                    .then(function(data) {
                                        if (data.redirect || !data.errors) {
                                            var m = bootstrap.Modal.getInstance(editTdrModal);
                                            if (m) m.hide();
                                            try {
                                                window.sessionStorage.setItem('tdrShowPendingNotification', data.message || '{{ __("Updated.") }}');
                                            } catch (e) {}
                                            window.location.reload();
                                        } else {
                                            window.tdrShowNotify(data.message || '{{ __("Failed to update.") }}', 'error');
                                            if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = origText; }
                                        }
                                    })
                                    .catch(function() {
                                        window.tdrShowNotify('{{ __("Failed to update.") }}', 'error');
                                        if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = origText; }
                                    });
                            });
                        }
                    })
                    .catch(function() {
                        body.innerHTML = '<div class="alert alert-danger">{{ __("Failed to load form.") }}</div>';
                    });
            }
        });
    }
});
</script>
<script src="{{ asset('js/tdrs/show/workorder-form-handler.js') }}"></script>
<script src="{{ asset('js/tdrs/show/pdf-badge-handler.js') }}"></script>
<script src="{{ asset('js/tdrs/show/pdf-library-handler.js') }}"></script>
<script src="{{ asset('js/tdrs/show/pdf-viewer-handler.js') }}"></script>
<script src="{{ asset('js/tdrs/show/pdf-upload-handler.js') }}"></script>
<script src="{{ asset('js/tdrs/show/pdf-delete-handler.js') }}"></script>
<script src="{{ asset('js/tdrs/show/show-main.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const selectAllCheckbox = document.getElementById('selectAllConditions');
        const conditionCheckboxes = document.querySelectorAll('.condition-checkbox');
        const saveBtn = document.getElementById('saveUnitInspectionsBtn');
        const form = document.getElementById('unitInspectionForm');

        const manageConditionBtn = document.querySelector('[data-bs-target="#manageConditionModal"]');
        const unitInspectionModal = document.getElementById('unitInspectionModal');
        const manageConditionModal = document.getElementById('manageConditionModal');

        if (manageConditionBtn && unitInspectionModal && manageConditionModal) {
            manageConditionBtn.addEventListener('click', function () {
                manageConditionModal.dataset.returnToModal = 'unitInspectionModal';
            });
            manageConditionModal.addEventListener('hidden.bs.modal', function () {
                if (manageConditionModal.dataset.returnToModal === 'unitInspectionModal') {
                    const unitModal = new bootstrap.Modal(unitInspectionModal);
                    unitModal.show();
                    delete manageConditionModal.dataset.returnToModal;
                }
            });
        }

        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function () {
                conditionCheckboxes.forEach(checkbox => { checkbox.checked = this.checked; });
            });
        }
        conditionCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function () {
                const allChecked = Array.from(conditionCheckboxes).every(cb => cb.checked);
                const someChecked = Array.from(conditionCheckboxes).some(cb => cb.checked);
                if (selectAllCheckbox) {
                    selectAllCheckbox.checked = allChecked;
                    selectAllCheckbox.indeterminate = someChecked && !allChecked;
                }
            });
        });

        if (saveBtn && form) {
            saveBtn.addEventListener('click', function () {
                const formData = new FormData(form);
                const conditionsData = {};
                conditionCheckboxes.forEach(checkbox => {
                    if (checkbox.checked) {
                        const conditionId = checkbox.getAttribute('data-condition-id');
                        const notesInput = document.querySelector(`input[name="conditions[${conditionId}][notes]"]`);
                        const inspectionIdInput = document.querySelector(`input[name="conditions[${conditionId}][inspection_id]"]`);
                        conditionsData[conditionId] = {
                            selected: true,
                            notes: notesInput ? notesInput.value : '',
                            inspection_id: inspectionIdInput ? inspectionIdInput.value : null
                        };
                    }
                });
                saveBtn.disabled = true;
                saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> {{ __("Saving...") }}';
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || formData.get('_token') || '{{ csrf_token() }}';
                fetch('{{ route("tdrs.store.unit-inspections") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify({ workorder_id: formData.get('workorder_id'), conditions: conditionsData })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('unitInspectionModal'));
                        if (modal) modal.hide();
                        const tabTdr = document.getElementById('tab-tdr');
                        if (tabTdr) { const t = new bootstrap.Tab(tabTdr); t.show(); }
                        window.location.reload();
                    } else {
                        if (typeof showNotification === 'function') showNotification(data.message || '{{ __("An error occurred while saving.") }}', 'error');
                        saveBtn.disabled = false;
                        saveBtn.innerHTML = '<i class="fas fa-save"></i> {{ __('Save') }}';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    (typeof showNotification === 'function' ? (m) => showNotification(m, 'error') : (window.NotificationHandler?.error || window.notifyError))('{{ __("An error occurred while saving.") }}');
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<i class="fas fa-save"></i> {{ __('Save') }}';
                });
            });
        }
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
        document.querySelectorAll('.edit-condition-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const conditionId = this.getAttribute('data-condition-id');
                const row = document.querySelector(`tr[data-condition-id="${conditionId}"]`);
                if (row) {
                    this.closest('.btn-group').classList.add('d-none');
                    row.querySelector('.save-cancel-group').classList.remove('d-none');
                    row.querySelector('.condition-name-display').classList.add('d-none');
                    const editInput = row.querySelector('.condition-name-edit');
                    editInput.classList.remove('d-none');
                    editInput.focus();
                }
            });
        });
        document.querySelectorAll('.cancel-edit-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const conditionId = this.getAttribute('data-condition-id');
                const row = document.querySelector(`tr[data-condition-id="${conditionId}"]`);
                if (row) {
                    const input = row.querySelector('.condition-name-edit');
                    input.value = input.getAttribute('data-original-name');
                    input.classList.add('d-none');
                    row.querySelector('.condition-name-display').classList.remove('d-none');
                    row.querySelector('.save-cancel-group').classList.add('d-none');
                    row.querySelector('.btn-group').classList.remove('d-none');
                }
            });
        });
        document.querySelectorAll('.save-condition-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const conditionId = this.getAttribute('data-condition-id');
                const row = document.querySelector(`tr[data-condition-id="${conditionId}"]`);
                const input = row?.querySelector('.condition-name-edit');
                const newName = input?.value.trim() || '';
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> {{ __("Saving...") }}';
                fetch(`/admin/conditions/${conditionId}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify({ name: newName, unit: 1 })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        row.querySelector('.condition-name-display').textContent = newName || '{{ __("(No name)") }}';
                        input.setAttribute('data-original-name', newName);
                        input.classList.add('d-none');
                        row.querySelector('.condition-name-display').classList.remove('d-none');
                        row.querySelector('.save-cancel-group').classList.add('d-none');
                        row.querySelector('.btn-group').classList.remove('d-none');
                        const tabTdr = document.getElementById('tab-tdr');
                        if (tabTdr) { const t = new bootstrap.Tab(tabTdr); t.show(); }
                        window.location.reload();
                    } else {
                        (typeof showNotification === 'function' ? (m) => showNotification(m, 'error') : (window.NotificationHandler?.error || window.notifyError))(data.message || '{{ __("An error occurred while saving.") }}');
                        this.disabled = false;
                        this.innerHTML = '<i class="fas fa-check"></i> {{ __("Save") }}';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-check"></i> {{ __("Save") }}';
                });
            });
        });
        document.querySelectorAll('.delete-condition-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const conditionId = this.getAttribute('data-condition-id');
                const conditionName = this.getAttribute('data-condition-name');
                const button = this;
                window.tdrShowConfirm(`{{ __("Are you sure you want to delete condition") }} "${conditionName}"?`).then(function(confirmed) {
                    if (!confirmed) return;
                    button.disabled = true;
                    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> {{ __("Deleting...") }}';
                    const workorderId = document.querySelector('#unitInspectionForm input[name="workorder_id"]')?.value || document.querySelector('#addConditionFormFromManage input[name="workorder_id"]')?.value || window.currentWorkorderId;
                    const deleteUrl = workorderId ? `/admin/conditions/${conditionId}?workorder_id=${workorderId}` : `/admin/conditions/${conditionId}`;
                    fetch(deleteUrl, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
                    })
                    .then(response => {
                        if (response.redirected && (response.url.includes('/show2/') || response.url.includes('/tdrs/show/'))) {
                            window.top.location.href = response.url;
                            return null;
                        }
                        if (response.redirected && window.tdrShowUrl) {
                            window.top.location.href = window.tdrShowUrl;
                            return null;
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (!data) return;
                        if (data.success) {
                            const redirectUrl = data.redirect || window.tdrShowUrl;
                            delete manageConditionModal.dataset.returnToModal;
                            window.top.location.href = redirectUrl;
                        } else {
                            window.tdrShowNotify(data.message || '{{ __("An error occurred while deleting.") }}', 'error');
                            button.disabled = false;
                            button.innerHTML = '<i class="fas fa-trash"></i> {{ __("Delete") }}';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        window.tdrShowNotify('{{ __("An error occurred while deleting.") }}', 'error');
                        button.disabled = false;
                        button.innerHTML = '<i class="fas fa-trash"></i> {{ __("Delete") }}';
                    });
                });
            });
        });
        const addConditionFormFromManage = document.getElementById('addConditionFormFromManage');
        if (addConditionFormFromManage) {
            addConditionFormFromManage.addEventListener('submit', function (e) {
                e.preventDefault();
                const formData = new FormData(this);
                const submitBtn = this.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> {{ __("Saving...") }}';
                fetch('{{ route("conditions.store") }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: formData
                })
                .then(response => response.headers.get('content-type')?.includes('application/json') ? response.json() : {})
                .then(data => {
                    if (data.success !== false) {
                        const addModal = bootstrap.Modal.getInstance(document.getElementById('addConditionModalFromManage'));
                        if (addModal) addModal.hide();
                        const tabTdr = document.getElementById('tab-tdr');
                        if (tabTdr) { const t = new bootstrap.Tab(tabTdr); t.show(); }
                        window.location.replace(window.tdrShowUrl);
                    } else {
                        (typeof showNotification === 'function' ? (m) => showNotification(m, 'error') : (window.NotificationHandler?.error || window.notifyError))(data.message || '{{ __("An error occurred while saving.") }}');
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '{{ __("Save Condition") }}';
                    }
                })
                .catch(() => {
                    (typeof showNotification === 'function' ? (m) => showNotification(m, 'error') : (window.NotificationHandler?.error || window.notifyError))('{{ __("An error occurred while saving.") }}');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '{{ __("Save Condition") }}';
                });
            });
        }
    });
</script>


