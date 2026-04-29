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
                        @php $currentComponent = $part->orderComponent ?? $part->component; @endphp
                        <tr>
                            <td class="p-2">{{ $currentComponent->ipl_num ?? '' }}</td>
                            <td class="p-2">{{ $currentComponent->name ?? '' }}</td>
                            <td class="p-2">{{ $currentComponent->part_number ?? '' }}</td>
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
                            @php $orderPartFormId = 'orderPartForm'.$part->id; @endphp
                            <tr>
                                <td class="p-2">{{ $part->orderComponent->ipl_num ?? '' }}</td>
                                <td class="p-2">{{ $part->orderComponent->name ?? '' }}</td>
                                <td class="p-2">{{ $part->orderComponent->part_number ?? '' }}</td>
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

{{-- PDF Library Modal (same as show) --}}
<div class="modal fade" id="pdfModal" tabindex="-1" aria-labelledby="pdfModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content" style="background-color: #343A40">
            <div class="modal-header">
                <h6 class="modal-title text-info" id="pdfModalLabel">PDF Library - Workorder W<span id="pdfModalWorkorderNumber"></span></h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-4">
                    <div class="card bg-dark border-secondary">
                        <div class="card-body d-flex">
                            <h6 class="text-primary mb-3 me-4">Upload PDF Files</h6>
                            <form id="pdfUploadForm" enctype="multipart/form-data" data-no-spinner>
                                <div class="ms-3">
                                    <div class="d-flex">
                                        <label for="pdfDocumentName" class="form-label me-2">Document Name</label>
                                        <input type="text" class="form-control" id="pdfDocumentName" name="document_name" placeholder="Enter document name (optional)" style="width: 400px" maxlength="255">
                                    </div>
                                    <div class="input-group mt-2 ms-4 d-flex" style="height: 40px">
                                        <input type="file" class="form-control" id="pdfFileInput" name="pdf" accept=".pdf" style="width: 385px" required>
                                        <button class="btn btn-primary" type="submit" id="uploadPdfBtn"><i class="bi bi-upload"></i> Upload</button>
                                        <small class="text-muted ms-3">Max size: 10MB. Upload one file at a time.</small>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div id="pdfListContainer" class="row g-3"></div>
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
                                foreach($tdrs as $tdr) {
                                    if($tdr->use_tdr == true && $tdr->use_process_forms != true && $tdr->conditions_id) {
                                        $existingInspections[$tdr->conditions_id] = ['id' => $tdr->id, 'description' => $tdr->description ?? ''];
                                    }
                                }
                            @endphp
                            @foreach($unit_conditions as $unit_condition)
                                @if($unit_condition->name != 'PARTS MISSING UPON ARRIVAL AS INDICATED ON PARTS LIST')
                                    @php
                                        $isChecked = isset($existingInspections[$unit_condition->id]);
                                        $existingDescription = $isChecked ? $existingInspections[$unit_condition->id]['description'] : '';
                                        $existingTdrId = $isChecked ? $existingInspections[$unit_condition->id]['id'] : null;
                                    @endphp
                                    <tr>
                                        <td class="text-center align-middle">
                                            <input type="checkbox" class="form-check-input condition-checkbox" name="conditions[{{ $unit_condition->id }}][selected]" value="1" data-condition-id="{{ $unit_condition->id }}" {{ $isChecked ? 'checked' : '' }}>
                                            @if($existingTdrId)
                                                <input type="hidden" name="conditions[{{ $unit_condition->id }}][tdr_id]" value="{{ $existingTdrId }}">
                                            @endif
                                        </td>
                                        <td class="align-middle">
                                            <label for="condition_{{ $unit_condition->id }}" style="cursor: pointer; margin: 0;">
                                                {{ empty($unit_condition->name) ? __('(No name)') : $unit_condition->name }}
                                            </label>
                                        </td>
                                        <td class="align-middle">
                                            <input type="text" class="form-control form-control-sm condition-notes" name="conditions[{{ $unit_condition->id }}][notes]" id="condition_{{ $unit_condition->id }}" value="{{ $existingDescription }}" placeholder="{{ __('Enter notes...') }}">
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

{{-- Add Part Processes Modal (iframe) --}}
<div class="modal fade" id="addPartProcessesModal" tabindex="-1" aria-labelledby="addPartProcessesModalLabel" aria-hidden="true">
    <div class="modal-dialog" style="max-width: 880px; width: 95%; height: 80vh;">
        <div class="modal-content bg-gradient" style="height: 80vh;">
            <div class="modal-header">
                <h6 class="modal-title text-info" id="addPartProcessesModalLabel">{{ __('Add Part Process') }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 overflow-hidden" style="height: calc(80vh - 60px);">
                <iframe id="addPartProcessesIframe" src="about:blank" style="width: 100%; height: 100%; border: none;"></iframe>
            </div>
        </div>
    </div>
</div>

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

{{-- Edit Bushing Modal (iframe) --}}
<div class="modal fade" id="editBushingModal" tabindex="-1" aria-labelledby="editBushingModalLabel" aria-hidden="true">
    <div class="modal-dialog" style="max-width: 1400px; width: 95%; height: 90vh;">
        <div class="modal-content bg-gradient" style="height: 90vh;">
            <div class="modal-header">
                <h6 class="modal-title text-info" id="editBushingModalLabel">{{ __('Update Bushings List') }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 overflow-hidden" style="height: calc(90vh - 60px);">
                <iframe id="editBushingIframe" src="about:blank" style="width: 100%; height: 100%; border: none;"></iframe>
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
