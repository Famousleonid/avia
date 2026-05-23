{{-- Missing Modal --}}
<div class="modal fade " id="missingModal{{$current_wo->number}}" tabindex="-1" role="dialog" aria-labelledby="missingModalLabel{{$current_wo->number}}" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document" style="max-width: min(1100px, calc(100vw - 2rem));">
        <div class="modal-content bg-gradient" style="width: 90%; margin: 0 auto;">
            <div class="modal-header">
                <h6 class="modal-title text-info">{{__('Work order ')}}{{$current_wo->number}} - {{__('Parts Missing ')}}</h6>
                <button type="button" class="btn-close pb-2" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="table-wrapper">
                <table class="display table table-cm table-hover table-striped align-middle table-bordered dir-table" style="font-size: 0.9rem">
                    <thead class="bg-gradient">
                    <tr>
                        <th class="text-primary ">{{__('IPL')}}</th>
                        <th class="text-primary ">{{__('Part Description')}}</th>
                        <th class="text-primary ">{{__('Part Number')}}</th>
                        <th class="text-primary  text-center" style="width: 5%">{{__('QTY')}}</th>
                        <th class="text-primary  text-center" style="width: 5%">{{__('Delete')}}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($missingParts as $part)
                        @php
                            $currentComponent = $part->orderComponent ?? $part->component;
                            $currentAssembly = $part->orderComponentAssembly;
                            $displayIpl = $currentAssembly->assy_ipl_num ?? $currentComponent->ipl_num ?? '';
                            $displayName = trim(($currentComponent->name ?? '') . ($currentAssembly ? ' (assy)' : ''));
                            $displayPartNumber = $currentAssembly->assy_part_number ?? $currentComponent->part_number ?? '';
                        @endphp
                        <tr>
                            <td class="p-2">{{ $displayIpl }}</td>
                            <td class="p-2">{{ $displayName }}</td>
                            <td class="p-2">{{ $displayPartNumber }}</td>
                            <td class="p-2 text-center">{{ $part->qty }}</td>
                            <td class="p-2 text-center">
                                <form action="{{ route('tdrs.destroy', $part->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="return_to" value="show">
                                    <button type="button"
                                            class="btn btn-link btn-sm p-0 border-0 bg-transparent text-danger"
                                            title="{{ __('Delete') }}"
                                            aria-label="{{ __('Delete') }}"
                                            data-bs-toggle="modal"
                                            data-bs-target="#useConfirmDelete"
                                            data-title="{{ __('Delete Confirmation') }}">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Order Modal --}}
