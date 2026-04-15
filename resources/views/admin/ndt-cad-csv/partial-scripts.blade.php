<script>
window.waitForJQuery = function(callback) {
    if (typeof $ !== 'undefined') {
        callback();
    } else {
        setTimeout(function() {
            window.waitForJQuery(callback);
        }, 100);
    }
};

const workorderId = {{ $workorder->id }};
let ndtComponents = @json($ndtCadCsv->ndt_components ?? []);
let cadComponents = @json($ndtCadCsv->cad_components ?? []);
let stressComponents = @json($ndtCadCsv->stress_components ?? []);
let paintComponents = @json($ndtCadCsv->paint_components ?? []);

paintComponents = (paintComponents || []).map((c, i) => ({ ...c, __i: i }));

function escapeHtml(text) {
    if (text == null || text === '') return '';
    const d = document.createElement('div');
    d.textContent = String(text);
    return d.innerHTML;
}
function tableHeadColspan(tableId) {
    const th = document.querySelectorAll('#' + tableId + ' thead th');
    return th.length || 8;
}

let allComponents = [];
let cadProcesses = [];
let stressProcesses = [];
let paintProcesses = [];

function updateNdtTable(components) {
    const tbody = $('#ndt-tbody');
    tbody.empty();
    const colspan = tableHeadColspan('ndt-table');
    const showManual = window.__woNdtCadCols && window.__woNdtCadCols.ndtManual;
    if (components.length === 0) {
        tbody.append(`<tr><td colspan="${colspan}" class="text-center text-muted">No NDT components</td></tr>`);
        $('#ndt-count').text('0');
        return;
    }
    const sortedComponents = components.sort((a, b) =>
        a.ipl_num.localeCompare(b.ipl_num, undefined, {numeric: true, sensitivity: 'base'})
    );
    sortedComponents.forEach((component, displayIndex) => {
        const originalIndex = components.indexOf(component);
        const manualTd = showManual ? `<td>${escapeHtml(component.manual)}</td>` : '';
        const row = `<tr data-index="${originalIndex}" data-display-index="${displayIndex}"><td>${escapeHtml(component.ipl_num)}</td><td>${escapeHtml(component.part_number)}</td><td>${escapeHtml(component.description)}</td><td>${escapeHtml(component.eff_code)}</td><td>${escapeHtml(component.process)}</td><td>${escapeHtml(component.qty)}</td>${manualTd}<td><button class="btn btn-sm btn-primary me-1" onclick="editNdtComponent(${originalIndex})" title="Edit"><i class="fas fa-edit"></i></button><button class="btn btn-sm btn-danger" onclick="removeNdtComponent(${originalIndex})" title="Delete"><i class="fas fa-trash"></i></button></td></tr>`;
        tbody.append(row);
    });
    $('#ndt-count').text(components.length);
}

function updateCadTable(components) {
    const tbody = $('#cad-tbody');
    tbody.empty();
    const colspan = tableHeadColspan('cad-table');
    const showManual = window.__woNdtCadCols && window.__woNdtCadCols.cadManual;
    if (components.length === 0) {
        tbody.append(`<tr><td colspan="${colspan}" class="text-center text-muted">No CAD components</td></tr>`);
        $('#cad-count').text('0');
        return;
    }
    const sortedComponents = components.sort((a, b) =>
        a.ipl_num.localeCompare(b.ipl_num, undefined, {numeric: true, sensitivity: 'base'})
    );
    sortedComponents.forEach((component, displayIndex) => {
        const originalIndex = components.indexOf(component);
        const manualTd = showManual ? `<td>${escapeHtml(component.manual)}</td>` : '';
        const row = `<tr data-index="${originalIndex}" data-display-index="${displayIndex}"><td>${escapeHtml(component.ipl_num)}</td><td>${escapeHtml(component.part_number)}</td><td>${escapeHtml(component.description)}</td><td>${escapeHtml(component.eff_code)}</td><td>${escapeHtml(component.process)}</td><td>${escapeHtml(component.qty)}</td>${manualTd}<td><button class="btn btn-sm btn-primary me-1" onclick="editCadComponent(${originalIndex})" title="Edit"><i class="fas fa-edit"></i></button><button class="btn btn-sm btn-danger" onclick="removeCadComponent(${originalIndex})" title="Delete"><i class="fas fa-trash"></i></button></td></tr>`;
        tbody.append(row);
    });
    $('#cad-count').text(components.length);
}

