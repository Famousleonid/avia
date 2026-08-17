<input type="hidden" name="manual_selection_present" value="1" form="createForm">

<div class="modal fade" id="createWorkorderManualsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable workorder-manuals-dialog" style="--bs-modal-width: 960px;">
        <div class="modal-content bg-dark text-light border-secondary">
            <div class="modal-header border-secondary">
                <div>
                    <h5 class="modal-title mb-0">{{ __('Workorder Manuals') }}</h5>
                    <div class="small text-secondary" id="createWorkorderManualsUnit">{{ __('Select a Unit first.') }}</div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info py-2">
                    {{ __('Additional manuals assigned to the primary Manual/CMM are selected by default. Clear one to exclude it from this Workorder only.') }}
                </div>
                @error('used_additional_manual_ids')
                    <div class="alert alert-danger py-2">{{ $message }}</div>
                @enderror
                <div class="table-responsive">
                    <table class="table table-dark table-bordered align-middle mb-0">
                        <thead>
                        <tr class="text-secondary small">
                            <th style="width: 70px;">{{ __('Use') }}</th>
                            <th>{{ __('Manual') }}</th>
                            <th class="workorder-manual-title-column">{{ __('Title') }}</th>
                            <th style="width: 100px;">{{ __('Lib') }}</th>
                            <th style="width: 130px;">{{ __('Type') }}</th>
                        </tr>
                        </thead>
                        <tbody id="createWorkorderManualsRows">
                        <tr><td colspan="5" class="text-center text-secondary py-3">{{ __('Select a Unit first.') }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-outline-info" data-bs-dismiss="modal">{{ __('Apply') }}</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const unitSelect = document.getElementById('unit_id');
        const openButton = document.getElementById('createWorkorderManualsBtn');
        const countBadge = document.getElementById('createWorkorderManualsCount');
        const unitLabel = document.getElementById('createWorkorderManualsUnit');
        const rows = document.getElementById('createWorkorderManualsRows');
        if (!unitSelect || !openButton || !countBadge || !unitLabel || !rows) return;

        const packages = @json($unitManualPackages);
        const oldUnitId = @json((string) old('unit_id', ''));
        const oldUsedIds = new Set(@json(array_map('strval', old('used_additional_manual_ids', []))));
        const restoreOldSelection = @json(session()->hasOldInput('manual_selection_present'));
        const selectedByUnit = new Map();

        const escapeHtml = function (value) {
            const node = document.createElement('div');
            node.textContent = value == null ? '' : String(value);
            return node.innerHTML;
        };

        const currentPackage = function () {
            const unitId = String(unitSelect.value || '');
            if (!unitId) return null;
            if (packages[unitId]) return packages[unitId];

            const option = unitSelect.options[unitSelect.selectedIndex];
            const manualId = Number(option?.dataset.manualId || 0);
            return {
                primary: manualId ? {
                    id: manualId,
                    number: option?.dataset.manualNumber || '',
                    title: option?.dataset.manualTitle || '',
                    lib: option?.dataset.manualLib || ''
                } : null,
                additional: []
            };
        };

        const selectedIds = function (unitId, packageData) {
            if (!selectedByUnit.has(unitId)) {
                const defaults = restoreOldSelection && unitId === oldUnitId
                    ? oldUsedIds
                    : new Set((packageData.additional || []).map(function (manual) { return String(manual.id); }));
                selectedByUnit.set(unitId, new Set(defaults));
            }
            return selectedByUnit.get(unitId);
        };

        const render = function () {
            const unitId = String(unitSelect.value || '');
            const packageData = currentPackage();
            openButton.disabled = !unitId;

            if (!unitId || !packageData) {
                unitLabel.textContent = '{{ __('Select a Unit first.') }}';
                rows.innerHTML = '<tr><td colspan="5" class="text-center text-secondary py-3">{{ __('Select a Unit first.') }}</td></tr>';
                countBadge.textContent = '0/0';
                return;
            }

            const option = unitSelect.options[unitSelect.selectedIndex];
            unitLabel.textContent = option?.textContent?.trim() || '';
            const selected = selectedIds(unitId, packageData);
            const primary = packageData.primary;
            const additional = packageData.additional || [];
            const html = [];

            if (primary) {
                html.push('<tr>'
                    + '<td class="text-center"><input class="form-check-input" type="checkbox" checked disabled></td>'
                    + '<td><strong>' + escapeHtml(primary.number) + '</strong></td>'
                    + '<td class="workorder-manual-title-column">' + escapeHtml(primary.title || '—') + '</td>'
                    + '<td class="text-secondary">' + escapeHtml(primary.lib || '—') + '</td>'
                    + '<td><span class="badge bg-primary">{{ __('Main') }}</span></td>'
                    + '</tr>');
            }

            additional.forEach(function (manual) {
                const id = String(manual.id);
                html.push('<tr>'
                    + '<td class="text-center"><input class="form-check-input create-workorder-manual-check" type="checkbox" name="used_additional_manual_ids[]" value="' + escapeHtml(id) + '" form="createForm" ' + (selected.has(id) ? 'checked' : '') + '></td>'
                    + '<td><strong>' + escapeHtml(manual.number) + '</strong></td>'
                    + '<td class="workorder-manual-title-column">' + escapeHtml(manual.title || '—') + '</td>'
                    + '<td class="text-secondary">' + escapeHtml(manual.lib || '—') + '</td>'
                    + '<td><span class="badge bg-secondary">{{ __('Additional') }}</span></td>'
                    + '</tr>');
            });

            if (!primary && additional.length === 0) {
                html.push('<tr><td colspan="5" class="text-center text-secondary py-3">{{ __('No manual assigned.') }}</td></tr>');
            } else if (additional.length === 0) {
                html.push('<tr><td colspan="5" class="text-center text-secondary py-3">{{ __('No additional manuals assigned to this Manual.') }}</td></tr>');
            }

            rows.innerHTML = html.join('');
            const updateCount = function () {
                countBadge.textContent = String((primary ? 1 : 0) + selected.size) + '/' + String((primary ? 1 : 0) + additional.length);
            };
            updateCount();

            rows.querySelectorAll('.create-workorder-manual-check').forEach(function (checkbox) {
                checkbox.addEventListener('change', function () {
                    if (checkbox.checked) selected.add(String(checkbox.value));
                    else selected.delete(String(checkbox.value));
                    updateCount();
                });
            });
        };

        if (window.jQuery) {
            window.jQuery(unitSelect).on('change.workorderManuals', render);
        } else {
            unitSelect.addEventListener('change', render);
        }
        render();
    });
</script>
