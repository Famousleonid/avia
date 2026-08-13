@php
    $partGroupPayload = $partGroups->map(function ($group) use ($cmm) {
        return [
            'id' => (int) $group->id,
            'code' => $group->code,
            'name' => $group->name,
            'type' => $group->type,
            'behavior' => $group->behavior,
            'status' => $group->status,
            'applies_to' => $group->applies_to ?: \App\Models\ManualPartGroup::validScopes(),
            'manual_service_bulletin_id' => $group->manual_service_bulletin_id,
            'notes' => $group->notes,
            'options' => $group->options->map(fn ($option) => [
                'id' => (int) $option->id,
                'component_id' => $option->component_id ? (int) $option->component_id : null,
                'part_number' => $option->part_number,
                'ipl_num' => $option->ipl_num,
                'is_default' => (bool) $option->is_default,
                'coverages' => $option->coverages->map(fn ($coverage) => [
                    'component_id' => (int) $coverage->component_id,
                    'qty' => (int) $coverage->qty,
                    'part_number' => $coverage->component?->part_number,
                    'ipl_num' => $coverage->component?->ipl_num,
                    'name' => $coverage->component?->name,
                ])->values()->all(),
            ])->values()->all(),
            'update_url' => route('manuals.part-groups.update', ['manual' => $cmm, 'partGroup' => $group]),
            'delete_url' => route('manuals.part-groups.destroy', ['manual' => $cmm, 'partGroup' => $group]),
        ];
    })->values();
@endphp