function updateStressTable(components) {
    const tbody = $('#stress-tbody');
    tbody.empty();
    const colspan = tableHeadColspan('stress-table');
    const showManual = window.__woNdtCadCols && window.__woNdtCadCols.stressManual;
    if (components.length === 0) {
        tbody.append(`<tr><td colspan="${colspan}" class="text-center text-muted">No Stress components</td></tr>`);
        $('#stress-count').text('0');
        return;
    }
    const sortedComponents = components.sort((a, b) =>
        a.ipl_num.localeCompare(b.ipl_num, undefined, {numeric: true, sensitivity: 'base'})
    );
    sortedComponents.forEach((component, displayIndex) => {
        const originalIndex = components.indexOf(component);
        const manualTd = showManual ? `<td>${escapeHtml(component.manual)}</td>` : '';
        const row = `<tr data-index="${originalIndex}" data-display-index="${displayIndex}"><td>${escapeHtml(component.ipl_num)}</td><td>${escapeHtml(component.part_number)}</td><td>${escapeHtml(component.description)}</td><td>${escapeHtml(component.eff_code)}</td><td>${escapeHtml(component.process)}</td><td>${escapeHtml(component.qty)}</td>${manualTd}<td><button class="btn btn-sm btn-primary me-1" onclick="editStressComponent(${originalIndex})" title="Edit"><i class="fas fa-edit"></i></button><button class="btn btn-sm btn-danger" onclick="removeStressComponent(${originalIndex})" title="Delete"><i class="fas fa-trash"></i></button></td></tr>`;
        tbody.append(row);
    });
    $('#stress-count').text(components.length);
}

function updatePaintTable(components) {
    const tbody = $('#paint-tbody');
    tbody.empty();
    const colspan = tableHeadColspan('paint-table');
    const showManual = window.__woNdtCadCols && window.__woNdtCadCols.paintManual;
    if (components.length === 0) {
        tbody.append(`<tr><td colspan="${colspan}" class="text-center text-muted">No Paint components</td></tr>`);
        $('#paint-count').text(0);
        return;
    }
    const sortedComponents = [...components].sort((a, b) => a.ipl_num.localeCompare(b.ipl_num, undefined, {numeric: true, sensitivity: 'base'}));
    sortedComponents.forEach((component, displayIndex) => {
        const originalIndex = components.indexOf(component);
        const manualTd = showManual ? `<td>${escapeHtml(component.manual)}</td>` : '';
        const row = `<tr data-index="${originalIndex}" data-display-index="${displayIndex}"><td>${escapeHtml(component.ipl_num)}</td><td>${escapeHtml(component.part_number)}</td><td>${escapeHtml(component.description)}</td><td>${escapeHtml(component.eff_code)}</td><td>${escapeHtml(component.process)}</td><td>${escapeHtml(component.qty)}</td>${manualTd}<td><button class="btn btn-sm btn-primary me-1" onclick="editPaintComponent(${originalIndex})" title="Edit"><i class="fas fa-edit"></i></button><button class="btn btn-sm btn-danger" onclick="removePaintComponent(${originalIndex})" title="Delete"><i class="fas fa-trash"></i></button></td></tr>`;
        tbody.append(row);
    });
    $('#paint-count').text(components.length);
}

function showNotification(message, type = 'info') {
    if (typeof window.showNotification === 'function') {
        window.showNotification(message, type);
        return;
    }

    const notification = $(`<div class="alert alert-${type} alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`);
    $('body').append(notification);
    setTimeout(() => notification.alert('close'), 3000);
}

window.showAddNdtModal = function() {
    if (typeof $ !== 'undefined') {
        $('#ndtForm')[0].reset();
        $('#ndtEditIndex').val('');
        $('#ndtComponent').val('').trigger('change');
        $('#ndtProcess').val('');
        $('#ndtQty').val('');
        if (typeof $.fn.select2 !== 'undefined') {
            $('#ndtComponent').select2({ placeholder: 'Select a component...', allowClear: true, width: '100%', dropdownParent: $('#ndtModal') });
        }
        $('#ndtModal').modal('show');
    } else {
        document.getElementById('ndtForm').reset();
        document.getElementById('ndtModal').style.display = 'block';
        document.getElementById('ndtModal').classList.add('show');
    }
};

