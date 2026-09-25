@php
    $partGroupPayload = $partGroups->map(function ($group) use ($cmm) {
        return [
            'id' => (int) $group->id,
            'code' => $group->code,
            'name' => $group->name,
            'type' => $group->type,
            'behavior' => $group->behavior,
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
                    'component_id' => $coverage->component_id ? (int) $coverage->component_id : null,
                    'covered_option_id' => $coverage->covered_manual_part_group_option_id
                        ? (int) $coverage->covered_manual_part_group_option_id
                        : null,
                    'qty' => (int) $coverage->qty,
                    'choice_slot' => $coverage->choice_slot,
                    'applies_to' => $coverage->applies_to,
                    'part_number' => $coverage->component?->part_number,
                    'ipl_num' => $coverage->component?->ipl_num,
                    'name' => $coverage->component?->name,
                    'covered_option' => $coverage->coveredOption ? [
                        'part_number' => $coverage->coveredOption->part_number,
                        'ipl_num' => $coverage->coveredOption->ipl_num,
                        'group_name' => $coverage->coveredOption->group?->name,
                        'component_ids' => $coverage->coveredOption->coverages
                            ->pluck('component_id')
                            ->filter()
                            ->map(fn ($componentId) => (int) $componentId)
                            ->values()
                            ->all(),
                    ] : null,
                ])->values()->all(),
            ])->values()->all(),
            'update_url' => route('manuals.part-groups.update', ['manual' => $cmm, 'partGroup' => $group]),
            'delete_url' => route('manuals.part-groups.destroy', ['manual' => $cmm, 'partGroup' => $group]),
        ];
    })->values();
    $partGroupCatalog = $parts->map(fn ($part) => [
        'component_id' => (int) $part->id,
        'ipl_num' => (string) $part->ipl_num,
        'part_number' => (string) $part->part_number,
        'name' => (string) $part->name,
    ])->values();
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
                    <div class="col-lg-4 border-end" id="manual-part-group-sidebar">
                        <div class="list-group" id="manual-part-group-list"></div>
                    </div>
                    <div class="col-lg-8" id="manual-part-group-editor">
                        <form id="manual-part-group-form" data-no-spinner data-store-url="{{ route('manuals.part-groups.store', ['manual' => $cmm]) }}">
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
                                        <option value="oversize">{{ __('Bushing: Original / Oversize') }}</option>
                                        <option value="assy">{{ __('ASSY') }}</option>
                                        <option value="kit">{{ __('KIT') }}</option>
                                    </select>
                                </div>
                                <div class="col-12" id="manual-part-group-order-fields">
                                    <div class="row g-2">
                                        <div class="col-7">
                                            <label class="form-label" for="manual-part-group-order-pn">{{ __('New KIT P/N') }}</label>
                                            <input class="form-control" id="manual-part-group-order-pn" maxlength="100">
                                        </div>
                                        <div class="col-5">
                                            <label class="form-label" for="manual-part-group-order-ipl">{{ __('Order IPL') }}</label>
                                            <input class="form-control" id="manual-part-group-order-ipl" maxlength="50">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 d-none" id="manual-part-group-sb-wrap">
                                    <label class="form-label" for="manual-part-group-sb">{{ __('Service Bulletin (optional)') }}</label>
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
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-1">
                                        <label class="form-label mb-0">{{ __('Members / alternatives') }}</label>
                                        <button type="button" class="btn btn-sm btn-outline-info" id="manual-part-group-add" aria-expanded="false" aria-controls="manual-part-group-picker"><i class="bi bi-plus-lg" aria-hidden="true"></i> {{ __('Add') }}</button>
                                    </div>
                                    <div class="mb-2">
                                        <span class="small text-muted" id="manual-part-group-member-help"></span>
                                    </div>
                                    <div class="border rounded p-2 mb-2 d-none" id="manual-part-group-picker">
                                        <label class="form-label small" for="manual-part-group-search">{{ __('Parts in this CMM — search Item / IPL, P/N or name') }}</label>
                                        <input type="search" class="form-control form-control-sm mb-2" id="manual-part-group-search" autocomplete="off" placeholder="{{ __('Search all Parts') }}">
                                        <div class="table-responsive border rounded" style="max-height:220px; overflow:auto">
                                            <table class="table table-sm mb-0" style="min-width:480px">
                                                <thead class="position-sticky top-0 bg-body"><tr><th aria-label="{{ __('Select') }}"></th><th>Item / IPL</th><th>P/N</th><th>{{ __('Name') }}</th><th>{{ __('Status') }}</th></tr></thead>
                                                <tbody id="manual-part-group-picker-rows"></tbody>
                                            </table>
                                        </div>
                                        <div class="d-flex flex-wrap gap-2 align-items-center mt-2">
                                            <span class="small text-muted me-auto" id="manual-part-group-picker-count" aria-live="polite"></span>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" id="manual-part-group-picker-cancel">{{ __('Cancel') }}</button>
                                            <button type="button" class="btn btn-sm btn-info" id="manual-part-group-picker-apply" disabled>{{ __('Add selected') }}</button>
                                        </div>
                                        <div class="small text-muted mt-1">{{ __('Save group to apply changes.') }}</div>
                                    </div>
                                    <div class="table-responsive border rounded" style="max-height: 280px; overflow:auto">
                                        <table class="table table-sm mb-0" style="min-width: 620px">
                                            <thead class="position-sticky top-0 bg-body"><tr><th>IPL</th><th>P/N</th><th>{{ __('Name') }}</th><th style="width:90px">Qty</th><th style="width:90px" id="manual-part-group-default-heading">{{ __('Default') }}</th><th style="width:50px"></th></tr></thead>
                                            <tbody id="manual-part-group-members"></tbody>
                                        </table>
                                    </div>
                                    <div class="alert alert-warning py-2 px-3 mt-2 mb-0 d-none" id="manual-part-group-bushing-warning" role="alert" aria-live="polite">
                                        {{ __('Add the complete Bushing Original/Oversize group.') }}
                                    </div>
                                </div>
                                <div class="col-12 d-none" id="manual-part-group-assy-wrap">
                                    <details class="border rounded p-2" id="manual-part-group-included-details">
                                        <summary class="fw-semibold">{{ __('Included groups') }} (<span id="manual-part-group-included-count">0</span>)</summary>
                                        <div class="small text-muted my-2" id="manual-part-group-nested-help">{{ __('Only directly included groups are listed. Expand a group to see its contents.') }}</div>
                                        <div id="manual-part-group-assy-members"></div>
                                        <div id="manual-part-group-conditional-members" class="small text-info mt-2 d-none"></div>
                                        <button type="button" class="btn btn-sm btn-outline-info mt-2" id="manual-part-group-nested-add" aria-expanded="false" aria-controls="manual-part-group-nested-picker">+ {{ __('Add group') }}</button>
                                        <div class="border rounded p-2 mt-2 d-none" id="manual-part-group-nested-picker">
                                            <label class="form-label small" for="manual-part-group-nested-search">{{ __('Find a group to include') }}</label>
                                            <input type="search" class="form-control form-control-sm mb-2" id="manual-part-group-nested-search" autocomplete="off">
                                            <div id="manual-part-group-nested-results" style="max-height:240px;overflow:auto"></div>
                                            <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="manual-part-group-nested-cancel">{{ __('Close') }}</button>
                                        </div>
                                    </details>
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

