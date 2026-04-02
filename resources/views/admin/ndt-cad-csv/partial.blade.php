<div class="ndt-cad-csv-partial">
<style>
    .ndt-cad-csv-partial .container {
        max-width: 1080px;
    }
    .ndt-cad-csv-partial .text-center {
        text-align: center;
        align-content: center;
    }
    .ndt-cad-csv-partial .card {
        max-width: 1060px;
    }

    html[data-bs-theme="dark"]  .select2-selection--single {
        background-color: #121212 !important;
        color: gray !important;
        height: 38px !important;
        border: 1px solid #495057 !important;
        align-items: center !important;
        border-radius: 8px;
    }

    html[data-bs-theme="dark"] .select2-container .select2-selection__rendered {
        color: #999999;
        line-height: 2.2 !important;
    }

    html[data-bs-theme="dark"] .select2-search--dropdown .select2-search__field  {
        background-color: #343A40 !important;
    }

    html[data-bs-theme="dark"] .select2-container--default .select2-selection--single .select2-selection__rendered {
        padding-right: 25px;
    }

    html[data-bs-theme="dark"] .select2-container .select2-dropdown {
        max-height: 40vh !important;
        overflow-y: auto !important;
        border: 1px solid #ccc !important;
        border-radius: 8px;
        color: white;
        background-color: #121212 !important;
    }

    html[data-bs-theme="light"] .select2-container .select2-dropdown {
        max-height: 40vh !important;
        overflow-y: auto !important;
    }

    html[data-bs-theme="dark"] .select2-container .select2-results__option:hover {
        background-color: #6ea8fe;
        color: #000000;
    }
    .select2-container .select2-selection__clear {
        position: absolute !important;
        right: 10px !important;
        top: 50% !important;
        transform: translateY(-50%) !important;
        z-index: 1;
    }

/* Убеждаемся, что dropdown отображается поверх модального окна */
.ndt-cad-csv-partial .modal .select2-container {
    z-index: 9999 !important;
}
</style>

<!-- Навигация по вкладкам -->
<ul class="nav nav-tabs" id="componentTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="ndt-tab" data-bs-toggle="tab" data-bs-target="#ndt-pane" type="button" role="tab">
            NDT  <span class="badge bg-primary ms-2" id="ndt-count">{{ count($ndtCadCsv->ndt_components ?? []) }}</span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="cad-tab" data-bs-toggle="tab" data-bs-target="#cad-pane" type="button" role="tab">
            CAD  <span class="badge bg-success ms-2" id="cad-count">{{ count($ndtCadCsv->cad_components ?? []) }}</span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="paint-tab" data-bs-toggle="tab" data-bs-target="#paint-pane" type="button" role="tab">
            Paint  <span class="badge bg-info ms-2" id="paint-count">{{ count($ndtCadCsv->paint_components ?? []) }}</span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="stress-tab" data-bs-toggle="tab" data-bs-target="#stress-pane" type="button" role="tab">
            Stress  <span class="badge bg-warning ms-2" id="stress-count">{{ count($ndtCadCsv->stress_components ?? []) }}</span>
        </button>
    </li>
</ul>