window.showAddCadModal = function() {
    if (typeof $ !== 'undefined') {
        $('#cadForm')[0].reset();
        $('#cadEditIndex').val('');
        $('#cadComponent').val('').trigger('change');
        $('#cadProcess').val('').trigger('change');
        $('#cadQty').val('');
        if (typeof $.fn.select2 !== 'undefined') {
            $('#cadComponent').select2({ placeholder: 'Select a component...', allowClear: true, width: '100%', dropdownParent: $('#cadModal') });
            $('#cadProcess').select2({ placeholder: 'Select a process...', allowClear: true, width: '100%', dropdownParent: $('#cadModal') });
        }
        $('#cadModal').modal('show');
    } else {
        document.getElementById('cadForm').reset();
        document.getElementById('cadModal').style.display = 'block';
        document.getElementById('cadModal').classList.add('show');
    }
};

window.showAddPaintModal = function() {
    if (typeof $ !== 'undefined') {
        $('#paintForm')[0].reset();
        $('#paintEditIndex').val('');
        $('#paintComponent').val('').trigger('change');
        $('#paintProcess').val('').trigger('change');
        $('#paintQty').val('');
        if (typeof $.fn.select2 !== 'undefined') {
            $('#paintComponent').select2({ placeholder: 'Select a component...', allowClear: true, width: '100%', dropdownParent: $('#paintModal') });
            $('#paintProcess').select2({ placeholder: 'Select a process...', allowClear: true, width: '100%', dropdownParent: $('#paintModal') });
        }
        $('#paintModal').modal('show');
    } else {
        document.getElementById('paintForm').reset();
        document.getElementById('paintModal').style.display = 'block';
        document.getElementById('paintModal').classList.add('show');
    }
};

window.showAddStressModal = function() {
    if (typeof $ !== 'undefined') {
        $('#stressForm')[0].reset();
        $('#stressEditIndex').val('');
        $('#stressComponent').val('').trigger('change');
        $('#stressProcess').val('');
        $('#stressQty').val('');
        if (typeof $.fn.select2 !== 'undefined') {
            $('#stressComponent').select2({ placeholder: 'Select a component...', allowClear: true, width: '100%', dropdownParent: $('#stressModal') });
            $('#stressProcess').select2({ placeholder: 'Select a process...', allowClear: true, width: '100%', dropdownParent: $('#stressModal') });
        }
        $('#stressModal').modal('show');
    } else {
        document.getElementById('stressForm').reset();
        document.getElementById('stressModal').style.display = 'block';
        document.getElementById('stressModal').classList.add('show');
    }
};

