<script>
    window.currentWorkorderId = {{ $current_wo->id }};
    window.show2Url = '{{ route("tdrs.show2", ["id" => $current_wo->id]) }}';
</script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script src="{{ asset('js/tdr-processes/sortable-handler.js') }}"></script>
<script src="{{ asset('js/tdr-processes/vendor-handler.js') }}"></script>
<script src="{{ asset('js/tdr-processes/edit-process/edit-process.js') }}"></script>
<script>
    window.ProcessesConfig = window.ProcessesConfig || {};
    ProcessesConfig.updateOrderUrl = '{{ route("tdr-processes.update-order") }}';
    ProcessesConfig.storeVendorUrl = '{{ route("vendors.store") }}';
</script>
@include('admin.tdrs.partials.component-inspection-scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
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

    function loadAllPartsProcesses() {
        if (!allPartsBody) return;
        allPartsBody.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>';
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

    function loadProcessesAndBind(tdrId) {
        if (!body) return;
        body.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>';
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
                if (typeof Sortable !== 'undefined' && typeof SortableHandler !== 'undefined') SortableHandler.init(updateOrderUrl);
                if (typeof VendorHandler !== 'undefined' && ProcessesConfig.storeVendorUrl) VendorHandler.init(ProcessesConfig.storeVendorUrl);
                bindProcessHandlers(wrapper);
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
                    .then(function(r) { return r.json().catch(function() { return {}; }); })
                    .then(function() {
                        loadProcessesAndBind(tdrId);
                        if (allPartsBody && allPartsBody.dataset.loaded) loadAllPartsProcesses();
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

    var groupProcessFormsHeaderBtn = document.getElementById('groupProcessFormsHeaderBtn');
    document.getElementById('show2TabList')?.addEventListener('shown.bs.tab', function(e) {
        var target = (e.target.getAttribute && e.target.getAttribute('data-bs-target')) || (e.target.getAttribute && e.target.getAttribute('href'));
        if (target && String(target).indexOf('content-part-processes') !== -1) return;
        if (target && String(target).indexOf('content-all-parts-processes') !== -1) {
            if (groupProcessFormsHeaderBtn) groupProcessFormsHeaderBtn.classList.remove('d-none');
            if (allPartsBody && !allPartsBody.dataset.loaded) {
                allPartsBody.dataset.loaded = '1';
                loadAllPartsProcesses();
            }
        } else {
            if (groupProcessFormsHeaderBtn) groupProcessFormsHeaderBtn.classList.add('d-none');
        }
        if (target && String(target).indexOf('content-part-processes') === -1) {
            if (tabLi) tabLi.classList.add('d-none');
            if (body) body.innerHTML = '<div class="text-center py-5 text-muted">{{ __("Click a component processes button to load.") }}</div>';
        }
    });

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
    if (editTdrModal) {
        editTdrModal.addEventListener('show.bs.modal', function(e) {
            var btn = e.relatedTarget;
            var body = document.getElementById('editTdrModalBody');
            if (!body) return;
            body.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">{{ __("Loading...") }}</span></div></div>';
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
                    if (response.redirected && response.url.includes('/show2/')) {
                        window.top.location.href = response.url;
                        return null;
                    }
                    if (response.redirected && window.show2Url) {
                        window.top.location.href = window.show2Url;
                        return null;
                    }
                    return response.json();
                })
                .then(data => {
                    if (!data) return;
                    if (data.success) {
                        const redirectUrl = data.redirect || window.show2Url;
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
                        window.location.replace(window.show2Url);
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