<div class="tab-content" id="componentTabsContent">
    <!-- NDT Компоненты -->
    <div class="tab-pane fade show active" id="ndt-pane" role="tabpanel">
        <h5><strong></strong></h5>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5>NDT List</h5>
            <div>
                <button type="button" class="btn btn-success btn-sm" onclick="showAddNdtModal()">
                    <i class="fas fa-plus"></i> Add
                </button>
                <button type="button" class="btn btn-warning btn-sm" onclick="loadSnapshotFromStd('ndt')">
                    <i class="fas fa-sync"></i> Load from STD
                </button>
            </div>
        </div>

        <div class="table-responsive" style="max-height: calc(100vh - 320px); overflow-y: auto;">
            @php
                $ndtComponents = $ndtCadCsv->ndt_components ?? [];
                $hasManual = false;
                foreach ($ndtComponents as $component) {
                    if (isset($component['manual']) && $component['manual'] !== null && $component['manual'] !== '') {
                        $hasManual = true;
                        break;
                    }
                }
            @endphp
            <table class="table table-hover table-bordered dir-table align-middle bg-gradient" id="ndt-table">
                <thead class="table-dark" style="position: sticky; top: 0; z-index: 5;">
                    <tr>
                        <th class="text-primary text-center align-middle">IPL №</th>
                        <th class="text-primary text-center align-middle">Part Number</th>
                        <th class="text-primary text-center align-middle">Description</th>
                        <th class="text-primary text-center align-middle">EFF Code</th>
                        <th class="text-primary text-center align-middle">Process</th>
                        <th class="text-primary text-center align-middle">QTY</th>
                        @if($hasManual)
                            <th class="text-primary text-center align-middle">Manual</th>
                        @endif
                        <th class="text-primary text-center align-middle">Action</th>
                    </tr>
                </thead>
                <tbody id="ndt-tbody">
                    @php
                        $sortedNdtComponents = collect($ndtComponents)->sort(function($a, $b) {
                            $manualA = isset($a['manual']) && !empty($a['manual']) ? $a['manual'] : '';
                            $manualB = isset($b['manual']) && !empty($b['manual']) ? $b['manual'] : '';
                            $iplA = $a['ipl_num'] ?? '';
                            $iplB = $b['ipl_num'] ?? '';
                            $manualCompare = strnatcasecmp($manualA, $manualB);
                            if ($manualCompare !== 0) return $manualCompare;
                            return strnatcasecmp($iplA, $iplB);
                        })->values();
                    @endphp
                    @forelse($sortedNdtComponents as $displayIndex => $component)
                    @php $originalIndex = array_search($component, $ndtComponents); @endphp
                    <tr data-index="{{ $originalIndex }}" data-display-index="{{ $displayIndex }}">
                        <td>{{ $component['ipl_num'] }}</td>
                        <td>{{ $component['part_number'] }}</td>
                        <td>{{ $component['description'] }}</td>
                        <td>{{ $component['eff_code'] ?? '' }}</td>
                        <td>{{ $component['process'] }}</td>
                        <td>{{ $component['qty'] }}</td>
                        @if($hasManual)
                            <td>{{ $component['manual'] ?? '' }}</td>
                        @endif
                        <td>
                            <button class="btn btn-sm btn-primary me-1" onclick="editNdtComponent({{ $originalIndex }})" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="removeNdtComponent({{ $originalIndex }})" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $hasManual ? 8 : 7 }}" class="text-center text-muted">No NDT components</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- CAD Компоненты -->
    <div class="tab-pane fade" id="cad-pane" role="tabpanel">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5>CAD List</h5>
            <div>
                <button type="button" class="btn btn-success btn-sm" onclick="showAddCadModal()">
                    <i class="fas fa-plus"></i> Add
                </button>
                <button type="button" class="btn btn-warning btn-sm" onclick="loadSnapshotFromStd('cad')">
                    <i class="fas fa-sync"></i> Load from STD
                </button>
            </div>
        </div>

        <div class="table-responsive" style="max-height: calc(100vh - 320px); overflow-y: auto;">
            @php
                $cadComponents = $ndtCadCsv->cad_components ?? [];
                $hasManual = false;
                foreach ($cadComponents as $component) {
                    if (isset($component['manual']) && $component['manual'] !== null && $component['manual'] !== '') {
                        $hasManual = true;
                        break;
                    }
                }
            @endphp
            <table class="table table-hover table-bordered dir-table align-middle bg-gradient" id="cad-table">
                <thead class="table-dark" style="position: sticky; top: 0; z-index: 5;">
                    <tr>
                        <th class="text-primary text-center align-middle">IPL №</th>
                        <th class="text-primary text-center align-middle">Part Number</th>
                        <th class="text-primary text-center align-middle">Description</th>
                        <th class="text-primary text-center align-middle">EFF Code</th>
                        <th class="text-primary text-center align-middle">Process</th>
                        <th class="text-primary text-center align-middle">QTY</th>
                        @if($hasManual)
                            <th class="text-primary text-center align-middle">Manual</th>
                        @endif
                        <th class="text-primary text-center align-middle">Action</th>
                    </tr>
                </thead>
                <tbody id="cad-tbody">
                    @php
                        $sortedCadComponents = collect($cadComponents)->sort(function($a, $b) {
                            $manualA = isset($a['manual']) && !empty($a['manual']) ? $a['manual'] : '';
                            $manualB = isset($b['manual']) && !empty($b['manual']) ? $b['manual'] : '';
                            $iplA = $a['ipl_num'] ?? '';
                            $iplB = $b['ipl_num'] ?? '';
                            $manualCompare = strnatcasecmp($manualA, $manualB);
                            if ($manualCompare !== 0) return $manualCompare;
                            return strnatcasecmp($iplA, $iplB);
                        })->values();
                    @endphp
                    @forelse($sortedCadComponents as $displayIndex => $component)
                    @php $originalIndex = array_search($component, $cadComponents); @endphp
                    <tr data-index="{{ $originalIndex }}" data-display-index="{{ $displayIndex }}">
                        <td>{{ $component['ipl_num'] }}</td>
                        <td>{{ $component['part_number'] }}</td>
                        <td>{{ $component['description'] }}</td>
                        <td>{{ $component['eff_code'] ?? '' }}</td>
                        <td>{{ $component['process'] }}</td>
                        <td>{{ $component['qty'] }}</td>
                        @if($hasManual)
                            <td>{{ $component['manual'] ?? '' }}</td>
                        @endif
                        <td>
                            <button class="btn btn-sm btn-primary me-1" onclick="editCadComponent({{ $originalIndex }})" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="removeCadComponent({{ $originalIndex }})" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $hasManual ? 8 : 7 }}" class="text-center text-muted">No CAD components</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Paint Компоненты -->
    <div class="tab-pane fade" id="paint-pane" role="tabpanel">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5>Paint List</h5>
            <div>
                <button type="button" class="btn btn-success btn-sm" onclick="showAddPaintModal()">
                    <i class="fas fa-plus"></i> Add
                </button>
                <button type="button" class="btn btn-warning btn-sm" onclick="loadSnapshotFromStd('paint')">
                    <i class="fas fa-sync"></i> Load from STD
                </button>
            </div>
        </div>

        <div class="table-responsive" style="max-height: calc(100vh - 320px); overflow-y: auto;">
            @php
                $paintComponents = $ndtCadCsv->paint_components ?? [];
                $hasManual = false;
                foreach ($paintComponents as $component) {
                    if (isset($component['manual']) && $component['manual'] !== null && $component['manual'] !== '') {
                        $hasManual = true;
                        break;
                    }
                }
            @endphp
            <table class="table table-hover table-bordered dir-table align-middle bg-gradient" id="paint-table">
                <thead class="table-dark" style="position: sticky; top: 0; z-index: 5;">
                    <tr>
                        <th class="text-primary text-center align-middle">IPL №</th>
                        <th class="text-primary text-center align-middle">Part Number</th>
                        <th class="text-primary text-center align-middle">Description</th>
                        <th class="text-primary text-center align-middle">EFF Code</th>
                        <th class="text-primary text-center align-middle">Process</th>
                        <th class="text-primary text-center align-middle">QTY</th>
                        @if($hasManual)
                            <th class="text-primary text-center align-middle">Manual</th>
                        @endif
                        <th class="text-primary text-center align-middle">Action</th>
                    </tr>
                </thead>
                <tbody id="paint-tbody">
                    @php
                        $sortedPaintComponents = collect($paintComponents)->sort(function($a, $b) {
                            $manualA = isset($a['manual']) && !empty($a['manual']) ? $a['manual'] : '';
                            $manualB = isset($b['manual']) && !empty($b['manual']) ? $b['manual'] : '';
                            $iplA = $a['ipl_num'] ?? '';
                            $iplB = $b['ipl_num'] ?? '';
                            $manualCompare = strnatcasecmp($manualA, $manualB);
                            if ($manualCompare !== 0) return $manualCompare;
                            return strnatcasecmp($iplA, $iplB);
                        })->values();
                    @endphp
                    @forelse($sortedPaintComponents as $displayIndex => $component)
                    @php $originalIndex = array_search($component, $paintComponents); @endphp
                    <tr data-index="{{ $originalIndex }}" data-display-index="{{ $displayIndex }}">
                        <td>{{ $component['ipl_num'] }}</td>
                        <td>{{ $component['part_number'] }}</td>
                        <td>{{ $component['description'] }}</td>
                        <td>{{ $component['eff_code'] ?? '' }}</td>
                        <td>{{ $component['process'] }}</td>
                        <td>{{ $component['qty'] }}</td>
                        @if($hasManual)
                            <td>{{ $component['manual'] ?? '' }}</td>
                        @endif
                        <td>
                            <button class="btn btn-sm btn-primary me-1" onclick="editPaintComponent({{ $originalIndex }})" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="removePaintComponent({{ $originalIndex }})" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $hasManual ? 8 : 7 }}" class="text-center text-muted">No Paint components</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Stress Компоненты -->
    <div class="tab-pane fade" id="stress-pane" role="tabpanel">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5>Stress List</h5>
            <div>
                <button type="button" class="btn btn-success btn-sm" onclick="showAddStressModal()">
                    <i class="fas fa-plus"></i> Add
                </button>
                <button type="button" class="btn btn-warning btn-sm" onclick="loadSnapshotFromStd('stress')">
                    <i class="fas fa-sync"></i> Load from STD
                </button>
            </div>
        </div>

        <div class="table-responsive" style="max-height: calc(100vh - 320px); overflow-y: auto;">
            @php
                $stressComponents = $ndtCadCsv->stress_components ?? [];
                $hasManual = false;
                foreach ($stressComponents as $component) {
                    if (isset($component['manual']) && $component['manual'] !== null && $component['manual'] !== '') {
                        $hasManual = true;
                        break;
                    }
                }
            @endphp
            <table class="table table-hover table-bordered dir-table align-middle bg-gradient" id="stress-table">
                <thead class="table-dark" style="position: sticky; top: 0; z-index: 5;">
                    <tr>
                        <th class="text-primary text-center align-middle">IPL №</th>
                        <th class="text-primary text-center align-middle">Part Number</th>
                        <th class="text-primary text-center align-middle">Description</th>
                        <th class="text-primary text-center align-middle">EFF Code</th>
                        <th class="text-primary text-center align-middle">Process</th>
                        <th class="text-primary text-center align-middle">QTY</th>
                        @if($hasManual)
                            <th class="text-primary text-center align-middle">Manual</th>
                        @endif
                        <th class="text-primary text-center align-middle">Action</th>
                    </tr>
                </thead>
                <tbody id="stress-tbody">
                    @php
                        $sortedStressComponents = collect($stressComponents)->sort(function($a, $b) {
                            $manualA = isset($a['manual']) && !empty($a['manual']) ? $a['manual'] : '';
                            $manualB = isset($b['manual']) && !empty($b['manual']) ? $b['manual'] : '';
                            $iplA = $a['ipl_num'] ?? '';
                            $iplB = $b['ipl_num'] ?? '';
                            $manualCompare = strnatcasecmp($manualA, $manualB);
                            if ($manualCompare !== 0) return $manualCompare;
                            return strnatcasecmp($iplA, $iplB);
                        })->values();
                    @endphp
                    @forelse($sortedStressComponents as $displayIndex => $component)
                    @php $originalIndex = array_search($component, $stressComponents); @endphp
                    <tr data-index="{{ $originalIndex }}" data-display-index="{{ $displayIndex }}">
                        <td>{{ $component['ipl_num'] }}</td>
                        <td>{{ $component['part_number'] }}</td>
                        <td>{{ $component['description'] }}</td>
                        <td>{{ $component['eff_code'] ?? '' }}</td>
                        <td>{{ $component['process'] }}</td>
                        <td>{{ $component['qty'] }}</td>
                        @if($hasManual)
                            <td>{{ $component['manual'] ?? '' }}</td>
                        @endif
                        <td>
                            <button class="btn btn-sm btn-primary me-1" onclick="editStressComponent({{ $originalIndex }})" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="removeStressComponent({{ $originalIndex }})" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $hasManual ? 8 : 7 }}" class="text-center text-muted">No Stress components</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Модальное окно для добавления NDT компонента -->