function initializeWhenReady() {
    if (typeof $ !== 'undefined') {
        loadComponents();
        loadCadProcesses();
        loadStressProcesses();
        loadPaintProcesses();
        if (typeof $.fn.select2 !== 'undefined') {
            $('#ndtModal #ndtComponent').select2({ placeholder: 'Select a component...', allowClear: true, width: '100%', dropdownParent: $('#ndtModal') });
            $('#cadModal #cadComponent').select2({ placeholder: 'Select a component...', allowClear: true, width: '100%', dropdownParent: $('#cadModal') });
            $('#cadModal #cadProcess').select2({ placeholder: 'Select a process...', allowClear: true, width: '100%', dropdownParent: $('#cadModal') });
            $('#cadEditModal #cadEditProcess').select2({ placeholder: 'Select a process...', allowClear: true, width: '100%', dropdownParent: $('#cadEditModal') });
            $('#paintModal #paintComponent').select2({ placeholder: 'Select a component...', allowClear: true, width: '100%', dropdownParent: $('#paintModal') });
            $('#paintModal #paintProcess').select2({ placeholder: 'Select a process...', allowClear: true, width: '100%', dropdownParent: $('#paintModal') });
            $('#paintEditModal #paintEditProcess').select2({ placeholder: 'Select a process...', allowClear: true, width: '100%', dropdownParent: $('#paintEditModal') });
            $('#stressModal #stressComponent').select2({ placeholder: 'Select a component...', allowClear: true, width: '100%', dropdownParent: $('#stressModal') });
            $('#stressModal #stressProcess').select2({ placeholder: 'Select a process...', allowClear: true, width: '100%', dropdownParent: $('#stressModal') });
            $('#stressEditModal #stressEditProcess').select2({ placeholder: 'Select a process...', allowClear: true, width: '100%', dropdownParent: $('#stressEditModal') });
        }
        $('#ndtComponent').on('change', function() {
            const o = $(this).find('option:selected');
            if (o.val()) { $('#ndtQty').val(o.data('units-assy') || 1); }
        });
        $('#cadComponent').on('change', function() {
            const o = $(this).find('option:selected');
            if (o.val()) { $('#cadQty').val(o.data('units-assy') || 1); }
        });
        $('#stressComponent').on('change', function() {
            const o = $(this).find('option:selected');
            if (o.val()) { $('#stressQty').val(o.data('units-assy') || 1); }
        });
        $('#paintComponent').on('change', function() {
            const o = $(this).find('option:selected');
            if (o.val()) { $('#paintQty').val(o.data('units-assy') || 1); }
        });
        $('#ndtForm').on('submit', function(e) {
            e.preventDefault();
            const sc = $('#ndtComponent option:selected');
            if (!sc.val()) { showNotification('Please select a component', 'warning'); return; }
            const data = { component_id: sc.val(), ipl_num: sc.data('ipl-num'), part_number: sc.data('part-number'), description: sc.data('description'), process: $('#ndtProcess').val(), qty: parseInt($('#ndtQty').val()), eff_code: ($('#ndtEffCode').val() || '').trim(), _token: $('meta[name="csrf-token"]').attr('content') };
            $.post(`{{ route('ndt-cad-csv.add-ndt', ['workorder' => $workorder->id]) }}`, data).done(function(r) {
                if (r.success) { ndtComponents.push(data); updateNdtTable(ndtComponents); $('#ndtModal').modal('hide'); $('#ndtForm')[0].reset(); $('#ndtEffCode').val(''); $('#ndtComponent').val('').trigger('change'); showNotification('NDT component added', 'success'); } else showNotification('Error: ' + r.message, 'error');
            }).fail(function(xhr) { showNotification('Error adding component', 'error'); });
        });
        $('#cadForm').on('submit', function(e) {
            e.preventDefault();
            const sc = $('#cadComponent option:selected');
            if (!sc.val()) { showNotification('Please select a component', 'warning'); return; }
            if (!$('#cadProcess').val()) { showNotification('Please select a process', 'warning'); return; }
            const data = { component_id: sc.val(), ipl_num: sc.data('ipl-num'), part_number: sc.data('part-number'), description: sc.data('description'), process: $('#cadProcess').val(), qty: parseInt($('#cadQty').val()), eff_code: ($('#cadEffCode').val() || '').trim(), _token: $('meta[name="csrf-token"]').attr('content') };
            $.post(`{{ route('ndt-cad-csv.add-cad', ['workorder' => $workorder->id]) }}`, data).done(function(r) {
                if (r.success) { cadComponents.push(data); updateCadTable(cadComponents); $('#cadModal').modal('hide'); $('#cadForm')[0].reset(); $('#cadEffCode').val(''); $('#cadComponent').val('').trigger('change'); $('#cadProcess').val('').trigger('change'); showNotification('CAD component added', 'success'); } else showNotification('Error: ' + r.message, 'error');
            }).fail(function() { showNotification('Error adding component', 'error'); });
        });
        $('#paintForm').on('submit', function(e) {
            e.preventDefault();
            const sc = $('#paintComponent option:selected');
            if (!sc.val()) { showNotification('Please select a component', 'warning'); return; }
            if (!$('#paintProcess').val()) { showNotification('Please select a process', 'warning'); return; }
            const data = { component_id: sc.val(), ipl_num: sc.data('ipl-num'), part_number: sc.data('part-number'), description: sc.data('description'), process: $('#paintProcess').val(), qty: parseInt($('#paintQty').val()), eff_code: ($('#paintEffCode').val() || '').trim(), _token: $('meta[name="csrf-token"]').attr('content') };
            $.post(`{{ route('ndt-cad-csv.add-paint', ['workorder' => $workorder->id]) }}`, data).done(function(r) {
                if (r.success) { paintComponents.push(data); updatePaintTable(paintComponents); $('#paintModal').modal('hide'); $('#paintForm')[0].reset(); $('#paintEffCode').val(''); $('#paintComponent').val('').trigger('change'); $('#paintProcess').val('').trigger('change'); showNotification('Paint component added', 'success'); } else showNotification('Error: ' + r.message, 'error');
            }).fail(function() { showNotification('Error adding component', 'error'); });
        });
        $('#stressForm').on('submit', function(e) {
            e.preventDefault();
            const sc = $('#stressComponent option:selected');
            if (!sc.val()) { showNotification('Please select a component', 'warning'); return; }
            if (!$('#stressProcess').val()) { showNotification('Please select a process', 'warning'); return; }
            const data = { component_id: sc.val(), ipl_num: sc.data('ipl-num'), part_number: sc.data('part-number'), description: sc.data('description'), process: $('#stressProcess').val(), qty: parseInt($('#stressQty').val()), _token: $('meta[name="csrf-token"]').attr('content') };
            $.post(`{{ route('ndt-cad-csv.add-stress', ['workorder' => $workorder->id]) }}`, data).done(function(r) {
                if (r.success) { stressComponents.push(data); updateStressTable(stressComponents); $('#stressModal').modal('hide'); $('#stressForm')[0].reset(); $('#stressComponent').val('').trigger('change'); $('#stressProcess').val('').trigger('change'); showNotification('Stress component added', 'success'); } else showNotification('Error: ' + r.message, 'error');
            }).fail(function() { showNotification('Error adding component', 'error'); });
        });
        $('#ndtEditForm').on('submit', function(e) {
            e.preventDefault();
            const editIndex = $('#ndtEditIndex').val();
            if (!editIndex) { showNotification('Edit index not found', 'error'); return; }
            const data = { index: editIndex, part_number: $('#ndtEditPartNumber').val(), description: $('#ndtEditDescription').val(), process: $('#ndtEditProcess').val(), qty: parseInt($('#ndtEditQty').val()), eff_code: ($('#ndtEditEffCode').val() || '').trim(), _token: $('meta[name="csrf-token"]').attr('content') };
            $.post(`{{ route('ndt-cad-csv.edit-ndt', ['workorder' => $workorder->id]) }}`, data).done(function(r) {
                if (r.success) { ndtComponents[editIndex] = { ...ndtComponents[editIndex], part_number: data.part_number, description: data.description, process: data.process, qty: data.qty, eff_code: data.eff_code }; updateNdtTable(ndtComponents); $('#ndtEditModal').modal('hide'); showNotification('NDT component updated', 'success'); } else showNotification('Error: ' + r.message, 'error');
            }).fail(function() { showNotification('Error saving', 'error'); });
        });
        $('#cadEditForm').on('submit', function(e) {
            e.preventDefault();
            const editIndex = $('#cadEditIndex').val();
            if (!editIndex) { showNotification('Edit index not found', 'error'); return; }
            const data = { index: editIndex, part_number: $('#cadEditPartNumber').val(), description: $('#cadEditDescription').val(), process: $('#cadEditProcess').val(), qty: parseInt($('#cadEditQty').val()), eff_code: ($('#cadEditEffCode').val() || '').trim(), _token: $('meta[name="csrf-token"]').attr('content') };
            $.post(`{{ route('ndt-cad-csv.edit-cad', ['workorder' => $workorder->id]) }}`, data).done(function(r) {
                if (r.success) { cadComponents[editIndex] = { ...cadComponents[editIndex], part_number: data.part_number, description: data.description, process: data.process, qty: data.qty, eff_code: data.eff_code }; updateCadTable(cadComponents); $('#cadEditModal').modal('hide'); showNotification('CAD component updated', 'success'); } else showNotification('Error: ' + r.message, 'error');
            }).fail(function() { showNotification('Error saving', 'error'); });
        });
        $('#paintEditForm').on('submit', function(e) {
            e.preventDefault();
            const editIndex = $('#paintEditIndex').val();
            if (!editIndex) { showNotification('Edit index not found', 'error'); return; }
            const data = { index: editIndex, part_number: $('#paintEditPartNumber').val(), description: $('#paintEditDescription').val(), process: $('#paintEditProcess').val(), qty: parseInt($('#paintEditQty').val()), eff_code: ($('#paintEditEffCode').val() || '').trim(), _token: $('meta[name="csrf-token"]').attr('content') };
            $.post(`{{ route('ndt-cad-csv.edit-paint', ['workorder' => $workorder->id]) }}`, data).done(function(r) {
                if (r.success) { paintComponents[editIndex] = { ...paintComponents[editIndex], part_number: data.part_number, description: data.description, process: data.process, qty: data.qty, eff_code: data.eff_code }; updatePaintTable(paintComponents); $('#paintEditModal').modal('hide'); showNotification('Paint component updated', 'success'); } else showNotification('Error: ' + r.message, 'error');
            }).fail(function() { showNotification('Error saving', 'error'); });
        });
        $('#stressEditForm').on('submit', function(e) {
            e.preventDefault();
            const editIndex = $('#stressEditIndex').val();
            if (!editIndex) { showNotification('Edit index not found', 'error'); return; }
            const data = { index: editIndex, part_number: $('#stressEditPartNumber').val(), description: $('#stressEditDescription').val(), process: $('#stressEditProcess').val(), qty: parseInt($('#stressEditQty').val()), _token: $('meta[name="csrf-token"]').attr('content') };
            $.post(`{{ route('ndt-cad-csv.edit-stress', ['workorder' => $workorder->id]) }}`, data).done(function(r) {
                if (r.success) { stressComponents[editIndex] = { ...stressComponents[editIndex], part_number: data.part_number, description: data.description, process: data.process, qty: data.qty }; updateStressTable(stressComponents); $('#stressEditModal').modal('hide'); showNotification('Stress component updated', 'success'); } else showNotification('Error: ' + r.message, 'error');
            }).fail(function() { showNotification('Error saving', 'error'); });
        });
        updatePaintTable(paintComponents);
    } else setTimeout(initializeWhenReady, 100);
}

