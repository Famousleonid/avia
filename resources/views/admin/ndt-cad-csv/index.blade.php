@extends('admin.master')

@section('title', 'Управление компонентами NDT/CAD - Workorder #' . $workorder->number)

<style>
    .container {
        max-width: 1080px;
    }
    .text-center {
        text-align: center;
        align-content: center;
    }
    .card{
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


/*!* Стили для Select2 в модальных окнах *!*/
/*.select2-container--default .select2-dropdown {*/
/*    z-index: 9999 !important;*/
/*}*/

/*.select2-container--default .select2-selection--single {*/
/*    height: 38px !important;*/
/*    border: 1px solid #ced4da !important;*/
/*    border-radius: 0.375rem !important;*/
/*}*/

/*.select2-container--default .select2-selection--single .select2-selection__rendered {*/
/*    color: #999999;*/
/*    line-height: 36px !important;*/
/*    padding-left: 12px !important;*/
/*}*/

/*.select2-container--default .select2-selection--single .select2-selection__arrow {*/
/*    height: 36px !important;*/
/*}*/

/* Убеждаемся, что dropdown отображается поверх модального окна */
.modal .select2-container {
    z-index: 9999 !important;
}
</style>


@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between">
                        <h3 class="card-title">
                            Modification of STD list Processes for W{{ $workorder->number }}
                        </h3>
                        <div class="card-tools">
                            <a href="{{ route('tdrs.show', ['id'=>$workorder->id]) }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Workorder
                            </a>
                        </div>
                    </div>

                </div>

                <div class="card-body">
                    <!-- Навигация по вкладкам -->
                    <ul class="nav nav-tabs" id="componentTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="ndt-tab" data-bs-toggle="tab" data-bs-target="#ndt-pane" type="button" role="tab">
                                NDT  <span class="badge bg-primary ms-2" id="ndt-count">{{ count($ndtCadCsv->ndt_components ?? []) }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="cad-tab" data-bs-toggle="tab" data-bs-target="#cad-pane" type="button" role="tab">
                                CAD  <span class="badge bg-success ms-2" id="cad-count">{{ count($ndtCadCsv->cad_components ??
                                 []) }}</span>
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
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5>NDT List</h5>
                                <div>
                                    <button type="button" class="btn btn-success btn-sm" onclick="showAddNdtModal()">
                                        <i class="fas fa-plus"></i> Add
                                    </button>
                                    <button type="button" class="btn btn-info btn-sm" onclick="importNdtFromCsv()">
                                        <i class="fas fa-file-import"></i> Upload CSV
                                    </button>
                                    <button type="button" class="btn btn-warning btn-sm" onclick="reloadFromManual('ndt')">
                                        <i class="fas fa-sync"></i> Reload CSV
                                    </button>
{{--                                    <button type="button" class="btn btn-secondary btn-sm" onclick="forceLoadFromManual('ndt')">--}}
{{--                                        <i class="fas fa-download"></i> Принудительная загрузка--}}
{{--                                    </button>--}}
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="ndt-table">
                                    <thead>
                                        <tr>
                                            <th>IPL №</th>
                                            <th>Part Number</th>
                                            <th>Description</th>
                                            <th>Process</th>
                                            <th>QTY</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                     <tbody id="ndt-tbody">
                                         @php
                                             $ndtComponents = $ndtCadCsv->ndt_components ?? [];
                                             $sortedNdtComponents = collect($ndtComponents)->sortBy('ipl_num', SORT_NATURAL)->values();
                                         @endphp
                                         @forelse($sortedNdtComponents as $displayIndex => $component)
                                         @php
                                             // Находим оригинальный индекс в исходном массиве
                                             $originalIndex = array_search($component, $ndtComponents);
                                         @endphp
                                         <tr data-index="{{ $originalIndex }}" data-display-index="{{ $displayIndex }}">
                                             <td>{{ $component['ipl_num'] }}</td>
                                             <td>{{ $component['part_number'] }}</td>
                                             <td>{{ $component['description'] }}</td>
                                             <td>{{ $component['process'] }}</td>
                                             <td>{{ $component['qty'] }}</td>
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
                                             <td colspan="6" class="text-center text-muted">No NDT components</td>
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
                                    <button type="button" class="btn btn-info btn-sm" onclick="importCadFromCsv()">
                                        <i class="fas fa-file-import"></i> Upload CSV
                                    </button>
                                    <button type="button" class="btn btn-warning btn-sm" onclick="reloadFromManual('cad')">
                                        <i class="fas fa-sync"></i> Reload CSV
                                    </button>
{{--                                    <button type="button" class="btn btn-secondary btn-sm" onclick="forceLoadFromManual('cad')">--}}
{{--                                        <i class="fas fa-download"></i> Принудительная загрузка--}}
{{--                                    </button>--}}
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="cad-table">
                                    <thead>
                                        <tr>
                                            <th>IPL №</th>
                                            <th>Part Number</th>
                                            <th>Description</th>
                                            <th>Process</th>
                                            <th>QTY</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                     <tbody id="cad-tbody">
                                         @php
                                             $cadComponents = $ndtCadCsv->cad_components ?? [];
                                             $sortedCadComponents = collect($cadComponents)->sortBy('ipl_num', SORT_NATURAL)->values();
                                         @endphp
                                         @forelse($sortedCadComponents as $displayIndex => $component)
                                         @php
                                             // Находим оригинальный индекс в исходном массиве
                                             $originalIndex = array_search($component, $cadComponents);
                                         @endphp
                                         <tr data-index="{{ $originalIndex }}" data-display-index="{{ $displayIndex }}">
                                             <td>{{ $component['ipl_num'] }}</td>
                                             <td>{{ $component['part_number'] }}</td>
                                             <td>{{ $component['description'] }}</td>
                                             <td>{{ $component['process'] }}</td>
                                             <td>{{ $component['qty'] }}</td>
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
                                             <td colspan="6" class="text-center text-muted">No CAD components</td>
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
                                    <button type="button" class="btn btn-info btn-sm" onclick="importPaintFromCsv()">
                                        <i class="fas fa-file-import"></i> Upload CSV
                                    </button>
                                    <button type="button" class="btn btn-warning btn-sm" onclick="reloadFromManual('paint')">
                                        <i class="fas fa-sync"></i> Reload CSV
                                    </button>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="paint-table">
                                    <thead>
                                        <tr>
                                            <th>IPL №</th>
                                            <th>Part Number</th>
                                            <th>Description</th>
                                            <th>Process</th>
                                            <th>QTY</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                     <tbody id="paint-tbody">
                                         @php
                                             $paintComponents = $ndtCadCsv->paint_components ?? [];
                                             $sortedPaintComponents = collect($paintComponents)->sortBy('ipl_num', SORT_NATURAL)->values();
                                         @endphp
                                         @forelse($sortedPaintComponents as $displayIndex => $component)
                                         @php
                                             // Находим оригинальный индекс в исходном массиве
                                             $originalIndex = array_search($component, $paintComponents);
                                         @endphp
                                         <tr data-index="{{ $originalIndex }}" data-display-index="{{ $displayIndex }}">
                                             <td>{{ $component['ipl_num'] }}</td>
                                             <td>{{ $component['part_number'] }}</td>
                                             <td>{{ $component['description'] }}</td>
                                             <td>{{ $component['process'] }}</td>
                                             <td>{{ $component['qty'] }}</td>
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
                                             <td colspan="6" class="text-center text-muted">No Paint components</td>
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
                                    <button type="button" class="btn btn-info btn-sm" onclick="importStressFromCsv()">
                                        <i class="fas fa-file-import"></i> Upload CSV
                                    </button>
                                    <button type="button" class="btn btn-warning btn-sm" onclick="reloadFromManual('stress')">
                                        <i class="fas fa-sync"></i> Reload CSV
                                    </button>
{{--                                    <button type="button" class="btn btn-secondary btn-sm" onclick="forceLoadFromManual('stress')">--}}
{{--                                        <i class="fas fa-download"></i> Принудительная загрузка--}}
{{--                                    </button>--}}
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="stress-table">
                                    <thead>
                                        <tr>
                                            <th>IPL №</th>
                                            <th>Part Number</th>
                                            <th>Description</th>
                                            <th>Process</th>
                                            <th>QTY</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                     <tbody id="stress-tbody">
                                         @php
                                             $stressComponents = $ndtCadCsv->stress_components ?? [];
                                             $sortedStressComponents = collect($stressComponents)->sortBy('ipl_num', SORT_NATURAL)->values();
                                         @endphp
                                         @forelse($sortedStressComponents as $displayIndex => $component)
                                         @php
                                             // Находим оригинальный индекс в исходном массиве
                                             $originalIndex = array_search($component, $stressComponents);
                                         @endphp
                                         <tr data-index="{{ $originalIndex }}" data-display-index="{{ $displayIndex }}">
                                             <td>{{ $component['ipl_num'] }}</td>
                                             <td>{{ $component['part_number'] }}</td>
                                             <td>{{ $component['description'] }}</td>
                                             <td>{{ $component['process'] }}</td>
                                             <td>{{ $component['qty'] }}</td>
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
                                             <td colspan="6" class="text-center text-muted">No Stress components</td>
                                         </tr>
                                         @endforelse
                                     </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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

                    <!-- Информация из JSON -->
                    <div class="alert alert-info">
                        <h6>Component Information:</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <strong>IPL:</strong> <span id="ndtCurrentIpl"></span><br>
                                <strong>Part Number:</strong> <span id="ndtCurrentPartNumber"></span>
                            </div>
                            <div class="col-md-6">
                                <strong>Description:</strong> <span id="ndtCurrentDescription"></span><br>
                                <strong>Process:</strong> <span id="ndtCurrentProcess"></span><br>
                                <strong>QTY:</strong> <span id="ndtCurrentQty"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Редактируемые поля -->
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

                    <!-- Информация из JSON -->
                    <div class="alert alert-info">
                        <h6>Component Information:</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <strong>IPL:</strong> <span id="cadCurrentIpl"></span><br>
                                <strong>Part Number:</strong> <span id="cadCurrentPartNumber"></span>
                            </div>
                            <div class="col-md-6">
                                <strong>Description:</strong> <span id="cadCurrentDescription"></span><br>
                                <strong>Process:</strong> <span id="cadCurrentProcess"></span><br>
                                <strong>QTY:</strong> <span id="cadCurrentQty"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Редактируемые поля -->
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

                    <!-- Информация из JSON -->
                    <div class="alert alert-info">
                        <h6>Component Information:</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <strong>IPL:</strong> <span id="stressCurrentIpl"></span><br>
                                <strong>Part Number:</strong> <span id="stressCurrentPartNumber"></span>
                            </div>
                            <div class="col-md-6">
                                <strong>Description:</strong> <span id="stressCurrentDescription"></span><br>
                                <strong>Process:</strong> <span id="stressCurrentProcess"></span><br>
                                <strong>QTY:</strong> <span id="stressCurrentQty"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Редактируемые поля -->
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

                    <!-- Информация из JSON -->
                    <div class="alert alert-info">
                        <h6>Component Information:</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <strong>IPL:</strong> <span id="paintCurrentIpl"></span><br>
                                <strong>Part Number:</strong> <span id="paintCurrentPartNumber"></span>
                            </div>
                            <div class="col-md-6">
                                <strong>Description:</strong> <span id="paintCurrentDescription"></span><br>
                                <strong>Process:</strong> <span id="paintCurrentProcess"></span><br>
                                <strong>QTY:</strong> <span id="paintCurrentQty"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Редактируемые поля -->
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
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Модальное окно для импорта CSV -->
<div class="modal fade" id="csvImportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Importing components from CSV</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="csvImportForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="csvType" class="form-label">Component type *</label>
                        <select class="form-control" id="csvType" required>
                            <option value="">Select type</option>
                            <option value="ndt">NDT</option>
                            <option value="cad">CAD</option>
                            <option value="paint">Paint</option>
                            <option value="stress">Stress</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="csvFile" class="form-label">CSV file *</label>
                        <input type="file" class="form-control" id="csvFile" accept=".csv,.txt" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Глобальная функция для ожидания загрузки jQuery
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

let allComponents = [];
let cadProcesses = [];
let stressProcesses = [];

// Функция для динамического обновления таблицы NDT
function updateNdtTable(components) {
    const tbody = $('#ndt-tbody');
    tbody.empty();

    if (components.length === 0) {
        tbody.append('<tr><td colspan="6" class="text-center text-muted">No NDT components</td></tr>');
        $('#ndt-count').text('0');
        return;
    }

    // Сортируем компоненты по IPL номеру
    const sortedComponents = components.sort((a, b) =>
        a.ipl_num.localeCompare(b.ipl_num, undefined, {numeric: true, sensitivity: 'base'})
    );

    sortedComponents.forEach((component, displayIndex) => {
        const originalIndex = components.indexOf(component);
        const row = `
            <tr data-index="${originalIndex}" data-display-index="${displayIndex}">
                <td>${component.ipl_num}</td>
                <td>${component.part_number}</td>
                <td>${component.description}</td>
                <td>${component.process}</td>
                <td>${component.qty}</td>
                <td>
                    <button class="btn btn-sm btn-primary me-1" onclick="editNdtComponent(${originalIndex})" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="removeNdtComponent(${originalIndex})" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        tbody.append(row);
    });

    // Обновляем счетчик
    $('#ndt-count').text(components.length);
}

// Функция для динамического обновления таблицы CAD
function updateCadTable(components) {
    const tbody = $('#cad-tbody');
    tbody.empty();

    if (components.length === 0) {
        tbody.append('<tr><td colspan="6" class="text-center text-muted">No CAD components</td></tr>');
        $('#cad-count').text('0');
        return;
    }

    // Сортируем компоненты по IPL номеру
    const sortedComponents = components.sort((a, b) =>
        a.ipl_num.localeCompare(b.ipl_num, undefined, {numeric: true, sensitivity: 'base'})
    );

    sortedComponents.forEach((component, displayIndex) => {
        const originalIndex = components.indexOf(component);
        const row = `
            <tr data-index="${originalIndex}" data-display-index="${displayIndex}">
                <td>${component.ipl_num}</td>
                <td>${component.part_number}</td>
                <td>${component.description}</td>
                <td>${component.process}</td>
                <td>${component.qty}</td>
                <td>
                    <button class="btn btn-sm btn-primary me-1" onclick="editCadComponent(${originalIndex})" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="removeCadComponent(${originalIndex})" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        tbody.append(row);
    });

    // Обновляем счетчик
    $('#cad-count').text(components.length);
}

// Функция для динамического обновления таблицы Stress
function updateStressTable(components) {
    const tbody = $('#stress-tbody');
    tbody.empty();

    if (components.length === 0) {
        tbody.append('<tr><td colspan="6" class="text-center text-muted">No Stress components</td></tr>');
        $('#stress-count').text('0');
        return;
    }

    // Сортируем компоненты по IPL номеру
    const sortedComponents = components.sort((a, b) =>
        a.ipl_num.localeCompare(b.ipl_num, undefined, {numeric: true, sensitivity: 'base'})
    );

    sortedComponents.forEach((component, displayIndex) => {
        const originalIndex = components.indexOf(component);
        const row = `
            <tr data-index="${originalIndex}" data-display-index="${displayIndex}">
                <td>${component.ipl_num}</td>
                <td>${component.part_number}</td>
                <td>${component.description}</td>
                <td>${component.process}</td>
                <td>${component.qty}</td>
                <td>
                    <button class="btn btn-sm btn-primary me-1" onclick="editStressComponent(${originalIndex})" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="removeStressComponent(${originalIndex})" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        tbody.append(row);
    });

    // Обновляем счетчик
    $('#stress-count').text(components.length);
}

// Функция для динамического обновления таблицы Paint
function updatePaintTable(components) {
    const tbody = $('#paint-tbody');
    tbody.empty();
    if (components.length === 0) {
        tbody.append('<tr><td colspan="6" class="text-center text-muted">No Paint components</td></tr>');
        $('#paint-count').text(0);
        return;
    }

    // Сортируем компоненты для отображения
    const sortedComponents = [...components].sort(function(a, b) {
        return a.ipl_num.localeCompare(b.ipl_num, undefined, {numeric: true, sensitivity: 'base'});
    });

    sortedComponents.forEach((component, displayIndex) => {
        // Находим оригинальный индекс в исходном массиве
        const originalIndex = components.indexOf(component);
        const row = `
            <tr data-index="${originalIndex}" data-display-index="${displayIndex}">
                <td>${component.ipl_num}</td>
                <td>${component.part_number}</td>
                <td>${component.description}</td>
                <td>${component.process}</td>
                <td>${component.qty}</td>
                <td>
                    <button class="btn btn-sm btn-primary me-1" onclick="editPaintComponent(${originalIndex})" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="removePaintComponent(${originalIndex})" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
    $('#paint-count').text(components.length);
}

// Функция для уведомлений
function showNotification(message, type = 'info') {
    const notification = $(`
        <div class="alert alert-${type} alert-dismissible fade show position-fixed"
             style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `);

    $('body').append(notification);

    setTimeout(() => {
        notification.alert('close');
    }, 3000);
}

// Определяем функции сразу в глобальной области видимости
window.showAddNdtModal = function() {
    console.log('showAddNdtModal called');
    // Простая проверка jQuery
    if (typeof $ !== 'undefined') {
        $('#ndtForm')[0].reset();
        $('#ndtEditIndex').val(''); // Сбрасываем индекс редактирования
        $('#ndtComponent').val('').trigger('change');
        $('#ndtProcess').val('');
        $('#ndtQty').val('');
        $('#ndtModalTitle').text('Add NDT Component'); // Сбрасываем заголовок
        $('#ndtSubmitBtn').text('Add'); // Меняем текст кнопки
        $('#ndtJsonInfo').hide(); // Скрываем информацию из JSON
        $('#ndtEditFields').hide(); // Скрываем поля редактирования
        $('#ndtAddFields').show(); // Показываем поля добавления

        // Инициализируем Select2 для модального окна
        if (typeof $.fn.select2 !== 'undefined') {
            $('#ndtComponent').select2({
                placeholder: 'Select a component...',
                allowClear: true,
                width: '100%',
                dropdownParent: $('#ndtModal')
            });
        }

        $('#ndtModal').modal('show');
    } else {
        console.log('jQuery not loaded yet, using fallback');
        // Fallback без jQuery
        document.getElementById('ndtForm').reset();
        document.getElementById('ndtEditIndex').value = '';
        document.getElementById('ndtModalTitle').textContent = 'Add NDT Component';
        document.getElementById('ndtModal').style.display = 'block';
        document.getElementById('ndtModal').classList.add('show');
    }
};

window.showAddCadModal = function() {
    console.log('showAddCadModal called');
    // Простая проверка jQuery
    if (typeof $ !== 'undefined') {
        $('#cadForm')[0].reset();
        $('#cadEditIndex').val(''); // Сбрасываем индекс редактирования
        $('#cadComponent').val('').trigger('change');
        $('#cadProcess').val('').trigger('change');
        $('#cadQty').val('');
        $('#cadModalTitle').text('Add CAD Component'); // Сбрасываем заголовок
        $('#cadSubmitBtn').text('Add'); // Меняем текст кнопки
        $('#cadJsonInfo').hide(); // Скрываем информацию из JSON
        $('#cadEditFields').hide(); // Скрываем поля редактирования
        $('#cadAddFields').show(); // Показываем поля добавления

        // Инициализируем Select2 для модального окна
        if (typeof $.fn.select2 !== 'undefined') {
            $('#cadComponent').select2({
                placeholder: 'Select a component...',
                allowClear: true,
                width: '100%',
                dropdownParent: $('#cadModal')
            });
            $('#cadProcess').select2({
                placeholder: 'Select a process...',
                allowClear: true,
                width: '100%',
                dropdownParent: $('#cadModal')
            });
        }

        $('#cadModal').modal('show');
    } else {
        console.log('jQuery not loaded yet, using fallback');
        // Fallback без jQuery
        document.getElementById('cadForm').reset();
        document.getElementById('cadEditIndex').value = '';
        document.getElementById('cadModalTitle').textContent = 'Add CAD Component';
        document.getElementById('cadModal').style.display = 'block';
        document.getElementById('cadModal').classList.add('show');
    }
};

window.showAddPaintModal = function() {
    console.log('showAddPaintModal called');
    // Простая проверка jQuery
    if (typeof $ !== 'undefined') {
        $('#paintForm')[0].reset();
        $('#paintEditIndex').val(''); // Сбрасываем индекс редактирования
        $('#paintComponent').val('').trigger('change');
        $('#paintProcess').val('').trigger('change');
        $('#paintQty').val('');
        $('#paintModalTitle').text('Add Paint Component'); // Сбрасываем заголовок
        $('#paintSubmitBtn').text('Add'); // Меняем текст кнопки
        $('#paintJsonInfo').hide(); // Скрываем информацию из JSON
        $('#paintEditFields').hide(); // Скрываем поля редактирования
        $('#paintAddFields').show(); // Показываем поля добавления

        // Инициализируем Select2 для модального окна
        if (typeof $.fn.select2 !== 'undefined') {
            $('#paintComponent').select2({
                placeholder: 'Select a component...',
                allowClear: true,
                width: '100%',
                dropdownParent: $('#paintModal')
            });
            $('#paintProcess').select2({
                placeholder: 'Select a process...',
                allowClear: true,
                width: '100%',
                dropdownParent: $('#paintModal')
            });
        }

        $('#paintModal').modal('show');
    } else {
        console.log('jQuery not loaded yet, using fallback');
        // Fallback без jQuery
        document.getElementById('paintForm').reset();
        document.getElementById('paintEditIndex').value = '';
        document.getElementById('paintModalTitle').textContent = 'Add Paint Component';
        document.getElementById('paintModal').style.display = 'block';
        document.getElementById('paintModal').classList.add('show');
    }
};

window.showAddStressModal = function() {
    console.log('showAddStressModal called');
    // Простая проверка jQuery
    if (typeof $ !== 'undefined') {
        $('#stressForm')[0].reset();
        $('#stressEditIndex').val(''); // Сбрасываем индекс редактирования
        $('#stressComponent').val('').trigger('change');
        $('#stressProcess').val('');
        $('#stressQty').val('');
        $('#stressModalTitle').text('Add Stress Component'); // Сбрасываем заголовок
        $('#stressSubmitBtn').text('Add'); // Меняем текст кнопки
        $('#stressJsonInfo').hide(); // Скрываем информацию из JSON
        $('#stressEditFields').hide(); // Скрываем поля редактирования
        $('#stressAddFields').show(); // Показываем поля добавления

        // Инициализируем Select2 для модального окна
        if (typeof $.fn.select2 !== 'undefined') {
            $('#stressComponent').select2({
                placeholder: 'Select a component...',
                allowClear: true,
                width: '100%',
                dropdownParent: $('#stressModal')
            });
            $('#stressProcess').select2({
                placeholder: 'Select a process...',
                allowClear: true,
                width: '100%',
                dropdownParent: $('#stressModal')
            });
        }

        $('#stressModal').modal('show');
    } else {
        console.log('jQuery not loaded yet, using fallback');
        // Fallback без jQuery
        document.getElementById('stressForm').reset();
        document.getElementById('stressEditIndex').value = '';
        document.getElementById('stressModalTitle').textContent = 'Add Stress Component';
        document.getElementById('stressModal').style.display = 'block';
        document.getElementById('stressModal').classList.add('show');
    }
};

// Загрузка данных при инициализации - ждем загрузки jQuery
function initializeWhenReady() {
    if (typeof $ !== 'undefined') {
        console.log('Document ready, initializing...');
        console.log('jQuery version:', $.fn.jquery);
        console.log('Bootstrap modal available:', typeof $.fn.modal);

        loadComponents();
        loadCadProcesses();
        loadStressProcesses();
        loadPaintProcesses();

        // Инициализация Select2 (если доступен)
        if (typeof $.fn.select2 !== 'undefined') {
            // Инициализируем Select2 для модальных окон
            $('#ndtModal #ndtComponent').select2({
                placeholder: 'Select a component...',
                allowClear: true,
                width: '100%',
                dropdownParent: $('#ndtModal')
            });

            $('#cadModal #cadComponent').select2({
                placeholder: 'Select a component...',
                allowClear: true,
                width: '100%',
                dropdownParent: $('#cadModal')
            });

            $('#cadModal #cadProcess').select2({
                placeholder: 'Select a process...',
                allowClear: true,
                width: '100%',
                dropdownParent: $('#cadModal')
            });

            $('#cadEditModal #cadEditProcess').select2({
                placeholder: 'Select a process...',
                allowClear: true,
                width: '100%',
                dropdownParent: $('#cadEditModal')
            });

            $('#stressModal #stressComponent').select2({
                placeholder: 'Select a component...',
                allowClear: true,
                width: '100%',
                dropdownParent: $('#stressModal')
            });

            $('#stressModal #stressProcess').select2({
                placeholder: 'Select a process...',
                allowClear: true,
                width: '100%',
                dropdownParent: $('#stressModal')
            });

            $('#stressEditModal #stressEditProcess').select2({
                placeholder: 'Select a process...',
                allowClear: true,
                width: '100%',
                dropdownParent: $('#stressEditModal')
            });

            console.log('Select2 initialized for all modals');
        } else {
            console.log('Select2 not available, using regular select');
        }

        console.log('Initialization complete');

        // Обработчики для автоматического заполнения полей
        $('#ndtComponent').on('change', function() {
            const selectedOption = $(this).find('option:selected');
            if (selectedOption.val()) {
                $('#ndtPartNumber').val(selectedOption.data('part-number') || '');
                $('#ndtDescription').val(selectedOption.data('description') || '');
                $('#ndtQty').val(selectedOption.data('units-assy') || 1);
            }
        });

        $('#cadComponent').on('change', function() {
            const selectedOption = $(this).find('option:selected');
            if (selectedOption.val()) {
                $('#cadPartNumber').val(selectedOption.data('part-number') || '');
                $('#cadDescription').val(selectedOption.data('description') || '');
                $('#cadQty').val(selectedOption.data('units-assy') || 1);
            }
        });

        $('#stressComponent').on('change', function() {
            const selectedOption = $(this).find('option:selected');
            if (selectedOption.val()) {
                $('#stressPartNumber').val(selectedOption.data('part-number') || '');
                $('#stressDescription').val(selectedOption.data('description') || '');
                $('#stressQty').val(selectedOption.data('units-assy') || 1);
            }
        });

        // Обработчики форм
        $('#ndtForm').on('submit', function(e) {
            e.preventDefault();
            console.log('NDT form submitted');

            const selectedComponent = $('#ndtComponent option:selected');
            if (!selectedComponent.val()) {
                alert('Please select a component');
                return;
            }

            const data = {
                component_id: selectedComponent.val(),
                ipl_num: selectedComponent.data('ipl-num'),
                part_number: selectedComponent.data('part-number'),
                description: selectedComponent.data('description'),
                process: $('#ndtProcess').val(),
                qty: parseInt($('#ndtQty').val()),
                _token: $('meta[name="csrf-token"]').attr('content')
            };

            console.log('Sending NDT add data:', data);

            $.post(`{{ route('ndt-cad-csv.add-ndt', ['workorder' => $workorder->id]) }}`, data).done(function(response) {
                console.log('NDT add response:', response);
                if (response.success) {
                    // Обновляем локальный массив
                    ndtComponents.push(data);

                    // Динамически обновляем таблицу
                    updateNdtTable(ndtComponents);

                    // Закрываем модальное окно
                    $('#ndtModal').modal('hide');

                    // Сбрасываем форму
                    $('#ndtForm')[0].reset();
                    $('#ndtComponent').val('').trigger('change');

                    // Показываем уведомление
                    showNotification('NDT компонент успешно добавлен', 'success');
                } else {
                    alert('Error: ' + response.message);
                }
            }).fail(function(xhr) {
                console.error('NDT add error:', xhr.responseText);
                alert('Error adding component');
            });
        });

        $('#cadForm').on('submit', function(e) {
            e.preventDefault();
            console.log('CAD form submitted');

            const selectedComponent = $('#cadComponent option:selected');
            if (!selectedComponent.val()) {
                alert('Please select a component');
                return;
            }

            if (!$('#cadProcess').val()) {
                alert('Please select a process');
                return;
            }

            const data = {
                component_id: selectedComponent.val(),
                ipl_num: selectedComponent.data('ipl-num'),
                part_number: selectedComponent.data('part-number'),
                description: selectedComponent.data('description'),
                process: $('#cadProcess').val(),
                qty: parseInt($('#cadQty').val()),
                _token: $('meta[name="csrf-token"]').attr('content')
            };

            console.log('Sending CAD add data:', data);

            $.post(`{{ route('ndt-cad-csv.add-cad', ['workorder' => $workorder->id]) }}`, data).done(function(response) {
                console.log('CAD add response:', response);
                if (response.success) {
                    // Обновляем локальный массив
                    cadComponents.push(data);

                    // Динамически обновляем таблицу
                    updateCadTable(cadComponents);

                    // Закрываем модальное окно
                    $('#cadModal').modal('hide');

                    // Сбрасываем форму
                    $('#cadForm')[0].reset();
                    $('#cadComponent').val('').trigger('change');
                    $('#cadProcess').val('').trigger('change');

                    // Показываем уведомление
                    showNotification('CAD компонент успешно добавлен', 'success');
                } else {
                    alert('Error: ' + response.message);
                }
            }).fail(function(xhr) {
                console.error('CAD add error:', xhr.responseText);
                alert('Error adding component');
            });
        });

        $('#paintForm').on('submit', function(e) {
            e.preventDefault();
            console.log('Paint form submitted');

            const selectedComponent = $('#paintComponent option:selected');
            if (!selectedComponent.val()) {
                alert('Please select a component');
                return;
            }

            if (!$('#paintProcess').val()) {
                alert('Please select a process');
                return;
            }

            const data = {
                component_id: selectedComponent.val(),
                ipl_num: selectedComponent.data('ipl-num'),
                part_number: selectedComponent.data('part-number'),
                description: selectedComponent.data('description'),
                process: $('#paintProcess').val(),
                qty: parseInt($('#paintQty').val()),
                _token: $('meta[name="csrf-token"]').attr('content')
            };

            console.log('Sending Paint add data:', data);

            $.post(`{{ route('ndt-cad-csv.add-paint', ['workorder' => $workorder->id]) }}`, data).done(function(response) {
                console.log('Paint add response:', response);
                if (response.success) {
                    // Обновляем локальный массив
                    paintComponents.push(data);

                    // Динамически обновляем таблицу
                    updatePaintTable(paintComponents);

                    // Закрываем модальное окно
                    $('#paintModal').modal('hide');

                    // Сбрасываем форму
                    $('#paintForm')[0].reset();
                    $('#paintComponent').val('').trigger('change');
                    $('#paintProcess').val('').trigger('change');

                    // Показываем уведомление
                    showNotification('Paint компонент успешно добавлен', 'success');
                } else {
                    alert('Error: ' + response.message);
                }
            }).fail(function(xhr) {
                console.error('Paint add error:', xhr.responseText);
                alert('Error adding component');
            });
        });

        $('#stressForm').on('submit', function(e) {
            e.preventDefault();
            console.log('Stress form submitted');

            const selectedComponent = $('#stressComponent option:selected');
            if (!selectedComponent.val()) {
                alert('Please select a component');
                return;
            }

            if (!$('#stressProcess').val()) {
                alert('Please select a process');
                return;
            }

            const data = {
                component_id: selectedComponent.val(),
                ipl_num: selectedComponent.data('ipl-num'),
                part_number: selectedComponent.data('part-number'),
                description: selectedComponent.data('description'),
                process: $('#stressProcess').val(),
                qty: parseInt($('#stressQty').val()),
                _token: $('meta[name="csrf-token"]').attr('content')
            };

            console.log('Sending Stress add data:', data);

            $.post(`{{ route('ndt-cad-csv.add-stress', ['workorder' => $workorder->id]) }}`, data).done(function(response) {
                console.log('Stress add response:', response);
                if (response.success) {
                    // Обновляем локальный массив
                    stressComponents.push(data);

                    // Динамически обновляем таблицу
                    updateStressTable(stressComponents);

                    // Закрываем модальное окно
                    $('#stressModal').modal('hide');

                    // Сбрасываем форму
                    $('#stressForm')[0].reset();
                    $('#stressComponent').val('').trigger('change');
                    $('#stressProcess').val('').trigger('change');

                    // Показываем уведомление
                    showNotification('Stress компонент успешно добавлен', 'success');
                } else {
                    alert('Error: ' + response.message);
                }
            }).fail(function(xhr) {
                console.error('Stress add error:', xhr.responseText);
                alert('Error adding component');
            });
        });

        // Обработчики форм редактирования
        $('#ndtEditForm').on('submit', function(e) {
            e.preventDefault();
            console.log('NDT Edit form submitted');

            const editIndex = $('#ndtEditIndex').val();
            if (!editIndex) {
                alert('Edit index not found');
                return;
            }

            const data = {
                index: editIndex,
                part_number: $('#ndtEditPartNumber').val(),
                description: $('#ndtEditDescription').val(),
                process: $('#ndtEditProcess').val(),
                qty: parseInt($('#ndtEditQty').val()),
                _token: $('meta[name="csrf-token"]').attr('content')
            };

            console.log('Sending NDT edit data:', data);

            $.post(`{{ route('ndt-cad-csv.edit-ndt', ['workorder' => $workorder->id]) }}`, data).done(function(response) {
                console.log('NDT edit response:', response);
                if (response.success) {
                    // Обновляем локальный массив
                    ndtComponents[editIndex] = {
                        ...ndtComponents[editIndex],
                        part_number: data.part_number,
                        description: data.description,
                        process: data.process,
                        qty: data.qty
                    };

                    // Динамически обновляем таблицу
                    updateNdtTable(ndtComponents);

                    // Закрываем модальное окно
                    $('#ndtEditModal').modal('hide');

                    // Показываем уведомление
                    showNotification('NDT компонент успешно обновлен', 'success');
                } else {
                    alert('Error: ' + response.message);
                }
            }).fail(function(xhr) {
                console.error('NDT edit error:', xhr.responseText);
                alert('Error saving changes');
            });
        });

        $('#cadEditForm').on('submit', function(e) {
            e.preventDefault();
            console.log('CAD Edit form submitted');

            const editIndex = $('#cadEditIndex').val();
            if (!editIndex) {
                alert('Edit index not found');
                return;
            }

            const data = {
                index: editIndex,
                part_number: $('#cadEditPartNumber').val(),
                description: $('#cadEditDescription').val(),
                process: $('#cadEditProcess').val(),
                qty: parseInt($('#cadEditQty').val()),
                _token: $('meta[name="csrf-token"]').attr('content')
            };

            console.log('Sending CAD edit data:', data);

            $.post(`{{ route('ndt-cad-csv.edit-cad', ['workorder' => $workorder->id]) }}`, data).done(function(response) {
                console.log('CAD edit response:', response);
                if (response.success) {
                    // Обновляем локальный массив
                    cadComponents[editIndex] = {
                        ...cadComponents[editIndex],
                        part_number: data.part_number,
                        description: data.description,
                        process: data.process,
                        qty: data.qty
                    };

                    // Динамически обновляем таблицу
                    updateCadTable(cadComponents);

                    // Закрываем модальное окно
                    $('#cadEditModal').modal('hide');

                    // Показываем уведомление
                    showNotification('CAD компонент успешно обновлен', 'success');
                } else {
                    alert('Error: ' + response.message);
                }
            }).fail(function(xhr) {
                console.error('CAD edit error:', xhr.responseText);
                alert('Error saving changes');
            });
        });

        $('#paintEditForm').on('submit', function(e) {
            e.preventDefault();
            console.log('Paint Edit form submitted');

            const editIndex = $('#paintEditIndex').val();
            if (!editIndex) {
                alert('Edit index not found');
                return;
            }

            const data = {
                index: editIndex,
                part_number: $('#paintEditPartNumber').val(),
                description: $('#paintEditDescription').val(),
                process: $('#paintEditProcess').val(),
                qty: parseInt($('#paintEditQty').val()),
                _token: $('meta[name="csrf-token"]').attr('content')
            };

            console.log('Sending Paint edit data:', data);

            $.post(`{{ route('ndt-cad-csv.edit-paint', ['workorder' => $workorder->id]) }}`, data).done(function(response) {
                console.log('Paint edit response:', response);
                if (response.success) {
                    // Обновляем локальный массив
                    paintComponents[editIndex] = {
                        ...paintComponents[editIndex],
                        part_number: data.part_number,
                        description: data.description,
                        process: data.process,
                        qty: data.qty
                    };

                    // Динамически обновляем таблицу
                    updatePaintTable(paintComponents);

                    // Закрываем модальное окно
                    $('#paintEditModal').modal('hide');

                    // Показываем уведомление
                    showNotification('Paint компонент успешно обновлен', 'success');
                } else {
                    alert('Error: ' + response.message);
                }
            }).fail(function(xhr) {
                console.error('Paint edit error:', xhr.responseText);
                alert('Error saving changes');
            });
        });

        $('#stressEditForm').on('submit', function(e) {
            e.preventDefault();
            console.log('Stress Edit form submitted');

            const editIndex = $('#stressEditIndex').val();
            if (!editIndex) {
                alert('Edit index not found');
                return;
            }

            const data = {
                index: editIndex,
                part_number: $('#stressEditPartNumber').val(),
                description: $('#stressEditDescription').val(),
                process: $('#stressEditProcess').val(),
                qty: parseInt($('#stressEditQty').val()),
                _token: $('meta[name="csrf-token"]').attr('content')
            };

            console.log('Sending Stress edit data:', data);

            $.post(`{{ route('ndt-cad-csv.edit-stress', ['workorder' => $workorder->id]) }}`, data).done(function(response) {
                console.log('Stress edit response:', response);
                if (response.success) {
                    // Обновляем локальный массив
                    stressComponents[editIndex] = {
                        ...stressComponents[editIndex],
                        part_number: data.part_number,
                        description: data.description,
                        process: data.process,
                        qty: data.qty
                    };

                    // Динамически обновляем таблицу
                    updateStressTable(stressComponents);

                    // Закрываем модальное окно
                    $('#stressEditModal').modal('hide');

                    // Показываем уведомление
                    showNotification('Stress компонент успешно обновлен', 'success');
                } else {
                    alert('Error: ' + response.message);
                }
            }).fail(function(xhr) {
                console.error('Stress edit error:', xhr.responseText);
                alert('Error saving changes');
            });
        });

        $('#csvImportForm').on('submit', function(e) {
            e.preventDefault();
            const formData = new FormData();
            formData.append('type', $('#csvType').val());
            formData.append('csv_file', $('#csvFile')[0].files[0]);
            formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

            $.ajax({
                url: `{{ route('ndt-cad-csv.import', ['workorder' => $workorder->id]) }}`,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
            if (response.success) {
                        $('#csvImportModal').modal('hide');

                        // Обновляем соответствующий массив компонентов
                        if (response.type === 'ndt') {
                            ndtComponents = response.components || [];
                            updateNdtTable(ndtComponents);
                        } else if (response.type === 'cad') {
                            cadComponents = response.components || [];
                            updateCadTable(cadComponents);
                        } else if (response.type === 'stress') {
                            stressComponents = response.components || [];
                            updateStressTable(stressComponents);
                        }

                        showNotification(`Successfully imported ${response.count} components`, 'success');
                    } else {
                        alert('Error: ' + response.message);
                    }
                }
            });
        });

        // Инициализируем таблицы с существующими данными
        updatePaintTable(paintComponents);
    } else {
        // Если jQuery еще не загружен, ждем
        setTimeout(initializeWhenReady, 100);
    }
}

// Запускаем инициализацию
initializeWhenReady();

// Проверяем, что функции определены
console.log('showAddNdtModal defined:', typeof window.showAddNdtModal);
console.log('showAddCadModal defined:', typeof window.showAddCadModal);

function loadComponents() {
    console.log('Loading components...');
    $.get(`{{ route('ndt-cad-csv.components', ['workorder' => $workorder->id]) }}`)
        .done(function(response) {
            console.log('Components response:', response);
            if (response.success) {
                allComponents = response.components;
                console.log('Loaded components:', allComponents);
                updateComponentDropdowns();
            } else {
                console.error('Failed to load components:', response.message);
            }
        })
        .fail(function(xhr) {
            console.error('Error loading components:', xhr.responseText);
        });
}

function loadCadProcesses() {
    $.get(`{{ route('ndt-cad-csv.cad-processes', ['workorder' => $workorder->id]) }}`)
        .done(function(response) {
            if (response.success) {
                cadProcesses = response.processes;
                updateCadProcessDropdown();
            }
        })
        .fail(function(xhr) {
            console.error('Error loading CAD processes:', xhr.responseText);
        });
}

function loadPaintProcesses() {
    $.get(`{{ route('ndt-cad-csv.paint-processes', ['workorder' => $workorder->id]) }}`)
        .done(function(response) {
            if (response.success) {
                paintProcesses = response.processes;
                updatePaintProcessDropdown();
            }
        })
        .fail(function(xhr) {
            console.error('Error loading Paint processes:', xhr.responseText);
        });
}

function loadStressProcesses() {
    $.get(`{{ route('ndt-cad-csv.stress-processes', ['workorder' => $workorder->id]) }}`)
        .done(function(response) {
            if (response.success) {
                stressProcesses = response.processes;
                updateStressProcessDropdown();
            }
        })
        .fail(function(xhr) {
            console.error('Error loading Stress processes:', xhr.responseText);
        });
}

function updateComponentDropdowns() {
    // Сортируем компоненты по ipl_num
    const sortedComponents = allComponents.sort(function(a, b) {
        return a.ipl_num.localeCompare(b.ipl_num, undefined, {numeric: true, sensitivity: 'base'});
    });

    // Обновляем NDT dropdown
    $('#ndtComponent').empty().append('<option value="">Выберите компонент...</option>');
    sortedComponents.forEach(function(component) {
        $('#ndtComponent').append(`<option value="${component.id}" data-ipl-num="${component.ipl_num}" data-part-number="${component.part_number}" data-description="${component.name}" data-units-assy="${component.units_assy}">${component.ipl_num} : ${component.part_number} - ${component.name}</option>`);
    });

    // Обновляем CAD dropdown
    $('#cadComponent').empty().append('<option value="">Выберите компонент...</option>');
    sortedComponents.forEach(function(component) {
        $('#cadComponent').append(`<option value="${component.id}" data-ipl-num="${component.ipl_num}" data-part-number="${component.part_number}" data-description="${component.name}" data-units-assy="${component.units_assy}">${component.ipl_num} : ${component.part_number} - ${component.name}</option>`);
    });

    // Обновляем Paint dropdown
    $('#paintComponent').empty().append('<option value="">Выберите компонент...</option>');
    sortedComponents.forEach(function(component) {
        $('#paintComponent').append(`<option value="${component.id}" data-ipl-num="${component.ipl_num}" data-part-number="${component.part_number}" data-description="${component.name}" data-units-assy="${component.units_assy}">${component.ipl_num} : ${component.part_number} - ${component.name}</option>`);
    });

    // Обновляем Stress dropdown
    $('#stressComponent').empty().append('<option value="">Выберите компонент...</option>');
    sortedComponents.forEach(function(component) {
        $('#stressComponent').append(`<option value="${component.id}" data-ipl-num="${component.ipl_num}" data-part-number="${component.part_number}" data-description="${component.name}" data-units-assy="${component.units_assy}">${component.ipl_num} : ${component.part_number} - ${component.name}</option>`);
    });

    // Обновляем Select2 если он инициализирован
    if (typeof $.fn.select2 !== 'undefined') {
        $('#ndtComponent').trigger('change.select2');
        $('#cadComponent').trigger('change.select2');
        $('#paintComponent').trigger('change.select2');
        $('#stressComponent').trigger('change.select2');
    }
}

function updateCadProcessDropdown() {
    $('#cadProcess').empty().append('<option value="">Выберите процесс...</option>');
    $('#cadProcessEdit').empty().append('<option value="">Выберите процесс...</option>');
    cadProcesses.forEach(function(process) {
        $('#cadProcess').append(`<option value="${process.process}">${process.process}</option>`);
        $('#cadProcessEdit').append(`<option value="${process.process}">${process.process}</option>`);
    });

    // Обновляем Select2 если он инициализирован
    if (typeof $.fn.select2 !== 'undefined') {
        $('#cadProcess').trigger('change.select2');
        $('#cadProcessEdit').trigger('change.select2');
    }
}

function updatePaintProcessDropdown() {
    $('#paintProcess').empty().append('<option value="">Выберите процесс...</option>');
    $('#paintProcessEdit').empty().append('<option value="">Выберите процесс...</option>');
    paintProcesses.forEach(function(process) {
        $('#paintProcess').append(`<option value="${process.process}">${process.process}</option>`);
        $('#paintProcessEdit').append(`<option value="${process.process}">${process.process}</option>`);
    });

    // Обновляем Select2 если он инициализирован
    if (typeof $.fn.select2 !== 'undefined') {
        $('#paintProcess').trigger('change.select2');
        $('#paintProcessEdit').trigger('change.select2');
    }
}

function updateStressProcessDropdown() {
    $('#stressProcess').empty().append('<option value="">Выберите процесс...</option>');
    $('#stressEditProcess').empty().append('<option value="">Выберите процесс...</option>');
    stressProcesses.forEach(function(process) {
        $('#stressProcess').append(`<option value="${process.process}">${process.process}</option>`);
        $('#stressEditProcess').append(`<option value="${process.process}">${process.process}</option>`);
    });

    // Обновляем Select2 если он инициализирован
    if (typeof $.fn.select2 !== 'undefined') {
        $('#stressProcess').trigger('change.select2');
        $('#stressEditProcess').trigger('change.select2');
    }
}

// Функции перенесены в глобальную область видимости в конце файла

// Обработчики форм перенесены в initializeWhenReady()
</script>

@endsection

<script>
// Определяем остальные функции в глобальной области видимости
window.removeNdtComponent = function(index) {
    console.log('Removing NDT component with index:', index);
    console.log('Current NDT components:', ndtComponents);

    if (confirm('Are you sure you want to remove this component?')) {
        if (typeof $ !== 'undefined') {
            $.post(`{{ route('ndt-cad-csv.remove-ndt', ['workorder' => $workorder->id]) }}`, {
                index: index,
                _token: $('meta[name="csrf-token"]').attr('content')
            }).done(function(response) {
                console.log('Server response:', response);
                if (response.success) {
                    // Удаляем из локального массива
                    ndtComponents.splice(index, 1);

                    // Динамически обновляем таблицу
                    updateNdtTable(ndtComponents);

                    // Показываем уведомление
                    showNotification('NDT компонент успешно удален', 'success');
                } else {
                    alert('Error: ' + response.message);
                }
            }).fail(function(xhr) {
                console.error('Ошибка AJAX:', xhr.responseText);
                alert('Error while deleting component');
            });
        } else {
            console.log('jQuery not loaded yet, using fallback');
            // Fallback без jQuery - просто перезагружаем страницу
            location.reload();
        }
    }
};

window.removeCadComponent = function(index) {
    console.log('Removing a CAD component with an index:', index);
    console.log('Current CAD components:', cadComponents);

    if (confirm('Are you sure you want to remove this component?')) {
        if (typeof $ !== 'undefined') {
            $.post(`{{ route('ndt-cad-csv.remove-cad', ['workorder' => $workorder->id]) }}`, {
                index: index,
                _token: $('meta[name="csrf-token"]').attr('content')
            }).done(function(response) {
                console.log('Server response:', response);
                if (response.success) {
                    // Удаляем из локального массива
                    cadComponents.splice(index, 1);

                    // Динамически обновляем таблицу
                    updateCadTable(cadComponents);

                    // Показываем уведомление
                    showNotification('CAD компонент успешно удален', 'success');
                } else {
                    alert('Error: ' + response.message);
                }
            }).fail(function(xhr) {
                console.error('Ошибка AJAX:', xhr.responseText);
                alert('Error while deleting component');
            });
        } else {
            console.log('jQuery not loaded yet, using fallback');
            // Fallback без jQuery - просто перезагружаем страницу
            location.reload();
        }
    }
};

window.removeStressComponent = function(index) {
    console.log('Removing a Stress component with an index:', index);
    console.log('Current Stress components:', stressComponents);

    if (confirm('Are you sure you want to remove this component?')) {
        if (typeof $ !== 'undefined') {
            $.post(`{{ route('ndt-cad-csv.remove-stress', ['workorder' => $workorder->id]) }}`, {
                index: index,
                _token: $('meta[name="csrf-token"]').attr('content')
            }).done(function(response) {
                console.log('Server response:', response);
                if (response.success) {
                    // Удаляем из локального массива
                    stressComponents.splice(index, 1);

                    // Динамически обновляем таблицу
                    updateStressTable(stressComponents);

                    // Показываем уведомление
                    showNotification('Stress компонент успешно удален', 'success');
                } else {
                    alert('Error: ' + response.message);
                }
            }).fail(function(xhr) {
                console.error('Ошибка AJAX:', xhr.responseText);
                alert('Error while deleting component');
            });
        } else {
            console.log('jQuery not loaded yet, using fallback');
            // Fallback без jQuery - просто перезагружаем страницу
            location.reload();
        }
    }
};

window.reloadFromManual = function(type) {
    if (confirm(`Are you sure you want to reload ${type.toUpperCase()} components from Manual CSV? This will replace all existing data.`)) {
        if (typeof $ !== 'undefined') {
            $.post(`/admin/${workorderId}/ndt-cad-csv/reload-from-manual`, {
                type: type,
                _token: $('meta[name="csrf-token"]').attr('content')
            }).done(function(response) {
                if (response.success) {
                    alert(`Successfully loaded ${response.count} components`);
                    location.reload();
                } else {
                    alert('Ошибка: ' + response.message);
                }
            });
        } else {
            console.log('jQuery not loaded yet, using fallback');
            // Fallback без jQuery - просто перезагружаем страницу
            location.reload();
        }
    }
};

window.forceLoadFromManual = function(type) {
    if (confirm(`Force loading ${type.toUpperCase()} components from Manual CSV?`)) {
        if (typeof $ !== 'undefined') {
            $.post(`/admin/${workorderId}/ndt-cad-csv/force-load-from-manual`, {
                type: type,
                _token: $('meta[name="csrf-token"]').attr('content')
            }).done(function(response) {
                if (response.success) {
                    alert(`Successfully loaded ${response.count} components`);
                    location.reload();
                } else {
                    alert('Ошибка: ' + response.message);
                }
            });
        } else {
            console.log('jQuery not loaded yet, using fallback');
            // Fallback без jQuery - просто перезагружаем страницу
            location.reload();
        }
    }
};

window.importNdtFromCsv = function() {
    if (typeof $ !== 'undefined') {
        $('#csvType').val('ndt');
        $('#csvImportModal').modal('show');
    } else {
        console.log('jQuery not loaded yet, using fallback');
        document.getElementById('csvType').value = 'ndt';
        document.getElementById('csvImportModal').style.display = 'block';
        document.getElementById('csvImportModal').classList.add('show');
    }
};

window.importCadFromCsv = function() {
    if (typeof $ !== 'undefined') {
        $('#csvType').val('cad');
        $('#csvImportModal').modal('show');
    } else {
        console.log('jQuery not loaded yet, using fallback');
        document.getElementById('csvType').value = 'cad';
        document.getElementById('csvImportModal').style.display = 'block';
        document.getElementById('csvImportModal').classList.add('show');
    }
};

window.importPaintFromCsv = function() {
    if (typeof $ !== 'undefined') {
        $('#csvType').val('paint');
        $('#csvImportModal').modal('show');
    } else {
        console.log('jQuery not loaded yet, using fallback');
        document.getElementById('csvType').value = 'paint';
        document.getElementById('csvImportModal').style.display = 'block';
        document.getElementById('csvImportModal').classList.add('show');
    }
};

window.importStressFromCsv = function() {
    if (typeof $ !== 'undefined') {
        $('#csvType').val('stress');
        $('#csvImportModal').modal('show');
    } else {
        console.log('jQuery not loaded yet, using fallback');
        document.getElementById('csvType').value = 'stress';
        document.getElementById('csvImportModal').style.display = 'block';
        document.getElementById('csvImportModal').classList.add('show');
    }
};


// Функции для открытия модальных окон добавления
window.showAddNdtModal = function() {
    console.log('showAddNdtModal called');

    if (typeof $ !== 'undefined') {
        // Сбрасываем форму
        $('#ndtForm')[0].reset();

        // Сбрасываем Select2 если он инициализирован
        if (typeof $.fn.select2 !== 'undefined') {
            $('#ndtComponent').val('').trigger('change');
        }

        // Показываем модальное окно
        $('#ndtModal').modal('show');
    } else {
        console.log('jQuery not loaded yet, using fallback');
        // Fallback без jQuery
        document.getElementById('ndtForm').reset();
        document.getElementById('ndtModal').style.display = 'block';
        document.getElementById('ndtModal').classList.add('show');
    }
};

window.showAddCadModal = function() {
    console.log('showAddCadModal called');

    if (typeof $ !== 'undefined') {
        // Сбрасываем форму
        $('#cadForm')[0].reset();

        // Сбрасываем Select2 если он инициализирован
        if (typeof $.fn.select2 !== 'undefined') {
            $('#cadComponent').val('').trigger('change');
            $('#cadProcess').val('').trigger('change');
        }

        // Показываем модальное окно
        $('#cadModal').modal('show');
    } else {
        console.log('jQuery not loaded yet, using fallback');
        // Fallback без jQuery
        document.getElementById('cadForm').reset();
        document.getElementById('cadModal').style.display = 'block';
        document.getElementById('cadModal').classList.add('show');
    }
};

window.showAddPaintModal = function() {
    console.log('showAddPaintModal called');

    if (typeof $ !== 'undefined') {
        // Сбрасываем форму
        $('#paintForm')[0].reset();

        // Сбрасываем Select2 если он инициализирован
        if (typeof $.fn.select2 !== 'undefined') {
            $('#paintComponent').val('').trigger('change');
            $('#paintProcess').val('').trigger('change');
        }

        // Показываем модальное окно
        $('#paintModal').modal('show');
    } else {
        console.log('jQuery not loaded yet, using fallback');
        // Fallback без jQuery
        document.getElementById('paintForm').reset();
        document.getElementById('paintModal').style.display = 'block';
        document.getElementById('paintModal').classList.add('show');
    }
};

// Функции для редактирования компонентов
window.editNdtComponent = function(index) {
    console.log('Editing NDT component with index:', index);
    console.log('NDT Components array:', ndtComponents);

    const component = ndtComponents[index];
    if (!component) {
        console.error('NDT Component not found at index:', index);
        alert('Component not found');
        return;
    }

    console.log('Found NDT component:', component);

    // Заполняем информацию из JSON
    $('#ndtCurrentIpl').text(component.ipl_num);
    $('#ndtCurrentPartNumber').text(component.part_number);
    $('#ndtCurrentDescription').text(component.description);
    $('#ndtCurrentProcess').text(component.process);
    $('#ndtCurrentQty').text(component.qty);

    // Заполняем редактируемые поля
    $('#ndtEditIndex').val(index);
    $('#ndtEditPartNumber').val(component.part_number);
    $('#ndtEditDescription').val(component.description);
    $('#ndtEditProcess').val(component.process);
    $('#ndtEditQty').val(component.qty);

    console.log('Filling NDT edit form with:', {
        ipl_num: component.ipl_num,
        part_number: component.part_number,
        description: component.description,
        process: component.process,
        qty: component.qty
    });

    // Показываем модальное окно
    $('#ndtEditModal').modal('show');
};

window.editCadComponent = function(index) {
    console.log('Editing CAD component with index:', index);
    console.log('CAD Components array:', cadComponents);

    const component = cadComponents[index];
    if (!component) {
        console.error('CAD Component not found at index:', index);
        alert('Component not found');
        return;
    }

    console.log('Found CAD component:', component);

    // Заполняем информацию из JSON
    $('#cadCurrentIpl').text(component.ipl_num);
    $('#cadCurrentPartNumber').text(component.part_number);
    $('#cadCurrentDescription').text(component.description);
    $('#cadCurrentProcess').text(component.process);
    $('#cadCurrentQty').text(component.qty);

    // Заполняем редактируемые поля
    $('#cadEditIndex').val(index);
    $('#cadEditPartNumber').val(component.part_number);
    $('#cadEditDescription').val(component.description);
    $('#cadEditQty').val(component.qty);

    // Загружаем и заполняем процессы для dropdown
    if (cadProcesses && cadProcesses.length > 0) {
        // Очищаем dropdown
        $('#cadEditProcess').empty().append('<option value="">Select a process...</option>');

        // Добавляем процессы
        cadProcesses.forEach(function(process) {
            $('#cadEditProcess').append(`<option value="${process.process}">${process.process}</option>`);
        });

        // Устанавливаем выбранный процесс
        $('#cadEditProcess').val(component.process);

        // Обновляем Select2
        if (typeof $.fn.select2 !== 'undefined') {
            $('#cadEditProcess').trigger('change.select2');
        }
    } else {
        // Если процессы еще не загружены, загружаем их
        console.log('CAD processes not loaded, loading now...');
        $.get(`/admin/${workorderId}/ndt-cad-csv/cad-processes`)
            .done(function(response) {
                if (response.success) {
                    cadProcesses = response.processes;
                    console.log('Loaded CAD processes:', cadProcesses);

                    // Очищаем dropdown
                    $('#cadEditProcess').empty().append('<option value="">Select a process...</option>');

                    // Добавляем процессы
                    cadProcesses.forEach(function(process) {
                        $('#cadEditProcess').append(`<option value="${process.process}">${process.process}</option>`);
                    });

                    // Устанавливаем выбранный процесс
                    $('#cadEditProcess').val(component.process);

                    // Обновляем Select2
                    if (typeof $.fn.select2 !== 'undefined') {
                        $('#cadEditProcess').trigger('change.select2');
                    }
                }
            })
            .fail(function(xhr) {
                console.error('Error loading CAD processes:', xhr.responseText);
            });
    }

    console.log('Filling CAD edit form with:', {
        ipl_num: component.ipl_num,
        part_number: component.part_number,
        description: component.description,
        process: component.process,
        qty: component.qty
    });

    // Показываем модальное окно
    $('#cadEditModal').modal('show');
};

window.editPaintComponent = function(index) {
    console.log('Editing Paint component with index:', index);
    console.log('Paint Components array:', paintComponents);

    const component = paintComponents[index];
    if (!component) {
        console.error('Paint Component not found at index:', index);
        alert('Component not found');
        return;
    }

    console.log('Found Paint component:', component);

    // Заполняем информацию из JSON
    $('#paintCurrentIpl').text(component.ipl_num);
    $('#paintCurrentPartNumber').text(component.part_number);
    $('#paintCurrentDescription').text(component.description);
    $('#paintCurrentProcess').text(component.process);
    $('#paintCurrentQty').text(component.qty);

    // Заполняем редактируемые поля
    $('#paintEditIndex').val(index);
    $('#paintEditPartNumber').val(component.part_number);
    $('#paintEditDescription').val(component.description);
    $('#paintEditQty').val(component.qty);

    // Загружаем и заполняем процессы для dropdown
    if (paintProcesses && paintProcesses.length > 0) {
        // Очищаем dropdown
        $('#paintEditProcess').empty().append('<option value="">Select a process...</option>');

        // Добавляем процессы
        paintProcesses.forEach(function(process) {
            $('#paintEditProcess').append(`<option value="${process.process}">${process.process}</option>`);
        });

        // Устанавливаем выбранный процесс
        $('#paintEditProcess').val(component.process);

        // Обновляем Select2
        if (typeof $.fn.select2 !== 'undefined') {
            $('#paintEditProcess').trigger('change.select2');
        }
    } else {
        // Если процессы еще не загружены, загружаем их
        console.log('Paint processes not loaded, loading now...');
        $.get(`/admin/${workorderId}/ndt-cad-csv/paint-processes`)
            .done(function(response) {
                if (response.success) {
                    paintProcesses = response.processes;
                    console.log('Loaded Paint processes:', paintProcesses);

                    // Очищаем dropdown
                    $('#paintEditProcess').empty().append('<option value="">Select a process...</option>');

                    // Добавляем процессы
                    paintProcesses.forEach(function(process) {
                        $('#paintEditProcess').append(`<option value="${process.process}">${process.process}</option>`);
                    });

                    // Устанавливаем выбранный процесс
                    $('#paintEditProcess').val(component.process);

                    // Обновляем Select2
                    if (typeof $.fn.select2 !== 'undefined') {
                        $('#paintEditProcess').trigger('change.select2');
                    }
                }
            })
            .fail(function(xhr) {
                console.error('Error loading Paint processes:', xhr.responseText);
            });
    }

    console.log('Filling Paint edit form with:', {
        ipl_num: component.ipl_num,
        part_number: component.part_number,
        description: component.description,
        process: component.process,
        qty: component.qty
    });

    // Показываем модальное окно
    $('#paintEditModal').modal('show');
};

window.editStressComponent = function(index) {
    console.log('Editing Stress component with index:', index);
    console.log('Stress Components array:', stressComponents);

    const component = stressComponents[index];
    if (!component) {
        console.error('Stress Component not found at index:', index);
        alert('Component not found');
        return;
    }

    console.log('Found Stress component:', component);

    // Заполняем информацию из JSON
    $('#stressCurrentIpl').text(component.ipl_num);
    $('#stressCurrentPartNumber').text(component.part_number);
    $('#stressCurrentDescription').text(component.description);
    $('#stressCurrentProcess').text(component.process);
    $('#stressCurrentQty').text(component.qty);

    // Заполняем редактируемые поля
    $('#stressEditIndex').val(index);
    $('#stressEditPartNumber').val(component.part_number);
    $('#stressEditDescription').val(component.description);
    $('#stressEditQty').val(component.qty);

    // Загружаем и заполняем процессы для dropdown
    if (stressProcesses && stressProcesses.length > 0) {
        // Очищаем dropdown
        $('#stressEditProcess').empty().append('<option value="">Select a process...</option>');

        // Добавляем процессы
        stressProcesses.forEach(function(process) {
            $('#stressEditProcess').append(`<option value="${process.process}">${process.process}</option>`);
        });

        // Устанавливаем выбранный процесс
        $('#stressEditProcess').val(component.process);

        // Обновляем Select2
        if (typeof $.fn.select2 !== 'undefined') {
            $('#stressEditProcess').trigger('change.select2');
        }
    } else {
        // Если процессы еще не загружены, загружаем их
        console.log('Stress processes not loaded, loading now...');
        $.get(`/admin/${workorderId}/ndt-cad-csv/stress-processes`)
            .done(function(response) {
                if (response.success) {
                    stressProcesses = response.processes;
                    console.log('Loaded Stress processes:', stressProcesses);

                    // Очищаем dropdown
                    $('#stressEditProcess').empty().append('<option value="">Select a process...</option>');

                    // Добавляем процессы
                    stressProcesses.forEach(function(process) {
                        $('#stressEditProcess').append(`<option value="${process.process}">${process.process}</option>`);
                    });

                    // Устанавливаем выбранный процесс
                    $('#stressEditProcess').val(component.process);

                    // Обновляем Select2
                    if (typeof $.fn.select2 !== 'undefined') {
                        $('#stressEditProcess').trigger('change.select2');
                    }
                }
            })
            .fail(function(xhr) {
                console.error('Error loading Stress processes:', xhr.responseText);
            });
    }

    console.log('Filling Stress edit form with:', {
        ipl_num: component.ipl_num,
        part_number: component.part_number,
        description: component.description,
        process: component.process,
        qty: component.qty
    });

    // Показываем модальное окно
    $('#stressEditModal').modal('show');
};

window.removePaintComponent = function(index) {
    console.log('Removing a Paint component with an index:', index);
    console.log('Current Paint components:', paintComponents);

    if (confirm('Are you sure you want to remove this component?')) {
        if (typeof $ !== 'undefined') {
            $.post(`/admin/${workorderId}/ndt-cad-csv/remove-paint`, {
                index: index,
                _token: $('meta[name="csrf-token"]').attr('content')
            }).done(function(response) {
                console.log('Server response:', response);
                if (response.success) {
                    // Удаляем из локального массива
                    paintComponents.splice(index, 1);

                    // Динамически обновляем таблицу
                    updatePaintTable(paintComponents);

                    // Показываем уведомление
                    showNotification('Paint компонент успешно удален', 'success');
                } else {
                    alert('Error: ' + response.message);
                }
            }).fail(function(xhr) {
                console.error('Ошибка AJAX:', xhr.responseText);
                alert('Error while deleting component');
            });
        } else {
            console.log('jQuery not loaded yet, using fallback');
            // Fallback без jQuery - просто перезагружаем страницу
            location.reload();
        }
    }
};

console.log('All global functions defined');
</script>