<div class="modal fade order-modal" id="orderModal{{$current_wo->number}}" tabindex="-1" role="dialog"
     aria-labelledby="orderModalLabel{{$current_wo->number}}" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document" style="max-width: min(1100px, calc(100vw - 2rem));">
        <div class="modal-content bg-gradient" style="width: 100%">
            <div class="modal-header">
                <h6 class="modal-title text-info">{{__('Work order W')}}{{$current_wo->number}} - {{__('Ordered Parts')}}</h6>
                <button type="button" class="btn-close pb-2" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            @if(count($ordersPartsNew))
                <div class="table-wrapper order-modal-table-wrapper">
                    <table class="table  table-hover table-striped align-middle table-bordered
                    dir-table">
                        <thead class="bg-gradient">
                        <tr>
                            <th class="text-primary bg-gradient" style="width: 8%">{{__('IPL')}}</th>
                            <th class="text-primary bg-gradient" style="width: 20%">{{__('Part Description')}}</th>
                            <th class="text-primary bg-gradient" style="width: 15%">{{__('Part Number')}}</th>
                            <th class="text-primary bg-gradient" style="min-width: 30%;">{{ __('Description') }}</th>
                            <th class="text-primary bg-gradient text-center" style="width: 4%">{{__('QTY')}}</th>
                            <th class="text-primary bg-gradient" style="width: 7%">{{__('Conditions')}}</th>
                            <th class="text-primary bg-gradient " style="width: 5%">{{__('Actions')}}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($ordersPartsNew as $part)
                            @php
                                $orderPartFormId = 'orderPartForm'.$part->id;
                                $currentAssembly = $part->orderComponentAssembly;
                                $displayIpl = $currentAssembly->assy_ipl_num ?? $part->orderComponent->ipl_num ?? '';
                                $displayName = trim(($part->orderComponent->name ?? '') . ($currentAssembly ? ' (assy)' : ''));
                                $displayPartNumber = $currentAssembly->assy_part_number ?? $part->orderComponent->part_number ?? '';
                            @endphp
                            <tr>
                                <td class="p-2">{{ $displayIpl }}</td>
                                <td class="p-2">{{ $displayName }}</td>
                                <td class="p-2">{{ $displayPartNumber }}</td>
                                <td class="p-2 align-middle" style="min-width: 10rem;">
                                    <textarea name="description" rows="1" class="form-control form-control-sm" form="{{ $orderPartFormId }}"
                                              placeholder="{{ __('Description') }}">{{ $part->description ?? '' }}</textarea>
                                </td>
                                <td class="p-2 align-middle text-center" style="min-width: 5.0rem;">
                                    <input type="number" name="qty" value="{{ $part->qty }}" min="1" max="999999"
                                           class="form-control form-control-sm" form="{{ $orderPartFormId }}">
                                </td>
                                <td class="p-2 align-middle" style="min-width: 8rem;">
                                    <select name="codes_id" class="form-select form-select-sm" form="{{ $orderPartFormId }}">
                                        @foreach($codes as $code)
                                            <option value="{{ $code->id }}" @selected($code->id == $part->codes_id)>{{ $code->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="p-2 align-middle">
                                    <div class="d-flex justify-content-evenly align-items-center w-100 text-center">
                                        <form id="{{ $orderPartFormId }}" method="POST" action="{{ route('tdrs.update', $part->id) }}" class="d-inline">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="workorder_id" value="{{ $current_wo->id }}">
                                            <button type="submit" class="btn btn-link btn-sm p-0 border-0 bg-transparent text-primary" title="{{ __('Save') }}" aria-label="{{ __('Save') }}">
                                                <i class="bi bi-floppy"></i>
                                            </button>
                                        </form>
                                        <form action="{{ route('tdrs.destroy', $part->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="return_to" value="show">
                                            <button type="button"
                                                    class="btn btn-link btn-sm p-0 border-0 bg-transparent text-danger"
                                                    title="{{ __('Delete') }}"
                                                    aria-label="{{ __('Delete') }}"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#useConfirmDelete"
                                                    data-title="{{ __('Delete Confirmation') }}">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <h5 class="text-center mt-3 mb-3 text-primary">{{__('No Ordered Parts')}}</h5>
            @endif
        </div>
    </div>
</div>

<style>
    .pdf-library-modal .modal-content {
        background: #252a2e;
        border: 1px solid rgba(13, 202, 240, .22);
        border-radius: 8px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, .45);
    }
    .pdf-library-modal .modal-header,
    .pdf-library-modal .modal-footer { border-color: rgba(148, 163, 184, .18); }
    .pdf-upload-panel {
        background: #1d2226;
        border: 1px solid rgba(148, 163, 184, .22);
        border-radius: 8px;
        padding: 14px;
    }
    .pdf-upload-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 12px;
        align-items: stretch;
    }
    .pdf-upload-panel .form-label {
        color: #9fb3bb;
        font-size: .76rem;
        margin-bottom: .28rem;
    }
    .pdf-upload-panel .form-control {
        background-color: #15191c;
        border-color: rgba(13, 202, 240, .28);
        color: #e8f6f8;
        min-height: 38px;
    }
    .pdf-drop-zone {
        min-height: 126px;
        border: 1px dashed rgba(13, 202, 240, .55);
        border-radius: 8px;
        background: linear-gradient(180deg, rgba(13, 202, 240, .07), rgba(13, 202, 240, .02));
        color: #d7e7eb;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px 18px;
        position: relative;
        transition: border-color .15s ease, background-color .15s ease, box-shadow .15s ease;
    }
    .pdf-drop-zone:hover,
    .pdf-drop-zone.is-dragover {
        border-color: #0dcaf0;
        background: rgba(13, 202, 240, .12);
        box-shadow: 0 0 0 .15rem rgba(13, 202, 240, .12);
    }
    .pdf-drop-icon {
        width: 42px;
        height: 42px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #ffc107;
        background: rgba(255, 193, 7, .12);
        border: 1px solid rgba(255, 193, 7, .32);
        flex: 0 0 auto;
        font-size: 1.35rem;
    }
    .pdf-drop-title { color: #f5fbfc; font-weight: 600; line-height: 1.15; }
    .pdf-drop-meta,
    .pdf-selected-file { color: #9fb3bb; font-size: .78rem; }
    .pdf-selected-file {
        margin-top: 6px;
        min-height: 18px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .pdf-upload-progress {
        display: none;
        margin-top: 10px;
        width: min(420px, 100%);
    }
    .pdf-upload-progress.is-visible {
        display: block;
    }
    .pdf-upload-progress-track {
        height: 6px;
        overflow: hidden;
        border-radius: 999px;
        background: rgba(148, 163, 184, .22);
    }
    .pdf-upload-progress-bar {
        width: 0;
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(90deg, #0dcaf0, #ffc107);
        transition: width .12s ease;
    }
    .pdf-upload-progress-label {
        color: #c8d6da;
        font-size: .76rem;
        margin-top: 5px;
    }
    .pdf-upload-actions {
        display: flex;
        flex-direction: column;
        gap: 6px;
        align-items: stretch;
    }
    .pdf-library-list {
        display: grid;
        gap: 8px;
    }
    .pdf-list-row {
        display: grid;
        grid-template-columns: 44px minmax(0, 1fr) auto;
        gap: 12px;
        align-items: center;
        background: #1d2226;
        border: 1px solid rgba(148, 163, 184, .2);
        border-radius: 8px;
        padding: 10px 12px;
        transition: border-color .15s ease, background-color .15s ease;
    }
    .pdf-list-row:hover {
        background: #20272b;
        border-color: rgba(13, 202, 240, .38);
    }
    .pdf-list-icon {
        width: 36px;
        height: 42px;
        border: 1px solid #ffc107;
        border-radius: 5px;
        color: #ffc107;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        position: relative;
        background: rgba(255, 193, 7, .08);
        font-weight: 700;
        font-size: .7rem;
    }
    .pdf-list-icon::after {
        content: "";
        position: absolute;
        top: -1px;
        right: -1px;
        width: 12px;
        height: 12px;
        border-left: 1px solid #ffc107;
        border-bottom: 1px solid #ffc107;
        background: #252a2e;
        clip-path: polygon(0 0, 100% 100%, 100% 0);
    }
    .pdf-list-title {
        color: #f3f8fa;
        font-weight: 600;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .pdf-list-meta {
        color: #9fb3bb;
        font-size: .78rem;
        margin-top: 2px;
    }
    .pdf-list-actions {
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .pdf-list-actions .btn {
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
    }
    @media (max-width: 1100px) {
        .pdf-list-row { grid-template-columns: 38px minmax(0, 1fr); }
        .pdf-list-actions { grid-column: 2; justify-content: flex-start; }
    }
</style>

{{-- PDF Library Modal (same as show) --}}
<div class="modal fade pdf-library-modal" id="pdfModal" tabindex="-1" aria-labelledby="pdfModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title text-info" id="pdfModalLabel">PDF Library - Workorder W<span id="pdfModalWorkorderNumber"></span></h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="pdfUploadForm" class="pdf-upload-panel mb-4" data-no-spinner>
                    <div class="pdf-upload-grid">
                        <div>
                            <label for="pdfFileInput" class="form-label">PDF File</label>
                            <label for="pdfFileInput" class="pdf-drop-zone" id="pdfDropZone">
                                <span class="pdf-drop-icon"><i class="bi bi-file-earmark-pdf"></i></span>
                                <span class="min-w-0">
                                    <span class="pdf-drop-title d-block">Drop PDF here or click to upload</span>
                                    <span class="pdf-drop-meta d-block">The file is saved with its original name. One PDF, max 10MB.</span>
                                    <span class="pdf-selected-file d-block" id="pdfSelectedFile">No file selected</span>
                                    <span class="pdf-upload-progress" id="pdfUploadProgress">
                                        <span class="pdf-upload-progress-track">
                                            <span class="pdf-upload-progress-bar" id="pdfUploadProgressBar"></span>
                                        </span>
                                        <span class="pdf-upload-progress-label" id="pdfUploadProgressLabel">Waiting for file</span>
                                    </span>
                                </span>
                            </label>
                            <input type="file" class="visually-hidden" id="pdfFileInput" name="pdf" accept="application/pdf,.pdf" required>
                        </div>
                        <div class="pdf-upload-actions">
                            <button class="btn btn-outline-secondary d-none" type="button" id="clearPdfFileBtn">Cancel upload</button>
                        </div>
                    </div>
                </div>
                <div id="pdfListContainer" class="pdf-library-list"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- PDF viewer: sibling of #pdfModal, not nested — nested modal breaks iframe width/layout for Chrome built-in PDF --}}
<div class="modal fade" id="pdfViewerModal" tabindex="-1" aria-labelledby="pdfViewerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: min(1200px, 92vw); width: min(1200px, 92vw); height: min(85vh, 900px); margin: 1.75rem auto;">
        <div class="modal-content bg-dark d-flex flex-column" style="height: 100%; max-height: min(85vh, 900px); overflow: hidden;">
            <div class="modal-header flex-shrink-0">
                <h6 class="modal-title text-info" id="pdfViewerModalLabel">PDF Viewer</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 flex-grow-1 d-flex flex-column" style="min-height: 0; min-width: 0; overflow: hidden;">
                <iframe id="pdfViewerFrame" title="PDF" class="w-100 border-0 flex-grow-1 d-block" src="" style="flex: 1 1 0; min-height: 0; min-width: 0; width: 100%;"></iframe>
            </div>
            <div class="modal-footer flex-shrink-0">
                <a id="pdfDownloadLink" href="#" class="btn btn-primary" download><i class="bi bi-download"></i> Download</a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmDeletePdfModal" tabindex="-1" aria-labelledby="confirmDeletePdfLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-center">
            <div class="modal-header">
                <h6 class="modal-title text-info" id="confirmDeletePdfLabel">Confirm Deletion</h6>
            </div>
            <div class="modal-body">Are you sure you want to delete this PDF file?</div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button id="confirmPdfDeleteBtn" class="btn btn-danger">Delete</button>
            </div>
        </div>
    </div>
</div>

<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1055">
    <div id="pdfDeletedToast" class="toast bg-success text-white" role="alert">
        <div class="toast-body">PDF deleted successfully.</div>
    </div>
</div>

{{-- Unit Inspection Modal --}}
<div class="modal fade" id="unitInspectionModal" tabindex="-1" aria-labelledby="unitInspectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content bg-gradient">
            <div class="modal-header">
                <h6 class="modal-title text-info" id="unitInspectionModalLabel">
                    <i class="fas fa-clipboard-check"></i> {{ __('Teardown Inspection') }} - {{ __('Work Order') }} {{ $current_wo->number }}
                </h6>
                <div class="ms-auto me-2">
                    @admin
                    <button type="button" class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#manageConditionModal" data-bs-dismiss="modal">
                        <i class="fas fa-cog"></i> {{ __('Manage Condition') }}
                    </button>
                    @endadmin
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="unitInspectionForm">
                    @csrf
                    <input type="hidden" name="workorder_id" value="{{ $current_wo->id }}">
                    <div class="table-responsive" style="max-height: 60vh; overflow-y: auto;">
                        <table class="table table-hover table-bordered dir-table">
                            <thead class="table-primary" style="position: sticky; top: 0; z-index: 10;">
                            <tr>
                                <th class="text-center" style="width: 50px;"><input type="checkbox" id="selectAllConditions" title="{{ __('Select All') }}"></th>
                                <th class="text-center">{{ __('Condition') }}</th>
                                <th class="text-center" style="width: 300px;">{{ __('Notes') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @php
                                $existingInspections = [];
                                foreach($inspectsUnit as $inspection) {
                                    if($inspection->condition_id) {
                                        $existingInspections[$inspection->condition_id] = ['id' => $inspection->id, 'notes' => $inspection->notes ?? ''];
                                    }
                                }
                            @endphp
                            @foreach($unit_conditions as $unit_condition)
                                @if($unit_condition->name != 'PARTS MISSING UPON ARRIVAL AS INDICATED ON PARTS LIST')
                                    @php
                                        $isChecked = isset($existingInspections[$unit_condition->id]);
                                        $existingNotes = $isChecked ? $existingInspections[$unit_condition->id]['notes'] : '';
                                        $existingInspectionId = $isChecked ? $existingInspections[$unit_condition->id]['id'] : null;
                                    @endphp
                                    <tr>
                                        <td class="text-center align-middle">
                                            <input type="checkbox" class="form-check-input condition-checkbox" name="conditions[{{ $unit_condition->id }}][selected]" value="1" data-condition-id="{{ $unit_condition->id }}" {{ $isChecked ? 'checked' : '' }}>
                                            @if($existingInspectionId)
                                                <input type="hidden" name="conditions[{{ $unit_condition->id }}][inspection_id]" value="{{ $existingInspectionId }}">
                                            @endif
                                        </td>
                                        <td class="align-middle">
                                            <label for="condition_{{ $unit_condition->id }}" style="cursor: pointer; margin: 0;">
                                                {{ empty($unit_condition->name) ? __('(No name)') : $unit_condition->name }}
                                            </label>
                                        </td>
                                        <td class="align-middle">
                                            <input type="text" class="form-control form-control-sm condition-notes" name="conditions[{{ $unit_condition->id }}][notes]" id="condition_{{ $unit_condition->id }}" value="{{ $existingNotes }}" placeholder="{{ __('Enter notes...') }}">
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-outline-primary" id="saveUnitInspectionsBtn"><i class="fas fa-save"></i> {{ __('Save') }}</button>
            </div>
        </div>
    </div>
</div>

{{-- Manage Condition Modal --}}
<div class="modal fade" id="manageConditionModal" tabindex="-1" aria-labelledby="manageConditionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content bg-gradient">
            <div class="modal-header">
                <h6 class="modal-title text-info" id="manageConditionModalLabel"><i class="fas fa-cog"></i> {{ __('Manage Condition') }} - {{ __('Unit Conditions') }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    @admin
                    <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#addConditionModalFromManage"><i class="fas fa-plus"></i> {{ __('Add Condition') }}</button>
                    @endadmin
                </div>
                <div class="table-responsive" style="max-height: 60vh; overflow-y: auto;">
                    <table class="table table-hover table-bordered dir-table">
                        <thead class="table-primary" style="position: sticky; top: 0; z-index: 10;">
                        <tr>
                            <th class="text-center">{{ __('Condition Name') }}</th>
                            <th class="text-center" style="width: 150px;">{{ __('Actions') }}</th>
                        </tr>
                        </thead>
                        <tbody id="manageConditionsTableBody">
                        @foreach($unit_conditions as $unit_condition)
                            <tr data-condition-id="{{ $unit_condition->id }}">
                                <td class="align-middle">
                                    <span class="condition-name-display">{{ empty($unit_condition->name) ? __('(No name)') : $unit_condition->name }}</span>
                                    <input type="text" class="form-control form-control-sm condition-name-edit d-none" value="{{ $unit_condition->name }}" data-original-name="{{ $unit_condition->name }}">
                                </td>
                                <td class="text-center align-middle">
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-outline-warning btn-sm edit-condition-btn" data-condition-id="{{ $unit_condition->id }}" data-condition-name="{{ $unit_condition->name }}"><i class="fas fa-edit"></i> {{ __('Edit') }}</button>
                                        @if($unit_condition->name != 'PARTS MISSING UPON ARRIVAL AS INDICATED ON PARTS LIST')
                                            <button type="button" class="btn btn-outline-danger btn-sm delete-condition-btn" data-condition-id="{{ $unit_condition->id }}" data-condition-name="{{ $unit_condition->name }}"><i class="fas fa-trash"></i> {{ __('Delete') }}</button>
                                        @endif
                                    </div>
                                    <div class="btn-group d-none save-cancel-group" role="group">
                                        <button type="button" class="btn btn-outline-success btn-sm save-condition-btn" data-condition-id="{{ $unit_condition->id }}"><i class="fas fa-check"></i> {{ __('Save') }}</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm cancel-edit-btn" data-condition-id="{{ $unit_condition->id }}"><i class="fas fa-times"></i> {{ __('Cancel') }}</button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
            </div>
        </div>
    </div>
</div>

{{-- Add Condition Modal --}}
<div class="modal fade" id="addConditionModalFromManage" tabindex="-1" aria-labelledby="addConditionModalFromManageLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content bg-gradient">
            <div class="modal-header">
                <h6 class="modal-title text-info" id="addConditionModalFromManageLabel">{{ __('Add Condition') }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addConditionFormFromManage">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="unit" value="1">
                    <input type="hidden" name="workorder_id" value="{{ $current_wo->id }}">
                    <div class="form-group">
                        <label for="conditionName">{{ __('Name') }} <small class="text-muted">({{ __('Optional') }})</small></label>
                        <input id="conditionName" type="text" class="form-control" name="name" placeholder="{{ __('Leave empty to create condition with notes only') }}">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-outline-primary">{{ __('Save Condition') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Component Inspection Modal (Add Part Inspection) --}}
<div class="modal fade" id="componentInspectionModal" tabindex="-1" aria-labelledby="componentInspectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content bg-gradient">
            <div class="modal-header">
                <h6 class="modal-title text-info" id="componentInspectionModalLabel">
                    <i class="fas fa-clipboard-list"></i> {{ __('Add Part Inspection') }} - {{ __('Work Order') }} {{ $current_wo->number }}
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="background-color: #232525">
                @include('admin.tdrs.partials.component-inspection-form')
            </div>
        </div>
    </div>
</div>

@include('admin.tdrs.partials.component-inspection-modals')

{{-- Edit Tdr Process Modal (iframe, like Add Process) --}}
<div class="modal fade" id="editTdrProcessModal" tabindex="-1" aria-labelledby="editTdrProcessModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" style="max-width: 880px; width: 95%; height: 80vh;">
        <div class="modal-content bg-gradient" style="height: 80vh;">
            <div class="modal-header">
                <h6 class="modal-title text-info" id="editTdrProcessModalLabel">{{ __('Edit Part Process') }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 overflow-hidden" style="height: calc(80vh - 60px);">
                <iframe id="editTdrProcessIframe" src="about:blank" style="width: 100%; height: 100%; border: none;"></iframe>
            </div>
        </div>
    </div>
</div>

{{-- Edit Extra Process Modal (iframe) --}}
<div class="modal fade" id="editExtraProcessModal" tabindex="-1" aria-labelledby="editExtraProcessModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" style="max-width: 880px; width: 95%; height: 80vh;">
        <div class="modal-content bg-gradient" style="height: 80vh;">
            <div class="modal-header">
                <h6 class="modal-title text-info" id="editExtraProcessModalLabel">{{ __('Edit Extra Component') }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 overflow-hidden" style="height: calc(80vh - 60px);">
                <iframe id="editExtraProcessIframe" src="about:blank" style="width: 100%; height: 100%; border: none;"></iframe>
            </div>
        </div>
    </div>
</div>

{{-- Edit Bushing Modal --}}
<div class="modal fade" id="editBushingModal" tabindex="-1" aria-labelledby="editBushingModalLabel" aria-hidden="true">
    <div class="modal-dialog" style="max-width: calc(100vw - 24px); width: 1320px; height: 90vh;">
        <div class="modal-content bg-gradient" style="height: 90vh;">
            <div class="modal-header">
                <h6 class="modal-title text-info" id="editBushingModalLabel">{{ __('Update Bushings List') }}</h6>
                <div class="ms-auto d-flex gap-2 align-items-center" id="editBushingModalActions" style="display: none !important;">
                    <button type="submit" form="bushings-form" class="btn btn-success btn-sm" id="editBushingModalSubmitBtn">
                        <i class="fas fa-save"></i> {{ __('Save Bushing Data') }}
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="editBushingModalClearBtn">
                        <i class="fas fa-eraser"></i> {{ __('Clear All') }}
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" id="editBushingModalCancelBtn">{{ __('Cancel') }}</button>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 overflow-hidden" id="editBushingModalBody" style="height: calc(90vh - 60px);">
                <div class="text-center py-5 text-muted">{{ __('Loading...') }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Add Processes Modal (iframe) - processes.create from Update Bushings List --}}
<div class="modal fade" id="addProcessesModal" tabindex="-1" aria-labelledby="addProcessesModalLabel" aria-hidden="true">
    <div class="modal-dialog" style="max-width: 900px; width: 95%; height: 85vh;">
        <div class="modal-content bg-gradient" style="height: 85vh;">
            <div class="modal-header">
                <h6 class="modal-title text-info" id="addProcessesModalLabel">{{ __('Add Processes') }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 overflow-hidden" style="height: calc(85vh - 60px);">
                <iframe id="addProcessesIframe" src="about:blank" style="width: 100%; height: 100%; border: none;"></iframe>
            </div>
        </div>
    </div>
</div>

{{-- Add Part Modal (iframe) - components.create from Update Bushings List --}}
<div class="modal fade" id="addPartModal" tabindex="-1" aria-labelledby="addPartModalLabel" aria-hidden="true">
    <div class="modal-dialog" style="max-width: 900px; width: 95%; height: 85vh;">
        <div class="modal-content bg-gradient" style="height: 85vh;">
            <div class="modal-header">
                <h6 class="modal-title text-info" id="addPartModalLabel">{{ __('Add Part') }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 overflow-hidden" style="height: calc(85vh - 60px);">
                <iframe id="addPartIframe" src="about:blank" style="width: 100%; height: 100%; border: none;"></iframe>
            </div>
        </div>
    </div>
</div>

@if($hasTransfers ?? false)
    @include('admin.transfers.change-sn-modal')
@endif

{{-- Add Extra Part Modal (iframe) - create new extra part --}}
<div class="modal fade" id="addExtraPartModal" tabindex="-1" aria-labelledby="addExtraPartModalLabel" aria-hidden="true">
    <div class="modal-dialog" style="max-width: 900px; width: 95%; height: 85vh;">
        <div class="modal-content bg-gradient" style="height: 85vh;">
            <div class="modal-header">
                <h6 class="modal-title text-info" id="addExtraPartModalLabel">{{ __('Add Extra Part') }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 overflow-hidden" style="height: calc(85vh - 60px);">
                <iframe id="addExtraPartIframe" src="about:blank" style="width: 100%; height: 100%; border: none;"></iframe>
            </div>
        </div>
    </div>
</div>

{{-- Add Extra Process Modal (iframe) --}}
<div class="modal fade" id="addExtraProcessModal" tabindex="-1" aria-labelledby="addExtraProcessModalLabel" aria-hidden="true">
    <div class="modal-dialog" style="max-width: 880px; width: 95%; height: 80vh;">
        <div class="modal-content bg-gradient" style="height: 80vh;">
            <div class="modal-header">
                <h6 class="modal-title text-info" id="addExtraProcessModalLabel">{{ __('Add Extra Process') }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 overflow-hidden" style="height: calc(80vh - 60px);">
                <iframe id="addExtraProcessIframe" src="about:blank" style="width: 100%; height: 100%; border: none;"></iframe>
            </div>
        </div>
    </div>
</div>

{{-- Component Inspection Edit Modal (content loaded via AJAX) --}}
<div class="modal fade" id="editTdrModal" tabindex="-1" aria-labelledby="editTdrModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content bg-gradient">
            <div class="modal-header">
                <h6 class="modal-title text-info" id="editTdrModalLabel">{{ __('Component Inspection Edit') }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="editTdrModalBody" style="min-height: 200px;">
                <div class="text-center py-5 text-muted">{{ __('Loading...') }}</div>
            </div>
        </div>
    </div>
</div>