initializeWhenReady();

function loadComponents() {
    $.get(`{{ route('ndt-cad-csv.components', ['workorder' => $workorder->id]) }}`).done(function(r) {
        if (r.success) { allComponents = r.components; updateComponentDropdowns(); } else console.error(r.message);
    }).fail(function(xhr) { console.error('Error loading components:', xhr.responseText); });
}

function loadCadProcesses() {
    $.get(`{{ route('ndt-cad-csv.cad-processes', ['workorder' => $workorder->id]) }}`).done(function(r) {
        if (r.success) { cadProcesses = r.processes; updateCadProcessDropdown(); }
    }).fail(function(xhr) { console.error('Error loading CAD processes:', xhr.responseText); });
}

function loadPaintProcesses() {
    $.get(`{{ route('ndt-cad-csv.paint-processes', ['workorder' => $workorder->id]) }}`).done(function(r) {
        if (r.success) { paintProcesses = r.processes; updatePaintProcessDropdown(); }
    }).fail(function(xhr) { console.error('Error loading Paint processes:', xhr.responseText); });
}

function loadStressProcesses() {
    $.get(`{{ route('ndt-cad-csv.stress-processes', ['workorder' => $workorder->id]) }}`).done(function(r) {
        if (r.success) { stressProcesses = r.processes; updateStressProcessDropdown(); }
    }).fail(function(xhr) { console.error('Error loading Stress processes:', xhr.responseText); });
}