<style>
    #manualPartGroupsModal .row > div { min-width: 0; }
    #manualPartGroupsModal details > summary { cursor: pointer; overflow-wrap: anywhere; }
    #manualPartGroupsModal .part-group-nested-row { display: flex; align-items: flex-start; gap: .5rem; padding: .5rem 0; }
    #manualPartGroupsModal .part-group-nested-row > details { flex: 1; min-width: 0; }
    #manualPartGroupsModal .part-group-nested-actions { display: flex; align-items: center; gap: .35rem; flex-shrink: 0; }
    #manualPartGroupsModal .part-group-nested-actions input { width: 65px; }
    #manualPartGroupsModal .part-group-composition { margin: .5rem 0 0; padding-left: 1.2rem; overflow-wrap: anywhere; }
    #manualPartGroupsModal .part-group-composition li { margin: .35rem 0; }
    #manualPartGroupsModal .part-group-nested-result { display: flex; align-items: center; gap: .5rem; padding: .5rem; }
    #manualPartGroupsModal .part-group-nested-result > span { flex: 1; min-width: 0; overflow-wrap: anywhere; }
    @media (max-width: 575px) {
        #manualPartGroupsModal .part-group-nested-row { flex-wrap: wrap; }
        #manualPartGroupsModal .part-group-nested-row > details { flex-basis: 100%; }
    }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('manualPartGroupsModal');
    const form = document.getElementById('manual-part-group-form');
    const partsTable = document.getElementById('manualPartsTable');
    if (!modal || !form || !partsTable) return;

    let groups = @json($partGroupPayload);
    const partCatalog = @json($partGroupCatalog);
    const picker = document.getElementById('manual-part-group-picker');
    const pickerRows = document.getElementById('manual-part-group-picker-rows');
    const pickerSearch = document.getElementById('manual-part-group-search');
    const pickerApply = document.getElementById('manual-part-group-picker-apply');
    const pickerToggle = document.getElementById('manual-part-group-add');
    const pickedParts = new Set();
    let members = [];
    const list = document.getElementById('manual-part-group-list');
    const type = document.getElementById('manual-part-group-type');
    const orderFields = document.getElementById('manual-part-group-order-fields');
    const sbWrap = document.getElementById('manual-part-group-sb-wrap');
    const assyWrap = document.getElementById('manual-part-group-assy-wrap');
    const assyMembers = document.getElementById('manual-part-group-assy-members');
    const conditionalMembers = document.getElementById('manual-part-group-conditional-members');
    const tbody = document.getElementById('manual-part-group-members');
    const nameInput = document.getElementById('manual-part-group-name');
    const bushingWarning = document.getElementById('manual-part-group-bushing-warning');
    const saveButton = document.getElementById('manual-part-group-save');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    let includedGroups = {};
    let editingExistingGroup = false;
    const nestedDetails = document.getElementById('manual-part-group-included-details');
    const nestedPicker = document.getElementById('manual-part-group-nested-picker');
    const nestedSearch = document.getElementById('manual-part-group-nested-search');
    const nestedResults = document.getElementById('manual-part-group-nested-results');
    const nestedAdd = document.getElementById('manual-part-group-nested-add');

    function setCatalogMode(showCatalog) {
        document.getElementById('manual-part-group-sidebar').classList.toggle('d-none', !showCatalog);
        const editor = document.getElementById('manual-part-group-editor');
        editor.classList.toggle('col-lg-8', showCatalog);
        editor.classList.toggle('col-lg-12', !showCatalog);
        modal.dataset.catalogMode = showCatalog ? '1' : '0';
    }

    function groupTypeLabel(group) {
        return { assy: 'ASSY', kit: 'KIT', alternative_pn: 'Alternative P/N', oversize: 'Bushing Original/Oversize' }[group?.type] || '';
    }

    function closeNestedPicker() {
        nestedPicker.classList.add('d-none');
        nestedAdd.setAttribute('aria-expanded', 'false');
    }

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

    function isBundle() { return ['assy', 'kit'].includes(type.value); }
    function isAssy() { return type.value === 'assy'; }
    function isKit() { return type.value === 'kit'; }

    function closePicker() {
        picker.classList.add('d-none');
        pickerToggle.setAttribute('aria-expanded', 'false');
        pickedParts.clear();
    }

    function pickerStatus(part) {
        if (members.some(member => Number(member.component_id) === part.component_id)) return '{{ __('Already included') }}';
        const nestedIds = Object.keys(includedGroups).flatMap(id => groupComponentIds(groupForOptionId(Number(id))));
        if (nestedIds.includes(part.component_id)) return '{{ __('Included via group') }}';
        if (isAssy() && memberBelongsToBushingGroup(part)) return '{{ __('Add the complete Bushing group below') }}';
        return '';
    }

    function refreshPickerCount() {
        pickerApply.disabled = pickedParts.size === 0;
        document.getElementById('manual-part-group-picker-count').textContent = pickedParts.size + ' {{ __('selected') }}';
    }

    function renderPicker() {
        const terms = pickerSearch.value.trim().toLocaleLowerCase().split(/\s+/).filter(Boolean);
        const matches = partCatalog.filter(part => {
            const text = [part.ipl_num, part.part_number, part.name].join(' ').toLocaleLowerCase();
            return terms.every(term => text.includes(term));
        });
        pickerRows.innerHTML = matches.map(part => {
            const status = pickerStatus(part);
            if (status) pickedParts.delete(part.component_id);
            return '<tr><td><input type="checkbox" class="form-check-input part-group-pick" data-component-id="' + part.component_id + '" aria-label="' + escapeHtml(part.ipl_num + ' / ' + part.part_number).replace(/"/g, '&quot;') + '" ' + (status ? 'disabled' : '') + ' ' + (pickedParts.has(part.component_id) ? 'checked' : '') + '></td><td>' + escapeHtml(part.ipl_num) + '</td><td>' + escapeHtml(part.part_number) + '</td><td>' + escapeHtml(part.name) + '</td><td class="small text-muted">' + escapeHtml(status) + '</td></tr>';
        }).join('') || '<tr><td colspan="5" class="text-muted text-center">{{ __('No matching parts') }}</td></tr>';
        refreshPickerCount();
    }

    pickerToggle.addEventListener('click', function () {
        if (!picker.classList.contains('d-none')) { closePicker(); return; }
        pickerSearch.value = '';
        picker.classList.remove('d-none');
        pickerToggle.setAttribute('aria-expanded', 'true');
        renderPicker();
        pickerSearch.focus();
    });
    pickerSearch.addEventListener('input', renderPicker);
    pickerSearch.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') event.preventDefault();
    });
    pickerRows.addEventListener('change', function (event) {
        const checkbox = event.target.closest('.part-group-pick');
        if (!checkbox) return;
        const id = Number(checkbox.dataset.componentId);
        if (checkbox.checked) pickedParts.add(id); else pickedParts.delete(id);
        refreshPickerCount();
    });
    document.getElementById('manual-part-group-picker-cancel').addEventListener('click', closePicker);
    pickerApply.addEventListener('click', function () {
        partCatalog.filter(part => pickedParts.has(part.component_id) && !pickerStatus(part)).forEach(part => {
            members.push(Object.assign({}, part, { qty: 1, is_default: members.length === 0 }));
        });
        closePicker();
        updateSuggestedGroupName();
        renderMembers();
        pickerToggle.focus();
    });

    function memberBelongsToBushingGroup(member) {
        return groups.some(function (group) {
            return group.type === 'oversize' && (group.options || []).some(function (option) {
                return Number(option.component_id) === Number(member.component_id);
            });
        });
    }

    function hasIndividualBushingMember() {
        return isAssy() && members.some(memberBelongsToBushingGroup);
    }

    function refreshBushingWarning() {
        const show = hasIndividualBushingMember();
        bushingWarning.classList.toggle('d-none', !show);
        saveButton.disabled = show;
    }

    function suggestedGroupName() {
        if (!isAssy()) return 'Default';
        const assyPart = members.find(function (member) { return member.is_default; }) || members[0];
        return String(assyPart?.part_number || 'Default').trim() || 'Default';
    }

    function updateSuggestedGroupName() {
        if (!editingExistingGroup) nameInput.value = suggestedGroupName();
    }

    function highlightGroupInList(groupId) {
        list.querySelectorAll('.part-group-list-item').forEach(function (item) {
            item.classList.toggle('is-selected', Number(item.dataset.id) === Number(groupId));
        });
        const selected = list.querySelector('.part-group-list-item.is-selected');
        if (modal.dataset.catalogMode === '1') selected?.scrollIntoView({ block: 'nearest' });
    }

    function groupForOptionId(optionId) {
        return groups.find(function (group) {
            return (group.options || []).some(function (option) { return Number(option.id) === Number(optionId); });
        });
    }

    function groupComponentIds(group, visited) {
        visited = visited || {};
        if (!group || visited[group.id]) return [];
        visited[group.id] = true;
        const ids = [];
        (group.options || []).forEach(function (option) {
            if (Number(option.component_id) > 0) ids.push(Number(option.component_id));
            (option.coverages || []).forEach(function (coverage) {
                if (Number(coverage.component_id) > 0) ids.push(Number(coverage.component_id));
                const nestedGroup = groupForOptionId(coverage.covered_option_id);
                groupComponentIds(nestedGroup, visited).forEach(function (componentId) { ids.push(componentId); });
            });
        });
        return Array.from(new Set(ids));
    }

    function groupReaches(startGroupId, targetGroupId, visited) {
        if (Number(startGroupId) === Number(targetGroupId)) return true;
        visited = visited || {};
        if (visited[startGroupId]) return false;
        visited[startGroupId] = true;

        const startGroup = groups.find(function (group) { return Number(group.id) === Number(startGroupId); });
        return (startGroup?.options || []).some(function (option) {
            return (option.coverages || []).some(function (coverage) {
                const nestedGroup = groupForOptionId(coverage.covered_option_id);
                return nestedGroup && groupReaches(nestedGroup.id, targetGroupId, visited);
            });
        });
    }

    function renderTableGroupBadges() {
        partsTable.querySelectorAll('tr[data-component-id]').forEach(function (row) {
            const componentId = Number(row.dataset.componentId || 0);
            const container = row.querySelector('.manual-part-groups-container');
            const empty = row.querySelector('.manual-part-group-empty');
            const assyContainer = row.querySelector('.manual-part-assy-groups-container');
            if (!container || componentId <= 0) return;

            container.replaceChildren();
            assyContainer?.replaceChildren();
            const componentGroups = groups.filter(function (group) {
                return (group.options || []).some(option => Number(option.component_id) === componentId);
            });

            componentGroups.forEach(function (group) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'badge manual-part-group-badge';
                button.dataset.partGroupId = String(group.id);
                button.title = group.name || group.code;

                const name = document.createElement('span');
                name.className = 'manual-part-group-badge-name';
                name.textContent = group.name || group.code;
                button.append(name);
                (group.type === 'assy' ? assyContainer : container)?.append(button);
            });

            empty?.classList.toggle('d-none', componentGroups.some(group => group.type !== 'assy'));
            row.querySelector('.manual-part-assy-empty')?.classList.toggle('d-none', componentGroups.some(group => group.type === 'assy'));
        });
    }

    function refreshType() {
        closePicker();
        closeNestedPicker();
        orderFields.classList.toggle('d-none', !isKit());
        sbWrap.classList.toggle('d-none', !isKit());
        assyWrap.classList.toggle('d-none', !isBundle());
        document.getElementById('manual-part-group-nested-help').textContent = '{{ __('Only directly included groups are listed. Expand a group to see its contents.') }}';
        document.getElementById('manual-part-group-default-heading').textContent = isAssy()
            ? '{{ __('ASSY part') }}'
            : '{{ __('Default') }}';
        document.getElementById('manual-part-group-member-help').textContent = isAssy()
            ? '{{ __('Select the ASSY part and every part included in it.') }}'
            : (isKit()
                ? '{{ __('Select ordinary parts here; include complete ASSY groups below.') }}'
                : (type.value === 'oversize'
                    ? '{{ __('Add the original bushing first, followed by its oversizes. The Bushing checkbox is not required.') }}'
                    : '{{ __('All selected P/Ns are variants of one detail.') }}'));
        updateSuggestedGroupName();
        renderMembers();
        renderNestedGroups();
    }

    function renderMembers() {
        tbody.innerHTML = members.map(function (member, index) {
            const invalidBushing = isAssy() && memberBelongsToBushingGroup(member);
            return '<tr data-index="' + index + '" class="' + (invalidBushing ? 'table-warning' : '') + '"><td>' + escapeHtml(member.ipl_num) + '</td><td>' + escapeHtml(member.part_number) + '</td><td>' + escapeHtml(member.name) + '</td>' +
                '<td><input type="number" min="1" max="9999" class="form-control form-control-sm part-group-member-qty" value="' + Number(member.qty || 1) + '" ' + (!isBundle() ? 'disabled' : '') + '></td>' +
                '<td class="text-center"><input type="radio" name="part-group-default" class="form-check-input part-group-member-default" ' + (member.is_default ? 'checked' : '') + ' ' + (isKit() ? 'disabled' : '') + '></td>' +
                '<td><button type="button" class="btn btn-sm btn-outline-danger part-group-member-remove" aria-label="Remove"><i class="bi bi-x"></i></button></td></tr>';
        }).join('');
        refreshBushingWarning();
        if (!picker.classList.contains('d-none')) renderPicker();
    }

    function optionEntry(optionId) {
        const group = groupForOptionId(Number(optionId));
        const option = group?.options.find(item => Number(item.id) === Number(optionId));
        return group && option ? { group, option } : null;
    }

    function nestedLabel(entry) {
        return [entry.option.ipl_num, entry.group.name || entry.option.part_number].filter(Boolean).join(' · ');
    }

    function compositionHtml(entry, visited = []) {
        if (visited.includes(entry.group.id)) return '<div class="text-warning">{{ __('Circular group reference') }}</div>';
        const path = [...visited, entry.group.id];
        let rows;
        if (['alternative_pn', 'oversize'].includes(entry.group.type)) {
            rows = entry.group.options.map(option => '<li>' + escapeHtml([option.ipl_num, option.part_number].filter(Boolean).join(' · ')) + '</li>');
        } else {
            rows = (entry.option.coverages || []).map(coverage => {
                const child = optionEntry(coverage.covered_option_id);
                if (child) return '<li><details><summary>' + escapeHtml(nestedLabel(child)) + ' <span class="badge text-bg-secondary">' + groupTypeLabel(child.group) + '</span> · Qty ' + Number(coverage.qty || 1) + '</summary>' + compositionHtml(child, path) + '</details></li>';
                if (!coverage.component_id || Number(coverage.component_id) === Number(entry.option.component_id)) return '';
                return '<li>' + escapeHtml([coverage.ipl_num, coverage.part_number, coverage.name].filter(Boolean).join(' · ')) + ' · Qty ' + Number(coverage.qty || 1) + '</li>';
            });
        }
        return '<ul class="part-group-composition">' + rows.join('') + '</ul>';
    }

    function eligibleNestedOptions() {
        const currentGroupId = Number(document.getElementById('manual-part-group-id').value || 0);
        const allowedTypes = isAssy() ? ['assy', 'oversize', 'alternative_pn'] : (isKit() ? ['assy'] : []);
        const includedGroupIds = Object.keys(includedGroups).map(id => groupForOptionId(Number(id))?.id);
        return groups
            .filter(function (group) {
                return allowedTypes.includes(group.type)
                    && Number(group.id) !== currentGroupId
                    && !includedGroupIds.includes(group.id)
                    && (group.options || []).length
                    && (!currentGroupId || !groupReaches(group.id, currentGroupId))
                    && !includedGroupIds.some(id => groupReaches(id, group.id));
            })
            .map(function (group) {
                const option = (group.options || []).find(function (item) { return item.is_default; }) || group.options[0];
                return { group: group, option: option };
            });

    }

    function renderNestedPicker() {
        const terms = nestedSearch.value.trim().toLocaleLowerCase().split(/\s+/).filter(Boolean);
        const entries = eligibleNestedOptions().filter(entry => {
            const text = [entry.group.name, entry.option.part_number, entry.option.ipl_num, groupTypeLabel(entry.group)].join(' ').toLocaleLowerCase();
            return terms.every(term => text.includes(term));
        });
        nestedResults.innerHTML = entries.map(entry => '<div class="part-group-nested-result"><span>' + escapeHtml(nestedLabel(entry)) + ' <span class="badge text-bg-secondary">' + groupTypeLabel(entry.group) + '</span></span><button type="button" class="btn btn-sm btn-outline-info part-group-nested-pick" data-option-id="' + entry.option.id + '">{{ __('Add') }}</button></div>').join('') || '<div class="small text-muted">{{ __('No eligible groups') }}</div>';
    }

    function renderNestedGroups() {
        const ids = Object.keys(includedGroups);
        document.getElementById('manual-part-group-included-count').textContent = ids.length;
        assyMembers.innerHTML = ids.map(id => {
            const entry = optionEntry(Number(id));
            const label = entry ? nestedLabel(entry) : '{{ __('Unavailable group') }} #' + id;
            return '<div class="part-group-nested-row" data-option-id="' + id + '"><details><summary>' + escapeHtml(label) + (entry ? ' <span class="badge text-bg-secondary">' + groupTypeLabel(entry.group) + '</span>' : '') + '</summary>' + (entry ? compositionHtml(entry) : '') + '</details><div class="part-group-nested-actions"><label class="small" for="nested-qty-' + id + '">Qty</label><input id="nested-qty-' + id + '" type="number" min="1" max="9999" class="form-control form-control-sm manual-part-group-assy-qty" data-option-id="' + id + '" value="' + Number(includedGroups[id].qty || 1) + '"><button type="button" class="btn btn-sm btn-outline-danger part-group-nested-remove" data-option-id="' + id + '" aria-label="{{ __('Remove group from composition') }}">×</button></div></div>';
        }).join('') || '<div class="small text-muted">{{ __('No included groups') }}</div>';
        if (!nestedPicker.classList.contains('d-none')) renderNestedPicker();
    }

    function resetForm(useSelection) {
        conditionalMembers.textContent = '';
        conditionalMembers.classList.add('d-none');
        closePicker();
        closeNestedPicker();
        nestedDetails.open = false;
        form.reset();
        editingExistingGroup = false;
        document.getElementById('manual-part-group-id').value = '';
        document.querySelectorAll('.manual-part-group-scope').forEach(function (scope) { scope.checked = true; });
        members = useSelection ? selectedTableMembers() : [];
        includedGroups = {};
        if (members[0]) members[0].is_default = true;
        nameInput.value = 'Default';
        document.getElementById('manual-part-group-delete').classList.add('d-none');
        highlightGroupInList(0);
        document.getElementById('manualPartGroupsModalLabel').textContent = '{{ __('Part Groups') }}';
        refreshType();
    }

    function editGroup(group) {
        resetForm(false);
        editingExistingGroup = true;
        document.getElementById('manual-part-group-id').value = group.id;
        nameInput.value = group.name || 'Default';
        type.value = group.type;
        document.getElementById('manual-part-group-notes').value = group.notes || '';
        document.getElementById('manual-part-group-sb').value = group.manual_service_bulletin_id || '';
        const option = group.options[0] || {};
        document.getElementById('manual-part-group-order-pn').value = isKit() ? (option.part_number || '') : '';
        document.getElementById('manual-part-group-order-ipl').value = isKit() ? (option.ipl_num || '') : '';
        const conditional = (option.coverages || []).filter(function (coverage) { return Boolean(coverage.choice_slot); });
        conditionalMembers.textContent = conditional.length
            ? '{{ __('Conditional members are preserved on save and selected by the technician in the workorder:') }} ' +
                conditional.map(function (coverage) {
                    return coverage.choice_slot.replaceAll('_', ' ') + ': ' +
                        (coverage.covered_option?.part_number || coverage.part_number || '');
                }).join('; ')
            : '';
        conditionalMembers.classList.toggle('d-none', conditional.length === 0);
        members = isBundle() ? (option.coverages || []).filter(function (coverage) { return Number(coverage.component_id) > 0 && !coverage.choice_slot; }).map(function (coverage) { return Object.assign({}, coverage); }) : group.options.map(function (item) {
            const row = partsTable.querySelector('.manual-part-select[data-component-id="' + item.component_id + '"]')?.closest('tr');
            const cells = row?.querySelectorAll('td') || [];
            return { component_id: item.component_id, ipl_num: item.ipl_num || '', part_number: item.part_number || '', name: cells[3]?.textContent.trim() || '', qty: 1, is_default: item.is_default };
        });
        if (isAssy()) {
            members.forEach(function (member) { member.is_default = Number(member.component_id) === Number(option.component_id); });
        }
        includedGroups = {};
        (option.coverages || []).filter(function (coverage) { return Number(coverage.covered_option_id) > 0 && !coverage.choice_slot; }).forEach(function (coverage) {
            includedGroups[Number(coverage.covered_option_id)] = { qty: Number(coverage.qty || 1) };
        });
        document.querySelectorAll('.manual-part-group-scope').forEach(function (scope) { scope.checked = (group.applies_to || []).includes(scope.value); });
        document.getElementById('manual-part-group-delete').classList.remove('d-none');
        refreshType();
        highlightGroupInList(group.id);
        document.getElementById('manualPartGroupsModalLabel').textContent = group.name + ' · ' + groupTypeLabel(group);
    }

    function renderList() {
        list.innerHTML = groups.length ? groups.map(function (group) {
            return '<button type="button" class="list-group-item list-group-item-action part-group-list-item" data-id="' + group.id + '"><strong>' + escapeHtml(group.name) + '</strong><div class="small text-muted">' + escapeHtml(group.code) + ' · ' + escapeHtml(group.type) + '</div></button>';
        }).join('') : '<div class="text-muted small">{{ __('No groups yet.') }}</div>';
    }

    function payload() {
        const componentIds = members.map(function (member) { return Number(member.component_id); });
        const memberQty = {};
        const memberScopes = {};
        tbody.querySelectorAll('tr').forEach(function (row) {
            const member = members[Number(row.dataset.index)];
            memberQty[member.component_id] = Number(row.querySelector('.part-group-member-qty')?.value || 1);
            member.is_default = Boolean(row.querySelector('.part-group-member-default')?.checked);
            if (member.applies_to?.length) memberScopes[member.component_id] = member.applies_to;
        });
        const includedOptionIds = isBundle() ? Object.keys(includedGroups).map(Number) : [];
        const includedGroupQty = {};
        includedOptionIds.forEach(function (optionId) {
            includedGroupQty[optionId] = Number(assyMembers.querySelector('.manual-part-group-assy-qty[data-option-id="' + optionId + '"]')?.value || 1);
        });
        return {
            name: document.getElementById('manual-part-group-name').value.trim(), type: type.value,
            applies_to: Array.from(document.querySelectorAll('.manual-part-group-scope:checked')).map(function (scope) { return scope.value; }),
            manual_service_bulletin_id: document.getElementById('manual-part-group-sb').value || null,
            notes: document.getElementById('manual-part-group-notes').value.trim() || null,
            component_ids: componentIds,
            default_component_id: members.find(function (member) { return member.is_default; })?.component_id || componentIds[0] || null,
            included_group_option_ids: includedOptionIds,
            included_group_qty: includedGroupQty,
            order_part_number: isKit() ? (document.getElementById('manual-part-group-order-pn').value.trim() || null) : null,
            order_ipl_num: isKit() ? (document.getElementById('manual-part-group-order-ipl').value.trim() || null) : null,
            member_qty: memberQty,
            member_applies_to: memberScopes,
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

    document.getElementById('manual-part-groups-open')?.addEventListener('click', function () { setCatalogMode(true); resetForm(true); });
    type.addEventListener('change', refreshType);
    assyMembers.addEventListener('change', function (event) {
        const qty = event.target.closest('.manual-part-group-assy-qty');
        if (qty && includedGroups[Number(qty.dataset.optionId)]) {
            includedGroups[Number(qty.dataset.optionId)].qty = Number(qty.value || 1);
            return;
        }
    });
    assyMembers.addEventListener('click', function (event) {
        const remove = event.target.closest('.part-group-nested-remove');
        if (!remove) return;
        delete includedGroups[Number(remove.dataset.optionId)];
        renderNestedGroups();
        if (!picker.classList.contains('d-none')) renderPicker();
    });
    nestedAdd.addEventListener('click', function () {
        if (!nestedPicker.classList.contains('d-none')) { closeNestedPicker(); return; }
        nestedPicker.classList.remove('d-none');
        nestedAdd.setAttribute('aria-expanded', 'true');
        nestedSearch.value = '';
        renderNestedPicker();
        nestedSearch.focus();
    });
    nestedSearch.addEventListener('input', renderNestedPicker);
    nestedSearch.addEventListener('keydown', event => { if (event.key === 'Enter') event.preventDefault(); });
    document.getElementById('manual-part-group-nested-cancel').addEventListener('click', closeNestedPicker);
    nestedResults.addEventListener('click', function (event) {
        const button = event.target.closest('.part-group-nested-pick');
        if (!button) return;
        const optionId = Number(button.dataset.optionId);
        if (!eligibleNestedOptions().some(entry => Number(entry.option.id) === optionId)) return;
        includedGroups[optionId] = { qty: 1 };
        closeNestedPicker();
        renderNestedGroups();
        if (!picker.classList.contains('d-none')) renderPicker();
    });
    tbody.addEventListener('click', function (event) {
        const button = event.target.closest('.part-group-member-remove'); if (!button) return;
        members.splice(Number(button.closest('tr').dataset.index), 1);
        if (!members.some(function (member) { return member.is_default; }) && members[0]) members[0].is_default = true;
        updateSuggestedGroupName();
        renderMembers();
    });
    tbody.addEventListener('change', function (event) {
        const quantity = event.target.closest('.part-group-member-qty');
        if (quantity) members[Number(quantity.closest('tr').dataset.index)].qty = Number(quantity.value || 1);
        const selectedAssyPart = event.target.closest('.part-group-member-default');
        if (!selectedAssyPart) return;
        members.forEach(function (member, index) { member.is_default = index === Number(selectedAssyPart.closest('tr').dataset.index); });
        updateSuggestedGroupName();
    });
    list.addEventListener('click', function (event) { const item = event.target.closest('.part-group-list-item'); if (item) editGroup(groups.find(function (group) { return group.id === Number(item.dataset.id); })); });
    partsTable.addEventListener('click', function (event) {
        const badge = event.target.closest('.manual-part-group-badge');
        if (!badge) return;
        const group = groups.find(function (item) { return item.id === Number(badge.dataset.partGroupId); });
        if (!group) return;
        setCatalogMode(false);
        editGroup(group);
        window.bootstrap?.Modal.getOrCreateInstance(modal).show();
    });
    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        if (hasIndividualBushingMember()) {
            refreshBushingWarning();
            showNotification('{{ __('Add the complete Bushing Original/Oversize group.') }}', 'error');
            return;
        }
        const id = Number(document.getElementById('manual-part-group-id').value || 0);
        const group = groups.find(function (item) { return item.id === id; });
        if (typeof window.confirmDialog !== 'function') { showNotification('{{ __('Confirmation dialog is unavailable. Nothing was saved.') }}', 'error'); return; }
        const confirmed = await window.confirmDialog({ title: '{{ __('Save part group?') }}', message: nameInput.value, okText: '{{ __('Save group') }}', cancelText: '{{ __('Cancel') }}' });
        if (!confirmed) return;
        const save = saveButton; save.disabled = true;
        try {
            const data = await request(group?.update_url || form.dataset.storeUrl, group ? 'PUT' : 'POST', payload());
            if (group) groups = groups.map(function (item) { return item.id === group.id ? Object.assign(data.group, { update_url: group.update_url, delete_url: group.delete_url }) : item; });
            else window.location.reload();
            renderList(); renderTableGroupBadges(); highlightGroupInList(data.group.id); showNotification(data.message, 'success');
        } catch (error) { showNotification(error.message, 'error'); } finally { refreshBushingWarning(); }
    });
    document.getElementById('manual-part-group-delete').addEventListener('click', async function () {
        const group = groups.find(function (item) { return item.id === Number(document.getElementById('manual-part-group-id').value); });
        if (!group) return;
        if (typeof window.confirmDialog !== 'function') { showNotification('{{ __('Confirmation dialog is unavailable. Nothing was deleted.') }}', 'error'); return; }
        const confirmed = await window.confirmDialog({ title: '{{ __('Delete part group?') }}', message: group.name, okText: '{{ __('Delete') }}', cancelText: '{{ __('Cancel') }}' });
        if (!confirmed) return;
        try { await request(group.delete_url, 'DELETE'); groups = groups.filter(function (item) { return item.id !== group.id; }); renderList(); renderTableGroupBadges(); resetForm(false); showNotification('{{ __('Part group deleted.') }}', 'success'); } catch (error) { showNotification(error.message, 'error'); }
    });

    renderList(); renderTableGroupBadges(); resetForm(true);
});
</script>