<div class="modal fade" id="manualPartGroupsModal" tabindex="-1" aria-labelledby="manualPartGroupsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="manualPartGroupsModalLabel">{{ __('Part Groups') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-lg-4 border-end">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong>{{ __('Existing groups') }}</strong>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="manual-part-group-new">{{ __('New from selected') }}</button>
                        </div>
                        <div class="list-group" id="manual-part-group-list"></div>
                        <div class="small text-muted mt-3">
                            {{ __('Draft groups do not affect PRL or STD forms. Activate a group only after its alternatives or composition have been checked.') }}
                        </div>
                    </div>
                    <div class="col-lg-8">
                        <form id="manual-part-group-form" data-store-url="{{ route('manuals.part-groups.store', ['manual' => $cmm]) }}">
                            <input type="hidden" id="manual-part-group-id">
                            <div class="row g-2">
                                <div class="col-md-7">
                                    <label class="form-label" for="manual-part-group-name">{{ __('Group name') }}</label>
                                    <input class="form-control" id="manual-part-group-name" maxlength="255" required>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label" for="manual-part-group-type">{{ __('Group type') }}</label>
                                    <select class="form-select" id="manual-part-group-type" required>
                                        <option value="alternative_pn">{{ __('Alternative P/N') }}</option>
                                        <option value="oversize">{{ __('Original / Oversize') }}</option>
                                        <option value="assy">{{ __('ASSY') }}</option>
                                        <option value="sb_kit">{{ __('Service Bulletin KIT') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="manual-part-group-status">{{ __('Status') }}</label>
                                    <select class="form-select" id="manual-part-group-status">
                                        <option value="draft">{{ __('Draft — no cross-outs') }}</option>
                                        <option value="active">{{ __('Active') }}</option>
                                        <option value="inactive">{{ __('Inactive') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-8" id="manual-part-group-order-fields">
                                    <div class="row g-2">
                                        <div class="col-7">
                                            <label class="form-label" for="manual-part-group-order-pn">{{ __('Order P/N') }}</label>
                                            <input class="form-control" id="manual-part-group-order-pn" maxlength="100">
                                        </div>
                                        <div class="col-5">
                                            <label class="form-label" for="manual-part-group-order-ipl">{{ __('Order IPL') }}</label>
                                            <input class="form-control" id="manual-part-group-order-ipl" maxlength="50">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 d-none" id="manual-part-group-sb-wrap">
                                    <label class="form-label" for="manual-part-group-sb">{{ __('Service Bulletin') }}</label>
                                    <select class="form-select" id="manual-part-group-sb">
                                        <option value="">{{ __('Select Service Bulletin') }}</option>
                                        @foreach($serviceBulletins as $bulletin)
                                            <option value="{{ $bulletin->id }}">{{ $bulletin->oem_service_bulletin_no ?: $bulletin->ac_mfg_service_bulletin_no ?: '#'.$bulletin->id }} — {{ $bulletin->description }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label d-block">{{ __('Cross out in') }}</label>
                                    @foreach(['prl' => 'PRL', 'ndt' => 'NDT STD', 'cad' => 'CAD STD', 'stress' => 'Stress STD', 'paint' => 'Paint STD'] as $scope => $label)
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input manual-part-group-scope" type="checkbox" value="{{ $scope }}" id="manual-part-group-scope-{{ $scope }}" checked>
                                            <label class="form-check-label" for="manual-part-group-scope-{{ $scope }}">{{ $label }}</label>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="col-12">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-label mb-0">{{ __('Members / alternatives') }}</label>
                                        <span class="small text-muted" id="manual-part-group-member-help"></span>
                                    </div>
                                    <div class="table-responsive border rounded" style="max-height: 280px; overflow:auto">
                                        <table class="table table-sm mb-0" style="min-width: 620px">
                                            <thead class="position-sticky top-0 bg-body"><tr><th>IPL</th><th>P/N</th><th>{{ __('Name') }}</th><th style="width:90px">Qty</th><th style="width:80px">Default</th><th style="width:50px"></th></tr></thead>
                                            <tbody id="manual-part-group-members"></tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="manual-part-group-notes">{{ __('Notes') }}</label>
                                    <textarea class="form-control" id="manual-part-group-notes" rows="2" maxlength="4000"></textarea>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between mt-3">
                                <button type="button" class="btn btn-outline-danger d-none" id="manual-part-group-delete">{{ __('Delete') }}</button>
                                <button type="submit" class="btn btn-primary ms-auto" id="manual-part-group-save">{{ __('Save group') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('manualPartGroupsModal');
    const form = document.getElementById('manual-part-group-form');
    const partsTable = document.getElementById('manualPartsTable');
    if (!modal || !form || !partsTable) return;

    let groups = @json($partGroupPayload);
    let members = [];
    const list = document.getElementById('manual-part-group-list');
    const type = document.getElementById('manual-part-group-type');
    const orderFields = document.getElementById('manual-part-group-order-fields');
    const sbWrap = document.getElementById('manual-part-group-sb-wrap');
    const tbody = document.getElementById('manual-part-group-members');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    function escapeHtml(value) {
        const span = document.createElement('span');
        span.textContent = value == null ? '' : String(value);
        return span.innerHTML;
    }

    function selectedTableMembers() {
        return Array.from(partsTable.querySelectorAll('.manual-part-select:checked')).map(function (box) {
            const row = box.closest('tr');
            const cells = row?.querySelectorAll('td') || [];
            return { component_id: Number(box.dataset.componentId), ipl_num: cells[1]?.textContent.trim() || '', part_number: cells[2]?.textContent.trim() || '', name: cells[3]?.textContent.trim() || '', qty: 1, is_default: false };
        });
    }

    function isBundle() { return ['assy', 'sb_kit'].includes(type.value); }

    function refreshType() {
        orderFields.classList.toggle('d-none', !isBundle());
        sbWrap.classList.toggle('d-none', type.value !== 'sb_kit');
        document.getElementById('manual-part-group-member-help').textContent = isBundle()
            ? '{{ __('Qty is the number included in one ASSY/KIT.') }}'
            : '{{ __('Choose the default ordering option.') }}';
        renderMembers();
    }

    function renderMembers() {
        tbody.innerHTML = members.map(function (member, index) {
            return '<tr data-index="' + index + '"><td>' + escapeHtml(member.ipl_num) + '</td><td>' + escapeHtml(member.part_number) + '</td><td>' + escapeHtml(member.name) + '</td>' +
                '<td><input type="number" min="1" max="9999" class="form-control form-control-sm part-group-member-qty" value="' + Number(member.qty || 1) + '" ' + (!isBundle() ? 'disabled' : '') + '></td>' +
                '<td class="text-center"><input type="radio" name="part-group-default" class="form-check-input part-group-member-default" ' + (member.is_default ? 'checked' : '') + ' ' + (isBundle() ? 'disabled' : '') + '></td>' +
                '<td><button type="button" class="btn btn-sm btn-outline-danger part-group-member-remove" aria-label="Remove"><i class="bi bi-x"></i></button></td></tr>';
        }).join('');
    }

    function resetForm(useSelection) {
        form.reset();
        document.getElementById('manual-part-group-id').value = '';
        document.getElementById('manual-part-group-status').value = 'draft';
        document.querySelectorAll('.manual-part-group-scope').forEach(function (scope) { scope.checked = true; });
        members = useSelection ? selectedTableMembers() : [];
        if (members[0]) members[0].is_default = true;
        document.getElementById('manual-part-group-delete').classList.add('d-none');
        refreshType();
    }

    function editGroup(group) {
        resetForm(false);
        document.getElementById('manual-part-group-id').value = group.id;
        document.getElementById('manual-part-group-name').value = group.name || '';
        type.value = group.type;
        document.getElementById('manual-part-group-status').value = group.status;
        document.getElementById('manual-part-group-notes').value = group.notes || '';
        document.getElementById('manual-part-group-sb').value = group.manual_service_bulletin_id || '';
        const option = group.options[0] || {};
        document.getElementById('manual-part-group-order-pn').value = isBundle() ? (option.part_number || '') : '';
        document.getElementById('manual-part-group-order-ipl').value = isBundle() ? (option.ipl_num || '') : '';
        members = isBundle() ? (option.coverages || []) : group.options.map(function (item) {
            const row = partsTable.querySelector('.manual-part-select[data-component-id="' + item.component_id + '"]')?.closest('tr');
            const cells = row?.querySelectorAll('td') || [];
            return { component_id: item.component_id, ipl_num: item.ipl_num || '', part_number: item.part_number || '', name: cells[3]?.textContent.trim() || '', qty: 1, is_default: item.is_default };
        });
        document.querySelectorAll('.manual-part-group-scope').forEach(function (scope) { scope.checked = (group.applies_to || []).includes(scope.value); });
        document.getElementById('manual-part-group-delete').classList.remove('d-none');
        refreshType();
    }

    function renderList() {
        list.innerHTML = groups.length ? groups.map(function (group) {
            const badge = group.status === 'active' ? 'success' : (group.status === 'draft' ? 'warning' : 'secondary');
            return '<button type="button" class="list-group-item list-group-item-action part-group-list-item" data-id="' + group.id + '"><div class="d-flex justify-content-between"><strong>' + escapeHtml(group.name) + '</strong><span class="badge text-bg-' + badge + '">' + escapeHtml(group.status) + '</span></div><div class="small text-muted">' + escapeHtml(group.code) + ' · ' + escapeHtml(group.type) + '</div></button>';
        }).join('') : '<div class="text-muted small">{{ __('No groups yet.') }}</div>';
    }

    function payload() {
        const componentIds = members.map(function (member) { return Number(member.component_id); });
        const memberQty = {};
        tbody.querySelectorAll('tr').forEach(function (row) {
            const member = members[Number(row.dataset.index)];
            memberQty[member.component_id] = Number(row.querySelector('.part-group-member-qty')?.value || 1);
            member.is_default = Boolean(row.querySelector('.part-group-member-default')?.checked);
        });
        return {
            name: document.getElementById('manual-part-group-name').value.trim(), type: type.value,
            status: document.getElementById('manual-part-group-status').value,
            applies_to: Array.from(document.querySelectorAll('.manual-part-group-scope:checked')).map(function (scope) { return scope.value; }),
            manual_service_bulletin_id: document.getElementById('manual-part-group-sb').value || null,
            notes: document.getElementById('manual-part-group-notes').value.trim() || null,
            component_ids: componentIds,
            default_component_id: members.find(function (member) { return member.is_default; })?.component_id || componentIds[0] || null,
            order_part_number: document.getElementById('manual-part-group-order-pn').value.trim() || null,
            order_ipl_num: document.getElementById('manual-part-group-order-ipl').value.trim() || null,
            member_qty: memberQty,
        };
    }

    async function request(url, method, body) {
        const response = await fetch(url, { method: method, credentials: 'same-origin', headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' }, body: body ? JSON.stringify(body) : null });
        const data = await response.json().catch(function () { return {}; });
        if (!response.ok || !data.success) {
            const first = data.errors ? Object.values(data.errors).flat().find(Boolean) : null;
            throw new Error(first || data.message || '{{ __('Could not save the part group.') }}');
        }
        return data;
    }

    document.getElementById('manual-part-groups-open')?.addEventListener('click', function () { resetForm(true); });
    document.getElementById('manual-part-group-new').addEventListener('click', function () { resetForm(true); });
    type.addEventListener('change', refreshType);
    tbody.addEventListener('click', function (event) {
        const button = event.target.closest('.part-group-member-remove'); if (!button) return;
        members.splice(Number(button.closest('tr').dataset.index), 1); renderMembers();
    });
    list.addEventListener('click', function (event) { const item = event.target.closest('.part-group-list-item'); if (item) editGroup(groups.find(function (group) { return group.id === Number(item.dataset.id); })); });
    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        const id = Number(document.getElementById('manual-part-group-id').value || 0);
        const group = groups.find(function (item) { return item.id === id; });
        const save = document.getElementById('manual-part-group-save'); save.disabled = true;
        try {
            const data = await request(group?.update_url || form.dataset.storeUrl, group ? 'PUT' : 'POST', payload());
            if (group) groups = groups.map(function (item) { return item.id === group.id ? Object.assign(data.group, { update_url: group.update_url, delete_url: group.delete_url }) : item; });
            else window.location.reload();
            renderList(); showNotification(data.message, 'success');
        } catch (error) { showNotification(error.message, 'error'); } finally { save.disabled = false; }
    });
    document.getElementById('manual-part-group-delete').addEventListener('click', async function () {
        const group = groups.find(function (item) { return item.id === Number(document.getElementById('manual-part-group-id').value); });
        if (!group) return;
        if (typeof window.confirmDialog !== 'function') { showNotification('{{ __('Confirmation dialog is unavailable. Nothing was deleted.') }}', 'error'); return; }
        const confirmed = await window.confirmDialog({ title: '{{ __('Delete part group?') }}', message: group.name, okText: '{{ __('Delete') }}', cancelText: '{{ __('Cancel') }}' });
        if (!confirmed) return;
        try { await request(group.delete_url, 'DELETE'); groups = groups.filter(function (item) { return item.id !== group.id; }); renderList(); resetForm(false); showNotification('{{ __('Part group deleted.') }}', 'success'); } catch (error) { showNotification(error.message, 'error'); }
    });

    renderList(); resetForm(true);
});
</script>