function updateComponentDropdowns() {
    const sorted = allComponents.sort((a,b) => a.ipl_num.localeCompare(b.ipl_num, undefined, {numeric: true, sensitivity: 'base'}));
    ['ndt','cad','paint','stress'].forEach(t => {
        $('#' + t + 'Component').empty().append('<option value="">Select a component...</option>');
        sorted.forEach(c => $('#' + t + 'Component').append(`<option value="${c.id}" data-ipl-num="${c.ipl_num}" data-part-number="${c.part_number}" data-description="${c.name}" data-units-assy="${c.units_assy}">${c.ipl_num} : ${c.part_number} - ${c.name}</option>`));
    });
    if (typeof $.fn.select2 !== 'undefined') {
        $('#ndtComponent,#cadComponent,#paintComponent,#stressComponent').trigger('change.select2');
    }
}

function updateCadProcessDropdown() {
    $('#cadProcess').empty().append('<option value="">Select a process...</option>');
    $('#cadEditProcess').empty().append('<option value="">Select a process...</option>');
    cadProcesses.forEach(p => {
        $('#cadProcess').append(`<option value="${p.process}">${p.process}</option>`);
        $('#cadEditProcess').append(`<option value="${p.process}">${p.process}</option>`);
    });
    if (typeof $.fn.select2 !== 'undefined') { $('#cadProcess,#cadEditProcess').trigger('change.select2'); }
}

function updatePaintProcessDropdown() {
    $('#paintProcess').empty().append('<option value="">Select a process...</option>');
    $('#paintEditProcess').empty().append('<option value="">Select a process...</option>');
    paintProcesses.forEach(p => {
        $('#paintProcess').append(`<option value="${p.process}">${p.process}</option>`);
        $('#paintEditProcess').append(`<option value="${p.process}">${p.process}</option>`);
    });
    if (typeof $.fn.select2 !== 'undefined') { $('#paintProcess,#paintEditProcess').trigger('change.select2'); }
}

function updateStressProcessDropdown() {
    $('#stressProcess').empty().append('<option value="">Select a process...</option>');
    $('#stressEditProcess').empty().append('<option value="">Select a process...</option>');
    stressProcesses.forEach(p => {
        $('#stressProcess').append(`<option value="${p.process}">${p.process}</option>`);
        $('#stressEditProcess').append(`<option value="${p.process}">${p.process}</option>`);
    });
    if (typeof $.fn.select2 !== 'undefined') { $('#stressProcess,#stressEditProcess').trigger('change.select2'); }
}