<div class="modal fade" id="ndtModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add NDT Component</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="ndtForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="ndtComponent" class="form-label">Select component *</label>
                        <select class="form-control select2" id="ndtComponent" name="component_id" required>
                            <option value="">Select a component...</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="ndtQty" class="form-label">QTY *</label>
                        <input type="number" class="form-control" id="ndtQty" name="qty" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="ndtProcess" class="form-label">Process *</label>
                        <input type="text" class="form-control" id="ndtProcess" name="process" required>
                    </div>
                    <div class="mb-3">
                        <label for="ndtEffCode" class="form-label">EFF Code</label>
                        <input type="text" class="form-control" id="ndtEffCode" name="eff_code" placeholder="А, В — пусто = все">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Модальное окно для добавления CAD компонента -->
<div class="modal fade" id="cadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add CAD Component</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="cadForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="cadComponent" class="form-label">Select component *</label>
                        <select class="form-control select2" id="cadComponent" name="component_id" required>
                            <option value="">Select a component...</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="cadQty" class="form-label">QTY *</label>
                        <input type="number" class="form-control" id="cadQty" name="qty" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="cadProcess" class="form-label">Process *</label>
                        <select class="form-control select2" id="cadProcess" name="process" required>
                            <option value="">Select a process...</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="cadEffCode" class="form-label">EFF Code</label>
                        <input type="text" class="form-control" id="cadEffCode" name="eff_code" placeholder="А, В — пусто = все">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Модальное окно для добавления Stress компонента -->
