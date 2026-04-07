<script>
    window.currentWorkorderId = {{ $current_wo->id }};
    window.tdrShowUrl = '{{ route("tdrs.show", ["id" => $current_wo->id]) }}';
</script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script src="{{ asset('js/tdr-processes/sortable-handler.js') }}"></script>
<script src="{{ asset('js/tdr-processes/vendor-handler.js') }}"></script>
<script src="{{ asset('js/tdr-processes/form-link-handler.js') }}"></script>
<script src="{{ asset('js/tdr-processes/edit-process/edit-process.js') }}"></script>
<script>
    window.ProcessesConfig = window.ProcessesConfig || {};
    ProcessesConfig.updateOrderUrl = '{{ route("tdr-processes.update-order") }}';
    ProcessesConfig.storeVendorUrl = '{{ route("vendors.store") }}';
</script>
@include('admin.tdrs.partials.component-inspection-scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var tdrShowTabListEl = document.getElementById('tdrShowTabList');
    var tdrShowTabsHeaderEl = document.getElementById('tdrShowTabsHeader');
    var tdrShowTabsLoadingEl = document.getElementById('tdrShowTabsLoading');
    var tdrShowTabContentEl = document.getElementById('tdrShowTabContent');
    var TAB_STORAGE_KEY = 'tdr_show_active_tab_wo_{{ $current_wo->id }}';
    var PERSISTENT_TAB_IDS = [
        'tab-tdr',
        'tab-all-parts-processes',
        'tab-extra-parts-processes',
        'tab-log-card',
        'tab-bushing',
        'tab-rm-reports',
        'tab-std-processes',
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
    var createProcessesUrl = '{{ route("tdr-processes.createProcesses", ["tdrId" => "__ID__"]) }}';
    var editFormUrl = '{{ route("tdr-processes.editForm", ["id" => "__ID__"]) }}';
    var updateOrderUrl = '{{ route("tdr-processes.update-order") }}';
    var body = document.getElementById('componentProcessesTabBody');
    var tabLi = document.getElementById('tab-part-processes-li');
    var tabBtn = document.getElementById('tab-part-processes');
    var woNum = document.getElementById('compProcessesWoNumber');
    var itemName = document.getElementById('compProcessesName');
    var itemIpl = document.getElementById('compProcessesIpl');
    var itemPn = document.getElementById('compProcessesPn');
    var itemSn = document.getElementById('compProcessesSn');
    var addProcessBtn = document.getElementById('compProcessesAddProcessBtn');
    var allPartsProcessesUrl = '{{ route("tdrs.processesPartial", ["workorder_id" => $current_wo->id]) }}';
    var allPartsBody = document.getElementById('allPartsProcessesTabBody');
    {{-- Имя extra_processes.partial: не используем route(), чтобы не падать при устаревшем route:cache; путь совпадает с routes/web.php --}}
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
    var stdProcessesTabBody = document.getElementById('stdProcessesTabBody');
    var stdProcessesPartialUrl = @json($current_wo->instruction_id == 1 ? route('ndt-cad-csv.partial', ['workorder' => $current_wo->id]) : null);
    var createLogCardUrl = '{{ route("log_card.create", ["id" => "__ID__"]) }}';
    var editLogCardUrl = '{{ route("log_card.edit", ["id" => "__ID__"]) }}';
    var editBushingUrl = '{{ route("wo_bushings.edit", ["wo_bushing" => "__ID__"]) }}';
    var getProcessesBaseUrl = '{{ url("/get-processes") }}';

    function loadAllPartsProcesses() {
        if (!allPartsBody) return;
        allPartsBody.innerHTML = '<div class="text-center py-5 text-muted">{{ __("Loading...") }}</div>';
        fetch(allPartsProcessesUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }, spinner: false })
            .then(function(r) { return r.text(); })
            .then(function(html) {
                allPartsBody.innerHTML = html;
                initAllPartsGroupForms(allPartsBody);
            })
            .catch(function() {
                allPartsBody.innerHTML = '<div class="alert alert-danger">{{ __("Failed to load.") }}</div>';
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
                    if (typeof showNotification === 'function') {
                        showNotification('{{ __("Session expired. Please log in again.") }}', 'warning', 5000);
                    } else {
                        alert('{{ __("Session expired. Please log in again.") }}');
                    }
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

    function loadStdProcessesPartial() {
        if (!stdProcessesTabBody || !stdProcessesPartialUrl) return;
        stdProcessesTabBody.innerHTML = '<div class="text-center py-5 text-muted">{{ __("Loading...") }}</div>';
        fetch(stdProcessesPartialUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }, credentials: 'same-origin' })
            .then(function(r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.text();
            })
            .then(function(html) {
                stdProcessesTabBody.innerHTML = html;
                stdProcessesTabBody.dataset.loaded = '1';
                stdProcessesTabBody.querySelectorAll('script').forEach(function(oldScript) {
                    var newScript = document.createElement('script');
                    Array.from(oldScript.attributes || []).forEach(function(attr) {
                        newScript.setAttribute(attr.name, attr.value);
                    });
                    newScript.textContent = oldScript.textContent;
                    oldScript.parentNode.replaceChild(newScript, oldScript);
                });
            })
            .catch(function(err) {
                stdProcessesTabBody.innerHTML = '<div class="alert alert-danger">{{ __("Failed to load.") }} (' + (err && err.message ? err.message : '') + ')</div>';
            });
    }

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
                            alert(data.message || '{{ __("Failed to delete.") }}');
                        }
                    })
                    .catch(function() { alert('{{ __("Failed to delete.") }}'); });
            });
        });
        var addVendorBtn = container.querySelector('#saveVendorButtonExtra');
        var addVendorForm = container.querySelector('#addVendorFormExtra');
        if (addVendorBtn && addVendorForm && typeof VendorHandler !== 'undefined' && ProcessesConfig.storeVendorUrl) {
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
    function openCreateLogCardModal(workorderId) {
        var iframe = document.getElementById('createLogCardIframe');
        var modal = document.getElementById('createLogCardModal');
        if (iframe && modal && workorderId) {
            iframe.src = createLogCardUrl.replace('__ID__', workorderId) + '?modal=1';
            var inst = bootstrap.Modal.getOrCreateInstance(modal);
            inst.show();
            modal.addEventListener('shown.bs.modal', function setZ() { modal.style.zIndex = '1080'; var b = document.querySelectorAll('.modal-backdrop'); if (b.length) b[b.length-1].style.zIndex = '1075'; }, { once: true });
        }
    }
    function openEditLogCardModal(logCardId) {
        var iframe = document.getElementById('editLogCardIframe');
        var modal = document.getElementById('editLogCardModal');
        if (iframe && modal && logCardId) {
            iframe.src = editLogCardUrl.replace('__ID__', logCardId) + '?modal=1';
            var inst = bootstrap.Modal.getOrCreateInstance(modal);
            inst.show();
            modal.addEventListener('shown.bs.modal', function setZ() { modal.style.zIndex = '1080'; var b = document.querySelectorAll('.modal-backdrop'); if (b.length) b[b.length-1].style.zIndex = '1075'; }, { once: true });
        }
    }

    function initAllPartsGroupForms(container) {
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
                url.searchParams.set('component_ids', Array.from(checkedBoxes).map(function(c){ return c.getAttribute('data-component-id'); }).join(','));
                url.searchParams.set('serial_numbers', Array.from(checkedBoxes).map(function(c){ return c.getAttribute('data-serial-number') || ''; }).join(','));
                url.searchParams.set('ipl_nums', Array.from(checkedBoxes).map(function(c){ return c.getAttribute('data-ipl-num') || ''; }).join(','));
                url.searchParams.set('part_numbers', Array.from(checkedBoxes).map(function(c){ return c.getAttribute('data-part-number') || ''; }).join(','));
            } else {
                url.searchParams.delete('component_ids'); url.searchParams.delete('serial_numbers');
                url.searchParams.delete('ipl_nums'); url.searchParams.delete('part_numbers');
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

    function initTravelerGroupHandlers() {
        if (!body) return;
        var wrapper = body.querySelector('.processes-modal-body');
        if (!wrapper) return;
        var tdrId = wrapper.dataset.tdrId;
        var groupUrl = wrapper.dataset.travelerGroupUrl;
        var ungroupUrl = wrapper.dataset.travelerUngroupUrl;
        var createBtn = body.querySelector('#btnCreateTraveler');
        var ungroupBtn = body.querySelector('#btnUngroupTraveler');
        function uniqueSelectedIds() {
            var ids = [];
            var seen = new Set();
            body.querySelectorAll('.traveler-select-cb:checked').forEach(function(cb) {
                var id = cb.getAttribute('data-tdr-process-id');
                if (id && !seen.has(id)) {
                    seen.add(id);
                    ids.push(parseInt(id, 10));
                }
            });
            return ids;
        }
        function syncCreateBtn() {
            if (!createBtn) return;
            createBtn.disabled = uniqueSelectedIds().length < 1;
        }
        body.querySelectorAll('.traveler-select-cb').forEach(function(cb) {
            cb.addEventListener('change', syncCreateBtn);
        });
        syncCreateBtn();
        if (createBtn && groupUrl) {
            createBtn.addEventListener('click', function() {
                var ids = uniqueSelectedIds();
                if (ids.length < 1) return;
                fetch(groupUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ process_ids: ids }),
                })
                    .then(function(r) { return r.json().then(function(data) { return { ok: r.ok, data: data }; }); })
                    .then(function(res) {
                        if (res.ok && res.data.success) {
                            loadProcessesAndBind(tdrId);
                            if (allPartsBody && allPartsBody.dataset.loaded) loadAllPartsProcesses();
                        } else {
                            var msg = (res.data && res.data.message) ? res.data.message : '{{ __("Request failed.") }}';
                            if (typeof showNotification === 'function') showNotification(msg, 'warning');
                            else alert(msg);
                        }
                    })
                    .catch(function() {
                        if (typeof showNotification === 'function') showNotification('{{ __("Request failed.") }}', 'error');
                        else alert('{{ __("Request failed.") }}');
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
                    },
                })
                    .then(function(r) { return r.json().then(function(data) { return { ok: r.ok, data: data }; }); })
                    .then(function(res) {
                        if (res.ok && res.data.success) {
                            loadProcessesAndBind(tdrId);
                            if (allPartsBody && allPartsBody.dataset.loaded) loadAllPartsProcesses();
                        } else {
                            var msg2 = (res.data && res.data.message) ? res.data.message : '{{ __("Request failed.") }}';
                            if (typeof showNotification === 'function') showNotification(msg2, 'warning');
                            else alert(msg2);
                        }
                    })
                    .catch(function() {
                        if (typeof showNotification === 'function') showNotification('{{ __("Request failed.") }}', 'error');
                        else alert('{{ __("Request failed.") }}');
                    });
            });
        }
        body.querySelectorAll('.travel-form-link').forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                var row = link.closest('tr');
                var vendorSel = row ? row.querySelector('.travel-vendor-select') : null;
                var repInp = row ? row.querySelector('.travel-repair-num') : null;
                if (!vendorSel || !vendorSel.value) {
                    var m = '{{ __("Please select a vendor.") }}';
                    if (typeof showNotification === 'function') showNotification(m, 'warning');
                    else alert(m);
                    return;
                }
                var u = new URL(link.getAttribute('href'), window.location.origin);
                u.searchParams.set('vendor_id', vendorSel.value);
                if (repInp && repInp.value.trim()) u.searchParams.set('repair_num', repInp.value.trim());
                window.open(u.toString(), '_blank');
            });
        });
    }

    function loadProcessesAndBind(tdrId) {
        if (!body) return;
        body.innerHTML = '<div class="text-center py-5 text-muted">{{ __("Loading...") }}</div>';
        if (woNum) woNum.textContent = '-';
        if (itemName) itemName.textContent = '-';
        if (itemIpl) itemIpl.textContent = '-';
        if (itemPn) itemPn.textContent = '-';
        if (itemSn) itemSn.textContent = '-';
        if (addProcessBtn) { addProcessBtn.dataset.tdrId = tdrId; addProcessBtn.disabled = true; }
        fetch(processesBodyUrl.replace('__ID__', tdrId), { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' } })
            .then(function(r) { return r.text(); })
            .then(function(html) {
                body.innerHTML = html;
                var wrapper = body.querySelector('.processes-modal-body');
                if (wrapper) {
                    if (woNum) woNum.textContent = wrapper.dataset.woNumber || '-';
                    if (itemName) itemName.textContent = wrapper.dataset.componentName || 'N/A';
                    if (itemIpl) itemIpl.textContent = wrapper.dataset.componentIpl || 'N/A';
                    if (itemPn) itemPn.textContent = wrapper.dataset.componentPn || 'N/A';
                    if (itemSn) itemSn.textContent = wrapper.dataset.serialNumber || 'N/A';
                }
                var processesWrapper = body.querySelector('.processes-modal-body');
                if (typeof Sortable !== 'undefined' && typeof SortableHandler !== 'undefined') {
                    if (!processesWrapper || processesWrapper.dataset.travelerBlock !== '1') {
                        SortableHandler.init(updateOrderUrl);
                    }
                }
                if (typeof VendorHandler !== 'undefined' && ProcessesConfig.storeVendorUrl) VendorHandler.init(ProcessesConfig.storeVendorUrl);
                bindProcessHandlers(wrapper);
                if (typeof FormLinkHandler !== 'undefined') FormLinkHandler.init(body);
                initTravelerGroupHandlers();
                if (addProcessBtn) {
                    addProcessBtn.disabled = false;
                    addProcessBtn.onclick = function() {
                        var tdrId = this.dataset.tdrId || (wrapper && wrapper.dataset.tdrId);
                        if (!tdrId) {
                            (typeof showNotification === 'function' ? (m) => showNotification(m, 'warning') : (window.NotificationHandler?.warning || alert))('{{ __("Please select a component first.") }}');
                            return;
                        }
                        openAddProcessModal(tdrId);
                    };
                }
            })
            .catch(function() {
                body.innerHTML = '<div class="alert alert-danger">{{ __("Failed to load processes.") }}</div>';
                if (addProcessBtn) addProcessBtn.disabled = false;
            });
    }

    function bindProcessHandlers(wrapper) {
        if (!body) return;
        body.querySelectorAll('.load-edit-process').forEach(function(b) {
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
        body.querySelectorAll('.ajax-delete-process').forEach(function(b) {
            b.addEventListener('click', function() {
                if (!confirm('{{ __("Are you sure you want to delete this process?") }}')) return;
                var tdrProcessId = this.dataset.tdrProcessId;
                var tdrId = this.dataset.tdrId || (wrapper && wrapper.dataset.tdrId);
                var process = this.dataset.process || '';
                var formData = new FormData();
                formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
                formData.append('_method', 'DELETE');
                formData.append('tdrId', tdrId);
                if (process) formData.append('process', process);
                fetch('{{ route("tdr-processes.destroy", ["tdr_process" => "__ID__"]) }}'.replace('__ID__', tdrProcessId), { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                    .then(function(r) { return r.json().catch(function() { return {}; }).then(function(data) { return { ok: r.ok, data: data }; }); })
                    .then(function(res) {
                        if (res.ok && res.data.success !== false) {
                            loadProcessesAndBind(tdrId);
                            if (allPartsBody && allPartsBody.dataset.loaded) loadAllPartsProcesses();
                        } else {
                            var dm = (res.data && res.data.message) ? res.data.message : '{{ __("Delete failed.") }}';
                            if (typeof showNotification === 'function') showNotification(dm, 'warning');
                            else alert(dm);
                        }
                    });
            });
        });
    }

    document.querySelectorAll('.open-part-processes-tab').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var tdrId = this.dataset.tdrId;
            if (!tdrId) return;
            if (tabLi) tabLi.classList.remove('d-none');
            loadProcessesAndBind(tdrId);
            if (tabBtn) { var tab = new bootstrap.Tab(tabBtn); tab.show(); }
        });
    });
    function openAddProcessModal(tdrId) {
        var iframe = document.getElementById('addPartProcessesIframe');
        var modal = document.getElementById('addPartProcessesModal');
        if (iframe && modal && tdrId) {
            iframe.src = createProcessesUrl.replace('__ID__', tdrId) + '?modal=1';
            var inst = bootstrap.Modal.getOrCreateInstance(modal);
            inst.show();
            modal.addEventListener('shown.bs.modal', function setAddModalZ() {
                modal.style.zIndex = '1080';
                var backdrops = document.querySelectorAll('.modal-backdrop');
                if (backdrops.length) backdrops[backdrops.length - 1].style.zIndex = '1075';
            }, { once: true });
        }
    }
    if (allPartsBody) {
        allPartsBody.addEventListener('click', function(e) {
            var addBtn = e.target.closest('.open-add-process-modal');
            if (addBtn && addBtn.dataset.tdrId) {
                e.preventDefault();
                openAddProcessModal(addBtn.dataset.tdrId);
                return;
            }
            var btn = e.target.closest('.open-part-processes-tab');
            if (!btn || !btn.dataset.tdrId) return;
            if (tabLi) tabLi.classList.remove('d-none');
            loadProcessesAndBind(btn.dataset.tdrId);
            if (tabBtn) { var tab = new bootstrap.Tab(tabBtn); tab.show(); }
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
                alert(m);
            }
        }
        function bushingToastErr(m) {
            if (typeof window.notifyError === 'function') {
                window.notifyError(m);
            } else {
                alert(m);
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
                alert('{{ __("Please select at least one component before submitting.") }}');
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
                alert('{{ __("Please enter quantity for all groups with selected components.") }}');
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
                        alert(result.json.message || (result.json.errors ? JSON.stringify(result.json.errors) : '') || '{{ __("Error creating bushings data.") }}');
                    }
                } else {
                    alert('{{ __("Failed to submit.") }} (HTTP ' + result.status + ')');
                }
            })
            .catch(function(err) {
                alert(err.name === 'AbortError' ? '{{ __("Request timed out. Please try again.") }}' : ('{{ __("Failed to submit.") }}' + (err.message ? ': ' + err.message : '')));
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
    if (logCardTabActions) {
        logCardTabActions.addEventListener('click', function(e) {
            var createBtn = e.target.closest('.open-create-log-card-modal');
            if (createBtn && createBtn.dataset.workorderId) {
                e.preventDefault();
                openCreateLogCardModal(createBtn.dataset.workorderId);
                return;
            }
            var editBtn = e.target.closest('.open-edit-log-card-modal');
            if (editBtn && editBtn.dataset.logCardId) {
                e.preventDefault();
                openEditLogCardModal(editBtn.dataset.logCardId);
                return;
            }
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

    var groupProcessFormsHeaderBtn = document.getElementById('groupProcessFormsHeaderBtn');
    tdrShowTabListEl?.addEventListener('shown.bs.tab', function(e) {
        var activeTabId = e?.target?.id || '';
        if (isPersistentTabId(activeTabId)) {
            try { localStorage.setItem(TAB_STORAGE_KEY, activeTabId); } catch (_) {}
        }

        var target = (e.target.getAttribute && e.target.getAttribute('data-bs-target')) || (e.target.getAttribute && e.target.getAttribute('href'));
        if (target && String(target).indexOf('content-part-processes') !== -1) {
            if (extraPartsTabActions) extraPartsTabActions.classList.add('d-none');
            if (logCardTabActions) logCardTabActions.classList.add('d-none');
            if (bushingTabActions) bushingTabActions.classList.add('d-none');
            if (transfersTabActions) transfersTabActions.classList.add('d-none');
            if (rmReportsTabBody) rmReportsTabBody.dataset.loaded = '';
            return;
        }
        if (target && String(target).indexOf('content-all-parts-processes') !== -1) {
            if (groupProcessFormsHeaderBtn) groupProcessFormsHeaderBtn.classList.remove('d-none');
            if (extraGroupFormsHeaderBtn) extraGroupFormsHeaderBtn.classList.add('d-none');
            if (extraPartsTabActions) extraPartsTabActions.classList.add('d-none');
            if (logCardTabActions) logCardTabActions.classList.add('d-none');
            if (bushingTabActions) bushingTabActions.classList.add('d-none');
            if (transfersTabActions) transfersTabActions.classList.add('d-none');
            if (allPartsBody && !allPartsBody.dataset.loaded) {
                allPartsBody.dataset.loaded = '1';
                loadAllPartsProcesses();
            }
        } else if (target && String(target).indexOf('content-extra-parts-processes') !== -1) {
            if (groupProcessFormsHeaderBtn) groupProcessFormsHeaderBtn.classList.add('d-none');
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
            if (groupProcessFormsHeaderBtn) groupProcessFormsHeaderBtn.classList.add('d-none');
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
            if (groupProcessFormsHeaderBtn) groupProcessFormsHeaderBtn.classList.add('d-none');
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
            if (groupProcessFormsHeaderBtn) groupProcessFormsHeaderBtn.classList.add('d-none');
            if (extraGroupFormsHeaderBtn) extraGroupFormsHeaderBtn.classList.add('d-none');
            if (extraPartsTabActions) extraPartsTabActions.classList.add('d-none');
            if (logCardTabActions) logCardTabActions.classList.add('d-none');
            if (bushingTabActions) bushingTabActions.classList.add('d-none');
            if (transfersTabActions) transfersTabActions.classList.add('d-none');
            if (rmReportsTabBody && !rmReportsTabBody.dataset.loaded) {
                rmReportsTabBody.dataset.loaded = '1';
                loadRmReportsPartial();
            }
        } else if (target && String(target).indexOf('content-std-processes') !== -1) {
            if (groupProcessFormsHeaderBtn) groupProcessFormsHeaderBtn.classList.add('d-none');
            if (extraGroupFormsHeaderBtn) extraGroupFormsHeaderBtn.classList.add('d-none');
            if (extraPartsTabActions) extraPartsTabActions.classList.add('d-none');
            if (logCardTabActions) logCardTabActions.classList.add('d-none');
            if (bushingTabActions) bushingTabActions.classList.add('d-none');
            if (transfersTabActions) transfersTabActions.classList.add('d-none');
            if (stdProcessesTabBody && !stdProcessesTabBody.dataset.loaded) {
                stdProcessesTabBody.dataset.loaded = '1';
                loadStdProcessesPartial();
            }
        } else if (target && String(target).indexOf('content-transfers') !== -1) {
            if (groupProcessFormsHeaderBtn) groupProcessFormsHeaderBtn.classList.add('d-none');
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
            if (groupProcessFormsHeaderBtn) groupProcessFormsHeaderBtn.classList.add('d-none');
            if (extraGroupFormsHeaderBtn) extraGroupFormsHeaderBtn.classList.add('d-none');
            if (extraPartsTabActions) extraPartsTabActions.classList.add('d-none');
            if (logCardTabActions) logCardTabActions.classList.add('d-none');
            if (bushingTabActions) bushingTabActions.classList.add('d-none');
            if (transfersTabActions) transfersTabActions.classList.add('d-none');
        } else {
            if (groupProcessFormsHeaderBtn) groupProcessFormsHeaderBtn.classList.add('d-none');
            if (extraGroupFormsHeaderBtn) extraGroupFormsHeaderBtn.classList.add('d-none');
            if (extraPartsTabActions) extraPartsTabActions.classList.add('d-none');
            if (logCardTabActions) logCardTabActions.classList.add('d-none');
            if (bushingTabActions) bushingTabActions.classList.add('d-none');
            if (transfersTabActions) transfersTabActions.classList.add('d-none');
        }
        if (target && String(target).indexOf('content-part-processes') === -1) {
            if (tabLi) tabLi.classList.add('d-none');
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
                                alert(data.message || (data.errors ? JSON.stringify(data.errors) : '') || '{{ __("Error.") }}');
                            }
                        })
                        .catch(function() {
                            alert('{{ __("Failed to submit.") }}');
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
            var m = bootstrap.Modal.getInstance(document.getElementById('addPartProcessesModal'));
            if (m) m.hide();
            var ifr = document.getElementById('addPartProcessesIframe');
            if (ifr) ifr.src = 'about:blank';
            loadProcessesAndBind(e.data.tdrId);
            if (allPartsBody && allPartsBody.dataset.loaded) loadAllPartsProcesses();
        } else if (e.data && e.data.type === 'createProcessCancel') {
            var m = bootstrap.Modal.getInstance(document.getElementById('addPartProcessesModal'));
            if (m) m.hide();
            var ifr = document.getElementById('addPartProcessesIframe');
            if (ifr) ifr.src = 'about:blank';
        } else if (e.data && e.data.type === 'editProcessSuccess' && e.data.tdrId) {
            var m = bootstrap.Modal.getInstance(document.getElementById('editTdrProcessModal'));
            if (m) m.hide();
            var ifr = document.getElementById('editTdrProcessIframe');
            if (ifr) ifr.src = 'about:blank';
            loadProcessesAndBind(e.data.tdrId);
            if (allPartsBody && allPartsBody.dataset.loaded) loadAllPartsProcesses();
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
        } else if (e.data && e.data.type === 'createLogCardSuccess') {
            var m = bootstrap.Modal.getInstance(document.getElementById('createLogCardModal'));
            if (m) m.hide();
            var ifr = document.getElementById('createLogCardIframe');
            if (ifr) ifr.src = 'about:blank';
            var logCardId = e.data.logCardId;
            if (logCardTabActions && logCardId) {
                logCardTabActions.innerHTML = '<button type="button" class="btn btn-outline-primary btn-sm open-edit-log-card-modal" data-log-card-id="' + logCardId + '"><i class="fas fa-edit"></i> {{ __("Edit Log Card") }}</button>';
            }
            if (logCardTabBody) loadLogCardPartial();
        } else if (e.data && e.data.type === 'createLogCardCancel') {
            var m = bootstrap.Modal.getInstance(document.getElementById('createLogCardModal'));
            if (m) m.hide();
            var ifr = document.getElementById('createLogCardIframe');
            if (ifr) ifr.src = 'about:blank';
        } else if (e.data && e.data.type === 'editLogCardSuccess') {
            var m = bootstrap.Modal.getInstance(document.getElementById('editLogCardModal'));
            if (m) m.hide();
            var ifr = document.getElementById('editLogCardIframe');
            if (ifr) ifr.src = 'about:blank';
            if (logCardTabBody) loadLogCardPartial();
        } else if (e.data && e.data.type === 'editLogCardCancel') {
            var m = bootstrap.Modal.getInstance(document.getElementById('editLogCardModal'));
            if (m) m.hide();
            var ifr = document.getElementById('editLogCardIframe');
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
        }
    });
    document.getElementById('addPartProcessesModal')?.addEventListener('hidden.bs.modal', function() {
        var ifr = document.getElementById('addPartProcessesIframe');
        if (ifr) ifr.src = 'about:blank';
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
                                            window.location.reload();
                                        } else {
                                            if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = origText; }
                                        }
                                    })
                                    .catch(function() {
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
                        const tdrIdInput = document.querySelector(`input[name="conditions[${conditionId}][tdr_id]"]`);
                        conditionsData[conditionId] = {
                            selected: true,
                            notes: notesInput ? notesInput.value : '',
                            tdr_id: tdrIdInput ? tdrIdInput.value : null
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
                    (typeof showNotification === 'function' ? (m) => showNotification(m, 'error') : (window.NotificationHandler?.error || alert))('{{ __("An error occurred while saving.") }}');
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
                        (typeof showNotification === 'function' ? (m) => showNotification(m, 'error') : (window.NotificationHandler?.error || alert))(data.message || '{{ __("An error occurred while saving.") }}');
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
                if (!confirm(`{{ __("Are you sure you want to delete condition") }} "${conditionName}"?`)) return;
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> {{ __("Deleting...") }}';
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
                        (typeof showNotification === 'function' ? (m) => showNotification(m, 'error') : (window.NotificationHandler?.error || alert))(data.message || '{{ __("An error occurred while deleting.") }}');
                        this.disabled = false;
                        this.innerHTML = '<i class="fas fa-trash"></i> {{ __("Delete") }}';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    (typeof showNotification === 'function' ? (m) => showNotification(m, 'error') : (window.NotificationHandler?.error || alert))('{{ __("An error occurred while deleting.") }}');
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-trash"></i> {{ __("Delete") }}';
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
                        (typeof showNotification === 'function' ? (m) => showNotification(m, 'error') : (window.NotificationHandler?.error || alert))(data.message || '{{ __("An error occurred while saving.") }}');
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '{{ __("Save Condition") }}';
                    }
                })
                .catch(() => {
                    (typeof showNotification === 'function' ? (m) => showNotification(m, 'error') : (window.NotificationHandler?.error || alert))('{{ __("An error occurred while saving.") }}');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '{{ __("Save Condition") }}';
                });
            });
        }
    });
</script>