window.removeNdtComponent = function(index) {
    if (confirm('Are you sure you want to remove this component?')) {
        $.post(`{{ route('ndt-cad-csv.remove-ndt', ['workorder' => $workorder->id]) }}`, { index, _token: $('meta[name="csrf-token"]').attr('content') }).done(function(r) {
            if (r.success) { ndtComponents.splice(index, 1); updateNdtTable(ndtComponents); showNotification('NDT component removed', 'success'); } else showNotification('Error: ' + r.message, 'error');
        }).fail(function() { showNotification('Error deleting component', 'error'); });
    }
};

window.removeCadComponent = function(index) {
    if (confirm('Are you sure you want to remove this component?')) {
        $.post(`{{ route('ndt-cad-csv.remove-cad', ['workorder' => $workorder->id]) }}`, { index, _token: $('meta[name="csrf-token"]').attr('content') }).done(function(r) {
            if (r.success) { cadComponents.splice(index, 1); updateCadTable(cadComponents); showNotification('CAD component removed', 'success'); } else showNotification('Error: ' + r.message, 'error');
        }).fail(function() { showNotification('Error deleting component', 'error'); });
    }
};

window.removeStressComponent = function(index) {
    if (confirm('Are you sure you want to remove this component?')) {
        $.post(`{{ route('ndt-cad-csv.remove-stress', ['workorder' => $workorder->id]) }}`, { index, _token: $('meta[name="csrf-token"]').attr('content') }).done(function(r) {
            if (r.success) { stressComponents.splice(index, 1); updateStressTable(stressComponents); showNotification('Stress component removed', 'success'); } else showNotification('Error: ' + r.message, 'error');
        }).fail(function() { showNotification('Error deleting component', 'error'); });
    }
};

window.removePaintComponent = function(index) {
    if (confirm('Are you sure you want to remove this component?')) {
        $.post(`{{ route('ndt-cad-csv.remove-paint', ['workorder' => $workorder->id]) }}`, { index, _token: $('meta[name="csrf-token"]').attr('content') }).done(function(r) {
            if (r.success) { paintComponents.splice(index, 1); updatePaintTable(paintComponents); showNotification('Paint component removed', 'success'); } else showNotification('Error: ' + r.message, 'error');
        }).fail(function() { showNotification('Error deleting component', 'error'); });
    }
};

window.loadSnapshotFromStd = function(type) {
    if (!confirm('Заменить весь список ' + type.toUpperCase() + ' данными из STD мануала? Все правки только для этого workorder по этой вкладке будут потеряны.')) return;
    $.post(`{{ route('ndt-cad-csv.reload-from-manual', ['workorder' => $workorder->id]) }}`, { type, _token: $('meta[name="csrf-token"]').attr('content') }).done(function(r) {
        if (r.success) { showNotification((r.message || 'OK') + (r.count != null ? ' (' + r.count + ')' : ''), 'success'); location.reload(); }
        else showNotification('Error: ' + (r.message || 'unknown'), 'error');
    }).fail(function(xhr) {
        var m = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : xhr.statusText;
        showNotification('Error: ' + m, 'error');
    });
};

window.reloadFromManual = window.loadSnapshotFromStd;
window.forceLoadFromManual = window.loadSnapshotFromStd;

window.editNdtComponent = function(index) {
    const c = ndtComponents[index];
    if (!c) { showNotification('Component not found', 'error'); return; }
    $('#ndtCurrentIpl').text(c.ipl_num); $('#ndtCurrentPartNumber').text(c.part_number); $('#ndtCurrentDescription').text(c.description); $('#ndtCurrentEffCode').text(c.eff_code || ''); $('#ndtCurrentProcess').text(c.process); $('#ndtCurrentQty').text(c.qty);
    $('#ndtEditIndex').val(index); $('#ndtEditPartNumber').val(c.part_number); $('#ndtEditDescription').val(c.description); $('#ndtEditProcess').val(c.process); $('#ndtEditQty').val(c.qty); $('#ndtEditEffCode').val(c.eff_code || '');
    $('#ndtEditModal').modal('show');
};