<div class="modal fade" id="stressModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Stress Component</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="stressForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="stressComponent" class="form-label">Select component *</label>
                        <select class="form-control select2" id="stressComponent" name="component_id" required>
                            <option value="">Select a component...</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="stressQty" class="form-label">QTY *</label>
                        <input type="number" class="form-control" id="stressQty" name="qty" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="stressProcess" class="form-label">Process *</label>
                        <select class="form-control select2" id="stressProcess" name="process" required>
                            <option value="">Select a process...</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="stressEffCode" class="form-label">EFF Code</label>
                        <input type="text" class="form-control" id="stressEffCode" name="eff_code" placeholder="А, В — пусто = все">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Модальное окно для добавления Paint компонента -->
<div class="modal fade" id="paintModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Paint Component</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="paintForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="paintComponent" class="form-label">Select component *</label>
                        <select class="form-control select2" id="paintComponent" name="component_id" required>
                            <option value="">Select a component...</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="paintQty" class="form-label">QTY *</label>
                        <input type="number" class="form-control" id="paintQty" name="qty" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="paintProcess" class="form-label">Process *</label>
                        <select class="form-control select2" id="paintProcess" name="process" required>
                            <option value="">Select a process...</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="paintEffCode" class="form-label">EFF Code</label>
                        <input type="text" class="form-control" id="paintEffCode" name="eff_code" placeholder="А, В — пусто = все">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Модальное окно для редактирования NDT компонента -->
<div class="modal fade" id="ndtEditModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit NDT Component</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="ndtEditForm">
                <div class="modal-body">
                    <input type="hidden" id="ndtEditIndex" name="edit_index" value="">
                    <div class="alert alert-info">
                        <h6>Component Information:</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <strong>IPL:</strong> <span id="ndtCurrentIpl"></span><br>
                                <strong>Part Number:</strong> <span id="ndtCurrentPartNumber"></span>
                            </div>
                            <div class="col-md-6">
                                <strong>Description:</strong> <span id="ndtCurrentDescription"></span><br>
                                <strong>EFF Code:</strong> <span id="ndtCurrentEffCode"></span><br>
                                <strong>Process:</strong> <span id="ndtCurrentProcess"></span><br>
                                <strong>QTY:</strong> <span id="ndtCurrentQty"></span>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="ndtEditPartNumber" class="form-label">Part Number *</label>
                        <input type="text" class="form-control" id="ndtEditPartNumber" name="part_number" required>
                    </div>
                    <div class="mb-3">
                        <label for="ndtEditDescription" class="form-label">Description *</label>
                        <input type="text" class="form-control" id="ndtEditDescription" name="description" required>
                    </div>
                    <div class="mb-3">
                        <label for="ndtEditProcess" class="form-label">Process *</label>
                        <input type="text" class="form-control" id="ndtEditProcess" name="process" required>
                    </div>
                    <div class="mb-3">
                        <label for="ndtEditQty" class="form-label">QTY *</label>
                        <input type="number" class="form-control" id="ndtEditQty" name="qty" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="ndtEditEffCode" class="form-label">EFF Code</label>
                        <input type="text" class="form-control" id="ndtEditEffCode" name="eff_code" placeholder="А, В — пусто = все">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Модальное окно для редактирования CAD компонента -->