window.editCadComponent = function(index) {
    const c = cadComponents[index];
    if (!c) { showNotification('Component not found', 'error'); return; }
    $('#cadCurrentIpl').text(c.ipl_num); $('#cadCurrentPartNumber').text(c.part_number); $('#cadCurrentDescription').text(c.description); $('#cadCurrentEffCode').text(c.eff_code || ''); $('#cadCurrentProcess').text(c.process); $('#cadCurrentQty').text(c.qty);
    $('#cadEditIndex').val(index); $('#cadEditPartNumber').val(c.part_number); $('#cadEditDescription').val(c.description); $('#cadEditQty').val(c.qty); $('#cadEditEffCode').val(c.eff_code || '');
    if (cadProcesses && cadProcesses.length) {
        $('#cadEditProcess').empty().append('<option value="">Select a process...</option>');
        cadProcesses.forEach(p => $('#cadEditProcess').append(`<option value="${p.process}">${p.process}</option>`));
        $('#cadEditProcess').val(c.process);
        if (typeof $.fn.select2 !== 'undefined') $('#cadEditProcess').trigger('change.select2');
    } else {
        $.get(`{{ route('ndt-cad-csv.cad-processes', ['workorder' => $workorder->id]) }}`).done(function(r) {
            if (r.success) { cadProcesses = r.processes; $('#cadEditProcess').empty().append('<option value="">Select a process...</option>'); cadProcesses.forEach(p => $('#cadEditProcess').append(`<option value="${p.process}">${p.process}</option>`)); $('#cadEditProcess').val(c.process); if (typeof $.fn.select2 !== 'undefined') $('#cadEditProcess').trigger('change.select2'); }
        });
    }
    $('#cadEditModal').modal('show');
};

window.editPaintComponent = function(index) {
    const c = paintComponents[index];
    if (!c) { showNotification('Component not found', 'error'); return; }
    $('#paintCurrentIpl').text(c.ipl_num); $('#paintCurrentPartNumber').text(c.part_number); $('#paintCurrentDescription').text(c.description); $('#paintCurrentProcess').text(c.process); $('#paintCurrentQty').text(c.qty);
    $('#paintEditIndex').val(index); $('#paintEditPartNumber').val(c.part_number); $('#paintEditDescription').val(c.description); $('#paintEditQty').val(c.qty);
    if (paintProcesses && paintProcesses.length) {
        $('#paintEditProcess').empty().append('<option value="">Select a process...</option>');
        paintProcesses.forEach(p => $('#paintEditProcess').append(`<option value="${p.process}">${p.process}</option>`));
        $('#paintEditProcess').val(c.process);
        if (typeof $.fn.select2 !== 'undefined') $('#paintEditProcess').trigger('change.select2');
    } else {
        $.get(`{{ route('ndt-cad-csv.paint-processes', ['workorder' => $workorder->id]) }}`).done(function(r) {
            if (r.success) { paintProcesses = r.processes; $('#paintEditProcess').empty().append('<option value="">Select a process...</option>'); paintProcesses.forEach(p => $('#paintEditProcess').append(`<option value="${p.process}">${p.process}</option>`)); $('#paintEditProcess').val(c.process); if (typeof $.fn.select2 !== 'undefined') $('#paintEditProcess').trigger('change.select2'); }
        });
    }
    $('#paintEditModal').modal('show');
};

window.editStressComponent = function(index) {
    const c = stressComponents[index];
    if (!c) { showNotification('Component not found', 'error'); return; }
    $('#stressCurrentIpl').text(c.ipl_num); $('#stressCurrentPartNumber').text(c.part_number); $('#stressCurrentDescription').text(c.description); $('#stressCurrentEffCode').text(c.eff_code || ''); $('#stressCurrentProcess').text(c.process); $('#stressCurrentQty').text(c.qty);
    $('#stressEditIndex').val(index); $('#stressEditPartNumber').val(c.part_number); $('#stressEditDescription').val(c.description); $('#stressEditQty').val(c.qty); $('#stressEditEffCode').val(c.eff_code || '');
    if (stressProcesses && stressProcesses.length) {
        $('#stressEditProcess').empty().append('<option value="">Select a process...</option>');
        stressProcesses.forEach(p => $('#stressEditProcess').append(`<option value="${p.process}">${p.process}</option>`));
        $('#stressEditProcess').val(c.process);
        if (typeof $.fn.select2 !== 'undefined') $('#stressEditProcess').trigger('change.select2');
    } else {
        $.get(`{{ route('ndt-cad-csv.stress-processes', ['workorder' => $workorder->id]) }}`).done(function(r) {
            if (r.success) { stressProcesses = r.processes; $('#stressEditProcess').empty().append('<option value="">Select a process...</option>'); stressProcesses.forEach(p => $('#stressEditProcess').append(`<option value="${p.process}">${p.process}</option>`)); $('#stressEditProcess').val(c.process); if (typeof $.fn.select2 !== 'undefined') $('#stressEditProcess').trigger('change.select2'); }
        });
    }
    $('#stressEditModal').modal('show');
};
</script>