<div class="modal fade" id="cadEditModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit CAD Component</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="cadEditForm">
                <div class="modal-body">
                    <input type="hidden" id="cadEditIndex" name="edit_index" value="">
                    <div class="alert alert-info">
                        <h6>Component Information:</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <strong>IPL:</strong> <span id="cadCurrentIpl"></span><br>
                                <strong>Part Number:</strong> <span id="cadCurrentPartNumber"></span>
                            </div>
                            <div class="col-md-6">
                                <strong>Description:</strong> <span id="cadCurrentDescription"></span><br>
                                <strong>EFF Code:</strong> <span id="cadCurrentEffCode"></span><br>
                                <strong>Process:</strong> <span id="cadCurrentProcess"></span><br>
                                <strong>QTY:</strong> <span id="cadCurrentQty"></span>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="cadEditPartNumber" class="form-label">Part Number *</label>
                        <input type="text" class="form-control" id="cadEditPartNumber" name="part_number" required>
                    </div>
                    <div class="mb-3">
                        <label for="cadEditDescription" class="form-label">Description *</label>
                        <input type="text" class="form-control" id="cadEditDescription" name="description" required>
                    </div>
                    <div class="mb-3">
                        <label for="cadEditProcess" class="form-label">Process *</label>
                        <select class="form-control select2" id="cadEditProcess" name="process" required>
                            <option value="">Select a process...</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="cadEditQty" class="form-label">QTY *</label>
                        <input type="number" class="form-control" id="cadEditQty" name="qty" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="cadEditEffCode" class="form-label">EFF Code</label>
                        <input type="text" class="form-control" id="cadEditEffCode" name="eff_code" placeholder="А, В — пусто = все">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Модальное окно для редактирования Stress компонента -->
<div class="modal fade" id="stressEditModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Stress Component</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="stressEditForm">
                <div class="modal-body">
                    <input type="hidden" id="stressEditIndex" name="edit_index" value="">
                    <div class="alert alert-info">
                        <h6>Component Information:</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <strong>IPL:</strong> <span id="stressCurrentIpl"></span><br>
                                <strong>Part Number:</strong> <span id="stressCurrentPartNumber"></span>
                            </div>
                            <div class="col-md-6">
                                <strong>Description:</strong> <span id="stressCurrentDescription"></span><br>
                                <strong>EFF Code:</strong> <span id="stressCurrentEffCode"></span><br>
                                <strong>Process:</strong> <span id="stressCurrentProcess"></span><br>
                                <strong>QTY:</strong> <span id="stressCurrentQty"></span>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="stressEditPartNumber" class="form-label">Part Number *</label>
                        <input type="text" class="form-control" id="stressEditPartNumber" name="part_number" required>
                    </div>
                    <div class="mb-3">
                        <label for="stressEditDescription" class="form-label">Description *</label>
                        <input type="text" class="form-control" id="stressEditDescription" name="description" required>
                    </div>
                    <div class="mb-3">
                        <label for="stressEditProcess" class="form-label">Process *</label>
                        <select class="form-control select2" id="stressEditProcess" name="process" required>
                            <option value="">Select a process...</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="stressEditQty" class="form-label">QTY *</label>
                        <input type="number" class="form-control" id="stressEditQty" name="qty" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="stressEditEffCode" class="form-label">EFF Code</label>
                        <input type="text" class="form-control" id="stressEditEffCode" name="eff_code" placeholder="А, В — пусто = все">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Модальное окно для редактирования Paint компонента -->
<div class="modal fade" id="paintEditModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Paint Component</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="paintEditForm">
                <div class="modal-body">
                    <input type="hidden" id="paintEditIndex" name="edit_index" value="">
                    <div class="alert alert-info">
                        <h6>Component Information:</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <strong>IPL:</strong> <span id="paintCurrentIpl"></span><br>
                                <strong>Part Number:</strong> <span id="paintCurrentPartNumber"></span>
                            </div>
                            <div class="col-md-6">
                                <strong>Description:</strong> <span id="paintCurrentDescription"></span><br>
                                <strong>EFF Code:</strong> <span id="paintCurrentEffCode"></span><br>
                                <strong>Process:</strong> <span id="paintCurrentProcess"></span><br>
                                <strong>QTY:</strong> <span id="paintCurrentQty"></span>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="paintEditPartNumber" class="form-label">Part Number *</label>
                        <input type="text" class="form-control" id="paintEditPartNumber" name="part_number" required>
                    </div>
                    <div class="mb-3">
                        <label for="paintEditDescription" class="form-label">Description *</label>
                        <input type="text" class="form-control" id="paintEditDescription" name="description" required>
                    </div>
                    <div class="mb-3">
                        <label for="paintEditProcess" class="form-label">Process *</label>
                        <select class="form-control select2" id="paintEditProcess" name="process" required>
                            <option value="">Select a process...</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="paintEditQty" class="form-label">QTY *</label>
                        <input type="number" class="form-control" id="paintEditQty" name="qty" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="paintEditEffCode" class="form-label">EFF Code</label>
                        <input type="text" class="form-control" id="paintEditEffCode" name="eff_code" placeholder="А, В — пусто = все">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

@php
    $ndtHasManualCol = collect($ndtCadCsv->ndt_components ?? [])->contains(fn ($c) => ! empty($c['manual'] ?? null));
    $cadHasManualCol = collect($ndtCadCsv->cad_components ?? [])->contains(fn ($c) => ! empty($c['manual'] ?? null));
    $paintHasManualCol = collect($ndtCadCsv->paint_components ?? [])->contains(fn ($c) => ! empty($c['manual'] ?? null));
    $stressHasManualCol = collect($ndtCadCsv->stress_components ?? [])->contains(fn ($c) => ! empty($c['manual'] ?? null));
@endphp
<script>
window.__woNdtCadCols = {
    ndtManual: @json($ndtHasManualCol),
    cadManual: @json($cadHasManualCol),
    paintManual: @json($paintHasManualCol),
    stressManual: @json($stressHasManualCol)
};
</script>
@include('admin.ndt-cad-csv.partial-scripts')
</div>
